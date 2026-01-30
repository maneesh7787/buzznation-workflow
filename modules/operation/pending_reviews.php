<?php
/**
 * Operation Module - Pending Reviews
 * 
 * Projects completed by Design team awaiting OPS review
 */

define('PAGE_TITLE', 'Pending Reviews - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Operation', 'Admin']);

$conn = getDBConnection();

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get pending reviews
$sql = "SELECT COUNT(*) as total FROM projects WHERE status = 'Completed & Sent to OPS'";
$result = mysqli_query($conn, $sql);
$totalProjects = mysqli_fetch_assoc($result)['total'];
$totalPages = ceil($totalProjects / $perPage);

$sql = "SELECT p.*, u.full_name as created_by_name, u2.full_name as assigned_to_name,
        (SELECT COUNT(*) FROM project_files WHERE project_id = p.project_id AND upload_stage = 'Design Work') as design_files
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        LEFT JOIN users u2 ON p.assigned_to = u2.user_id
        WHERE p.status = 'Completed & Sent to OPS'
        ORDER BY p.updated_at ASC
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
    <h1 class="mb-4"><i class="fas fa-clipboard-check"></i> Pending Reviews</h1>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Projects Awaiting Review (<?php echo $totalProjects; ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($projects)): ?>
            <p class="text-muted text-center py-4">
                <i class="fas fa-check-circle fa-3x mb-3"></i><br>
                All caught up! No projects pending review.
            </p>
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
                            <th>Created By</th>
                            <th>Design By</th>
                            <th>Design Files</th>
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
                            <td>$<?php echo number_format($project['initial_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                            <td><?php echo htmlspecialchars($project['assigned_to_name'] ?? '-'); ?></td>
                            <td><i class="fas fa-paperclip"></i> <?php echo $project['design_files']; ?></td>
                            <td><?php echo formatDateTime($project['updated_at']); ?></td>
                            <td>
                                <a href="/modules/sales/view_project.php?id=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button class="btn btn-sm btn-success" 
                                        onclick="openReviewModal(<?php echo $project['project_id']; ?>, '<?php echo htmlspecialchars($project['event_name']); ?>', <?php echo $project['initial_cost']; ?>)"
                                        title="Review & Submit">
                                    <i class="fas fa-check-double"></i> Review
                                </button>
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

<!-- Review Project Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Project</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="/modules/operation/submit_review.php">
                <div class="modal-body">
                    <input type="hidden" name="project_id" id="review_project_id">
                    
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" class="form-control" id="review_event_name" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Initial Cost</label>
                                <input type="text" class="form-control" id="review_initial_cost" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="final_cost">Final Cost <span class="required">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="final_cost" 
                                       id="final_cost" required placeholder="Enter final cost">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ops_remarks">Operation Remarks <span class="required">*</span></label>
                        <textarea class="form-control" name="ops_remarks" id="ops_remarks" 
                                  rows="4" required placeholder="Add your review comments, notes, or observations..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> After submission, this project will be sent back to Sales team for final review.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane"></i> Submit Review to Sales
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReviewModal(projectId, eventName, initialCost) {
    $('#review_project_id').val(projectId);
    $('#review_event_name').val(eventName);
    $('#review_initial_cost').val('$' + parseFloat(initialCost).toFixed(2));
    $('#final_cost').val(initialCost);
    $('#ops_remarks').val('');
    $('#reviewModal').modal('show');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
