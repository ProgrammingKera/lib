<?php
session_start();
include 'includes/config.php';

$message = '';
$messageType = '';
$generatedId = '';

// Password validation function
function validatePassword($password) {
    $errors = [];
    
    // Check minimum length (8 characters)
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    // Check for at least one uppercase letter
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    // Check for at least one special character (@, #, $)
    if (!preg_match('/[@#$]/', $password)) {
        $errors[] = "Password must contain at least one special character (@, #, $)";
    }
    
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = $_POST['role']; // Get role from form
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $message = "All required fields must be filled out";
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match";
        $messageType = "danger";
    } else {
        // Validate password strength
        $passwordErrors = validatePassword($password);
        if (!empty($passwordErrors)) {
            $message = implode(". ", $passwordErrors);
            $messageType = "danger";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Email already registered";
                $messageType = "danger";
            } else {
                // Generate unique ID
                $uniqueId = generateUniqueId($conn, $role);
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (unique_id, name, email, password, role, department, phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $uniqueId, $name, $email, $hashedPassword, $role, $department, $phone);
                
                if ($stmt->execute()) {
                    $generatedId = $uniqueId;
                    $message = "Registration successful! Your unique ID is: <strong>$uniqueId</strong><br>Please save this ID for login. You can now login using either your email or unique ID.";
                    $messageType = "success";
                    
                    // Don't redirect immediately, show the ID first
                } else {
                    $message = "Error registering user: " . $stmt->error;
                    $messageType = "danger";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Library Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="icon" type="image/svg+xml" href="uploads/assests/book.svg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            </div>
        </div>
    </nav>

    <div class="auth-page">
        <div class="auth-container large">
            <div class="auth-header">
                <h1>
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </h1>
                
            </div>
            
            <div class="auth-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                    
                    <?php if ($messageType == 'success' && !empty($generatedId)): ?>
                        <div class="unique-id-display">
                            <h3>
                                <i class="fas fa-id-card"></i> 
                                Your Unique ID
                            </h3>
                            <div class="id-value" id="uniqueId"><?php echo $generatedId; ?></div>
                            <button type="button" class="copy-btn" onclick="copyToClipboard()">
                                <i class="fas fa-copy"></i> Copy ID
                            </button>
                            <p><strong>Important:</strong> Save this ID safely. You can use either your email or this unique ID to login.</p>
                            <a href="index.php" class="btn-auth" style="display: inline-block; margin-top: 15px; text-decoration: none; width: auto; padding: 12px 24px;">
                                <i class="fas fa-sign-in-alt"></i> Go to Login
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($messageType != 'success'): ?>
                <form method="POST" action="" id="registerForm">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="name">
                                    <i class="fas fa-user"></i> 
                                    Full Name *
                                </label>
                                <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> 
                                    Email Address *
                                </label>
                                <input type="email" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-user-tag"></i> 
                            Role *
                        </label>
                        <select id="role" name="role" required>
                            <option value="">Select your role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty Member</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-lock"></i> 
                                    Password *
                                </label>
                                <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                                <div class="password-requirements" id="passwordRequirements">
                                    <h4>Password Requirements:</h4>
                                    <div class="requirement" id="length-req">
                                        <i class="fas fa-times"></i>
                                        <span>At least 8 characters long</span>
                                    </div>
                                    <div class="requirement" id="uppercase-req">
                                        <i class="fas fa-times"></i>
                                        <span>At least one uppercase letter (A-Z)</span>
                                    </div>
                                    <div class="requirement" id="special-req">
                                        <i class="fas fa-times"></i>
                                        <span>At least one special character (@, #, $)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> 
                                    Confirm Password *
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="department">
                                    <i class="fas fa-building"></i> 
                                    Department
                                </label>
                                <input type="text" id="department" name="department" placeholder="Your department (optional)">
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="phone">
                                    <i class="fas fa-phone"></i> 
                                    Phone Number
                                </label>
                                <input type="tel" id="phone" name="phone" placeholder="03123456789 (optional)">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-auth" id="submitBtn" disabled>
                        <i class="fas fa-user-plus"></i> 
                        Create Account
                    </button>
                    
                    <div style="text-align: center;">
                        <a href="index.php" class="btn-link-auth">
                            <i class="fas fa-sign-in-alt"></i> 
                            Already have an account? Sign in
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
            
            <div class="auth-footer">
                <p>&copy; 2025 Library Management System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const uniqueId = document.getElementById('uniqueId').textContent;
            navigator.clipboard.writeText(uniqueId).then(function() {
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.classList.add('copied');
                
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.classList.remove('copied');
                }, 2000);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const lengthReq = document.getElementById('length-req');
            const uppercaseReq = document.getElementById('uppercase-req');
            const specialReq = document.getElementById('special-req');
            const passwordRequirements = document.getElementById('passwordRequirements');

            if (passwordInput) {
                // Show password requirements when password field is focused
                passwordInput.addEventListener('focus', function() {
                    passwordRequirements.classList.add('show');
                });

                // Hide password requirements when password field loses focus (with delay)
                passwordInput.addEventListener('blur', function() {
                    setTimeout(() => {
                        if (document.activeElement !== confirmPasswordInput) {
                            passwordRequirements.classList.remove('show');
                        }
                    }, 200);
                });

                function validatePassword() {
                    const password = passwordInput.value;
                    let isValid = true;

                    // Check length
                    if (password.length >= 8) {
                        lengthReq.classList.add('valid');
                        lengthReq.classList.remove('invalid');
                        lengthReq.querySelector('i').className = 'fas fa-check';
                    } else {
                        lengthReq.classList.add('invalid');
                        lengthReq.classList.remove('valid');
                        lengthReq.querySelector('i').className = 'fas fa-times';
                        isValid = false;
                    }

                    // Check uppercase
                    if (/[A-Z]/.test(password)) {
                        uppercaseReq.classList.add('valid');
                        uppercaseReq.classList.remove('invalid');
                        uppercaseReq.querySelector('i').className = 'fas fa-check';
                    } else {
                        uppercaseReq.classList.add('invalid');
                        uppercaseReq.classList.remove('valid');
                        uppercaseReq.querySelector('i').className = 'fas fa-times';
                        isValid = false;
                    }

                    // Check special characters
                    if (/[@#$]/.test(password)) {
                        specialReq.classList.add('valid');
                        specialReq.classList.remove('invalid');
                        specialReq.querySelector('i').className = 'fas fa-check';
                    } else {
                        specialReq.classList.add('invalid');
                        specialReq.classList.remove('valid');
                        specialReq.querySelector('i').className = 'fas fa-times';
                        isValid = false;
                    }

                    // Check if passwords match
                    const passwordsMatch = password === confirmPasswordInput.value && password.length > 0;

                    // Enable/disable submit button
                    if (submitBtn) {
                        submitBtn.disabled = !(isValid && passwordsMatch);
                    }

                    return isValid;
                }

                passwordInput.addEventListener('input', function() {
                    validatePassword();
                    // Show requirements when typing
                    if (this.value.length > 0) {
                        passwordRequirements.classList.add('show');
                    }
                });

                if (confirmPasswordInput) {
                    confirmPasswordInput.addEventListener('input', validatePassword);
                }

                // Form submission validation
                const form = document.getElementById('registerForm');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!validatePassword()) {
                            e.preventDefault();
                            alert('Please ensure your password meets all requirements.');
                        }

                        if (passwordInput.value !== confirmPasswordInput.value) {
                            e.preventDefault();
                            alert('Passwords do not match.');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>