<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use CodeIgniter\API\ResponseTrait;

class LibraryController extends BaseApiController
{
    /**
     * Helper to get the logged-in User ID from the JWT request header
     */
    private function getUserId()
    {
        $header = $this->request->getHeaderLine('Authorization');
        preg_match('/Bearer\s(\S+)/', $header, $matches);
        $token = $matches[1];
        
        $key = getenv('JWT_SECRET');
        $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
        
        return $decoded->uid;
    }

    /**
     * POST /api/v1/library/bookmarks/toggle/{slug}
     * Adds or removes a bookmark for the logged-in user
     */
    public function toggleBookmark($slug)
    {
        $userId = $this->getUserId();
        $db = \Config\Database::connect();
        
        // 1. Get the podcast ID from the slug
        $podcast = $db->table('podcasts')->where('slug', $slug)->get()->getRowArray();
        if (!$podcast) {
            return $this->sendError('Teaching not found', 404);
        }

        // 2. Check if it is already bookmarked
        $existing = $db->table('bookmarks')
            ->where('user_id', $userId)
            ->where('podcast_id', $podcast['id'])
            ->get()->getRowArray();

        if ($existing) {
            // Remove Bookmark
            $db->table('bookmarks')->where(['user_id' => $userId, 'podcast_id' => $podcast['id']])->delete();
            return $this->sendSuccess(['is_bookmarked' => false], 'Removed from library');
        } else {
            // Add Bookmark
            $db->table('bookmarks')->insert(['user_id' => $userId, 'podcast_id' => $podcast['id']]);
            return $this->sendSuccess(['is_bookmarked' => true], 'Added to library');
        }
    }

    /**
     * GET /api/v1/library/bookmarks
     * Fetches all teachings the user has saved
     */
    public function bookmarks()
    {
        $userId = $this->getUserId();
        $podcastModel = new PodcastModel();

        // Join the bookmarks table to filter only saved items
        $rawPodcasts = $podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            ->join('bookmarks', 'bookmarks.podcast_id = podcasts.id')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('bookmarks.user_id', $userId)
            ->where('podcasts.status', 'published')
            ->orderBy('bookmarks.created_at', 'DESC') // Newest bookmarks first
            ->findAll();

        // Pass through our Base Controller formatting engine!
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);

        return $this->sendSuccess(['podcasts' => $formattedPodcasts], 'Bookmarks loaded successfully');
    }

    /**
     * GET /api/v1/library/history
     * Fetches the user's recently listened teachings
     */
   /**
     * GET /api/v1/library/history?page=1
     * Fetches the user's recently listened teachings (Paginated)
     */
    public function history()
    {
        $userId = $this->getUserId();
        $podcastModel = new PodcastModel();

        // Join the listen_history table and apply CodeIgniter Pagination!
        $rawPodcasts = $podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text, listen_history.last_listened_at')
            ->join('listen_history', 'listen_history.podcast_id = podcasts.id')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('listen_history.user_id', $userId)
            ->where('podcasts.status', 'published')
            ->orderBy('listen_history.last_listened_at', 'DESC')
            ->paginate(15); // 15 items per page

        $pager = $podcastModel->pager;

        // Pass through our Base Controller formatting engine
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);

        // Assemble Payload with Pagination Data
        $payload = [
            'podcasts'   => $formattedPodcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];

        return $this->sendSuccess($payload, 'Listen history loaded successfully');
    }

    /**
     * POST /api/v1/library/downloads/track/{slug}
     * Records that a user downloaded a specific teaching.
     */
    public function trackDownload($slug)
    {
        $userId = $this->getUserId();
        $db = \Config\Database::connect();
        
        // 1. Get the podcast ID from the slug
        $podcast = $db->table('podcasts')->where('slug', $slug)->get()->getRowArray();
        if (!$podcast) {
            return $this->sendError('Teaching not found', 404);
        }

        // 2. Upsert into download_history
        // If they already downloaded it, we just update the timestamp to today
        $historyQuery = "INSERT INTO download_history (user_id, podcast_id, downloaded_at) 
                         VALUES (?, ?, NOW()) 
                         ON DUPLICATE KEY UPDATE downloaded_at = NOW()";
        $db->query($historyQuery, [$userId, $podcast['id']]);

        // 3. Increment the global download count for the podcast
        $db->table('podcasts')
           ->where('id', $podcast['id'])
           ->set('download_count', 'download_count+1', FALSE)
           ->update();

        return $this->sendSuccess(null, 'Download tracked successfully');
    }
}