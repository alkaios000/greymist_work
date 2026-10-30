<?php
/**
 * File: views/pages/about.php
 * Fungsi: Tampilan halaman Tentang Greymist (Informasi Platform & Fitur Utama).
 */

$page_title    = 'Tentang Greymist';
$page_subtitle = 'Platform visual web builder dan kolaborasi desain antarmuka';
$active_page   = 'about';

require ROOT_PATH . '/views/layouts/header.php';
?>

<section class="about-hero">
    <div style="max-width: 680px;">
        <span class="badge badge-blue" style="margin-bottom: 10px;"><i class="fas fa-bolt"></i> Visual Web Workspace</span>
        <h2 style="font-size: 1.5rem; margin-bottom: 8px;"><?= e($site_settings['site_name'] ?? 'Greymist') ?> Design Platform</h2>
        <p>
            <?= e($site_settings['site_description'] ?? 'Greymist memudahkan desainer dan pengembang membangun tata letak web secara visual, mengelola token warna, serta membagikan template siap pakai.') ?>
        </p>
    </div>
    <div>
        <form method="POST" action="<?= e(action_url('new_project')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-rocket"></i>
                <span>Mulai Desain Baru</span>
            </button>
        </form>
    </div>
</section>

<!-- Statistik Platform -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_users'] ?? 0) ?></div>
            <div class="stat-label">Pengguna Terdaftar</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-folder-open"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_projects'] ?? 0) ?></div>
            <div class="stat-label">Total Proyek Dibuat</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-border-all"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_frames'] ?? 0) ?></div>
            <div class="stat-label">Total Frame Kanvas</div>
        </div>
    </div>
</section>

<!-- Fitur Unggulan -->
<section class="about-features-grid">
    <div class="feature-item-card">
        <div class="feature-item-icon"><i class="fas fa-vector-square"></i></div>
        <h4>Visual Drag & Drop Canvas</h4>
        <p>Susun struktur elemen DOM, flexbox, dan tipografi secara langsung di atas kanvas putih bersih.</p>
    </div>
    <div class="feature-item-card">
        <div class="feature-item-icon"><i class="fas fa-palette"></i></div>
        <h4>Manajemen Color Tokens</h4>
        <p>Simpan variabel warna proyek secara konsisten dan gunakan ulang di berbagai komponen.</p>
    </div>
    <div class="feature-item-card">
        <div class="feature-item-icon"><i class="fas fa-cubes"></i></div>
        <h4>Komponen Kustom</h4>
        <p>Simpan blok elemen yang sering Anda gunakan ke dalam pustaka komponen pribadi.</p>
    </div>
    <div class="feature-item-card">
        <div class="feature-item-icon"><i class="fas fa-code"></i></div>
        <h4>Ekspor HTML & CSS Bersih</h4>
        <p>Ubah rancangan visual menjadi struktur kode standar web yang siap dikembangkan lebih lanjut.</p>
    </div>
</section>

<section class="panel-card">
    <h3 style="font-size: 1rem; margin-bottom: 6px;">Arsitektur & Teknologi</h3>
    <p style="font-size: 0.85rem;">Dibangun menggunakan arsitektur MVC-Hybrid + Front Controller dengan keamanan berlapis (PDO Prepared Statements, proteksi CSRF, dan sanitasi XSS).</p>
    <div class="tech-badges-list">
        <span class="badge badge-blue"><i class="fab fa-php"></i> PHP 8 Native</span>
        <span class="badge badge-blue"><i class="fas fa-database"></i> MySQL PDO</span>
        <span class="badge badge-blue"><i class="fab fa-html5"></i> HTML5 Semantic</span>
        <span class="badge badge-blue"><i class="fab fa-css3-alt"></i> Modular CSS3</span>
        <span class="badge badge-blue"><i class="fab fa-js"></i> ES6 Modules</span>
    </div>
</section>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

