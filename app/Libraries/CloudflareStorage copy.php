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
     * @return string|bool The public URL if successful, false if failed
     */
    public function upload($file, string $folder = '')
    {
        if (!$file->isValid() || $file->hasMoved()) {
            return false;
        }

        // Generate a secure, random file name to prevent overwrites
        $newName = $file->getRandomName();
        $path    = $folder ? trim($folder, '/') . '/' . $newName : $newName;

        try {
            $this->client->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $path,
                'SourceFile'  => $file->getTempName(),
                'ContentType' => $file->getMimeType(),
                // 'ACL' => 'public-read' is not needed for R2 if the bucket is set to public!
            ]);

            // Return the absolute public URL so the mobile app can stream it!
            return $this->publicUrl . '/' . $path;

        } catch (AwsException $e) {
            log_message('error', 'Cloudflare R2 Upload Failed: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(?string $fileUrl): bool
    {
        if (empty($fileUrl)) {
            return true; // Nothing to delete
        }

        // We need to extract the exact bucket path (the "Key") from the full URL.
        // E.g., Changes "https://pub-xyz.r2.dev/podcasts/covers/image.jpg" to "podcasts/covers/image.jpg"
        $baseUrl = rtrim($this->publicUrl, '/') . '/';
        $key     = str_replace($baseUrl, '', $fileUrl);

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
            // S3/R2 does not have a single "delete folder" command. 
            // We must use the built-in deleteMatchingObjects helper.
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
}