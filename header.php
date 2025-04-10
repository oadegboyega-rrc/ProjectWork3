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
            <li><a href="index.php">Home</a></li>
            <li class="dropdown">
                <a href="#">Categories</a>
                <div class="dropdown-content">
                    <?php if (empty($categories)): ?>
                        <a href="#">No categories available</a>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <a href="#"><?php echo htmlspecialchars($category['name']); ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
</header>

<div class="container">
    <h1>Welcome to Winnipeg Recipe Hub</h1>
    <div class="call-to-action">
        <p>Explore our recipes by category below!</p>
    </div>

    <div class="categories-section">
        <?php if (empty($categoryImages)): ?>
            <p>No categories available at this time. Please check back later!</p>
        <?php else: ?>
            <?php foreach ($categoryImages as $categoryId => $category): ?>
                <div class="category-card">
                    <h2><?php echo htmlspecialchars($category['name']); ?></h2>
                    <?php if (!empty($category['image'])): ?>
                        <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-image">
                    <?php else: ?>
                        <img src="uploads/default_recipe_image.png" alt="Default Category Image" class="category-image">
                    <?php endif; ?>
                    <div class="category-recipes">
                        <h3>Recipes:</h3>
                        <ul>
                            <?php if (!empty($recipesByCategory[$category['name']])): ?>
                                <?php foreach ($recipesByCategory[$category['name']] as $recipe): ?>
                                    <li class="dropdown-recipe">
                                        <a href="recipe.php?id=<?php echo $recipe['id']; ?>"><?php echo htmlspecialchars($recipe['title']); ?></a>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>No recipes available</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>