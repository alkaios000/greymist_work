<?php
/**
 * File: admin/login.php
 * Fungsi: Jembatan kompatibilitas Login Admin menuju Front Controller.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_GET['action'] = 'admin_login';
} else {
    $_GET['page'] = 'admin_login';
}
require dirname(__DIR__) . '/index.php';
