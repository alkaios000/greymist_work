<?php
/**
 * File: user/project.php
 * Fungsi: Jembatan kompatibilitas rute Proyek Saya menuju Front Controller.
 */
$_GET['page'] = 'projects';
require dirname(__DIR__) . '/index.php';
