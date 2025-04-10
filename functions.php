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
?>
