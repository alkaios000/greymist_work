<?php
/**
 * File: user/dashboard.php
 * Fungsi: Jembatan kompatibilitas rute Dashboard Pengguna menuju Front Controller.
 */
$_GET['page'] = 'dashboard';
require dirname(__DIR__) . '/index.php';
