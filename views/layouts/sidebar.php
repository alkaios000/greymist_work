<?php
/**
 * File: views/layouts/sidebar.php
 * Fungsi: Menampilkan bilah navigasi samping (Sidebar) untuk Pengguna maupun Administrator.
 */

$active_page   = $active_page ?? 'dashboard';
$is_admin_view = $is_admin_view ?? false;
$display_name  = $current_user['nickname'] ?: ($current_user['username'] ?? 'User');
$initial       = strtoupper(substr($display_name, 0, 1));
$avatar_url    = !empty($current_user['profile']) ? upload_url($current_user['profile']) : '';
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <a href="<?= e(route_url($is_admin_view ? 'admin_dashboard' : 'dashboard')) ?>" class="sidebar-brand-link">
            <span><?= e($site_settings['site_name'] ?? 'Greymist') ?></span>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php if ($is_admin_view): ?>
            <div class="sidebar-section-label">Manajemen Sistem</div>
            <a href="<?= e(route_url('admin_dashboard')) ?>" class="sidebar-link <?= $active_page === 'admin_dashboard' ? 'active' : '' ?>">
                <span>Dashboard Admin</span>
            </a>
            <a href="<?= e(route_url('admin_users')) ?>" class="sidebar-link <?= $active_page === 'admin_users' ? 'active' : '' ?>">
                <span>Manajemen Pengguna</span>
            </a>
            <a href="<?= e(route_url('admin_projects')) ?>" class="sidebar-link <?= $active_page === 'admin_projects' ? 'active' : '' ?>">
                <span>Semua Proyek</span>
            </a>
            <a href="<?= e(route_url('admin_templates')) ?>" class="sidebar-link <?= $active_page === 'admin_templates' ? 'active' : '' ?>">
                <span>Kelola Template</span>
            </a>
            <a href="<?= e(route_url('admin_reports')) ?>" class="sidebar-link <?= $active_page === 'admin_reports' ? 'active' : '' ?>">
                <span>Laporan & Moderasi</span>
            </a>
            <a href="<?= e(route_url('admin_settings')) ?>" class="sidebar-link <?= $active_page === 'admin_settings' ? 'active' : '' ?>">
                <span>Pengaturan Sistem</span>
            </a>

            <div class="sidebar-section-label">Pintasan</div>
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-link">
                <span>Kembali ke Dashboard</span>
            </a>
        <?php else: ?>
            <div class="sidebar-section-label">Menu Utama</div>
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <span>Dashboard</span>
            </a>
            <a href="<?= e(route_url('projects')) ?>" class="sidebar-link <?= $active_page === 'projects' ? 'active' : '' ?>">
                <span>Proyek Saya</span>
            </a>
            <a href="<?= e(route_url('explore')) ?>" class="sidebar-link <?= $active_page === 'explore' ? 'active' : '' ?>">
                <span>Eksplorasi</span>
            </a>
            <a href="<?= e(route_url('templates')) ?>" class="sidebar-link <?= $active_page === 'templates' ? 'active' : '' ?>">
                <span>Template</span>
            </a>
            <a href="<?= e(route_url('trash')) ?>" class="sidebar-link <?= $active_page === 'trash' ? 'active' : '' ?>">
                <span>Tempat Sampah</span>
            </a>

            <div class="sidebar-section-label">Akun & Informasi</div>
            <a href="<?= e(route_url('profile')) ?>" class="sidebar-link <?= $active_page === 'profile' ? 'active' : '' ?>">
                <span>Pengaturan Profil</span>
            </a>
            <a href="<?= e(route_url('about')) ?>" class="sidebar-link <?= $active_page === 'about' ? 'active' : '' ?>">
                <span>Tentang Greymist</span>
            </a>

            <?php if (is_admin()): ?>
                <div class="sidebar-section-label">Administrator</div>
                <a href="<?= e(route_url('admin_dashboard')) ?>" class="sidebar-link">
                    <span>Panel Admin</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <a href="<?= e(route_url('profile')) ?>" class="sidebar-user-info">
                <div class="sidebar-avatar">
                    <?php if ($avatar_url !== ''): ?>
                        <img src="<?= e($avatar_url) ?>" alt="<?= e($display_name) ?>">
                    <?php else: ?>
                        <span><?= e($initial) ?></span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-user-meta">
                    <div class="sidebar-user-name"><?= e($display_name) ?></div>
                    <div class="sidebar-user-role">@<?= e($current_user['username'] ?? 'user') ?></div>
                </div>
            </a>

            <a href="<?= e(action_url($is_admin_view ? 'admin_logout' : 'logout')) ?>" class="btn-icon danger" title="Keluar Sesi">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

