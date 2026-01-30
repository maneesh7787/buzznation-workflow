<?php
/**
 * Sales Module - My Projects
 * 
 * List of all projects created by the current sales user
 */

define('PAGE_TITLE', 'My Projects - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Sales', 'Admin']);

$conn = getDBConnection();
$userId = getCurrentUserId();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Search and filter
$search = isset($_GET['search']) ? sanitizeInput($conn, $_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? sanitizeInput($conn, $_GET['status']) : '';

// Build query
$whereClause = "WHERE p.created_by = $userId";

if ($search) {
    $whereClause .= " AND (p.event_name LIKE '%$search%' OR p.venue LIKE '%$search%')";
}

if ($statusFilter) {
    $whereClause .= " AND p.status = '$statusFilter'";
}

// Get total count
$sql = "SELECT COUNT(*) as total FROM projects p $whereClause";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

// Get projects
$sql = "SELECT p.*, 
        (SELECT COUNT(*) FROM project_files WHERE project_id = p.project_id) as file_count
        FROM projects p 
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
    <h1 class="mb-4"><i class="fas fa-list"></i> My Projects</h1>
    
    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="form-inline">
                <div class="form-group mr-3">
                    <input type="text" class="form-control" name="search" placeholder="Search projects..." 
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
                
                <a href="/modules/sales/my_projects.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>
    </div>
    
    <!-- Projects Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Projects (<?php echo $totalProjects; ?>)</h5>
            <a href="/modules/sales/create_project.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> New Project
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
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
                            <th>Initial Cost</th>
                            <th>Final Cost</th>
                            <th>Status</th>
                            <th>Files</th>
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
                            <td>$<?php echo number_format($project['initial_cost'], 2); ?></td>
                            <td>
                                <?php if ($project['final_cost'] > 0): ?>
                                $<?php echo number_format($project['final_cost'], 2); ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td>
                                <i class="fas fa-paperclip"></i> <?php echo $project['file_count']; ?>
                            </td>
                            <td><?php echo formatDateTime($project['created_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <?php if ($project['status'] === 'Reviewed & Sent to Sales'): ?>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="openClientUpdateModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['event_name']); ?>')"
                                        title="Request Client Update">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
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

<!-- Client Update Modal -->
<div class="modal fade" id="clientUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Client Update</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="/modules/sales/client_update.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="update_project_id">
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" class="form-control" id="update_event_name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="update_instructions">Update Instructions <span class="required">*</span></label>
                        <textarea class="form-control" name="update_instructions" id="update_instructions" 
                                  rows="4" required placeholder="Describe the changes needed..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="update_files">Upload Files (optional)</label>
                        <input type="file" class="form-control-file" name="update_files[]" 
                               id="update_files" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                        <small class="form-text text-muted">Maximum file size: 10MB. Allowed types: JPG, PNG, GIF, PDF</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openClientUpdateModal(projectId, eventName) {
    $('#update_project_id').val(projectId);
    $('#update_event_name').val(eventName);
    $('#update_instructions').val('');
    $('#update_files').val('');
    $('#clientUpdateModal').modal('show');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
