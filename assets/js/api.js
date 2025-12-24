/**
 * API Helper Functions
 * Handles all API calls using Fetch API
 */

// Determine API base URL based on current path
// This ensures the API path works from both root and subdirectories
let API_BASE_URL = 'api/books.php';

// Auto-detect correct path
(function() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/admin/') || currentPath.includes('/user/')) {
        API_BASE_URL = '../api/books.php';
    }
})();

/**
 * Show alert message
 * @param {string} message - Alert message
 * @param {string} type - Alert type (success, error, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

/**
 * Handle API errors
 * @param {Response} response - Fetch response object
 * @returns {Promise} Parsed JSON or throws error
 */
async function handleResponse(response) {
    let data;
    const contentType = response.headers.get('content-type');
    
    try {
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // If not JSON, get text response
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned invalid response. Status: ' + response.status);
        }
    } catch (error) {
        if (error.message) {
            throw error;
        }
        throw new Error('Invalid response from server. Status: ' + response.status + '. ' + error.message);
    }
    
    if (!response.ok) {
        const errorMsg = data.message || data.error || `Server error (${response.status})`;
        console.error('API Error:', {
            status: response.status,
            statusText: response.statusText,
            data: data
        });
        throw new Error(errorMsg);
    }
    
    return data;
}

/**
 * GET request - Fetch all books or single book
 * @param {number|null} id - Book ID (optional)
 * @returns {Promise} Book(s) data
 */
async function getBooks(id = null) {
    try {
        const url = id ? `${API_BASE_URL}?id=${id}` : API_BASE_URL;
        const response = await fetch(url, {
            credentials: 'same-origin' // Include cookies/session
        });
        const data = await handleResponse(response);
        return data;
    } catch (error) {
        console.error('Error fetching books:', error);
        throw error;
    }
}

/**
 * POST request - Create new book
 * @param {Object} bookData - Book data object
 * @returns {Promise} Created book data
 */
async function createBook(bookData) {
    try {
        // Validate required fields before sending
        if (!bookData.title || !bookData.author) {
            throw new Error('Title and author are required');
        }
        
        // Ensure API URL is correct
        let apiUrl = API_BASE_URL;
        const currentPath = window.location.pathname;
        if (currentPath.includes('/admin/') || currentPath.includes('/user/')) {
            apiUrl = '../api/books.php';
        } else {
            apiUrl = 'api/books.php';
        }
        
        console.log('Creating book with URL:', apiUrl);
        console.log('Book data being sent:', bookData);
        
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Include cookies/session
            body: JSON.stringify(bookData)
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await handleResponse(response);
        console.log('Book created successfully:', data);
        return data;
    } catch (error) {
        console.error('Error creating book:', error);
        console.error('Book data:', bookData);
        console.error('API URL used:', API_BASE_URL);
        throw error;
    }
}

/**
 * PUT request - Update existing book
 * @param {number} id - Book ID
 * @param {Object} bookData - Updated book data
 * @returns {Promise} Updated book data
 */
async function updateBook(id, bookData) {
    try {
        const response = await fetch(`${API_BASE_URL}?id=${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin', // Include cookies/session
            body: JSON.stringify(bookData)
        });
        
        const data = await handleResponse(response);
        return data;
    } catch (error) {
        console.error('Error updating book:', error);
        throw error;
    }
}

/**
 * DELETE request - Delete book
 * @param {number} id - Book ID
 * @returns {Promise} Deletion result
 */
async function deleteBook(id) {
    try {
        const response = await fetch(`${API_BASE_URL}?id=${id}`, {
            method: 'DELETE',
            credentials: 'same-origin' // Include cookies/session
        });
        
        const data = await handleResponse(response);
        return data;
    } catch (error) {
        console.error('Error deleting book:', error);
        throw error;
    }
}

