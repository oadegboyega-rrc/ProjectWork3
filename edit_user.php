<?php
require 'authenticate.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get user ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}
$user_id = intval($_GET['id']);

// Fetch user details
$stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: manage_users.php");
    exit();
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];

    if (!empty($username) && !empty($email)) {
        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $email, $role, $user_id]);
        header("Location: manage_users.php");
        exit();
    } else {
        $error_message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="Style/edit_user.css">
</head>
<body>
    <div class="header">
        <h1>Edit User</h1>
        <a href="manage_users.php">Back to Manage Users</a>
    </div>

    <div class="edit-user-form">
        <h2>Edit User Details</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']); ?>" required>

            <label for="role">Role:</label>
            <select name="role" id="role" required>
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>

            <button type="submit">Update User</button>
        </form>
    </div>
</body>
</html>