<?php
/**
 * File: user/explore.php
 * Fungsi: Jembatan kompatibilitas rute Eksplorasi menuju Front Controller.
 */
$_GET['page'] = 'explore';
require dirname(__DIR__) . '/index.php';
