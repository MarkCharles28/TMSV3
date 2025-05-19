<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to view ticket details', 'danger');
    redirect('login.php');
}

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if ticket exists and user has permission to view it
$ticket = null;
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

if ($ticket_id > 0) {
    if ($is_admin) {
        // Admin can view any ticket
        $stmt = $conn->prepare("SELECT t.*, u.username, u.first_name, u.last_name, u.email 
                               FROM tickets t
                               JOIN users u ON t.user_id = u.id
                               WHERE t.id = ?");
        $stmt->bind_param("i", $ticket_id);
    } else {
        // Regular users can only view their own tickets
        $stmt = $conn->prepare("SELECT t.*, u.username, u.first_name, u.last_name, u.email 
                               FROM tickets t
                               JOIN users u ON t.user_id = u.id
                               WHERE t.id = ? AND t.user_id = ?");
        $stmt->bind_param("ii", $ticket_id, $user_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $ticket = $result->fetch_assoc();
    }
}

// If ticket not found or user doesn't have permission
if (!$ticket) {
    setFlashMessage('Ticket not found or you do not have permission to view it', 'danger');
    redirect('dashboard.php');
}

// Define available departments (for admin to change)
$departments = [
    'treasury' => 'Treasury Department',
    'engineering' => 'Engineering Department',
    'admin' => 'Administrative Department',
    'planning' => 'Planning Department',
    'health' => 'Health Department',
    'social' => 'Social Welfare Department'
];

// Define ticket priorities (for admin to change)
$priorities = [
    'low' => 'Low',
    'medium' => 'Medium',
    'high' => 'High',
    'urgent' => 'Urgent'
];

// Define ticket statuses (for admin to change)
$statuses = [
    'new' => 'New',
    'in-progress' => 'In Progress',
    'resolved' => 'Resolved',
    'closed' => 'Closed'
];

// Handle status update (admin only)
if ($is_admin && isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status']);
    $new_department = sanitize($_POST['department']);
    $new_priority = sanitize($_POST['priority']);
    
    // Validate inputs
    if (array_key_exists($new_status, $statuses) && 
        array_key_exists($new_department, $departments) && 
        array_key_exists($new_priority, $priorities)) {
        
        $stmt = $conn->prepare("UPDATE tickets SET status = ?, department = ?, priority = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $new_status, $new_department, $new_priority, $ticket_id);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity('update_ticket', 'Updated ticket #' . $ticket['ticket_number'] . ' status to ' . $new_status);
            
            // Create notification for ticket owner
            if ($ticket['user_id'] != $user_id) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, created_at) 
                                       VALUES (?, ?, ?, ?, NOW())");
                $title = 'Ticket Updated';
                $message = 'Your ticket #' . $ticket['ticket_number'] . ' has been updated to status: ' . $statuses[$new_status];
                $link = 'ticket-details.php?id=' . $ticket_id;
                $stmt->bind_param("isss", $ticket['user_id'], $title, $message, $link);
                $stmt->execute();
            }
            
            // Update ticket in our local variable
            $ticket['status'] = $new_status;
            $ticket['department'] = $new_department;
            $ticket['priority'] = $new_priority;
            
            setFlashMessage('Ticket has been updated successfully', 'success');
        } else {
            setFlashMessage('Failed to update ticket status', 'danger');
        }
    } else {
        setFlashMessage('Invalid status, department or priority', 'danger');
    }
}

// Get ticket comments/replies
$stmt = $conn->prepare("SELECT c.*, u.username, u.first_name, u.last_name, u.role
                       FROM ticket_comments c
                       JOIN users u ON c.user_id = u.id
                       WHERE c.ticket_id = ?
                       ORDER BY c.created_at ASC");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle new comment submission
if (isset($_POST['submit_comment'])) {
    $comment_text = sanitize($_POST['comment']);
    $is_internal = isset($_POST['is_internal']) && $_POST['is_internal'] == '1' ? 1 : 0;
    
    // Only admins can create internal notes
    if (!$is_admin) {
        $is_internal = 0;
    }
    
    if (!empty($comment_text)) {
        $stmt = $conn->prepare("INSERT INTO ticket_comments (ticket_id, user_id, comment, is_internal, created_at) 
                               VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iisi", $ticket_id, $user_id, $comment_text, $is_internal);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity('add_comment', 'Added comment to ticket #' . $ticket['ticket_number']);
            
            // Create notification for ticket owner or admin
            if (!$is_internal) {
                $notify_user_id = ($ticket['user_id'] == $user_id) ? getAdminId() : $ticket['user_id'];
                
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link, created_at) 
                                       VALUES (?, ?, ?, ?, NOW())");
                $title = 'New Comment on Ticket';
                $message = 'A new comment has been added to ticket #' . $ticket['ticket_number'];
                $link = 'ticket-details.php?id=' . $ticket_id;
                $stmt->bind_param("isss", $notify_user_id, $title, $message, $link);
                $stmt->execute();
            }
            
            setFlashMessage('Your comment has been added successfully', 'success');
            redirect('ticket-details.php?id=' . $ticket_id);
        } else {
            setFlashMessage('Failed to add comment', 'danger');
        }
    } else {
        setFlashMessage('Comment cannot be empty', 'danger');
    }
}

/**
 * Get an admin ID for notifications
 * @return int Admin user ID
 */
function getAdminId() {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc()['id'];
    }
    return 1; // Default to ID 1 if no admin found
}

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>
            Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?>
            <span class="status-badge status-<?php echo $ticket['status']; ?> ms-2">
                <?php echo ucfirst($ticket['status']); ?>
            </span>
        </h1>
        <h5 class="text-muted"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
        <?php if ($is_admin): ?>
        <a href="tickets-management.php" class="btn btn-outline-primary ms-2">
            <i class="fas fa-list me-2"></i>All Tickets
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ticket Details</h5>
            </div>
            <div class="card-body">
                <div class="ticket-message mb-4">
                    <?php echo nl2br(htmlspecialchars($ticket['message'])); ?>
                </div>
                
                <?php if (!empty($ticket['attachment'])): ?>
                <div class="mb-3">
                    <strong>Attachment:</strong>
                    <a href="<?php echo SITE_URL . 'uploads/' . htmlspecialchars($ticket['attachment']); ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-paperclip me-1"></i>View Attachment
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="text-muted small">
                    <p>Created on <?php echo formatDate($ticket['created_at']); ?></p>
                    <p>Last updated on <?php echo formatDate($ticket['updated_at']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Comments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Comments</h5>
            </div>
            <div class="card-body">
                <?php if (count($comments) > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php if (!$comment['is_internal'] || $is_admin): // Only show internal notes to admins ?>
                        <div class="comment-item mb-4 <?php echo $comment['is_internal'] ? 'internal-note bg-light' : ''; ?>">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <div class="avatar bg-<?php echo $comment['role'] === 'admin' ? 'primary' : 'secondary'; ?> text-white">
                                        <?php echo strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1)); ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                            <?php if ($comment['role'] === 'admin'): ?>
                                            <span class="badge bg-primary ms-2">Staff</span>
                                            <?php endif; ?>
                                            <?php if ($comment['is_internal']): ?>
                                            <span class="badge bg-warning text-dark ms-2">Internal Note</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?php echo formatDate($comment['created_at']); ?></small>
                                    </div>
                                    <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted">No comments yet.</p>
                <?php endif; ?>
                
                <!-- Add Comment Form -->
                <form action="ticket-details.php?id=<?php echo $ticket_id; ?>" method="post" class="mt-4">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Add a Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1">
                        <label class="form-check-label" for="is_internal">
                            Internal Note (only visible to staff)
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="submit_comment" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Submit Comment
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Ticket Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Ticket Information</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Department:</span>
                        <span class="badge bg-info"><?php echo $departments[$ticket['department']] ?? $ticket['department']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Priority:</span>
                        <span class="badge bg-<?php 
                            echo $ticket['priority'] === 'low' ? 'success' : 
                                ($ticket['priority'] === 'medium' ? 'info' : 
                                ($ticket['priority'] === 'high' ? 'warning' : 'danger')); 
                            ?>">
                            <?php echo ucfirst($ticket['priority']); ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Status:</span>
                        <span class="status-badge status-<?php echo $ticket['status']; ?>">
                            <?php echo ucfirst($ticket['status']); ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- User Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Submitted By</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($ticket['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($ticket['email']); ?></p>
            </div>
        </div>
        
        <?php if ($is_admin): ?>
        <!-- Admin Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Admin Actions</h5>
            </div>
            <div class="card-body">
                <form action="ticket-details.php?id=<?php echo $ticket_id; ?>" method="post">
                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status</label>
                        <select class="form-select" id="status" name="status">
                            <?php foreach ($statuses as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $ticket['status'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department">
                            <?php foreach ($departments as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $ticket['department'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <?php foreach ($priorities as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo $ticket['priority'] === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Update Ticket
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.comment-item {
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.comment-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.internal-note {
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #ffc107;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 