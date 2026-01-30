<?php
/**
 * Sales Module - Client Update Handler
 * 
 * Processes client update requests
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Sales', 'Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /modules/sales/my_projects.php");
    exit();
}

$conn = getDBConnection();
$projectId = intval($_POST['project_id']);
$updateInstructions = sanitizeInput($conn, $_POST['update_instructions']);
$userId = getCurrentUserId();

// Verify project ownership
$sql = "SELECT * FROM projects WHERE project_id = ? AND created_by = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $projectId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$project = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$project) {
    header("Location: /modules/sales/my_projects.php?error=not_found");
    exit();
}

// Handle file uploads if any
if (isset($_FILES['update_files']) && !empty($_FILES['update_files']['name'][0])) {
    $fileCount = count($_FILES['update_files']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['update_files']['error'][$i] === UPLOAD_ERR_OK) {
            $file = array(
                'name' => $_FILES['update_files']['name'][$i],
                'type' => $_FILES['update_files']['type'][$i],
                'tmp_name' => $_FILES['update_files']['tmp_name'][$i],
                'error' => $_FILES['update_files']['error'][$i],
                'size' => $_FILES['update_files']['size'][$i]
            );
            
            $uploadResult = handleFileUpload($file, $projectId, 'Client Update');
            
            if ($uploadResult['success']) {
                saveFileToDatabase($conn, $projectId, $uploadResult, 'Client Update');
            }
        }
    }
}

// Update project status
updateProjectStatus($conn, $projectId, 'Client Update Requested', $updateInstructions);

// Create notification for Design team
createRoleNotification($conn, 'Design', $projectId, 'Client Update', "Client update requested for project: {$project['event_name']}");

// Log action
logAction($conn, 'Client Update Request', "Requested client update for project", $projectId, $project['status'], 'Client Update Requested', $updateInstructions);

closeDBConnection($conn);

header("Location: /modules/sales/my_projects.php?success=update_requested");
exit();
?>
