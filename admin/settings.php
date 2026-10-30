<?php
/**
 * File: admin/settings.php
 * Fungsi: Jembatan kompatibilitas Pengaturan Sistem Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_settings';
require dirname(__DIR__) . '/index.php';
