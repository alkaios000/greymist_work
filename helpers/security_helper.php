<?php
/**
 * File: helpers/security_helper.php
 * Fungsi: Sanitasi output HTML (pencegah XSS) dan pembuatan/validasi token CSRF.
 */

if (!function_exists('e')) {
    /**
     * Membersihkan string sebelum ditampilkan ke HTML untuk mencegah serangan XSS.
     */
    function e($value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Menghasilkan atau mengambil token CSRF aktif dalam sesi pengguna.
     */
    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Menghasilkan elemen input hidden berisi token CSRF untuk disisipkan di dalam <form>.
     */
    function csrf_field(): string
    {
        $token = e(csrf_token());
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Memvalidasi kecocokan token CSRF dari form atau header HTTP.
     */
    function verify_csrf_token(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals((string)$_SESSION['csrf_token'], (string)$token);
    }
}

