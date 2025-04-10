<?php
session_start();
require 'connect.php'; // Database connection

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is an admin
$admin = false;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $admin = true;
}
?>
