<?php
/**
 * File: views/pages/admin_dashboard.php
 * Fungsi: Tampilan Dashboard Utama Panel Administrator.
 */

$page_title    = 'Dashboard Admin';
$page_subtitle = 'Ikhtisar metrik platform, pengguna terbaru, dan aktivitas proyek';
$active_page   = 'admin_dashboard';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Metrik Statistik Platform -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_users'] ?? 0) ?></div>
            <div class="stat-label">Total Pengguna (<?= (int)($platform_stats['total_admins'] ?? 0) ?> Admin)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-folder-open"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_projects'] ?? 0) ?></div>
            <div class="stat-label">Total Proyek (<?= (int)($platform_stats['published_count'] ?? 0) ?> Publik)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-clone"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['template_count'] ?? 0) ?></div>
            <div class="stat-label">Template Aktif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-border-all"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_frames'] ?? 0) ?></div>
            <div class="stat-label">Total Frame Kanvas</div>
        </div>
    </div>
</section>

<!-- Dua Kolom: Pengguna Terbaru & Proyek Terbaru -->
<div class="admin-two-col">
    <section class="panel-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 0.95rem;"><i class="fas fa-user-plus" style="color: var(--btn-blue); margin-right: 6px;"></i> Pengguna Terbaru</h3>
            <a href="<?= e(route_url('admin_users')) ?>" class="btn btn-soft-blue btn-sm">Semua Pengguna</a>
        </div>
        <div class="table-container" style="border: none; border-radius: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pengguna</th>
                        <th>Role</th>
                        <th>Proyek</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $u): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?= e(strtoupper(substr($u['username'], 0, 1))) ?></div>
                                    <div>
                                        <div style="font-weight: 600;"><?= e($u['nickname'] ?: $u['username']) ?></div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ((int)$u['is_admin'] === 1): ?>
                                    <span class="badge badge-blue">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">User</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)($u['project_count'] ?? 0) ?></td>
                            <td><?= e(format_date($u['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-card" style="padding: 0; overflow: hidden;">
        <div style="padding: 14px 18px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 0.95rem;"><i class="fas fa-folder" style="color: var(--btn-blue); margin-right: 6px;"></i> Proyek Terbaru</h3>
            <a href="<?= e(route_url('admin_projects')) ?>" class="btn btn-soft-blue btn-sm">Semua Proyek</a>
        </div>
        <div class="table-container" style="border: none; border-radius: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Proyek</th>
                        <th>Pemilik</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_projects as $p): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= e($p['project_name']) ?></div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);"><?= (int)($p['frame_count'] ?? 0) ?> Frame</div>
                            </td>
                            <td>@<?= e($p['username'] ?? 'unknown') ?></td>
                            <td>
                                <?php if ($p['project_status'] === 'published'): ?>
                                    <span class="badge badge-published">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-draft">Draf</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= e(route_url('admin_preview_project', ['id' => $p['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

