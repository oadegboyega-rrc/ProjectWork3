<?php
// Prevent direct access
if (!defined('CMS_LOADED')) {
    die("Direct access not allowed.");
}

// Include database connection
require_once 'connect.php';

/**
 * Get all categories from the database.
 */
// function get_all_categories() {
//     global $db;
//     $stmt = $db->query("SELECT id, name FROM categories ORDER BY name ASC");
//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }

/**
 * Get categories for a specific page.
 * @param int $page_id - The ID of the page.
 */
function get_page_categories($page_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.id, c.name FROM categories c
                           JOIN page_categories pc ON c.id = pc.category_id
                           WHERE pc.page_id = ?");
    $stmt->execute([$page_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_category($id) {
    global $db;
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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
?>
