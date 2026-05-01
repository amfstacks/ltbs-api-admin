<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use App\Models\CategoryModel;
use App\Models\ThemeModel;

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
}