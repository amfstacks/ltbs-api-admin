<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use App\Models\CategoryModel;
use App\Models\ThemeModel;

class DiscoveryController extends BaseApiController
{
    protected $podcastModel;

    public function __construct()
    {
        $this->podcastModel = new PodcastModel();
    }

    /**
     * GET /api/v1/discovery/home
     * The Mega-Endpoint for the Flutter App Home Screen
     */
    public function home()
    {
        // 1. Fetch Categories (Limit to 8 for the home grid)
        $categoryModel = new CategoryModel();
$categories = $categoryModel->select('id, name as title, slug, icon_url')
                            ->orderBy('id', 'RANDOM')
                            ->findAll(6);

// 2. Fetch Themes (Limit to 6)
$themeModel = new ThemeModel();
$themes = $themeModel->select('id, name as title, slug, icon_url')
                     ->orderBy('id', 'RANDOM')
                     ->findAll(6);

        // 3. Fetch Featured Podcast (The absolute latest published teaching)
        // $featured = $this->getFormattedPodcasts(1, 'created_at', 'DESC');
        // $featured = $this->getFormattedPodcasts(1, 'featured DESC, created_at', 'DESC');
        // $featured = $this->getFormattedPodcasts(1, 'featured DESC, podcasts.created_at', 'DESC');
        $featured = $this->getFormattedPodcasts(1, 'featured, podcasts.created_at', 'DESC');

        // 4. Fetch Recent Teachings (Next 10 latest, excluding the featured one)
        // If there's no featured, just get 10. If there is, skip the first one using offset(1)
        $recentOffset = !empty($featured) ? 1 : 0;
        $recent = $this->getFormattedPodcasts(11, 'created_at', 'DESC', 0);

        // 5. Fetch Most Listened (Top 10 highest play_count)
        $popular = $this->getFormattedPodcasts(10, 'play_count', 'DESC');

        // Assemble the Mega-Payload
        $payload = [
            'featured'   => !empty($featured) ? $featured[0] : null, // Send as a single object, not array
            'recent'     => $recent,
            'popular'    => $popular,
            'categories' => $categories,
            'themes'     => $themes
        ];

        return $this->sendSuccess($payload, 'Discovery data loaded successfully');
    }

    /**
     * Helper Method: Generates a clean, joined query for the Flutter App
     * Grabs the Category Name and the Primary Author's Name in one sweep.
     */
    // private function getFormattedPodcasts($limit, $orderByField, $orderDirection, $offset = 0)
    // {
    //     return $this->podcastModel
    //         ->select('
    //             podcasts.id, 
    //             podcasts.title, 
    //             podcasts.slug, 
    //             podcasts.description, 
    //             podcasts.cover_image_url, 
    //             podcasts.media_high_url, 
    //             podcasts.media_low_url, 
    //             podcasts.play_count,
    //             podcasts.created_at as published_at,
    //             categories.name as category_name,
    //             users.first_name as author_first,
    //             users.last_name as author_last
    //         ')
    //         ->join('categories', 'categories.id = podcasts.category_id', 'left')
    //         // Only join the PRIMARY author for the list views
    //         ->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id AND podcast_authors.is_primary = 1', 'left')
    //         ->join('users', 'users.id = podcast_authors.author_id', 'left')
    //         ->where('podcasts.status', 'published')
    //         ->orderBy("podcasts.$orderByField", $orderDirection)
    //         ->findAll($limit, $offset);
    // }

    private function getFormattedPodcasts($limit, $orderByField, $orderDirection, $offset = 0)
    {
        // 1. Just fetch the raw podcast data with basic category/theme joins
        $rawPodcasts = $this->podcastModel
            ->select('
                podcasts.*, 
                categories.name as category_name,
                themes.name as theme_text
            ')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.status', 'published')
            ->orderBy("podcasts.$orderByField", $orderDirection)
            ->findAll($limit, $offset);

        // 2. Pass it through the Master Formatter so it matches the Flutter App perfectly!
        return $this->formatPodcastsWithAuthors($rawPodcasts);
    }
    /**
     * GET /api/v1/discovery/categories
     * Fetches paginated categories with their podcast counts
     */
    public function categories()
    {
        $categoryModel = new CategoryModel();
        
        // Use 12 per page because it divides perfectly for 2, 3, or 4 column grids!
        $limit = $this->request->getGet('limit') ?? 12; 

        $categories = $categoryModel->select('categories.id, categories.name as title, categories.slug, categories.icon_url, COUNT(podcasts.id) as podcastCount')
            ->join('podcasts', 'podcasts.category_id = categories.id AND podcasts.status = "published"', 'left')
            ->groupBy('categories.id')
            ->orderBy('categories.name', 'ASC')
            ->paginate($limit);

        $pager = $categoryModel->pager;

        $payload = [
            'categories' => $categories,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'has_more'     => $pager->getCurrentPage() < $pager->getPageCount()
            ]
        ];

        return $this->sendSuccess($payload, 'Categories retrieved successfully');
    }

    /**
     * GET /api/v1/discovery/themes
     * Fetches paginated themes with their podcast counts
     */
    public function themes()
    {
        $themeModel = new ThemeModel();
        
        $limit = $this->request->getGet('limit') ?? 12;

        $themes = $themeModel->select('themes.id, themes.name as title, themes.slug, themes.icon_url, COUNT(podcasts.id) as podcastCount')
            ->join('podcasts', 'podcasts.theme_id = themes.id AND podcasts.status = "published"', 'left')
            ->groupBy('themes.id')
            ->orderBy('themes.name', 'ASC')
            ->paginate($limit);

        $pager = $themeModel->pager;

        $payload = [
            'themes' => $themes,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'has_more'     => $pager->getCurrentPage() < $pager->getPageCount()
            ]
        ];

        return $this->sendSuccess($payload, 'Themes retrieved successfully');
    }

    /**
     * POST /api/v1/discovery/check-version
     * Intercepts Android platform binaries to enforce kill-switch expirations aa
     */ 
    /**
     * POST /api/v1/discovery/check-version
     * Intercepts Android platform binaries to enforce kill-switch expirations
     */
    public function checkVersion()
    {
        // 👉 THE FIX: Use getVar() to successfully read the JSON payload from Flutter
        $vVersion = $this->request->getVar('v_version');
        
        if (empty($vVersion)) {
            return $this->sendError('App version identifier parameter is required.', 400);
        }

        $db = \Config\Database::connect();
        $versionRow = $db->table('app_versions')
                         ->where('v_version', $vVersion)
                         ->get()
                         ->getRowArray();

        // Safe fallback scenario: if you haven't explicitly registered this string tag in DB, let it pass
        if (!$versionRow) {
            return $this->sendSuccess(['is_blocked' => false], 'Version tracking trace omitted.');
        }

        $now = new \DateTime();
        $expiry = new \DateTime($versionRow['expires_at']);

        // Check if the exact server timestamp exceeds the hard limit configuration
        $isBlocked = ($now > $expiry);

        $payload = [
            'is_blocked'     => $isBlocked,
            'expiry_date'    => $versionRow['expires_at'],
            'update_url'     => $versionRow['update_url'],
            'custom_message' => $versionRow['custom_message'] ?? 'This version of the application has expired. An update is required.'
        ];

        return $this->sendSuccess($payload, 'Version health metrics checked successfully.');
    }
}