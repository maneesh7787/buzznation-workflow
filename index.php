<?php
/**
 * Index Page
 * 
 * Redirects to appropriate page based on login status
 */

require_once __DIR__ . '/config/security.php';

if (isLoggedIn()) {
    header("Location: /dashboard.php");
} else {
    header("Location: /login.php");
}
exit();
?>
