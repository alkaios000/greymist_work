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
            <span class="sidebar-brand-icon">
                <i class="fas fa-layer-group"></i>
            </span>
            <span><?= e($site_settings['site_name'] ?? 'Greymist') ?></span>
        </a>
        <?php if ($is_admin_view): ?>
            <span class="badge badge-blue">Admin</span>
        <?php endif; ?>
    </div>

    <nav class="sidebar-nav">
        <?php if ($is_admin_view): ?>
            <div class="sidebar-section-label">Manajemen Sistem</div>
            <a href="<?= e(route_url('admin_dashboard')) ?>" class="sidebar-link <?= $active_page === 'admin_dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-pie"></i>
                <span>Dashboard Admin</span>
            </a>
            <a href="<?= e(route_url('admin_users')) ?>" class="sidebar-link <?= $active_page === 'admin_users' ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Manajemen Pengguna</span>
            </a>
            <a href="<?= e(route_url('admin_projects')) ?>" class="sidebar-link <?= $active_page === 'admin_projects' ? 'active' : '' ?>">
                <i class="fas fa-folder-open"></i>
                <span>Semua Proyek</span>
            </a>
            <a href="<?= e(route_url('admin_templates')) ?>" class="sidebar-link <?= $active_page === 'admin_templates' ? 'active' : '' ?>">
                <i class="fas fa-clone"></i>
                <span>Kelola Template</span>
            </a>
            <a href="<?= e(route_url('admin_reports')) ?>" class="sidebar-link <?= $active_page === 'admin_reports' ? 'active' : '' ?>">
                <i class="fas fa-flag"></i>
                <span>Laporan & Moderasi</span>
            </a>
            <a href="<?= e(route_url('admin_settings')) ?>" class="sidebar-link <?= $active_page === 'admin_settings' ? 'active' : '' ?>">
                <i class="fas fa-sliders-h"></i>
                <span>Pengaturan Sistem</span>
            </a>

            <div class="sidebar-section-label">Pintasan</div>
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-link">
                <i class="fas fa-arrow-left"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        <?php else: ?>
            <div class="sidebar-section-label">Menu Utama</div>
            <a href="<?= e(route_url('dashboard')) ?>" class="sidebar-link <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= e(route_url('projects')) ?>" class="sidebar-link <?= $active_page === 'projects' ? 'active' : '' ?>">
                <i class="fas fa-folder"></i>
                <span>Proyek Saya</span>
            </a>
            <a href="<?= e(route_url('explore')) ?>" class="sidebar-link <?= $active_page === 'explore' ? 'active' : '' ?>">
                <i class="fas fa-compass"></i>
                <span>Eksplorasi</span>
            </a>
            <a href="<?= e(route_url('templates')) ?>" class="sidebar-link <?= $active_page === 'templates' ? 'active' : '' ?>">
                <i class="fas fa-layer-group"></i>
                <span>Template</span>
            </a>
            <a href="<?= e(route_url('trash')) ?>" class="sidebar-link <?= $active_page === 'trash' ? 'active' : '' ?>">
                <i class="fas fa-trash-alt"></i>
                <span>Tempat Sampah</span>
            </a>

            <div class="sidebar-section-label">Akun & Informasi</div>
            <a href="<?= e(route_url('profile')) ?>" class="sidebar-link <?= $active_page === 'profile' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i>
                <span>Pengaturan Profil</span>
            </a>
            <a href="<?= e(route_url('about')) ?>" class="sidebar-link <?= $active_page === 'about' ? 'active' : '' ?>">
                <i class="fas fa-info-circle"></i>
                <span>Tentang Greymist</span>
            </a>

            <?php if (is_admin()): ?>
                <div class="sidebar-section-label">Administrator</div>
                <a href="<?= e(route_url('admin_dashboard')) ?>" class="sidebar-link">
                    <i class="fas fa-shield-alt"></i>
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

