/**
 * User Dashboard JavaScript
 * Handles book browsing for regular users
 */

let allBooks = [];

// Load books when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadBooks();
});

/**
 * Load all books and display in grid
 */
async function loadBooks() {
    try {
        const response = await getBooks();
        allBooks = response.data || [];
        displayBooks(allBooks);
    } catch (error) {
        showAlert('Error loading books: ' + error.message, 'error');
        document.getElementById('books-grid').innerHTML = 
            '<div class="loading">Error loading books</div>';
    }
}

/**
 * Display books in grid layout
 * @param {Array} books - Array of book objects
 */
function displayBooks(books) {
    const grid = document.getElementById('books-grid');
    
    if (books.length === 0) {
        grid.innerHTML = '<div class="loading">No books found</div>';
        return;
    }
    
    grid.innerHTML = books.map(book => `
        <div class="book-card" onclick="showBookDetails(${book.id})">
            <h3>${escapeHtml(book.title)}</h3>
            <p class="book-author">by ${escapeHtml(book.author)}</p>
            ${book.description ? `<p style="font-size: 13px; color: #666; margin-top: 10px;">${escapeHtml(book.description.substring(0, 100))}${book.description.length > 100 ? '...' : ''}</p>` : ''}
            <div class="book-meta">
                <span>${book.category || 'Uncategorized'}</span>
                <span class="book-available">${book.available} available</span>
            </div>
        </div>
    `).join('');
}

/**
 * Filter books based on search input
 */
function filterBooks() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    
    if (!searchTerm) {
        displayBooks(allBooks);
        return;
    }
    
    const filtered = allBooks.filter(book => 
        book.title.toLowerCase().includes(searchTerm) ||
        book.author.toLowerCase().includes(searchTerm) ||
        (book.category && book.category.toLowerCase().includes(searchTerm)) ||
        (book.isbn && book.isbn.toLowerCase().includes(searchTerm))
    );
    
    displayBooks(filtered);
}

/**
 * Show book details in modal
 * @param {number} id - Book ID
 */
async function showBookDetails(id) {
    try {
        const response = await getBooks(id);
        const book = response.data;
        
        const content = `
            <div class="book-details">
                <h4>${escapeHtml(book.title)}</h4>
                <p><span class="detail-label">Author:</span> ${escapeHtml(book.author)}</p>
                ${book.isbn ? `<p><span class="detail-label">ISBN:</span> ${escapeHtml(book.isbn)}</p>` : ''}
                ${book.category ? `<p><span class="detail-label">Category:</span> ${escapeHtml(book.category)}</p>` : ''}
                ${book.publication_year ? `<p><span class="detail-label">Publication Year:</span> ${book.publication_year}</p>` : ''}
                ${book.description ? `<p><span class="detail-label">Description:</span><br>${escapeHtml(book.description)}</p>` : ''}
                <p><span class="detail-label">Total Quantity:</span> ${book.quantity}</p>
                <p><span class="detail-label">Available:</span> <span style="color: #28a745; font-weight: bold;">${book.available}</span></p>
            </div>
        `;
        
        document.getElementById('book-details-content').innerHTML = content;
        document.getElementById('details-title').textContent = book.title;
        document.getElementById('book-details-modal').classList.add('show');
    } catch (error) {
        showAlert('Error loading book details: ' + error.message, 'error');
    }
}

/**
 * Close book details modal
 */
function closeDetailsModal() {
    document.getElementById('book-details-modal').classList.remove('show');
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
    const modal = document.getElementById('book-details-modal');
    if (event.target === modal) {
        closeDetailsModal();
    }
}

