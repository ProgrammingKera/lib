<?php
include_once '../includes/config.php';

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

// Get all categories with book counts
$categories = [];
$sql = "
    SELECT 
        category,
        COUNT(*) as book_count,
        SUM(available_quantity) as available_books,
        SUM(total_quantity) as total_books
    FROM books 
    WHERE category != '' AND category IS NOT NULL
    GROUP BY category 
    ORDER BY category
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Get total library stats
$totalBooksQuery = "SELECT COUNT(*) as total FROM books";
$totalResult = $conn->query($totalBooksQuery);
$totalBooks = $totalResult->fetch_assoc()['total'];

$availableBooksQuery = "SELECT SUM(available_quantity) as available FROM books";
$availableResult = $conn->query($availableBooksQuery);
$availableBooks = $availableResult->fetch_assoc()['available'];
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

        .dashboard-btn {
            background: var(--primary-color);
            color: white;
        }

        .dashboard-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 94, 60, 0.3);
        }

        .logout-btn {
            background: var(--danger-color);
            color: white;
        }

        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        /* Main Content */
        .catalog-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: center;
            margin-bottom: 50px;
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            font-size: 3em;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .welcome-section p {
            font-size: 1.2em;
            color: var(--text-light);
            margin-bottom: 30px;
        }

        /* Library Stats */
        .library-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-light);
            font-weight: 500;
        }

        /* Bookshelf Layout */
        .library-shelves {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .shelves-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .shelves-header h2 {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .shelves-header p {
            font-size: 1.1em;
            color: var(--text-light);
        }

        .bookshelf-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .bookshelf-section {
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 
                0 8px 25px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .bookshelf-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .bookshelf-section:hover::before {
            transform: scaleX(1);
        }

        .bookshelf-section:hover {
            transform: translateY(-8px);
            box-shadow: 
                0 15px 40px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
        }

        .shelf-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .shelf-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 1.5em;
        }

        .shelf-title {
            flex: 1;
        }

        .shelf-title h3 {
            font-size: 1.4em;
            color: var(--primary-color);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .shelf-subtitle {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .shelf-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }

        .shelf-stat {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .shelf-stat:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.05);
        }

        .shelf-stat-number {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .shelf-stat-label {
            font-size: 0.8em;
            color: var(--text-light);
            font-weight: 500;
        }

        .browse-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1em;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .browse-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 94, 60, 0.3);
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
            }

            .welcome-section h1 {
                font-size: 2.5em;
            }

            .welcome-section {
                padding: 30px 20px;
            }

            .library-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .bookshelf-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .shelf-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .catalog-container {
                padding: 20px 15px;
            }

            .library-shelves {
                padding: 25px 20px;
            }
        }

        @media (max-width: 480px) {
            .welcome-section h1 {
                font-size: 2em;
            }

            .library-stats {
                grid-template-columns: 1fr;
            }

            .stat-number {
                font-size: 2em;
            }

            .shelves-header h2 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="catalog-navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-brand">
                <img src="../uploads/assests/library-logo.png" alt="Library Logo">
                <h1>Book Bridge</h1>
            </a>
            
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
            <h1>Welcome to Our Digital Library</h1>
            <p>Discover thousands of books across various categories</p>
            
            <!-- Library Stats -->
            <div class="library-stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $totalBooks; ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $availableBooks; ?></div>
                    <div class="stat-label">Available Now</div>
                </div>
            </div>
        </div>

        <!-- Library Shelves -->
        <div class="library-shelves">
            <div class="shelves-header">
                <h2>Browse by Category</h2>
                <p>Click on any category to explore our collection</p>
            </div>

            <div class="bookshelf-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="bookshelf-section" onclick="window.location.href='category_books.php?category=<?php echo urlencode($category['category']); ?>'">
                        <div class="shelf-header">
                            <div class="shelf-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="shelf-title">
                                <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                                <div class="shelf-subtitle">Explore this collection</div>
                            </div>
                        </div>

                        <div class="shelf-stats">
                            <div class="shelf-stat">
                                <div class="shelf-stat-number"><?php echo $category['book_count']; ?></div>
                                <div class="shelf-stat-label">Titles</div>
                            </div>
                            <div class="shelf-stat">
                                <div class="shelf-stat-number"><?php echo $category['total_books']; ?></div>
                                <div class="shelf-stat-label">Total Copies</div>
                            </div>
                            <div class="shelf-stat">
                                <div class="shelf-stat-number"><?php echo $category['available_books']; ?></div>
                                <div class="shelf-stat-label">Available</div>
                            </div>
                        </div>

                        <button class="browse-btn">
                            <i class="fas fa-arrow-right"></i>
                            Browse Books
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>