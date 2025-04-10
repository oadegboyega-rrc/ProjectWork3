<?php
session_start();
include 'connect.php';
// include 'authenticate.php';
// define('CMS_LOADED', true);
// require_once 'functions.php';

// var_dump($_SESSION);

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // die("You do not have permission to access this page.");
}

// Handle category addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name'])) {
    $name = trim($_POST['category_name']);
    
    if (!empty($name)) {
        // Insert the new category into the database
        $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        header("Location: view_category.php"); // Redirect to view categories page
        exit();
    } else {
        $error = "Category name cannot be empty.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Category</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Add New Category</h2>

        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="category_name">Category Name:</label>
            <input type="text" name="category_name" id="category_name" required>

            <button type="submit">Add Category</button>
        </form>

        <br>
        <a href="view_category.php">View All Categories</a>
    </div>
</body>
</html>
