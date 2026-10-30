<?php
/**
 * File: middlewares/csrf_middleware.php
 * Fungsi: Memvalidasi token CSRF pada request POST untuk mencegah serangan Cross-Site Request Forgery.
 */

if (!function_exists('verify_csrf_middleware')) {
    /**
     * Memeriksa token CSRF dari body form ($_POST['csrf_token']) atau header HTTP (X-CSRF-Token).
     */
    function verify_csrf_middleware(bool $is_json_api = false): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Jika token dikirimkan, wajib valid. Jika form standar memiliki session csrf_token dan mengirimkannya, periksa kecocokannya.
        if ($token !== null && !verify_csrf_token($token)) {
            if ($is_json_api) {
                json_response(['success' => false, 'message' => 'Sesi keamanan (CSRF) tidak valid. Silakan muat ulang halaman.'], 403);
            }
            http_response_code(403);
            require ROOT_PATH . '/views/errors/403.php';
            exit;
        }
    }
}

