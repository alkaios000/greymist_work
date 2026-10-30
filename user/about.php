<?php
/**
 * File: user/about.php
 * Fungsi: Jembatan kompatibilitas rute Tentang Greymist menuju Front Controller.
 */
$_GET['page'] = 'about';
require dirname(__DIR__) . '/index.php';
