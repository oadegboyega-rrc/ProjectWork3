<?php
session_start();
require 'connect.php'; // Database connection

// Ensure only authenticated users can delete
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if recipe_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipe_id'])) {
    $recipe_id = intval($_POST['recipe_id']);

    try {
        // Prepare delete query
        $stmt = $db->prepare("DELETE FROM recipes WHERE id = :id");
        $stmt->bindParam(':id', $recipe_id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect to index after deletion
        header("Location: index.php?message=Recipe deleted successfully");
        exit();
    } catch (PDOException $e) {
        echo "Error deleting recipe: " . $e->getMessage();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
