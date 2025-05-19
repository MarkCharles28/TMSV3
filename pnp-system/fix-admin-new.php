<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pnp_system');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Admin Account Fix Utility (New Method)</h2>";

// Use a plain password - we'll hash it fresh
$plain_password = "admin123";

// Generate a NEW hash for "admin123" using current PHP environment
$new_hash = password_hash($plain_password, PASSWORD_DEFAULT);

echo "<p>Generated a new hash for admin123: $new_hash</p>";

// Test verification with the new hash
if (password_verify($plain_password, $new_hash)) {
    echo "<p style='color:green;'>✓ Password verification test passed with new hash!</p>";
} else {
    echo "<p style='color:red;'>✗ Password verification test failed with new hash!</p>";
    echo "<p>There may be a PHP configuration issue on this server.</p>";
    die("Cannot proceed as password verification is not working correctly.");
}

// Check if admin exists
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<p>Admin account exists with ID: " . $admin['id'] . "</p>";
    echo "<p>Current status: " . $admin['status'] . ", role: " . $admin['role'] . "</p>";
    
    // Update admin with the new hash
    $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'active', role = 'admin' WHERE username = 'admin'");
    $stmt->bind_param("s", $new_hash);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Admin password has been reset to 'admin123' with a new hash</p>";
        echo "<p style='color:green;'>✓ Admin status set to 'active'</p>";
        echo "<p style='color:green;'>✓ Admin role set to 'admin'</p>";
    } else {
        echo "<p style='color:red;'>Failed to update admin account: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange;'>Admin account does not exist. Creating it now...</p>";
    
    // Create new admin user with the new hash
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, contact_number, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())");
    $username = "admin";
    $email = "admin@example.com";
    $firstName = "System";
    $lastName = "Administrator";
    $contactNumber = "1234567890";
    $address = "Municipal Office";
    
    $stmt->bind_param("sssssss", $username, $new_hash, $email, $firstName, $lastName, $contactNumber, $address);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Admin account created successfully!</p>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: admin123</p>";
    } else {
        echo "<p style='color:red;'>Failed to create admin account: " . $conn->error . "</p>";
    }
}

echo "<h3>Try Login Now</h3>";
echo "<p>Use these credentials:</p>";
echo "<ul>";
echo "<li>Username: admin</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";

// Close connection
$conn->close();
?> 