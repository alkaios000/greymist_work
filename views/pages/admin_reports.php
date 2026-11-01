<?php
/**
 * File: views/pages/admin_reports.php
 * Fungsi: Tampilan halaman Laporan & Moderasi pada Panel Administrator.
 */

$page_title    = 'Laporan & Moderasi';
$page_subtitle = '';
$active_page   = 'admin_reports';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';
?>

<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-flag"></i></div>
        <div class="stat-info">
            <div class="stat-value">0</div>
            <div class="stat-label">Laporan Menunggu</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['published_count'] ?? 0) ?></div>
            <div class="stat-label">Proyek Publik Terverifikasi</div>
        </div>
    </div>
</section>

<div class="empty-state">
    <div class="empty-state-icon"><i class="fas fa-shield-alt"></i></div>
    <h3>Tidak Ada Laporan Pelanggaran</h3>
    <p>Seluruh proyek yang dipublikasikan oleh komunitas saat ini dalam kondisi aman dan tidak memiliki antrean laporan.</p>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

