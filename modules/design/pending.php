<?php
/**
 * Design Module - Pending Tasks
 * 
 * Tasks that haven't been accepted yet by Design team
 */

define('PAGE_TITLE', 'Pending Tasks - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Design', 'Admin']);

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get pending tasks (NOT accepted yet - very important rule)
$sql = "SELECT COUNT(*) as total FROM projects 
        WHERE status IN ('Pending Design Review', 'Returned by Design', 'Client Update Requested')";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

// Get pending projects
$sql = "SELECT p.*, u.full_name as created_by_name,
        (SELECT COUNT(*) FROM project_files WHERE project_id = p.project_id AND upload_stage = 'Sales Initial') as initial_files
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE p.status IN ('Pending Design Review', 'Returned by Design', 'Client Update Requested')
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
    <h1 class="mb-4"><i class="fas fa-clock"></i> Pending Tasks</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> These tasks are waiting for your review. 
        <strong>They will NOT appear in your ongoing projects until you ACCEPT them.</strong>
    </div>
    
    <!-- Pending Tasks Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Pending Design Review (<?php echo $totalProjects; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                No pending tasks! Great job!
            </p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Venue</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th>Files</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo $project['project_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($project['event_name']); ?></strong></td>
                            <td><?php echo formatDate($project['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($project['venue']); ?></td>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td><i class="fas fa-paperclip"></i> <?php echo $project['initial_files']; ?></td>
                            <td><?php echo formatDateTime($project['created_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-success" 
                                        onclick="openAcceptModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['event_name']); ?>')"
                                        title="Accept Task">
                                    <i class="fas fa-check"></i> Accept
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="openReturnModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['event_name']); ?>')"
                                        title="Return Task">
                                    <i class="fas fa-times"></i> Return
                                </button>
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
                        <a class="page-link" href="?page=<?php echo $i; ?>">
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

<!-- Accept Task Modal -->
<div class="modal fade" id="acceptModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Accept Task</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="/modules/design/accept_task.php">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="accept_project_id">
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" class="form-control" id="accept_event_name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="timeline_days">Timeline (Days) <span class="required">*</span></label>
                        <input type="number" class="form-control" name="timeline_days" id="timeline_days" 
                               min="1" required placeholder="Enter number of days needed">
                    </div>
                    
                    <div class="form-group">
                        <label for="design_comments">Design Comments</label>
                        <textarea class="form-control" name="design_comments" id="design_comments" 
                                  rows="4" placeholder="Add any comments or notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Accept Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Task Modal -->
<div class="modal fade" id="returnModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Return Task</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="/modules/design/return_task.php">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="return_project_id">
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" class="form-control" id="return_event_name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="return_reason">Reason / Additional Information Needed <span class="required">*</span></label>
                        <textarea class="form-control" name="return_reason" id="return_reason" 
                                  rows="4" required placeholder="Explain what information or changes are needed..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Return Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAcceptModal(projectId, eventName) {
    $('#accept_project_id').val(projectId);
    $('#accept_event_name').val(eventName);
    $('#timeline_days').val('');
    $('#design_comments').val('');
    $('#acceptModal').modal('show');
}

function openReturnModal(projectId, eventName) {
    $('#return_project_id').val(projectId);
    $('#return_event_name').val(eventName);
    $('#return_reason').val('');
    $('#returnModal').modal('show');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
