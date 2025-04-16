<?php
session_start();
require_once 'connect.php';

$error_message = '';
$success_message = '';

// Generate a CAPTCHA if it doesn't exist
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = rand(1000, 9999); // Generate a random 4-digit number
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $captcha_input = $_POST['captcha_input'];

    // Validate input
    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password) && !empty($captcha_input)) {
        if ($password === $confirm_password) {
            // Validate CAPTCHA
            if ($captcha_input === $_SESSION['captcha']) {
                try {
                    // Hash the password using password_hash()
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert the user into the database
                    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, 'user')");
                    $stmt->execute([
                        'username' => $username,
                        'email' => $email,
                        'password' => $hashed_password
                    ]);

                    // Set success message and redirect to login
                    $_SESSION['success_message'] = 'Registration successful! You can now log in.';
                    unset($_SESSION['captcha']); // Clear CAPTCHA after successful registration
                    header('Location: login.php');
                    exit();
                } catch (PDOException $e) {
                    // Handle duplicate username or email
                    if ($e->getCode() === '23000') {
                        $error_message = 'Username or email already exists.';
                    } else {
                        $error_message = 'An error occurred. Please try again later.';
                    }
                }
            } else {
                $error_message = 'Invalid CAPTCHA. Please try again.';
                $_SESSION['captcha'] = rand(1000, 9999); // Regenerate CAPTCHA
            }
        } else {
            $error_message = 'Passwords do not match.';
        }
    } else {
        $error_message = 'All fields are required.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="Style/registerstyle.css">
    <script>
        function refreshCaptcha() {
            document.getElementById('captcha-image').src = 'captcha.php?' + Date.now();
        }
    </script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="uploads/logo_image.png" alt="Winnipeg Recipe Logo">
            <h1>Winnipeg Recipe Hub</h1>
        </div>
    </header>

    <div class="register-container">
        <form method="POST" action="">
            <h2>Register</h2>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <!-- CAPTCHA Section -->
            <div class="form-group">
                <label for="captcha_input">Enter the CAPTCHA:</label>
                <img src="captcha.php" alt="CAPTCHA Image" id="captcha-image">
                <input type="text" id="captcha_input" name="captcha_input" required>
                <button type="button" onclick="refreshCaptcha()">Refresh CAPTCHA</button>
            </div>

            <button type="submit">Register</button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2025 Winnipeg Recipe Hub</p>
    </footer>
</body>
</html>