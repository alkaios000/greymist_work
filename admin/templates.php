<?php
/**
 * File: admin/templates.php
 * Fungsi: Jembatan kompatibilitas Manajemen Template Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_templates';
require dirname(__DIR__) . '/index.php';
