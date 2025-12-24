<?php
/**
 * Admin Dashboard
 * Book management interface for administrators
 */

require_once __DIR__ . '/../config/config.php';
requireAdmin(); // Ensure only admins can access

$page_title = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo"><?php echo APP_NAME; ?></h1>
            <div class="nav-menu">
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
                <a href="../logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-header">
            <h2>📊 Dashboard Statistics</h2>
            <button class="btn btn-sm btn-secondary" onclick="refreshStatistics()" title="Refresh Statistics">
                <span class="btn-icon">🔄</span> Refresh
            </button>
        </div>
        <div class="stats-container" id="stats-container">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-info">
                    <div class="stat-value" id="total-books">0</div>
                    <div class="stat-label">Total Books</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <div class="stat-value" id="total-users">0</div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👑</div>
                <div class="stat-info">
                    <div class="stat-value" id="total-admins">0</div>
                    <div class="stat-label">Administrators</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📖</div>
                <div class="stat-info">
                    <div class="stat-value" id="available-books">0</div>
                    <div class="stat-label">Available Copies</div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('books')">
                <span class="tab-icon">📚</span> Books Management
            </button>
            <button class="tab-btn" onclick="switchTab('users')">
                <span class="tab-icon">👥</span> Users Management
            </button>
        </div>

        <!-- Books Tab -->
        <div id="books-tab" class="tab-content active">
            <div class="dashboard-header">
                <h2>📚 Book Management</h2>
                <button class="btn btn-primary" onclick="openAddBookModal()">
                    <span class="btn-icon">➕</span> Add New Book
                </button>
            </div>

            <!-- Alert messages -->
            <div id="alert-container"></div>

            <!-- Books Table -->
            <div class="table-container">
                <table class="data-table" id="books-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Year</th>
                            <th>Quantity</th>
                            <th>Available</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="books-tbody">
                        <tr>
                            <td colspan="9" class="loading">Loading books...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users-tab" class="tab-content">
            <div class="dashboard-header">
                <h2>👥 User Management</h2>
                <div class="header-actions">
                    <button class="btn btn-success" onclick="openCreateUserModal('admin')">
                        <span class="btn-icon">👑</span> Create Admin
                    </button>
                    <button class="btn btn-primary" onclick="openCreateUserModal('user')">
                        <span class="btn-icon">👤</span> Create User
                    </button>
                    <button class="btn btn-secondary" onclick="loadUsers()">
                        <span class="btn-icon">🔄</span> Refresh
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <table class="data-table" id="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Registered Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        <tr>
                            <td colspan="6" class="loading">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit Book Modal -->
    <div id="book-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Add New Book</h3>
                <span class="close" onclick="closeBookModal()">&times;</span>
            </div>
            <form id="book-form">
                <input type="hidden" id="book-id" name="id">
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="publication_year">Publication Year</label>
                        <input type="number" id="publication_year" name="publication_year" min="1000" max="9999">
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1">
                    </div>
                    
                    <div class="form-group">
                        <label for="available">Available</label>
                        <input type="number" id="available" name="available" min="0" placeholder="Auto-set from quantity">
                        <small class="form-hint">Leave empty to match quantity</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBookModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Book</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create User/Admin Modal -->
    <div id="create-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="create-user-modal-title">Create New User</h3>
                <span class="close" onclick="closeCreateUserModal()">&times;</span>
            </div>
            <form id="create-user-form">
                <div class="form-group">
                    <label for="new-username">Username *</label>
                    <input type="text" id="new-username" name="username" required minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="new-email">Email *</label>
                    <input type="email" id="new-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="new-password">Password *</label>
                    <input type="password" id="new-password" name="password" required minlength="6">
                    <small class="form-hint">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="new-role">Role *</label>
                    <select id="new-role" name="role" required>
                        <option value="user">Regular User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">✓</span> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close" onclick="closeEditUserModal()">&times;</span>
            </div>
            <form id="edit-user-form">
                <input type="hidden" id="edit-user-id" name="id">
                
                <div class="form-group">
                    <label for="edit-username">Username *</label>
                    <input type="text" id="edit-username" name="username" required minlength="3">
                </div>
                
                <div class="form-group">
                    <label for="edit-email">Email *</label>
                    <input type="email" id="edit-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-role">Role *</label>
                    <select id="edit-role" name="role" required>
                        <option value="user">Regular User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit-password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit-password" name="password" minlength="6">
                    <small class="form-hint">Minimum 6 characters if changing</small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="btn-icon">💾</span> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="user-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="user-modal-title">User Details</h3>
                <span class="close" onclick="closeUserModal()">&times;</span>
            </div>
            <div id="user-details-content">
                <!-- User details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUserModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>

