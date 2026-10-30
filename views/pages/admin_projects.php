<?php
/**
 * File: views/pages/admin_projects.php
 * Fungsi: Tampilan halaman Manajemen Seluruh Proyek pada Panel Administrator.
 */

$page_title    = 'Semua Proyek';
$page_subtitle = 'Pantau, publikasikan, jadikan template, atau hapus proyek di seluruh platform';
$active_page   = 'admin_projects';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Filter & Pencarian -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="admin_projects">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama proyek atau pemilik...">
    </div>
    <div class="filter-controls">
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Publik</option>
            <option value="unpublished" <?= ($status ?? '') === 'unpublished' ? 'selected' : '' ?>>Draf</option>
        </select>

        <select name="template" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Tipe</option>
            <option value="yes" <?= ($template ?? '') === 'yes' ? 'selected' : '' ?>>Template</option>
            <option value="no" <?= ($template ?? '') === 'no' ? 'selected' : '' ?>>Bukan Template</option>
        </select>

        <button type="submit" class="btn btn-soft-blue btn-sm">
            <i class="fas fa-filter"></i>
            <span>Filter</span>
        </button>
    </div>
</form>

<?php if (empty($all_projects)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
        <h3>Tidak Ada Proyek Ditemukan</h3>
        <p>Belum ada proyek yang sesuai dengan filter pencarian Anda.</p>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($all_projects as $proj): ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <?php if ($proj['project_status'] === 'published'): ?>
                            <span class="badge badge-published">Publik</span>
                        <?php else: ?>
                            <span class="badge badge-draft">Draf</span>
                        <?php endif; ?>

                        <?php if ((int)$proj['is_template'] === 1): ?>
                            <span class="badge badge-template">Template</span>
                        <?php endif; ?>
                    </div>
                    <?php require ROOT_PATH . '/views/layouts/project_thumb_preview.php'; ?>
                </div>

                <div class="project-body">
                    <h4 class="project-title" title="<?= e($proj['project_name']) ?>"><?= e($proj['project_name']) ?></h4>
                    <div class="project-meta">
                        <span><i class="fas fa-user"></i> @<?= e($proj['username'] ?? 'unknown') ?></span>
                        <span><i class="fas fa-border-all"></i> <?= (int)($proj['frame_count'] ?? 0) ?> Frame</span>
                    </div>
                </div>

                <div class="project-actions">
                    <a href="<?= e(route_url('admin_preview_project', ['id' => $proj['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                        <i class="fas fa-eye"></i>
                        <span>Pratinjau</span>
                    </a>

                    <div class="project-actions-group">
                        <form method="POST" action="<?= e(action_url('admin_toggle_status')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="admin_projects">
                            <button type="submit" class="btn-icon" title="Ubah Status Publikasi">
                                <i class="fas <?= $proj['project_status'] === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                            </button>
                        </form>

                        <form method="POST" action="<?= e(action_url('admin_toggle_template')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="admin_projects">
                            <button type="submit" class="btn-icon" title="Toggle Status Template">
                                <i class="fas fa-clone" style="<?= (int)$proj['is_template'] === 1 ? 'color: var(--btn-blue);' : '' ?>"></i>
                            </button>
                        </form>

                        <form method="POST" action="<?= e(action_url('admin_delete_project')) ?>" style="display:inline;" onsubmit="return confirm('Hapus proyek ini secara permanen?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="admin_projects">
                            <button type="submit" class="btn-icon danger" title="Hapus Permanen">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

