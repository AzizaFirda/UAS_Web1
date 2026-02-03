<?php
// index.php - Main Entry Point

require_once __DIR__ . '/backend/middleware/AuthMiddleware.php';

// Check if user is authenticated
AuthMiddleware::init();

// Get the requested URI
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get the script's directory (handling folder names with spaces)
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = (!empty($script_path) && $script_path !== '/') ? $script_path : '';

// Remove base path if exists
$request_path = str_replace($base_path, '', $request_uri);
$request_path = '/' . ltrim($request_path, '/');

if (AuthMiddleware::check()) {
    // User is logged in
    // Only redirect to login if they're accessing login or register page
    if (strpos($request_path, 'login') !== false || strpos($request_path, 'register') !== false) {
        header('Location: ' . $base_path . '/frontend/pages/dashboard.html');
        exit();
    }
} else {
    // User not logged in
    // Allow access to login and register pages, and API
    if (strpos($request_path, 'login') === false && 
        strpos($request_path, 'register') === false && 
        strpos($request_path, 'backend/api') === false &&
        strpos($request_path, '/frontend/landing.html') === false) {
        header('Location: ' . $base_path . '/frontend/landing.html');
        exit();
    }
}
?>