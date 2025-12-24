<?php
/**
 * Main Entry Point
 * Redirects users to appropriate dashboard based on login status and role
 */

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect based on user role
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
} else {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

