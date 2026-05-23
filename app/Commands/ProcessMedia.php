<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessMedia extends BaseCommand
{
    protected $group       = 'Media';
    protected $name        = 'media:process';
    protected $description = 'Checks the media_queue table and processes HLS conversions.';


    public function run(array $params)
    {

        date_default_timezone_set('Africa/Lagos');
        // 1. Unlimited time and memory for CLI processing
        set_time_limit(0); 
        ini_set('memory_limit', '2048M'); 

        $db = \Config\Database::connect();

        // ==========================================
        // FETCH JOB: Only get pending jobs where start_time has arrived
        // ==========================================
        $job = $db->table('media_queue')
                  ->where('status', 'pending')
                  ->where('start_time <=', date('Y-m-d H:i:s')) // Crucial!
                  ->orderBy('start_time', 'ASC') // Oldest eligible job first
                  ->limit(1)
                  ->get()
                  ->getRow();

        if (!$job) {
            CLI::write('No pending jobs found.', 'yellow');
            return;
        }

        // ==========================================
        // VULNERABILITY 2 FIXED: The Atomic Lock
        // ==========================================
        // Try to strictly claim the job before any other cron worker does.
        $db->query("UPDATE media_queue SET status = 'processing' WHERE id = ? AND status = 'pending'", [$job->id]);
        
        if ($db->affectedRows() === 0) {
            CLI::write("Job {$job->id} was just claimed by another worker. Exiting to prevent duplication.", 'yellow');
            return; 
        }

        CLI::write("Starting Job ID: {$job->id} for Slug: {$job->slug}", 'green');

        $slug = $job->slug;
        $originalMp3 = WRITEPATH . "uploads/vault/{$slug}.mp3";

        if (!file_exists($originalMp3)) {
            $db->table('media_queue')->where('id', $job->id)->update(['status' => 'failed', 'error_log' => 'Source MP3 not found.']);
            CLI::error("Source file missing for {$slug}. Cannot process.");
            return;
        }

        try {
            // 4. Run the Heavy Lifting (This could take 5-20+ minutes!)
            $mediaResults = $this->runHeavyProcessor($originalMp3, $slug);

            // ==========================================
            // VULNERABILITY 1 FIXED: Wake up the Database!
            // ==========================================
            // The MySQL connection likely timed out while FFmpeg was working. Reconnect!
            $db->reconnect();

            // 5. Update the Database with the Cloudflare URLs!
            $db->table('podcasts')->where('id', $job->podcast_id)->update([
                'master_high_url' => $mediaResults['master_high'],
                'master_low_url'  => $mediaResults['master_low'],
                'media_high_url'  => $mediaResults['hls_high'],
                'media_low_url'   => $mediaResults['hls_low'],
                'ffmeg_status'    => 'completed', // Make it live!
                'status'          => 'in_review'  // Make it available for review!
            ]);

            // 6. Mark Job Complete and clean up the Vault (we don't need the local master anymore!)
            $db->table('media_queue')->where('id', $job->id)->update(['status' => 'completed']);
            if (file_exists($originalMp3)) {
                unlink($originalMp3); 
            }
            
            CLI::write("Job {$job->id} Completed Successfully!", 'green');

        } catch (\Throwable $e) {
            
            // ==========================================
            // THE ORPHAN CLEANUP ROLLBACK
            // ==========================================
            
            // Wake up the DB here too, just in case the timeout is what caused the Exception!
            $db->reconnect();
            
            CLI::error("Job Failed: " . $e->getMessage());
            CLI::write("Initiating rollback to remove orphan files...", 'yellow');

            // // 1. Mark Job as Failed so we can manually or automatically retry it
            // $db->table('media_queue')->where('id', $job->id)->update([
            //     'status'    => 'failed',
            //     'attempts'  => $job->attempts + 1,
            //     'error_log' => $e->getMessage()
            // ]);

            // // 2. Wipe the local temporary processing folder
            // helper('media'); // Ensure our helper is loaded
            // $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
            // if (function_exists('delete_directory_safely')) {
            //     delete_directory_safely($baseDir);
            // }

            // // 3. Wipe any partially uploaded files from Cloudflare
            // try {
            //     $cloudflare = new \App\Libraries\CloudflareStorage();
            //     $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
            //     $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
            //     $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
            //     $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
            //     CLI::write("Cloudflare orphaned files successfully deleted.", 'green');
            // } catch (\Exception $cfError) {
            //     CLI::error("Notice: Could not reach Cloudflare to delete orphans: " . $cfError->getMessage());
            // }
            helper('media'); 
            $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
            if (function_exists('delete_directory_safely')) {
                delete_directory_safely($baseDir);
                CLI::write("Local temporary processing folder deleted.", 'green');
            }

            // 2. Wipe any partially uploaded files from Cloudflare
            try {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
                $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
                $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
                $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
                CLI::write("Cloudflare orphaned files successfully deleted.", 'green');
            } catch (\Exception $cfError) {
                CLI::error("Notice: Could not reach Cloudflare to delete orphans: " . $cfError->getMessage());
            }

            // 3. The Smart Retry Logic (Max 3 Attempts)
            $newAttemptCount = $job->attempts + 1;
            
            if ($newAttemptCount < 3) {
                // If it hasn't failed 3 times yet, put it back in the queue as 'pending'
                $db->table('media_queue')->where('id', $job->id)->update([
                    'status'    => 'pending',
                    'attempts'  => $newAttemptCount,
                    'error_log' => "Retry {$newAttemptCount} scheduled due to error: " . $e->getMessage()
                ]);
                CLI::write("Job reverted to PENDING (Attempt {$newAttemptCount}/3). It will retry automatically.", 'yellow');
            } else {
                // If it failed 3 times, mark it as completely failed so the queue doesn't freeze forever
                $db->table('media_queue')->where('id', $job->id)->update([
                    'status'    => 'failed',
                    'attempts'  => $newAttemptCount,
                    'error_log' => 'Final Failure after 3 attempts: ' . $e->getMessage()
                ]);
                
                // Also update the podcast table so the admin knows it failed in the UI!
                $db->table('podcasts')->where('id', $job->podcast_id)->update([
                    'ffmeg_status' => 'cancelled' // or 'failed' if you add it to your enum
                ]);
                
                CLI::error("Job FAILED permanently after 3 attempts. Queue moving on.");
            }

            // Note: We DO NOT unlink($originalMp3) here. We leave it in the vault 
            // so the queue can try again later!
        }
    }
    public function run_old(array $params)
    {
        // 1. Unlimited time and memory for CLI processing
        set_time_limit(0); 
        ini_set('memory_limit', '2048M'); 

        $db = \Config\Database::connect();
        
        // 2. Check for pending jobs (Process 1 at a time to save CPU)
        // $job = $db->table('media_queue')->where('status', 'pending')->orderBy('created_at', 'ASC')->get()->getRow();

// ==========================================
        // FETCH JOB: Only get pending jobs where start_time has arrived
        // ==========================================
        $job = $db->table('media_queue')
                  ->where('status', 'pending')
                  ->where('start_time <=', date('Y-m-d H:i:s')) // Crucial!
                  ->orderBy('start_time', 'ASC') // Oldest eligible job first
                  ->limit(1)
                  ->get()
                  ->getRow();


        if (!$job) {
            CLI::write('No pending jobs found.', 'yellow');
            return;
        }

        CLI::write("Starting Job ID: {$job->id} for Slug: {$job->slug}", 'green');

        // 3. Lock the job so no other worker picks it up
        $db->table('media_queue')->where('id', $job->id)->update(['status' => 'processing']);

        $slug = $job->slug;
        $originalMp3 = WRITEPATH . "uploads/vault/{$slug}.mp3";

        if (!file_exists($originalMp3)) {
            $db->table('media_queue')->where('id', $job->id)->update(['status' => 'failed', 'error_log' => 'Source MP3 not found.']);
            CLI::error("Source file missing for {$slug}. Cannot process.");
            return;
        }

        try {
            // 4. Run the Heavy Lifting
            $mediaResults = $this->runHeavyProcessor($originalMp3, $slug);

            // 5. Update the Database with the Cloudflare URLs!
            $db->table('podcasts')->where('id', $job->podcast_id)->update([
                'master_high_url' => $mediaResults['master_high'],
                'master_low_url'  => $mediaResults['master_low'],
                'media_high_url'  => $mediaResults['hls_high'],
                'media_low_url'   => $mediaResults['hls_low'],
                'ffmeg_status'          => 'completed', // Make it live!
                'status'          => 'in_review' // Make it available for review!
            ]);

            // 6. Mark Job Complete and clean up the Vault (we don't need the local master anymore!)
            $db->table('media_queue')->where('id', $job->id)->update(['status' => 'completed']);
            if (file_exists($originalMp3)) {
                unlink($originalMp3); 
            }
            
            CLI::write("Job {$job->id} Completed Successfully!", 'green');

        } catch (\Throwable $e) {
            
            // ==========================================
            // THE ORPHAN CLEANUP ROLLBACK
            // ==========================================
            CLI::error("Job Failed: " . $e->getMessage());
            CLI::write("Initiating rollback to remove orphan files...", 'yellow');

            // 1. Mark Job as Failed so we can manually or automatically retry it
            $db->table('media_queue')->where('id', $job->id)->update([
                'status' => 'failed',
                'attempts' => $job->attempts + 1,
                'error_log' => $e->getMessage()
            ]);

            // 2. Wipe the local temporary processing folder
            helper('media'); // Ensure our helper is loaded
            $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
            if (function_exists('delete_directory_safely')) {
                delete_directory_safely($baseDir);
            }

            // 3. Wipe any partially uploaded files from Cloudflare
            try {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
                $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
                $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
                $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
                CLI::write("Cloudflare orphaned files successfully deleted.", 'green');
            } catch (\Exception $cfError) {
                CLI::error("Notice: Could not reach Cloudflare to delete orphans: " . $cfError->getMessage());
            }

            // Note: We DO NOT unlink($originalMp3) here. We leave it in the vault 
            // so the queue can try again later!
        }
    }

    private function runHeavyProcessor_old_working($originalMp3Local, $slug)
    {
        helper('media'); // Load our delete_directory_safely helper
        $ffmpegPath = env('ffmpeg.path', 'ffmpeg');

        $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
        $hlsHighDir = $baseDir . 'hls_high/';
        $hlsLowDir  = $baseDir . 'hls_low/';
        
        if (!is_dir($hlsHighDir)) mkdir($hlsHighDir, 0777, true);
        if (!is_dir($hlsLowDir)) mkdir($hlsLowDir, 0777, true);

        $lowMp3Local = $baseDir . 'low_quality.mp3';

        $runFFmpeg = function($command) {
            exec($command . " 2>&1", $output, $returnCode);
            if ($returnCode !== 0) throw new \Exception("FFmpeg Error: " . implode("\n", $output));
        };

        // FFmpeg Conversions
        CLI::write("Running FFmpeg conversions...", 'white');
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($lowMp3Local));
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 128k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsHighDir . "seg_%03d.ts") . " " . escapeshellarg($hlsHighDir . "index.m3u8"));
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 64k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsLowDir . "seg_%03d.ts") . " " . escapeshellarg($hlsLowDir . "index.m3u8"));

        // Cloudflare Upload
        CLI::write("Uploading to Cloudflare R2...", 'white');
        $s3Client = new \Aws\S3\S3Client([
            'region'      => 'auto',
            'endpoint'    => getenv('R2_ENDPOINT'),
            'version'     => 'latest',
            'credentials' => [
                'key'    => getenv('R2_ACCESS_KEY'),
                'secret' => getenv('R2_SECRET_KEY'),
            ],
            'http' => [
                'connect_timeout' => 30,
                'timeout'         => 300, 
            ]
        ]);
        
        $bucket = getenv('R2_BUCKET');

        // Upload single files
        $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/high/{$slug}.mp3", 'SourceFile' => $originalMp3Local, 'ContentType' => 'audio/mpeg']);
        $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/low/{$slug}.mp3", 'SourceFile' => $lowMp3Local, 'ContentType' => 'audio/mpeg']);

        // Upload Folders
        $this->uploadDirectoryToR2($hlsHighDir, "podcasts/hls/high/{$slug}/", $s3Client);
        $this->uploadDirectoryToR2($hlsLowDir, "podcasts/hls/low/{$slug}/", $s3Client);
        
        // Clean up local processing folder
        delete_directory_safely($baseDir);

        return [
            'master_high' => "podcasts/raw/high/{$slug}.mp3",
            'master_low'  => "podcasts/raw/low/{$slug}.mp3",
            'hls_high'    => "podcasts/hls/high/{$slug}/index.m3u8",
            'hls_low'     => "podcasts/hls/low/{$slug}/index.m3u8",
        ];
    }

    private function uploadDirectoryToR2_old_working($dir, $r2Path, $client) {
        foreach (glob($dir . "*") as $file) {
            $client->putObject([
                'Bucket'      => getenv('R2_BUCKET'),
                'Key'         => $r2Path . basename($file),
                'SourceFile'  => $file,
                'ContentType' => (pathinfo($file, PATHINFO_EXTENSION) === 'm3u8') ? 'application/x-mpegURL' : 'video/MP2T'
            ]);
        }
    }
    private function runHeavyProcessor($originalMp3Local, $slug)
    {
        helper('media'); // Load our delete_directory_safely helper
        $ffmpegPath = env('ffmpeg.path', 'ffmpeg');

        $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
        $hlsHighDir = $baseDir . 'hls_high/';
        $hlsLowDir  = $baseDir . 'hls_low/';
        
        if (!is_dir($hlsHighDir)) mkdir($hlsHighDir, 0777, true);
        if (!is_dir($hlsLowDir)) mkdir($hlsLowDir, 0777, true);

        $lowMp3Local = $baseDir . 'low_quality.mp3';

        $runFFmpeg = function($command) {
            exec($command . " 2>&1", $output, $returnCode);
            if ($returnCode !== 0) throw new \Exception("FFmpeg Error: " . implode("\n", $output));
        };

        // FFmpeg Conversions
        CLI::write("Running FFmpeg conversions...", 'white');
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($lowMp3Local));
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 128k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsHighDir . "seg_%03d.ts") . " " . escapeshellarg($hlsHighDir . "index.m3u8"));
        $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 64k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsLowDir . "seg_%03d.ts") . " " . escapeshellarg($hlsLowDir . "index.m3u8"));

        // Cloudflare Upload Setup
        CLI::write("Connecting to Cloudflare R2...", 'white');
        $s3Client = new \Aws\S3\S3Client([
            'region'      => 'auto',
            'endpoint'    => getenv('R2_ENDPOINT'),
            'version'     => 'latest',
            'use_path_style_endpoint' => true, // Ensure this is true to prevent DNS hanging!
            'credentials' => [
                'key'    => getenv('R2_ACCESS_KEY'),
                'secret' => getenv('R2_SECRET_KEY'),
            ],
            'http' => [
                'connect_timeout' => 10,  // Fail fast if connection drops (10s instead of 30s)
                'timeout'         => 60,  // 60s max per file instead of 300s
            ]
        ]);
        
        $bucket = getenv('R2_BUCKET');

        // DRY Helper function for uploading with retries
        $uploadWithRetry = function($key, $sourceFile, $contentType) use ($s3Client, $bucket) {
            $retries = 3;
            while ($retries > 0) {
                try {
                    $s3Client->putObject([
                        'Bucket'      => $bucket, 
                        'Key'         => $key, 
                        'SourceFile'  => $sourceFile, 
                        'ContentType' => $contentType
                    ]);
                    return true; // Success!
                } catch (\Exception $e) {
                    $retries--;
                    if ($retries === 0) {
                        throw new \Exception("Failed to upload {$key} after 3 attempts. Error: " . $e->getMessage());
                    }
                    CLI::write("  [Network Error] Retrying {$key}... ({$retries} attempts left)", 'yellow');
                    sleep(2); // Wait 2 seconds before hammering the server again
                }
            }
        };

        // Upload Single Master Files
        CLI::write("Uploading Master High Quality MP3...", 'cyan');
        $uploadWithRetry("podcasts/raw/high/{$slug}.mp3", $originalMp3Local, 'audio/mpeg');
        
        CLI::write("Uploading Master Data Saver MP3...", 'cyan');
        $uploadWithRetry("podcasts/raw/low/{$slug}.mp3", $lowMp3Local, 'audio/mpeg');

        // Upload Folders (Pass the closure to use the retry logic)
        CLI::write("Uploading High Quality HLS Segments...", 'cyan');
        $this->uploadDirectoryToR2($hlsHighDir, "podcasts/hls/high/{$slug}/", $uploadWithRetry);
        
        CLI::write("Uploading Data Saver HLS Segments...", 'cyan');
        $this->uploadDirectoryToR2($hlsLowDir, "podcasts/hls/low/{$slug}/", $uploadWithRetry);
        
        // Clean up local processing folder
        delete_directory_safely($baseDir);

        return [
            'master_high' => "podcasts/raw/high/{$slug}.mp3",
            'master_low'  => "podcasts/raw/low/{$slug}.mp3",
            'hls_high'    => "podcasts/hls/high/{$slug}/index.m3u8",
            'hls_low'     => "podcasts/hls/low/{$slug}/index.m3u8",
        ];
    }

    private function uploadDirectoryToR2($dir, $r2Path, $uploadClosure) {
        $files = glob($dir . "*");
        $totalFiles = count($files);
        $current = 0;

        foreach ($files as $file) {
            $current++;
            $fileName = basename($file);
            $contentType = (pathinfo($file, PATHINFO_EXTENSION) === 'm3u8') ? 'application/x-mpegURL' : 'video/MP2T';
            
            // Print progress so the terminal doesn't look frozen!
            CLI::write("  -> Uploading segment {$current} of {$totalFiles}: {$fileName}", 'dark_gray');
            
            // Trigger the retry closure from the parent function
            $uploadClosure($r2Path . $fileName, $file, $contentType);
        }
    }
}