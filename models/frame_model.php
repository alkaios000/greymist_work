<?php
/**
 * File: models/frame_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_frames.
 */

if (!function_exists('get_project_frames')) {
    /**
     * Mengambil seluruh frame dari suatu proyek secara berurutan berdasarkan sort_order.
     */
    function get_project_frames(PDO $pdo, int $project_id): array
    {
        $stmt = $pdo->prepare("
            SELECT frame_id, frame_name, core_content, thumbnail, sort_order, created_at
            FROM gm_frames
            WHERE project_id = ?
            ORDER BY sort_order ASC, frame_id ASC
        ");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_frame_with_owner')) {
    /**
     * Mengambil data frame sekaligus memverifikasi kepemilikan proyek oleh user_id.
     */
    function get_frame_with_owner(PDO $pdo, int $frame_id, int $user_id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT f.*
            FROM gm_frames f
            INNER JOIN gm_projects p ON f.project_id = p.project_id
            WHERE f.frame_id = ? AND p.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$frame_id, $user_id]);
        $frame = $stmt->fetch();
        return $frame ?: null;
    }
}

if (!function_exists('add_project_frame')) {
    /**
     * Menambahkan frame baru ke dalam suatu proyek pada urutan terakhir.
     */
    function add_project_frame(PDO $pdo, int $project_id, string $frame_name = 'Untitled Frame', string $core_content = ''): array
    {
        $stmt_order = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM gm_frames WHERE project_id = ?");
        $stmt_order->execute([$project_id]);
        $next_order = (int)$stmt_order->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO gm_frames (project_id, frame_name, core_content, sort_order, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$project_id, $frame_name, $core_content, $next_order]);
        $new_id = (int)$pdo->lastInsertId();

        return [
            'frame_id'     => $new_id,
            'frame_name'   => $frame_name,
            'core_content' => $core_content,
            'sort_order'   => $next_order,
        ];
    }
}

if (!function_exists('copy_project_frame')) {
    /**
     * Menduplikasi suatu frame di dalam proyek yang sama.
     */
    function copy_project_frame(PDO $pdo, int $frame_id, int $user_id): ?array
    {
        $original = get_frame_with_owner($pdo, $frame_id, $user_id);
        if (!$original) {
            return null;
        }
        $new_name = $original['frame_name'] . ' copy';
        return add_project_frame($pdo, (int)$original['project_id'], $new_name, (string)$original['core_content']);
    }
}

if (!function_exists('rename_project_frame')) {
    /**
     * Mengubah nama suatu frame setelah memverifikasi kepemilikan proyek.
     */
    function rename_project_frame(PDO $pdo, int $frame_id, int $user_id, string $new_name): bool
    {
        $frame = get_frame_with_owner($pdo, $frame_id, $user_id);
        if (!$frame) {
            return false;
        }
        $stmt = $pdo->prepare("UPDATE gm_frames SET frame_name = ? WHERE frame_id = ?");
        return $stmt->execute([$new_name, $frame_id]);
    }
}

if (!function_exists('delete_project_frame')) {
    /**
     * Menghapus frame dari proyek (minimal harus menyisakan 1 frame di dalam proyek).
     */
    function delete_project_frame(PDO $pdo, int $frame_id, int $user_id): array
    {
        $frame = get_frame_with_owner($pdo, $frame_id, $user_id);
        if (!$frame) {
            return ['success' => false, 'message' => 'Frame tidak ditemukan atau akses ditolak.'];
        }

        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM gm_frames WHERE project_id = ?");
        $count_stmt->execute([$frame['project_id']]);
        $total_frames = (int)$count_stmt->fetchColumn();

        if ($total_frames <= 1) {
            return ['success' => false, 'message' => 'Tidak dapat menghapus satu-satunya frame di dalam proyek.'];
        }

        $del = $pdo->prepare("DELETE FROM gm_frames WHERE frame_id = ?");
        $del->execute([$frame_id]);
        return ['success' => true, 'message' => 'Frame berhasil dihapus.'];
    }
}

if (!function_exists('save_single_frame_content')) {
    /**
     * Menyimpan HTML core_content pada satu frame atau memperbarui frame pertama proyek.
     */
    function save_single_frame_content(PDO $pdo, int $project_id, string $core_content, ?int $frame_id = null): bool
    {
        if ($frame_id !== null && $frame_id > 0) {
            $stmt = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE frame_id = ? AND project_id = ?");
            return $stmt->execute([$core_content, $frame_id, $project_id]);
        }

        $stmt_find = $pdo->prepare("SELECT frame_id FROM gm_frames WHERE project_id = ? ORDER BY sort_order ASC, frame_id ASC LIMIT 1");
        $stmt_find->execute([$project_id]);
        $existing = $stmt_find->fetch();

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE frame_id = ?");
            return $stmt->execute([$core_content, $existing['frame_id']]);
        }

        $stmt = $pdo->prepare("INSERT INTO gm_frames (project_id, frame_name, core_content, sort_order) VALUES (?, 'Main Frame', ?, 0)");
        return $stmt->execute([$project_id, $core_content]);
    }
}

if (!function_exists('sync_project_frames')) {
    /**
     * Menyinkronkan daftar frame (urutan, nama, dan isi HTML) pada saat penyimpanan workspace.
     */
    function sync_project_frames(PDO $pdo, int $project_id, array $frames): void
    {
        $pdo->beginTransaction();
        try {
            $stmt_existing = $pdo->prepare("SELECT frame_id FROM gm_frames WHERE project_id = ?");
            $stmt_existing->execute([$project_id]);
            $existing_ids = $stmt_existing->fetchAll(PDO::FETCH_COLUMN);

            $sent_ids = [];
            $stmt_update = $pdo->prepare("
                UPDATE gm_frames
                SET frame_name = ?, core_content = ?, sort_order = ?
                WHERE frame_id = ? AND project_id = ?
            ");
            $stmt_insert = $pdo->prepare("
                INSERT INTO gm_frames (project_id, frame_name, core_content, sort_order)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($frames as $index => $frame) {
                $fid   = isset($frame['frame_id']) && is_numeric($frame['frame_id']) ? (int)$frame['frame_id'] : null;
                $fname = $frame['frame_name'] ?? ('Frame ' . ($index + 1));
                $fcore = $frame['core_content'] ?? '';

                if ($fid && in_array($fid, $existing_ids)) {
                    $stmt_update->execute([$fname, $fcore, $index, $fid, $project_id]);
                    $sent_ids[] = $fid;
                } else {
                    $stmt_insert->execute([$project_id, $fname, $fcore, $index]);
                    $sent_ids[] = (int)$pdo->lastInsertId();
                }
            }

            $to_delete = array_diff($existing_ids, $sent_ids);
            if (!empty($to_delete)) {
                $placeholders = implode(',', array_fill(0, count($to_delete), '?'));
                $stmt_del = $pdo->prepare("DELETE FROM gm_frames WHERE project_id = ? AND frame_id IN ({$placeholders})");
                $stmt_del->execute(array_merge([$project_id], array_values($to_delete)));
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

