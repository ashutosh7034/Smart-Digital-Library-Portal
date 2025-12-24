<?php
/**
 * Users REST API Endpoint
 * Handles user management operations for admin
 * 
 * Endpoints:
 * GET /api/users.php - Get all users
 * GET /api/users.php?id={id} - Get single user details
 * POST /api/users.php - Create new user/admin
 * PUT /api/users.php?id={id} - Update user
 * DELETE /api/users.php?id={id} - Delete user
 */

require_once __DIR__ . '/../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication and admin access
requireAdmin();

// Get database connection
$conn = getDBConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get user ID from query string
$user_id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // GET /api/users.php - Get all users
            // GET /api/users.php?id={id} - Get single user
            if ($user_id) {
                // Get single user (excluding password)
                $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    jsonResponse([
                        'success' => true,
                        'data' => $user
                    ]);
                } else {
                    jsonResponse([
                        'success' => false,
                        'message' => 'User not found'
                    ], 404);
                }
                $stmt->close();
            } else {
                // Get all users (excluding passwords)
                $result = $conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
                $users = [];
                
                while ($row = $result->fetch_assoc()) {
                    // Format created_at date
                    $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
                    $users[] = $row;
                }
                
                jsonResponse([
                    'success' => true,
                    'data' => $users,
                    'count' => count($users)
                ]);
            }
            break;
            
        case 'POST':
            // POST /api/users.php - Create new user/admin
            // Get JSON input
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
            
            // Check if JSON parsing failed
            if (json_last_error() !== JSON_ERROR_NONE) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON data: ' . json_last_error_msg()
                ], 400);
            }
            
            // Check if input is null or empty
            if ($input === null || !is_array($input)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid request data'
                ], 400);
            }
            
            // Validate required fields
            if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Username, email, and password are required'
                ], 400);
            }
            
            $username = sanitizeInput($input['username']);
            $email = sanitizeInput($input['email']);
            $password = $input['password'];
            $role = sanitizeInput($input['role'] ?? 'user');
            
            // Validate role
            if (!in_array($role, ['admin', 'user'])) {
                $role = 'user';
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid email address'
                ], 400);
            }
            
            // Validate password length
            if (strlen($password) < 6) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Password must be at least 6 characters long'
                ], 400);
            }
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $stmt->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'Username or email already exists'
                ], 400);
            }
            $stmt->close();
            
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                jsonResponse([
                    'success' => true,
                    'message' => ucfirst($role) . ' created successfully',
                    'data' => ['id' => $new_user_id, 'username' => $username, 'role' => $role]
                ], 201);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create user: ' . $stmt->error
                ], 500);
            }
            $stmt->close();
            break;
            
        case 'PUT':
            // PUT /api/users.php?id={id} - Update user
            if (!$user_id) {
                jsonResponse([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                jsonResponse([
                    'success' => false,
                    'message' => 'You cannot modify your own account'
                ], 400);
            }
            
            // Get JSON input
            $raw_input = file_get_contents('php://input');
            $input = json_decode($raw_input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON data: ' . json_last_error_msg()
                ], 400);
            }
            
            if ($input === null || !is_array($input)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Invalid request data'
                ], 400);
            }
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            $stmt->close();
            
            // Build update query
            $update_fields = [];
            $update_values = [];
            $types = '';
            
            if (isset($input['username']) && !empty($input['username'])) {
                // Check username uniqueness
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check_stmt->bind_param("si", $input['username'], $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $check_stmt->close();
                    jsonResponse([
                        'success' => false,
                        'message' => 'Username already exists'
                    ], 400);
                }
                $check_stmt->close();
                
                $update_fields[] = "username = ?";
                $update_values[] = sanitizeInput($input['username']);
                $types .= 's';
            }
            
            if (isset($input['email']) && !empty($input['email'])) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Invalid email address'
                    ], 400);
                }
                
                // Check email uniqueness
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $input['email'], $user_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $check_stmt->close();
                    jsonResponse([
                        'success' => false,
                        'message' => 'Email already exists'
                    ], 400);
                }
                $check_stmt->close();
                
                $update_fields[] = "email = ?";
                $update_values[] = sanitizeInput($input['email']);
                $types .= 's';
            }
            
            if (isset($input['role']) && !empty($input['role'])) {
                $role = sanitizeInput($input['role']);
                if (!in_array($role, ['admin', 'user'])) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Invalid role'
                    ], 400);
                }
                $update_fields[] = "role = ?";
                $update_values[] = $role;
                $types .= 's';
            }
            
            if (isset($input['password']) && !empty($input['password'])) {
                if (strlen($input['password']) < 6) {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Password must be at least 6 characters long'
                    ], 400);
                }
                $update_fields[] = "password = ?";
                $update_values[] = password_hash($input['password'], PASSWORD_DEFAULT);
                $types .= 's';
            }
            
            if (empty($update_fields)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
            }
            
            $update_values[] = $user_id;
            $types .= 'i';
            
            $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$update_values);
            
            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update user: ' . $stmt->error
                ], 500);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            // DELETE /api/users.php?id={id} - Delete user
            if (!$user_id) {
                jsonResponse([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }
            
            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                jsonResponse([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 400);
            }
            
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            $stmt->close();
            
            // Delete user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete user: ' . $stmt->error
                ], 500);
            }
            $stmt->close();
            break;
            
        default:
            jsonResponse([
                'success' => false,
                'message' => 'Method not allowed'
            ], 405);
    }
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
} finally {
    closeDBConnection($conn);
}

