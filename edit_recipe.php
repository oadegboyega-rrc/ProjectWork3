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

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // CSRF protection
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token.");
    }

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
            } else {
                $error_message = "Error resizing or saving the image.";
            }
        } else {
            $error_message = "Invalid image type. Allowed types: jpg, jpeg, png, gif.";
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

// Helper function to resize images
function resizeImage($file, $width, $height, $type) {
    switch ($type) {
        case 'jpg':
        case 'jpeg':
            $src = imagecreatefromjpeg($file);
            break;
        case 'png':
            $src = imagecreatefrompng($file);
            break;
        case 'gif':
            $src = imagecreatefromgif($file);
            break;
        default:
            return false;
    }

    $orig_width = imagesx($src);
    $orig_height = imagesy($src);

    $aspect_ratio = $orig_width / $orig_height;
    if ($width / $height > $aspect_ratio) {
        $width = $height * $aspect_ratio;
    } else {
        $height = $width / $aspect_ratio;
    }

    $dst = imagecreatetruecolor($width, $height);

    if ($type === 'png' || $type === 'gif') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
    imagedestroy($src);

    return $dst;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Recipe</title>
    <link rel="stylesheet" href="Style/editstyle.css">
    <script src="tinymce/js/tinymce/tinymce.min.js"></script>
    <script>
        tinymce.init({
            selector: 'textarea.wysiwyg-editor',
            plugins: 'lists link image code fullscreen preview',
            toolbar: 'undo redo | styleselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | code',
            menubar: false,
            height: 300
        });
    </script>
</head>
<body>
    <h1>Edit Recipe</h1>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?= htmlspecialchars($recipe['title']); ?>" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" class="wysiwyg-editor" required><?= htmlspecialchars($recipe['description']); ?></textarea>

        <label for="ingredients">Ingredients:</label>
        <textarea name="ingredients" id="ingredients" class="wysiwyg-editor" required><?= htmlspecialchars($recipe['ingredients']); ?></textarea>

        <label for="instructions">Instructions:</label>
        <textarea name="instructions" id="instructions" class="wysiwyg-editor" required><?= htmlspecialchars($recipe['instructions']); ?></textarea>

        <label for="image">Image (optional):</label>
        <input type="file" name="image" id="image" accept="image/*">
        <?php if (!empty($recipe['image_path'])): ?>
            <p>Current Image: <img src="<?= htmlspecialchars($recipe['image_path']); ?>" alt="Recipe Image" width="100"></p>
            <label><input type="checkbox" name="delete_image"> Delete current image</label>
        <?php endif; ?>

        <label for="category">Category:</label>
        <select name="category_id" id="category" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>" <?= $recipe['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Update Recipe</button>`
        <a href="recipe.php?id=<?= $recipe['id']; ?>">Back to Recipe</a>
    </form>
</body>
</html>