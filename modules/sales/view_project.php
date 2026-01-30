<?php
/**
 * Sales Module - View Project Details
 * 
 * Detailed view of a single project with files and history
 */

define('PAGE_TITLE', 'Project Details - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /dashboard.php");
    exit();
}

$conn = getDBConnection();
$projectId = intval($_GET['id']);
$userId = getCurrentUserId();
$role = getCurrentUserRole();

// Get project details
$sql = "SELECT p.*, u.full_name as created_by_name, u2.full_name as assigned_to_name
        FROM projects p
        LEFT JOIN users u ON p.created_by = u.user_id
        LEFT JOIN users u2 ON p.assigned_to = u2.user_id
        WHERE p.project_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    header("Location: /dashboard.php");
    exit();
}

// Check permissions (Sales can only view their own projects unless Admin)
if ($role === 'Sales' && $project['created_by'] != $userId) {
    header("Location: /dashboard.php?error=unauthorized");
    exit();
}

// Get project files
$sql = "SELECT f.*, u.full_name as uploaded_by_name
        FROM project_files f
        LEFT JOIN users u ON f.uploaded_by = u.user_id
        WHERE f.project_id = ?
        ORDER BY f.uploaded_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$files = array();
while ($row = mysqli_fetch_assoc($result)) {
    $files[] = $row;
}
mysqli_stmt_close($stmt);

// Get status history
$sql = "SELECT h.*, u.full_name as changed_by_name
        FROM project_status_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        WHERE h.project_id = ?
        ORDER BY h.changed_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$history = array();
while ($row = mysqli_fetch_assoc($result)) {
    $history[] = $row;
}
mysqli_stmt_close($stmt);

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-project-diagram"></i> Project Details</h1>
        <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    
    <!-- Project Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Project Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Project ID:</th>
                            <td><?php echo $project['project_id']; ?></td>
                        </tr>
                        <tr>
                            <th>Event Name:</th>
                            <td><strong><?php echo htmlspecialchars($project['event_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Event Date:</th>
                            <td><?php echo formatDate($project['event_date']); ?></td>
                        </tr>
                        <tr>
                            <th>Venue:</th>
                            <td><?php echo htmlspecialchars($project['venue']); ?></td>
                        </tr>
                        <tr>
                            <th>Exhibition Size:</th>
                            <td><?php echo htmlspecialchars($project['exhibition_size']) ?: '-'; ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Initial Cost:</th>
                            <td>$<?php echo number_format($project['initial_cost'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Final Cost:</th>
                            <td>
                                <?php if ($project['final_cost'] > 0): ?>
                                <strong>$<?php echo number_format($project['final_cost'], 2); ?></strong>
                                <?php else: ?>
                                <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge <?php echo getStatusBadgeClass($project['status']); ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td><?php echo htmlspecialchars($project['created_by_name']); ?></td>
                        </tr>
                        <tr>
                            <th>Timeline:</th>
                            <td>
                                <?php if ($project['timeline_days']): ?>
                                <?php echo $project['timeline_days']; ?> days
                                <?php else: ?>
                                <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($project['other_instructions']): ?>
            <div class="mt-3">
                <h6>Other Instructions:</h6>
                <p class="border p-3 bg-light"><?php echo nl2br(htmlspecialchars($project['other_instructions'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($project['design_comments']): ?>
            <div class="mt-3">
                <h6>Design Comments:</h6>
                <p class="border p-3 bg-light"><?php echo nl2br(htmlspecialchars($project['design_comments'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($project['ops_remarks']): ?>
            <div class="mt-3">
                <h6>Operation Remarks:</h6>
                <p class="border p-3 bg-light"><?php echo nl2br(htmlspecialchars($project['ops_remarks'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Project Files -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-paperclip"></i> Project Files (<?php echo count($files); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($files)): ?>
            <p class="text-muted">No files uploaded yet.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Stage</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['file_original_name']); ?></td>
                            <td>
                                <?php if (strpos($file['file_type'], 'image') !== false): ?>
                                <i class="fas fa-image text-primary"></i> Image
                                <?php else: ?>
                                <i class="fas fa-file-pdf text-danger"></i> PDF
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                            <td><span class="badge badge-info"><?php echo $file['upload_stage']; ?></span></td>
                            <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                            <td><?php echo formatDateTime($file['uploaded_at']); ?></td>
                            <td>
                                <a href="/<?php echo $file['file_path']; ?>" target="_blank" class="btn btn-sm btn-primary" title="View File">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Status History -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history"></i> Status History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($history)): ?>
            <p class="text-muted">No history available.</p>
            <?php else: ?>
            <div class="timeline">
                <?php foreach ($history as $item): ?>
                <div class="border-left pl-3 pb-3">
                    <div class="d-flex justify-content-between">
                        <strong>
                            <?php if ($item['old_status']): ?>
                            <?php echo $item['old_status']; ?> → 
                            <?php endif; ?>
                            <?php echo $item['new_status']; ?>
                        </strong>
                        <small class="text-muted"><?php echo formatDateTime($item['changed_at']); ?></small>
                    </div>
                    <div class="text-muted">
                        Changed by: <?php echo htmlspecialchars($item['changed_by_name']); ?>
                    </div>
                    <?php if ($item['remarks']): ?>
                    <div class="mt-1">
                        <small><?php echo nl2br(htmlspecialchars($item['remarks'])); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
