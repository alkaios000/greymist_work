<?php
/**
 * File: admin/users.php
 * Fungsi: Jembatan kompatibilitas Manajemen Pengguna Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_users';
require dirname(__DIR__) . '/index.php';
