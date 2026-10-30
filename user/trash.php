<?php
/**
 * File: user/trash.php
 * Fungsi: Jembatan kompatibilitas rute Tempat Sampah menuju Front Controller.
 */
$_GET['page'] = 'trash';
require dirname(__DIR__) . '/index.php';
