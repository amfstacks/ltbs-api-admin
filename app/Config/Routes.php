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
    });
});
