<?php
session_start();
include 'includes/config.php';

$message = '';
$messageType = '';
$validToken = false;
$token = isset($_GET['token']) ? $_GET['token'] : '';

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

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("
        SELECT pr.*, u.email, u.id as user_id
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? AND pr.expires_at > NOW()
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $reset = $result->fetch_assoc();
        $validToken = true;
    } else {
        $message = "Invalid or expired reset token. Please request a new password reset.";
        $messageType = "danger";
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate password strength
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) {
        $message = implode(". ", $passwordErrors);
        $messageType = "danger";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $reset['user_id']);
        
        if ($stmt->execute()) {
            // Delete all reset tokens for this user
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $stmt->bind_param("i", $reset['user_id']);
            $stmt->execute();
            
            $message = "Password has been reset successfully. You can now login with your new password.";
            $messageType = "success";
            
            // Redirect to login page after 3 seconds
            header("refresh:3;url=index.php");
        } else {
            $message = "Error resetting password. Please try again.";
            $messageType = "danger";
        }
    }
}

// Create password_resets table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token (token)
)";
$conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Library Management System</title>
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
        <div class="auth-container">
            <div class="auth-header">
                <h1>
                    <i class="fas fa-lock"></i>
                    Reset Password
                </h1>
                <p>Create your new password</p>
            </div>
            
            <div class="auth-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($validToken): ?>
                    <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" id="resetForm">
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> 
                                New Password
                            </label>
                            <input type="password" id="password" name="password" placeholder="Enter your new password" required>
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
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> 
                                Confirm New Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                        </div>
                        
                        <button type="submit" class="btn-auth" id="submitBtn" disabled>
                            <i class="fas fa-save"></i> 
                            Update Password
                        </button>
                    </form>
                <?php else: ?>
                    <div style="text-align: center;">
                        <a href="forgot_password.php" class="btn-auth">
                            <i class="fas fa-redo"></i> 
                            Request New Reset Link
                        </a>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: center;">
                    <a href="index.php" class="btn-link-auth">
                        <i class="fas fa-arrow-left"></i> 
                        Back to Login
                    </a>
                </div>
            </div>
            
            <div class="auth-footer">
                <p>&copy; 2025 Library Management System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const lengthReq = document.getElementById('length-req');
            const uppercaseReq = document.getElementById('uppercase-req');
            const specialReq = document.getElementById('special-req');
            const passwordRequirements = document.getElementById('passwordRequirements');

            if (passwordInput && confirmPasswordInput) {
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
                    submitBtn.disabled = !(isValid && passwordsMatch);

                    return isValid;
                }

                passwordInput.addEventListener('input', function() {
                    validatePassword();
                    // Show requirements when typing
                    if (this.value.length > 0) {
                        passwordRequirements.classList.add('show');
                    }
                });

                confirmPasswordInput.addEventListener('input', validatePassword);

                // Form submission validation
                document.getElementById('resetForm').addEventListener('submit', function(e) {
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
        });
    </script>
</body>
</html>