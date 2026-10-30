<?php
/**
 * File: views/errors/403.php
 * Fungsi: Halaman error HTTP 403 (Akses Ditolak / Forbidden).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Akses Ditolak — Greymist</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/buttons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/auth.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-container" style="text-align: center;">
        <div class="auth-brand">
            <span class="auth-brand-icon"><i class="fas fa-shield-alt"></i></span>
        </div>
        <h1 style="font-size: 2rem; margin-bottom: 6px;">403</h1>
        <h3 style="margin-bottom: 10px;">Akses Ditolak</h3>
        <p style="margin-bottom: 22px;">Maaf, Anda tidak memiliki izin untuk mengakses halaman atau sumber daya ini.</p>
        <a href="<?= e(route_url('dashboard')) ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Beranda</span>
        </a>
    </div>
</div>
</body>
</html>

