<?php
/**
 * File: user/profile.php
 * Fungsi: Jembatan kompatibilitas rute Pengaturan Profil menuju Front Controller.
 */
$_GET['page'] = 'profile';
require dirname(__DIR__) . '/index.php';
