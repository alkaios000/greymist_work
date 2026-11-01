<?php
/**
 * File: views/pages/admin_users.php
 * Fungsi: Tampilan halaman Manajemen Pengguna pada Panel Administrator.
 */

$page_title    = 'Manajemen Pengguna';
$page_subtitle = '';
$active_page   = 'admin_users';
$is_admin_view = true;

$header_actions_html = '<button type="button" class="btn btn-primary btn-sm" onclick="GreymistModal.open(\'adminAddUserModal\')"><i class="fas fa-user-plus"></i><span>Tambah Pengguna</span></button>';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Filter & Pencarian Pengguna -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="admin_users">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari username, nickname, atau email...">
    </div>
    <div class="filter-controls">
        <select name="role" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Role</option>
            <option value="admin" <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
            <option value="user" <?= ($role ?? '') === 'user' ? 'selected' : '' ?>>Pengguna Biasa</option>
        </select>
        <button type="submit" class="btn btn-soft-blue btn-sm">Filter</button>
    </div>
</form>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Pengguna</th>
                <th>Telepon</th>
                <th>Role</th>
                <th>Proyek</th>
                <th>Login Terakhir</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users_list)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 28px; color: var(--text-muted);">Tidak ada data pengguna ditemukan.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users_list as $u): ?>
                    <tr>
                        <td>#<?= (int)$u['user_id'] ?></td>
                        <td>
                            <div class="user-cell">
                                <div class="user-cell-avatar">
                                    <?php if (!empty($u['profile'])): ?>
                                        <img src="<?= e(upload_url($u['profile'])) ?>" alt="<?= e($u['username']) ?>">
                                    <?php else: ?>
                                        <span><?= e(strtoupper(substr($u['username'], 0, 1))) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?= e($u['nickname'] ?: $u['username']) ?> <span style="color: var(--text-muted); font-weight: 400;">(@<?= e($u['username']) ?>)</span></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u['phone'] ?: '-') ?></td>
                        <td>
                            <?php if ((int)$u['is_admin'] === 1): ?>
                                <span class="badge badge-blue">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-muted">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)($u['project_count'] ?? 0) ?></td>
                        <td><?= e(format_datetime($u['last_login'])) ?></td>
                        <td>
                            <div class="project-actions-group">
                                <button type="button" class="btn-icon" title="Edit Pengguna"
                                        onclick='openAdminEditUserModal(<?= json_encode([
                                            "user_id"  => (int)$u["user_id"],
                                            "username" => $u["username"],
                                            "email"    => $u["email"],
                                            "nickname" => $u["nickname"],
                                            "phone"    => $u["phone"],
                                            "is_admin" => (int)$u["is_admin"],
                                        ]) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button type="button" class="btn-icon" title="Reset Password"
                                        onclick="openAdminResetPasswordModal(<?= (int)$u['user_id'] ?>, '<?= e(addslashes($u['username'])) ?>')">
                                    <i class="fas fa-key"></i>
                                </button>

                                <?php if ((int)$u['user_id'] !== current_user_id()): ?>
                                    <form method="POST" action="<?= e(action_url('admin_delete_user')) ?>" style="display:inline;" onsubmit="return confirm('Hapus pengguna ini beserta seluruh proyeknya?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn-icon danger" title="Hapus Pengguna">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= render_pagination($pagination ?? [], 'pengguna') ?>

<!-- Modal Tambah Pengguna Baru -->
<div class="modal-overlay" id="adminAddUserModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Pengguna Baru</h3>
            <button type="button" class="modal-close" data-modal-close="adminAddUserModal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= e(action_url('admin_add_user')) ?>">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" name="nickname">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Password Awal</label>
                    <input type="password" class="form-control" name="password" required minlength="6">
                </div>
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="is_admin" value="1">
                    <span>Berikan hak akses Administrator</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close="adminAddUserModal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Tambah Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div class="modal-overlay" id="adminEditUserModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Edit Data Pengguna</h3>
            <button type="button" class="modal-close" data-modal-close="adminEditUserModal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= e(action_url('admin_update_user')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" id="editUserId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" id="editUsername" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="editEmail" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" name="nickname" id="editNickname">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control" name="phone" id="editPhone">
                    </div>
                </div>
                <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="is_admin" id="editIsAdmin" value="1">
                    <span>Hak akses Administrator</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close="adminEditUserModal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal-overlay" id="adminResetPasswordModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">Reset Password</h3>
            <button type="button" class="modal-close" data-modal-close="adminResetPasswordModal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="<?= e(action_url('admin_reset_password')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="user_id" id="resetPasswordUserId">
            <div class="modal-body">
                <p style="margin-bottom: 12px; font-size: 0.85rem;">Masukkan password baru untuk pengguna <strong id="resetPasswordUsername"></strong>:</p>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Password Baru</label>
                    <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="Minimal 6 karakter">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-close="adminResetPasswordModal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

