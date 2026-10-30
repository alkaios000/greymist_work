<?php
/**
 * File: models/asset_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_bg_images (aset gambar).
 */

if (!function_exists('get_user_bg_images')) {
    /**
     * Mengambil daftar gambar/aset yang diunggah oleh pengguna.
     */
    function get_user_bg_images(PDO $pdo, int $user_id): array
    {
        $stmt = $pdo->prepare("
            SELECT image_id, image, image_name, path, created_at
            FROM gm_bg_images
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_user_bg_image_by_id')) {
    /**
     * Mengambil satu data gambar berdasarkan image_id dan user_id.
     */
    function get_user_bg_image_by_id(PDO $pdo, int $image_id, int $user_id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM gm_bg_images WHERE image_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$image_id, $user_id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

if (!function_exists('create_user_bg_image')) {
    /**
     * Menyimpan metadata gambar unggahan baru ke tabel gm_bg_images.
     */
    function create_user_bg_image(PDO $pdo, int $user_id, string $filename, string $image_name, string $relative_path): int
    {
        $stmt = $pdo->prepare("
            INSERT INTO gm_bg_images (user_id, image, image_name, path, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $filename, $image_name, $relative_path]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('update_user_bg_image_name')) {
    /**
     * Mengubah nama tampilan gambar/aset milik pengguna.
     */
    function update_user_bg_image_name(PDO $pdo, int $image_id, int $user_id, string $new_name): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_bg_images SET image_name = ? WHERE image_id = ? AND user_id = ?");
        $stmt->execute([$new_name, $image_id, $user_id]);
        return $stmt->rowCount() >= 0;
    }
}

if (!function_exists('delete_user_bg_image')) {
    /**
     * Menghapus catatan gambar dari tabel gm_bg_images.
     */
    function delete_user_bg_image(PDO $pdo, int $image_id, int $user_id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM gm_bg_images WHERE image_id = ? AND user_id = ?");
        $stmt->execute([$image_id, $user_id]);
        return $stmt->rowCount() > 0;
    }
}

