<?php
/**
 * File: admin/projects.php
 * Fungsi: Jembatan kompatibilitas Manajemen Proyek Admin menuju Front Controller.
 */
$_GET['page'] = 'admin_projects';
require dirname(__DIR__) . '/index.php';
