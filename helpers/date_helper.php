<?php
/**
 * File: helpers/date_helper.php
 * Fungsi: Pemformatan tanggal dan waktu agar konsisten di seluruh tampilan aplikasi.
 */

if (!function_exists('format_date')) {
    /**
     * Memformat string tanggal SQL menjadi format tanggal yang mudah dibaca (contoh: 30 Oct 2026).
     */
    function format_date(?string $date_string, string $format = 'M d, Y'): string
    {
        if (empty($date_string)) {
            return 'N/A';
        }
        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return 'N/A';
        }
        return date($format, $timestamp);
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Memformat string datetime SQL menjadi tanggal beserta jam (contoh: 30 Oct 2026, 14:30).
     */
    function format_datetime(?string $date_string, string $format = 'M d, Y H:i'): string
    {
        if (empty($date_string)) {
            return 'Never';
        }
        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return 'Never';
        }
        return date($format, $timestamp);
    }
}

