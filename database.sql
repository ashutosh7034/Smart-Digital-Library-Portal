-- Digital Library Portal Database
-- Created for Smart Digital Library Portal Project

-- Create database
CREATE DATABASE IF NOT EXISTS digital_library;
USE digital_library;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Books table for library management
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(50) UNIQUE,
    description TEXT,
    category VARCHAR(100),
    publication_year INT,
    quantity INT DEFAULT 1,
    available INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
-- Password is hashed using password_hash() PHP function
-- NOTE: If password doesn't work, run fix_admin_password.php to update it
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@library.com', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy', 'admin');

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample books
INSERT INTO books (title, author, isbn, description, category, publication_year, quantity, available) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', '978-0-7432-7356-5', 'A classic American novel about the Jazz Age.', 'Fiction', 1925, 5, 5),
('To Kill a Mockingbird', 'Harper Lee', '978-0-06-112008-4', 'A gripping tale of racial injustice and childhood innocence.', 'Fiction', 1960, 3, 3),
('1984', 'George Orwell', '978-0-452-28423-4', 'A dystopian social science fiction novel.', 'Science Fiction', 1949, 4, 4),
('Pride and Prejudice', 'Jane Austen', '978-0-14-143951-8', 'A romantic novel of manners.', 'Romance', 1813, 2, 2),
('The Catcher in the Rye', 'J.D. Salinger', '978-0-316-76948-0', 'A controversial novel about teenage rebellion.', 'Fiction', 1951, 3, 3);
