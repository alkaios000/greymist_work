<?php
/**
 * File: views/pages/trash.php
 * Fungsi: Tampilan halaman Tempat Sampah (proyek yang dihapus sementara).
 */

$page_title    = 'Tempat Sampah';
$page_subtitle = 'Pulihkan proyek yang tidak sengaja terhapus atau bersihkan secara permanen';
$active_page   = 'trash';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Filter & Pencarian -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="trash">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari proyek di tempat sampah...">
    </div>
    <div class="filter-controls">
        <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Terbaru Dihapus</option>
            <option value="oldest" <?= ($sort ?? '') === 'oldest' ? 'selected' : '' ?>>Terlama</option>
            <option value="name" <?= ($sort ?? '') === 'name' ? 'selected' : '' ?>>Nama Proyek (A-Z)</option>
        </select>
        <button type="submit" class="btn btn-soft-blue btn-sm">
            <i class="fas fa-search"></i>
            <span>Cari</span>
        </button>
    </div>
</form>

<?php if (empty($trashed_projects)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-trash-restore"></i></div>
        <h3>Tempat Sampah Kosong</h3>
        <p>Tidak ada proyek yang berada di dalam Tempat Sampah saat ini.</p>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($trashed_projects as $proj): ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <span class="badge badge-danger"><i class="fas fa-trash-alt"></i> Di Sampah</span>
                    </div>
                    <?php require ROOT_PATH . '/views/layouts/project_thumb_preview.php'; ?>
                </div>

                <div class="project-body">
                    <h4 class="project-title" title="<?= e($proj['project_name']) ?>"><?= e($proj['project_name']) ?></h4>
                    <div class="project-meta">
                        <span><i class="far fa-calendar-alt"></i> <?= e(format_date($proj['created_at'])) ?></span>
                        <span><i class="fas fa-border-all"></i> <?= (int)($proj['frame_count'] ?? 0) ?> Frame</span>
                    </div>
                </div>

                <div class="project-actions">
                    <form method="POST" action="<?= e(action_url('restore_project')) ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                        <button type="submit" class="btn btn-soft-blue btn-sm">
                            <i class="fas fa-undo"></i>
                            <span>Pulihkan</span>
                        </button>
                    </form>

                    <button type="button" class="btn btn-danger btn-sm"
                            onclick="openConfirmActionModal({
                                actionUrl: '<?= e(action_url('permanent_delete')) ?>',
                                projectId: '<?= (int)$proj['project_id'] ?>',
                                title: 'Hapus Proyek Secara Permanen',
                                message: 'Tindakan ini tidak dapat dibatalkan. Seluruh frame dan komponen di dalam proyek ini akan dihapus selamanya.',
                                confirmText: 'Hapus Permanen',
                                buttonClass: 'btn-danger btn-sm'
                            })">
                        <i class="fas fa-times-circle"></i>
                        <span>Hapus Permanen</span>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

