<?php
session_start();
require_once 'connect.php';
// require 'authenticate.php'; 
define('CMS_LOADED', true);
require_once 'functions.php';

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
function get_category($id) {
    global $db;
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get recipes for a category
function get_recipes_by_category($category_id) {
    global $db;
    $stmt = $db->prepare("
        SELECT r.id, r.title, r.description, r.ingredients, r.instructions, r.created_at, r.image_path, u.username
        FROM recipes r
        JOIN users u ON r.user_id = u.id
        WHERE r.category_id = ?
        ORDER BY r.title
    ");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$category = get_category($category_id);
if (!$category) {
    header('Location: index.php');
    exit();
}

$recipes = get_recipes_by_category($category_id);

// Page title
$page_title = "Recipes in category: " . htmlspecialchars($category['name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="Style/view_categorystyle.css">
</head>
<body>
    <!-- <?php include 'header.php'; // Include header if you have one ?> -->

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <h1><?php echo htmlspecialchars($page_title); ?></h1>
                
                <?php if (empty($recipes)): ?>
                    <div class="alert alert-info">
                        No recipes found in this category.
                    </div>
                <?php else: ?>
                    <div class="recipe-grid">
                        <?php foreach ($recipes as $recipe): ?>
                            <div class="recipe-card">
                                <?php if (!empty($recipe['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($recipe['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($recipe['title']); ?>" 
                                         class="recipe-image">
                                <?php else: ?>
                                    <div class="recipe-image-placeholder">No Image</div>
                                <?php endif; ?>
                                
                                <div class="recipe-content">
                                    <h3><a href="recipe.php?id=<?php echo htmlspecialchars($recipe['id']); ?>">
                                        <?php echo htmlspecialchars($recipe['title']); ?>
                                    </a></h3>
                                    <p class="recipe-description">
                                        <?php echo substr(strip_tags($recipe['description']), 0, 100) . '...'; ?>
                                    </p>
                                    <div class="recipe-meta">
                                        <span>By: <?php echo htmlspecialchars($recipe['username']); ?></span>
                                        <span>Added: <?php echo date('M j, Y', strtotime($recipe['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-secondary">Back</a>
            </div>
            
            <!-- <div class="col-md-4">
                <?php include 'sidebar.php'; // Include sidebar if you have one ?>
            </div> -->
        </div>
    </div>

    <footer>
        <p>&copy; 2025 Winnipeg Recipe Hub</p>
    </footer>
</body>
</html>