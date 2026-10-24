<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $redirect = file_exists('session/login.php') ? 'session/login.php' : '../session/login.php';
    header("Location: $redirect");
    exit;
}
