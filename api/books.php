<?php
/**
 * Books REST API Endpoint
 * Handles CRUD operations for books
 * 
 * Endpoints:
 * GET /api/books.php - Get all books
 * GET /api/books.php?id={id} - Get single book
 * POST /api/books.php - Create new book (Admin only)
 * PUT /api/books.php?id={id} - Update book (Admin only)
 * DELETE /api/books.php?id={id} - Delete book (Admin only)
 */

require_once __DIR__ . '/../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
requireLogin();

// Get database connection
$conn = getDBConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get book ID from query string or request body
$book_id = $_GET['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            // GET /api/books.php - Get all books
            // GET /api/books.php?id={id} - Get single book
            if ($book_id) {
                // Get single book
                $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $book = $result->fetch_assoc();
                    jsonResponse([
                        'success' => true,
                        'data' => $book
                    ]);
                } else {
                    jsonResponse([
                        'success' => false,
                        'message' => 'Book not found'
                    ], 404);
                }
                $stmt->close();
            } else {
                // Get all books
                $result = $conn->query("SELECT * FROM books ORDER BY created_at DESC");
                $books = [];
                
                while ($row = $result->fetch_assoc()) {
                    $books[] = $row;
                }
                
                jsonResponse([
                    'success' => true,
                    'data' => $books,
                    'count' => count($books)
                ]);
            }
            break;
            
        case 'POST':
            // POST /api/books.php - Create new book (Admin only)
            requireAdmin();
            
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
            if (empty($input['title']) || empty($input['author'])) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Title and author are required'
                ], 400);
            }
            
            $title = sanitizeInput($input['title']);
            $author = sanitizeInput($input['author']);
            $isbn = sanitizeInput($input['isbn'] ?? '');
            $description = sanitizeInput($input['description'] ?? '');
            $category = sanitizeInput($input['category'] ?? '');
            $publication_year = !empty($input['publication_year']) ? intval($input['publication_year']) : null;
            $quantity = intval($input['quantity'] ?? 1);
            // If available is not provided, default to quantity
            $available = isset($input['available']) ? intval($input['available']) : $quantity;
            
            // Ensure available doesn't exceed quantity
            if ($available > $quantity) {
                $available = $quantity;
            }
            
            // Validate quantity and available
            if ($quantity < 1) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Quantity must be at least 1'
                ], 400);
            }
            
            if ($available < 0) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Available cannot be negative'
                ], 400);
            }
            
            // Check if ISBN already exists (if provided)
            if (!empty($isbn)) {
                $stmt = $conn->prepare("SELECT id FROM books WHERE isbn = ?");
                $stmt->bind_param("s", $isbn);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $stmt->close();
                    jsonResponse([
                        'success' => false,
                        'message' => 'ISBN already exists'
                    ], 400);
                }
                $stmt->close();
            }
            
            // Insert new book
            // Handle publication_year - use NULL if not provided or 0
            if ($publication_year === null || $publication_year === 0) {
                $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, description, category, quantity, available) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssii", $title, $author, $isbn, $description, $category, $quantity, $available);
            } else {
                $stmt = $conn->prepare("INSERT INTO books (title, author, isbn, description, category, publication_year, quantity, available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiii", $title, $author, $isbn, $description, $category, $publication_year, $quantity, $available);
            }
            
            if ($stmt->execute()) {
                $new_book_id = $conn->insert_id;
                jsonResponse([
                    'success' => true,
                    'message' => 'Book created successfully',
                    'data' => ['id' => $new_book_id]
                ], 201);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create book: ' . $stmt->error
                ], 500);
            }
            $stmt->close();
            break;
            
        case 'PUT':
            // PUT /api/books.php?id={id} - Update book (Admin only)
            requireAdmin();
            
            if (!$book_id) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Book ID is required'
                ], 400);
            }
            
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
            
            // Check if book exists
            $stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'Book not found'
                ], 404);
            }
            $stmt->close();
            
            // Build update query dynamically based on provided fields
            $update_fields = [];
            $update_values = [];
            $types = '';
            
            $allowed_fields = ['title', 'author', 'isbn', 'description', 'category', 'publication_year', 'quantity', 'available'];
            
            foreach ($allowed_fields as $field) {
                if (isset($input[$field])) {
                    $update_fields[] = "$field = ?";
                    $update_values[] = $field === 'publication_year' || $field === 'quantity' || $field === 'available' 
                        ? intval($input[$field]) 
                        : sanitizeInput($input[$field]);
                    $types .= ($field === 'publication_year' || $field === 'quantity' || $field === 'available') ? 'i' : 's';
                }
            }
            
            if (empty($update_fields)) {
                jsonResponse([
                    'success' => false,
                    'message' => 'No fields to update'
                ], 400);
            }
            
            // Check ISBN uniqueness if updating ISBN
            if (isset($input['isbn']) && !empty($input['isbn'])) {
                $stmt = $conn->prepare("SELECT id FROM books WHERE isbn = ? AND id != ?");
                $stmt->bind_param("si", $input['isbn'], $book_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $stmt->close();
                    jsonResponse([
                        'success' => false,
                        'message' => 'ISBN already exists'
                    ], 400);
                }
                $stmt->close();
            }
            
            $update_values[] = $book_id;
            $types .= 'i';
            
            $sql = "UPDATE books SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$update_values);
            
            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Book updated successfully'
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update book'
                ], 500);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            // DELETE /api/books.php?id={id} - Delete book (Admin only)
            requireAdmin();
            
            if (!$book_id) {
                jsonResponse([
                    'success' => false,
                    'message' => 'Book ID is required'
                ], 400);
            }
            
            // Check if book exists
            $stmt = $conn->prepare("SELECT id FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                jsonResponse([
                    'success' => false,
                    'message' => 'Book not found'
                ], 404);
            }
            $stmt->close();
            
            // Delete book
            $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
            $stmt->bind_param("i", $book_id);
            
            if ($stmt->execute()) {
                jsonResponse([
                    'success' => true,
                    'message' => 'Book deleted successfully'
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete book'
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

