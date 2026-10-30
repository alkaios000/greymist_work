<?php
/**
 * File: database/seeders/seed_users.php
 * Fungsi: Menyinkronkan akun administrator dan pengguna default dari konfigurasi .env ke tabel gm_users.
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

$accounts_to_seed = [
    [
        'nickname' => (string)env('ADMIN_NICKNAME', 'Greymist Admin'),
        'username' => (string)env('ADMIN_USERNAME', 'admin'),
        'email'    => (string)env('ADMIN_EMAIL', 'admin@greymist.com'),
        'password' => (string)env('ADMIN_PASSWORD', 'admin123'),
        'is_admin' => 1,
    ],
    [
        'nickname' => (string)env('ADMIN_NICKNAME', 'Greymist Admin'),
        'username' => (string)env('ADMIN_ALT_USERNAME', 'greymist'),
        'email'    => 'greymist_admin@greymist.com',
        'password' => (string)env('ADMIN_ALT_PASSWORD', 'admin123'),
        'is_admin' => 1,
    ],
    [
        'nickname' => (string)env('USER_NICKNAME', 'Nasuha'),
        'username' => (string)env('USER_USERNAME', 'nasuha'),
        'email'    => (string)env('USER_EMAIL', 'nasuha@greymist.com'),
        'password' => (string)env('USER_PASSWORD', '123456'),
        'is_admin' => 0,
    ],
];

foreach ($accounts_to_seed as $acc) {
    $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
    $stmt   = $pdo->prepare("SELECT user_id FROM gm_users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$acc['username'], $acc['email']]);
    $row = $stmt->fetch();

    if ($row) {
        $upd = $pdo->prepare("UPDATE gm_users SET password = ?, is_admin = ? WHERE user_id = ?");
        $upd->execute([$hashed, $acc['is_admin'], $row['user_id']]);
        echo "[Seeder] Diperbarui: {$acc['username']} (role admin: {$acc['is_admin']}).\n";
    } else {
        $ins = $pdo->prepare("
            INSERT INTO gm_users (nickname, username, email, password, is_admin, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$acc['nickname'], $acc['username'], $acc['email'], $hashed, $acc['is_admin']]);
        echo "[Seeder] Dibuat: {$acc['username']} (role admin: {$acc['is_admin']}).\n";
    }
}
