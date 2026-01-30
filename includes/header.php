<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'BuzzNation Project Management');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo PAGE_TITLE; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #007bff;
            color: #fff;
        }
        .content {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-card.primary {
            border-left-color: #007bff;
        }
        .stats-card.warning {
            border-left-color: #ffc107;
        }
        .stats-card.info {
            border-left-color: #17a2b8;
        }
        .stats-card.success {
            border-left-color: #28a745;
        }
        .notification-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            padding: 3px 6px;
            border-radius: 10px;
            background-color: #dc3545;
            color: white;
            font-size: 10px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .btn-sm {
            margin: 2px;
        }
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .required {
            color: red;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="/dashboard.php">
            <i class="fas fa-briefcase"></i> BuzzNation PM
        </a>
        
        <?php if (isLoggedIn()): ?>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars(getCurrentUserName()); ?>
                        <?php
                        $conn = getDBConnection();
                        $unreadCount = getUnreadNotificationCount($conn);
                        closeDBConnection($conn);
                        if ($unreadCount > 0):
                        ?>
                        <span class="badge badge-danger ml-1"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="/notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                            <span class="badge badge-danger ml-1"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>
    
    <?php if (isLoggedIn()): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <?php
                $role = getCurrentUserRole();
                ?>
                
                <!-- Admin Menu -->
                <?php if ($role === 'Admin'): ?>
                <a href="/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/modules/admin/users.php">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="/modules/admin/email_users.php">
                    <i class="fas fa-envelope"></i> Email List
                </a>
                <a href="/modules/admin/projects.php">
                    <i class="fas fa-project-diagram"></i> All Projects
                </a>
                <a href="/modules/admin/logs.php">
                    <i class="fas fa-file-alt"></i> System Logs
                </a>
                <?php endif; ?>
                
                <!-- Sales Menu -->
                <?php if ($role === 'Sales' || $role === 'Admin'): ?>
                <a href="/modules/sales/create_project.php">
                    <i class="fas fa-plus-circle"></i> New Project
                </a>
                <a href="/modules/sales/my_projects.php">
                    <i class="fas fa-list"></i> My Projects
                </a>
                <?php endif; ?>
                
                <!-- Design Menu -->
                <?php if ($role === 'Design' || $role === 'Admin'): ?>
                <a href="/modules/design/pending.php">
                    <i class="fas fa-clock"></i> Pending Tasks
                </a>
                <a href="/modules/design/ongoing.php">
                    <i class="fas fa-spinner"></i> Ongoing Projects
                </a>
                <a href="/modules/design/completed.php">
                    <i class="fas fa-check-circle"></i> Completed
                </a>
                <?php endif; ?>
                
                <!-- Operation Menu -->
                <?php if ($role === 'Operation' || $role === 'Admin'): ?>
                <a href="/modules/operation/pending_reviews.php">
                    <i class="fas fa-clipboard-check"></i> Pending Reviews
                </a>
                <a href="/modules/operation/reviewed.php">
                    <i class="fas fa-check-double"></i> Reviewed Projects
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 content">
    <?php else: ?>
    <div class="container">
    <?php endif; ?>
