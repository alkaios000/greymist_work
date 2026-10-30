<?php
/**
 * File: middlewares/role_middleware.php
 * Fungsi: Memeriksa otorisasi peran (role) pengguna seperti hak akses Administrator.
 */

if (!function_exists('is_admin')) {
    /**
     * Memeriksa apakah pengguna yang sedang login memiliki hak akses admin.
     */
    function is_admin(): bool
    {
        return is_logged_in() && isset($_SESSION['is_admin']) && (int)$_SESSION['is_admin'] === 1;
    }
}

if (!function_exists('require_admin')) {
    /**
     * Memastikan hanya Administrator yang dapat mengakses rute atau aksi ini.
     */
    function require_admin(bool $is_json_api = false): void
    {
        if (!is_admin()) {
            if ($is_json_api) {
                json_response(['success' => false, 'error' => 'Forbidden', 'message' => 'Akses khusus Administrator.'], 403);
            }
            redirect(route_url('admin_login'));
        }
    }
}

