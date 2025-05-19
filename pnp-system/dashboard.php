<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to access this page', 'danger');
    redirect('login.php');
}

// Set page title
$pageTitle = 'User Dashboard';

// Get user statistics
$user_id = $_SESSION['user_id'];

// User tickets
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_count = $stmt->get_result()->fetch_assoc()['total'];

// Open tickets
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE user_id = ? AND status IN ('new', 'in-progress')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$open_tickets = $stmt->get_result()->fetch_assoc()['total'];

// User payments
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments_count = $stmt->get_result()->fetch_assoc()['total'];

// Total spent
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_spent = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Recent tickets
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent payments
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include dashboard header
include 'includes/dashboard-layout.php';
?>

<h1 class="mb-4">Welcome, <?php echo $_SESSION['full_name']; ?></h1>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100 border-left-primary shadow py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            My Tickets</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $tickets_count; ?></div>
                        <div class="small text-muted mt-2"><?php echo $open_tickets; ?> open tickets</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100 border-left-success shadow py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Payments</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $payments_count; ?></div>
                        <div class="small text-muted mt-2">Processed Payments</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100 border-left-info shadow py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Total Spent</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($total_spent, 2); ?></div>
                        <div class="small text-muted mt-2">All time payments</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100 border-left-warning shadow py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Account Status</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Active</div>
                        <div class="small text-muted mt-2">Member since <?php echo formatDate($_SESSION['created_at'] ?? date('Y-m-d'), 'M d, Y'); ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">My Recent Tickets</h6>
                <a href="tickets.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_tickets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo str_pad($ticket['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <a href="ticket-details.php?id=<?php echo $ticket['id']; ?>">
                                        <?php echo htmlspecialchars($ticket['subject']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($ticket['created_at'], 'M d, Y'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-ticket-alt fa-4x text-muted"></i>
                    </div>
                    <p class="text-muted">You haven't created any tickets yet</p>
                    <a href="create-ticket.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create New Ticket
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
                <a href="payment-tracking.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td>#<?php echo $payment['payment_number']; ?></td>
                                <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-money-bill fa-4x text-muted"></i>
                    </div>
                    <p class="text-muted">No payment records found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Notifications</h6>
                <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_notifications) > 0): ?>
                <div class="list-group notification-list">
                    <?php foreach ($recent_notifications as $notification): ?>
                    <a href="<?php echo $notification['link']; ?>" class="list-group-item list-group-item-action <?php echo ($notification['is_read'] ? '' : 'unread'); ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                            <small class="text-muted"><?php echo formatDate($notification['created_at'], 'M d, h:i A'); ?></small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-bell fa-4x text-muted"></i>
                    </div>
                    <p class="text-muted">No notifications at this time</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include dashboard footer
include 'includes/dashboard-footer.php';
?> 