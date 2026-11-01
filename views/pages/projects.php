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
                <span>+ Buat Proyek Baru</span>
            </button>
        </form>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($projects as $proj): ?>
            <article class="project-card">
                <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="project-thumb">
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
                </a>

                <div class="project-body">
                    <div class="project-title-row">
                        <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="project-title-link">
                            <h4 class="project-title" title="<?= e($proj['project_name']) ?>"><?= e($proj['project_name']) ?></h4>
                        </a>

                        <div class="project-card-menu">
                            <button type="button" class="project-menu-btn" title="Opsi Proyek" onclick="toggleProjectMenu(event, this)" aria-label="Menu Opsi">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="project-menu-dropdown">
                                <!-- Buka Workspace -->
                                <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="dropdown-item">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                    <span>Buka di Editor</span>
                                </a>

                                <!-- Ubah Nama -->
                                <button type="button" class="dropdown-item"
                                        onclick="openRenameProjectModal(<?= (int)$proj['project_id'] ?>, '<?= e(addslashes($proj['project_name'])) ?>')">
                                    <i class="fas fa-pen"></i>
                                    <span>Ubah Nama</span>
                                </button>

                                <!-- Toggle Status Publikasi -->
                                <form method="POST" action="<?= e(action_url('toggle_status')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                                    <input type="hidden" name="return_page" value="projects">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas <?= $proj['project_status'] === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                                        <span><?= $proj['project_status'] === 'published' ? 'Jadikan Draf' : 'Publikasikan' ?></span>
                                    </button>
                                </form>

                                <!-- Toggle Template -->
                                <form method="POST" action="<?= e(action_url('toggle_template')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                                    <input type="hidden" name="return_page" value="projects">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-clone"></i>
                                        <span><?= (int)$proj['is_template'] === 1 ? 'Batal Jadi Template' : 'Jadikan Template' ?></span>
                                    </button>
                                </form>

                                <div class="dropdown-divider"></div>

                                <!-- Hapus ke Sampah -->
                                <button type="button" class="dropdown-item danger"
                                        onclick="openConfirmActionModal({
                                            actionUrl: '<?= e(action_url('delete_project')) ?>',
                                            projectId: '<?= (int)$proj['project_id'] ?>',
                                            title: 'Pindahkan ke Tempat Sampah',
                                            message: 'Apakah Anda yakin ingin memindahkan proyek ini ke Tempat Sampah?',
                                            confirmText: 'Buang ke Sampah',
                                            buttonClass: 'btn-danger btn-sm'
                                        })">
                                    <i class="fas fa-trash"></i>
                                    <span>Pindahkan ke Sampah</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="project-meta">
                        <span><?= e(format_date($proj['created_at'])) ?></span>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?= render_pagination($pagination ?? [], 'proyek') ?>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

