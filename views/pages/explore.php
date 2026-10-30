<?php
/**
 * File: views/pages/explore.php
 * Fungsi: Tampilan halaman Eksplorasi Karya Komunitas (proyek yang dipublikasikan).
 */

$page_title    = 'Eksplorasi Karya';
$page_subtitle = 'Jelajahi proyek desain web yang dipublikasikan oleh komunitas Greymist';
$active_page   = 'explore';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Ringkasan Statistik Komunitas -->
<section class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-globe"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['published_count'] ?? 0) ?></div>
            <div class="stat-label">Proyek Publik</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['total_creators'] ?? 0) ?></div>
            <div class="stat-label">Kreator Aktif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-layer-group"></i></div>
        <div class="stat-info">
            <div class="stat-value"><?= (int)($platform_stats['template_count'] ?? 0) ?></div>
            <div class="stat-label">Template Tersedia</div>
        </div>
    </div>
</section>

<!-- Filter & Pencarian -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="explore">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari proyek atau nama kreator...">
    </div>
    <div class="filter-controls">
        <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Terbaru</option>
            <option value="oldest" <?= ($sort ?? '') === 'oldest' ? 'selected' : '' ?>>Terlama</option>
            <option value="name" <?= ($sort ?? '') === 'name' ? 'selected' : '' ?>>Nama Proyek (A-Z)</option>
        </select>
        <button type="submit" class="btn btn-soft-blue btn-sm">
            <i class="fas fa-search"></i>
            <span>Cari</span>
        </button>
    </div>
</form>

<?php if (empty($published_projects)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-compass"></i></div>
        <h3>Belum Ada Karya Publik</h3>
        <p>Belum ada proyek publik yang sesuai dengan pencarian Anda.</p>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($published_projects as $proj): ?>
            <?php
            $author_name = $proj['nickname'] ?: ($proj['username'] ?? 'Anonim');
            $author_init = strtoupper(substr($author_name, 0, 1));
            ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <span class="badge badge-published"><i class="fas fa-globe"></i> Publik</span>
                        <?php if ((int)$proj['is_template'] === 1): ?>
                            <span class="badge badge-template"><i class="fas fa-layer-group"></i> Template</span>
                        <?php endif; ?>
                    </div>
                    <?php require ROOT_PATH . '/views/layouts/project_thumb_preview.php'; ?>
                </div>

                <div class="project-body">
                    <h4 class="project-title" title="<?= e($proj['project_name']) ?>"><?= e($proj['project_name']) ?></h4>
                    <div class="project-meta">
                        <span class="author-chip">
                            <span class="author-chip-avatar"><?= e($author_init) ?></span>
                            <span><?= e($author_name) ?></span>
                        </span>
                        <span><i class="fas fa-border-all"></i> <?= (int)($proj['frame_count'] ?? 0) ?> Frame</span>
                    </div>
                </div>

                <div class="project-actions">
                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                        <i class="far fa-clock"></i> <?= e(format_date($proj['created_at'])) ?>
                    </span>

                    <div class="project-actions-group">
                        <?php if ((int)$proj['user_id'] === current_user_id()): ?>
                            <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                                <i class="fas fa-external-link-alt"></i>
                                <span>Buka</span>
                            </a>
                        <?php endif; ?>

                        <form method="POST" action="<?= e(action_url('clone_project')) ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-copy"></i>
                                <span>Gunakan Karya</span>
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

