<?php
/**
 * File: views/pages/templates.php
 * Fungsi: Tampilan halaman Galeri Template Siap Pakai.
 */

$page_title    = 'Galeri Template';
$page_subtitle = 'Mulai lebih cepat menggunakan template desain siap pakai';
$active_page   = 'templates';

require ROOT_PATH . '/views/layouts/header.php';
?>

<!-- Filter & Pencarian Template -->
<form method="GET" action="<?= e(base_url('index.php')) ?>" class="filter-bar">
    <input type="hidden" name="page" value="templates">
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama template atau pembuat...">
    </div>
    <div class="filter-controls">
        <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?= ($sort ?? 'newest') === 'newest' ? 'selected' : '' ?>>Terbaru</option>
            <option value="oldest" <?= ($sort ?? '') === 'oldest' ? 'selected' : '' ?>>Terlama</option>
            <option value="name" <?= ($sort ?? '') === 'name' ? 'selected' : '' ?>>Nama Template (A-Z)</option>
        </select>
        <button type="submit" class="btn btn-soft-blue btn-sm">
            <span>Filter</span>
        </button>
    </div>
</form>

<?php if (empty($templates)): ?>
    <div class="empty-state">
        <h3>Belum Ada Template Tersedia</h3>
        <p>Anda dapat menandai proyek Anda sendiri sebagai template dari halaman Proyek Saya.</p>
        <a href="<?= e(route_url('projects')) ?>" class="btn btn-soft-blue btn-sm" style="margin-top: 6px;">
            <span>Buka Proyek Saya</span>
        </a>
    </div>
<?php else: ?>
    <div class="projects-grid">
        <?php foreach ($templates as $proj): ?>
            <?php
            $author_name = $proj['nickname'] ?: ($proj['username'] ?? 'Greymist');
            $author_init = strtoupper(substr($author_name, 0, 1));
            ?>
            <article class="project-card">
                <div class="project-thumb">
                    <div class="project-badges">
                        <span class="badge badge-template">Template</span>
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
                        <span><?= e(format_date($proj['created_at'])) ?></span>
                    </div>
                </div>

                <div class="project-actions">
                    <?php if ((int)$proj['user_id'] === current_user_id()): ?>
                        <a href="<?= e(route_url('workspace', ['project_id' => $proj['project_id']])) ?>" class="btn btn-soft-blue btn-sm">
                            <span>Edit</span>
                        </a>
                    <?php else: ?>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Siap pakai</span>
                    <?php endif; ?>

                    <form method="POST" action="<?= e(action_url('clone_template')) ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="project_id" value="<?= (int)$proj['project_id'] ?>">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span>Gunakan</span>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?= render_pagination($pagination ?? [], 'template') ?>
<?php endif; ?>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

