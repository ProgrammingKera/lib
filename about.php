<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/svg+xml" href="../uploads/assests/book.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .about-page {
            min-height: 100vh;
            background: var(--secondary-color);
            padding: 80px 0 40px;
            margin-top: 50px;
        }

        .about-container {
            
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .about-header h1 {
            font-size: 2.5em;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 700;
        }

        .about-header p {
            font-size: 1.2em;
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .about-content {
            display: grid;
            gap: 40px;
        }

        .about-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .about-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }

        .section-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5em;
        }

        .section-title {
            font-size: 1.8em;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }

        .section-content {
            color: var(--text-color);
            line-height: 1.7;
            font-size: 1.05em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .feature-card {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            transition: var(--transition);
        }

        .feature-card:hover {
            background: var(--gray-200);
            transform: translateY(-3px);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.3em;
            margin: 0 auto 15px;
        }

        .feature-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 10px;
        }

        .feature-description {
            color: var(--text-light);
            font-size: 0.95em;
            line-height: 1.5;
        }

        .stats-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .team-member {
            text-align: center;
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 30px 20px;
            transition: var(--transition);
        }

        .team-member:hover {
            background: var(--gray-200);
            transform: translateY(-5px);
        }

        .member-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 2em;
            margin: 0 auto 20px;
        }

        .member-name {
            font-size: 1.3em;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 5px;
        }

        .member-role {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 15px;
        }

        .member-description {
            color: var(--text-light);
            font-size: 0.9em;
            line-height: 1.5;
        }

        .contact-info {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 30px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
        }

        .contact-details h4 {
            margin: 0 0 5px 0;
            color: var(--text-color);
            font-weight: 600;
        }

        .contact-details p {
            margin: 0;
            color: var(--text-light);
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .about-header h1 {
                font-size: 2em;
            }

            .about-section {
                padding: 25px;
            }

            .section-title {
                font-size: 1.5em;
            }

            .features-grid,
            .team-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .about-header h1 {
                font-size: 1.8em;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .contact-grid {
                grid-template-columns: 1fr;
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

    <div class="about-page">
        <div class="about-container">
            <div class="about-header">
                <h1><i class="fas fa-info-circle"></i> About Our Library</h1>
                <p>Discover the story behind our modern library management system and the passionate team dedicated to serving our community's educational needs.</p>
            </div>

            <div class="about-content">
                <!-- Mission Section -->
                <div class="about-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h2 class="section-title">Our Mission</h2>
                    </div>
                    <div class="section-content">
                        <p>Our mission is to provide a comprehensive, user-friendly library management system that enhances the learning experience for students, faculty, and researchers. We strive to make knowledge accessible, organized, and easily discoverable through innovative technology and exceptional service.</p>
                        <p>We believe that libraries are the cornerstone of education and intellectual growth. Our system is designed to bridge the gap between traditional library services and modern digital needs, ensuring that every user can efficiently access the resources they need for their academic and personal development.</p>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="about-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h2 class="section-title">Key Features</h2>
                    </div>
                    <div class="section-content">
                        <p>Our library management system offers a comprehensive suite of features designed to streamline library operations and enhance user experience:</p>
                        
                        <div class="features-grid">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <h3 class="feature-title">Book Management</h3>
                                <p class="feature-description">Comprehensive catalog management with advanced search and filtering capabilities</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="feature-title">User Management</h3>
                                <p class="feature-description">Efficient user registration, role management, and profile customization</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <h3 class="feature-title">Reservation System</h3>
                                <p class="feature-description">Smart book reservation and queue management for popular titles</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <h3 class="feature-title">Digital Resources</h3>
                                <p class="feature-description">Access to e-books and digital materials with secure download options</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <h3 class="feature-title">Notifications</h3>
                                <p class="feature-description">Real-time alerts for due dates, reservations, and important updates</p>
                            </div>
                            
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h3 class="feature-title">Analytics</h3>
                                <p class="feature-description">Comprehensive reporting and analytics for library administrators</p>
                            </div>
                        </div>
                    </div>
                </div>

                
                <!-- Contact Section -->
                <div class="about-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h2 class="section-title">Get in Touch</h2>
                    </div>
                    <div class="section-content">
                        <p>We'd love to hear from you! Whether you have questions, suggestions, or need support, our team is here to help.</p>
                        
                        <div class="contact-info">
                            <div class="contact-grid">
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="contact-details">
                                        <h4>Address</h4>
                                        <p>123 University Avenue<br>Education City, EC 12345</p>
                                    </div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <div class="contact-details">
                                        <h4>Phone</h4>
                                        <p>+1 (555) 123-4567<br>Mon-Fri: 8AM-6PM</p>
                                    </div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <div class="contact-details">
                                        <h4>Email</h4>
                                        <p>info@library.edu<br>support@library.edu</p>
                                    </div>
                                </div>
                                
                                <div class="contact-item">
                                    <div class="contact-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="contact-details">
                                        <h4>Hours</h4>
                                        <p>Mon-Fri: 8AM-10PM<br>Sat-Sun: 10AM-8PM</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate sections on scroll
            const sections = document.querySelectorAll('.about-section');
            
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = `all 0.6s ease ${index * 0.1}s`;
                observer.observe(section);
            });

            // Animate stats numbers
            const statNumbers = document.querySelectorAll('.stat-number');
            
            function animateNumber(element) {
                const target = element.textContent;
                const number = parseInt(target.replace(/[^0-9]/g, ''));
                const suffix = target.replace(/[0-9]/g, '');
                let current = 0;
                const increment = number / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= number) {
                        current = number;
                        clearInterval(timer);
                    }
                    
                    if (number >= 1000) {
                        element.textContent = Math.floor(current).toLocaleString() + suffix;
                    } else {
                        element.textContent = Math.floor(current) + suffix;
                    }
                }, 30);
            }

            const statsObserver = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        animateNumber(entry.target);
                        statsObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            statNumbers.forEach(stat => {
                statsObserver.observe(stat);
            });
        });
    </script>
</body>
</html>