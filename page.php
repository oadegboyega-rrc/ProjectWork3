<?php
require 'connect.php';

function addPage($title, $content, $category_id, $user_id) {
    global $db;
    $stmt = $db->prepare("INSERT INTO pages (title, content, category_id, user_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    return $stmt->execute([$title, $content, $category_id, $user_id]);
}

function editPage($id, $title, $content, $category_id) {
    global $db;
    $stmt = $pdo->prepare("UPDATE pages SET title = ?, content = ?, category_id = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$title, $content, $category_id, $id]);
}

function deletePage($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
    return $stmt->execute([$id]);
}

function getCategories() {
    global $db;
    $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPagesByCategory($category_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM pages WHERE category_id = ? ORDER BY created_at DESC");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoryName($category_id) {
    global $db;
    $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    return $stmt->fetchColumn();
}

function getPages($sortBy = 'created_at') {
    global $db;
    $stmt = $db->query("SELECT * FROM pages ORDER BY " . $sortBy . " DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>