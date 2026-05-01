<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

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

    protected function formatPodcastsWithAuthors($rawPodcasts)
    {
        if (empty($rawPodcasts)) return [];

        // 1. Check for the data saver flag globally!
        $isDataSaver = $this->request->getGet('data_saver') === 'true';

        $podcastIds = array_column($rawPodcasts, 'id');
        $db = \Config\Database::connect();
        
        // 2. Fetch ALL authors for these podcasts in one fast query
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

        // 3. Format to perfectly match the Flutter AudioModel
        $formatted = [];
        foreach ($rawPodcasts as $p) {
            
            // Apply Data Saver Logic dynamically
            $finalAudioUrl = $p['media_high_url'] ?? null;
            if ($isDataSaver && !empty($p['media_low_url'])) {
                $finalAudioUrl = $p['media_low_url']; 
            }

            $formatted[] = [
                'id'              => (int)$p['id'],
                'title'           => $p['title'],
                'authors'         => $authorsByPodcast[$p['id']] ?? [], // Nested list of authors
                'duration'        => $p['duration'] ?? '00:00', 
                'file_size_bytes' => isset($p['file_size_bytes']) ? (int)$p['file_size_bytes'] : null,
                'published_at'    => $p['created_at'] ?? date('Y-m-d H:i:s'),
                'category_name'   => $p['category_name'] ?? 'Uncategorized',
                'theme_id'        => isset($p['theme_id']) ? (int)$p['theme_id'] : null,
                'theme_text'      => $p['theme_text'] ?? null,
                'audio_url'       => $finalAudioUrl, // Dynamically assigned
                'cover_url'       => $p['cover_image_url'] ?? null,
                'listen_count'    => (int)($p['play_count'] ?? 0),
                'like_count'      => 0,
                'comment_count'   => 0,
                'reshare_count'   => 0,
            ];
        }

        return $formatted;
    }
}