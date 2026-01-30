<?php
/**
 * Development Configuration
 * 
 * This file sets development-specific settings.
 * Include this file in config.php when in development mode.
 * DO NOT use this in production!
 */

// Enable debug information display
define('SHOW_DEBUG_INFO', true);

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
