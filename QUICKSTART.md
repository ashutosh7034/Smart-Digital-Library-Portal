# Quick Start Guide

## Step 1: Database Setup

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "Import" tab
   - Choose `database.sql` file
   - Click "Go" to import

   OR manually:
   - Click "New" to create database
   - Name it: `digital_library`
   - Click "Import" and select `database.sql`

## Step 2: Configure Database (if needed)

Edit `config/database.php` if your MySQL has a password:
```php
define('DB_PASS', 'your_password_here');
```

## Step 3: Fix Admin Password (IMPORTANT!)

**If you get "Invalid username or password" error:**

1. Open browser: http://localhost/Smart-Digital-Library-Portal-1/fix_admin_password.php
2. This will automatically set admin password to `admin123`
3. **IMPORTANT**: Delete `fix_admin_password.php` file after running!

**OR use setup script for custom password:**

1. Open browser: http://localhost/Smart-Digital-Library-Portal-1/setup_admin.php
2. Enter new password (minimum 6 characters)
3. Click "Update Admin Password"
4. **IMPORTANT**: Delete `setup_admin.php` file after setup!

## Step 4: Access Application

Open: http://localhost/Smart-Digital-Library-Portal-1

### Default Login (after setup):
- **Username**: admin
- **Password**: (the one you set in Step 3)

## Step 5: Test Features

### As Admin:
1. Login with admin credentials
2. Add a new book
3. Edit an existing book
4. Delete a book
5. View all books

### As User:
1. Click "Register here" on login page
2. Create a new account
3. Browse books
4. Search for books
5. View book details

## Troubleshooting

### "Connection failed" error
- Check if MySQL is running in XAMPP
- Verify database name is `digital_library`
- Check database credentials in `config/database.php`

### "Access denied" error
- Make sure you're accessing via http://localhost/
- Check file permissions
- Ensure Apache is running

### Admin password not working
- Run `setup_admin.php` again
- Make sure you're using the correct username: `admin`

## Project Structure Overview

```
├── config/          → Configuration files
├── api/             → REST API endpoints
├── auth/            → Authentication pages
├── admin/           → Admin dashboard
├── user/            → User dashboard
├── assets/          → CSS and JavaScript
├── index.php        → Main entry point
├── login.php        → Login page
└── database.sql     → Database schema
```

## API Testing

You can test the API using browser console or Postman:

```javascript
// Get all books
fetch('api/books.php')
  .then(r => r.json())
  .then(console.log);

// Get single book
fetch('api/books.php?id=1')
  .then(r => r.json())
  .then(console.log);
```

## Next Steps

1. ✅ Database imported
2. ✅ Admin password set
3. ✅ Application accessible
4. ✅ Test admin features
5. ✅ Test user features
6. ✅ Ready for submission!

---

**Need Help?** Check the main README.md for detailed documentation.

