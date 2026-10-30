<?php
/**
 * File: session/logout.php
 * Fungsi: Jembatan kompatibilitas rute logout menuju Front Controller (index.php).
 */
$_GET['action'] = 'logout';
require dirname(__DIR__) . '/index.php';
