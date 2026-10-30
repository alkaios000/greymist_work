<?php
/**
 * File: views/pages/workspace.php
 * Fungsi: Tampilan antarmuka Visual Workspace Editor Greymist (tanpa query SQL di dalam view).
 * Variabel input: $project, $frames, $colors
 */

// Pastikan direktori kerja berada di ROOT_PATH agar relative include di dalam part/ berjalan lancar
chdir(ROOT_PATH);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($project['project_name'] ?? 'Untitled Project') ?> — Greymist Workspace</title>
    <link rel="icon" href="<?= e(base_url('assets/web-icon/greymist.svg')) ?>">

    <?php include ROOT_PATH . '/part/style.html'; ?>
    <?php include ROOT_PATH . '/part/CDN.html'; ?>
</head>
<script>
    window.GM_USER_ID = <?= (int)current_user_id() ?>;
</script>
<body class="gm-body">
    <?php
    include ROOT_PATH . '/part/aside-left.html';
    include ROOT_PATH . '/part/main.html';
    include ROOT_PATH . '/part/aside-right.html';
    include ROOT_PATH . '/part/modal.html';
    ?>

    <script>
        window.GREYMIST = {
            project: <?= json_encode($project, JSON_UNESCAPED_UNICODE) ?>,
            frames: <?= json_encode($frames, JSON_UNESCAPED_UNICODE) ?>,
            colors: <?= json_encode($colors, JSON_UNESCAPED_UNICODE) ?>
        };
    </script>

    <?php include ROOT_PATH . '/part/script.html'; ?>
</body>
</html>
