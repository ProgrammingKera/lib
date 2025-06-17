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
        COUNT(*) as book_count
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

        /* Library Shelves */
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

        /* Category Grid - Door Style */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .category-door {
            background: linear-gradient(145deg, #8B5E3C, #7C4A2D);
            border-radius: 15px;
            padding: 0;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.2),
                inset 0 2px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            height: 200px;
            border: 3px solid #5A3620;
        }

        .category-door::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            pointer-events: none;
        }

        .category-door:hover {
            transform: perspective(1000px) rotateY(-15deg) scale(1.05);
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.3),
                inset 0 2px 0 rgba(255, 255, 255, 0.2);
        }

        .door-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: white;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .door-icon {
            font-size: 3em;
            margin-bottom: 15px;
            color: #F9F5F0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .door-title {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .door-subtitle {
            font-size: 0.9em;
            opacity: 0.9;
            font-weight: 500;
        }

        .door-handle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 12px;
            height: 12px;
            background: #C97B4A;
            border-radius: 50%;
            box-shadow: 
                0 2px 4px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .category-door:hover .door-handle {
            background: #E68A2E;
            transform: translateY(-50%) scale(1.2);
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

            .category-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
            }

            .category-door {
                height: 180px;
            }

            .door-icon {
                font-size: 2.5em;
            }

            .door-title {
                font-size: 1.1em;
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

            .category-grid {
                grid-template-columns: 1fr;
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
        </div>

        <!-- Library Shelves -->
        <div class="library-shelves">
            <div class="shelves-header">
                <h2>Browse by Category</h2>
                <p>Click on any door to explore our collection</p>
            </div>

            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-door" onclick="window.location.href='category_books.php?category=<?php echo urlencode($category['category']); ?>'">
                        <div class="door-content">
                            <div class="door-icon">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="door-title"><?php echo htmlspecialchars($category['category']); ?></div>
                            <div class="door-subtitle"><?php echo $category['book_count']; ?> books</div>
                        </div>
                        <div class="door-handle"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>