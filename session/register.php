<?php
/**
 * File: session/register.php
 * Fungsi: Jembatan kompatibilitas rute registrasi menuju Front Controller (index.php).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['action'] = 'register';
} else {
    $_GET['page'] = 'register';
}
require dirname(__DIR__) . '/index.php';
