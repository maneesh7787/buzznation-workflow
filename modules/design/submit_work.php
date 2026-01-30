<?php
/**
 * Design Module - Submit Work Handler
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Design', 'Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/design/ongoing.php");
    exit();
}

$conn = getDBConnection();
$projectId = intval($_POST['project_id']);
$completionNotes = sanitizeInput($conn, $_POST['completion_notes']);
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
    header("Location: /modules/design/ongoing.php?error=not_found");
    exit();
}

// Handle file uploads
if (!isset($_FILES['design_files']) || empty($_FILES['design_files']['name'][0])) {
    header("Location: /modules/design/ongoing.php?error=no_files");
    exit();
}

$fileCount = count($_FILES['design_files']['name']);
$uploadedCount = 0;

for ($i = 0; $i < $fileCount; $i++) {
    if ($_FILES['design_files']['error'][$i] === UPLOAD_ERR_OK) {
        $file = array(
            'name' => $_FILES['design_files']['name'][$i],
            'type' => $_FILES['design_files']['type'][$i],
            'tmp_name' => $_FILES['design_files']['tmp_name'][$i],
            'error' => $_FILES['design_files']['error'][$i],
            'size' => $_FILES['design_files']['size'][$i]
        );
        
        $uploadResult = handleFileUpload($file, $projectId, 'Design Work');
        
        if ($uploadResult['success']) {
            saveFileToDatabase($conn, $projectId, $uploadResult, 'Design Work');
            $uploadedCount++;
        }
    }
}

if ($uploadedCount === 0) {
    header("Location: /modules/design/ongoing.php?error=upload_failed");
    exit();
}

// Update project status
updateProjectStatus($conn, $projectId, 'Completed & Sent to OPS', $completionNotes);

// Create notifications for Operation team
createRoleNotification($conn, 'Operation', $projectId, 'Design Complete', "Design work completed for project: {$project['event_name']}. Ready for review.");

// Log action
logAction($conn, 'Submit Design Work', "Submitted design work to Operations", $projectId, $project['status'], 'Completed & Sent to OPS', "$uploadedCount files uploaded. Notes: $completionNotes");

closeDBConnection($conn);

header("Location: /modules/design/completed.php?success=work_submitted");
exit();
?>
