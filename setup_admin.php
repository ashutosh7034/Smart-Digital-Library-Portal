<?php
/**
 * Admin Password Setup Script
 * Run this once to set up the admin password properly
 * 
 * Usage: Open in browser: http://localhost/Smart-Digital-Library-Portal-1/setup_admin.php
 * Then DELETE this file for security!
 */

require_once __DIR__ . '/config/database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    if (empty($password) || strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long!';
    } else {
        $conn = getDBConnection();
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update admin password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $stmt->bind_param("s", $hashed_password);
        
        if ($stmt->execute()) {
            $message = 'Admin password updated successfully! Please DELETE this file now for security.';
            $success = true;
        } else {
            $message = 'Error updating password: ' . $conn->error;
        }
        
        $stmt->close();
        closeDBConnection($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #5568d3;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Setup Admin Password</h2>
        
        <div class="warning">
            <strong>⚠️ Security Warning:</strong> Delete this file after setting up the admin password!
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">New Admin Password</label>
                    <input type="password" id="password" name="password" required minlength="6" 
                           placeholder="Enter new password (min 6 characters)">
                </div>
                <button type="submit">Update Admin Password</button>
            </form>
        <?php endif; ?>
        
        <p style="margin-top: 20px; font-size: 12px; color: #666;">
            Default admin username: <strong>admin</strong><br>
            After setting password, delete this file for security.
        </p>
    </div>
</body>
</html>

