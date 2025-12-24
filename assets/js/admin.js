/**
 * Admin Dashboard JavaScript
 * Handles book management operations for admin users
 */

let currentEditingId = null;

// Load books when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadBooks();
    loadUsers();
    loadStatistics();
    
    // Handle form submission
    document.getElementById('book-form').addEventListener('submit', handleFormSubmit);
    
    // Handle create user form
    const createUserForm = document.getElementById('create-user-form');
    if (createUserForm) {
        createUserForm.addEventListener('submit', handleCreateUserSubmit);
    }
    
    // Handle edit user form
    const editUserForm = document.getElementById('edit-user-form');
    if (editUserForm) {
        editUserForm.addEventListener('submit', handleEditUserSubmit);
    }
});

/**
 * Load all books and display in table
 */
async function loadBooks() {
    try {
        const response = await getBooks();
        const books = response.data || [];
        displayBooks(books);
        // Don't call loadStatistics here to avoid double calls
        // It will be called after operations complete
        return books; // Return for use in statistics
    } catch (error) {
        showAlert('Error loading books: ' + error.message, 'error');
        document.getElementById('books-tbody').innerHTML = 
            '<tr><td colspan="9" class="loading">Error loading books</td></tr>';
        throw error;
    }
}

/**
 * Display books in table
 * @param {Array} books - Array of book objects
 */
function displayBooks(books) {
    const tbody = document.getElementById('books-tbody');
    
    if (books.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="loading">No books found</td></tr>';
        return;
    }
    
    tbody.innerHTML = books.map(book => `
        <tr>
            <td>${book.id}</td>
            <td>${escapeHtml(book.title)}</td>
            <td>${escapeHtml(book.author)}</td>
            <td>${escapeHtml(book.isbn || '-')}</td>
            <td>${escapeHtml(book.category || '-')}</td>
            <td>${book.publication_year || '-'}</td>
            <td>${book.quantity}</td>
            <td>${book.available}</td>
            <td class="actions">
                <button class="btn btn-success" onclick="editBook(${book.id})" title="Edit Book">
                    <span class="btn-icon">✏️</span> Edit
                </button>
                <button class="btn btn-danger" onclick="deleteBookConfirm(${book.id})" title="Delete Book">
                    <span class="btn-icon">🗑️</span> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Open modal for adding new book
 */
function openAddBookModal() {
    currentEditingId = null;
    document.getElementById('modal-title').textContent = 'Add New Book';
    document.getElementById('book-form').reset();
    document.getElementById('book-id').value = '';
    document.getElementById('book-modal').classList.add('show');
}

/**
 * Close book modal
 */
function closeBookModal() {
    document.getElementById('book-modal').classList.remove('show');
    currentEditingId = null;
    document.getElementById('book-form').reset();
}

/**
 * Edit book - Load book data and open modal
 * @param {number} id - Book ID
 */
async function editBook(id) {
    try {
        const response = await getBooks(id);
        const book = response.data;
        
        currentEditingId = id;
        document.getElementById('modal-title').textContent = 'Edit Book';
        document.getElementById('book-id').value = book.id;
        document.getElementById('title').value = book.title || '';
        document.getElementById('author').value = book.author || '';
        document.getElementById('isbn').value = book.isbn || '';
        document.getElementById('description').value = book.description || '';
        document.getElementById('category').value = book.category || '';
        document.getElementById('publication_year').value = book.publication_year || '';
        const quantity = book.quantity || 1;
        document.getElementById('quantity').value = quantity;
        document.getElementById('available').value = book.available || quantity;
        
        document.getElementById('book-modal').classList.add('show');
    } catch (error) {
        showAlert('Error loading book: ' + error.message, 'error');
    }
}

/**
 * Handle form submission (Create or Update)
 * @param {Event} e - Form submit event
 */
async function handleFormSubmit(e) {
    e.preventDefault();
    
    // Get form values
    const title = document.getElementById('title').value.trim();
    const author = document.getElementById('author').value.trim();
    
    // Validate required fields
    if (!title || !author) {
        showAlert('Title and author are required!', 'error');
        return;
    }
    
    // Get form values
    const isbn = document.getElementById('isbn').value.trim();
    const description = document.getElementById('description').value.trim();
    const category = document.getElementById('category').value.trim();
    const publicationYearInput = document.getElementById('publication_year').value.trim();
    const quantityInput = document.getElementById('quantity').value.trim();
    const availableInput = document.getElementById('available').value.trim();
    
    const formData = {
        title: title,
        author: author
    };
    
    // Add optional fields only if they have values
    if (isbn) formData.isbn = isbn;
    if (description) formData.description = description;
    if (category) formData.category = category;
    
    // Handle numeric fields
    if (publicationYearInput) {
        const year = parseInt(publicationYearInput);
        if (year > 0) formData.publication_year = year;
    }
    
    const quantity = parseInt(quantityInput) || 1;
    // If available is not provided, default to quantity
    const available = availableInput ? parseInt(availableInput) : quantity;
    
    // Ensure available doesn't exceed quantity
    formData.quantity = quantity;
    formData.available = Math.min(available, quantity);
    
    console.log('Submitting book data:', formData);
    console.log('API URL:', API_BASE_URL);
    console.log('Current path:', window.location.pathname);
    
    // Show loading state
    const submitBtn = document.querySelector('#book-form button[type="submit"]');
    const originalBtnText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Saving...';
    
    try {
        if (currentEditingId) {
            // Update existing book
            console.log('Updating book ID:', currentEditingId);
            await updateBook(currentEditingId, formData);
            showAlert('Book updated successfully!', 'success');
        } else {
            // Create new book
            console.log('Creating new book');
            const result = await createBook(formData);
            console.log('Create result:', result);
            showAlert('Book created successfully!', 'success');
        }
        
        closeBookModal();
        await loadBooks(); // Wait for books to load
        loadStatistics(); // Update statistics after book operation
    } catch (error) {
        console.error('Error saving book:', error);
        console.error('Full error:', error);
        
        // Show detailed error message
        let errorMessage = 'Error saving book: ';
        if (error.message) {
            errorMessage += error.message;
        } else {
            errorMessage += 'Unknown error occurred. Please check console for details.';
        }
        
        showAlert(errorMessage, 'error');
        
        // Don't close modal on error so user can fix and retry
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
    }
}

/**
 * Confirm and delete book
 * @param {number} id - Book ID
 */
async function deleteBookConfirm(id) {
    if (!confirm('Are you sure you want to delete this book?')) {
        return;
    }
    
    try {
        await deleteBook(id);
        showAlert('Book deleted successfully!', 'success');
        await loadBooks(); // Wait for books to load
        loadStatistics(); // Update statistics after deletion
    } catch (error) {
        showAlert('Error deleting book: ' + error.message, 'error');
    }
}

/**
 * Escape HTML to prevent XSS
 * @param {string} text - Text to escape
 * @returns {string} Escaped text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('book-modal');
    if (event.target === modal) {
        closeBookModal();
    }
    
    const userModal = document.getElementById('user-details-modal');
    if (event.target === userModal) {
        closeUserModal();
    }
    
    const createUserModal = document.getElementById('create-user-modal');
    if (event.target === createUserModal) {
        closeCreateUserModal();
    }
    
    const editUserModal = document.getElementById('edit-user-modal');
    if (event.target === editUserModal) {
        closeEditUserModal();
    }
}

// ==================== USER MANAGEMENT FUNCTIONS ====================

/**
 * Switch between tabs
 */
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Activate selected button
    event.target.classList.add('active');
    
    // Load data if switching tabs
    if (tabName === 'users') {
        loadUsers(); // This will also update statistics
    } else if (tabName === 'books') {
        loadBooks(); // This will also update statistics
    }
    
    // Always refresh statistics when switching tabs
    loadStatistics();
}

/**
 * Load statistics - Always fetches fresh data for accuracy
 * This function always fetches fresh data from the API to ensure accuracy
 */
async function loadStatistics() {
    try {
        // Show loading state
        const statCards = document.querySelectorAll('.stat-value');
        statCards.forEach(card => {
            if (card.textContent !== '0') {
                card.style.opacity = '0.6';
            }
        });
        
        // Always fetch fresh data from API (never use cached data)
        const [booksRes, usersRes] = await Promise.all([
            getBooks(),
            getUsers()
        ]);
        
        const books = booksRes.data || [];
        const users = usersRes.data || [];
        
        // Calculate accurate statistics from fresh data
        const totalBooks = books.length;
        const totalUsers = users.length;
        const totalAdmins = users.filter(u => u.role === 'admin').length;
        const totalRegularUsers = totalUsers - totalAdmins;
        
        // Calculate available books (sum of all available fields)
        // Ensure we parse as integer to avoid string concatenation
        const availableBooks = books.reduce((sum, book) => {
            const available = parseInt(book.available) || 0;
            return sum + available;
        }, 0);
        
        // Calculate total quantity
        const totalQuantity = books.reduce((sum, book) => {
            const quantity = parseInt(book.quantity) || 0;
            return sum + quantity;
        }, 0);
        
        // Calculate borrowed books
        const borrowedBooks = totalQuantity - availableBooks;
        
        // Update statistics with smooth animation
        updateStatValue('total-books', totalBooks);
        updateStatValue('total-users', totalUsers);
        updateStatValue('total-admins', totalAdmins);
        updateStatValue('available-books', availableBooks);
        
        // Store for reference (can be used for debugging)
        window.currentStats = {
            totalBooks,
            totalUsers,
            totalAdmins,
            totalRegularUsers,
            availableBooks,
            totalQuantity,
            borrowedBooks,
            lastUpdated: new Date().toLocaleTimeString()
        };
        
        console.log('Statistics updated:', window.currentStats);
        
    } catch (error) {
        console.error('Error loading statistics:', error);
        // Don't show alert for statistics errors to avoid spam
        // Just log it
    }
}

/**
 * Update statistic value with animation
 */
function updateStatValue(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const oldValue = parseInt(element.textContent) || 0;
    
    if (oldValue !== newValue) {
        // Animate the change
        element.style.transform = 'scale(1.1)';
        element.style.color = '#28a745';
        
        setTimeout(() => {
            element.textContent = newValue;
            element.style.transform = 'scale(1)';
            element.style.opacity = '1';
            element.style.color = '';
            element.style.transition = 'all 0.3s ease';
        }, 150);
    } else {
        element.style.opacity = '1';
    }
}

/**
 * Refresh statistics manually
 */
async function refreshStatistics() {
    showAlert('Refreshing statistics...', 'info');
    await loadStatistics();
    showAlert('Statistics updated!', 'success');
}

/**
 * Get users API endpoint
 */
async function getUsers(id = null) {
    try {
        let apiUrl = '../api/users.php';
        if (!window.location.pathname.includes('/admin/') && !window.location.pathname.includes('/user/')) {
            apiUrl = 'api/users.php';
        }
        
        const url = id ? `${apiUrl}?id=${id}` : apiUrl;
        const response = await fetch(url, {
            credentials: 'same-origin'
        });
        
        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Invalid response from server. Status: ' + response.status);
        }
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        console.error('Error fetching users:', error);
        throw error;
    }
}

/**
 * Load all users and display in table
 */
async function loadUsers() {
    try {
        const response = await getUsers();
        const users = response.data || [];
        displayUsers(users);
        // Statistics will be updated after operations
    } catch (error) {
        showAlert('Error loading users: ' + error.message, 'error');
        document.getElementById('users-tbody').innerHTML = 
            '<tr><td colspan="6" class="loading">Error loading users</td></tr>';
    }
}

/**
 * Display users in table
 */
function displayUsers(users) {
    const tbody = document.getElementById('users-tbody');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="loading">No users found</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td><strong>#${user.id}</strong></td>
            <td><strong>${escapeHtml(user.username)}</strong></td>
            <td>${escapeHtml(user.email)}</td>
            <td><span class="role-badge ${user.role}">${user.role === 'admin' ? '👑 ' : '👤 '}${escapeHtml(user.role.toUpperCase())}</span></td>
            <td>${escapeHtml(user.created_at)}</td>
            <td class="actions">
                <button class="btn btn-success" onclick="editUser(${user.id})" title="Edit User">
                    <span class="btn-icon">✏️</span> Edit
                </button>
                <button class="btn btn-danger" onclick="deleteUserConfirm(${user.id})" title="Delete User">
                    <span class="btn-icon">🗑️</span> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

/**
 * Open create user modal
 */
function openCreateUserModal(role = 'user') {
    document.getElementById('create-user-form').reset();
    document.getElementById('new-role').value = role;
    
    const title = role === 'admin' ? 'Create New Administrator' : 'Create New User';
    document.getElementById('create-user-modal-title').textContent = title;
    document.getElementById('create-user-modal').classList.add('show');
}

/**
 * Close create user modal
 */
function closeCreateUserModal() {
    document.getElementById('create-user-modal').classList.remove('show');
    document.getElementById('create-user-form').reset();
}

/**
 * Handle create user form submission
 */
async function handleCreateUserSubmit(e) {
    e.preventDefault();
    
    const formData = {
        username: document.getElementById('new-username').value.trim(),
        email: document.getElementById('new-email').value.trim(),
        password: document.getElementById('new-password').value,
        role: document.getElementById('new-role').value
    };
    
    if (!formData.username || !formData.email || !formData.password) {
        showAlert('All fields are required!', 'error');
        return;
    }
    
    if (formData.password.length < 6) {
        showAlert('Password must be at least 6 characters long!', 'error');
        return;
    }
    
    try {
        await createUser(formData);
        showAlert(`${formData.role === 'admin' ? 'Administrator' : 'User'} created successfully!`, 'success');
        closeCreateUserModal();
        await loadUsers(); // Wait for users to load
        loadStatistics(); // Update statistics after user creation
    } catch (error) {
        showAlert('Error creating user: ' + error.message, 'error');
    }
}

/**
 * Create user via API
 */
async function createUser(userData) {
    try {
        let apiUrl = '../api/users.php';
        if (!window.location.pathname.includes('/admin/') && !window.location.pathname.includes('/user/')) {
            apiUrl = 'api/users.php';
        }
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(userData)
        });
        
        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Invalid response from server. Status: ' + response.status);
        }
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        console.error('Error creating user:', error);
        throw error;
    }
}

/**
 * Edit user - Load user data and open modal
 */
async function editUser(id) {
    try {
        const response = await getUsers(id);
        const user = response.data;
        
        document.getElementById('edit-user-id').value = user.id;
        document.getElementById('edit-username').value = user.username || '';
        document.getElementById('edit-email').value = user.email || '';
        document.getElementById('edit-role').value = user.role || 'user';
        document.getElementById('edit-password').value = '';
        
        document.getElementById('edit-user-modal').classList.add('show');
    } catch (error) {
        showAlert('Error loading user: ' + error.message, 'error');
    }
}

/**
 * Close edit user modal
 */
function closeEditUserModal() {
    document.getElementById('edit-user-modal').classList.remove('show');
    document.getElementById('edit-user-form').reset();
}

/**
 * Handle edit user form submission
 */
async function handleEditUserSubmit(e) {
    e.preventDefault();
    
    const userId = document.getElementById('edit-user-id').value;
    const formData = {
        username: document.getElementById('edit-username').value.trim(),
        email: document.getElementById('edit-email').value.trim(),
        role: document.getElementById('edit-role').value
    };
    
    const password = document.getElementById('edit-password').value;
    if (password) {
        if (password.length < 6) {
            showAlert('Password must be at least 6 characters long!', 'error');
            return;
        }
        formData.password = password;
    }
    
    if (!formData.username || !formData.email) {
        showAlert('Username and email are required!', 'error');
        return;
    }
    
    try {
        await updateUser(userId, formData);
        showAlert('User updated successfully!', 'success');
        closeEditUserModal();
        await loadUsers(); // Wait for users to load
        loadStatistics(); // Update statistics after user update
    } catch (error) {
        showAlert('Error updating user: ' + error.message, 'error');
    }
}

/**
 * Update user via API
 */
async function updateUser(id, userData) {
    try {
        let apiUrl = '../api/users.php';
        if (!window.location.pathname.includes('/admin/') && !window.location.pathname.includes('/user/')) {
            apiUrl = 'api/users.php';
        }
        
        const response = await fetch(`${apiUrl}?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(userData)
        });
        
        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Invalid response from server. Status: ' + response.status);
        }
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        console.error('Error updating user:', error);
        throw error;
    }
}

/**
 * Confirm and delete user
 */
async function deleteUserConfirm(id) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone!')) {
        return;
    }
    
    try {
        await deleteUser(id);
        showAlert('User deleted successfully!', 'success');
        await loadUsers(); // Wait for users to load
        loadStatistics(); // Update statistics after user deletion
    } catch (error) {
        showAlert('Error deleting user: ' + error.message, 'error');
    }
}

/**
 * Delete user via API
 */
async function deleteUser(id) {
    try {
        let apiUrl = '../api/users.php';
        if (!window.location.pathname.includes('/admin/') && !window.location.pathname.includes('/user/')) {
            apiUrl = 'api/users.php';
        }
        
        const response = await fetch(`${apiUrl}?id=${id}`, {
            method: 'DELETE',
            credentials: 'same-origin'
        });
        
        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new Error('Invalid response from server. Status: ' + response.status);
        }
        
        if (!response.ok) {
            throw new Error(data.message || data.error || 'An error occurred');
        }
        
        return data;
    } catch (error) {
        console.error('Error deleting user:', error);
        throw error;
    }
}

/**
 * View user details
 */
async function viewUserDetails(id) {
    try {
        const response = await getUsers(id);
        const user = response.data;
        
        const content = `
            <div class="user-details">
                <h4>User Information</h4>
                <div class="detail-row">
                    <span class="detail-label">User ID:</span>
                    <span class="detail-value">${user.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value">${escapeHtml(user.username)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${escapeHtml(user.email)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">
                        <span class="role-badge ${user.role}">${escapeHtml(user.role)}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Registered Date:</span>
                    <span class="detail-value">${escapeHtml(user.created_at)}</span>
                </div>
            </div>
        `;
        
        document.getElementById('user-details-content').innerHTML = content;
        document.getElementById('user-modal-title').textContent = `User: ${escapeHtml(user.username)}`;
        document.getElementById('user-details-modal').classList.add('show');
    } catch (error) {
        showAlert('Error loading user details: ' + error.message, 'error');
    }
}

/**
 * Close user details modal
 */
function closeUserModal() {
    document.getElementById('user-details-modal').classList.remove('show');
}

