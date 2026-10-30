<?php
/**
 * File: views/errors/500.php
 * Fungsi: Halaman error HTTP 500 / 503 (Gangguan Server atau Mode Pemeliharaan).
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemeliharaan Sistem — Greymist</title>
    <link rel="stylesheet" href="<?= e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/components/buttons.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/modules/auth.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-container" style="text-align: center;">
        <div class="auth-brand">
            <span class="auth-brand-icon"><i class="fas fa-tools"></i></span>
        </div>
        <h2 style="margin-bottom: 10px;">Sistem Sedang Dalam Pemeliharaan</h2>
        <p style="margin-bottom: 22px;">Kami sedang meningkatkan layanan Greymist. Silakan kembali beberapa saat lagi.</p>
        <a href="<?= e(route_url('login')) ?>" class="btn btn-soft-blue">
            <i class="fas fa-sync-alt"></i>
            <span>Muat Ulang</span>
        </a>
    </div>
</div>
</body>
</html>

