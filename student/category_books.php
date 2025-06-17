<?php
include_once '../includes/config.php';
include_once '../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user is student or faculty
if ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get category from URL
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
if (empty($category)) {
    header('Location: catalog.php');
    exit();
}

// Handle book request submission
if (isset($_POST['request_book'])) {
    $bookId = (int)$_POST['book_id'];
    $notes = trim($_POST['notes']);
    
    // Check if user already has a pending request for this book
    $stmt = $conn->prepare("
        SELECT id FROM book_requests 
        WHERE book_id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $bookId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = "You already have a pending request for this book.";
        $messageType = "warning";
    } else {
        // Create book request
        $stmt = $conn->prepare("
            INSERT INTO book_requests (book_id, user_id, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $bookId, $userId, $notes);
        
        if ($stmt->execute()) {
            $message = "Book request submitted successfully!";
            $messageType = "success";
        } else {
            $message = "Error submitting request: " . $stmt->error;
            $messageType = "danger";
        }
    }
}

// Handle reservation request submission
if (isset($_POST['request_reservation'])) {
    $bookId = (int)$_POST['book_id'];
    $notes = trim($_POST['notes']);
    
    $result = createReservationRequest($conn, $bookId, $userId, $notes);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'danger';
}

// Check for pending fines
$pendingFinesQuery = "SELECT SUM(amount) as total FROM fines WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pendingFinesQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$pendingFines = $result->fetch_assoc()['total'] ?? 0;

// Pagination settings
$booksPerPage = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $booksPerPage;

// Get total count of books in this category
$countSql = "SELECT COUNT(*) as total FROM books WHERE category = ?";
$stmt = $conn->prepare($countSql);
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();
$totalBooks = $result->fetch_assoc()['total'];
$totalPages = ceil($totalBooks / $booksPerPage);

// Get books for current page
$sql = "
    SELECT * FROM books 
    WHERE category = ? 
    ORDER BY title 
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $category, $booksPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category); ?> - Library Catalog</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/svg+xml" href="../uploads/assests/book.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: var(--text-color);
        }

        /* Navigation Bar */
        .catalog-navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary-color);
        }

        .navbar-brand img {
            height: 45px;
        }

        .navbar-brand h1 {
            font-size: 1.6em;
            font-weight: bold;
            margin: 0;
        }

        .navbar-actions {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .catalog-btn {
            background: var(--secondary-color);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .catalog-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .dashboard-btn {
            background: var(--primary-color);
            color: white;
        }

        .dashboard-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .logout-btn {
            background: var(--danger-color);
            color: white;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* Main Content */
        .category-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Category Header */
        .category-header {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            text-align: center;
        }

        .category-header h1 {
            font-size: 3em;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .category-stat {
            background: rgba(255, 255, 255, 0.7);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .category-stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .category-stat-label {
            color: var(--text-light);
            font-weight: 500;
        }

        /* Fine Warning */
        .fine-banner {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 30px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.1);
            color: #2e7d32;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.1);
            color: #ef6c00;
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .alert-danger {
            background: rgba(244, 67, 54, 0.1);
            color: #c62828;
            border: 1px solid rgba(244, 67, 54, 0.3);
        }

        /* Books Grid */
        .books-section {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .book-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .book-cover {
            height: 200px;
            background: linear-gradient(135deg, var(--gray-200), var(--gray-300));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 3em;
            position: relative;
            overflow: hidden;
        }

        .book-cover::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .book-card:hover .book-cover::before {
            transform: translateX(100%);
        }

        .book-info {
            padding: 20px;
        }

        .book-title {
            font-size: 1.1em;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: var(--primary-color);
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            color: var(--text-light);
            margin: 0 0 10px 0;
            font-style: italic;
        }

        .book-details {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .book-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .status-available {
            background: var(--success-color);
        }

        .status-unavailable {
            background: var(--danger-color);
        }

        .book-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-request {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }

        .btn-request:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 94, 60, 0.3);
        }

        .btn-reserve {
            background: linear-gradient(135deg, var(--warning-color), #f57c00);
            color: white;
        }

        .btn-reserve:hover {
            background: linear-gradient(135deg, #f57c00, var(--warning-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        }

        .btn-disabled {
            background: var(--gray-400);
            color: var(--gray-600);
            cursor: not-allowed;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
        }

        .pagination-btn {
            padding: 10px 15px;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 45px;
            text-align: center;
        }

        .pagination-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .pagination-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            color: var(--text-light);
            font-weight: 500;
            margin: 0 15px;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            margin: 0;
            font-size: 1.3em;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 30px;
        }

        .book-request-info {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .book-request-info h4 {
            color: var(--primary-color);
            margin: 0 0 10px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 94, 60, 0.1);
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: var(--gray-300);
            color: var(--text-color);
        }

        .btn-cancel:hover {
            background: var(--gray-400);
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            margin-bottom: 15px;
            color: var(--text-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 15px;
            }

            .navbar-actions {
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }

            .category-header h1 {
                font-size: 2.5em;
            }

            .category-header {
                padding: 30px 20px;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .books-section {
                padding: 25px 20px;
            }

            .category-container {
                padding: 20px 15px;
            }

            .pagination-container {
                flex-wrap: wrap;
                gap: 5px;
            }

            .pagination-info {
                width: 100%;
                text-align: center;
                margin: 10px 0;
            }
        }

        @media (max-width: 480px) {
            .category-header h1 {
                font-size: 2em;
            }

            .books-grid {
                grid-template-columns: 1fr;
            }

            .modal {
                margin: 10px;
            }

            .modal-header,
            .modal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="catalog-navbar">
        <div class="navbar-container">
            <a href="catalog.php" class="navbar-brand">
                <img src="../uploads/assests/library-logo.png" alt="Library Logo">
                <h1>Book Bridge</h1>
            </a>
            
            <div class="navbar-actions">
                <a href="catalog.php" class="nav-btn catalog-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Catalog
                </a>
                <a href="dashboard.php" class="nav-btn dashboard-btn">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="../logout.php" class="nav-btn logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="category-container">
        <!-- Category Header -->
        <div class="category-header">
            <h1><?php echo htmlspecialchars($category); ?></h1>
            <p>Explore our collection of <?php echo htmlspecialchars($category); ?> books</p>
            
            <div class="category-stats">
                <div class="category-stat">
                    <div class="category-stat-number"><?php echo $totalBooks; ?></div>
                    <div class="category-stat-label">Total Books</div>
                </div>
                <div class="category-stat">
                    <div class="category-stat-number"><?php echo count($books); ?></div>
                    <div class="category-stat-label">Showing</div>
                </div>
                <div class="category-stat">
                    <div class="category-stat-number"><?php echo $page; ?>/<?php echo $totalPages; ?></div>
                    <div class="category-stat-label">Page</div>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : ($messageType == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Fine Warning Banner -->
        <?php if ($pendingFines > 0): ?>
            <div class="fine-banner">
                <strong><i class="fas fa-exclamation-triangle"></i> Outstanding Fines:</strong>
                You have pending fines of <strong>PKR <?php echo number_format($pendingFines, 2); ?></strong>.
                <a href="fines.php" style="color: white; text-decoration: underline; margin-left: 10px;">
                    Pay Now
                </a>
            </div>
        <?php endif; ?>

        <!-- Books Section -->
        <div class="books-section">
            <?php if (count($books) > 0): ?>
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                <div class="book-details">
                                    <div class="book-status">
                                        <span class="status-indicator <?php echo $book['available_quantity'] > 0 ? 'status-available' : 'status-unavailable'; ?>"></span>
                                        <span><?php echo $book['available_quantity']; ?> / <?php echo $book['total_quantity']; ?> available</span>
                                    </div>
                                    <?php if (!empty($book['book_no'])): ?>
                                        <span><strong>Book No:</strong> <?php echo htmlspecialchars($book['book_no']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($book['publisher'])): ?>
                                        <span><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="book-actions">
                                    <?php if ($pendingFines > 0): ?>
                                        <button class="action-btn btn-disabled" disabled title="Pay pending fines to request books">
                                            <i class="fas fa-ban"></i> Cannot Request
                                        </button>
                                    <?php elseif ($book['available_quantity'] > 0): ?>
                                        <button class="action-btn btn-request" onclick="openRequestModal(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>', '<?php echo addslashes($book['author']); ?>', '<?php echo addslashes($book['book_no']); ?>', <?php echo $book['available_quantity']; ?>)">
                                            <i class="fas fa-book"></i> Request
                                        </button>
                                    <?php else: ?>
                                        <button class="action-btn btn-reserve" onclick="openReservationModal(<?php echo $book['id']; ?>, '<?php echo addslashes($book['title']); ?>', '<?php echo addslashes($book['author']); ?>', '<?php echo addslashes($book['book_no']); ?>')">
                                            <i class="fas fa-bookmark"></i> Reserve
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-container">
                        <?php if ($page > 1): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=1" class="pagination-btn">1</a>
                            <?php if ($startPage > 2): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $i; ?>" 
                               class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?>
                                <span class="pagination-btn disabled">...</span>
                            <?php endif; ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $totalPages; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?category=<?php echo urlencode($category); ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>

                        <div class="pagination-info">
                            Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $booksPerPage, $totalBooks); ?> of <?php echo $totalBooks; ?> books
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Books Found</h3>
                    <p>There are no books in the <?php echo htmlspecialchars($category); ?> category at the moment.</p>
                    <a href="catalog.php" class="action-btn btn-request" style="margin-top: 20px; display: inline-flex;">
                        <i class="fas fa-arrow-left"></i> Back to Catalog
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Book Request Modal -->
    <div class="modal-overlay" id="requestModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Request Book</h3>
                <button class="modal-close" onclick="closeModal('requestModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="book-request-info" id="requestBookInfo">
                    <!-- Book info will be populated by JavaScript -->
                </div>
                
                <form method="POST">
                    <input type="hidden" name="book_id" id="requestBookId">
                    
                    <div class="form-group">
                        <label for="requestNotes">Additional Notes (Optional)</label>
                        <textarea id="requestNotes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="modal-btn btn-cancel" onclick="closeModal('requestModal')">Cancel</button>
                        <button type="submit" name="request_book" class="modal-btn btn-submit">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reservation Request Modal -->
    <div class="modal-overlay" id="reservationModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Reserve Book</h3>
                <button class="modal-close" onclick="closeModal('reservationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="book-request-info" id="reservationBookInfo">
                    <!-- Book info will be populated by JavaScript -->
                </div>
                
                <div class="alert alert-info" style="margin-bottom: 20px;">
                    <i class="fas fa-magic"></i>
                    <strong>Auto-Issue Feature:</strong> When this book becomes available, it will be automatically issued to you! You'll receive a notification to collect it from the library.
                </div>
                
                <form method="POST">
                    <input type="hidden" name="book_id" id="reservationBookId">
                    
                    <div class="form-group">
                        <label for="reservationNotes">Additional Notes (Optional)</label>
                        <textarea id="reservationNotes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="modal-btn btn-cancel" onclick="closeModal('reservationModal')">Cancel</button>
                        <button type="submit" name="request_reservation" class="modal-btn btn-submit">
                            <i class="fas fa-bookmark"></i> Submit Reservation Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Open request modal
        function openRequestModal(bookId, title, author, bookNo, availableQuantity) {
            document.getElementById('requestBookId').value = bookId;
            document.getElementById('requestBookInfo').innerHTML = `
                <h4>${title}</h4>
                <p style="margin: 5px 0; color: #666;">by ${author}</p>
                <p style="margin: 5px 0; color: #666;">Book No: ${bookNo}</p>
                <div style="margin-top: 10px;">
                    <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">
                        ${availableQuantity} copies available
                    </span>
                </div>
            `;
            document.getElementById('requestModal').classList.add('active');
        }

        // Open reservation modal
        function openReservationModal(bookId, title, author, bookNo) {
            document.getElementById('reservationBookId').value = bookId;
            document.getElementById('reservationBookInfo').innerHTML = `
                <h4>${title}</h4>
                <p style="margin: 5px 0; color: #666;">by ${author}</p>
                <p style="margin: 5px 0; color: #666;">Book No: ${bookNo}</p>
                <div style="margin-top: 10px;">
                    <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">
                        Currently unavailable
                    </span>
                </div>
            `;
            document.getElementById('reservationModal').classList.add('active');
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    modal.classList.remove('active');
                });
            }
        });

        // Smooth scroll to top when pagination is clicked
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>