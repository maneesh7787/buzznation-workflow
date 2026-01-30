<?php
/**
 * Common Functions
 * 
 * Reusable utility functions for the application
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';

/**
 * Log user action to database
 * 
 * @param mysqli $conn Database connection
 * @param string $actionType Type of action
 * @param string $actionDescription Description of action
 * @param int|null $projectId Project ID if applicable
 * @param string|null $oldStatus Old status if status change
 * @param string|null $newStatus New status if status change
 * @param string|null $remarks Additional remarks
 */
function logAction($conn, $actionType, $actionDescription, $projectId = null, $oldStatus = null, $newStatus = null, $remarks = null) {
    $userId = getCurrentUserId();
    $role = getCurrentUserRole();
    $ipAddress = getClientIP();
    
    $sql = "INSERT INTO logs (user_id, role, action_type, action_description, project_id, old_status, new_status, remarks, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isssissss", $userId, $role, $actionType, $actionDescription, $projectId, $oldStatus, $newStatus, $remarks, $ipAddress);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Create notification for user
 * 
 * @param mysqli $conn Database connection
 * @param int $userId User ID to notify
 * @param int $projectId Project ID
 * @param string $type Notification type
 * @param string $message Notification message
 */
function createNotification($conn, $userId, $projectId, $type, $message) {
    $sql = "INSERT INTO notifications (user_id, project_id, notification_type, message) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iiss", $userId, $projectId, $type, $message);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/**
 * Create notifications for all users with specific role
 * 
 * @param mysqli $conn Database connection
 * @param string $role Role to notify
 * @param int $projectId Project ID
 * @param string $type Notification type
 * @param string $message Notification message
 */
function createRoleNotification($conn, $role, $projectId, $type, $message) {
    $sql = "SELECT user_id FROM users WHERE role = ? AND is_active = 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        createNotification($conn, $row['user_id'], $projectId, $type, $message);
    }
    
    mysqli_stmt_close($stmt);
}

/**
 * Get unread notification count for current user
 * 
 * @param mysqli $conn Database connection
 * @return int Unread notification count
 */
function getUnreadNotificationCount($conn) {
    $userId = getCurrentUserId();
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['count'];
}

/**
 * Update project status
 * 
 * @param mysqli $conn Database connection
 * @param int $projectId Project ID
 * @param string $newStatus New status
 * @param string|null $remarks Remarks
 */
function updateProjectStatus($conn, $projectId, $newStatus, $remarks = null) {
    // Get old status
    $sql = "SELECT status FROM projects WHERE project_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $projectId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $project = mysqli_fetch_assoc($result);
    $oldStatus = $project['status'];
    mysqli_stmt_close($stmt);
    
    // Update project status
    $sql = "UPDATE projects SET status = ? WHERE project_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $newStatus, $projectId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Record status change in history
    $userId = getCurrentUserId();
    $sql = "INSERT INTO project_status_history (project_id, changed_by, old_status, new_status, remarks) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisss", $projectId, $userId, $oldStatus, $newStatus, $remarks);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Log the action
    logAction($conn, 'Status Change', "Changed project status from '$oldStatus' to '$newStatus'", $projectId, $oldStatus, $newStatus, $remarks);
}

/**
 * Handle file upload
 * 
 * @param array $file File from $_FILES
 * @param int $projectId Project ID
 * @param string $stage Upload stage
 * @return array Array with 'success' and 'message' or 'file_path'
 */
function handleFileUpload($file, $projectId, $stage) {
    // Allowed file types
    $allowedTypes = array('image/jpeg', 'image/png', 'image/gif', 'application/pdf');
    $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf');
    
    // Maximum file size: 10MB
    $maxSize = 10 * 1024 * 1024;
    
    // Validate file
    if (!isset($file['error']) || is_array($file['error'])) {
        return array('success' => false, 'message' => 'Invalid file upload');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('success' => false, 'message' => 'Upload error occurred');
    }
    
    if ($file['size'] > $maxSize) {
        return array('success' => false, 'message' => 'File size exceeds 10MB limit');
    }
    
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension']);
    
    if (!in_array($extension, $allowedExtensions)) {
        return array('success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and PDF allowed');
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return array('success' => false, 'message' => 'Invalid file type detected');
    }
    
    // Create project folder if not exists
    $uploadDir = __DIR__ . "/../uploads/projects/project_$projectId/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $newFileName = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return array('success' => false, 'message' => 'Failed to move uploaded file');
    }
    
    return array(
        'success' => true,
        'file_name' => $newFileName,
        'file_original_name' => $file['name'],
        'file_path' => "uploads/projects/project_$projectId/" . $newFileName,
        'file_type' => $mimeType,
        'file_size' => $file['size']
    );
}

/**
 * Save uploaded file to database
 * 
 * @param mysqli $conn Database connection
 * @param int $projectId Project ID
 * @param array $fileInfo File information from handleFileUpload
 * @param string $stage Upload stage
 * @return int|false File ID or false on failure
 */
function saveFileToDatabase($conn, $projectId, $fileInfo, $stage) {
    $userId = getCurrentUserId();
    
    $sql = "INSERT INTO project_files (project_id, uploaded_by, file_name, file_original_name, file_path, file_type, file_size, upload_stage) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iisssis", 
        $projectId, 
        $userId, 
        $fileInfo['file_name'], 
        $fileInfo['file_original_name'], 
        $fileInfo['file_path'], 
        $fileInfo['file_type'], 
        $fileInfo['file_size'], 
        $stage
    );
    
    $result = mysqli_stmt_execute($stmt);
    $fileId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    return $result ? $fileId : false;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

/**
 * Format datetime for display
 * 
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return date('d M Y h:i A', strtotime($datetime));
}

/**
 * Get status badge class for Bootstrap
 * 
 * @param string $status Status string
 * @return string Bootstrap badge class
 */
function getStatusBadgeClass($status) {
    $classes = array(
        'Submitted by Sales' => 'badge-primary',
        'Pending Design Review' => 'badge-warning',
        'Accepted by Design' => 'badge-info',
        'Returned by Design' => 'badge-danger',
        'Design Work In Progress' => 'badge-info',
        'Completed & Sent to OPS' => 'badge-success',
        'Reviewed by OPS' => 'badge-success',
        'Reviewed & Sent to Sales' => 'badge-primary',
        'Client Update Requested' => 'badge-warning',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-dark'
    );
    
    return isset($classes[$status]) ? $classes[$status] : 'badge-secondary';
}

/**
 * Send email notification
 * 
 * @param string $to Email address
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool True if sent successfully
 */
function sendEmail($to, $subject, $message) {
    // In production, use a proper email library like PHPMailer
    // For now, we'll use PHP's mail() function
    $headers = "From: no-reply@buzznation.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>
