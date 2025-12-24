<?php
/**
 * Quick Fix: Update Admin Password
 * This script will update the admin password to "admin123"
 * Run this once: http://localhost/Smart-Digital-Library-Portal-1/fix_admin_password.php
 * Then DELETE this file!
 */

require_once __DIR__ . '/config/database.php';

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$conn = getDBConnection();

// Update admin password
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "<h2 style='color: green;'>✅ Success!</h2>";
    echo "<p>Admin password has been updated to: <strong>admin123</strong></p>";
    echo "<p><strong>Username:</strong> admin</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<hr>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANT: Delete this file (fix_admin_password.php) now for security!</strong></p>";
    echo "<p><a href='login.php'>Go to Login Page</a></p>";
} else {
    echo "<h2 style='color: red;'>❌ Error!</h2>";
    echo "<p>Failed to update password: " . $conn->error . "</p>";
    echo "<p>Make sure the database is set up correctly.</p>";
}

$stmt->close();
closeDBConnection($conn);
?>

