# Digital Library Portal

A complete full-stack web application for managing a digital library with user authentication, role-based access control, and REST API endpoints.

**Live Demo**: https://smartdigitallibrary.infinityfreeapp.com/

## Tech Stack

- **Frontend**: HTML, CSS, JavaScript (Vanilla JS)
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Authentication**: Sessions and Cookies
- **API**: REST API using PHP

## Features

- ✅ User registration and login
- ✅ Admin login
- ✅ Session-based authentication
- ✅ Cookie-based "Remember Me" functionality
- ✅ Role-based dashboards (Admin/User)
- ✅ CRUD operations on books
- ✅ REST API endpoints for books
- ✅ Modern, responsive UI
- ✅ Error handling and validation

## Project Structure

```
Smart-Digital-Library-Portal-1/
├── config/
│   ├── database.php      # Database connection
│   └── config.php        # Application configuration
├── api/
│   └── books.php         # REST API endpoints for books
├── auth/
│   └── register.php      # User registration page
├── admin/
│   └── dashboard.php     # Admin dashboard
├── user/
│   └── dashboard.php     # User dashboard
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   └── js/
│       ├── api.js        # API helper functions
│       ├── admin.js      # Admin dashboard logic
│       └── user.js       # User dashboard logic
├── index.php             # Main entry point
├── login.php             # Login page
├── logout.php            # Logout handler
├── database.sql          # Database schema
└── README.md             # This file
```

## Installation & Setup

### Prerequisites

- XAMPP (or any PHP/MySQL server)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Step 1: Database Setup

1. Start XAMPP and ensure MySQL is running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the `database.sql` file to create the database and tables
   - Or run the SQL commands manually in phpMyAdmin

### Step 2: Database Configuration

1. Open `config/database.php`
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Your MySQL password
   define('DB_NAME', 'digital_library');
   ```

### Step 3: Application URL Configuration

1. Open `config/config.php`
2. Update the application URL if your project is in a different location:
   ```php
   define('APP_URL', 'http://localhost/Smart-Digital-Library-Portal-1');
   ```

### Step 4: Access the Application

1. Place the project folder in `htdocs` directory (for XAMPP)
2. Open browser and navigate to:
   ```
   http://localhost/Smart-Digital-Library-Portal-1
   ```

### Hosted Instance

- A live deployment is available at https://smartdigitallibrary.infinityfreeapp.com/
- Use it to explore the app without local setup; functionality mirrors the local install.
- Default admin creds may be rotated; if login fails, reset via your own deployment or local setup scripts.

## Default Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `admin123`

**⚠️ IMPORTANT**: If you get "Invalid username or password" error:
1. Run this fix script: `http://localhost/Smart-Digital-Library-Portal-1/fix_admin_password.php`
2. This will set the admin password to `admin123`
3. **DELETE** `fix_admin_password.php` after running it!

### User Account
- Register a new account through the registration page

## API Endpoints

### Books API (`/api/books.php`)

#### GET - Get all books
```
GET /api/books.php
```

#### GET - Get single book
```
GET /api/books.php?id={id}
```

#### POST - Create book (Admin only)
```
POST /api/books.php
Content-Type: application/json

{
  "title": "Book Title",
  "author": "Author Name",
  "isbn": "978-0-123456-78-9",
  "description": "Book description",
  "category": "Fiction",
  "publication_year": 2023,
  "quantity": 5,
  "available": 5
}
```

#### PUT - Update book (Admin only)
```
PUT /api/books.php?id={id}
Content-Type: application/json

{
  "title": "Updated Title",
  "author": "Updated Author"
}
```

#### DELETE - Delete book (Admin only)
```
DELETE /api/books.php?id={id}
```

## Usage Guide

### For Administrators

1. Login with admin credentials
2. View all books in the admin dashboard
3. Click "Add New Book" to create a new book
4. Click "Edit" to modify book details
5. Click "Delete" to remove a book

### For Regular Users

1. Register a new account or login
2. Browse books in the user dashboard
3. Use the search box to filter books
4. Click on any book card to view details

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control
- CSRF protection considerations

## File Explanations

### Configuration Files

- **config/database.php**: Handles database connection
- **config/config.php**: Application-wide settings and helper functions

### Authentication Files

- **login.php**: User and admin login page
- **auth/register.php**: User registration page
- **logout.php**: Session destruction and logout

### API Files

- **api/books.php**: REST API endpoints for CRUD operations

### Dashboard Files

- **admin/dashboard.php**: Admin interface for book management
- **user/dashboard.php**: User interface for browsing books

### Frontend Files

- **assets/css/style.css**: Complete styling for the application
- **assets/js/api.js**: Fetch API wrapper functions
- **assets/js/admin.js**: Admin dashboard functionality
- **assets/js/user.js**: User dashboard functionality

## Error Handling

- Form validation on both client and server side
- Database error handling
- API error responses with appropriate HTTP status codes
- User-friendly error messages

## Browser Compatibility

- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## Notes for College Submission

1. All code is well-commented
2. Follows PHP best practices
3. Clean and organized structure
4. Beginner-friendly code
5. No external frameworks required
6. Fully functional and testable

## Troubleshooting

### Database Connection Error
- Check if MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database exists

### Session Issues
- Check PHP session configuration
- Ensure `session_start()` is called
- Clear browser cookies if needed

### API Not Working
- Check file permissions
- Verify `.htaccess` if using Apache
- Check browser console for errors

## License

This project is created for educational purposes.

## Author

Created for college project submission.

---

**Note**: Remember to change default admin password in production!

