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

// Create a new admin user if doesn't exist or update the existing one
$password = password_hash('admin123', PASSWORD_DEFAULT); // Hash the password "admin123"

// Check if admin exists
$check = $conn->query("SELECT id FROM users WHERE username = 'admin'");
if ($check->num_rows > 0) {
    // Update existing admin
    $stmt = $conn->prepare("UPDATE users SET password = ?, status = 'active', role = 'admin' WHERE username = 'admin'");
    $stmt->bind_param("s", $password);
    $result = $stmt->execute();
    
    if ($result) {
        echo "Admin password updated successfully to 'admin123'";
    } else {
        echo "Error updating admin: " . $conn->error;
    }
} else {
    // Create new admin user
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, contact_number, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())");
    $email = "admin@example.com";
    $firstName = "System";
    $lastName = "Administrator";
    $contactNumber = "1234567890";
    $address = "Municipal Office";
    
    $stmt->bind_param("sssssss", 
        $username = "admin", 
        $password, 
        $email, 
        $firstName, 
        $lastName, 
        $contactNumber, 
        $address
    );
    
    $result = $stmt->execute();
    
    if ($result) {
        echo "New admin user created successfully with username 'admin' and password 'admin123'";
    } else {
        echo "Error creating admin: " . $conn->error;
    }
}

// Close connection
$conn->close();
?> 