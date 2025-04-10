<?php
session_start();
require_once 'connect.php';

$error_message = '';
$success_message = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (!empty($username) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
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
    <title>Register</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="register-container">
        <form method="POST" action="">
            <h2>Register</h2>

            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message); ?>
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

            <button type="submit">Register</button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </form>
    </div>
</body>
</html>