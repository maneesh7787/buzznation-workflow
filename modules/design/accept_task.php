<?php
/**
 * Design Module - Accept Task Handler
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
$timelineDays = intval($_POST['timeline_days']);
$designComments = sanitizeInput($conn, $_POST['design_comments']);
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

// Update project - assign to current user, set timeline and comments
$sql = "UPDATE projects SET status = 'Accepted by Design', assigned_to = ?, timeline_days = ?, design_comments = ? WHERE project_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iisi", $userId, $timelineDays, $designComments, $projectId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Record status change
$sql = "INSERT INTO project_status_history (project_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, 'Accepted by Design', ?)";
$stmt = mysqli_prepare($conn, $sql);
$oldStatus = $project['status'];
$remarks = "Timeline: $timelineDays days. Comments: $designComments";
mysqli_stmt_bind_param($stmt, "iiss", $projectId, $userId, $oldStatus, $remarks);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Create notification for Sales
createNotification($conn, $project['created_by'], $projectId, 'Task Accepted', "Your project '{$project['event_name']}' has been accepted by Design team. Timeline: $timelineDays days");

// Log action
logAction($conn, 'Accept Task', "Accepted design task", $projectId, $oldStatus, 'Accepted by Design', $remarks);

closeDBConnection($conn);

header("Location: /modules/design/ongoing.php?success=task_accepted");
exit();
?>
