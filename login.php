<?php
session_start();

// Database connection
require_once 'connect.php';

$error_message = '';

// Check if a success message exists in the session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Remove the message after displaying it
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];

    // Validate input
    if (!empty($username) && !empty($password)) {
        try {
            // Prepare SQL statement using parameterized query for security
            $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verify password using password_verify (assumes passwords are hashed)
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Store user information in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on user role
                if ($user['role'] == 'admin') {
                    header("Location: admin.php");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }
            } else {
                $error_message = "Invalid username or password.";
            }
        } catch (PDOException $e) {
            // Log error securely (don't expose details to user)
            error_log("Login error: " . $e->getMessage());
            $error_message = "An error occurred. Please try again later.";
        }
    } else {
        $error_message = "Please enter both username and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="Style/loginstyle.css">
</head>
<body>
    <header class=""header>
        <div class="logo">
            <img src="uploads/logo_image.png" alt="Winnipeg Recipe Logo">
            <h1>Winnipeg Recipe!</h1>
        </div>
    </header>
    
    <div class="login-container">
        <form method="POST" action="">
            <h2>Login</h2>

            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?= htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>

            <button type="submit">Login</button>

            <div class="register-link">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </form>
    </div>

    <footer>
        <p>&copy; 2025 Winnipeg Recipe Hub</p>
    </footer>
</body>
</html>