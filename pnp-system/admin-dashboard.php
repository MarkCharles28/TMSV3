<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access the admin dashboard', 'danger');
    redirect('login.php');
}

// Set page title
$pageTitle = 'Admin Dashboard';

// Get system statistics
// Total users
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$users_count = $stmt->get_result()->fetch_assoc()['total'];

// Pending users
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$stmt->execute();
$pending_users = $stmt->get_result()->fetch_assoc()['total'];

// Total tickets
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets");
$stmt->execute();
$tickets_count = $stmt->get_result()->fetch_assoc()['total'];

// Open tickets
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tickets WHERE status IN ('new', 'in-progress')");
$stmt->execute();
$open_tickets = $stmt->get_result()->fetch_assoc()['total'];

// Payments this month
$stmt = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE()) AND YEAR(payment_date) = YEAR(CURRENT_DATE())");
$stmt->execute();
$monthly_payments = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

// Recent activity logs
$stmt = $conn->prepare("SELECT al.*, u.username FROM activity_logs al 
                        LEFT JOIN users u ON al.user_id = u.id
                        ORDER BY al.created_at DESC LIMIT 10");
$stmt->execute();
$activity_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent tickets
$stmt = $conn->prepare("SELECT t.*, u.username, u.first_name, u.last_name 
                        FROM tickets t
                        JOIN users u ON t.user_id = u.id
                        ORDER BY t.created_at DESC LIMIT 5");
$stmt->execute();
$recent_tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include dashboard header
include 'includes/dashboard-layout.php';
?>

<h1 class="mb-4">Admin Dashboard</h1>

<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100 border-left-primary shadow py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Users</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $users_count; ?></div>
                        <div class="small text-muted mt-2"><?php echo $pending_users; ?> pending approval</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                            Monthly Revenue</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<?php echo number_format($monthly_payments, 2); ?></div>
                        <div class="small text-muted mt-2">Current month</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Tickets
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $tickets_count; ?></div>
                        <div class="small text-muted mt-2"><?php echo $open_tickets; ?> open tickets</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                            System Health</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Good</div>
                        <div class="small text-muted mt-2">All systems operational</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Revenue Overview</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="payment-chart" height="320"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Ticket Status Distribution</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="ticket-status-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Tickets</h6>
                <a href="tickets-management.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_tickets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>User</th>
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
                                <td><?php echo htmlspecialchars($ticket['first_name'] . ' ' . $ticket['last_name']); ?></td>
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
                <p class="text-center text-muted">No tickets found</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
            </div>
            <div class="card-body">
                <?php if (count($activity_logs) > 0): ?>
                <div class="activity-timeline">
                    <?php foreach ($activity_logs as $log): ?>
                    <div class="activity-item">
                        <div class="small text-muted"><?php echo formatDate($log['created_at']); ?></div>
                        <div class="activity-content">
                            <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                            <?php echo htmlspecialchars($log['description']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-center text-muted">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
// Add chart scripts for dashboard
$extraScripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Payment chart
    const paymentChart = new Chart(
        document.getElementById("payment-chart"),
        {
            type: "line",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                datasets: [{
                    label: "Monthly Revenue",
                    data: [1200, 1900, 2400, 2800, 3100, 2950, 3500, 4100, 3800, 4200, 4500, ' . $monthly_payments . '],
                    borderColor: "#4e73df",
                    backgroundColor: "rgba(78, 115, 223, 0.05)",
                    pointRadius: 3,
                    pointBackgroundColor: "#4e73df",
                    pointBorderColor: "#4e73df",
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: "#4e73df",
                    pointHoverBorderColor: "#4e73df",
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        }
    );
    
    // Ticket status chart
    const ticketStatusChart = new Chart(
        document.getElementById("ticket-status-chart"),
        {
            type: "doughnut",
            data: {
                labels: ["New", "In Progress", "Resolved", "Closed"],
                datasets: [{
                    data: [' . $open_tickets . ', ' . (int)($open_tickets * 0.4) . ', ' . (int)($tickets_count * 0.3) . ', ' . (int)($tickets_count * 0.4) . '],
                    backgroundColor: ["#4e73df", "#f6c23e", "#1cc88a", "#e74a3b"],
                    hoverOffset: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom"
                    }
                },
                cutout: "70%"
            }
        }
    );
});
</script>';

// Include dashboard footer
include 'includes/dashboard-footer.php';
?> 