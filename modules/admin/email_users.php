<?php
/**
 * Admin Module - Email Notification Users Management
 */

define('PAGE_TITLE', 'Email Notification Users - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('Admin');

$conn = getDBConnection();
$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $fullName = sanitizeInput($conn, $_POST['full_name']);
        $email = sanitizeInput($conn, $_POST['email']);
        
        // Check if email exists
        $sql = "SELECT email_user_id FROM email_notification_users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email already exists';
        } else {
            $sql = "INSERT INTO email_notification_users (full_name, email) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $fullName, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                logAction($conn, 'Create Email User', "Added email user: $email");
                $success = 'Email user added successfully';
            } else {
                $error = 'Failed to add email user';
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($action === 'toggle_status') {
        $emailUserId = intval($_POST['email_user_id']);
        $sql = "UPDATE email_notification_users SET is_active = NOT is_active WHERE email_user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $emailUserId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        logAction($conn, 'Toggle Email User Status', "Toggled email user status");
        $success = 'Status updated';
    }
    
    if ($action === 'delete') {
        $emailUserId = intval($_POST['email_user_id']);
        $sql = "DELETE FROM email_notification_users WHERE email_user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $emailUserId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        logAction($conn, 'Delete Email User', "Deleted email user");
        $success = 'Email user deleted';
    }
}

// Get all email users
$sql = "SELECT * FROM email_notification_users ORDER BY full_name";
$result = mysqli_query($conn, $sql);
$emailUsers = array();
while ($row = mysqli_fetch_assoc($result)) {
    $emailUsers[] = $row;
}

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-envelope"></i> Email Notification Users</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Email Recipients (<?php echo count($emailUsers); ?>)</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="$('#createEmailUserModal').modal('show')">
                <i class="fas fa-plus"></i> Add Email User
            </button>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emailUsers as $user): ?>
                        <tr>
                            <td><?php echo $user['email_user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDateTime($user['created_at']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="email_user_id" value="<?php echo $user['email_user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="Toggle Status">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="email_user_id" value="<?php echo $user['email_user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Email User Modal -->
<div class="modal fade" id="createEmailUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Email User</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
