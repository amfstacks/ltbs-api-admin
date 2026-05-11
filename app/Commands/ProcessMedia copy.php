<?php

// namespace App\Commands;

// use CodeIgniter\CLI\BaseCommand;
// use CodeIgniter\CLI\CLI;

// class ProcessMedia extends BaseCommand
// {
//     protected $group       = 'Media';
//     protected $name        = 'media:process';
//     protected $description = 'Checks the media_queue table and processes HLS conversions.';

//     public function run(array $params)
//     {
//         // 1. Unlimited time and memory for CLI processing
//         set_time_limit(0); 
//         ini_set('memory_limit', '2048M'); 

//         $db = \Config\Database::connect();
        
//         // 2. Check for pending jobs (Process 1 at a time to save CPU)
//         $job = $db->table('media_queue')->where('status', 'pending')->orderBy('created_at', 'ASC')->get()->getRow();

//         if (!$job) {
//             CLI::write('No pending jobs found.', 'yellow');
//             return;
//         }

//         CLI::write("Starting Job ID: {$job->id} for Slug: {$job->slug}", 'green');

//         // 3. Lock the job so no other worker picks it up
//         $db->table('media_queue')->where('id', $job->id)->update(['status' => 'processing']);

//         $slug = $job->slug;
//         $originalMp3 = WRITEPATH . "uploads/vault/{$slug}.mp3";

//         if (!file_exists($originalMp3)) {
//             $db->table('media_queue')->where('id', $job->id)->update(['status' => 'failed', 'error_log' => 'Source MP3 not found.']);
//             CLI::error("Source file missing for {$slug}");
//             return;
//         }

//         try {
//             // 4. Run the Heavy Lifting (This is the logic we perfected earlier)
//             $mediaResults = $this->runHeavyProcessor($originalMp3, $slug);

//             // 5. Update the Database with the Cloudflare URLs!
//             $db->table('podcasts')->where('id', $job->podcast_id)->update([
//                 'master_high_url' => $mediaResults['master_high'],
//                 'master_low_url'  => $mediaResults['master_low'],
//                 'media_high_url'  => $mediaResults['hls_high'],
//                 'media_low_url'   => $mediaResults['hls_low'],
//                 'status'          => 'published' // Make it live!
//             ]);

//             // 6. Mark Job Complete and clean up Vault
//             $db->table('media_queue')->where('id', $job->id)->update(['status' => 'completed']);
//             unlink($originalMp3); 
            
//             CLI::write("Job {$job->id} Completed Successfully!", 'green');

//         } catch (\Throwable $e) {
//             // Rollback and log failure
//             $db->table('media_queue')->where('id', $job->id)->update([
//                 'status' => 'failed',
//                 'attempts' => $job->attempts + 1,
//                 'error_log' => $e->getMessage()
//             ]);
//             CLI::error("Job Failed: " . $e->getMessage());
//         }
//     }

//     private function runHeavyProcessor($originalMp3Local, $slug)
//     {
//         helper('media'); // Load our delete_directory_safely helper
//         $ffmpegPath = env('ffmpeg.path', 'ffmpeg');

//         $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
//         $hlsHighDir = $baseDir . 'hls_high/';
//         $hlsLowDir  = $baseDir . 'hls_low/';
//         if (!is_dir($hlsHighDir)) mkdir($hlsHighDir, 0777, true);
//         if (!is_dir($hlsLowDir)) mkdir($hlsLowDir, 0777, true);

//         $lowMp3Local = $baseDir . 'low_quality.mp3';

//         $runFFmpeg = function($command) {
//             exec($command . " 2>&1", $output, $returnCode);
//             if ($returnCode !== 0) throw new \Exception("FFmpeg Error: " . implode("\n", $output));
//         };

//         // FFmpeg Conversions
//         $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($lowMp3Local));
//         $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 128k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsHighDir . "seg_%03d.ts") . " " . escapeshellarg($hlsHighDir . "index.m3u8"));
//         $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 64k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsLowDir . "seg_%03d.ts") . " " . escapeshellarg($hlsLowDir . "index.m3u8"));

//         // Cloudflare Upload
//         $cloudflare = new \App\Libraries\CloudflareStorage();
//         // Assume you add a helper to upload by local file path to your CF library
//         // ... Upload the files ...
        

//          $s3Client = new \Aws\S3\S3Client([
//                 'region'      => 'auto',
//                 'endpoint'    => getenv('R2_ENDPOINT'),
//                 'version'     => 'latest',
//                 'credentials' => [
//                     'key'    => getenv('R2_ACCESS_KEY'),
//                     'secret' => getenv('R2_SECRET_KEY'),
//                 ],
//                 'http' => [
//                     'connect_timeout' => 30,
//                     'timeout'         => 300, 
//                 ]
//             ]);
            
//             $bucket = getenv('R2_BUCKET');

//             // Upload single files
//             $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/high/{$slug}.mp3", 'SourceFile' => $originalMp3Local, 'ContentType' => 'audio/mpeg']);
//             $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/low/{$slug}.mp3", 'SourceFile' => $lowMp3Local, 'ContentType' => 'audio/mpeg']);

//             // Upload Folders
//             $this->uploadDirectoryToR2($hlsHighDir, "podcasts/hls/high/{$slug}/", $s3Client);
//             $this->uploadDirectoryToR2($hlsLowDir, "podcasts/hls/low/{$slug}/", $s3Client);
//         delete_directory_safely($baseDir);

//         return [
//             'master_high' => "podcasts/raw/high/{$slug}.mp3",
//             'master_low'  => "podcasts/raw/low/{$slug}.mp3",
//             'hls_high'    => "podcasts/hls/high/{$slug}/index.m3u8",
//             'hls_low'     => "podcasts/hls/low/{$slug}/index.m3u8",
//         ];
//     }

//     private function uploadDirectoryToR2($dir, $r2Path, $client) {
//     foreach (glob($dir . "*") as $file) {
//         $client->putObject([
//             'Bucket'      => getenv('R2_BUCKET'),
//             'Key'         => $r2Path . basename($file),
//             'SourceFile'  => $file,
//             'ContentType' => (pathinfo($file, PATHINFO_EXTENSION) === 'm3u8') ? 'application/x-mpegURL' : 'video/MP2T'
//         ]);
//     }
// }
// }