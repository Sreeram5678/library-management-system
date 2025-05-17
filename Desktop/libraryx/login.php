<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $sql = "SELECT id, username, password, full_name, status FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['status'] === 'suspended') {
                $error = "Your account is suspended. Please contact the library administrator.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];

                // Update last login
                $update_sql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();

                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LibraryX</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-form">
            <h2>Welcome Back!</h2>
            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                </div>
                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="social-login">
                <p>Or continue with</p>
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
                Don't have an account? <a href="signup.php">Sign up</a>
            </p>
        </div>
    </div>

    <script>
    // Add password visibility toggle
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const togglePassword = document.createElement('i');
        togglePassword.className = 'fas fa-eye password-toggle';
        togglePassword.style.position = 'absolute';
        togglePassword.style.right = '10px';
        togglePassword.style.top = '50%';
        togglePassword.style.transform = 'translateY(-50%)';
        togglePassword.style.cursor = 'pointer';
        togglePassword.style.color = '#4a5568';
        
        passwordInput.parentElement.style.position = 'relative';
        passwordInput.parentElement.appendChild(togglePassword);
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.className = `fas fa-${type === 'password' ? 'eye' : 'eye-slash'} password-toggle`;
        });
    });
    </script>
</body>
</html> 