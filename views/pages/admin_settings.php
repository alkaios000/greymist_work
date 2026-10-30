<?php
/**
 * File: views/pages/admin_settings.php
 * Fungsi: Tampilan halaman Pengaturan Sistem pada Panel Administrator.
 */

$page_title    = 'Pengaturan Sistem';
$page_subtitle = 'Konfigurasi informasi situs, kuota pengguna, dan mode pemeliharaan';
$active_page   = 'admin_settings';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';
?>

<div class="admin-two-col">
    <section class="panel-card">
        <h3 style="font-size: 1rem; margin-bottom: 14px;">
            <i class="fas fa-sliders-h" style="color: var(--btn-blue); margin-right: 6px;"></i>
            Konfigurasi Umum Platform
        </h3>

        <form method="POST" action="<?= e(action_url('admin_save_settings')) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="site_name">Nama Situs</label>
                <input type="text" class="form-control" id="site_name" name="site_name" value="<?= e($site_settings['site_name'] ?? 'Greymist') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="site_description">Deskripsi Situs</label>
                <input type="text" class="form-control" id="site_description" name="site_description" value="<?= e($site_settings['site_description'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="max_projects_user">Batas Maksimal Proyek per Pengguna</label>
                <input type="number" class="form-control" id="max_projects_user" name="max_projects_user" min="1" max="500" value="<?= (int)($site_settings['max_projects_user'] ?? 10) ?>">
            </div>

            <div class="switch-row">
                <div>
                    <div style="font-weight: 600; font-size: 0.85rem;">Izinkan Pendaftaran Pengguna Baru</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Jika dimatikan, hanya Admin yang dapat menambahkan akun baru.</div>
                </div>
                <input type="checkbox" name="allow_registrations" value="1" <?= !empty($site_settings['allow_registrations']) ? 'checked' : '' ?>>
            </div>

            <div class="switch-row" style="margin-bottom: 14px;">
                <div>
                    <div style="font-weight: 600; font-size: 0.85rem;">Mode Pemeliharaan (Maintenance Mode)</div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Hanya Administrator yang dapat mengakses aplikasi saat aktif.</div>
                </div>
                <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($site_settings['maintenance_mode']) ? 'checked' : '' ?>>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-save"></i>
                <span>Simpan Pengaturan</span>
            </button>
        </form>
    </section>

    <section class="panel-card">
        <h3 style="font-size: 1rem; margin-bottom: 14px;">
            <i class="fas fa-server" style="color: var(--btn-blue); margin-right: 6px;"></i>
            Informasi Lingkungan Server
        </h3>

        <div class="switch-row">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Versi PHP</span>
            <span class="badge badge-blue"><?= e(PHP_VERSION) ?></span>
        </div>
        <div class="switch-row">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Database Engine</span>
            <span class="badge badge-muted">MySQL / PDO</span>
        </div>
        <div class="switch-row">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Zona Waktu Aktif</span>
            <span class="badge badge-muted"><?= e(date_default_timezone_get()) ?></span>
        </div>
        <div class="switch-row">
            <span style="font-size: 0.85rem; color: var(--text-secondary);">Total Ruang Basis Data</span>
            <span class="badge badge-published"><?= (int)($platform_stats['total_projects'] ?? 0) ?> Proyek / <?= (int)($platform_stats['total_frames'] ?? 0) ?> Frame</span>
        </div>

        <form method="POST" action="<?= e(action_url('admin_clear_cache')) ?>" style="margin-top: 16px;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-soft-blue btn-sm">
                <i class="fas fa-broom"></i>
                <span>Segarkan Cache Sesi</span>
            </button>
        </form>
    </section>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

