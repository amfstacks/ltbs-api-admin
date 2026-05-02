<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use App\Models\CategoryModel;
use App\Models\ThemeModel;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class PodcastController extends BaseApiController
{
    protected $podcastModel;

    public function __construct()
    {
        $this->podcastModel = new PodcastModel();
    }

    /**
     * GET /api/v1/podcasts/category/{slug}?page=1
     * Fetches paginated teachings for a specific category
     */
    public function category($slug)
    {
        // 1. Find the Category by Slug
        $categoryModel = new CategoryModel();
        $category = $categoryModel->where('slug', $slug)->first();

        if (!$category) {
            return $this->sendError('Category not found', 404);
        }

        // 2. Fetch the paginated podcasts for this category
        $podcasts = $this->podcastModel
            ->select('
                podcasts.id, 
                podcasts.title, 
                podcasts.slug, 
                podcasts.cover_image_url, 
                podcasts.play_count,
                podcasts.created_at as published_at,
                users.first_name as author_first,
                users.last_name as author_last
            ')
            ->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id AND podcast_authors.is_primary = 1', 'left')
            ->join('users', 'users.id = podcast_authors.author_id', 'left')
            ->where('podcasts.category_id', $category['id'])
            ->where('podcasts.status', 'published')
            ->orderBy('podcasts.created_at', 'DESC')
            ->paginate(10); // 10 teachings per page

        // 3. Extract Pagination Math for Flutter
        $pager = $this->podcastModel->pager;

        // 4. Assemble the Payload
        $payload = [
            'category' => [
                'id'       => $category['id'],
                'title'    => $category['name'],
                'slug'     => $category['slug'],
                'icon_url' => $category['icon_url'] ?? null
            ],
            'podcasts'   => $podcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];

        return $this->sendSuccess($payload, 'Category teachings loaded successfully');
    }

    /**
     * GET /api/v1/podcasts/theme/{slug}?page=1
     * Fetches paginated teachings for a specific theme
     */
    public function theme($slug)
    {
        $themeModel = new ThemeModel();
        $theme = $themeModel->where('slug', $slug)->first();

        if (!$theme) {
            return $this->sendError('Theme not found', 404);
        }

        $podcasts = $this->podcastModel
            ->select('
                podcasts.id, 
                podcasts.title, 
                podcasts.slug, 
                podcasts.cover_image_url, 
                podcasts.play_count,
                podcasts.created_at as published_at,
                users.first_name as author_first,
                users.last_name as author_last
            ')
            ->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id AND podcast_authors.is_primary = 1', 'left')
            ->join('users', 'users.id = podcast_authors.author_id', 'left')
            ->where('podcasts.theme_id', $theme['id'])
            ->where('podcasts.status', 'published')
            ->orderBy('podcasts.created_at', 'DESC')
            ->paginate(10); 

        $pager = $this->podcastModel->pager;

        $payload = [
            'theme' => [
                'id'       => $theme['id'],
                'title'    => $theme['name'],
                'slug'     => $theme['slug']
            ],
            'podcasts'   => $podcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];

        return $this->sendSuccess($payload, 'Theme teachings loaded successfully');
    }

    /**
     * Helper method to check if a file exists on a remote server (Cloudflare R2)
     * Uses an HTTP HEAD request so it doesn't download the actual MP3 file.
     */
    private function remoteFileExists($url)
    {
        if (empty($url)) return false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Fetch headers only, not the body!
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 second timeout so the API never hangs
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode == 200;
    }

    /**
     * GET /api/v1/podcasts/{id}?data_saver=true
     * Fetches the full details of a specific teaching.
     */
    public function showPodcast($slug)
    {
        // Check if the Flutter app passed the data_saver flag in the URL query
        $isDataSaver = $this->request->getGet('data_saver') === 'true';

        // 1. Fetch the raw podcast data
        $podcast = $this->podcastModel
            ->select('
                podcasts.*, 
                categories.name as category_name,
                themes.name as theme_text
            ')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.slug', $slug)
            ->where('podcasts.status', 'published')
            ->first();

        if (!$podcast) {
            return $this->sendError('Teaching not found or has been removed.', 404);
        }

        // 2. Determine which Audio URL to send back based on Data Saver
        $finalAudioUrl = $podcast['media_high_url']; // Default to High Quality

        if ($isDataSaver && !empty($podcast['media_low_url'])) {
            // App wants low quality, and the database has a link for it.
            // Let's quickly ping Cloudflare to make 100% sure the file is actually there.
            if ($this->remoteFileExists($podcast['media_low_url'])) {
                $finalAudioUrl = $podcast['media_low_url']; // Success! Switch to Low Quality.
            }
        }

        // 3. Fetch ALL authors for this specific podcast
        $db = \Config\Database::connect();
        $authorsQuery = $db->table('podcast_authors')
            ->select('users.id, users.first_name, users.last_name, users.profile_image_url, users.bio')
            ->join('users', 'users.id = podcast_authors.author_id')
            ->where('podcast_authors.podcast_id', $podcast['id'])
            ->orderBy('podcast_authors.is_primary', 'DESC')
            ->get()
            ->getResultArray();

        // 4. Format the Authors
        $authors = [];
        foreach ($authorsQuery as $author) {
            $authors[] = [
                'id'        => (int)$author['id'],
                'name'      => trim($author['first_name'] . ' ' . $author['last_name']),
                'image_url' => $author['profile_image_url'],
                'bio'       => $author['bio']
            ];
        }

        // 5. Assemble the final Payload
        $payload = [
            'id'              => (int)$podcast['id'],
            'title'           => $podcast['title'],
            'slug'           => $podcast['slug'],
            'authors'         => $authors,
            'duration'        => '00:00',
            // 'file_size_bytes' => $podcast['file_size_bytes'] ? (int)$podcast['file_size_bytes'] : null,
            'file_size_bytes' => isset($podcast['file_size_bytes']) ? (int)$podcast['file_size_bytes'] : 0,
            'published_at'    => $podcast['created_at'],
            'category_name'   => $podcast['category_name'] ?? 'Uncategorized',
            'theme_id'        => $podcast['theme_id'] ? (int)$podcast['theme_id'] : null,
            'theme_text'      => $podcast['theme_text'],
            'audio_url'       => $finalAudioUrl, // Dynamically assigned based on logic!
            'cover_url'       => $podcast['cover_image_url'],
            'listen_count'    => (int)($podcast['play_count'] ?? 0),
            'like_count'      => 0,
            'comment_count'   => 0,
            'reshare_count'   => 0,
        ];

        return $this->sendSuccess($payload, 'Podcast loaded successfully');
    }

    /**
     * POST /api/v1/track/play/{id}
     * Silently increments the play_count for a podcast.
     * We use the integer ID here because it's an internal background network request,
     * not a public-facing URL the user will ever see or share.
     */
    /**
     * POST /api/v1/track/play/{slug}
     * Silently increments the play_count for a podcast using its slug.
     */
    public function trackPlay_old($slug)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('podcasts');
        
        // Ensure the podcast actually exists before incrementing
        $exists = $builder->where('slug', $slug)->countAllResults() > 0;
        
        if (!$exists) {
            return $this->sendError('Podcast not found', 404);
        }

        // Optimized SQL Increment: "UPDATE podcasts SET play_count = play_count + 1 WHERE slug = 'X'"
        // The 'FALSE' parameter tells CodeIgniter not to wrap the equation in quotes.
        $builder->where('slug', $slug)
                ->set('play_count', 'play_count+1', FALSE)
                ->update();

        // Return a silent success response
        return $this->sendSuccess(null, 'Play counted successfully');
    }
    public function trackPlay($slug)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('podcasts');
        
        // 1. Find the podcast by slug
        $podcast = $builder->where('slug', $slug)->get()->getRowArray();
        
        if (!$podcast) {
            return $this->sendError('Podcast not found', 404);
        }

        // 2. Update Global Play Count
        $builder->where('id', $podcast['id'])
                ->set('play_count', 'play_count+1', FALSE)
                ->update();

   $userId = $this->getUserId(); // <--- LOOK HOW CLEAN THIS IS NOW!
        
       if ($userId) {
            try {
                $historyQuery = "INSERT INTO listen_history (user_id, podcast_id, last_listened_at) 
                                 VALUES (?, ?, NOW()) 
                                 ON DUPLICATE KEY UPDATE last_listened_at = NOW()";
                $db->query($historyQuery, [$userId, $podcast['id']]);
                
            } catch (\Exception $e) {
                // The query failed, but we catch the error so the API doesn't crash!
                // We log it to the server files so you (the admin) can debug it later.
                log_message('error', '[Listen History Tracker Failed] User ID: ' . $userId . ' | Error: ' . $e->getMessage());
            }
        }

        return $this->sendSuccess(null, 'Play counted and history updated');
    }

    /**
     * GET /api/v1/podcasts/recent?page=1
     * Fetches paginated list of all published teachings, sorted by newest.
     */
    public function recent()
    {
        // 1. Fetch raw paginated results

        $page = (int) ($this->request->getVar('page') ?? 1);
        $rawPodcasts = $this->podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.status', 'published')
            ->orderBy('podcasts.created_at', 'DESC')
            // ->paginate(3); // 15 items per page
            ->paginate(3, 'default', $page);
            
        $pager = $this->podcastModel->pager;
        if ($page > $pager->getPageCount()) {
            $rawPodcasts = []; 
        }
        
        // 2. Pass them through our Base Controller formatting engine
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);
        
        // 3. Assemble Payload with Pagination Data
        $payload = [
            'podcasts'   => $formattedPodcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];
        
        return $this->sendSuccess($payload, 'Recent teachings loaded successfully');
    }

    /**
     * GET /api/v1/podcasts/popular?page=1
     * Fetches paginated list of all published teachings, sorted by most listened.
     */
    public function popular()
    {
        // 1. Fetch raw paginated results sorted by play_count!
        $rawPodcasts = $this->podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.status', 'published')
            ->orderBy('podcasts.play_count', 'DESC') // <--- THE MAGIC SORT
            ->paginate(15);
            
        $pager = $this->podcastModel->pager;
        
        // 2. Pass them through our Base Controller formatting engine
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);
        
        // 3. Assemble Payload
        $payload = [
            'podcasts'   => $formattedPodcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];
        
        return $this->sendSuccess($payload, 'Popular teachings loaded successfully');
    }
}