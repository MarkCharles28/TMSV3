<?php
// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pnp_system');

// Create database connection
try {
    // Connect without database first to check if it exists
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    // Check if database exists
    $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    
    if ($result && $result->num_rows == 0) {
        // Database doesn't exist, create it
        if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            throw new Exception("Error creating database: " . $mysqli->error);
        }
        
        // Now connect with the database
        $mysqli->select_db(DB_NAME);
        
        // Import database structure from SQL file
        if (file_exists('database.sql')) {
            $sql = file_get_contents('database.sql');
            if (!$mysqli->multi_query($sql)) {
                throw new Exception("Error importing database structure: " . $mysqli->error);
            }
            
            // Clear results to allow next query
            while ($mysqli->more_results() && $mysqli->next_result()) {
                if ($result = $mysqli->store_result()) {
                    $result->free();
                }
            }
            
            // Success message if database was created
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['flash_message'] = 'Database created successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            throw new Exception("Database SQL file not found.");
        }
    } else {
        // Database exists, just connect to it
        $mysqli->select_db(DB_NAME);
    }
    
    // Final connection for the rest of the script
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Set character set
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Ensure admin user always exists
function ensureAdminExists($conn) {
    // Check if admin exists
    $check = $conn->query("SELECT id FROM users WHERE username = 'admin'");
    
    if ($check && $check->num_rows == 0) {
        // The admin password hash for "admin123"
        $password = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6';
        
        // Create new admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, contact_number, address, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'admin', 'active', NOW())");
        $email = "admin@example.com";
        $firstName = "System";
        $lastName = "Administrator";
        $contactNumber = "1234567890";
        $address = "Municipal Office";
        $username = "admin";
        
        $stmt->bind_param("sssssss", $username, $password, $email, $firstName, $lastName, $contactNumber, $address);
        $stmt->execute();
    } else if ($check) {
        // Ensure admin is set to active and has admin role
        $conn->query("UPDATE users SET status = 'active', role = 'admin' WHERE username = 'admin'");
    }
}

// Call the function to ensure admin exists
if (isset($conn) && $conn->ping()) {
    // Check if users table exists before trying to ensure admin exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableExists && $tableExists->num_rows > 0) {
        ensureAdminExists($conn);
    }
} 