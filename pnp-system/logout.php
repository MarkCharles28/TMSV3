<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Log the logout activity
    logActivity('logout', 'User logged out');
    
    // Unset all session variables
    $_SESSION = array();
    
    // If it's desired to kill the session cookie, delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Set flash message
setFlashMessage('You have been logged out successfully.', 'success');

// Redirect to login page
redirect('login.php');
?> 