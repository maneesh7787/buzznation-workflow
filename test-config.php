<?php
/**
 * Configuration Test Script
 * 
 * This script helps verify that the base path configuration is working correctly.
 * Access this file directly to see if paths are being generated correctly.
 */

// Include configuration
require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Test - BuzzNation PM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .test-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 3px solid #667eea;
            border-radius: 5px;
        }
        .test-item strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        .test-item code {
            background: #fff;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
            color: #667eea;
            font-family: 'Courier New', monospace;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .status.ok {
            background: #4caf50;
            color: white;
        }
        .status.warning {
            background: #ff9800;
            color: white;
        }
        .links {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .links a {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .links a:hover {
            background: #5568d3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f0f0f0;
            font-weight: bold;
            color: #333;
        }
        table td {
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configuration Test</h1>
        
        <?php
        $hasIssues = false;
        
        // Check if BASE_DIR is properly set
        if (!defined('BASE_DIR')) {
            echo '<span class="status warning">⚠ WARNING: BASE_DIR is not defined!</span>';
            $hasIssues = true;
        } else {
            echo '<span class="status ok">✓ Configuration OK</span>';
        }
        ?>
        
        <h2>Base Path Configuration</h2>
        <div class="test-item">
            <strong>Configured Base Directory:</strong>
            <code><?php echo defined('BASE_DIR') ? BASE_DIR : 'NOT DEFINED'; ?></code>
        </div>
        
        <div class="test-item">
            <strong>Base URL:</strong>
            <code><?php echo function_exists('getBaseUrl') ? getBaseUrl() : 'Function not available'; ?></code>
        </div>
        
        <h2>Server Information</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>HTTP_HOST</td>
                <td><?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>SERVER_NAME</td>
                <td><?php echo $_SERVER['SERVER_NAME'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>SCRIPT_NAME</td>
                <td><?php echo $_SERVER['SCRIPT_NAME'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>REQUEST_URI</td>
                <td><?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>DOCUMENT_ROOT</td>
                <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'N/A'; ?></td>
            </tr>
            <tr>
                <td>PHP_SELF</td>
                <td><?php echo $_SERVER['PHP_SELF'] ?? 'N/A'; ?></td>
            </tr>
        </table>
        
        <h2>Generated URLs</h2>
        <div class="test-item">
            <strong>Login Page URL:</strong>
            <code><?php echo BASE_DIR . '/login.php'; ?></code>
        </div>
        
        <div class="test-item">
            <strong>Dashboard Page URL:</strong>
            <code><?php echo BASE_DIR . '/dashboard.php'; ?></code>
        </div>
        
        <div class="test-item">
            <strong>Index Page URL:</strong>
            <code><?php echo BASE_DIR . '/index.php'; ?></code>
        </div>
        
        <div class="test-item">
            <strong>Logout Page URL:</strong>
            <code><?php echo BASE_DIR . '/logout.php'; ?></code>
        </div>
        
        <h2>Auto-detected Base Path</h2>
        <div class="test-item">
            <strong>Auto-detected from SCRIPT_NAME:</strong>
            <code><?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?></code>
            <br><br>
            <em>If this matches your expected base directory, you can use auto-detection in config.php</em>
        </div>
        
        <div class="links">
            <a href="<?php echo BASE_DIR; ?>/index.php">Go to Index</a>
            <a href="<?php echo BASE_DIR; ?>/login.php">Go to Login</a>
        </div>
    </div>
</body>
</html>
