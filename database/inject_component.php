<?php

declare(strict_types=1);

/**
 * CLI & Helper Injektor, Pembaca Frame & Penghapus Komponen Antigravity IDE -> Database Greymist
 *
 * Cara penggunaan:
 * 1. Lihat daftar project & frame:
 *    php "c:\laragon\www\Greymist_work\database\inject_component.php" --list
 * 2. Baca isi komponen di dalam frame:
 *    php "c:\laragon\www\Greymist_work\database\inject_component.php" --read --project_id=4 --frame_id=16
 * 3. Hapus komponen tertentu atau kosongkan frame:
 *    php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --delete="navbar"
 *    php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --clear
 * 4. Tambah / ganti komponen ke dalam frame:
 *    php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --name="Hero Modern" --mode=both --html="<section class='gm-object'>...</section>"
 */

define('AI_ACTION_LIBRARY_ONLY', true);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/controllers/ai_action.php';
require_once __DIR__ . '/../models/component_model.php';
require_once __DIR__ . '/../models/frame_model.php';

$options = getopt('', [
    'list',
    'read',
    'clear',
    'delete::',
    'project_id::',
    'frame_id::',
    'user_id::',
    'name::',
    'mode::',
    'replace',
    'html::',
    'file::',
]);

if (isset($options['list'])) {
    echo "=== DAFTAR PROYEK & FRAME GREYMIST ===\n";
    $stmt = $pdo->query("
        SELECT p.project_id, p.project_name, p.user_id, f.frame_id, f.frame_name
        FROM gm_projects p
        LEFT JOIN gm_frames f ON p.project_id = f.project_id
        ORDER BY p.project_id DESC, f.sort_order ASC, f.frame_id ASC
    ");
    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        echo "(Belum ada proyek di database)\n";
    } else {
        foreach ($rows as $row) {
            echo sprintf(
                "Project #%d (%s) [User %d] -> Frame #%s (%s)\n",
                (int) $row['project_id'],
                (string) $row['project_name'],
                (int) $row['user_id'],
                (string) ($row['frame_id'] ?? 'belum ada frame'),
                (string) ($row['frame_name'] ?? '-')
            );
        }
    }
    exit(0);
}

$project_id = isset($options['project_id']) ? (int) $options['project_id'] : 0;
$frame_id   = isset($options['frame_id']) ? (int) $options['frame_id'] : 0;
$user_id    = isset($options['user_id']) ? (int) $options['user_id'] : 0;

if ($project_id <= 0) {
    $stmt = $pdo->query("SELECT project_id, user_id FROM gm_projects ORDER BY project_id DESC LIMIT 1");
    $row  = $stmt->fetch();
    if ($row) {
        $project_id = (int) $row['project_id'];
        if ($user_id <= 0) {
            $user_id = (int) $row['user_id'];
        }
    }
}

if ($frame_id <= 0 && $project_id > 0) {
    $stmt = $pdo->prepare("SELECT frame_id FROM gm_frames WHERE project_id = ? ORDER BY sort_order ASC, frame_id ASC LIMIT 1");
    $stmt->execute([$project_id]);
    $row = $stmt->fetch();
    if ($row) {
        $frame_id = (int) $row['frame_id'];
    }
}

// Mode BACA ISI FRAME (--read)
if (isset($options['read'])) {
    $stmt = $pdo->prepare("SELECT frame_id, frame_name, core_content FROM gm_frames WHERE project_id = ? AND frame_id = ? LIMIT 1");
    $stmt->execute([$project_id, $frame_id]);
    $row = $stmt->fetch();
    if (!$row) {
        fwrite(STDERR, "Frame tidak ditemukan untuk project_id={$project_id}, frame_id={$frame_id}\n");
        exit(1);
    }
    $content = (string) ($row['core_content'] ?? '');
    $summary = process_local_frame_command('baca isi frame', $content);
    echo json_encode([
        'status'       => 'OK',
        'project_id'   => $project_id,
        'frame_id'     => (int) $row['frame_id'],
        'frame_name'   => $row['frame_name'],
        'summary'      => $summary['summary'] ?? '',
        'core_content' => $content,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}

// Mode KOSONGKAN FRAME (--clear)
if (isset($options['clear'])) {
    $upd = $pdo->prepare("UPDATE gm_frames SET core_content = '' WHERE project_id = ? AND frame_id = ?");
    $upd->execute([$project_id, $frame_id]);
    echo json_encode([
        'status'     => 'OK',
        'message'    => 'Seluruh isi frame berhasil dikosongkan.',
        'project_id' => $project_id,
        'frame_id'   => $frame_id,
    ], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

// Mode HAPUS KOMPONEN TERTENTU (--delete="navbar")
if (!empty($options['delete'])) {
    $target_keyword = trim((string) $options['delete']);
    $stmt = $pdo->prepare("SELECT core_content FROM gm_frames WHERE project_id = ? AND frame_id = ? LIMIT 1");
    $stmt->execute([$project_id, $frame_id]);
    $row = $stmt->fetch();
    $existing_content = (string) ($row['core_content'] ?? '');

    $cmd_res = process_local_frame_command('hapus ' . $target_keyword, $existing_content);
    if ($cmd_res && $cmd_res['operation'] === 'replace_frame') {
        $upd = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE project_id = ? AND frame_id = ?");
        $upd->execute([$cmd_res['html'], $project_id, $frame_id]);
        echo json_encode([
            'status'     => 'OK',
            'message'    => $cmd_res['summary'] ?? 'Komponen berhasil dihapus.',
            'project_id' => $project_id,
            'frame_id'   => $frame_id,
        ], JSON_PRETTY_PRINT) . "\n";
        exit(0);
    }

    echo json_encode([
        'status'  => 'WARN',
        'message' => "Tidak ditemukan komponen yang cocok dengan kata kunci '{$target_keyword}'.",
    ], JSON_PRETTY_PRINT) . "\n";
    exit(0);
}

$raw_html = '';
if (!empty($options['file']) && is_file((string) $options['file'])) {
    $raw_html = (string) file_get_contents((string) $options['file']);
} elseif (!empty($options['html'])) {
    $raw_html = (string) $options['html'];
}

if (trim($raw_html) === '') {
    fwrite(STDERR, "Error: Parameter --html, --file, --read, --delete, atau --clear wajib diisi.\n");
    exit(1);
}

$clean_html = ensure_gm_object_class($raw_html);
$name       = isset($options['name']) && trim((string) $options['name']) !== '' ? trim((string) $options['name']) : 'Antigravity AI Component';
$mode       = isset($options['mode']) ? strtolower(trim((string) $options['mode'])) : 'both';
$replace    = isset($options['replace']);

if ($user_id <= 0 && $project_id > 0) {
    $stmt = $pdo->prepare("SELECT user_id FROM gm_projects WHERE project_id = ? LIMIT 1");
    $stmt->execute([$project_id]);
    $row = $stmt->fetch();
    if ($row) {
        $user_id = (int) $row['user_id'];
    }
}
if ($user_id <= 0) {
    $user_id = 1;
}

$results = [];

if ($mode === 'component' || $mode === 'both') {
    $comp_id = create_user_component($pdo, $user_id, $name, $clean_html, 'Antigravity AI');
    $results['component_id'] = $comp_id;
}

if (($mode === 'frame' || $mode === 'both') && $project_id > 0) {
    if ($frame_id <= 0) {
        $new_frame = add_project_frame($pdo, $project_id, 'Main Frame', '');
        $frame_id  = (int) $new_frame['frame_id'];
    }

    if ($frame_id > 0) {
        $stmt = $pdo->prepare("SELECT core_content FROM gm_frames WHERE project_id = ? AND frame_id = ? LIMIT 1");
        $stmt->execute([$project_id, $frame_id]);
        $row = $stmt->fetch();
        if ($row) {
            $existing_content = (string) ($row['core_content'] ?? '');
            $new_content      = $replace || trim($existing_content) === ''
                ? $clean_html
                : ($existing_content . "\n" . $clean_html);

            $upd = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE project_id = ? AND frame_id = ?");
            $upd->execute([$new_content, $project_id, $frame_id]);

            $results['injected_project_id'] = $project_id;
            $results['injected_frame_id']   = $frame_id;
        }
    }
}

echo json_encode([
    'status'  => 'OK',
    'message' => 'Komponen berhasil diinjeksi ke Greymist Workspace.',
    'details' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
