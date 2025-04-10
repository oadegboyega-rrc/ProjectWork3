<?php
session_start();

// destroy all session
session_unset();
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit();
?>