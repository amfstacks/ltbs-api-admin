<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use CodeIgniter\API\ResponseTrait;

class LibraryController extends BaseApiController
{
    /**
     * Helper to get the logged-in User ID from the JWT request header
     */
    // private function getUserId()
    // {
    //     $header = $this->request->getHeaderLine('Authorization');
    //     preg_match('/Bearer\s(\S+)/', $header, $matches);
    //     $token = $matches[1];
        
    //     $key = getenv('JWT_SECRET');
    //     $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
        
    //     return $decoded->uid;
    // }


    /**
     * POST /api/v1/podcasts/{slug}/like
     * Toggles a Like on/off using strict Database Transactions.
     */
    public function toggleLike($slug)
    {
        $userId = $this->getUserId(); // Fails if not logged in
        if (!$userId) return $this->sendError('Please login to like this teaching.', 401);

        $db = \Config\Database::connect();
        $podcast = $db->table('podcasts')->where('slug', $slug)->get()->getRowArray();
        
        if (!$podcast) return $this->sendError('Podcast not found', 404);
        $podcastId = $podcast['id'];

        // Start Transaction
        $db->transStart();

        // Check if already liked
        $existing = $db->table('podcast_likes')->where(['user_id' => $userId, 'podcast_id' => $podcastId])->countAllResults();

        if ($existing > 0) {
            // UNLIKE: Delete record and decrement count
            $db->table('podcast_likes')->where(['user_id' => $userId, 'podcast_id' => $podcastId])->delete();
            $db->table('podcasts')->where('id', $podcastId)->set('like_count', 'like_count-1', FALSE)->update();
            $action = 'unliked';
        } else {
            // LIKE: Insert record and increment count
            $db->table('podcast_likes')->insert(['user_id' => $userId, 'podcast_id' => $podcastId]);
            $db->table('podcasts')->where('id', $podcastId)->set('like_count', 'like_count+1', FALSE)->update();
            $action = 'liked';
        }

        // Commit Transaction
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return $this->sendError('Database error occurred while updating like status.', 500);
        }

        return $this->sendSuccess(['action' => $action], "Teaching $action successfully");
    }

    /**
     * POST /api/v1/podcasts/{slug}/bookmark
     * Toggles a Bookmark on/off.
     */
  
    /**
     * POST /api/v1/library/bookmarks/toggle/{slug}
     * Adds or removes a bookmark for the logged-in user
     */
    public function toggleBookmark($slug)
    {
        $userId = $this->getUserId(); // Fails if not logged in
        if (!$userId) return $this->sendError('Please login to bookmark this teaching.', 401);

        $db = \Config\Database::connect();
        
        // 1. Get the podcast ID from the slug
        $podcast = $db->table('podcasts')->where('slug', $slug)->get()->getRowArray();
        if (!$podcast) {
            return $this->sendError('Teaching not found', 404);
        }

        $podcastId = $podcast['id'];

        // 👉 Start Transaction to ensure both tables update perfectly or not at all
        $db->transStart();

        // 2. Check if it is already bookmarked
        $existing = $db->table('bookmarks')
            ->where(['user_id' => $userId, 'podcast_id' => $podcastId])
            ->countAllResults();

        if ($existing > 0) {
            // REMOVE BOOKMARK: Delete record and decrement count
            $db->table('bookmarks')->where(['user_id' => $userId, 'podcast_id' => $podcastId])->delete();
            $db->table('podcasts')->where('id', $podcastId)->set('bookmark_count', 'bookmark_count-1', FALSE)->update();
            
            $isBookmarked = false;
            $message = 'Removed from library';
        } else {
            // ADD BOOKMARK: Insert record and increment count
            $db->table('bookmarks')->insert(['user_id' => $userId, 'podcast_id' => $podcastId]);
            $db->table('podcasts')->where('id', $podcastId)->set('bookmark_count', 'bookmark_count+1', FALSE)->update();
            
            $isBookmarked = true;
            $message = 'Added to library';
        }

        // 👉 Commit Transaction
        $db->transComplete();

        // Safety check if the database locked up
        if ($db->transStatus() === FALSE) {
            return $this->sendError('Database error occurred while updating bookmark status.', 500);
        }

        return $this->sendSuccess(['is_bookmarked' => $isBookmarked], $message);
    }

   /**
     * GET /api/v1/library/bookmarks?page=1
     * Fetches paginated teachings the user has saved
     */
    public function bookmarks()
    {
        $userId = $this->getUserId();
        
        // 👉 FIX 1: Manually capture the page from the Flutter URL request
        $page = (int) ($this->request->getVar('page') ?? 1);

        $podcastModel = new PodcastModel();

        // Join the bookmarks table to filter only saved items
        $rawPodcasts = $podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            ->join('bookmarks', 'bookmarks.podcast_id = podcasts.id')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('bookmarks.user_id', $userId)
            ->where('podcasts.status', 'published')
            ->orderBy('bookmarks.created_at', 'DESC') 
            // 👉 FIX 2: Replace findAll() with paginate()
            ->paginate(15, 'default', $page); 

        $pager = $podcastModel->pager;

        // 👉 FIX 3: The Out-Of-Bounds Safety Net
        if ($page > $pager->getPageCount()) {
            $rawPodcasts = [];
        }

        // Pass through our Base Controller formatting engine!
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);

        // 👉 FIX 4: Assemble the Payload properly so Flutter's generic helper can read it
        $payload = [
            'podcasts'   => $formattedPodcasts,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];

        return $this->sendSuccess($payload, 'Bookmarks loaded successfully');
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