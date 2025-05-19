<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access this page', 'danger');
    redirect('login.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user exists
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        setFlashMessage('User not found', 'danger');
        redirect('user-management.php');
    }
} else {
    setFlashMessage('Invalid user ID', 'danger');
    redirect('user-management.php');
}

// Get user's tickets
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's payments
$stmt = $conn->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's activity logs
$stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activity_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>User Details</h1>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="user-management.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to User Management
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="profile-avatar bg-primary text-white mx-auto">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                    </div>
                    <h4 class="mt-3"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p>
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        <span class="badge bg-<?php 
                            echo $user['status'] === 'active' ? 'success' : 
                                ($user['status'] === 'pending' ? 'warning text-dark' : 'danger'); 
                            ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </p>
                </div>
                
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Username:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($user['username']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Email:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Contact Number:</span>
                        <span class="text-muted"><?php echo htmlspecialchars($user['contact_number']); ?></span>
                    </li>
                    <li class="list-group-item">
                        <div><strong>Address:</strong></div>
                        <div class="text-muted"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Registered On:</span>
                        <span class="text-muted"><?php echo formatDate($user['created_at']); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Last Login:</span>
                        <span class="text-muted"><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></span>
                    </li>
                </ul>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#editUserModal">
                        <i class="fas fa-edit me-2"></i>Edit User
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- User Activity -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php if (count($activity_logs) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo formatDate($log['created_at'], 'M d, Y h:i A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center">No activity records found for this user.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Tickets -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">Tickets</h5>
            </div>
            <div class="card-body">
                <?php if (count($tickets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo str_pad($ticket['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['department']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $ticket['status']; ?>">
                                        <?php echo ucfirst($ticket['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($ticket['created_at'], 'M d, Y'); ?></td>
                                <td>
                                    <a href="ticket-details.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center">No tickets found for this user.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Payments -->
        <div class="card shadow">
            <div class="card-header py-3">
                <h5 class="card-title mb-0">Payments</h5>
            </div>
            <div class="card-body">
                <?php if (count($payments) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $payment['status'] === 'completed' ? 'success' : 
                                            ($payment['status'] === 'pending' ? 'warning text-dark' : 
                                            ($payment['status'] === 'failed' ? 'danger' : 'secondary')); 
                                        ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($payment['payment_date'], 'M d, Y'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-center">No payment records found for this user.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="user-management.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role">
                            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: bold;
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 