<?php
if (!defined('DB_DSN')) {
    define('DB_DSN', 'mysql:host=localhost;dbname=serverside');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'serveruser');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', 'gorgonzola7!');
}

try {
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>


