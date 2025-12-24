<?php
/**
 * Reset Password Page
 * Allows users to reset their password using a token
 */

require_once __DIR__ . '/../config/config.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../user/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$valid_token = false;
$user_id = null;

// Validate token
if (!empty($token)) {
    try {
        $conn = getDBConnection();
        
        // Check if reset table exists
        $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Verify token
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $token_data = $result->fetch_assoc();
            $user_id = $token_data['user_id'];
            $valid_token = true;
        } else {
            $error = 'Invalid or expired reset token. Please request a new password reset.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again.';
    }
} else {
    $error = 'No reset token provided.';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'All fields are required!';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        try {
            $conn = getDBConnection();
            
            // Hash new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Delete used token
                $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $success = 'Password reset successfully! You can now login with your new password.';
                $valid_token = false; // Hide form after success
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
            
            $stmt->close();
            closeDBConnection($conn);
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <div style="margin-top: 15px;">
                        <a href="../login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token && !$success): ?>
                <p class="auth-subtitle">Enter your new password below.</p>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">
                            <span class="label-icon">🔒</span>
                            New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" required 
                                   minlength="6" placeholder="Enter new password (min 6 characters)">
                            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password')" title="Show/Hide Password">
                                <span id="password-icon">👁️</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <span class="label-icon">🔒</span>
                            Confirm New Password
                        </label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   minlength="6" placeholder="Confirm new password">
                            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('confirm_password')" title="Show/Hide Password">
                                <span id="confirm_password-icon">👁️</span>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <?php if (!$valid_token && !$success): ?>
                <div class="auth-links">
                    <a href="forgot_password.php" class="btn btn-secondary">Request New Reset Link</a>
                    <a href="../login.php" class="back-to-login">← Back to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const passwordIcon = document.getElementById(fieldId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = '👁️';
            }
        }

        // Password match validation
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (password && confirmPassword) {
                function validatePasswords() {
                    if (confirmPassword.value && password.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                        confirmPassword.style.borderColor = '#dc3545';
                    } else {
                        confirmPassword.setCustomValidity('');
                        confirmPassword.style.borderColor = '';
                    }
                }
                
                password.addEventListener('input', validatePasswords);
                confirmPassword.addEventListener('input', validatePasswords);
            }
        });
    </script>
</body>
</html>

