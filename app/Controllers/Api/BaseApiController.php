<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class BaseApiController extends ResourceController
{

protected function getUserId()
    {
        $header = $this->request->getHeaderLine('Authorization');
        
        if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $token = $matches[1];
            try {
                $key = getenv('JWT_SECRET');
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
                return $decoded->uid;
            } catch (\Throwable $th) { // <--- CHANGED THIS LINE
                // Catches absolutely everything (expired tokens, missing secrets, type errors)
                return null;
            }
        }
        return null;
    }
    /**
     * Standardized Success Response
     * 
     * @param mixed $data The payload to send to the app
     * @param string $message A human-readable success message
     * @param int $statusCode HTTP Status Code (Default 200)
     */
    protected function sendSuccess($data = null, $message = 'Success', $statusCode = 200)
    {
        $response = [
            'status'  => 'success',
            'message' => $message,
        ];

        // Only attach the 'data' key if there is actual data to send
        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->respond($response, $statusCode);
    }

    /**
     * Standardized Error Response
     * 
     * @param string $message A human-readable error message
     * @param int $statusCode HTTP Status Code (Default 400 Bad Request)
     * @param mixed $errors Detailed validation errors (Optional)
     */
    protected function sendError($message = 'An error occurred', $statusCode = 400, $errors = null)
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        // Attach detailed validation errors (e.g., "Password must be 8 characters")
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->respond($response, $statusCode);
    }

    /**
     * Formats an array of raw podcasts from the database into the exact 
     * nested JSON structure required by the Flutter AudioModel.
     */
    protected function formatPodcastsWithAuthors_old_working($rawPodcasts)
    {
        if (empty($rawPodcasts)) return [];

        $podcastIds = array_column($rawPodcasts, 'id');
        $db = \Config\Database::connect();
        
        // Fetch ALL authors for these podcasts in one fast query
        $authorsQuery = $db->table('podcast_authors')
            ->select('podcast_authors.podcast_id, users.id, users.first_name, users.last_name, users.profile_image_url, users.bio')
            ->join('users', 'users.id = podcast_authors.author_id')
            ->whereIn('podcast_authors.podcast_id', $podcastIds)
            ->orderBy('podcast_authors.is_primary', 'DESC')
            ->get()
            ->getResultArray();

        $authorsByPodcast = [];
        foreach ($authorsQuery as $author) {
            $pid = $author['podcast_id'];
            $authorsByPodcast[$pid][] = [
                'id'        => (int)$author['id'],
                'name'      => trim($author['first_name'] . ' ' . $author['last_name']),
                'image_url' => $author['profile_image_url'],
                'bio'       => $author['bio']
            ];
        }

        $formatted = [];
        foreach ($rawPodcasts as $p) {
            $formatted[] = [
                'id'              => (int)$p['id'],
                'title'           => $p['title'],
                'authors'         => $authorsByPodcast[$p['id']] ?? [], // Nested Flutter AuthorModels
                'duration'        => '00:00', 
                'file_size_bytes' => isset($p['file_size_bytes']) ? (int)$p['file_size_bytes'] : null,
                'published_at'    => $p['created_at'] ?? $p['published_at'],
                'category_name'   => $p['category_name'] ?? 'Uncategorized',
                'theme_id'        => isset($p['theme_id']) ? (int)$p['theme_id'] : null,
                'theme_text'      => $p['theme_text'] ?? null,
                'audio_url'       => $p['media_high_url'] ?? null,
                'cover_url'       => $p['cover_image_url'] ?? null,
                'listen_count'    => (int)($p['play_count'] ?? 0),
                'like_count'      => 0,
                'comment_count'   => 0,
                'reshare_count'   => 0,
            ];
        }

        return $formatted;
    }

    protected function formatPodcastsWithAuthors($rawPodcasts,$usehls = false)
    {
        $usehls = true;
        if (empty($rawPodcasts)) return [];

        // 1. Check for the data saver flag globally!
        $isDataSaver = $this->request->getGet('data_saver') === 'true';

        $podcastIds = array_column($rawPodcasts, 'id');
        $db = \Config\Database::connect();
        
        // 2. Fetch ALL authors for these podcasts in one fast query
        $authorsQuery = $db->table('podcast_authors')
            ->select('podcast_authors.podcast_id,users.title, users.id, users.first_name, users.last_name, users.profile_image_url, users.bio')
            ->join('users', 'users.id = podcast_authors.author_id')
            ->whereIn('podcast_authors.podcast_id', $podcastIds)
            ->orderBy('podcast_authors.is_primary', 'DESC')
            ->get()
            ->getResultArray();

        $authorsByPodcast = [];
        foreach ($authorsQuery as $author) {
            $pid = $author['podcast_id'];
            $titlePrefix = !empty($author['title']) ? trim($author['title']) . ' ' : '';
            $authorsByPodcast[$pid][] = [
                'id'        => (int)$author['id'],
                'name'      => trim($titlePrefix . $author['first_name'] . ' ' . $author['last_name']),
                'image_url' => media_url($author['profile_image_url']),
                'bio'       => $author['bio'],
                'title'     => !empty($author['title']) ? $author['title'] : ''
            ];
        }

        $userId =  $this->getUserId(); // Ensure your getUserId() doesn't throw a 401 for guests!
    
    $userLikes = [];
    $userBookmarks = [];

    if ($userId && !empty($podcastIds)) {
        // Fetch all likes for this user for just these 15 podcasts
        $likes = $db->table('podcast_likes')
                    ->select('podcast_id')
                    ->where('user_id', $userId)
                    ->whereIn('podcast_id', $podcastIds)
                    ->get()
                    ->getResultArray();
        
        // Converts array of arrays to a simple list of IDs: [1, 5, 12]
        $userLikes = array_column($likes, 'podcast_id');

        // Fetch all bookmarks for this user
        $bookmarks = $db->table('bookmarks')
                    ->select('podcast_id')
                    ->where('user_id', $userId)
                    ->whereIn('podcast_id', $podcastIds)
                    ->get()
                    ->getResultArray();
        
        $userBookmarks = array_column($bookmarks, 'podcast_id');
    }

        // 3. Format to perfectly match the Flutter AudioModel
        $formatted = [];
        foreach ($rawPodcasts as $p) {
            
            // Apply Data Saver Logic dynamically
            $finalAudioUrl = $p['media_high_url'] ?? null;
            
            $rawAudioUrl = null;

            if ($usehls) {
                // User requested HLS Streaming
                $rawAudioUrl = ($isDataSaver && !empty($p['media_low_url'])) 
                                ? $p['media_low_url'] 
                                : ($p['media_high_url'] ?? null);
                $finalAudioUrl = getenv('R2_PUBLIC_URL') . '/' . $rawAudioUrl;
                // $finalAudioUrl = $this->getSecureAudioUrl($rawAudioUrl);
            } else {
                // User requested standard MP3
                $rawAudioUrl = ($isDataSaver && !empty($p['master_low_url'])) 
                                ? $p['master_low_url'] 
                                : ($p['master_high_url'] ?? null);
                                $finalAudioUrl = $this->getSecureAudioUrl($rawAudioUrl);
            }
            // if (empty($rawAudioUrl)) {
            //     $rawAudioUrl = $p['media_high_url'] ?? $p['master_high_url'] ?? null;
            // }

            // $download_url = $this->getSecureAudioUrl($rawAudioUrl);
            $mp3Path = ($isDataSaver && !empty($p['master_low_url'])) 
                        ? $p['master_low_url'] 
                        : ($p['master_high_url'] ?? null);
                        
        // Calls your AWS SDK method to generate the 2-hour link
        $downloadUrl = $this->getSecureAudioUrl($mp3Path);

            $formatted[] = [
                // 'id'              => (int)$p['id'],
                'slug'           => $p['slug'],
                'title'           => $p['title'],
                'authors'         => $authorsByPodcast[$p['id']] ?? [], // Nested list of authors
                'duration'        => $p['duration'] ?? '00:00', 
                'file_size_bytes' => isset($p['file_size_bytes']) ? (int)$p['file_size_bytes'] : null,
                'published_at'    => $p['created_at'] ?? date('Y-m-d H:i:s'),
                'category_name'   => $p['category_name'] ?? 'Uncategorized',
                'theme_id'        => isset($p['theme_id']) ? (int)$p['theme_id'] : null,
                'theme_text'      => $p['theme_text'] ?? null,
                'audio_url'       => $finalAudioUrl, // Dynamically assigned
                'download_url'    => $downloadUrl, // Dynamically assigned
                'is_hls'          => $usehls,
                'cover_url'       => media_url($p['cover_image_url']) ?? null,
                'listen_count'    => (int)($p['play_count'] ?? 0),
                'like_count'      => (int)($p['like_count'] ?? 0),
                'bookmark_count'  => (int)($p['bookmark_count'] ?? 0),
                'comment_count'   => (int)($p['comment_count'] ?? 0),
                'reshare_count'   => 0,
                'is_liked'        => in_array($p['id'], $userLikes),
                'is_bookmarked'   => in_array($p['id'], $userBookmarks),
            ];
        }

        return $formatted;
    }


    protected function getSecureAudioUrl($rawUrl)
    {
        if (empty($rawUrl)) return null;

        // 1. Extract the exact file path from the URL
        // E.g., "https://pub-mycdn.r2.dev/audio/message.mp3" -> "audio/message.mp3"
        $parsedUrl = parse_url($rawUrl);
        $objectKey = ltrim($parsedUrl['path'], '/');

        try {
            // 2. Initialize the S3 Client for Cloudflare R2
            $s3Client = new S3Client([
                'region'      => 'auto', // R2 always uses 'auto'
                'endpoint'    => getenv('R2_ENDPOINT'),
                'version'     => 'latest',
                'credentials' => [
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
            
        } catch (AwsException $e) {
            // If Cloudflare is down or keys are wrong, log it so you can debug,
            // but don't crash the entire API response.
            log_message('error', '[R2 URL Signing Failed] ' . $e->getMessage());
            return null;
        }}
}