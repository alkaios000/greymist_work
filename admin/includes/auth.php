<?php
session_start();

// Cek apakah user sudah login sebagai admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Redirect jika tidak login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Untuk halaman yang membutuhkan login admin
if (basename($_SERVER['PHP_SELF']) != 'login.php') {
    requireAdminLogin();
}
?>