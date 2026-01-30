<?php
// Start session
session_start();

// Include configuration
require_once 'config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page with proper base path
redirectTo('login.php');
?>
