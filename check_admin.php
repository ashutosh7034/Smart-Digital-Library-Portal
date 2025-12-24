<?php
/**
 * Admin Account Diagnostic & Fix Script
 * This script will check and fix admin login issues
 * Run: http://localhost/Smart-Digital-Library-Portal-1/check_admin.php
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Admin Check</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{color:green;padding:10px;background:#d4edda;border:1px solid #c3e6cb;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;padding:10px;background:#f8d7da;border:1px solid #f5c6cb;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;padding:10px;background:#d1ecf1;border:1px solid #bee5eb;border-radius:5px;margin:10px 0;}";
echo "button{background:#667eea;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin:5px;}";
echo "button:hover{background:#5568d3;}";
echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
echo "th,td{padding:10px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#667eea;color:white;}</style></head><body>";

echo "<h1>🔍 Admin Account Diagnostic Tool</h1>";

try {
    $conn = getDBConnection();
    
    // Check if admin user exists
    echo "<h2>Step 1: Checking Admin User</h2>";
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<div class='error'>❌ Admin user NOT found in database!</div>";
        echo "<p>Creating admin user...</p>";
        
        // Create admin user
        $password = 'admin123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $email = 'admin@library.com';
        $role = 'admin';
        
        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        $username = 'admin';
        
        if ($insert_stmt->execute()) {
            echo "<div class='success'>✅ Admin user created successfully!</div>";
            echo "<p><strong>Username:</strong> admin</p>";
            echo "<p><strong>Password:</strong> admin123</p>";
        } else {
            echo "<div class='error'>❌ Failed to create admin user: " . $conn->error . "</div>";
        }
        $insert_stmt->close();
    } else {
        $admin = $result->fetch_assoc();
        echo "<div class='success'>✅ Admin user found!</div>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>" . htmlspecialchars($admin['id']) . "</td></tr>";
        echo "<tr><td>Username</td><td>" . htmlspecialchars($admin['username']) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($admin['email']) . "</td></tr>";
        echo "<tr><td>Role</td><td>" . htmlspecialchars($admin['role']) . "</td></tr>";
        echo "<tr><td>Password Hash</td><td>" . substr(htmlspecialchars($admin['password']), 0, 30) . "...</td></tr>";
        echo "</table>";
        
        // Test password verification
        echo "<h2>Step 2: Testing Password Verification</h2>";
        $test_password = 'admin123';
        if (password_verify($test_password, $admin['password'])) {
            echo "<div class='success'>✅ Password verification works! Password 'admin123' is correct.</div>";
        } else {
            echo "<div class='error'>❌ Password verification FAILED! Password 'admin123' does NOT match.</div>";
            echo "<p>Updating password...</p>";
            
            // Update password
            $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
            $update_stmt->bind_param("s", $new_hash);
            
            if ($update_stmt->execute()) {
                echo "<div class='success'>✅ Password updated successfully!</div>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
            } else {
                echo "<div class='error'>❌ Failed to update password: " . $conn->error . "</div>";
            }
            $update_stmt->close();
        }
    }
    
    $stmt->close();
    
    // Final verification
    echo "<h2>Step 3: Final Verification</h2>";
    $final_stmt = $conn->prepare("SELECT username, password FROM users WHERE username = 'admin'");
    $final_stmt->execute();
    $final_result = $final_stmt->get_result();
    $final_admin = $final_result->fetch_assoc();
    
    if (password_verify('admin123', $final_admin['password'])) {
        echo "<div class='success'>";
        echo "<h3>✅ Everything is working correctly!</h3>";
        echo "<p><strong>Login Credentials:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> admin</li>";
        echo "<li><strong>Password:</strong> admin123</li>";
        echo "</ul>";
        echo "<p>You can now login at: <a href='login.php'>Login Page</a></p>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Something went wrong. Please check database connection.</div>";
    }
    
    $final_stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Check your database configuration in config/database.php</p>";
}

echo "<hr>";
echo "<div class='info'>";
echo "<h3>📝 Next Steps:</h3>";
echo "<ol>";
echo "<li>Try logging in with username: <strong>admin</strong> and password: <strong>admin123</strong></li>";
echo "<li>If login still fails, check browser console for errors</li>";
echo "<li>Make sure sessions are working (check PHP session configuration)</li>";
echo "<li>Delete this file (check_admin.php) after fixing the issue</li>";
echo "</ol>";
echo "</div>";

echo "<p><button onclick='window.location.href=\"login.php\"'>Go to Login Page</button></p>";

echo "</body></html>";
?>

