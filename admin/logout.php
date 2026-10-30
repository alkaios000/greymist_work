<?php
/**
 * File: admin/logout.php
 * Fungsi: Jembatan kompatibilitas Logout Admin menuju Front Controller.
 */
$_GET['action'] = 'admin_logout';
require dirname(__DIR__) . '/index.php';
