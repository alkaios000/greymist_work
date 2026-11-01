<?php
/**
 * File: views/pages/admin_preview_project.php
 * Fungsi: Tampilan Pratinjau Kanvas Proyek pada Panel Administrator.
 */

$page_title    = 'Pratinjau: ' . ($preview_project['project_name'] ?? 'Proyek');
$page_subtitle = '';
$active_page   = 'admin_projects';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';

$active_frame_index = isset($_GET['frame_idx']) ? max(0, (int)$_GET['frame_idx']) : 0;
$current_frame      = $preview_frames[$active_frame_index] ?? ($preview_frames[0] ?? null);
?>

<div class="admin-preview-shell">
    <div class="admin-preview-toolbar">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="<?= e(route_url('admin_projects')) ?>" class="btn btn-outline btn-sm">Kembali</a>

            <?php foreach ($preview_frames as $idx => $fr): ?>
                <a href="<?= e(route_url('admin_preview_project', ['id' => $preview_project['project_id'], 'frame_idx' => $idx])) ?>"
                   class="btn <?= $idx === $active_frame_index ? 'btn-primary' : 'btn-soft-blue' ?> btn-sm">
                    <span><?= e($fr['frame_name'] ?: ('Frame ' . ($idx + 1))) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="<?= e(route_url('workspace', ['project_id' => $preview_project['project_id']])) ?>" class="btn btn-primary btn-sm">Buka di Workspace</a>
        </div>
    </div>

    <?php if ($current_frame && !empty($current_frame['core_content'])): ?>
        <?php
        $preview_html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
            . '<link rel="stylesheet" href="' . e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) . '">'
            . '<link rel="stylesheet" href="' . e(base_url('assets/Style/body.css')) . '">'
            . '<link rel="stylesheet" href="' . e(base_url('assets/Style/main/main.css')) . '">'
            . '<style>body,html{margin:0;padding:0;width:100%;min-height:100vh;background:#ffffff;}</style>'
            . '</head><body>' . $current_frame['core_content'] . '</body></html>';
        ?>
        <iframe class="admin-preview-viewport" srcdoc="<?= e($preview_html) ?>" sandbox="allow-same-origin" title="Pratinjau Proyek"></iframe>
    <?php else: ?>
        <div class="empty-state" style="border: none; border-radius: 0;">
            <div class="empty-state-icon"><i class="fas fa-layer-group"></i></div>
            <h3>Frame Kosong</h3>
            <p>Frame ini belum memiliki elemen visual di dalamnya.</p>
        </div>
    <?php endif; ?>
</div>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>

