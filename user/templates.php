<?php
/**
 * File: user/templates.php
 * Fungsi: Jembatan kompatibilitas rute Template menuju Front Controller.
 */
$_GET['page'] = 'templates';
require dirname(__DIR__) . '/index.php';
