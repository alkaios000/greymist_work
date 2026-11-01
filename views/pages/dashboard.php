<?php
/**
 * File: views/pages/dashboard.php
 * Fungsi: Tampilan halaman Dashboard utama pengguna, konsisten dengan gaya Command Center Admin yang bersih & bebas fluff.
 */

$page_title    = 'Dashboard';
$page_subtitle = '';
$active_page   = 'dashboard';

require ROOT_PATH . '/views/layouts/header.php';

// Kalkulasi statistik proyek pengguna
$my_total_projects = (int)($user_stats['total_projects'] ?? 0);
$my_published      = (int)($user_stats['published_projects'] ?? 0);
$my_draft          = max(0, $my_total_projects - $my_published);
$my_pub_ratio      = $my_total_projects > 0 ? round(($my_published / $my_total_projects) * 100, 1) : 0;
$my_draft_ratio    = $my_total_projects > 0 ? (100 - $my_pub_ratio) : 100;

$my_templates      = (int)($user_stats['template_projects'] ?? 0);
$community_count   = (int)($platform_stats['published_count'] ?? 0);
?>

<!-- Ringkasan Metrik Ringkas & Hemat Ruang -->
<section class="compact-metric-grid">
    <!-- Card 1: Proyek Saya -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Workspace Saya</span>
            <div class="metric-value">
                <span><?= number_format($my_total_projects) ?></span>
                <span class="metric-sub">Proyek</span>
            </div>
        </div>
        <span class="metric-pill"><?= $my_published ?> Publik • <?= $my_draft ?> Draf</span>
    </div>

    <!-- Card 2: Status Publikasi -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Publikasi Komunitas</span>
            <div class="metric-value">
                <span><?= number_format($my_published) ?></span>
                <span class="metric-sub">Live</span>
            </div>
        </div>
        <span class="metric-pill <?= $my_published > 0 ? 'active' : '' ?>"><?= $my_pub_ratio ?>% Live</span>
    </div>

    <!-- Card 3: Template Saya -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Template Saya</span>
            <div class="metric-value">
                <span><?= number_format($my_templates) ?></span>
                <span class="metric-sub">Preset</span>
            </div>
        </div>
        <span class="metric-pill"><?= $my_templates ?> Template</span>
    </div>

    <!-- Card 4: Karya Komunitas -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Eksplorasi Platform</span>
            <div class="metric-value">
                <span><?= number_format($community_count) ?></span>
                <span class="metric-sub">Karya</span>
            </div>
        </div>
        <a href="<?= e(route_url('explore')) ?>" class="metric-link">Jelajahi &rarr;</a>
    </div>
</section>

<!-- Daftar Proyek Terbaru (Langsung Tampil di Bagian Atas) -->
<section>
    <div class="section-header">
        <h3 class="section-title">Proyek Terbaru Anda</h3>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="<?= e(route_url('templates')) ?>" class="btn btn-outline btn-sm">Galeri Template</a>
            <a href="<?= e(route_url('projects')) ?>" class="btn btn-soft-blue btn-sm">Lihat Semua</a>
        </div>
    </div>

    <?php if (empty($recent_projects)): ?>
        <div class="empty-state">
            <h3>Belum Ada Proyek</h3>
            <p>Anda belum membuat proyek desain apa pun. Klik tombol di bawah untuk memulai proyek pertama Anda.</p>
            <form method="POST" action="<?= e(action_url('new_project')) ?>" style="margin-top: 6px;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span>+ Buat Proyek Pertama</span>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($recent_projects as $proj): ?>
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
                                        <input type="hidden" name="return_page" value="dashboard">
                                        <button type="submit" class="dropdown-item">
                                            <i class="fas <?= $proj['project_status'] === 'published' ? 'fa-eye-slash' : 'fa-globe' ?>"></i>
                                            <span><?= $proj['project_status'] === 'published' ? 'Jadikan Draf' : 'Publikasikan' ?></span>
                                        </button>
                                    </form>

                                    <!-- Toggle Template -->
                                    <form method="POST" action="<?= e(action_url('toggle_template')) ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                                        <input type="hidden" name="return_page" value="dashboard">
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
                                                message: 'Proyek ini akan dipindahkan ke Tempat Sampah dan dapat dipulihkan kapan saja.',
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
    <?php endif; ?>
</section>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
