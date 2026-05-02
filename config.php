<?php
// Secure this file!
session_start();

$admin_user = "admin";
$admin_pass = "your_strong_password_here"; // CHANGE THIS

// Logic to check if user is logged in
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Handle Login
if (isset($_POST['login'])) {
    if ($_POST['user'] === $admin_user && $_POST['pass'] === $admin_pass) {
        $_SESSION['logged_in'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>