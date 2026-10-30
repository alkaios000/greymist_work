<?php
/**
 * File: models/component_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_components.
 */

if (!function_exists('get_user_components')) {
    /**
     * Mengambil daftar komponen kustom milik pengguna.
     */
    function get_user_components(PDO $pdo, int $user_id): array
    {
        $stmt = $pdo->prepare("
            SELECT component_id, component_name, html_content, category, created_at
            FROM gm_components
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('create_user_component')) {
    /**
     * Menyimpan komponen HTML kustom baru milik pengguna.
     */
    function create_user_component(PDO $pdo, int $user_id, string $name, string $html_content, string $category = 'Custom'): int
    {
        $stmt = $pdo->prepare("
            INSERT INTO gm_components (user_id, component_name, html_content, category, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $name, $html_content, $category]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('delete_user_component')) {
    /**
     * Menghapus komponen kustom milik pengguna berdasarkan component_id.
     */
    function delete_user_component(PDO $pdo, int $component_id, int $user_id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM gm_components WHERE component_id = ? AND user_id = ?");
        $stmt->execute([$component_id, $user_id]);
        return $stmt->rowCount() > 0;
    }
}

