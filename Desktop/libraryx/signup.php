<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);

    // Enhanced validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif (!preg_match("/^[a-zA-Z0-9_]+$/", $username)) {
        $error = "Username can only contain letters, numbers, and underscores";
    } else {
        try {
            // Check if username or email already exists
            $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Username or email already exists";
            } else {
                // Hash password with strong algorithm
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user with prepared statement
                $sql = "INSERT INTO users (username, email, password, full_name, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $full_name);

                if ($stmt->execute()) {
                    // Get the new user's ID
                    $user_id = $conn->insert_id;
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['full_name'] = $full_name;
                    
                    $success = "Registration successful! You are now logged in.";
                    
                    // Redirect after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Create Account</h2>
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-at"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required 
                           pattern="[a-zA-Z0-9_]+" 
                           placeholder="Choose a username"
                           title="Username can only contain letters, numbers, and underscores">
                </div>
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           minlength="8" 
                           placeholder="Create a password"
                           title="Password must be at least 8 characters long">
                </div>
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm your password">
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="social-login">
                <p>Or sign up with</p>
                <div class="social-buttons">
                    <button class="social-btn">
                        <i class="fab fa-google"></i>
                    </button>
                    <button class="social-btn">
                        <i class="fab fa-facebook"></i>
                    </button>
                    <button class="social-btn">
                        <i class="fab fa-twitter"></i>
                    </button>
                </div>
            </div>

            <p class="form-footer">
                Already have an account? <a href="login.php">Login</a>
            </p>
        </div>
    </div>

    <script>
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        const terms = document.getElementById('terms').checked;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return false;
        }
        
        if (!terms) {
            alert('Please agree to the Terms of Service and Privacy Policy');
            return false;
        }
        
        return true;
    }

    // Add password visibility toggle
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        
        passwordInputs.forEach(input => {
            const togglePassword = document.createElement('i');
            togglePassword.className = 'fas fa-eye password-toggle';
            togglePassword.style.position = 'absolute';
            togglePassword.style.right = '10px';
            togglePassword.style.top = '50%';
            togglePassword.style.transform = 'translateY(-50%)';
            togglePassword.style.cursor = 'pointer';
            togglePassword.style.color = '#4a5568';
            
            input.parentElement.style.position = 'relative';
            input.parentElement.appendChild(togglePassword);
            
            togglePassword.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.className = `fas fa-${type === 'password' ? 'eye' : 'eye-slash'} password-toggle`;
            });
        });
    });
    </script>
</body>
</html> 