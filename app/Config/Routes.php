<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->options('(:any)', 'Home::index');
$routes->get('/', 'Home::index');

// --- ADMIN PANEL ROUTES ---
$routes->group('admin', function($routes) {
    
    // Public Routes (No Filter)
    $routes->get('login', 'Admin\AuthController::index');
    $routes->post('login/authenticate', 'Admin\AuthController::authenticate');
    $routes->get('logout', 'Admin\AuthController::logout');
    $routes->get('setup-password/(:segment)', 'Admin\AuthController::setupPassword/$1');
    $routes->post('setup-password/save', 'Admin\AuthController::savePassword');

    // Protected Routes (Requires AdminFilter)
    $routes->group('', ['filter' => 'adminAuth'], function($routes) {
        // We will build this controller in a second!
        $routes->get('dashboard', 'Admin\DashboardController::index');

        // Taxonomy: Categories
        $routes->get('categories', 'Admin\CategoryController::index');
        $routes->get('categories/form', 'Admin\CategoryController::form');
        $routes->get('categories/form/(:num)', 'Admin\CategoryController::form/$1');
        $routes->post('categories/save', 'Admin\CategoryController::save');
        $routes->post('categories/save/(:num)', 'Admin\CategoryController::save/$1');
        $routes->get('categories/delete/(:num)', 'Admin\CategoryController::delete/$1');


        // Taxonomy: Themes
        $routes->get('themes', 'Admin\ThemeController::index');
        $routes->get('themes/form', 'Admin\ThemeController::form');
        $routes->get('themes/form/(:num)', 'Admin\ThemeController::form/$1');
        $routes->post('themes/save', 'Admin\ThemeController::save');
        $routes->post('themes/save/(:num)', 'Admin\ThemeController::save/$1');
        $routes->get('themes/delete/(:num)', 'Admin\ThemeController::delete/$1');


        $routes->get('podcasts', 'Admin\PodcastController::index');
        $routes->get('podcasts/wizard', 'Admin\PodcastController::wizard');
        $routes->post('podcasts/store', 'Admin\PodcastController::store');


        // Podcasts
        $routes->get('podcasts', 'Admin\PodcastController::index');
        $routes->get('podcasts/wizard', 'Admin\PodcastController::wizard');
        $routes->get('podcasts/edit/(:num)', 'Admin\PodcastController::edit/$1'); // NEW
        $routes->post('podcasts/save', 'Admin\PodcastController::save'); // RENAMED FROM STORE
        $routes->post('podcasts/save/(:num)', 'Admin\PodcastController::save/$1'); // NEW
        $routes->get('podcasts/delete/(:num)', 'Admin\PodcastController::delete/$1'); // NEW


        $routes->get('users', 'Admin\UserController::index');
        $routes->get('users/create', 'Admin\UserController::create');
        $routes->post('users/store', 'Admin\UserController::store');


        // Forum
        $routes->get('forum', 'Admin\ForumController::index');
        $routes->get('forum/view/(:num)', 'Admin\ForumController::view/$1');
        $routes->get('forum/api/replies/(:num)', 'Admin\ForumController::fetchReplies/$1');
        $routes->post('forum/reply/(:num)', 'Admin\ForumController::reply/$1');
        $routes->get('forum/podcast/(:num)', 'Admin\ForumController::podcast/$1'); // NEW


        $routes->get('podcasts/media/([0-9]+)', 'Admin\PodcastController::media/$1');
        $routes->post('podcasts/update-media/([0-9]+)', 'Admin\PodcastController::updateMedia/$1');

    });

    
});



// ====================================================================
// MOBILE APP API ROUTES (v1)
// ====================================================================
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], function ($routes) {

    // ----------------------------------------------------------------
    // TIER 1: PUBLIC ROUTES (Requires App-Key only)
    // ----------------------------------------------------------------
    
    // Auth 
    $routes->post('auth/register', 'AuthController::register');
    $routes->post('auth/login', 'AuthController::login');
    $routes->post('auth/logout', 'AuthController::logout', ['filter' => 'jwt']);

    $routes->post('auth/google', 'AuthController::googleLogin');
    $routes->post('auth/truecaller', 'AuthController::truecallerLogin');
    
    // Discovery
    $routes->get('discovery/home', 'DiscoveryController::home');
    $routes->get('categories', 'DiscoveryController::categories');
    $routes->get('themes', 'DiscoveryController::themes');
    
    // Podcasts & Media
    $routes->get('podcasts', 'PodcastController::index');
    $routes->get('podcasts/recent', 'PodcastController::recent');
    $routes->get('podcasts/popular', 'PodcastController::popular');
    $routes->get('podcasts/(:segment)', 'PodcastController::showPodcast/$1');
    $routes->post('track/play/(:segment)', 'PodcastController::trackPlay/$1');// Silent play tracker

    $routes->get('podcasts/author/(:num)', 'PodcastController::author/$1');

    // Fetch podcasts by Category or Theme slug
    $routes->get('podcasts/category/(:segment)', 'PodcastController::category/$1');
    $routes->get('podcasts/theme/(:segment)', 'PodcastController::theme/$1');
    
    // Forums (Read-Only Public)
    $routes->get('forums', 'ForumController::index');
    $routes->get('forums/(:num)/comments', 'ForumController::comments/$1');
    $routes->get('forums/(:segment)/comments', 'ForumController::comments/$1');
    $routes->get('forums/threads/(:num)/replies', 'ForumController::threadReplies/$1');

    // ----------------------------------------------------------------
    // TIER 2: PROTECTED ROUTES (Requires Login / JWT Bearer Token)
    // ----------------------------------------------------------------
    // Note: We will create the 'jwt' filter in the next step!
    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        
        // Profile
        $routes->get('profile', 'ProfileController::index');
        $routes->post('profile/update', 'ProfileController::updateProfile');
        $routes->post('profile/avatar', 'ProfileController::uploadAvatar');
        $routes->post('auth/logout', 'AuthController::logout');
        $routes->post('profile/password', 'ProfileController::updatePassword');

        // Library (Bookmarks)
        $routes->get('library/bookmarks', 'LibraryController::bookmarks');
        $routes->post('library/bookmarks/toggle/(:num)', 'LibraryController::toggleBookmark/$1');
        $routes->post('library/like/toggle/(:num)', 'LibraryController::toggleLike/$1');
        $routes->post('library/downloads/track/(:segment)', 'LibraryController::trackDownload/$1');
        
        // Forums (Write Access)
        // $routes->post('forums/(:num)/comments', 'ForumController::createComment/$1');
        $routes->post('forums/(:segment)/comments', 'ForumController::createComment/$1');

        $routes->post('profile/settings', 'ProfileController::updateSettings');
    });

});
