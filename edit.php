<?php
session_start();
include 'connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if the recipe ID is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid Recipe ID');
}

$recipe_id = $_GET['id'];


// Fetch the recipe details from the database
$stmt = $db->prepare("SELECT * FROM recipes WHERE id = :id");
$stmt->execute(['id' => $recipe_id]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
    die('Recipe not found!');
}

// Fetch all categories for the dropdown
$categories = [];
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching categories: " . $e->getMessage();
}

// Handle the form submission to update the recipe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);

    // Check if an image was uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image']['name'];
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = mime_content_type($_FILES["image"]["tmp_name"]);

        if (!in_array($fileType, $allowedTypes)) {
            die("Invalid image format. Only JPG and PNG are allowed.");
        }

        if ($_FILES["image"]["size"] > 5000000) {
            die("Sorry, your file is too large.");
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            die("Error uploading your file.");
        }
    } else {
        // Keep the old image if no new image is uploaded
        $image = $recipe['image_path'];
    }

    // Update the recipe details in the database
    try {
        $stmt = $db->prepare("UPDATE recipes 
                              SET title = :title, 
                                  description = :description, 
                                  ingredients = :ingredients, 
                                  image_path = :image_path 
                              WHERE id = :id");

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'ingredients' => $ingredients,
            'image_path' => $image,
            'id' => $recipe_id
        ]);

        // Redirect to the recipe view page after a successful update
        header("Location: recipe.php?id=" . $recipe_id);
        exit;
    } catch (PDOException $e) {
        echo "Error updating recipe: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Style/editstyle.css">
    <title>Edit Recipe</title>
</head>
<body>
    <h1>Edit Recipe</h1>

    <!-- Edit recipe form -->
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($recipe['title']); ?>" required><br><br>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?= htmlspecialchars($recipe['description']); ?></textarea><br><br>

        <label for="ingredients">Ingredients:</label>
        <textarea id="ingredients" name="ingredients" required><?= htmlspecialchars($recipe['ingredients']); ?></textarea><br><br>
        
        <label for="category">Category:</label>
        <select id="category" name="category_id" required>
            <option value="" disabled>Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>" <?= $recipe['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label for="image">Image (JPG/PNG only):</label>
        <input type="file" id="image" name="image"><br><br>

        <button type="submit">Update Recipe</button>

        <?php    include 'comments.php';?>
    </form>

    <br><a href="recipe.php?id=<?= $recipe['id']; ?>">Back to Recipe</a>
</body>
</html>
