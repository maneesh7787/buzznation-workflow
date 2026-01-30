<?php
// Start session
session_start();

// Include configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Not logged in, redirect to login page with proper base path
    redirectTo('login.php');
}

// Get username from session
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BuzzNation PM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        h1 {
            font-size: 24px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        .welcome-card h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .welcome-card p {
            color: #666;
            line-height: 1.6;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .info-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .info-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            background: #4caf50;
            color: white;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .debug-info {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-left: 3px solid #667eea;
            border-radius: 5px;
        }
        .debug-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .debug-info pre {
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>BuzzNation PM Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                <a href="<?php echo BASE_DIR; ?>/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-card">
            <h2>Welcome to Your Dashboard!</h2>
            <p>You have successfully logged in. The redirection is now working correctly with the proper base path.</p>
            <p style="margin-top: 10px;"><span class="status">✓ Redirections Fixed</span></p>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <h3>Redirection Fix</h3>
                <p>All redirections now properly include the base directory path (<code>/buzznation-pm</code>). This ensures that the application works correctly when deployed in a subfolder.</p>
            </div>
            
            <div class="info-card">
                <h3>Base Path Configuration</h3>
                <p>The base path is defined in <code>config.php</code> and used throughout the application via the <code>redirectTo()</code> function.</p>
            </div>
            
            <div class="info-card">
                <h3>Session Management</h3>
                <p>Sessions are properly managed across all pages. Unauthenticated users are redirected to the login page, and authenticated users can access the dashboard.</p>
            </div>
        </div>
        
        <div class="debug-info">
            <h3>Debug Information</h3>
            <pre><?php
echo "Base Directory: " . BASE_DIR . "\n";
echo "Base URL: " . getBaseUrl() . "\n";
echo "Current URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Session Status: " . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'Authenticated' : 'Not Authenticated') . "\n";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'N/A') . "\n";
            ?></pre>
        </div>
    </div>
</body>
</html>
