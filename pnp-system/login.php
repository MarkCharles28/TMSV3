<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// Verify admin exists in database (provides additional recovery method)
if (isset($_GET['action']) && $_GET['action'] == 'fix_admin' && !isLoggedIn()) {
    $admin_check = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    $admin_exists = $admin_check->fetch_assoc()['count'] > 0;
    
    if (!$admin_exists) {
        // Create admin with default password
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $username = "admin";
        $email = "admin@example.com";
        $firstName = "System";
        $lastName = "Administrator";
        $contactNumber = "1234567890";
        $address = "Municipal Office";
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, contact_number, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())");
        $stmt->bind_param("sssssss", $username, $password, $email, $firstName, $lastName, $contactNumber, $address);
        $stmt->execute();
        
        setFlashMessage('Admin account has been restored. You can now login with username: admin, password: admin123', 'success');
    }
}

$error = '';
$username = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Query database for user
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Debug output for admin
            if ($username === 'admin' && isset($_GET['debug'])) {
                echo "<div style='background: #eee; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
                echo "<h3>Debug Information (Only visible with ?debug=1)</h3>";
                echo "<p>User found in database: Yes</p>";
                echo "<p>Status: " . $user['status'] . "</p>";
                echo "<p>Role: " . $user['role'] . "</p>";
                echo "<p>Password verification: " . (password_verify($password, $user['password']) ? "Passed" : "Failed") . "</p>";
                echo "</div>";
            }
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if account is active
                if ($user['status'] === 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    
                    // Update last login time
                    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->bind_param("i", $user['id']);
                    $stmt->execute();
                    
                    // Log activity
                    logActivity('login', 'User logged in', $user['id']);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirect('admin-dashboard.php');
                    } else {
                        redirect('dashboard.php');
                    }
                } else {
                    $error = 'Your account is not active. Please contact the administrator.';
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="row justify-content-center mt-4">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center py-3">
                <h4 class="mb-0">Sign In to Your Account</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form action="login.php" method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label required-field">Username or Email</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="text" class="form-control" id="username" name="username" 
                                value="<?php echo htmlspecialchars($username); ?>" 
                                placeholder="Enter your username or email" required autofocus>
                            <div class="invalid-feedback">Please enter your username or email.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label required-field">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">Please enter your password.</div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none small">Forgot password?</a>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="login.php?action=fix_admin" class="text-muted small">Administrator Access Recovery</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    togglePassword.addEventListener('click', function() {
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?> 