<?php
/**
 * File: views/pages/profile.php
 * Fungsi: Tampilan halaman Pengaturan Profil, Password, dan Foto Profil Pengguna.
 */

$page_title    = 'Pengaturan Profil';
$page_subtitle = 'Kelola informasi akun, foto profil, dan kata sandi Anda';
$active_page   = 'profile';

$display_name = $current_user['nickname'] ?: ($current_user['username'] ?? 'User');
$initial      = strtoupper(substr($display_name, 0, 1));
$avatar_url   = !empty($current_user['profile']) ? upload_url($current_user['profile']) : '';

require ROOT_PATH . '/views/layouts/header.php';
?>

<div class="profile-layout">
    <!-- Kolom Kiri: Kartu Ringkasan Profil & Foto -->
    <aside class="profile-summary-card">
        <div class="profile-avatar-large">
            <?php if ($avatar_url !== ''): ?>
                <img src="<?= e($avatar_url) ?>" alt="<?= e($display_name) ?>">
            <?php else: ?>
                <span><?= e($initial) ?></span>
            <?php endif; ?>
        </div>

        <h3 class="profile-name"><?= e($display_name) ?></h3>
        <div class="profile-handle">@<?= e($current_user['username']) ?></div>

        <form method="POST" action="<?= e(action_url('upload_profile_picture')) ?>" enctype="multipart/form-data" style="margin-bottom: 8px;">
            <?= csrf_field() ?>
            <label class="btn btn-soft-blue btn-sm" style="width: 100%; cursor: pointer;">
                <i class="fas fa-camera"></i>
                <span>Ubah Foto Profil</span>
                <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.gif,.webp" style="display: none;" onchange="this.form.submit()">
            </label>
        </form>

        <?php if (!empty($current_user['profile'])): ?>
            <form method="POST" action="<?= e(action_url('delete_profile_picture')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;">
                    <i class="fas fa-trash-alt"></i>
                    <span>Hapus Foto</span>
                </button>
            </form>
        <?php endif; ?>

        <div class="profile-mini-stats">
            <div class="profile-mini-stat">
                <strong><?= (int)($user_stats['total_projects'] ?? 0) ?></strong>
                <span>Total Proyek</span>
            </div>
            <div class="profile-mini-stat">
                <strong><?= (int)($user_stats['published_projects'] ?? 0) ?></strong>
                <span>Dipublikasikan</span>
            </div>
        </div>
    </aside>

    <!-- Kolom Kanan: Form Informasi Profil & Keamanan -->
    <div class="profile-sections">
        <section class="panel-card">
            <h3 style="margin-bottom: 14px; font-size: 1rem;">
                <i class="fas fa-user-edit" style="color: var(--btn-blue); margin-right: 6px;"></i>
                Informasi Pribadi
            </h3>
            <form method="POST" action="<?= e(action_url('update_profile')) ?>">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="nickname">Nama Panggilan (Nickname)</label>
                        <input type="text" class="form-control" id="nickname" name="nickname" value="<?= e($current_user['nickname'] ?? '') ?>" placeholder="Nama tampilan Anda">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= e($current_user['username'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Alamat Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($current_user['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Nomor Telepon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($current_user['phone'] ?? '') ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i>
                    <span>Simpan Perubahan Profil</span>
                </button>
            </form>
        </section>

        <section class="panel-card">
            <h3 style="margin-bottom: 14px; font-size: 1rem;">
                <i class="fas fa-key" style="color: var(--btn-blue); margin-right: 6px;"></i>
                Ubah Password
            </h3>
            <form method="POST" action="<?= e(action_url('update_password')) ?>">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="current_password">Password Saat Ini</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="new_password">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-soft-blue btn-sm">
                    <i class="fas fa-lock"></i>
                    <span>Perbarui Password</span>
                </button>
            </form>
        </section>

        <section class="panel-card danger-zone-card">
            <h3 style="margin-bottom: 6px; font-size: 1rem; color: var(--color-danger);">
                <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>
                Zona Berbahaya
            </h3>
            <p style="margin-bottom: 14px; font-size: 0.82rem;">
                Menghapus akun akan menghapus seluruh proyek, frame, dan komponen kustom Anda secara permanen.
            </p>
            <button type="button" class="btn btn-danger btn-sm"
                    onclick="openConfirmActionModal({
                        actionUrl: '<?= e(action_url('delete_account')) ?>',
                        projectId: '',
                        title: 'Hapus Akun Permanen',
                        message: 'Apakah Anda yakin ingin menghapus akun Greymist Anda beserta seluruh proyek di dalamnya secara permanen?',
                        confirmText: 'Ya, Hapus Akun Saya',
                        buttonClass: 'btn-danger btn-sm'
                    })">
                <i class="fas fa-user-slash"></i>
                <span>Hapus Akun Saya</span>
            </button>
        </section>
    </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

