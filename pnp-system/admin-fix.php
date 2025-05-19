<?php
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

echo "<h2>Admin Account Fix Utility</h2>";

// Check if admin exists
$result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "<p>Admin account exists with ID: " . $admin['id'] . "</p>";
    echo "<p>Current status: " . $admin['status'] . ", role: " . $admin['role'] . "</p>";
    
    // Reset admin password to a known working value
    // This is the hash for password "admin123"
    $new_password = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6';
    
    $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'active', role = 'admin' WHERE username = 'admin'");
    $stmt->bind_param("s", $new_password);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Admin password has been reset to 'admin123'</p>";
        echo "<p style='color:green;'>✓ Admin status set to 'active'</p>";
        echo "<p style='color:green;'>✓ Admin role set to 'admin'</p>";
    } else {
        echo "<p style='color:red;'>Failed to update admin account: " . $conn->error . "</p>";
    }
    
} else {
    echo "<p style='color:red;'>Admin account does not exist. Creating it now...</p>";
    
    // The admin password hash for "admin123"
    $password = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6';
    
    // Create new admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, contact_number, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())");
    $username = "admin";
    $email = "admin@example.com";
    $firstName = "System";
    $lastName = "Administrator";
    $contactNumber = "1234567890";
    $address = "Municipal Office";
    
    $stmt->bind_param("sssssss", $username, $password, $email, $firstName, $lastName, $contactNumber, $address);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✓ Admin account created successfully!</p>";
        echo "<p>Username: admin</p>";
        echo "<p>Password: admin123</p>";
    } else {
        echo "<p style='color:red;'>Failed to create admin account: " . $conn->error . "</p>";
    }
}

// Test the password verification to ensure it works
echo "<h3>Password Verification Test</h3>";
$test_password = "admin123";
$test_hash = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6';

if (password_verify($test_password, $test_hash)) {
    echo "<p style='color:green;'>✓ Password verification test passed!</p>";
} else {
    echo "<p style='color:red;'>Password verification test failed!</p>";
}

// Show admin login form
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