<?php
// Configuration file for base path management

// Load development config if it exists (for debugging)
if (file_exists(__DIR__ . '/config.dev.php')) {
    require_once __DIR__ . '/config.dev.php';
}

// Define the base directory name (the subfolder name)
// Change this to match your actual folder name
define('BASE_DIR', '/buzznation-pm');

// Alternative: Auto-detect the base directory from the script path
// This will automatically determine the base path regardless of folder name
// Uncomment the following line to use auto-detection instead:
// define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Session security configuration
// Set these before any session_start() call
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
// Set session cookie path to base directory
session_set_cookie_params([
    'path' => BASE_DIR . '/',
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'samesite' => 'Lax'
]);

// Function to get the full URL with base path
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    // Use SERVER_NAME instead of HTTP_HOST for better security
    $host = $_SERVER['SERVER_NAME'];
    return $protocol . '://' . $host . BASE_DIR;
}

// Function to redirect with proper base path
function redirectTo($page) {
    // Validate page parameter to prevent path traversal
    $page = ltrim($page, '/');
    if (strpos($page, '..') !== false) {
        die('Invalid redirect path');
    }
    header('Location: ' . BASE_DIR . '/' . $page);
    exit();
}
?>
