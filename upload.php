<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    Header("Location: login.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $category_id = isset($_POST['category']) ? $_POST['category'] : null; // Check if category exists
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    $image = !empty($_FILES["image"]["name"]) ? "uploads/" . basename($_FILES["image"]["name"]) : null;

    if (!empty($title) && !empty($category_id) && !empty($ingredients) && !empty($instructions)) {
        if ($image && !move_uploaded_file($_FILES["image"]["tmp_name"], $image)) {
            echo "Image upload failed. Please check file permissions.";
        } else {
            $user_id = $_SESSION['user_id'];
            $stmt = $db->prepare("INSERT INTO recipes (user_id, category_id, title, image, ingredients, instructions) 
                                  VALUES (:user_id, :category_id, :title, :image, :ingredients, :instructions)");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':image', $image);
            $stmt->bindParam(':ingredients', $ingredients);
            $stmt->bindParam(':instructions', $instructions);

            if ($stmt->execute()) {
                echo "Recipe created successfully!";
                Header("Location: index.php");
            } else {
                echo "Upload failed. Please try again later.";
            }
        }
    } else {
        echo "Please fill in all fields";
    }
}
?>
<form method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Recipe Title" required>
    <select name="category" required>
        <option value="">Select Category</option>
        <?php
        $result = $db->query("SELECT * FROM categories");
        while ($row = $result->fetch()) {
            echo "<option value='{$row['id']}'>{$row['name']}</option>";
        }
        ?>
    </select>
    <textarea name="ingredients" placeholder="Ingredients" required></textarea>
    <textarea name="instructions" placeholder="Instructions" required></textarea>
    <input type="file" name="image">
    <button type="submit">Submit</button>
</form>