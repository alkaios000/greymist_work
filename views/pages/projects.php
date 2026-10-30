<?php
/**
 * File: views/pages/projects.php
 * Fungsi: Tampilan halaman daftar Proyek Saya beserta filter pencarian, status, dan pengurutan.
 */

$page_title    = 'Proyek Saya';
$page_subtitle = 'Kelola seluruh proyek desain dan publikasi Anda';
$active_page   = 'projects';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Bilah Filter & Pencarian -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="projects">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama proyek Anda...">
    </div>
    <div class="filter-controls">
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="published" <?= ($status ?? '') === 'published' ? 'selected' : '' ?>>Publik (Published)</option>
            <option value="unpublished" <?= ($status ?? '') === 'unpublished' ? 'selected' : '' ?>>Draf (Unpublished)</option>
        </select>

        <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Terbaru</option>
            <option value="oldest" <?= ($sort ?? '') === 'oldest' ? 'selected' : '' ?>>Terlama</option>
            <option value="name" <?= ($sort ?? '') === 'name' ? 'selected' : '' ?>>Nama (A-Z)</option>
        </select>

        <button type="submit" class="btn btn-soft-blue btn-sm">
            <i class="fas fa-filter"></i>
            <span>Terapkan</span>
        </button>
    </div>
</form>

<?php if (empty($projects)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
        <h3>Tidak Ada Proyek Ditemukan</h3>
        <p>Belum ada proyek yang sesuai dengan kriteria pencarian Anda, atau Anda belum membuat proyek baru.</p>
        <form method="POST" action="<?= e(action_url('new_project')) ?>" style="margin-top: 6px;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i>
                <span>Buat Proyek Baru</span>
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $proj): ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <?php if ($proj['project_status'] === 'published'): ?>
                            <span class="badge badge-published"><i class="fas fa-globe"></i> Publik</span>
                        <?php else: ?>
                            <span class="badge badge-draft"><i class="fas fa-lock"></i> Draf</span>
                        <?php endif; ?>

                        <?php if ((int)$proj['is_template'] === 1): ?>
                            <span class="badge badge-template"><i class="fas fa-layer-group"></i> Template</span>
                        <?php endif; ?>
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
                    <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt"></i>
                        <span>Editor</span>
                    </a>

                    <div class="project-actions-group">
                        <!-- Toggle Publikasi -->
                        <form method="POST" action="<?= e(action_url('toggle_status')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="projects">
                            <button type="submit" class="btn-icon" title="<?= $proj['project_status'] === 'published' ? 'Jadikan Draf' : 'Publikasikan Proyek' ?>">
                                <i class="fas <?= $proj['project_status'] === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                            </button>
                        </form>

                        <!-- Toggle Template -->
                        <form method="POST" action="<?= e(action_url('toggle_template')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <input type="hidden" name="return_page" value="projects">
                            <button type="submit" class="btn-icon" title="<?= (int)$proj['is_template'] === 1 ? 'Hapus dari Template' : 'Jadikan Template' ?>">
                                <i class="fas fa-clone" style="<?= (int)$proj['is_template'] === 1 ? 'color: var(--btn-blue);' : '' ?>"></i>
                            </button>
                        </form>

                        <!-- Ubah Nama -->
                        <button type="button" class="btn-icon" title="Ubah Nama Proyek"
                                onclick="openRenameProjectModal(<?= (int)$proj['project_id'] ?>, '<?= e(addslashes($proj['project_name'])) ?>')">
                            <i class="fas fa-pen"></i>
                        </button>

                        <!-- Hapus ke Sampah -->
                        <button type="button" class="btn-icon danger" title="Pindahkan ke Tempat Sampah"
                                onclick="openConfirmActionModal({
                                    actionUrl: '<?= e(action_url('delete_project')) ?>',
                                    projectId: '<?= (int)$proj['project_id'] ?>',
                                    title: 'Pindahkan ke Tempat Sampah',
                                    message: 'Apakah Anda yakin ingin memindahkan proyek ini ke Tempat Sampah?',
                                    confirmText: 'Buang ke Sampah',
                                    buttonClass: 'btn-danger btn-sm'
                                })">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

