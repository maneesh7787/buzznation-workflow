<?php
/**
 * Design Module - Ongoing Projects
 * 
 * Projects that have been ACCEPTED by Design team
 */

define('PAGE_TITLE', 'Ongoing Projects - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Design', 'Admin']);

$conn = getDBConnection();
$userId = getCurrentUserId();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Success message
$success = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'task_accepted') {
        $success = 'Task accepted successfully!';
    } elseif ($_GET['success'] === 'work_submitted') {
        $success = 'Design work submitted successfully!';
    }
}

// Get ongoing projects (accepted by Design)
$sql = "SELECT COUNT(*) as total FROM projects 
        WHERE status IN ('Accepted by Design', 'Design Work In Progress')";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

// Get projects
$sql = "SELECT p.*, u.full_name as created_by_name,
        (SELECT COUNT(*) FROM project_files WHERE project_id = p.project_id AND upload_stage = 'Design Work') as design_files
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        WHERE p.status IN ('Accepted by Design', 'Design Work In Progress')
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
    <h1 class="mb-4"><i class="fas fa-spinner"></i> Ongoing Projects</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <!-- Ongoing Projects Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Accepted & In Progress (<?php echo $totalProjects; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-inbox fa-3x mb-3"></i><br>
                No ongoing projects. Check pending tasks to accept new work.
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
                            <th>Timeline</th>
                            <th>Status</th>
                            <th>Design Files</th>
                            <th>Updated</th>
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
                            <td>
                                <?php if ($project['timeline_days']): ?>
                                <span class="badge badge-info"><?php echo $project['timeline_days']; ?> days</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                            <td><i class="fas fa-paperclip"></i> <?php echo $project['design_files']; ?></td>
                            <td><?php echo formatDateTime($project['updated_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-success" 
                                        onclick="openSubmitModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['event_name']); ?>')"
                                        title="Submit Design Work">
                                    <i class="fas fa-upload"></i> Submit
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

<!-- Submit Design Work Modal -->
<div class="modal fade" id="submitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Design Work</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="/modules/design/submit_work.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="submit_project_id">
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" class="form-control" id="submit_event_name" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="design_files">Upload Design Files <span class="required">*</span></label>
                        <input type="file" class="form-control-file" name="design_files[]" 
                               id="design_files" multiple accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                        <small class="form-text text-muted">
                            Upload your design work (images/PDFs). Maximum file size: 10MB per file.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="completion_notes">Completion Notes</label>
                        <textarea class="form-control" name="completion_notes" id="completion_notes" 
                                  rows="3" placeholder="Add any notes about the completed work..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Submit to Operations
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSubmitModal(projectId, eventName) {
    $('#submit_project_id').val(projectId);
    $('#submit_event_name').val(eventName);
    $('#design_files').val('');
    $('#completion_notes').val('');
    $('#submitModal').modal('show');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
