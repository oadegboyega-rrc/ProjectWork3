<?php
// Require necessary files
require_once 'page.php';
include 'connect.php';

// Check if the user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get all categories
$categories = getCategories();

// Fetch random recipe images for each category
$categoryImages = [];
try {
    $stmt = $db->query("SELECT c.id AS category_id, c.name AS category_name, 
                               (SELECT r.image_path 
                                FROM recipes r 
                                WHERE r.category_id = c.id 
                                ORDER BY RAND() LIMIT 1) AS random_image 
                        FROM categories c");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryImages[$row['category_id']] = [
            'name' => $row['category_name'],
            'image' => $row['random_image']
        ];
    }
} catch (PDOException $e) {
    echo "Error fetching category images: " . $e->getMessage();
}

// Fetch recipes grouped by categories
$recipesByCategory = [];
try {
    $stmt = $db->query("SELECT r.id, r.title, r.image_path, r.category_id, c.name AS category_name 
                        FROM recipes r 
                        JOIN categories c ON r.category_id = c.id 
                        ORDER BY c.name, r.created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recipesByCategory[$row['category_name']][] = $row;
    }
} catch (PDOException $e) {
    echo "Error fetching recipes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Style/headerstyle.css">
    <title>Winnipeg Recipe Hub</title>
</head>
<body>
<header>
    <div class="logo">
        <img src="uploads/logo_image.png" alt="Winnipeg Recipe Logo">
        <h1>Winnipeg Recipe!</h1>
    </div>
    <nav>
        <ul>
            <!-- <li><a href="index.php">Home</a></li> -->
            <li class="dropdown">
                <a href="#">Categories</a>
                <div class="dropdown-content">
                    <?php foreach ($categories as $category): ?>
                        <a href="view_category.php?id=<?= $category['id']; ?>">
                            <?= htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </li>
            <?php if ($isLoggedIn): ?>
                <li><a href="add_category.php">Add New Page</a></li>
                <li><a href="view_pages.php">Manage Pages</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <form method="GET" action="search.php" class="search-form">
        <input type="text" name="query" placeholder="Search pages..." required>
        <button type="submit">Search</button>
    </form>
</header>