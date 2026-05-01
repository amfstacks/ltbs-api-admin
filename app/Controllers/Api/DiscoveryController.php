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
                                    ->orderBy('name', 'ASC')
                                    ->findAll(8);

        // 2. Fetch Themes (Limit to 8)
        $themeModel = new ThemeModel();
        $themes = $themeModel->select('id, name as title, slug, icon_url')
                             ->orderBy('name', 'ASC')
                             ->findAll(8);

        // 3. Fetch Featured Podcast (The absolute latest published teaching)
        $featured = $this->getFormattedPodcasts(1, 'created_at', 'DESC');

        // 4. Fetch Recent Teachings (Next 10 latest, excluding the featured one)
        // If there's no featured, just get 10. If there is, skip the first one using offset(1)
        $recentOffset = !empty($featured) ? 1 : 0;
        $recent = $this->getFormattedPodcasts(10, 'created_at', 'DESC', $recentOffset);

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
}