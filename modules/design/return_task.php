<?php
/**
 * Design Module - Return Task Handler
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Design', 'Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/design/pending.php");
    exit();
}

$conn = getDBConnection();
$projectId = intval($_POST['project_id']);
$returnReason = sanitizeInput($conn, $_POST['return_reason']);
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
    header("Location: /modules/design/pending.php?error=not_found");
    exit();
}

// Update project status and add return reason to design comments
$sql = "UPDATE projects SET status = 'Returned by Design', design_comments = ? WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $returnReason, $projectId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Record status change
$sql = "INSERT INTO project_status_history (project_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, 'Returned by Design', ?)";
$stmt = mysqli_prepare($conn, $sql);
$oldStatus = $project['status'];
mysqli_stmt_bind_param($stmt, "iiss", $projectId, $userId, $oldStatus, $returnReason);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Create notification for Sales
createNotification($conn, $project['created_by'], $projectId, 'Task Returned', "Your project '{$project['event_name']}' has been returned by Design team. Reason: $returnReason");

// Log action
logAction($conn, 'Return Task', "Returned task to Sales", $projectId, $oldStatus, 'Returned by Design', $returnReason);

closeDBConnection($conn);

header("Location: /modules/design/pending.php?success=task_returned");
exit();
?>
