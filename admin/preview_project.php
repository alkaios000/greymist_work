<?php
/**
 * File: admin/preview_project.php
 * Fungsi: Jembatan kompatibilitas Pratinjau Proyek Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_preview_project';
require dirname(__DIR__) . '/index.php';
