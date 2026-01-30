<?php
/**
 * Notifications Page
 * 
 * Displays notifications for the current user
 */

define('PAGE_TITLE', 'Notifications - BuzzNation PM');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$conn = getDBConnection();
$userId = getCurrentUserId();

// Mark notification as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notificationId = intval($_GET['mark_read']);
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $notificationId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: /notifications.php");
    exit();
}

// Mark all as read if requested
if (isset($_GET['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: /notifications.php");
    exit();
}

// Get notifications
$sql = "SELECT n.*, p.event_name 
        FROM notifications n 
        LEFT JOIN projects p ON n.project_id = p.project_id 
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC 
        LIMIT 50";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = array();
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

mysqli_stmt_close($stmt);
closeDBConnection($conn);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-bell"></i> Notifications</h1>
        <?php if (!empty($notifications)): ?>
        <a href="?mark_all_read=1" class="btn btn-sm btn-primary">
            <i class="fas fa-check-double"></i> Mark All as Read
        </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($notifications)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
            <p class="text-muted">No notifications yet.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $notification): ?>
        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">
                    <?php if (!$notification['is_read']): ?>
                    <span class="badge badge-primary">New</span>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($notification['event_name']); ?>
                </h6>
                <small><?php echo formatDateTime($notification['created_at']); ?></small>
            </div>
            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
            <small class="text-muted">Type: <?php echo htmlspecialchars($notification['notification_type']); ?></small>
            
            <?php if (!$notification['is_read']): ?>
            <div class="mt-2">
                <a href="?mark_read=<?php echo $notification['notification_id']; ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-check"></i> Mark as Read
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
