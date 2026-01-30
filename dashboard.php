<?php
/**
 * Dashboard Page
 * 
 * Main dashboard for all user roles with role-specific statistics
 */

define('PAGE_TITLE', 'Dashboard - BuzzNation PM');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

$conn = getDBConnection();
$role = getCurrentUserRole();
$userId = getCurrentUserId();

// Get statistics based on role
$stats = array();

if ($role === 'Admin') {
    // Admin sees all projects
    $sql = "SELECT COUNT(*) as total FROM projects";
    $result = mysqli_query($conn, $sql);
    $stats['total'] = mysqli_fetch_assoc($result)['total'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status IN ('Submitted by Sales', 'Pending Design Review')";
    $result = mysqli_query($conn, $sql);
    $stats['pending'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status IN ('Accepted by Design', 'Design Work In Progress')";
    $result = mysqli_query($conn, $sql);
    $stats['ongoing'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status = 'Completed'";
    $result = mysqli_query($conn, $sql);
    $stats['completed'] = mysqli_fetch_assoc($result)['count'];
    
} elseif ($role === 'Sales') {
    // Sales sees their own projects
    $sql = "SELECT COUNT(*) as total FROM projects WHERE created_by = $userId";
    $result = mysqli_query($conn, $sql);
    $stats['total'] = mysqli_fetch_assoc($result)['total'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE created_by = $userId AND status IN ('Submitted by Sales', 'Pending Design Review')";
    $result = mysqli_query($conn, $sql);
    $stats['pending'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE created_by = $userId AND status IN ('Accepted by Design', 'Design Work In Progress')";
    $result = mysqli_query($conn, $sql);
    $stats['ongoing'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE created_by = $userId AND status = 'Completed'";
    $result = mysqli_query($conn, $sql);
    $stats['completed'] = mysqli_fetch_assoc($result)['count'];
    
} elseif ($role === 'Design') {
    // Design sees tasks assigned to them or pending
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status = 'Pending Design Review'";
    $result = mysqli_query($conn, $sql);
    $stats['pending'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status IN ('Accepted by Design', 'Design Work In Progress')";
    $result = mysqli_query($conn, $sql);
    $stats['ongoing'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status = 'Completed & Sent to OPS'";
    $result = mysqli_query($conn, $sql);
    $stats['completed'] = mysqli_fetch_assoc($result)['count'];
    
    $stats['total'] = $stats['pending'] + $stats['ongoing'] + $stats['completed'];
    
} elseif ($role === 'Operation') {
    // Operation sees tasks sent to them
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status = 'Completed & Sent to OPS'";
    $result = mysqli_query($conn, $sql);
    $stats['pending'] = mysqli_fetch_assoc($result)['count'];
    
    $sql = "SELECT COUNT(*) as count FROM projects WHERE status = 'Reviewed by OPS'";
    $result = mysqli_query($conn, $sql);
    $stats['reviewed'] = mysqli_fetch_assoc($result)['count'];
    
    $stats['total'] = $stats['pending'] + $stats['reviewed'];
}

// Get recent projects (role-specific)
$recentProjects = array();
if ($role === 'Admin') {
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM projects p 
            LEFT JOIN users u ON p.created_by = u.user_id 
            ORDER BY p.created_at DESC LIMIT 10";
} elseif ($role === 'Sales') {
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM projects p 
            LEFT JOIN users u ON p.created_by = u.user_id 
            WHERE p.created_by = $userId 
            ORDER BY p.created_at DESC LIMIT 10";
} elseif ($role === 'Design') {
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM projects p 
            LEFT JOIN users u ON p.created_by = u.user_id 
            WHERE p.status IN ('Pending Design Review', 'Accepted by Design', 'Design Work In Progress', 'Completed & Sent to OPS') 
            ORDER BY p.created_at DESC LIMIT 10";
} elseif ($role === 'Operation') {
    $sql = "SELECT p.*, u.full_name as created_by_name 
            FROM projects p 
            LEFT JOIN users u ON p.created_by = u.user_id 
            WHERE p.status IN ('Completed & Sent to OPS', 'Reviewed by OPS') 
            ORDER BY p.created_at DESC LIMIT 10";
}

$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $recentProjects[] = $row;
}

closeDBConnection($conn);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4">
        <i class="fas fa-tachometer-alt"></i> Dashboard
        <small class="text-muted">Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?></small>
    </h1>
    
    <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> You are not authorized to access that page.
    </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row">
        <?php if ($role !== 'Operation'): ?>
        <div class="col-md-3">
            <div class="card stats-card primary">
                <div class="card-body">
                    <h5 class="card-title text-muted">Total Projects</h5>
                    <h2 class="mb-0"><?php echo $stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body">
                    <h5 class="card-title text-muted">
                        <?php echo $role === 'Operation' ? 'Pending Reviews' : 'Pending'; ?>
                    </h5>
                    <h2 class="mb-0"><?php echo $stats['pending']; ?></h2>
                </div>
            </div>
        </div>
        
        <?php if ($role !== 'Operation'): ?>
        <div class="col-md-3">
            <div class="card stats-card info">
                <div class="card-body">
                    <h5 class="card-title text-muted">Ongoing</h5>
                    <h2 class="mb-0"><?php echo $stats['ongoing']; ?></h2>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <h5 class="card-title text-muted">Completed</h5>
                    <h2 class="mb-0"><?php echo $stats['completed']; ?></h2>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-3">
            <div class="card stats-card success">
                <div class="card-body">
                    <h5 class="card-title text-muted">Reviewed</h5>
                    <h2 class="mb-0"><?php echo $stats['reviewed']; ?></h2>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Projects -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Recent Projects</h5>
        </div>
        <div class="card-body">
            <?php if (empty($recentProjects)): ?>
            <p class="text-muted">No projects found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentProjects as $project): ?>
                        <tr>
                            <td><?php echo $project['project_id']; ?></td>
                            <td><?php echo htmlspecialchars($project['event_name']); ?></td>
                            <td><?php echo formatDate($project['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($project['venue']); ?></td>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($project['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <?php if ($role === 'Sales' || $role === 'Admin'): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                    <h5>Create New Project</h5>
                    <p class="text-muted">Start a new project/task</p>
                    <a href="/modules/sales/create_project.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Project
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($role === 'Design' || $role === 'Admin'): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                    <h5>Pending Tasks</h5>
                    <p class="text-muted">Review and accept tasks</p>
                    <a href="/modules/design/pending.php" class="btn btn-warning">
                        <i class="fas fa-list"></i> View Pending
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($role === 'Operation' || $role === 'Admin'): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-check fa-3x text-info mb-3"></i>
                    <h5>Pending Reviews</h5>
                    <p class="text-muted">Review completed work</p>
                    <a href="/modules/operation/pending_reviews.php" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Reviews
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
