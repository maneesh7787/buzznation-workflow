<?php
/**
 * Logout Page
 * 
 * Handles user logout
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    $conn = getDBConnection();
    logAction($conn, 'Logout', 'User logged out');
    closeDBConnection($conn);
}

logout();
?>
