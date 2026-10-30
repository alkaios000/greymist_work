<?php
/**
 * File: database/migrate.php
 * Fungsi: Menjalankan seluruh file migrasi SQL secara berurutan.
 *         Hanya dapat dijalankan melalui CLI atau oleh Administrator yang sedang login.
 */

define('ALLOW_DB_BOOT_WITHOUT_SCHEMA', true);
require_once dirname(__DIR__) . '/config/config.php';

$is_cli = (php_sapi_name() === 'cli');
if (!$is_cli && !is_admin()) {
    http_response_code(403);
    die('Akses ditolak: Migrasi hanya dapat dijalankan via CLI atau oleh Administrator.');
}

$db_name = env('DB_NAME', 'GREYMISTBETA');
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `{$db_name}`");

$migration_files = glob(__DIR__ . '/migrations/*.sql');
sort($migration_files);

foreach ($migration_files as $file) {
    $filename = basename($file);
    $sql = file_get_contents($file);
    if ($sql !== false && trim($sql) !== '') {
        $pdo->exec($sql);
        echo "[Migrate] Berhasil menerapkan: {$filename}\n";
    }
}

// Pastikan kolom tambahan tersedia pada gm_projects jika tabel sudah ada sebelumnya
$columns_stmt = $pdo->query("SHOW COLUMNS FROM `gm_projects`");
$existing_cols = array_column($columns_stmt->fetchAll(), 'Field');

if (!in_array('color_tokens', $existing_cols, true)) {
    $pdo->exec("ALTER TABLE `gm_projects` ADD COLUMN `color_tokens` JSON DEFAULT NULL");
    echo "[Migrate] Menambahkan kolom color_tokens ke gm_projects.\n";
}
if (!in_array('bg_color_settings', $existing_cols, true)) {
    $pdo->exec("ALTER TABLE `gm_projects` ADD COLUMN `bg_color_settings` JSON DEFAULT NULL");
    echo "[Migrate] Menambahkan kolom bg_color_settings ke gm_projects.\n";
}

echo "[Migrate] Seluruh migrasi selesai dijalankan.\n";

