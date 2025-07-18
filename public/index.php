<?php

/**
 * Netflix Login Code Service
 * Entry Point
 * 
 * This file handles all HTTP requests and routes them through the application.
 */

// Define paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load Composer autoloader
if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    die('Composer dependencies not installed. Please run: composer install');
}

require_once ROOT_PATH . '/vendor/autoload.php';

// Create storage directories if they don't exist
$directories = [
    ROOT_PATH . '/storage',
    ROOT_PATH . '/storage/logs',
    ROOT_PATH . '/storage/cache',
    ROOT_PATH . '/storage/backups',
    ROOT_PATH . '/storage/uploads'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

try {
    // Initialize and run the application
    $app = new \NetflixLoginService\Core\Application();
    $app->run();
    
} catch (Exception $e) {
    // Fallback error handling
    error_log("Fatal error: " . $e->getMessage());
    
    if ($_ENV['APP_DEBUG'] ?? false) {
        echo "<h1>Application Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        echo "<h1>500 - Internal Server Error</h1>";
        echo "<p>Something went wrong. Please try again later.</p>";
    }
}