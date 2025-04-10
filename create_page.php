<?php
require 'authenticate.php';

// Check if the user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch categories for dropdown
try {
    $stmt = $db->query("SELECT id, name FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// Helper function to resize images
function resizeImage($file, $width, $height, $type) {
    // Create an image resource from the file
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

    // Get original dimensions
    $orig_width = imagesx($src);
    $orig_height = imagesy($src);

    // Calculate aspect ratio
    $aspect_ratio = $orig_width / $orig_height;
    if ($width / $height > $aspect_ratio) {
        $width = $height * $aspect_ratio;
    } else {
        $height = $width / $aspect_ratio;
    }

    // Create a new blank image with the desired dimensions
    $dst = imagecreatetruecolor($width, $height);

    // Preserve transparency for PNG and GIF
    if ($type === 'png' || $type === 'gif') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    // Copy and resize the original image into the new image
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);

    // Free memory
    imagedestroy($src);

    return $dst;
}

// Handle recipe submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id'];

    // Initialize image path as NULL
    $image_path = null;

    // Handle image upload (optional)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $image_name = basename($image['name']);
        $image_tmp_name = $image['tmp_name'];
        $image_type = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($image_type, $allowed_types) && $image['size'] <= $max_size) {
            $upload_dir = 'uploads/';
            $new_image_path = $upload_dir . uniqid() . '.' . $image_type;

            // Resize the image before saving
            $resized_image = resizeImage($image_tmp_name, 800, 600, $image_type); // Resize to 800x600
            if ($resized_image) {
                // Save the resized image
                if (imagejpeg($resized_image, $new_image_path, 90)) { // Save as JPEG with 90% quality
                    $image_path = $new_image_path;
                } else {
                    echo "Error saving resized image.";
                }
                imagedestroy($resized_image); // Free memory
            } else {
                echo "Error resizing image.";
            }
        } else {
            echo "Invalid image type or file size.";
        }
    }

    // Insert the recipe into the database
    $stmt = $db->prepare("INSERT INTO recipes (title, description, ingredients, instructions, image_path, category_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $ingredients, $instructions, $image_path, $category_id, $user_id]);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Recipe</title>
    <link rel="stylesheet" href="Style/createpagestyle.css">
</head>
<body>
    <h1>Create a New Recipe</h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" required></textarea>

        <label for="ingredients">Ingredients:</label>
        <textarea name="ingredients" id="ingredients" required></textarea>

        <label for="instructions">Instructions:</label>
        <textarea name="instructions" id="instructions" required></textarea>

        <label for="image">Image (optional):</label>
        <input type="file" name="image" id="image" accept="image/*">

        <label for="category">Category:</label>
        <select name="category_id" id="category" required>
            <option value="">Select a category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Create Recipe</button>
    </form>
</body>
</html>