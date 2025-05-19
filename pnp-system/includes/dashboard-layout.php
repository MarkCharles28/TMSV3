<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    setFlashMessage('You must be logged in to access this page', 'danger');
    redirect('login.php');
}

// Determine if admin for styling
$isAdmin = isAdmin();
$navClass = $isAdmin ? 'admin-nav' : 'user-nav';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNP System - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/dashboard-nav.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/pnp-theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap JS (loaded in the header to ensure it's available immediately) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Side Navigation -->
    <nav id="dashboard-nav" class="dashboard-nav <?php echo $navClass; ?>">
        <div class="logo-container">
            <div class="logo">
                <img src="<?php echo SITE_URL; ?>assets/images/pnp-logo.png" alt="PNP Logo">
                <span>PNP System</span>
            </div>
            <button id="toggle-nav" class="toggle-nav">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <ul class="nav-items">
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>admin-dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>user-management.php" class="nav-link">
                        <i class="fas fa-users-cog"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <div class="nav-divider"></div>
            
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>create-ticket.php" class="nav-link">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Create Ticket</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>payment-tracking.php" class="nav-link">
                    <i class="fas fa-money-bill"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>notifications.php" class="nav-link">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                    <?php
                    // Count unread notifications
                    $user_id = $_SESSION['user_id'];
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $notificationCount = $row['count'];
                    
                    if ($notificationCount > 0) {
                        echo '<span class="badge bg-danger rounded-pill ms-2">' . $notificationCount . '</span>';
                    }
                    ?>
                </a>
            </li>
            
            <div class="nav-divider"></div>
            
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>profile.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo SITE_URL; ?>logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
        
        <div class="user-section">
            <div class="user-profile">
                <img src="<?php echo SITE_URL; ?>assets/images/default-avatar.png" alt="User Avatar" class="user-avatar">
                <div class="user-info">
                    <div class="user-name"><?php echo $_SESSION['full_name']; ?></div>
                    <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Top Navigation -->
    <div id="top-nav" class="top-nav">
        <div class="d-flex align-items-center">
            <button id="mobile-toggle" class="d-md-none btn btn-sm btn-outline-secondary me-2">
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-none d-md-block">
                <h5 class="mb-0"><?php echo $pageTitle ?? 'Dashboard'; ?></h5>
            </div>
        </div>
        
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                    <div class="dropdown-header">Notifications</div>
                    <?php
                    // Get recent notifications
                    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (count($notifications) > 0) {
                        foreach ($notifications as $notification) {
                            $readClass = $notification['is_read'] ? '' : 'fw-bold';
                            echo '<a class="dropdown-item ' . $readClass . '" href="' . $notification['link'] . '">';
                            echo htmlspecialchars($notification['title']);
                            echo '<div class="small text-muted">' . formatDate($notification['created_at'], 'M d, h:i A') . '</div>';
                            echo '</a>';
                        }
                        echo '<div class="dropdown-divider"></div>';
                        echo '<a class="dropdown-item text-center small text-primary" href="notifications.php">Show all notifications</a>';
                    } else {
                        echo '<div class="dropdown-item text-center small text-muted">No notifications</div>';
                    }
                    ?>
                </div>
            </div>
            <div class="dropdown ms-2">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="profile.php">Profile</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div id="main-content" class="main-content dashboard-content">
        <?php echo displayFlashMessage(); ?>
        
        <!-- Content goes here -->
        <div class="content-wrapper">
            <?php // Page content will be injected here ?>
        </div>
    </div>
    
    <!-- Footer Scripts -->
    <script src="<?php echo SITE_URL; ?>assets/js/dashboard-nav.js"></script>
</body>
</html> 