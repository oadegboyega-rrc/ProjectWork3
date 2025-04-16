<?php
session_start();
include 'connect.php';

// Check if recipe_id is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid Recipe ID');
}

$recipe_id = $_GET['id'] ?? NULL;  // Define the recipe_id variable

// Fetch recipe details
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
$stmt->execute(['id' => $recipe_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recipe) {
    die('Recipe not found!');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe['title']); ?> - Recipe</title>
    <link rel="stylesheet" href="Style/recipestyle.css"> <!-- Link to the CSS file -->
</head>
<body>
<header>
    <h1>Winnipeg Recipe Hub</h1>
    <nav>
        <ul>
            <?php if (isset($_SESSION['role'])): ?>
                <li><a href="index.php">Home</a></li>
            <?php endif; ?>
            <!-- <li><a href="category.php">Categories</a></li>
            <?php if (isset($_SESSION['role'])): ?>
                <li><a href="create_page.php">Submit Recipe</a></li>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?> -->
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="admin.php">Admin Panel</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div class="container">
    <div class="recipe-header">
        <h1><?php echo htmlspecialchars($recipe['title']); ?></h1>
        <?php if (!empty($recipe['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image">
        <?php else: ?>
            <p>No image available</p>
        <?php endif; ?>
    </div>

    <div class="recipe-details">
        <article>
            <h1><?=htmlspecialchars($recipe['title']); ?></h1>
            <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>
            <p><strong>Ingredients:</strong><br><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>
            <p><strong>Instructions:</strong><br><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>
            <p class="category">Category: <?php echo htmlspecialchars($recipe['category_id']); ?></p>
        </article>
    </div>

    <!-- Include comments section -->
    <?php include 'comments.php'; ?>
</div>

<footer>
    <p>&copy; 2025 Winnipeg Recipe Hub</p>
</footer>
</body>
</html>