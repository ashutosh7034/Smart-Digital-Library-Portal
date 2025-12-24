<?php
/**
 * User Dashboard
 * Book browsing interface for regular users
 */

require_once __DIR__ . '/../config/config.php';
requireLogin(); // Ensure user is logged in

$page_title = 'User Dashboard';
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
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="../logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Browse Library Books</h2>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="🔍 Search by title, author, category..." onkeyup="filterBooks()">
            </div>
        </div>

        <!-- Books Grid -->
        <div class="books-grid" id="books-grid">
            <div class="loading">Loading books...</div>
        </div>
    </div>

    <!-- Book Details Modal -->
    <div id="book-details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="details-title">Book Details</h3>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="book-details-content">
                <!-- Book details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/user.js"></script>
</body>
</html>

