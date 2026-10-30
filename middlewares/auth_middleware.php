<?php
/**
 * File: middlewares/auth_middleware.php
 * Fungsi: Memeriksa status autentikasi pengguna sebelum mengakses halaman atau API.
 */

if (!function_exists('is_logged_in')) {
    /**
     * Memeriksa apakah pengguna saat ini sudah login.
     */
    function is_logged_in(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_id')) {
    /**
     * Mengambil ID pengguna yang sedang login, atau null jika belum login.
     */
    function current_user_id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

if (!function_exists('require_auth')) {
    /**
     * Memastikan pengguna sudah login. Jika belum, alihkan ke halaman login atau kirim 401 JSON.
     */
    function require_auth(bool $is_json_api = false): void
    {
        global $site_settings;

        // Periksa mode pemeliharaan (maintenance mode) untuk non-admin
        if (!empty($site_settings['maintenance_mode']) && empty($_SESSION['is_admin'])) {
            if ($is_json_api) {
                json_response(['success' => false, 'message' => 'Sistem sedang dalam pemeliharaan.'], 503);
            }
            http_response_code(503);
            require ROOT_PATH . '/views/errors/500.php';
            exit;
        }

        if (!is_logged_in()) {
            if ($is_json_api) {
                json_response(['success' => false, 'error' => 'Unauthorized', 'message' => 'Silakan login terlebih dahulu.'], 401);
            }
            redirect(route_url('login'));
        }
    }
}

if (!function_exists('require_guest')) {
    /**
     * Memastikan halaman hanya diakses oleh tamu (belum login), misalnya halaman login/register.
     */
    function require_guest(): void
    {
        if (is_logged_in()) {
            redirect(route_url('dashboard'));
        }
    }
}

