<?php
// Include header
include_once '../includes/header.php';

// Check if user is a librarian
checkUserRole('librarian');

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Process weed-off operations
$message = '';
$messageType = '';

if (isset($_POST['weed_off_books'])) {
    $bookIds = isset($_POST['book_ids']) ? $_POST['book_ids'] : [];
    $reason = trim($_POST['reason']);

    if (empty($bookIds)) {
        $message = "Please select at least one book to weed off.";
        $messageType = "danger";
    } elseif (empty($reason)) {
        $message = "Please provide a reason for weeding off the books.";
        $messageType = "danger";
    } else {
        $conn->begin_transaction();

        try {
            foreach ($bookIds as $bookId) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM issued_books WHERE book_id = ? AND (status = 'issued' OR status = 'overdue')");
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();

                if ($row['count'] > 0) {
                    throw new Exception("Cannot remove book ID $bookId. It is currently issued to users.");
                }

                $stmt = $conn->prepare("SELECT title FROM books WHERE id = ?");
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
                $book = $stmt->get_result()->fetch_assoc();

                $stmt = $conn->prepare("INSERT INTO weed_off_history (book_id, book_title, reason, removed_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $bookId, $book['title'], $reason, $_SESSION['user_id']);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
                $stmt->bind_param("i", $bookId);
                $stmt->execute();
            }

            $conn->commit();
            $message = count($bookIds) . " book(s) have been successfully removed.";
            $messageType = "success";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Get total books count
$totalResult = $conn->query("SELECT COUNT(DISTINCT b.id) as total FROM books b LEFT JOIN issued_books ib ON b.id = ib.book_id GROUP BY b.id HAVING COUNT(DISTINCT ib.id) = 0 OR MAX(ib.issue_date) < DATE_SUB(NOW(), INTERVAL 2 YEAR)");
$totalBooks = $totalResult ? $totalResult->num_rows : 0;
$totalPages = ceil($totalBooks / $limit);

// Get books for current page
$sql = "SELECT b.*, COUNT(DISTINCT ib.id) as times_issued, MAX(ib.issue_date) as last_issued FROM books b LEFT JOIN issued_books ib ON b.id = ib.book_id GROUP BY b.id HAVING times_issued = 0 OR last_issued < DATE_SUB(NOW(), INTERVAL 2 YEAR) ORDER BY last_issued ASC, times_issued ASC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
$books = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

$conn->query("CREATE TABLE IF NOT EXISTS weed_off_history (id INT AUTO_INCREMENT PRIMARY KEY, book_id INT, book_title VARCHAR(255), reason TEXT, removed_by INT, removed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (removed_by) REFERENCES users(id))");
?>

<link rel="stylesheet" href="styles.css">

<h1 class="page-title">Weed-Off Books</h1>
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>"> <?php echo $message; ?> </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h3>Books for Consideration</h3>
        <ul class="text-muted">
            <li>Never been issued</li>
            <li>Not issued in last 2 years</li>
        </ul>
    </div>
    <div class="card-body">
        <form action="" method="POST" onsubmit="return confirmWeedOff()">
            <div class="table-container">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Times Issued</th>
                            <th>Last Issued</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($books) > 0): ?>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><input type="checkbox" name="book_ids[]" value="<?php echo $book['id']; ?>" class="book-checkbox"></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo $book['times_issued']; ?></td>
                                    <td><?php echo $book['last_issued'] ? date('M d, Y', strtotime($book['last_issued'])) : 'Never'; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($book['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center">No books found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (count($books) > 0): ?>
                <div class="form-group mt-4">
                    <label for="reason">Reason for Removal *</label>
                    <textarea id="reason" name="reason" class="form-control" rows="3" required></textarea>
                    <small class="text-muted">Please provide a detailed reason.</small>
                </div>
                <div class="form-group text-right">
                    <button type="submit" name="weed_off_books" class="btn btn-danger"><i class="fas fa-trash"></i> Remove Selected Books</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="pagination-nav">
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li><a href="?page=<?php echo $page - 1; ?>">« Prev</a></li>
        <?php endif; ?>

        <?php
        $range = 2;
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);

        if ($start > 1) echo '<li><a href="?page=1">1</a></li><li class="dots">...</li>';
        for ($i = $start; $i <= $end; $i++):
            $active = $i == $page ? 'active' : '';
        ?>
            <li class="<?php echo $active; ?>"><a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
        <?php endfor;
        if ($end < $totalPages) echo '<li class="dots">...</li><li><a href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
        ?>

        <?php if ($page < $totalPages): ?>
            <li><a href="?page=<?php echo $page + 1; ?>">Next »</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3>Weed-Off History</h3></div>
    <div class="card-body">
        <?php
        $sql = "SELECT wh.*, u.name as librarian_name FROM weed_off_history wh JOIN users u ON wh.removed_by = u.id ORDER BY wh.removed_at DESC";
        $result = $conn->query($sql);
        ?>
        <div class="table-container">
            <table class="table table-striped">
                <thead>
                    <tr><th>Book Title</th><th>Reason</th><th>Removed By</th><th>Removed On</th></tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td><?php echo htmlspecialchars($row['librarian_name']); ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($row['removed_at'])); ?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="4" class="text-center">No weed-off history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.getElementsByClassName('book-checkbox');
    for (let checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
});

function confirmWeedOff() {
    const checkboxes = document.getElementsByClassName('book-checkbox');
    let selectedCount = 0;
    for (let checkbox of checkboxes) {
        if (checkbox.checked) selectedCount++;
    }
    if (selectedCount === 0) {
        alert('Please select at least one book.');
        return false;
    }
    return confirm(`Remove ${selectedCount} book(s)? This can't be undone.`);
}
</script>

<style>
.pagination-nav {
  text-align: center;
  margin-top: 20px;
}
.pagination {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 0;
  list-style: none;
  justify-content: center;
}
.pagination li {
  display: inline;
}
.pagination a {
  display: block;
  padding: 6px 12px;
  border: 1px solid var(--primary-dark);
  border-radius: 8px;
  text-decoration: none;
  color: var(--primary-dark);
  transition: 0.2s ease;
}
.pagination a:hover {
  background: var(--primary-dark);
  color: white;
}
.pagination .active a {
  background: var(--primary-dark);
  color: white;
  font-weight: bold;
}
.pagination .dots {
  display: inline-block;
  padding: 6px 12px;
  color: var(--primary-dark);
}
</style>

<?php include_once '../includes/footer.php'; ?>
