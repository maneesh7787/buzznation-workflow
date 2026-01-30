<?php
/**
 * Admin Module - All Projects
 */

define('PAGE_TITLE', 'All Projects - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('Admin');

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? sanitizeInput($conn, $_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($conn, $_GET['status']) : '';

// Build query
$whereClause = "WHERE 1=1";

if ($search) {
    $whereClause .= " AND (p.event_name LIKE '%$search%' OR p.venue LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

if ($statusFilter) {
    $whereClause .= " AND p.status = '$statusFilter'";
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM projects p LEFT JOIN users u ON p.created_by = u.user_id $whereClause";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

// Get projects
$sql = "SELECT p.*, u.full_name as created_by_name
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT $offset, $perPage";

$result = mysqli_query($conn, $sql);
$projects = array();
while ($row = mysqli_fetch_assoc($result)) {
    $projects[] = $row;
}

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-project-diagram"></i> All Projects</h1>
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <input type="text" class="form-control" name="search" placeholder="Search..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group mr-3">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="Pending Design Review" <?php echo $statusFilter === 'Pending Design Review' ? 'selected' : ''; ?>>Pending Design Review</option>
                        <option value="Accepted by Design" <?php echo $statusFilter === 'Accepted by Design' ? 'selected' : ''; ?>>Accepted by Design</option>
                        <option value="Returned by Design" <?php echo $statusFilter === 'Returned by Design' ? 'selected' : ''; ?>>Returned by Design</option>
                        <option value="Design Work In Progress" <?php echo $statusFilter === 'Design Work In Progress' ? 'selected' : ''; ?>>Design Work In Progress</option>
                        <option value="Completed & Sent to OPS" <?php echo $statusFilter === 'Completed & Sent to OPS' ? 'selected' : ''; ?>>Completed & Sent to OPS</option>
                        <option value="Reviewed & Sent to Sales" <?php echo $statusFilter === 'Reviewed & Sent to Sales' ? 'selected' : ''; ?>>Reviewed & Sent to Sales</option>
                        <option value="Client Update Requested" <?php echo $statusFilter === 'Client Update Requested' ? 'selected' : ''; ?>>Client Update Requested</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-search"></i> Search
                </button>
                
                <a href="/modules/admin/projects.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">All Projects (<?php echo $totalProjects; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted">No projects found.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Created By</th>
                            <th>Initial Cost</th>
                            <th>Final Cost</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['project_id']; ?></td>
                            <td><?php echo htmlspecialchars($project['event_name']); ?></td>
                            <td><?php echo formatDate($project['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($project['venue']); ?></td>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td>$<?php echo number_format($project['initial_cost'], 2); ?></td>
                            <td>
                                <?php if ($project['final_cost'] > 0): ?>
                                $<?php echo number_format($project['final_cost'], 2); ?>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($project['created_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
