<?php
/**
 * File: views/layouts/header.php
 * Fungsi: Menyediakan bagian pembuka HTML, meta tag, pemuatan CSS modular,
 *         sidebar navigasi, dan bilah header atas putih bersih (#FFFFFF).
 */

$page_title    = $page_title ?? ($site_settings['site_name'] ?? 'Greymist');
$page_subtitle = $page_subtitle ?? '';
$active_page   = $active_page ?? 'dashboard';
$is_admin_view = $is_admin_view ?? false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — <?= e($site_settings['site_name'] ?? 'Greymist') ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/scrollbar.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/layout.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/buttons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/cards.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/forms.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/tables.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/modals.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/badges.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/profile.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/about.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/admin.css')) ?>">
</head>
<body>
<div class="app-shell">
    <?php require ROOT_PATH . '/views/layouts/sidebar.php'; ?>

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-main">
        <header class="app-header">
            <div class="header-left">
                <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Buka Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-breadcrumbs">
                    <span class="crumb-brand"><?= e($site_settings['site_name'] ?? 'Greymist') ?></span>
                    <span class="crumb-separator">/</span>
                    <h1 class="header-title"><?= e($page_title) ?></h1>
                </div>
            </div>

            <div class="header-right">
                <div class="header-telemetry-badge" title="Status Jaringan & Engine">
                    <span class="status-indicator-dot"></span>
                    <span>Operasional</span>
                </div>

                <div class="header-time-chip" id="headerLiveClock">
                    <?= date('H:i') ?>
                </div>

                <?php if (!empty($header_actions_html)): ?>
                    <?= $header_actions_html ?>
                <?php elseif (!$is_admin_view): ?>
                    <form method="POST" action="<?= e(action_url('new_project')) ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span>+ Proyek Baru</span>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(route_url('projects')) ?>" class="btn btn-outline btn-sm">Workspace</a>
                    <a href="<?= e(route_url('dashboard')) ?>" class="btn btn-soft-blue btn-sm">Area Pengguna</a>
                <?php endif; ?>
            </div>
        </header>

        <main class="app-content">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success" data-auto-dismiss="true">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <span><?= e($_SESSION['flash_success']) ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-error" data-auto-dismiss="true">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= e($_SESSION['flash_error']) ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

