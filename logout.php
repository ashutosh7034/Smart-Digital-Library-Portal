<?php
/**
 * Logout Handler
 * Destroys session and clears cookies
 */

require_once __DIR__ . '/config/config.php';

// Destroy session
session_unset();
session_destroy();

// Clear remember me cookie
if (isset($_COOKIE[COOKIE_NAME])) {
    setcookie(COOKIE_NAME, '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php');
exit();

