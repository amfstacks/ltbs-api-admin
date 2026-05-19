<?php

if (!function_exists('delete_directory_safely')) {
    /**
     * Recursively deletes a directory and its contents.
     * Built as a global function so it survives PHP fatal crashes.
     * * @param string $dir The path to the directory
     */
    function delete_directory_safely($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                    delete_directory_safely($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dir);
    }
}

// if (!function_exists('media_url')) {
//     /**
//      * Formats a database relative path into a full Cloudflare R2 URL.
//      * @param string $path The relative path from the database
//      */
//     function media_url($path)
//     {
//         if (empty($path)) return '';
        
//         // If it's already an absolute URL (for older records), just return it
//         if (strpos($path, 'http') === 0) {
//             return $path;
//         }

//         // Otherwise, append the public R2 domain cleanly
//         return rtrim(getenv('R2_PUBLIC_URL'), '/') . '/' . ltrim($path, '/');
//     }
// }

if (!function_exists('media_url')) {
    /**
     * Formats a database relative path into a full Cloudflare R2 URL.
     * * @param string $path The relative path from the database
     * @param string|int|null $cacheBuster A specific version string or timestamp
     */
    function media_url($path, $cacheBuster = null)
    {
        if (empty($path)) return '';
        
        // Handle older absolute URLs
        $url = (strpos($path, 'http') === 0) 
            ? $path 
            : rtrim(getenv('R2_PUBLIC_URL'), '/') . '/' . ltrim($path, '/');

        // Append the cache buster if provided
        if ($cacheBuster) {
            $url .= '?v=' . urlencode((string)$cacheBuster);
        }

        return $url;
    }
}

if (!function_exists('secure_audio_url')) {
    /**
     * Generates a secure/presigned URL for audio files via Cloudflare R2.
     * * @param string|null $path The path from the database (relative or absolute)
     * @return string
     */
    function secure_audio_url($path)
    {
        if (empty($path)) {
            return '';
        }

        // 1. Determine the exact Object Key.
        // If it's an old absolute URL, extract the path. Otherwise, it's already the exact key!
        if (strpos($path, 'http') === 0) {
            $parsedUrl = parse_url($path);
            $objectKey = ltrim($parsedUrl['path'], '/');
        } else {
            $objectKey = ltrim($path, '/');
        }

        try {
            // 2. Initialize the S3 Client for Cloudflare R2
            // (Using fully qualified namespaces since we are in a helper file)
            $s3Client = new \Aws\S3\S3Client([
                'region'                  => 'auto', // R2 always uses 'auto'
                'endpoint'                => getenv('R2_ENDPOINT'),
                'version'                 => 'latest',
                'use_path_style_endpoint' => true,   // Fixed the cURL DNS issue!
                'credentials'             => [
                    'key'    => getenv('R2_ACCESS_KEY'),
                    'secret' => getenv('R2_SECRET_KEY'),
                ],
            ]);

            // 3. Create the command to fetch the file
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => getenv('R2_BUCKET'),
                'Key'    => $objectKey,
            ]);

            // 4. Generate the signed URL valid for 2 hours (+2 hours)
            $request = $s3Client->createPresignedRequest($cmd, '+2 hours');

            // Return the secure, time-expiring URL!
            return (string) $request->getUri();
            
        } catch (\Aws\Exception\AwsException $e) {
            // Log it so you can debug, but don't crash the UI
            log_message('error', '[R2 URL Signing Failed] ' . $e->getMessage());
            return '';
        }
    }
}