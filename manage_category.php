<?php
session_start();
require 'connect.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all categories
$categoriesStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle category update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_category'])) {
    $category_id = intval($_POST['category_id']);
    $category_name = trim($_POST['category_name']);

    if (!empty($category_name)) {
        $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->execute([$category_name, $category_id]);
        $success_message = "Category updated successfully.";
    } else {
        $error_message = "Category name cannot be empty.";
    }
}

// Handle category creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_category'])) {
    $category_name = trim($_POST['category_name']);

    if (!empty($category_name)) {
        $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$category_name]);
        $success_message = "Category created successfully.";
    } else {
        $error_message = "Category name cannot be empty.";
    }
}

// Handle category deletion
if (isset($_GET['delete_category'])) {
    $category_id = intval($_GET['delete_category']);
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $success_message = "Category deleted successfully.";
    header("Location: manage_category.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <link rel="stylesheet" href="Style/categorystyle.css">
</head>
<body>
    <h1>Manage Categories</h1>
    <a href="admin.php">Back to Admin Dashboard</a>

    <!-- Display Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?= htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Create New Category -->
    <h2>Create New Category</h2>
    <form method="POST" action="manage_category.php">
        <input type="text" name="category_name" placeholder="Enter category name" required>
        <button type="submit" name="create_category">Create Category</button>
    </form>

    <!-- Display Categories -->
    <h2>Existing Categories</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= htmlspecialchars($category['id']); ?></td>
                <td>
                    <form method="POST" action="manage_category.php" style="display: inline;">
                        <input type="hidden" name="category_id" value="<?= $category['id']; ?>">
                        <input type="text" name="category_name" value="<?= htmlspecialchars($category['name']); ?>" required>
                        <button type="submit" name="update_category">Update</button>
                    </form>
                </td>
                <td>
                    <a href="manage_category.php?delete_category=<?= $category['id']; ?>" 
                       onclick="return confirm('Are you sure you want to delete this category?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>