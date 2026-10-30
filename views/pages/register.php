<?php
/**
 * File: views/pages/register.php
 * Fungsi: Tampilan halaman Pendaftaran Pengguna Baru (Pure White & Soft Blue).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — <?= e($site_settings['site_name'] ?? 'Greymist') ?></title>
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/scrollbar.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/buttons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/forms.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/modals.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/auth.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-container auth-wide">
        <div class="auth-brand">
            <span class="auth-brand-icon"><i class="fas fa-layer-group"></i></span>
            <span class="auth-brand-text"><?= e($site_settings['site_name'] ?? 'Greymist') ?></span>
        </div>

        <div class="auth-header">
            <h1>Buat Akun Greymist</h1>
            <p>Mulai rancang dan bangun antarmuka web secara visual.</p>
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

        <form method="POST" action="<?= e(action_url('register')) ?>">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nickname">Nama Panggilan (Nickname)</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-id-badge input-icon"></i>
                        <input type="text" class="form-control" id="nickname" name="nickname" placeholder="Contoh: Budi" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-at input-icon"></i>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Contoh: budi_design" required>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Alamat Email</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" class="form-control" id="email" name="email" placeholder="nama@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Nomor Telepon</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="08123456789" required>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmasi Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fas fa-check-double input-icon"></i>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required minlength="6">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px; padding: 10px 16px;">
                <i class="fas fa-user-plus"></i>
                <span>Daftar Sekarang</span>
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="<?= e(route_url('login')) ?>">Masuk di sini</a>
        </div>
    </div>
</div>
</body>
</html>

