<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// --- ADMIN PANEL ROUTES ---
$routes->group('admin', function($routes) {
    
    // Public Routes (No Filter)
    $routes->get('login', 'Admin\AuthController::index');
    $routes->post('login/authenticate', 'Admin\AuthController::authenticate');
    $routes->get('logout', 'Admin\AuthController::logout');

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
    });

    
});
