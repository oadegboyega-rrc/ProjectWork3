<?php
session_start();
require 'connect.php';

// Check if the user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get the recipe ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php");
    exit();
}
$recipe_id = intval($_GET['id']);

// Fetch the recipe details
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = ?");
$stmt->execute([$recipe_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    header("Location: admin.php");
    exit();
}

// Fetch all categories for the dropdown
$categoriesStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $category_id = intval($_POST['category_id']);
    $image_path = $recipe['image_path']; // Default to existing image

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_type = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($image_type, $allowed_types)) {
            $upload_dir = 'uploads/';
            $new_image_path = $upload_dir . uniqid() . '.' . $image_type;

            // Resize and save the image
            $resized_image = resizeImage($image_tmp_name, 800, 600, $image_type);
            if ($resized_image && imagejpeg($resized_image, $new_image_path, 90)) {
                // Delete the old image
                if ($image_path && file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = $new_image_path;
                imagedestroy($resized_image);
            }
        }
    }

    // Handle image deletion
    if (isset($_POST['delete_image']) && $_POST['delete_image'] === 'on') {
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $image_path = null;
    }

    // Update the recipe in the database
    if (!empty($title) && !empty($description) && !empty($ingredients) && !empty($instructions)) {
        $stmt = $db->prepare("UPDATE recipes SET title = ?, description = ?, ingredients = ?, instructions = ?, category_id = ?, image_path = ? WHERE id = ?");
        $stmt->execute([$title, $description, $ingredients, $instructions, $category_id, $image_path, $recipe_id]);
        header("Location: admin.php");
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
    <title>Edit Recipe</title>
    <link rel="stylesheet" href="Style/editstyle.css">
</head>
<body>
    <h1>Edit Recipe</h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($recipe['title']); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" required><?= htmlspecialchars($recipe['description']); ?></textarea>

        <label for="ingredients">Ingredients:</label>
        <textarea name="ingredients" id="ingredients" required><?= htmlspecialchars($recipe['ingredients']); ?></textarea>

        <label for="instructions">Instructions:</label>
        <textarea name="instructions" id="instructions" required><?= htmlspecialchars($recipe['instructions']); ?></textarea>

        <label for="category">Category:</label>
        <select name="category_id" id="category" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>" <?= ($recipe['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="image">Recipe Image:</label>
        <?php if (!empty($recipe['image_path'])): ?>
            <img src="<?= htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image" width="100">
        <?php endif; ?>
        <input type="file" name="image" id="image" accept="image/*">

        <button type="submit">Update Recipe</button>
    </form>
</body>
</html>