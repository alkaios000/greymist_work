<?php
/**
 * File: admin/get_user.php
 * Fungsi: Jembatan kompatibilitas API JSON detail pengguna Admin menuju Front Controller.
 */
$_GET['action'] = 'admin_get_user';
require dirname(__DIR__) . '/index.php';
