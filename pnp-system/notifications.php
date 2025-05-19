<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to view notifications', 'danger');
    redirect('login.php');
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Handle mark all as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Log activity
    logActivity('mark_notifications_read', 'Marked all notifications as read');
    
    setFlashMessage('All notifications marked as read', 'success');
    redirect('notifications.php');
}

// Handle mark single notification as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    
    // Log activity
    logActivity('mark_notification_read', 'Marked notification as read');
    
    setFlashMessage('Notification marked as read', 'success');
    redirect('notifications.php');
}

// Handle delete notification
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $notification_id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    
    // Log activity
    logActivity('delete_notification', 'Deleted notification');
    
    setFlashMessage('Notification deleted', 'success');
    redirect('notifications.php');
}

// Get all notifications for the user
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count unread notifications
$unread_count = 0;
foreach ($notifications as $notification) {
    if ($notification['is_read'] == 0) {
        $unread_count++;
    }
}

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Notifications</h1>
    <?php if ($unread_count > 0): ?>
    <form action="notifications.php" method="post">
        <button type="submit" name="mark_all_read" class="btn btn-primary">
            <i class="fas fa-check-double me-2"></i>Mark All as Read
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="card shadow">
    <div class="card-header py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Your Notifications</h5>
            <?php if ($unread_count > 0): ?>
            <span class="badge bg-primary"><?php echo $unread_count; ?> unread</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (count($notifications) > 0): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($notifications as $notification): ?>
            <li class="list-group-item notification-item <?php echo $notification['is_read'] == 0 ? 'unread' : ''; ?>" data-id="<?php echo $notification['id']; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="d-flex align-items-center">
                            <div class="notification-icon me-3">
                                <?php if (strpos($notification['title'], 'Ticket') !== false): ?>
                                <i class="fas fa-ticket-alt fa-lg text-primary"></i>
                                <?php elseif (strpos($notification['title'], 'Payment') !== false): ?>
                                <i class="fas fa-money-bill fa-lg text-success"></i>
                                <?php elseif (strpos($notification['title'], 'Account') !== false): ?>
                                <i class="fas fa-user-circle fa-lg text-info"></i>
                                <?php else: ?>
                                <i class="fas fa-bell fa-lg text-warning"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <small class="text-muted"><?php echo formatDate($notification['created_at']); ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <?php if (!empty($notification['link'])): ?>
                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($notification['is_read'] == 0): ?>
                        <a href="notifications.php?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-success me-2">
                            <i class="fas fa-check"></i> Mark Read
                        </a>
                        <?php endif; ?>
                        
                        <a href="notifications.php?delete=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this notification?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fas fa-bell-slash fa-4x text-muted"></i>
            </div>
            <h5>No notifications</h5>
            <p class="text-muted">You don't have any notifications at the moment.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.notification-item {
    padding: 15px;
    transition: background-color 0.3s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f7ff;
}

.notification-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 768px) {
    .notification-actions {
        display: flex;
        flex-direction: column;
        gap: 5px;
        margin-top: 10px;
    }
    
    .notification-actions .btn {
        margin-right: 0 !important;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 