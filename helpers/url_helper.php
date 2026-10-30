<?php
/**
 * File: helpers/url_helper.php
 * Fungsi: Pembuatan URL rute Front Controller, path asset statis, dan pengalihan halaman (redirect).
 *         Mendukung penuh Virtual Host (https://greymist_work.test) maupun Subfolder (http://localhost/Greymist_work).
 */

if (!function_exists('base_url')) {
    /**
     * Menghasilkan URL root-relative atau absolut yang bersih tanpa double-slash atau backslash Windows.
     */
    function base_url(string $path = ''): string
    {
        $configured_base = trim((string)env('BASE_URL', ''));

        if ($configured_base !== '') {
            $base = rtrim(str_replace('\\', '/', $configured_base), '/');
        } else {
            // Normalisasi backslash Windows terlebih dahulu, lalu ambil dirname dan normalisasi kembali
            $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
            $raw_dir     = str_replace('\\', '/', dirname($script_name));
            $script_dir  = rtrim($raw_dir, '/');

            // Jika diakses dari sub-folder kompatibilitas (user/, admin/, session/, api/), naik 1 level ke root proyek
            if (preg_match('#/(user|admin|session|api)$#', $script_dir)) {
                $parent_dir = str_replace('\\', '/', dirname($script_dir));
                $script_dir = rtrim($parent_dir, '/');
            }

            if ($script_dir === '.' || $script_dir === '/') {
                $script_dir = '';
            }
            $base = $script_dir;
        }

        $clean_path = ltrim(str_replace('\\', '/', $path), '/');
        if ($clean_path === '') {
            return $base !== '' ? $base . '/' : '/';
        }

        return ($base !== '' ? $base : '') . '/' . $clean_path;
    }
}

if (!function_exists('route_url')) {
    /**
     * Membuat URL menuju halaman Front Controller (index.php?page=...).
     */
    function route_url(string $page, array $params = []): string
    {
        $query = array_merge(['page' => $page], $params);
        return base_url('index.php?' . http_build_query($query));
    }
}

if (!function_exists('action_url')) {
    /**
     * Membuat URL menuju aksi controller (index.php?action=...).
     */
    function action_url(string $action, array $params = []): string
    {
        $query = array_merge(['action' => $action], $params);
        return base_url('index.php?' . http_build_query($query));
    }
}

if (!function_exists('asset_url')) {
    /**
     * Membuat URL menuju file di dalam folder assets/.
     */
    function asset_url(string $path): string
    {
        return base_url('assets/' . ltrim(str_replace('\\', '/', $path), '/'));
    }
}

if (!function_exists('upload_url')) {
    /**
     * Menormalkan path gambar profil atau file unggahan agar valid diakses dari halaman mana pun.
     */
    function upload_url(?string $stored_path, string $default = ''): string
    {
        if (empty($stored_path)) {
            return $default !== '' ? base_url($default) : '';
        }
        $clean = preg_replace('#^(\.\./)+#', '', trim(str_replace('\\', '/', $stored_path)));
        if (str_starts_with($clean, 'http://') || str_starts_with($clean, 'https://') || str_starts_with($clean, 'data:')) {
            return $clean;
        }
        return base_url($clean);
    }
}

if (!function_exists('redirect')) {
    /**
     * Mengalihkan browser ke URL tujuan dan menghentikan eksekusi script.
     */
    function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('json_response')) {
    /**
     * Mengirimkan respons JSON terstandarisasi dan menghentikan eksekusi script.
     */
    function json_response($payload, int $status_code = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
