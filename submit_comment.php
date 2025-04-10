<?php
session_start();
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = $_POST['recipe_id'];
    $comment = $_POST['content']; // Use the correct variable name for the comment
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null; // Get the rating value
    $name = isset($_SESSION['username']) ? $_SESSION['username'] : $_POST['name']; // Use logged-in username or input name

    // Validate input
    if (empty($name) || empty($comment) || empty($rating)) {
        die('Name, comment content, and rating are required.');
    }

    try {
        // Use the correct column names from your database
        $stmt = $db->prepare("INSERT INTO comments (recipe_id, user_name, comment, rating, created_at) VALUES (:recipe_id, :user_name, :comment, :rating, NOW())");
        $stmt->execute([
            ':recipe_id' => $recipe_id,
            ':user_name' => $name,
            ':comment' => $comment,
            ':rating' => $rating
        ]);
        header("Location: recipe.php?id=$recipe_id");
        exit;
    } catch (PDOException $e) {
        die("Error submitting comment: " . $e->getMessage());
    }
}
?>