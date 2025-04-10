<?php
session_start();
include 'connect.php';

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Fetch categories and their recipes
$stmt = $db->query("
    SELECT c.id AS category_id, c.name AS category_name, r.id AS recipe_id, r.title AS recipe_title
    FROM categories c
    LEFT JOIN recipes r ON c.id = r.category_id
    ORDER BY c.name, r.title
");
$categories = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $categories[$row['category_id']]['name'] = $row['category_name'];
    if (!empty($row['recipe_id'])) {
        $categories[$row['category_id']]['recipes'][] = [
            'id' => $row['recipe_id'],
            'title' => $row['recipe_title']
        ];
    }
}

// Handle category addition (logged-in users only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['category_name']) && empty($_POST['category_id']) && $is_logged_in) {
    $name = trim($_POST['category_name']);
    $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    header("Location: category.php");
    exit();
}

// Handle updating a category (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $category_id = $_POST['category_id'];
    $name = trim($_POST['category_name']);
    $stmt = $db->prepare("UPDATE categories SET name = ? WHERE id = ?");
    $stmt->execute([$name, $category_id]);
    header("Location: category.php");
    exit();
}

// Handle deleting a category (admin only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $category_id = $_POST['category_id'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    header("Location: category.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Style/categorystyle.css">
    <title>Recipe Categories</title>
    <style>
        .category-list {
            list-style: none;
            padding: 0;
        }

        .category-item {
            margin-bottom: 10px;
        }

        .category-name {
            font-weight: bold;
            cursor: pointer;
            display: inline-block;
            margin-bottom: 5px;
        }

        .recipe-list {
            list-style: none;
            padding-left: 20px;
            display: none; /* Initially hidden */
        }

        .recipe-list li {
            margin-bottom: 5px;
        }

        .recipe-list a {
            text-decoration: none;
            color: #007BFF;
        }

        .recipe-list a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h1>Recipe Categories</h1>
        <nav>
            <ul>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Admin Dashboard</a></li>
                <?php endif; ?>
                <li><a href="index.php">Home</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>Browse Categories</h2>
        <ul class="category-list">
            <?php foreach ($categories as $category_id => $category): ?>
                <li class="category-item">
                    <span class="category-name" onclick="toggleRecipes(<?= $category_id; ?>)">
                        <?= htmlspecialchars($category['name']); ?>
                    </span>
                    <ul class="recipe-list" id="recipes-<?= $category_id; ?>">
                        <?php if (!empty($category['recipes'])): ?>
                            <?php foreach ($category['recipes'] as $recipe): ?>
                                <li>
                                    <a href="recipe.php?id=<?= $recipe['id']; ?>">
                                        <?= htmlspecialchars($recipe['title']); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li>No recipes available</li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ($is_logged_in): ?>
            <h3>Add New Category</h3>
            <form method="POST" class="category-form">
                <input type="text" name="category_name" placeholder="Enter category name" required>
                <button type="submit">Add Category</button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Winnipeg Recipe Hub</p>
    </footer>

    <script>
        function toggleRecipes(categoryId) {
            const recipeList = document.getElementById(`recipes-${categoryId}`);
            if (recipeList.style.display === 'none' || recipeList.style.display === '') {
                recipeList.style.display = 'block';
            } else {
                recipeList.style.display = 'none';
            }
        }
    </script>
</body>
</html>