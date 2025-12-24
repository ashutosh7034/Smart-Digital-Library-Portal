<?php
/**
 * Forgot Password Page
 * Allows users to request password reset
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email address is required!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address!';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour from now
                
                // Check if reset table exists, if not create it
                $conn->query("CREATE TABLE IF NOT EXISTS password_reset_tokens (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(64) UNIQUE NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                // Delete any existing tokens for this user
                $delete_stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
                $delete_stmt->bind_param("i", $user['id']);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                // Insert new token
                $insert_stmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("iss", $user['id'], $token, $expires_at);
                
                if ($insert_stmt->execute()) {
                    // In a real application, you would send an email here
                    // For now, we'll show the reset link (for development/testing)
                    $reset_link = APP_URL . '/auth/reset_password.php?token=' . $token;
                    
                    $success = 'Password reset link has been generated!';
                    
                    // For development: Show the link (remove in production)
                    $dev_message = '<div class="dev-reset-link">
                        <strong>Development Mode:</strong><br>
                        Reset Link: <a href="' . $reset_link . '" target="_blank">' . $reset_link . '</a><br>
                        <small>(In production, this would be sent via email)</small>
                    </div>';
                } else {
                    $error = 'Failed to generate reset token. Please try again.';
                }
                
                $insert_stmt->close();
            } else {
                // Don't reveal if email exists or not (security best practice)
                $success = 'If that email exists in our system, a password reset link has been sent.';
            }
            
            $stmt->close();
            closeDBConnection($conn);
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            // Uncomment for debugging: $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h2>Forgot Password</h2>
            <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <?php if (isset($dev_message)) echo $dev_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <span class="label-icon">📧</span>
                            Email Address
                        </label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               placeholder="Enter your registered email">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <a href="../login.php" class="back-to-login">← Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

