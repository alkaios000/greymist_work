<?php
/**
 * File: views/pages/admin_login.php
 * Fungsi: Tampilan halaman Login khusus Administrator (Pure White & Soft Blue).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator — <?= e($site_settings['site_name'] ?? 'Greymist') ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/scrollbar.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/buttons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/forms.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/modals.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/badges.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/auth.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-container">
        <div class="auth-brand">
            <span class="auth-brand-icon"><i class="fas fa-shield-alt"></i></span>
            <span class="auth-brand-text"><?= e($site_settings['site_name'] ?? 'Greymist') ?></span>
            <span class="badge badge-blue">Admin</span>
        </div>

        <div class="auth-header">
            <h1>Panel Administrator</h1>
            <p>Masuk menggunakan akun Administrator untuk mengelola sistem.</p>
        </div>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-error">
                <div class="alert-content">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($_SESSION['flash_error']) ?></span>
                </div>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <form method="POST" action="<?= e(action_url('admin_login')) ?>">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label" for="username">Username Admin</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-user-shield input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username admin" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="auth-input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password admin" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 6px; padding: 10px 16px;">
                <i class="fas fa-sign-in-alt"></i>
                <span>Masuk ke Panel Admin</span>
            </button>
        </form>

        <div class="auth-footer">
            <a href="<?= e(route_url('login')) ?>"><i class="fas fa-arrow-left"></i> Kembali ke Login Pengguna</a>
        </div>
    </div>
</div>
</body>
</html>

