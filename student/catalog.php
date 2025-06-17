<?php
include_once '../includes/header.php';

// Check if user is student or faculty
if ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'faculty') {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

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

// Get all categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM books WHERE category != '' ORDER BY category");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Get books by category
$booksByCategory = [];
foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE category = ? ORDER BY title");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    $books = [];
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
    $booksByCategory[$category] = $books;
}

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchResults = [];
if (!empty($search)) {
    $sql = "SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR book_no LIKE ? ORDER BY title";
    $searchParam = "%$search%";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Catalog - Book Bridge</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" type="image/svg+xml" href="../uploads/assests/book.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
  margin: 0;
  padding: 0;
  background: var(--gray-100);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: var(--text-color);
  min-height: 100vh;
}

/* Navigation Bar */
.catalog-navbar {
  background: var(--white);
  padding: 15px 0;
  box-shadow: var(--box-shadow);
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

.navbar-search {
  flex: 1;
  max-width: 500px;
  margin: 0 30px;
}

.search-form {
  display: flex;
  background: var(--white);
  border-radius: 30px;
  overflow: hidden;
  box-shadow: var(--box-shadow);
}

.search-input {
  flex: 1;
  padding: 10px 20px;
  border: none;
  outline: none;
}

.search-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 10px 20px;
  cursor: pointer;
  transition: var(--transition);
}

.search-btn:hover {
  background: var(--primary-dark);
}

.navbar-actions {
  display: flex;
  gap: 10px;
}

.nav-btn {
  padding: 8px 16px;
  border-radius: 20px;
  font-weight: 600;
  text-decoration: none;
  transition: var(--transition);
}

.dashboard-btn {
  background: var(--primary-color);
  color: white;
}

.dashboard-btn:hover {
  background: var(--primary-dark);
}

.logout-btn {
  background: var(--danger-color);
  color: white;
}

.logout-btn:hover {
  background: var(--primary-light);
}

/* Welcome */
.welcome-section {
  text-align: center;
  padding: 40px 20px;
  background: var(--secondary-color);
  border-radius: var(--border-radius);
  margin: 30px auto;
  max-width: 1000px;
}

.welcome-section h2 {
  font-size: 2.2em;
  margin-bottom: 10px;
}

.welcome-section p {
  font-size: 1.1em;
  color: var(--text-light);
}

/* Fine Warning */
.fine-banner {
  background: var(--warning-color);
  color: white;
  padding: 12px 20px;
  text-align: center;
  border-radius: var(--border-radius);
  margin-bottom: 20px;
}

/* Search Results */
.search-results {
  background: var(--white);
  border-radius: var(--border-radius);
  padding: 25px;
  box-shadow: var(--box-shadow);
  margin-bottom: 30px;
}

.search-results h3 {
  color: var(--primary-color);
  margin-bottom: 15px;
}

/* Category Shelves */
.library-shelves {
  display: grid;
  gap: 20px;
}

.bookshelf {
  background: var(--gray-200);
  border-radius: var(--border-radius);
  box-shadow: var(--box-shadow);
  transition: var(--transition);
}

.bookshelf:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.shelf-header {
  padding: 15px 20px;
  background: var(--primary-light);
  color: white;
  font-weight: 600;
  font-size: 1.2em;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.shelf-content {
  background: var(--white);
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.5s ease-in-out;
}

.shelf-content.expanded {
  max-height: 2000px;
}

.books-display {
  padding: 20px;
}

/* View toggle */
.view-toggle {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-bottom: 15px;
}

.view-btn {
  padding: 6px 12px;
  border: 1px solid var(--primary-color);
  background: transparent;
  border-radius: 6px;
  color: var(--primary-color);
  cursor: pointer;
  transition: var(--transition);
}

.view-btn:hover,
.view-btn.active {
  background: var(--primary-color);
  color: white;
}

        /* Book Grid View */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .book-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: var(--transition);
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .book-cover {
            height: 200px;
            background: linear-gradient(135deg, var(--gray-200), var(--gray-300));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-light);
            font-size: 3em;
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
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.9em;
        }

        .btn-request {
            background: var(--primary-color);
            color: white;
        }

        .btn-request:hover {
            background: var(--primary-dark);
        }

        .btn-reserve {
            background: var(--warning-color);
            color: white;
        }

        .btn-reserve:hover {
            background: #e68900;
        }

        .btn-disabled {
            background: var(--gray-400);
            color: var(--gray-600);
            cursor: not-allowed;
        }

        /* Book Table View */
        .books-table {
            display: none;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .books-table.active {
            display: block;
        }

        .books-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table th {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .books-table td {
            padding: 15px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .books-table tr:hover {
            background: var(--gray-100);
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
            transition: var(--transition);
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
            transition: var(--transition);
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
            transition: var(--transition);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 15px;
            }

            .navbar-search {
                margin: 0;
                max-width: 100%;
            }

            .navbar-actions {
                width: 100%;
                justify-content: center;
            }

            .welcome-section h2 {
                font-size: 2em;
            }

            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }

            .shelf-header {
                padding: 15px 20px;
            }

            .books-display {
                padding: 20px;
            }

            .modal {
                margin: 10px;
            }

            .modal-header,
            .modal-body {
                padding: 20px;
            }
        }

        /* Alert Styles */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(107, 142, 35, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(107, 142, 35, 0.3);
        }

        .alert-warning {
            background: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(255, 152, 0, 0.3);
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="catalog-navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-brand">
                <img src="../uploads/assests/library-logo.png" alt="Library Logo">
                
            </a>
            
            <div class="navbar-search">
                <form class="search-form" method="GET">
                    <input type="text" name="search" class="search-input" 
                           placeholder="Search books by title, author, or book number..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="navbar-actions">
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
    <div class="catalog-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome to Our Digital Library</h2>
            <p>Discover thousands of books across various categories</p>
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

        <!-- Search Results -->
        <?php if (!empty($search)): ?>
            <div class="search-results">
                <h3><i class="fas fa-search"></i> Search Results for "<?php echo htmlspecialchars($search); ?>"</h3>
                <?php if (count($searchResults) > 0): ?>
                    <div class="books-grid">
                        <?php foreach ($searchResults as $book): ?>
                            <div class="book-card">
                                <div class="book-cover">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="book-info">
                                    <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-details">
                                        <div class="book-status">
                                            <span class="status-indicator <?php echo $book['available_quantity'] > 0 ? 'status-available' : 'status-unavailable'; ?>"></span>
                                            <span><?php echo $book['available_quantity']; ?> / <?php echo $book['total_quantity']; ?> available</span>
                                        </div>
                                        <span><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></span>
                                        <?php if (!empty($book['book_no'])): ?>
                                            <span><strong>Book No:</strong> <?php echo htmlspecialchars($book['book_no']); ?></span>
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
                <?php else: ?>
                    <p>No books found matching your search criteria.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Library Shelves -->
        <div class="library-shelves">
            <?php foreach ($categories as $category): ?>
                <?php $books = $booksByCategory[$category]; ?>
                <div class="bookshelf">
                    <div class="shelf-header" onclick="toggleShelf('<?php echo $category; ?>')">
                        <h3 class="shelf-title">
                            <i class="fas fa-book-open"></i>
                            <?php echo htmlspecialchars($category); ?>
                        </h3>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="shelf-count"><?php echo count($books); ?> books</span>
                            <i class="fas fa-chevron-down shelf-toggle" id="toggle-<?php echo $category; ?>"></i>
                        </div>
                    </div>
                    <div class="shelf-content" id="content-<?php echo $category; ?>">
                        <div class="books-display">
                            <div class="view-toggle">
                                <button class="view-btn active" onclick="switchView('<?php echo $category; ?>', 'grid')">
                                    <i class="fas fa-th"></i> Grid
                                </button>
                                <button class="view-btn" onclick="switchView('<?php echo $category; ?>', 'table')">
                                    <i class="fas fa-list"></i> Table
                                </button>
                            </div>
                            
                            <!-- Grid View -->
                            <div class="books-grid" id="grid-<?php echo $category; ?>">
                                <?php foreach ($books as $book): ?>
                                    <div class="book-card">
                                        <div class="book-cover">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div class="book-info">
                                            <h4 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h4>
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
                            
                            <!-- Table View -->
                            <div class="books-table" id="table-<?php echo $category; ?>">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Book No</th>
                                            <th>Publisher</th>
                                            <th>Availability</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($books as $book): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($book['author']); ?></td>
                                                <td><?php echo htmlspecialchars($book['book_no']); ?></td>
                                                <td><?php echo htmlspecialchars($book['publisher']); ?></td>
                                                <td>
                                                    <div class="book-status">
                                                        <span class="status-indicator <?php echo $book['available_quantity'] > 0 ? 'status-available' : 'status-unavailable'; ?>"></span>
                                                        <span><?php echo $book['available_quantity']; ?> / <?php echo $book['total_quantity']; ?></span>
                                                    </div>
                                                </td>
                                                <td>
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
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
                
                <div class="alert alert-info">
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
        // Toggle shelf content
        function toggleShelf(category) {
            const content = document.getElementById('content-' + category);
            const toggle = document.getElementById('toggle-' + category);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.style.transform = 'rotate(0deg)';
            } else {
                content.classList.add('expanded');
                toggle.style.transform = 'rotate(180deg)';
            }
        }

        // Switch between grid and table view
        function switchView(category, view) {
            const gridView = document.getElementById('grid-' + category);
            const tableView = document.getElementById('table-' + category);
            const buttons = document.querySelectorAll(`#content-${category} .view-btn`);
            
            buttons.forEach(btn => btn.classList.remove('active'));
            
            if (view === 'grid') {
                gridView.style.display = 'grid';
                tableView.classList.remove('active');
                buttons[0].classList.add('active');
            } else {
                gridView.style.display = 'none';
                tableView.classList.add('active');
                buttons[1].classList.add('active');
            }
        }

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

        // By default, show table view first instead of grid view
        document.addEventListener('DOMContentLoaded', function() {
            // For each shelf, switch to table view by default
            document.querySelectorAll('.library-shelves .bookshelf').forEach(function(shelf) {
                const category = shelf.querySelector('.shelf-header').getAttribute('onclick').match(/toggleShelf\('(.+)'\)/)[1];
                switchView(category, 'table');
            });
        });
    </script>
</body>
</html>