<?php
/**
 * Sales Module - Create New Project
 * 
 * Form to create new project/task with file uploads and email notifications
 */

define('PAGE_TITLE', 'Create New Project - BuzzNation PM');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole(['Sales', 'Admin']);

$conn = getDBConnection();
$errors = array();
$success = '';

// Get email notification users for selector
$emailUsers = array();
$sql = "SELECT * FROM email_notification_users WHERE is_active = 1 ORDER BY full_name";
$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $emailUsers[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $eventName = sanitizeInput($conn, $_POST['event_name'] ?? '');
    $eventDate = sanitizeInput($conn, $_POST['event_date'] ?? '');
    $venue = sanitizeInput($conn, $_POST['venue'] ?? '');
    $initialCost = sanitizeInput($conn, $_POST['initial_cost'] ?? '0');
    $exhibitionSize = sanitizeInput($conn, $_POST['exhibition_size'] ?? '');
    $otherInstructions = sanitizeInput($conn, $_POST['other_instructions'] ?? '');
    $salesConfirmed = isset($_POST['sales_confirmed']) ? 1 : 0;
    $selectedEmailUsers = $_POST['email_users'] ?? array();
    
    // Validation
    if (empty($eventName)) {
        $errors[] = 'Event name is required';
    }
    if (empty($eventDate)) {
        $errors[] = 'Event date is required';
    }
    if (empty($venue)) {
        $errors[] = 'Venue is required';
    }
    if (!$salesConfirmed) {
        $errors[] = 'Please confirm that all information is correct';
    }
    
    if (empty($errors)) {
        // Insert project
        $userId = getCurrentUserId();
        $sql = "INSERT INTO projects (event_name, event_date, venue, initial_cost, exhibition_size, other_instructions, sales_confirmed, created_by, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending Design Review')";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssdssii", $eventName, $eventDate, $venue, $initialCost, $exhibitionSize, $otherInstructions, $salesConfirmed, $userId);
        
        if (mysqli_stmt_execute($stmt)) {
            $projectId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            
            // Handle file uploads
            if (isset($_FILES['project_files']) && !empty($_FILES['project_files']['name'][0])) {
                $fileCount = count($_FILES['project_files']['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['project_files']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = array(
                            'name' => $_FILES['project_files']['name'][$i],
                            'type' => $_FILES['project_files']['type'][$i],
                            'tmp_name' => $_FILES['project_files']['tmp_name'][$i],
                            'error' => $_FILES['project_files']['error'][$i],
                            'size' => $_FILES['project_files']['size'][$i]
                        );
                        
                        $uploadResult = handleFileUpload($file, $projectId, 'Sales Initial');
                        
                        if ($uploadResult['success']) {
                            saveFileToDatabase($conn, $projectId, $uploadResult, 'Sales Initial');
                        }
                    }
                }
            }
            
            // Record status in history
            $sql = "INSERT INTO project_status_history (project_id, changed_by, old_status, new_status, remarks) 
                    VALUES (?, ?, NULL, 'Pending Design Review', 'Project created')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $projectId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Create notifications for Design team
            createRoleNotification($conn, 'Design', $projectId, 'New Project', "New project '$eventName' submitted for design review");
            
            // Save selected email users
            if (!empty($selectedEmailUsers)) {
                foreach ($selectedEmailUsers as $emailUserId) {
                    $sql = "INSERT INTO project_email_notifications (project_id, email_user_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $projectId, $emailUserId);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    
                    // Get email user details
                    $sql = "SELECT email, full_name FROM email_notification_users WHERE email_user_id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "i", $emailUserId);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $emailUser = mysqli_fetch_assoc($result);
                    mysqli_stmt_close($stmt);
                    
                    // Send email notification
                    $subject = "New Project: $eventName";
                    $message = "<h3>New Project Created</h3>";
                    $message .= "<p><strong>Event:</strong> $eventName</p>";
                    $message .= "<p><strong>Date:</strong> " . formatDate($eventDate) . "</p>";
                    $message .= "<p><strong>Venue:</strong> $venue</p>";
                    $message .= "<p><strong>Project Link:</strong> <a href='http://{$_SERVER['HTTP_HOST']}/modules/design/view_project.php?id=$projectId'>View Project</a></p>";
                    
                    sendEmail($emailUser['email'], $subject, $message);
                }
            }
            
            // Log action
            logAction($conn, 'Create Project', "Created new project: $eventName", $projectId, null, 'Pending Design Review', "Initial cost: $initialCost");
            
            $success = 'Project created successfully!';
            
            // Redirect after 2 seconds
            header("refresh:2;url=/modules/sales/my_projects.php");
        } else {
            $errors[] = 'Failed to create project. Please try again.';
        }
    }
}

closeDBConnection($conn);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mb-4"><i class="fas fa-plus-circle"></i> Create New Project</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?> Redirecting...
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="" id="projectForm" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="event_name">Event Name <span class="required">*</span></label>
                            <input type="text" class="form-control" id="event_name" name="event_name" 
                                   value="<?php echo isset($_POST['event_name']) ? htmlspecialchars($_POST['event_name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="event_date">Event Date <span class="required">*</span></label>
                            <input type="date" class="form-control" id="event_date" name="event_date" 
                                   value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="initial_cost">Initial/Estimated Cost</label>
                            <input type="number" step="0.01" class="form-control" id="initial_cost" name="initial_cost" 
                                   value="<?php echo isset($_POST['initial_cost']) ? htmlspecialchars($_POST['initial_cost']) : '0'; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="venue">Venue <span class="required">*</span></label>
                            <input type="text" class="form-control" id="venue" name="venue" 
                                   value="<?php echo isset($_POST['venue']) ? htmlspecialchars($_POST['venue']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exhibition_size">Exhibition Size</label>
                            <input type="text" class="form-control" id="exhibition_size" name="exhibition_size" 
                                   value="<?php echo isset($_POST['exhibition_size']) ? htmlspecialchars($_POST['exhibition_size']) : ''; ?>" 
                                   placeholder="e.g., 10x10 sqft">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="other_instructions">Other Instructions</label>
                    <textarea class="form-control" id="other_instructions" name="other_instructions" rows="4"><?php echo isset($_POST['other_instructions']) ? htmlspecialchars($_POST['other_instructions']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="project_files">Upload Files (Images/PDFs)</label>
                    <input type="file" class="form-control-file" id="project_files" name="project_files[]" 
                           multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                    <small class="form-text text-muted">Maximum file size: 10MB. Allowed types: JPG, PNG, GIF, PDF</small>
                </div>
                
                <div class="form-group">
                    <label>Email Notification Recipients</label>
                    <div class="border p-3" style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($emailUsers as $emailUser): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="email_users[]" 
                                   value="<?php echo $emailUser['email_user_id']; ?>" 
                                   id="email_user_<?php echo $emailUser['email_user_id']; ?>">
                            <label class="form-check-label" for="email_user_<?php echo $emailUser['email_user_id']; ?>">
                                <?php echo htmlspecialchars($emailUser['full_name']); ?> 
                                (<?php echo htmlspecialchars($emailUser['email']); ?>)
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="sales_confirmed" name="sales_confirmed" required>
                        <label class="form-check-label" for="sales_confirmed">
                            <span class="required">*</span> I confirm that all information entered is correct and validated
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Create Project
                    </button>
                    <a href="/modules/sales/my_projects.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Client-side validation
    $('#projectForm').on('submit', function(e) {
        let eventName = $('#event_name').val().trim();
        let eventDate = $('#event_date').val();
        let venue = $('#venue').val().trim();
        let confirmed = $('#sales_confirmed').is(':checked');
        
        if (!eventName) {
            alert('Event name is required');
            e.preventDefault();
            return false;
        }
        
        if (!eventDate) {
            alert('Event date is required');
            e.preventDefault();
            return false;
        }
        
        if (!venue) {
            alert('Venue is required');
            e.preventDefault();
            return false;
        }
        
        if (!confirmed) {
            alert('Please confirm that all information is correct');
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
