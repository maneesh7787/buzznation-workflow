<?php
/**
 * Operation Module - Reviewed Projects
 */

define('PAGE_TITLE', 'Reviewed Projects - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Operation', 'Admin']);

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Success message
$success = '';
if (isset($_GET['success']) && $_GET['success'] === 'review_submitted') {
    $success = 'Review submitted to Sales successfully!';
}

// Get reviewed projects
$sql = "SELECT COUNT(*) as total FROM projects WHERE status = 'Reviewed & Sent to Sales'";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

$sql = "SELECT p.*, u.full_name as created_by_name
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE p.status = 'Reviewed & Sent to Sales'
        ORDER BY p.updated_at DESC
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
    <h1 class="mb-4"><i class="fas fa-check-double"></i> Reviewed Projects</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Reviewed & Sent to Sales (<?php echo $totalProjects; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted">No reviewed projects yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Venue</th>
                            <th>Initial Cost</th>
                            <th>Final Cost</th>
                            <th>Created By</th>
                            <th>Reviewed</th>
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
                            <td>$<?php echo number_format($project['initial_cost'], 2); ?></td>
                            <td><strong>$<?php echo number_format($project['final_cost'], 2); ?></strong></td>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td><?php echo formatDateTime($project['updated_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
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
