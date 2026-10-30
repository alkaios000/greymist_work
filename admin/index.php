<?php
/**
 * File: admin/index.php
 * Fungsi: Jembatan kompatibilitas Dashboard Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_dashboard';
require dirname(__DIR__) . '/index.php';
