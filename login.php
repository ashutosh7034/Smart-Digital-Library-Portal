<?php
/**
 * Login Page
 * Handles user and admin login with session and cookie support
 */

require_once __DIR__ . '/config/config.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required!';
    } else {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    
                    // Handle "Remember Me" cookie
                    if ($remember) {
                        // Generate a secure token
                        $token = bin2hex(random_bytes(32));
                        
                        // Store token in database (you might want to create a remember_tokens table)
                        // For simplicity, we'll store it in a cookie
                        setcookie(COOKIE_NAME, $token, COOKIE_EXPIRE, '/');
                    }
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } else {
                        header('Location: user/dashboard.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid username or password!';
                }
            } else {
                $error = 'Invalid username or password!';
            }
            
            $stmt->close();
            closeDBConnection($conn);
        } catch (Exception $e) {
            $error = 'Database error. Please check your connection.';
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
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h2>Login to Library Portal</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility()" title="Show/Hide Password">
                            <span id="password-icon">👁️</span>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        Remember me for 30 days
                    </label>
                    <a href="auth/forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login" id="login-btn">
                    <span class="btn-text">Login</span>
                    <span class="btn-spinner" id="btn-spinner" style="display: none;">⏳</span>
                </button>
            </form>
            
            <p class="auth-link">
                Don't have an account? <a href="auth/register.php">Register here</a>
            </p>
            
            <div class="demo-credentials">
                <div class="demo-header" onclick="toggleDemoCredentials()">
                    <span class="demo-icon">💡</span>
                    <strong>Demo Credentials</strong>
                    <span class="demo-arrow" id="demo-arrow">▼</span>
                </div>
                <div class="demo-content" id="demo-content" style="display: none;">
                    <div class="demo-item">
                        <div class="demo-info">
                            <span class="demo-badge admin-badge">Admin</span>
                            <div>
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> admin123
                            </div>
                        </div>
                        <button class="btn btn-sm btn-success" onclick="fillDemoCredentials('admin', 'admin123')">Fill & Login</button>
                    </div>
                    <div class="demo-item">
                        <div class="demo-info">
                            <span class="demo-badge user-badge">User</span>
                            <div>
                                <strong>Note:</strong> Register a new account to get started
                            </div>
                        </div>
                        <a href="auth/register.php" class="btn btn-sm btn-primary">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password visibility toggle
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = '👁️';
            }
        }

        // Toggle demo credentials
        function toggleDemoCredentials() {
            const content = document.getElementById('demo-content');
            const arrow = document.getElementById('demo-arrow');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                arrow.textContent = '▲';
            } else {
                content.style.display = 'none';
                arrow.textContent = '▼';
            }
        }

        // Fill demo credentials
        function fillDemoCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            
            // Visual feedback
            const inputs = document.querySelectorAll('#username, #password');
            inputs.forEach(input => {
                input.style.background = '#e8f5e9';
                input.style.borderColor = '#28a745';
                setTimeout(() => {
                    input.style.background = '';
                    input.style.borderColor = '';
                }, 1500);
            });
            
            // Show success message
            showMessage('Credentials filled! Click Login to continue.', 'success');
        }


        // Show temporary message
        function showMessage(text, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `alert alert-${type} temp-message`;
            messageDiv.textContent = text;
            messageDiv.style.position = 'fixed';
            messageDiv.style.top = '20px';
            messageDiv.style.left = '50%';
            messageDiv.style.transform = 'translateX(-50%)';
            messageDiv.style.zIndex = '10000';
            messageDiv.style.minWidth = '300px';
            messageDiv.style.textAlign = 'center';
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transition = 'opacity 0.3s';
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }

        // Form submission with loading state
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('form');
            const loginBtn = document.getElementById('login-btn');
            const btnText = document.querySelector('.btn-text');
            const btnSpinner = document.getElementById('btn-spinner');
            
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const password = document.getElementById('password').value;
                    
                    if (!username || !password) {
                        e.preventDefault();
                        showMessage('Please fill in all fields!', 'error');
                        return false;
                    }
                    
                    // Show loading state
                    loginBtn.disabled = true;
                    btnText.textContent = 'Logging in...';
                    btnSpinner.style.display = 'inline-block';
                });
            }

            // Auto-focus username field
            const usernameInput = document.getElementById('username');
            if (usernameInput && !usernameInput.value) {
                setTimeout(() => usernameInput.focus(), 100);
            }

            // Enter key to submit
            document.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const activeElement = document.activeElement;
                    if (activeElement && (activeElement.id === 'username' || activeElement.id === 'password')) {
                        loginForm.dispatchEvent(new Event('submit'));
                    }
                }
            });

            // Input validation feedback
            const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#28a745';
                    } else {
                        this.style.borderColor = '';
                    }
                });
            });
        });
    </script>
</body>
</html>

