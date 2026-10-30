<?php
/**
 * File: views/layouts/project_thumb_preview.php
 * Fungsi: Merender pratinjau (thumbnail atau iframe HTML) pada kartu proyek secara DRY.
 * Variabel input: $proj (array baris dari gm_projects)
 */

$has_thumb   = !empty($proj['thumbnail']);
$has_content = !empty($proj['first_frame_content']);
?>
<?php if ($has_thumb): ?>
    <img src="<?= e(upload_url($proj['thumbnail'])) ?>" alt="<?= e($proj['project_name']) ?>">
<?php elseif ($has_content): ?>
    <?php
    $iframe_doc = '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<link rel="stylesheet" href="' . e(base_url('assets/fontawesome-free-6.7.2-web/css/all.min.css')) . '">'
        . '<link rel="stylesheet" href="' . e(base_url('assets/Style/body.css')) . '">'
        . '<link rel="stylesheet" href="' . e(base_url('assets/Style/main/main.css')) . '">'
        . '<style>body,html{margin:0;padding:0;width:100%;height:100%;overflow:hidden;background:#ffffff;}</style>'
        . '</head><body>' . $proj['first_frame_content'] . '</body></html>';
    ?>
    <iframe class="live-preview-frame" srcdoc="<?= e($iframe_doc) ?>" sandbox="allow-same-origin" loading="lazy" title="<?= e($proj['project_name']) ?>"></iframe>
<?php else: ?>
    <div class="project-thumb-placeholder">
        <i class="fas fa-layer-group"></i>
        <span>Kanvas Kosong</span>
    </div>
<?php endif; ?>

