<?php
/**
 * Main Configuration File
 * Contains application-wide settings
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Application settings
define('APP_NAME', 'Digital Library Portal');
define('APP_URL', 'http://localhost/Smart-Digital-Library-Portal-1');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Cookie settings for "Remember Me" functionality
define('COOKIE_NAME', 'remember_token');
define('COOKIE_EXPIRE', time() + (30 * 24 * 60 * 60)); // 30 days

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // If called from API, return JSON error instead of redirect
        if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
            jsonResponse([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        } else {
            header('Location: login.php');
            exit();
        }
    }
}

/**
 * Require admin - redirect to user dashboard if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Get the base path
        $base_path = dirname($_SERVER['SCRIPT_NAME']);
        if (strpos($_SERVER['SCRIPT_NAME'], '/api/') !== false) {
            // If called from API, return JSON error instead of redirect
            jsonResponse([
                'success' => false,
                'message' => 'Admin access required'
            ], 403);
        } else {
            header('Location: ../user/dashboard.php');
            exit();
        }
    }
}

/**
 * Sanitize input data
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Return JSON response
 * @param mixed $data Data to return
 * @param int $statusCode HTTP status code
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

