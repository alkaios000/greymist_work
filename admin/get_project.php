<?php
/**
 * File: admin/get_project.php
 * Fungsi: Jembatan kompatibilitas API JSON detail proyek Admin menuju Front Controller.
 */
$_GET['action'] = 'admin_get_project';
require dirname(__DIR__) . '/index.php';
