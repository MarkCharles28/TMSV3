<?php
// Application configuration settings
define('SITE_NAME', 'Municipal Ticket Monitoring System');
define('SITE_URL', '/pnp-system/');
define('ADMIN_EMAIL', 'admin@example.com');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/pnp-system/uploads/');
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Session settings
session_start();

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone setting
date_default_timezone_set('Asia/Manila'); 