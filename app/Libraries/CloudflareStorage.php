<?php

namespace App\Libraries;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use CodeIgniter\Files\File;

class CloudflareStorage
{
    protected $client;
    protected $bucket;
    protected $publicUrl;

    public function __construct()
    {
        $this->bucket    = env('R2_BUCKET');
        $this->publicUrl = env('R2_PUBLIC_URL');

        // Initialize the S3 Client pointed at Cloudflare
        $this->client = new S3Client([
            'version'     => 'latest',
            'region'      => 'auto', // R2 requires region to be 'auto'
            'endpoint'    => env('R2_ENDPOINT'),
            // 'use_path_style_endpoint' => true,
            'credentials' => [
                'key'    => env('R2_ACCESS_KEY'),
                'secret' => env('R2_SECRET_KEY'),
            ],
        ]);
    }

    /**
     * Uploads a CodeIgniter File to Cloudflare R2
     *
     * @param \CodeIgniter\Files\File|\CodeIgniter\HTTP\Files\UploadedFile $file
     * @param string $folder The subfolder in your bucket (e.g., 'podcasts' or 'covers')
     * @param string $slug Optional slug to use as the filename to allow overwriting
     * @return string|bool The relative Object Key if successful, false if failed
     */
    public function upload($file, string $folder = '', string $slug = '')
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return false;
        }

        // Determine the filename
        if (!empty($slug)) {
            // Keep the original extension (e.g., .png, .jpg) but use the slug as the name
            $ext = $file->getClientExtension() ?: $file->guessExtension();
            $newName = $slug . '.' . $ext;
        } else {
            // Fallback to random name if no slug is provided
            $newName = $file->getRandomName();
        }

        // Build the clean relative path (Object Key)
        $path = $folder ? trim($folder, '/') . '/' . $newName : $newName;

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $path,
                'SourceFile'  => $file->getTempName(),
                'ContentType' => $file->getMimeType(),
                // 'ACL' => 'public-read' is not needed for R2 if the bucket is set to public!
            ]);

            // 👉 CRITICAL ARCHITECTURE UPDATE: 
            // Return ONLY the relative path (e.g., 'categories/icons/my-slug.png')
            return $path;

        } catch (AwsException $e) {
            log_message('error', 'Cloudflare R2 Upload Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes a file from Cloudflare R2
     * * @param string|null $fileUrlOrKey Can accept an old absolute URL or a new relative Key
     */
    public function delete(?string $fileUrlOrKey): bool
    {
        if (empty($fileUrlOrKey)) {
            return true; // Nothing to delete
        }

        // 👉 BACKWARDS COMPATIBILITY FIX:
        // If the database still has old absolute URLs (https://media...), this strips the domain.
        // If the database has the new relative paths, this safely does nothing.
        $baseUrl = rtrim($this->publicUrl, '/') . '/';
        $key     = str_replace($baseUrl, '', $fileUrlOrKey);
        
        // Ensure no leading slash just in case
        $key     = ltrim($key, '/');

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);
            return true;
        } catch (AwsException $e) {
            log_message('error', 'Cloudflare R2 Delete Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteFolder(string $prefix): bool
    {
        if (empty($prefix)) return false;

        try {
            \Aws\S3\ObjectCopier::class; // Ensure AWS classes are loaded
            
            \Aws\S3\BatchDelete::fromListObjects($this->client, [
                'Bucket' => $this->bucket,
                'Prefix' => ltrim($prefix, '/')
            ])->delete();

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Cloudflare R2 Bulk Delete Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Uploads a raw optimized file path to Cloudflare R2
     *
     * @param string $filePath The absolute local path to the optimized file
     * @param string $folder The subfolder in your bucket (e.g., 'categories/icons')
     * @param string $fileName The exact filename to save it as (e.g., 'grace.webp')
     * @return string|bool The relative Object Key if successful, false if failed
     */
    public function uploadOptimized(string $filePath, string $folder = '', string $fileName = '')
    {
        if (!file_exists($filePath)) {
            return false;
        }

        // Build the clean relative path (Object Key)
        $path = $folder ? trim($folder, '/') . '/' . $fileName : $fileName;

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $path,
                'SourceFile'  => $filePath,
                'ContentType' => mime_content_type($filePath), // Automatically detects image/webp
            ]);

            return $path;

        } catch (AwsException $e) {
            log_message('error', 'Cloudflare R2 Optimized Upload Failed: ' . $e->getMessage());
            return false;
        }
    }

   /**
     * The Ultimate DRY Image Uploader.
     * Validates, compresses to WebP, uploads to R2, deletes the old file, and cleans up RAM.
     *
     * @param \CodeIgniter\Files\File|\CodeIgniter\HTTP\Files\UploadedFile|null $file
     * @param string $folder The R2 folder (e.g., 'categories/icons'). Leave empty for root.
     * @param string $slug The base name for the file. If empty, generates a random name.
     * @param string|null $oldFileUrl The previous URL to delete (if updating)
     * @return string|false Returns the new Object Key, or false on failure
     */
    public function optimizeAndUpload($file, string $folder = '', string $slug = '', ?string $oldFileUrl = null)
    {
        // 1. Validate the file
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return false;
        }

        // 2. Compress the image to WebP
        $optimizer = new \App\Libraries\ImageOptimizer();
        $optimizedPath = $optimizer->optimizeToWebp($file->getTempName(), 800, 80);

        if (!$optimizedPath) {
            log_message('error', 'Failed to compress image for slug: ' . ($slug ?: 'empty_slug'));
            return false;
        }

        // 3. Handle Empty Slug (Fallback to a random unique string)
        // If the slug is empty, we generate a random name like 'img_64b2a9f...' 
        $safeSlug = !empty($slug) ? $slug : uniqid('img_');
        $newIconName = $safeSlug . '.webp';

        // 4. Upload the optimized WebP to Cloudflare
        $newUrl = $this->uploadOptimized($optimizedPath, $folder, $newIconName);

        if ($newUrl) {
            // 5. Cleanly delete the old file IF it exists and the path actually changed
            if (!empty($oldFileUrl) && $oldFileUrl !== $newUrl) {
                $this->delete($oldFileUrl);
            }
        }

        // 6. Always delete the temporary optimized file from the local server
        unlink($optimizedPath);

        return $newUrl;
    }
}