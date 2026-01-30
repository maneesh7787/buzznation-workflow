<?php
/**
 * Operation Module - Submit Review Handler
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Operation', 'Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/operation/pending_reviews.php");
    exit();
}

$conn = getDBConnection();
$projectId = intval($_POST['project_id']);
$finalCost = floatval($_POST['final_cost']);
$opsRemarks = sanitizeInput($conn, $_POST['ops_remarks']);
$userId = getCurrentUserId();

// Get project
$sql = "SELECT * FROM projects WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $projectId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    header("Location: /modules/operation/pending_reviews.php?error=not_found");
    exit();
}

// Update project with final cost and ops remarks
$sql = "UPDATE projects SET final_cost = ?, ops_remarks = ?, status = 'Reviewed & Sent to Sales' WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "dsi", $finalCost, $opsRemarks, $projectId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Record status change
$sql = "INSERT INTO project_status_history (project_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, 'Reviewed & Sent to Sales', ?)";
$stmt = mysqli_prepare($conn, $sql);
$oldStatus = $project['status'];
$remarks = "Final cost: $finalCost. OPS remarks: $opsRemarks";
mysqli_stmt_bind_param($stmt, "iiss", $projectId, $userId, $oldStatus, $remarks);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Create notification for Sales (project creator)
createNotification($conn, $project['created_by'], $projectId, 'Review Complete', "Project '{$project['event_name']}' has been reviewed by Operations. Final cost: \$$finalCost");

// Log action
logAction($conn, 'Review Project', "Reviewed and submitted to Sales", $projectId, $oldStatus, 'Reviewed & Sent to Sales', $remarks);

closeDBConnection($conn);

header("Location: /modules/operation/reviewed.php?success=review_submitted");
exit();
?>
