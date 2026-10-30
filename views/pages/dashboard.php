<?php
/**
 * File: views/pages/dashboard.php
 * Fungsi: Tampilan halaman Dashboard utama pengguna (tanpa SQL, murni presentasi).
 */

$page_title    = 'Dashboard';
$page_subtitle = 'Ringkasan aktivitas dan proyek terbaru Anda';
$active_page   = 'dashboard';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Banner Sambutan -->
<section class="welcome-banner">
    <div class="welcome-text">
        <h2>Halo, <?= e($current_user['nickname'] ?: $current_user['username']) ?></h2>
        <p>Siap merancang antarmuka web baru hari ini? Mulai dari kanvas kosong atau gunakan template komunitas.</p>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <form method="POST" action="<?= e(action_url('new_project')) ?>" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                <span>Buat Proyek Baru</span>
            </button>
        </form>
        <a href="<?= e(route_url('templates')) ?>" class="btn btn-soft-blue">
            <i class="fas fa-layer-group"></i>
            <span>Pilih Template</span>
        </a>
    </div>
</section>

<!-- Kartu Statistik Ringkas -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-folder"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($user_stats['total_projects'] ?? 0) ?></div>
            <div class="stat-label">Total Proyek Saya</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-globe"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($user_stats['published_projects'] ?? 0) ?></div>
            <div class="stat-label">Proyek Dipublikasikan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-clone"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($user_stats['template_projects'] ?? 0) ?></div>
            <div class="stat-label">Template Saya</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-compass"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['published_count'] ?? 0) ?></div>
            <div class="stat-label">Karya Komunitas</div>
        </div>
    </div>
</section>

<!-- Pintasan Cepat -->
<section class="quick-actions-grid">
    <form method="POST" action="<?= e(action_url('new_project')) ?>">
        <?= csrf_field() ?>
        <button type="submit" class="quick-action-item">
            <div class="quick-action-icon"><i class="fas fa-plus-circle"></i></div>
            <div class="quick-action-text">
                <h4>Kanvas Kosong</h4>
                <p>Mulai proyek desain dari awal</p>
            </div>
        </button>
    </form>

    <a href="<?= e(route_url('projects')) ?>" class="quick-action-item">
        <div class="quick-action-icon"><i class="fas fa-folder-open"></i></div>
        <div class="quick-action-text">
            <h4>Kelola Proyek</h4>
            <p>Lihat seluruh daftar proyek Anda</p>
        </div>
    </a>

    <a href="<?= e(route_url('explore')) ?>" class="quick-action-item">
        <div class="quick-action-icon"><i class="fas fa-compass"></i></div>
        <div class="quick-action-text">
            <h4>Eksplorasi Karya</h4>
            <p>Temukan inspirasi dari kreator lain</p>
        </div>
    </a>

    <a href="<?= e(route_url('profile')) ?>" class="quick-action-item">
        <div class="quick-action-icon"><i class="fas fa-user-cog"></i></div>
        <div class="quick-action-text">
            <h4>Pengaturan Akun</h4>
            <p>Perbarui profil dan keamanan</p>
        </div>
    </a>
</section>

<!-- Daftar Proyek Terbaru -->
<section>
    <div class="section-header">
        <h3 class="section-title"><i class="fas fa-clock"></i> Proyek Terbaru Anda</h3>
        <a href="<?= e(route_url('projects')) ?>" class="btn btn-soft-blue btn-sm">
            <span>Lihat Semua</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <?php if (empty($recent_projects)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-folder-plus"></i></div>
            <h3>Belum Ada Proyek</h3>
            <p>Anda belum membuat proyek desain apa pun. Klik tombol di bawah untuk memulai proyek pertama Anda.</p>
            <form method="POST" action="<?= e(action_url('new_project')) ?>" style="margin-top: 6px;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i>
                    <span>Buat Proyek Pertama</span>
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="projects-grid">
            <?php foreach ($recent_projects as $proj): ?>
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
                        <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                            <i class="fas fa-external-link-alt"></i>
                            <span>Buka Editor</span>
                        </a>
                        <div class="project-actions-group">
                            <button type="button" class="btn-icon" title="Ubah Nama"
                                    onclick="openRenameProjectModal(<?= (int)$proj['project_id'] ?>, '<?= e(addslashes($proj['project_name'])) ?>')">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button type="button" class="btn-icon danger" title="Pindahkan ke Sampah"
                                    onclick="openConfirmActionModal({
                                        actionUrl: '<?= e(action_url('delete_project')) ?>',
                                        projectId: '<?= (int)$proj['project_id'] ?>',
                                        title: 'Pindahkan ke Tempat Sampah',
                                        message: 'Proyek ini akan dipindahkan ke Tempat Sampah dan dapat dipulihkan kapan saja.',
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
</section>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

