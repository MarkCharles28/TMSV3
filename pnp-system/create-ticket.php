<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to create a ticket', 'danger');
    redirect('login.php');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Define available departments
$departments = [
    'treasury' => 'Treasury Department',
    'engineering' => 'Engineering Department',
    'admin' => 'Administrative Department',
    'planning' => 'Planning Department',
    'health' => 'Health Department',
    'social' => 'Social Welfare Department'
];

// Define ticket priorities
$priorities = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
];

$errors = [];
$formData = [
    'subject' => '',
    'department' => '',
    'priority' => 'medium',
    'message' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    foreach ($formData as $key => $value) {
        $formData[$key] = sanitize($_POST[$key] ?? '');
    }
    
    // Validate form data
    if (empty($formData['subject'])) {
        $errors[] = 'Subject is required.';
    }
    
    if (empty($formData['department']) || !array_key_exists($formData['department'], $departments)) {
        $errors[] = 'Please select a valid department.';
    }
    
    if (empty($formData['priority']) || !array_key_exists($formData['priority'], $priorities)) {
        $errors[] = 'Please select a valid priority.';
    }
    
    if (empty($formData['message'])) {
        $errors[] = 'Message is required.';
    }
    
    // Process file upload if present
    $attachment = '';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $attachment_result = uploadFile($_FILES['attachment'], 'tickets/');
        if ($attachment_result === false) {
            $errors[] = 'Invalid file. Please upload a valid file (PDF, DOC, DOCX, JPG, JPEG, PNG) under 5MB.';
        } else {
            $attachment = $attachment_result;
        }
    }
    
    // If no errors, create the ticket
    if (empty($errors)) {
        $status = 'new';
        $ticket_number = generateTicketNumber();
        
        $stmt = $conn->prepare("INSERT INTO tickets (user_id, ticket_number, subject, department, priority, message, attachment, status, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $stmt->bind_param("isssssss", 
            $user_id, 
            $ticket_number,
            $formData['subject'], 
            $formData['department'], 
            $formData['priority'], 
            $formData['message'],
            $attachment,
            $status
        );
        
        if ($stmt->execute()) {
            $ticket_id = $conn->insert_id;
            
            // Log activity
            logActivity('create_ticket', 'Created new ticket #' . $ticket_number);
            
            // Create notification for admin
            createAdminNotification('New Ticket Created', 'A new ticket has been created: ' . $formData['subject'], 'ticket-details.php?id=' . $ticket_id);
            
            // Set flash message
            setFlashMessage('Your ticket has been created successfully.', 'success');
            redirect('ticket-details.php?id=' . $ticket_id);
        } else {
            $errors[] = 'Failed to create ticket. Please try again.';
        }
    }
}

/**
 * Generate a unique ticket number
 * @return string Ticket number
 */
function generateTicketNumber() {
    $prefix = 'TKT';
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    return $prefix . $date . $random;
}

/**
 * Create notification for admin users
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $link Link to resource
 * @return void
 */
function createAdminNotification($title, $message, $link = '') {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($admin = $result->fetch_assoc()) {
        $admin_id = $admin['id'];
        
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $admin_id, $title, $message, $link);
        $stmt->execute();
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Create New Ticket</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form action="create-ticket.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="subject" class="form-label required-field">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($formData['subject']); ?>" required>
                        <div class="invalid-feedback">Please enter a subject for your ticket.</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="department" class="form-label required-field">Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="" disabled <?php echo empty($formData['department']) ? 'selected' : ''; ?>>Select Department</option>
                                <?php foreach ($departments as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $formData['department'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a department.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label required-field">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                <?php foreach ($priorities as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo $formData['priority'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a priority level.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label required-field">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($formData['message']); ?></textarea>
                        <div class="invalid-feedback">Please enter a message describing your issue.</div>
                        <small class="text-muted">Please provide as much detail as possible about your issue.</small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="attachment" class="form-label">Attachment (optional)</label>
                        <input type="file" class="form-control" id="attachment" name="attachment">
                        <div class="form-text">Allowed file types: PDF, DOC, DOCX, JPG, JPEG, PNG (max 5MB)</div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Ticket
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?> 