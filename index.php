<?php
// Start session
session_start();

// Include configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // User is not logged in, redirect to login page with proper base path
    redirectTo('login.php');
}

// If user is logged in, redirect to dashboard
redirectTo('dashboard.php');
?>
