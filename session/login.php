<?php
/**
 * File: session/login.php
 * Fungsi: Jembatan kompatibilitas rute login menuju Front Controller (index.php).
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['action'] = 'login';
} else {
    $_GET['page'] = 'login';
}
require dirname(__DIR__) . '/index.php';
