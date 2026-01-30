<?php
/**
 * Admin Module - User Management
 */

define('PAGE_TITLE', 'User Management - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('Admin');

$conn = getDBConnection();
$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = sanitizeInput($conn, $_POST['username']);
        $email = sanitizeInput($conn, $_POST['email']);
        $password = $_POST['password'];
        $fullName = sanitizeInput($conn, $_POST['full_name']);
        $role = sanitizeInput($conn, $_POST['role']);
        
        // Check if username or email exists
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Username or email already exists';
        } else {
            $passwordHash = hashPassword($password);
            $sql = "INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssss", $username, $email, $passwordHash, $fullName, $role);
            
            if (mysqli_stmt_execute($stmt)) {
                logAction($conn, 'Create User', "Created new user: $username", null, null, null, "Role: $role");
                $success = 'User created successfully';
            } else {
                $error = 'Failed to create user';
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    if ($action === 'toggle_status') {
        $userId = intval($_POST['user_id']);
        $sql = "UPDATE users SET is_active = NOT is_active WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        logAction($conn, 'Toggle User Status', "Toggled user status", null, null, null, "User ID: $userId");
        $success = 'User status updated';
    }
    
    if ($action === 'change_password') {
        $userId = intval($_POST['user_id']);
        $newPassword = $_POST['new_password'];
        $passwordHash = hashPassword($newPassword);
        
        $sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $passwordHash, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        logAction($conn, 'Change Password', "Changed user password", null, null, null, "User ID: $userId");
        $success = 'Password changed successfully';
    }
}

// Get all users
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
$users = array();
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-users"></i> User Management</h1>
    
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
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">All Users (<?php echo count($users); ?>)</h5>
        </div>
        <div class="card-body">
            <button class="btn btn-primary mb-3" onclick="openCreateUserModal()">
                <i class="fas fa-plus"></i> Create New User
            </button>
            
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge badge-info"><?php echo $user['role']; ?></span></td>
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
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning" title="Toggle Status">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-info" 
                                        onclick="openChangePasswordModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                        title="Change Password">
                                    <i class="fas fa-key"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New User</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username <span class="required">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="required">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Role <span class="required">*</span></label>
                        <select class="form-control" name="role" required>
                            <option value="Sales">Sales</option>
                            <option value="Design">Design</option>
                            <option value="Operation">Operation</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="user_id" id="change_password_user_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" class="form-control" id="change_password_username" readonly>
                    </div>
                    <div class="form-group">
                        <label>New Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateUserModal() {
    $('#createUserModal').modal('show');
}

function openChangePasswordModal(userId, username) {
    $('#change_password_user_id').val(userId);
    $('#change_password_username').val(username);
    $('#changePasswordModal').modal('show');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
