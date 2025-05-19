<?php
require_once 'config.php';
require_once 'db.php';

/**
 * Clean and sanitize input data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

/**
 * Check if user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is an admin
 * @return bool True if admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Redirect to a specific page
 * @param string $page Page to redirect to
 * @return void
 */
function redirect($page) {
    header("Location: " . SITE_URL . $page);
    exit;
}

/**
 * Display flash message to user
 * @param string $message Message to display
 * @param string $type Type of message (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Display flash message if exists and clear it
 * @return string HTML for flash message
 */
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                    ' . $message . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

/**
 * Generate a random string
 * @param int $length Length of random string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Format date to readable format
 * @param string $date Date to format
 * @param string $format Format to use
 * @return string Formatted date
 */
function formatDate($date, $format = 'F d, Y h:i A') {
    return date($format, strtotime($date));
}

/**
 * Get user details by ID
 * @param int $user_id User ID
 * @return array|bool User data or false if not found
 */
function getUserById($user_id) {
    global $conn;
    $user_id = (int)$user_id;
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

/**
 * Get ticket details by ID
 * @param int $ticket_id Ticket ID
 * @return array|bool Ticket data or false if not found
 */
function getTicketById($ticket_id) {
    global $conn;
    $ticket_id = (int)$ticket_id;
    
    $stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return false;
}

/**
 * Log activity in the system
 * @param string $action Action performed
 * @param string $description Description of action
 * @param int|null $user_id User ID, defaults to current user
 * @return void
 */
function logActivity($action, $description, $user_id = null) {
    global $conn;
    
    // Check if connection is still alive, if not reconnect
    if (!$conn->ping()) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            // If reconnection fails, log to error file instead
            error_log("Database connection failed during activity logging: " . $conn->connect_error);
            return;
        }
        // Reset charset after reconnection
        $conn->set_charset("utf8mb4");
    }
    
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else if ($user_id === null) {
        $user_id = 0; // System or guest
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("isss", $user_id, $action, $description, $ip);
        $stmt->execute();
    } catch (Exception $e) {
        // Log error but continue execution
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Upload a file to the system
 * @param array $file $_FILES array element
 * @param string $destination Subfolder in uploads directory
 * @return string|bool File path or false on failure
 */
function uploadFile($file, $destination = '') {
    // Check if file was uploaded without errors
    if ($file['error'] === UPLOAD_ERR_OK) {
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        
        // Get file extension and check if it's allowed
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExt, ALLOWED_FILE_TYPES)) {
            return false;
        }
        
        // Check file size
        if ($fileSize > MAX_FILE_SIZE) {
            return false;
        }
        
        // Create unique file name
        $newFileName = time() . '_' . generateRandomString(6) . '.' . $fileExt;
        $uploadPath = UPLOAD_PATH . $destination;
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($fileTmp, $uploadPath . $newFileName)) {
            return $destination . $newFileName;
        }
    }
    
    return false;
} 