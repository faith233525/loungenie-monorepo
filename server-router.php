<?php
/**
 * LounGenie Portal Development Server Router
 * 
 * Routes requests to appropriate handlers:
 * - /login → Custom login page
 * - /portal → Portal dashboard
 * - /wp-admin → WordPress admin
 * - / → Demo/index page
 * 
 * Usage: php -S localhost:8000 server-router.php
 */

// Base directory
$basedir = dirname(__FILE__);
$requested_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requested_path = rtrim($requested_path, '/');

// Security: Prevent directory traversal
if (strpos($requested_path, '..') !== false) {
    http_response_code(403);
    die('Forbidden');
}

// Route handlers
$routes = [
    '/' => 'index.html',
    '/index' => 'index.html',
    '/index.html' => 'index.html',
    '/login' => 'loungenie-portal/templates/custom-login.php',
    '/portal' => 'loungenie-portal/templates/portal-shell.php',
    '/wp-admin' => 'loungenie-portal/wp-admin/index.php',
];

// Try to match exact route
if (isset($routes[$requested_path])) {
    $file = $basedir . '/' . $routes[$requested_path];
    if (file_exists($file)) {
        // Set appropriate content type
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $content_types = [
            'php' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        
        if (isset($content_types[$ext])) {
            header('Content-Type: ' . $content_types[$ext]);
        }
        
        include $file;
        exit;
    }
}

// Try to serve static files
if ($requested_path !== '') {
    $file = $basedir . $requested_path;
    
    // Security check
    if (realpath($file) && strpos(realpath($file), $basedir) === 0) {
        if (is_file($file)) {
            // Set appropriate content type
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $content_types = [
                'css' => 'text/css',
                'js' => 'text/javascript',
                'json' => 'application/json',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
            ];
            
            if (isset($content_types[$ext])) {
                header('Content-Type: ' . $content_types[$ext]);
            }
            
            readfile($file);
            exit;
        }
    }
}

// Default: show index page
header('Content-Type: text/html');
include $basedir . '/index.html';
?>
