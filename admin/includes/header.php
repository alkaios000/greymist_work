<?php
/**
 * File: admin/includes/header.php
 * Fungsi: Wrapper kompatibilitas header Admin menuju views/layouts/header.php.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
$is_admin_view = true;
require dirname(__DIR__, 2) . '/views/layouts/header.php';
