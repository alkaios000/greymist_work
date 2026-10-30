<?php
/**
 * File: views/pages/admin_templates.php
 * Fungsi: Tampilan halaman Kelola Template pada Panel Administrator.
 */

$page_title    = 'Kelola Template';
$page_subtitle = 'Daftar proyek yang ditandai sebagai template komunitas';
$active_page   = 'admin_templates';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';
?>

<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="admin_templates">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama template atau pembuat...">
    </div>
    <div class="filter-controls">
        <button type="submit" class="btn btn-soft-blue btn-sm">
            <i class="fas fa-search"></i>
            <span>Cari</span>
        </button>
    </div>
</form>

<?php if (empty($templates)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-clone"></i></div>
        <h3>Belum Ada Template</h3>
        <p>Anda dapat menandai proyek sebagai template melalui halaman Semua Proyek.</p>
        <a href="<?= e(route_url('admin_projects')) ?>" class="btn btn-soft-blue btn-sm" style="margin-top: 6px;">Buka Semua Proyek</a>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($templates as $proj): ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <span class="badge badge-template"><i class="fas fa-layer-group"></i> Template</span>
                        <span class="badge badge-muted"><?= (int)($proj['frame_count'] ?? 0) ?> Frame</span>
                    </div>
                    <?php require ROOT_PATH . '/views/layouts/project_thumb_preview.php'; ?>
                </div>

                <div class="project-body">
                    <h4 class="project-title" title="<?= e($proj['project_name']) ?>"><?= e($proj['project_name']) ?></h4>
                    <div class="project-meta">
                        <span><i class="fas fa-user"></i> @<?= e($proj['username'] ?? 'unknown') ?></span>
                        <span><?= e(format_date($proj['created_at'])) ?></span>
                    </div>
                </div>

                <div class="project-actions">
                    <a href="<?= e(route_url('admin_preview_project', ['id' => $proj['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                        <i class="fas fa-eye"></i>
                        <span>Pratinjau</span>
                    </a>

                    <div class="project-actions-group">
                        <form method="POST" action="<?= e(action_url('admin_remove_template')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" title="Copot Status Template">
                                <i class="fas fa-unlink"></i>
                                <span>Copot</span>
                            </button>
                        </form>

                        <form method="POST" action="<?= e(action_url('admin_delete_project')) ?>" style="display:inline;" onsubmit="return confirm('Hapus proyek template ini secara permanen?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="admin_templates">
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

