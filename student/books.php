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

// Check for pending fines (for sticky banner)
$pendingFinesQuery = "SELECT SUM(amount) as total FROM fines WHERE user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($pendingFinesQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$pendingFines = $result->fetch_assoc()['total'] ?? 0;

// Sticky banner for unpaid fines
if ($pendingFines > 0): ?>
    <div class="fine-banner sticky-banner">
        <strong>⚠️ Pay Your Fines:</strong> You have pending fines of 
        <strong>PKR <?php echo number_format($pendingFines, 2); ?></strong>.
        <a href="fines.php" class="btn btn-sm btn-warning ml-3">
            <i class="fas fa-credit-card"></i> Pay Now
        </a>
    </div>

    <style>
        .fine-banner {
            background-color: #ffe0b2;
            color: #bf360c;
            padding: 12px 20px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
            border-bottom: 2px solid #ff9800;
            z-index: 9999;
        }
        .sticky-banner {
            position: sticky;
            top: 0;
        }
    </style>
<?php endif;

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// Get all categories
$categories = [];
$result = $conn->query("SELECT DISTINCT category FROM books WHERE category != '' ORDER BY category");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Build search query
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR author LIKE ? OR book_no LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($category)) {
    $sql .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$sql .= " ORDER BY title";

// Execute search
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$books = [];
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
?>

<div class="container">
    <h1 class="page-title">Library Books</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="search-section mb-4">
        <form action="" method="GET" class="search-form">
            <div class="search-row">
                <div class="search-input-group">
                    <input type="text" name="search" placeholder="Search books by title, author, or book number..." 
                           class="form-control search-input" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="search-select-group">
                    <select name="category" class="form-control category-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-button-group">
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search) || !empty($category)): ?>
                        <a href="books.php" class="btn btn-secondary clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="books-grid">
        <?php if (count($books) > 0): ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-cover">
                        <i class="fas fa-book fa-3x"></i>
                    </div>
                    <div class="book-info">
                        <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p class="book-author">By <?php echo htmlspecialchars($book['author']); ?></p>
                        <div class="book-details">
                            <span><?php echo htmlspecialchars($book['category']); ?></span>
                            <span>Book No: <?php echo htmlspecialchars($book['book_no']); ?></span>
                            <span>
                                <?php echo $book['available_quantity']; ?> / <?php echo $book['total_quantity']; ?> available
                            </span>
                        </div>
                    </div>
                    <div class="book-actions">
                        <?php if ($pendingFines > 0): ?>
                            <button class="btn btn-secondary btn-sm" disabled title="Pay pending fines to request books">
                                <i class="fas fa-ban"></i> Cannot Request
                            </button>
                        <?php elseif ($book['available_quantity'] > 0): ?>
                            <button class="btn btn-primary btn-sm" data-modal-target="requestModal<?php echo $book['id']; ?>">
                                <i class="fas fa-book"></i> Request Book
                            </button>
                        <?php else: ?>
                            <button class="btn btn-warning btn-sm" data-modal-target="reservationModal<?php echo $book['id']; ?>">
                                <i class="fas fa-bookmark"></i> Reserve Book
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Book Request Modal (for available books) -->
                    <?php if ($pendingFines == 0 && $book['available_quantity'] > 0): ?>
                    <div class="modal-overlay" id="requestModal<?php echo $book['id']; ?>">
                        <div class="modal">
                            <div class="modal-header">
                                <h3 class="modal-title">Request Book</h3>
                                <button class="modal-close">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="book-request-info">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="text-muted">Book No: <?php echo htmlspecialchars($book['book_no']); ?></p>
                                    <div class="availability-info">
                                        <span class="badge badge-success">
                                            <?php echo $book['available_quantity']; ?> copies available
                                        </span>
                                    </div>
                                </div>
                                
                                <form action="" method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="notes">Additional Notes (Optional)</label>
                                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                                        <button type="submit" name="request_book" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Submit Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Reservation Request Modal (for unavailable books) -->
                    <?php if ($pendingFines == 0 && $book['available_quantity'] == 0): ?>
                    <div class="modal-overlay" id="reservationModal<?php echo $book['id']; ?>">
                        <div class="modal">
                            <div class="modal-header">
                                <h3 class="modal-title">Reserve Book</h3>
                                <button class="modal-close">&times;</button>
                            </div>
                            <div class="modal-body">
                                <div class="book-request-info">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="text-muted">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <p class="text-muted">Book No: <?php echo htmlspecialchars($book['book_no']); ?></p>
                                    <div class="availability-info">
                                        <span class="badge badge-danger">Currently unavailable</span>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Auto-Issue Feature:</strong> When this book becomes available, it will be automatically issued to you! You'll receive a notification to collect it from the library.
                                </div>
                                
                                <form action="" method="POST">
                                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="reservation_notes">Additional Notes (Optional)</label>
                                        <textarea id="reservation_notes" name="notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                                    </div>
                                    
                                    <div class="form-group text-right">
                                        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                                        <button type="submit" name="request_reservation" class="btn btn-warning">
                                            <i class="fas fa-bookmark"></i> Submit Reservation Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                <h3>No Books Found</h3>
                <p class="text-muted">
                    <?php if (!empty($search) || !empty($category)): ?>
                        No books match your search criteria. Try adjusting your search terms.
                    <?php else: ?>
                        No books are currently available in the library.
                    <?php endif; ?>
                </p>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="books.php" class="btn btn-primary">
                        <i class="fas fa-list"></i> View All Books
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.fine-warning {
    border-left: 4px solid #ff9800;
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    border-radius: 10px;
    margin-bottom: 30px;
}

.fine-warning-content {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 5px 0;
}

.fine-warning-content i {
    font-size: 2em;
    color: #ff9800;
    flex-shrink: 0;
}

.fine-warning-text {
    flex: 1;
}

.fine-warning-text p {
    margin: 5px 0 0 0;
    color: #e65100;
}

.search-section {
    background: var(--white);
    padding: 20px;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 30px;
}

.search-form {
    width: 100%;
}

.search-row {
    display: flex;
    gap: 15px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.search-input-group {
    flex: 2;
    min-width: 250px;
}

.search-select-group {
    flex: 1;
    min-width: 200px;
}

.search-button-group {
    display: flex;
    gap: 10px;
}

.search-input, .category-select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 1em;
    transition: var(--transition);
}

.search-input:focus, .category-select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1);
}

.search-btn, .clear-btn {
    padding: 12px 20px;
    white-space: nowrap;
    font-weight: 500;
}

.clear-btn {
    background-color: var(--gray-400);
    color: var(--white);
}

.clear-btn:hover {
    background-color: var(--gray-500);
}

.no-results {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
}

.no-results h3 {
    color: var(--text-color);
    margin-bottom: 15px;
}

.book-request-info {
    margin-bottom: 20px;
    padding: 15px;
    background: var(--gray-100);
    border-radius: var(--border-radius);
    text-align: center;
}

.book-request-info h4 {
    margin: 0 0 5px 0;
    color: var(--primary-color);
    font-size: 1.2em;
}

.book-request-info .text-muted {
    margin-bottom: 10px;
}

.availability-info {
    margin-top: 10px;
}

@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input-group,
    .search-select-group {
        flex: none;
        min-width: auto;
    }
    
    .search-button-group {
        justify-content: center;
    }
    
    .search-btn, .clear-btn {
        flex: 1;
        max-width: 150px;
    }
    
    .fine-warning-content {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}
</style>

<?php include_once '../includes/footer.php'; ?>