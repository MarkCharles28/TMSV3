<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to access payment tracking', 'danger');
    redirect('login.php');
}

// Get user ID
$user_id = $_SESSION['user_id'];
$is_admin = isAdmin();

// Set default filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Define payment statuses
$payment_statuses = [
    'all' => 'All Statuses',
    'completed' => 'Completed',
    'pending' => 'Pending',
    'failed' => 'Failed',
    'refunded' => 'Refunded'
];

// Prepare base query
if ($is_admin) {
    $base_query = "SELECT p.*, u.username, u.first_name, u.last_name 
                  FROM payments p
                  JOIN users u ON p.user_id = u.id
                  WHERE 1=1";
} else {
    $base_query = "SELECT p.*, u.username, u.first_name, u.last_name 
                  FROM payments p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = ?";
}

// Add filters to query
$params = [];
$types = '';

if (!$is_admin) {
    $params[] = $user_id;
    $types .= 'i';
}

if (!empty($status_filter) && $status_filter !== 'all') {
    $base_query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($date_from)) {
    $base_query .= " AND DATE(p.payment_date) >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if (!empty($date_to)) {
    $base_query .= " AND DATE(p.payment_date) <= ?";
    $params[] = $date_to;
    $types .= 's';
}

if (!empty($search)) {
    $base_query .= " AND (p.payment_number LIKE ? OR p.description LIKE ? OR p.payment_method LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Add ordering
$base_query .= " ORDER BY p.payment_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($base_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_amount = 0;
$completed_amount = 0;
$pending_amount = 0;

foreach ($payments as $payment) {
    $total_amount += $payment['amount'];
    if ($payment['status'] === 'completed') {
        $completed_amount += $payment['amount'];
    } elseif ($payment['status'] === 'pending') {
        $pending_amount += $payment['amount'];
    }
}

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Payment Tracking</h1>
    <?php if ($is_admin): ?>
    <a href="create-payment.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create New Payment
    </a>
    <?php endif; ?>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Amount</h6>
                        <h2 class="mb-0">₱<?php echo number_format($total_amount, 2); ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Completed Payments</h6>
                        <h2 class="mb-0">₱<?php echo number_format($completed_amount, 2); ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Pending Payments</h6>
                        <h2 class="mb-0">₱<?php echo number_format($pending_amount, 2); ?></h2>
                    </div>
                    <div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h5 class="mb-0">Payment History</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="payment-tracking.php" method="get" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <?php foreach ($payment_statuses as $key => $value): ?>
                        <option value="<?php echo $key; ?>" <?php echo $status_filter === $key ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Payment #, Description..." value="<?php echo $search; ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Payments Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Payment #</th>
                        <?php if ($is_admin): ?>
                        <th>User</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                            <?php if ($is_admin): ?>
                            <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                            <?php endif; ?>
                            <td><?php echo formatDate($payment['payment_date'], 'M d, Y'); ?></td>
                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $payment['status'] === 'completed' ? 'success' : 
                                        ($payment['status'] === 'pending' ? 'warning text-dark' : 
                                        ($payment['status'] === 'failed' ? 'danger' : 'secondary')); 
                                    ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="payment-details.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($is_admin && $payment['status'] === 'pending'): ?>
                                <a href="update-payment.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($payment['status'] === 'pending' && !empty($payment['payment_link'])): ?>
                                <a href="<?php echo htmlspecialchars($payment['payment_link']); ?>" class="btn btn-sm btn-success" target="_blank">
                                    <i class="fas fa-credit-card"></i> Pay
                                </a>
                                <?php endif; ?>
                                <?php if ($payment['receipt_url']): ?>
                                <a href="<?php echo SITE_URL . 'uploads/' . htmlspecialchars($payment['receipt_url']); ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-file-invoice"></i> Receipt
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo $is_admin ? '8' : '7'; ?>" class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-search fa-3x text-muted"></i>
                                </div>
                                <h5>No payments found</h5>
                                <p class="text-muted">Try adjusting your search or filter criteria</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($is_admin): ?>
<div class="card shadow">
    <div class="card-header py-3">
        <h5 class="mb-0">Payment Analytics</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Monthly Payment Trends</h6>
                <div class="chart-container">
                    <canvas id="payment-chart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Payment Methods Distribution</h6>
                <div class="chart-container">
                    <canvas id="payment-methods-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
include 'includes/footer.php';
?> 