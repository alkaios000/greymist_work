<?php
/**
 * File: models/chart_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_user_charts (Chart Kustom Pengguna).
 */

if (!function_exists('ensure_charts_table')) {
    /**
     * Memastikan tabel gm_user_charts tersedia di database.
     */
    function ensure_charts_table(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) return;

        $sql = "
            CREATE TABLE IF NOT EXISTS gm_user_charts (
                chart_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                chart_name VARCHAR(150) NOT NULL,
                chart_type VARCHAR(50) NOT NULL DEFAULT 'bar',
                library_cdn VARCHAR(100) NOT NULL DEFAULT 'chartjs',
                cdn_url TEXT NULL,
                chart_code MEDIUMTEXT NOT NULL,
                chart_data_json MEDIUMTEXT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_charts_uid (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $pdo->exec($sql);
            $checked = true;
        } catch (Throwable $e) {
            error_log("Gagal membuat tabel gm_user_charts: " . $e->getMessage());
        }
    }
}

if (!function_exists('get_user_charts')) {
    /**
     * Mengambil daftar chart yang disimpan oleh pengguna tertentu.
     */
    function get_user_charts(PDO $pdo, int $user_id): array
    {
        ensure_charts_table($pdo);
        $stmt = $pdo->prepare("
            SELECT chart_id, user_id, chart_name, chart_type, library_cdn, cdn_url, chart_code, chart_data_json, created_at, updated_at
            FROM gm_user_charts
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('get_user_chart_by_id')) {
    /**
     * Mengambil satu data chart berdasarkan chart_id dan user_id.
     */
    function get_user_chart_by_id(PDO $pdo, int $chart_id, int $user_id): ?array
    {
        ensure_charts_table($pdo);
        $stmt = $pdo->prepare("
            SELECT chart_id, user_id, chart_name, chart_type, library_cdn, cdn_url, chart_code, chart_data_json, created_at, updated_at
            FROM gm_user_charts
            WHERE chart_id = ? AND user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$chart_id, $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('create_user_chart')) {
    /**
     * Menyimpan chart kustom baru milik pengguna.
     */
    function create_user_chart(
        PDO $pdo,
        int $user_id,
        string $chart_name,
        string $chart_type,
        string $library_cdn,
        string $cdn_url,
        string $chart_code,
        ?string $chart_data_json = null
    ): int {
        ensure_charts_table($pdo);
        $stmt = $pdo->prepare("
            INSERT INTO gm_user_charts (user_id, chart_name, chart_type, library_cdn, cdn_url, chart_code, chart_data_json, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $user_id,
            $chart_name,
            $chart_type,
            $library_cdn,
            $cdn_url,
            $chart_code,
            $chart_data_json
        ]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('update_user_chart')) {
    /**
     * Memperbarui chart kustom milik pengguna.
     */
    function update_user_chart(
        PDO $pdo,
        int $chart_id,
        int $user_id,
        string $chart_name,
        string $chart_type,
        string $library_cdn,
        string $cdn_url,
        string $chart_code,
        ?string $chart_data_json = null
    ): bool {
        ensure_charts_table($pdo);
        $stmt = $pdo->prepare("
            UPDATE gm_user_charts
            SET chart_name = ?,
                chart_type = ?,
                library_cdn = ?,
                cdn_url = ?,
                chart_code = ?,
                chart_data_json = ?,
                updated_at = NOW()
            WHERE chart_id = ? AND user_id = ?
        ");
        $stmt->execute([
            $chart_name,
            $chart_type,
            $library_cdn,
            $cdn_url,
            $chart_code,
            $chart_data_json,
            $chart_id,
            $user_id
        ]);
        return $stmt->rowCount() >= 0;
    }
}

if (!function_exists('delete_user_chart')) {
    /**
     * Menghapus chart kustom milik pengguna berdasarkan chart_id.
     */
    function delete_user_chart(PDO $pdo, int $chart_id, int $user_id): bool
    {
        ensure_charts_table($pdo);
        $stmt = $pdo->prepare("DELETE FROM gm_user_charts WHERE chart_id = ? AND user_id = ?");
        $stmt->execute([$chart_id, $user_id]);
        return $stmt->rowCount() > 0;
    }
}

