<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    setFlashMessage('You do not have permission to access this page', 'danger');
    redirect('login.php');
}

// Handle user status updates
if (isset($_POST['update_user'])) {
    $user_id = (int)$_POST['user_id'];
    $status = sanitize($_POST['status']);
    $role = sanitize($_POST['role']);
    
    if (in_array($status, ['active', 'pending', 'suspended']) && in_array($role, ['admin', 'user'])) {
        $stmt = $conn->prepare("UPDATE users SET status = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, $role, $user_id);
        
        if ($stmt->execute()) {
            // Log activity
            logActivity('update_user', "Updated user ID $user_id status to $status and role to $role");
            
            // Create notification for user
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, created_at) VALUES (?, ?, ?, NOW())");
            $title = 'Account Status Updated';
            $message = "Your account status has been updated to: $status";
            $stmt->bind_param("iss", $user_id, $title, $message);
            $stmt->execute();
            
            setFlashMessage('User updated successfully', 'success');
        } else {
            setFlashMessage('Error updating user', 'danger');
        }
    } else {
        setFlashMessage('Invalid status or role', 'danger');
    }
    
    // Redirect to avoid form resubmission
    redirect('user-management.php');
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deletion of own account
    if ($user_id == $_SESSION['user_id']) {
        setFlashMessage('You cannot delete your own account', 'danger');
        redirect('user-management.php');
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Log activity
        logActivity('delete_user', "Deleted user ID $user_id");
        
        setFlashMessage('User deleted successfully', 'success');
    } else {
        setFlashMessage('Error deleting user', 'danger');
    }
    
    // Redirect to avoid accidental refreshes that would delete again
    redirect('user-management.php');
}

// Set up filtering and pagination
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 10 users per page
$offset = ($page - 1) * $limit;

// Build query based on filters
$base_query = "FROM users WHERE 1=1";
$count_query = "SELECT COUNT(*) as total $base_query";
$user_query = "SELECT * $base_query";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $base_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $base_query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

// Update queries with the WHERE clause
$count_query = "SELECT COUNT(*) as total $base_query";
$user_query = "SELECT * $base_query ORDER BY created_at DESC LIMIT $offset, $limit";

// Execute count query for pagination
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_records = $stmt->get_result()->fetch_assoc()['total'];

$total_pages = ceil($total_records / $limit);

// Execute user query
$stmt = $conn->prepare($user_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Count pending users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'pending'");
$stmt->execute();
$pending_count = $stmt->get_result()->fetch_assoc()['count'];

// Include header
include 'includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>User Management</h1>
    <?php if ($pending_count > 0): ?>
    <span class="badge bg-warning text-dark">
        <?php echo $pending_count; ?> pending approval<?php echo $pending_count > 1 ? 's' : ''; ?>
    </span>
    <?php endif; ?>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h5 class="mb-0">Users</h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="user-management.php" method="get" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="suspended" <?php echo $status_filter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Username, email, name..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="user-management.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
                </div>
            </div>
        </form>
        
        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['status'] === 'active' ? 'success' : 
                                        ($user['status'] === 'pending' ? 'warning text-dark' : 'danger'); 
                                    ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($user['created_at'], 'M d, Y'); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editUser<?php echo $user['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="user-management.php?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                                <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        
                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUser<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserLabel<?php echo $user['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editUserLabel<?php echo $user['id']; ?>">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="user-management.php" method="post">
                                        <div class="modal-body">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            
                                            <div class="mb-3">
                                                <label for="username<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="email<?php echo $user['id']; ?>" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email<?php echo $user['id']; ?>" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="status<?php echo $user['id']; ?>" class="form-label">Status</label>
                                                <select class="form-select" id="status<?php echo $user['id']; ?>" name="status">
                                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                <select class="form-select" id="role<?php echo $user['id']; ?>" name="role">
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
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-users fa-3x text-muted"></i>
                                </div>
                                <h5>No users found</h5>
                                <p class="text-muted">Try adjusting your search or filter criteria</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mt-4">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $page <= 1 ? '#' : '?page=' . ($page - 1) . (!empty($status_filter) ? '&status=' . $status_filter : '') . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo $page >= $total_pages ? '#' : '?page=' . ($page + 1) . (!empty($status_filter) ? '&status=' . $status_filter : '') . (!empty($search) ? '&search=' . urlencode($search) : ''); ?>">Next</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?> 