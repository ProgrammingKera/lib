<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/svg+xml" href="uploads/assests/book.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gallery-page {
            min-height: 100vh;
            background: var(--secondary-color);
            padding: 80px 0 40px;
            margin-top: 50px;
        }

        .gallery-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 0 20px;
        }

        .gallery-header h1 {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 15px;
            font-weight: 700;
        }

        .gallery-header p {
            font-size: 1.1em;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .gallery-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .gallery-filters {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
            font-size: 0.9em;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .gallery-item {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            cursor: pointer;
        }

        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .gallery-image {
            width: 100%;
            height: 250px;
            background: var(--gray-200);
            position: relative;
            overflow: hidden;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }

        .gallery-item:hover .gallery-image img {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(139, 94, 60, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-overlay i {
            color: var(--white);
            font-size: 2em;
        }

        .gallery-info {
            padding: 20px;
        }

        .gallery-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
        }

        .gallery-description {
            color: var(--text-light);
            font-size: 0.9em;
            line-height: 1.5;
        }

        .gallery-category {
            display: inline-block;
            background: var(--primary-color);
            color: var(--white);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
            margin-top: 10px;
        }

        /* Modal for image preview */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .image-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .modal-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: var(--border-radius);
        }

        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: var(--white);
            font-size: 2em;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--primary-color);
            transform: scale(1.1);
        }

        .modal-info {
            position: absolute;
            bottom: -60px;
            left: 0;
            right: 0;
            text-align: center;
            color: var(--white);
        }

        .modal-title {
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .modal-description {
            font-size: 0.9em;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .gallery-header h1 {
                font-size: 2em;
            }

            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .gallery-filters {
                gap: 10px;
            }

            .filter-btn {
                padding: 8px 16px;
                font-size: 0.85em;
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-header h1 {
                font-size: 1.8em;
            }

            .gallery-filters {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="auth-navbar">
        <div class="container">
            <a href="index.php" class="auth-logo">
                <img src="uploads/assests/library-logo.png" alt="Library Logo">
                
            </a>
            <div class="auth-nav-links">
                <a href="gallery.php" class="auth-nav-link">
                    <i class="fas fa-images"></i>
                    <span>Gallery</span>
                </a>
                <a href="about.php" class="auth-nav-link">
                    <i class="fas fa-info-circle"></i>
                    <span>About</span>
                </a>
                <a href="index.php" class="auth-nav-link">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Login</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="gallery-page">
        <div class="gallery-header">
            <h1><i class="fas fa-images"></i> Library Gallery</h1>
            <p>Explore our beautiful library spaces, events, and community moments captured through the lens</p>
        </div>

        <div class="gallery-container">
            <div class="gallery-filters">
                <button class="filter-btn active" data-filter="all">All Images</button>
                <button class="filter-btn" data-filter="library">Library Spaces</button>
                <button class="filter-btn" data-filter="events">Events</button>
                <button class="filter-btn" data-filter="students">Students</button>
                <button class="filter-btn" data-filter="books">Book Collections</button>
            </div>

            <div class="gallery-grid" id="galleryGrid">
                <!-- Library Spaces -->
                <div class="gallery-item" data-category="library">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/159711/books-bookstore-book-reading-159711.jpeg" alt="Main Reading Hall">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Main Reading Hall</h3>
                        <p class="gallery-description">Our spacious main reading hall with comfortable seating and natural lighting, perfect for focused study sessions.</p>
                        <span class="gallery-category">Library Spaces</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="library">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/2041540/pexels-photo-2041540.jpeg" alt="Book Stacks">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Book Stacks Section</h3>
                        <p class="gallery-description">Organized book stacks with thousands of volumes across various subjects and genres.</p>
                        <span class="gallery-category">Library Spaces</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="library">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1319854/pexels-photo-1319854.jpeg" alt="Study Area">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Quiet Study Area</h3>
                        <p class="gallery-description">Dedicated quiet zones for individual study and research work.</p>
                        <span class="gallery-category">Library Spaces</span>
                    </div>
                </div>

                <!-- Events -->
                <div class="gallery-item" data-category="events">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1181406/pexels-photo-1181406.jpeg" alt="Book Fair">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Annual Book Fair</h3>
                        <p class="gallery-description">Our yearly book fair featuring local authors, publishers, and literary discussions.</p>
                        <span class="gallery-category">Events</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg" alt="Reading Session">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Community Reading Session</h3>
                        <p class="gallery-description">Weekly community reading sessions where members share their favorite books.</p>
                        <span class="gallery-category">Events</span>
                    </div>
                </div>

                <!-- Students -->
                <div class="gallery-item" data-category="students">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1438081/pexels-photo-1438081.jpeg" alt="Students Studying">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Students in Action</h3>
                        <p class="gallery-description">Students engaged in collaborative learning and research activities.</p>
                        <span class="gallery-category">Students</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="students">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1370296/pexels-photo-1370296.jpeg" alt="Group Study">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Group Study Sessions</h3>
                        <p class="gallery-description">Collaborative learning spaces where students work together on projects.</p>
                        <span class="gallery-category">Students</span>
                    </div>
                </div>

                <!-- Book Collections -->
                <div class="gallery-item" data-category="books">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1029141/pexels-photo-1029141.jpeg" alt="Classic Literature">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Classic Literature Collection</h3>
                        <p class="gallery-description">Our extensive collection of classic literature from around the world.</p>
                        <span class="gallery-category">Book Collections</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="books">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1029140/pexels-photo-1029140.jpeg" alt="Reference Books">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Reference Section</h3>
                        <p class="gallery-description">Comprehensive reference materials including encyclopedias, dictionaries, and academic journals.</p>
                        <span class="gallery-category">Book Collections</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="library">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/2908984/pexels-photo-2908984.jpeg" alt="Digital Section">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Digital Resources Center</h3>
                        <p class="gallery-description">Modern computer lab with access to digital databases and e-books.</p>
                        <span class="gallery-category">Library Spaces</span>
                    </div>
                </div>

                <div class="gallery-item" data-category="events">
                    <div class="gallery-image">
                        <img src="https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg" alt="Author Visit">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="gallery-info">
                        <h3 class="gallery-title">Author Visit Program</h3>
                        <p class="gallery-description">Special sessions with renowned authors sharing their writing experiences.</p>
                        <span class="gallery-category">Events</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="image-modal" id="imageModal">
        <div class="modal-content">
            <button class="modal-close" id="modalClose">&times;</button>
            <img class="modal-image" id="modalImage" src="" alt="">
            <div class="modal-info">
                <h3 class="modal-title" id="modalTitle"></h3>
                <p class="modal-description" id="modalDescription"></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const galleryItems = document.querySelectorAll('.gallery-item');
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalDescription = document.getElementById('modalDescription');
            const modalClose = document.getElementById('modalClose');

            // Filter functionality
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    const filter = this.getAttribute('data-filter');

                    galleryItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = 'block';
                            setTimeout(() => {
                                item.style.opacity = '1';
                                item.style.transform = 'scale(1)';
                            }, 10);
                        } else {
                            item.style.opacity = '0';
                            item.style.transform = 'scale(0.8)';
                            setTimeout(() => {
                                item.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });

            // Modal functionality
            galleryItems.forEach(item => {
                item.addEventListener('click', function() {
                    const img = this.querySelector('img');
                    const title = this.querySelector('.gallery-title').textContent;
                    const description = this.querySelector('.gallery-description').textContent;

                    modalImage.src = img.src;
                    modalImage.alt = img.alt;
                    modalTitle.textContent = title;
                    modalDescription.textContent = description;

                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            });

            // Close modal
            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });

            function closeModal() {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }

            // Initialize gallery items with transition
            galleryItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>