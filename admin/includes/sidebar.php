<?php
/**
 * File: admin/includes/sidebar.php
 * Fungsi: Wrapper kompatibilitas sidebar Admin menuju views/layouts/sidebar.php.
 */
require_once dirname(__DIR__, 2) . '/config/config.php';
$is_admin_view = true;
require dirname(__DIR__, 2) . '/views/layouts/sidebar.php';
