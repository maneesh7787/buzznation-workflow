<?php
// Configuration file for base path management

// Define the base directory name (the subfolder name)
// Change this to match your actual folder name
define('BASE_DIR', '/buzznation-pm');

// Alternative: Auto-detect the base directory from the script path
// This will automatically determine the base path regardless of folder name
// Uncomment the following line to use auto-detection instead:
// define('BASE_DIR', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

// Function to get the full URL with base path
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . BASE_DIR;
}

// Function to redirect with proper base path
function redirectTo($page) {
    header('Location: ' . BASE_DIR . '/' . ltrim($page, '/'));
    exit();
}
?>
