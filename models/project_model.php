<?php
/**
 * File: models/project_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_projects dan gm_colors.
 */

if (!function_exists('get_project_by_id')) {
    /**
     * Mengambil data proyek berdasarkan project_id beserta informasi pemiliknya.
     */
    function get_project_by_id(PDO $pdo, int $project_id): ?array
    {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.nickname, u.email,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count
            FROM gm_projects p
            LEFT JOIN gm_users u ON p.user_id = u.user_id
            WHERE p.project_id = ?
            LIMIT 1
        ");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        return $project ?: null;
    }
}

if (!function_exists('get_user_project')) {
    /**
     * Mengambil data proyek milik pengguna tertentu (validasi kepemilikan).
     */
    function get_user_project(PDO $pdo, int $project_id, int $user_id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM gm_projects WHERE project_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$project_id, $user_id]);
        $project = $stmt->fetch();
        return $project ?: null;
    }
}

if (!function_exists('get_user_dashboard_stats')) {
    /**
     * Mengambil statistik proyek pengguna untuk ditampilkan di halaman Dashboard.
     */
    function get_user_dashboard_stats(PDO $pdo, int $user_id): array
    {
        $total = $pdo->prepare("SELECT COUNT(*) FROM gm_projects WHERE user_id = ? AND is_deleted = 0");
        $total->execute([$user_id]);

        $published = $pdo->prepare("SELECT COUNT(*) FROM gm_projects WHERE user_id = ? AND project_status = 'published' AND is_deleted = 0");
        $published->execute([$user_id]);

        $templates = $pdo->prepare("SELECT COUNT(*) FROM gm_projects WHERE user_id = ? AND is_template = 1 AND is_deleted = 0");
        $templates->execute([$user_id]);

        $trashed = $pdo->prepare("SELECT COUNT(*) FROM gm_projects WHERE user_id = ? AND is_deleted = 1");
        $trashed->execute([$user_id]);

        return [
            'total_projects'     => (int)$total->fetchColumn(),
            'published_projects' => (int)$published->fetchColumn(),
            'template_projects'  => (int)$templates->fetchColumn(),
            'trashed_projects'   => (int)$trashed->fetchColumn(),
        ];
    }
}

if (!function_exists('get_platform_stats')) {
    /**
     * Mengambil statistik global seluruh platform untuk halaman Explore, Templates, About, dan Admin.
     */
    function get_platform_stats(PDO $pdo): array
    {
        return [
            'total_users'       => (int)$pdo->query("SELECT COUNT(*) FROM gm_users")->fetchColumn(),
            'total_admins'      => (int)$pdo->query("SELECT COUNT(*) FROM gm_users WHERE is_admin = 1")->fetchColumn(),
            'total_projects'    => (int)$pdo->query("SELECT COUNT(*) FROM gm_projects")->fetchColumn(),
            'published_count'   => (int)$pdo->query("SELECT COUNT(*) FROM gm_projects WHERE project_status = 'published' AND is_deleted = 0")->fetchColumn(),
            'draft_count'       => (int)$pdo->query("SELECT COUNT(*) FROM gm_projects WHERE project_status = 'unpublished'")->fetchColumn(),
            'template_count'    => (int)$pdo->query("SELECT COUNT(*) FROM gm_projects WHERE is_template = 1 AND is_deleted = 0")->fetchColumn(),
            'total_frames'      => (int)$pdo->query("SELECT COUNT(*) FROM gm_frames")->fetchColumn(),
            'total_creators'    => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM gm_projects WHERE project_status = 'published'")->fetchColumn(),
            'template_creators' => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM gm_projects WHERE is_template = 1")->fetchColumn(),
        ];
    }
}

if (!function_exists('get_recent_user_projects')) {
    /**
     * Mengambil daftar proyek terbaru milik pengguna beserta preview frame pertamanya.
     */
    function get_recent_user_projects(PDO $pdo, int $user_id, int $limit = 6): array
    {
        $stmt = $pdo->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            WHERE p.user_id = ? AND p.is_deleted = 0
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_user_projects_filtered')) {
    /**
     * Mengambil daftar proyek aktif pengguna dengan filter pencarian, status, dan pengurutan.
     */
    function get_user_projects_filtered(PDO $pdo, int $user_id, string $search = '', string $status = '', string $sort = 'newest'): array
    {
        $where = ["p.user_id = ?", "p.is_deleted = 0"];
        $params = [$user_id];

        if ($search !== '') {
            $where[] = "p.project_name LIKE ?";
            $params[] = "%{$search}%";
        }
        if ($status === 'published' || $status === 'unpublished') {
            $where[] = "p.project_status = ?";
            $params[] = $status;
        }

        $order_sql = match ($sort) {
            'oldest' => 'p.created_at ASC',
            'name'   => 'p.project_name ASC',
            default  => 'p.created_at DESC',
        };

        $where_sql = implode(' AND ', $where);
        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            WHERE {$where_sql}
            ORDER BY {$order_sql}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_published_projects_filtered')) {
    /**
     * Mengambil daftar proyek publik (Explore) dengan filter pencarian dan urutan.
     */
    function get_published_projects_filtered(PDO $pdo, string $search = '', string $sort = 'newest'): array
    {
        $where = ["p.project_status = 'published'"];
        $params = [];

        if ($search !== '') {
            $where[] = "(p.project_name LIKE ? OR u.username LIKE ? OR u.nickname LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }

        $order_sql = match ($sort) {
            'oldest' => 'p.created_at ASC',
            'name'   => 'p.project_name ASC',
            default  => 'p.created_at DESC',
        };

        $where_sql = implode(' AND ', $where);
        $sql = "
            SELECT p.*, u.username, u.nickname, u.profile AS author_avatar,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            LEFT JOIN gm_users u ON p.user_id = u.user_id
            WHERE {$where_sql}
            ORDER BY {$order_sql}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_templates_filtered')) {
    /**
     * Mengambil daftar proyek yang ditandai sebagai template (is_template = 1).
     */
    function get_templates_filtered(PDO $pdo, string $search = '', string $sort = 'newest'): array
    {
        $where = ["p.is_template = 1"];
        $params = [];

        if ($search !== '') {
            $where[] = "(p.project_name LIKE ? OR u.username LIKE ? OR u.nickname LIKE ?)";
            $like = "%{$search}%";
            $params = [$like, $like, $like];
        }

        $order_sql = match ($sort) {
            'oldest' => 'p.created_at ASC',
            'name'   => 'p.project_name ASC',
            default  => 'p.created_at DESC',
        };

        $where_sql = implode(' AND ', $where);
        $sql = "
            SELECT p.*, u.username, u.nickname, u.email, u.profile AS author_avatar,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            LEFT JOIN gm_users u ON p.user_id = u.user_id
            WHERE {$where_sql}
            ORDER BY {$order_sql}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_trashed_projects_filtered')) {
    /**
     * Mengambil daftar proyek di tempat sampah (is_deleted = 1) milik pengguna.
     */
    function get_trashed_projects_filtered(PDO $pdo, int $user_id, string $search = '', string $sort = 'newest'): array
    {
        $where = ["p.user_id = ?", "p.is_deleted = 1"];
        $params = [$user_id];

        if ($search !== '') {
            $where[] = "p.project_name LIKE ?";
            $params[] = "%{$search}%";
        }

        $order_sql = match ($sort) {
            'oldest' => 'p.created_at ASC',
            'name'   => 'p.project_name ASC',
            default  => 'p.created_at DESC',
        };

        $where_sql = implode(' AND ', $where);
        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            WHERE {$where_sql}
            ORDER BY {$order_sql}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('create_project_with_default_frame')) {
    /**
     * Membuat proyek baru beserta 1 frame awal ('Main Frame') di dalam transaksi database.
     */
    function create_project_with_default_frame(PDO $pdo, int $user_id, string $project_name = 'Untitled Project'): int
    {
        $name = trim($project_name) !== '' ? trim($project_name) : 'Untitled Project';
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO gm_projects (user_id, project_name, project_status, created_at)
                VALUES (?, ?, 'unpublished', NOW())
            ");
            $stmt->execute([$user_id, $name]);
            $project_id = (int)$pdo->lastInsertId();

            $stmt_frame = $pdo->prepare("
                INSERT INTO gm_frames (project_id, frame_name, core_content, sort_order)
                VALUES (?, 'Main Frame', '', 0)
            ");
            $stmt_frame->execute([$project_id]);

            $pdo->commit();
            return $project_id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

if (!function_exists('rename_user_project')) {
    /**
     * Mengubah nama proyek milik pengguna.
     */
    function rename_user_project(PDO $pdo, int $project_id, int $user_id, string $new_name): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET project_name = ? WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$new_name, $project_id, $user_id]);
        return $stmt->rowCount() >= 0;
    }
}

if (!function_exists('toggle_user_project_status')) {
    /**
     * Mengubah status proyek antara 'published' dan 'unpublished'.
     */
    function toggle_user_project_status(PDO $pdo, int $project_id, ?int $user_id = null): bool
    {
        if ($user_id !== null) {
            $stmt = $pdo->prepare("
                UPDATE gm_projects
                SET project_status = IF(project_status = 'published', 'unpublished', 'published')
                WHERE project_id = ? AND user_id = ?
            ");
            return $stmt->execute([$project_id, $user_id]);
        }
        $stmt = $pdo->prepare("
            UPDATE gm_projects
            SET project_status = IF(project_status = 'published', 'unpublished', 'published')
            WHERE project_id = ?
        ");
        return $stmt->execute([$project_id]);
    }
}

if (!function_exists('toggle_user_project_template')) {
    /**
     * Mengubah status template proyek (is_template 0 <-> 1).
     */
    function toggle_user_project_template(PDO $pdo, int $project_id, ?int $user_id = null): bool
    {
        if ($user_id !== null) {
            $stmt = $pdo->prepare("UPDATE gm_projects SET is_template = NOT is_template WHERE project_id = ? AND user_id = ?");
            return $stmt->execute([$project_id, $user_id]);
        }
        $stmt = $pdo->prepare("UPDATE gm_projects SET is_template = NOT is_template WHERE project_id = ?");
        return $stmt->execute([$project_id]);
    }
}

if (!function_exists('remove_project_from_templates')) {
    /**
     * Mencopot status template dari suatu proyek (is_template = 0).
     */
    function remove_project_from_templates(PDO $pdo, int $project_id): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET is_template = 0 WHERE project_id = ?");
        return $stmt->execute([$project_id]);
    }
}

if (!function_exists('clone_existing_project')) {
    /**
     * Menduplikasi proyek (beserta seluruh frame dan variabel warnanya) untuk pengguna tertentu.
     */
    function clone_existing_project(
        PDO $pdo,
        int $source_project_id,
        int $target_user_id,
        string $requirement_mode = 'published_or_template',
        string $suffix = ' (Copy)'
    ): ?int {
        $condition = match ($requirement_mode) {
            'published' => "AND project_status = 'published'",
            'template'  => "AND is_template = 1",
            default     => "AND (project_status = 'published' OR is_template = 1 OR user_id = " . (int)$target_user_id . ")",
        };

        $stmt = $pdo->prepare("SELECT * FROM gm_projects WHERE project_id = ? {$condition} LIMIT 1");
        $stmt->execute([$source_project_id]);
        $source = $stmt->fetch();
        if (!$source) {
            return null;
        }

        $pdo->beginTransaction();
        try {
            $new_name = $source['project_name'] . $suffix;
            $ins = $pdo->prepare("
                INSERT INTO gm_projects (user_id, project_name, thumbnail, project_status, is_template, color_tokens, bg_color_settings, created_at)
                VALUES (?, ?, ?, 'unpublished', 0, ?, ?, NOW())
            ");
            $ins->execute([
                $target_user_id,
                $new_name,
                $source['thumbnail'],
                $source['color_tokens'],
                $source['bg_color_settings'],
            ]);
            $new_project_id = (int)$pdo->lastInsertId();

            // Salin seluruh frame
            $frames_stmt = $pdo->prepare("SELECT * FROM gm_frames WHERE project_id = ? ORDER BY sort_order ASC, frame_id ASC");
            $frames_stmt->execute([$source_project_id]);
            $frames = $frames_stmt->fetchAll();

            $ins_frame = $pdo->prepare("
                INSERT INTO gm_frames (project_id, frame_name, core_content, thumbnail, sort_order, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            foreach ($frames as $frame) {
                $ins_frame->execute([
                    $new_project_id,
                    $frame['frame_name'],
                    $frame['core_content'],
                    $frame['thumbnail'],
                    (int)$frame['sort_order'],
                ]);
            }

            // Salin variabel warna (gm_colors)
            $colors_stmt = $pdo->prepare("SELECT * FROM gm_colors WHERE project_id = ?");
            $colors_stmt->execute([$source_project_id]);
            $colors = $colors_stmt->fetchAll();

            $ins_color = $pdo->prepare("INSERT INTO gm_colors (project_id, color_variable) VALUES (?, ?)");
            foreach ($colors as $color) {
                $ins_color->execute([$new_project_id, $color['color_variable']]);
            }

            $pdo->commit();
            return $new_project_id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

if (!function_exists('soft_delete_user_project')) {
    /**
     * Memindahkan proyek pengguna ke tempat sampah (is_deleted = 1).
     */
    function soft_delete_user_project(PDO $pdo, int $project_id, int $user_id): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET is_deleted = 1 WHERE project_id = ? AND user_id = ?");
        return $stmt->execute([$project_id, $user_id]);
    }
}

if (!function_exists('restore_user_project')) {
    /**
     * Memulihkan proyek pengguna dari tempat sampah (is_deleted = 0).
     */
    function restore_user_project(PDO $pdo, int $project_id, int $user_id): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET is_deleted = 0 WHERE project_id = ? AND user_id = ?");
        return $stmt->execute([$project_id, $user_id]);
    }
}

if (!function_exists('permanent_delete_project')) {
    /**
     * Menghapus proyek secara permanen dari database.
     */
    function permanent_delete_project(PDO $pdo, int $project_id, ?int $user_id = null): bool
    {
        if ($user_id !== null) {
            $stmt = $pdo->prepare("DELETE FROM gm_projects WHERE project_id = ? AND user_id = ?");
            return $stmt->execute([$project_id, $user_id]);
        }
        $stmt = $pdo->prepare("DELETE FROM gm_projects WHERE project_id = ?");
        return $stmt->execute([$project_id]);
    }
}

if (!function_exists('get_project_colors_list')) {
    /**
     * Mengambil daftar color_variable dari tabel gm_colors untuk proyek tertentu.
     */
    function get_project_colors_list(PDO $pdo, int $project_id): array
    {
        $stmt = $pdo->prepare("SELECT color_variable FROM gm_colors WHERE project_id = ?");
        $stmt->execute([$project_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if (!function_exists('get_project_color_tokens')) {
    /**
     * Mengambil JSON color_tokens dari proyek pengguna.
     */
    function get_project_color_tokens(PDO $pdo, int $project_id, int $user_id): ?array
    {
        $stmt = $pdo->prepare("SELECT color_tokens FROM gm_projects WHERE project_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$project_id, $user_id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $tokens = !empty($row['color_tokens']) ? json_decode($row['color_tokens'], true) : [];
        return is_array($tokens) ? $tokens : [];
    }
}

if (!function_exists('save_project_color_tokens')) {
    /**
     * Menyimpan array color_tokens ke dalam kolom JSON pada gm_projects.
     */
    function save_project_color_tokens(PDO $pdo, int $project_id, int $user_id, array $tokens): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET color_tokens = ? WHERE project_id = ? AND user_id = ?");
        return $stmt->execute([json_encode($tokens), $project_id, $user_id]);
    }
}

if (!function_exists('get_project_bg_color_settings')) {
    /**
     * Mengambil pengaturan warna background kanvas proyek.
     */
    function get_project_bg_color_settings(PDO $pdo, int $project_id, int $user_id): ?array
    {
        $stmt = $pdo->prepare("SELECT bg_color_settings FROM gm_projects WHERE project_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$project_id, $user_id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $settings = !empty($row['bg_color_settings']) ? json_decode($row['bg_color_settings'], true) : null;
        return is_array($settings) ? $settings : [
            'hex'          => '#FFFFFF',
            'opacity'      => '100',
            'is_tokenized' => false,
            'token_name'   => '',
        ];
    }
}

if (!function_exists('save_project_bg_color_settings')) {
    /**
     * Menyimpan pengaturan warna background kanvas proyek.
     */
    function save_project_bg_color_settings(PDO $pdo, int $project_id, int $user_id, array $settings): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_projects SET bg_color_settings = ? WHERE project_id = ? AND user_id = ?");
        return $stmt->execute([json_encode($settings), $project_id, $user_id]);
    }
}

if (!function_exists('get_all_projects_admin')) {
    /**
     * Mengambil seluruh daftar proyek untuk panel Admin dengan filter pencarian, status, dan template.
     */
    function get_all_projects_admin(PDO $pdo, string $search = '', string $status = '', string $template = ''): array
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(p.project_name LIKE ? OR u.username LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
        }
        if ($status === 'published' || $status === 'unpublished') {
            $where[] = "p.project_status = ?";
            $params[] = $status;
        }
        if ($template === 'yes') {
            $where[] = "p.is_template = 1";
        } elseif ($template === 'no') {
            $where[] = "p.is_template = 0";
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "
            SELECT p.*, u.username, u.nickname, u.email,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count,
                   (SELECT f2.core_content FROM gm_frames f2 WHERE f2.project_id = p.project_id ORDER BY f2.sort_order ASC, f2.frame_id ASC LIMIT 1) AS first_frame_content
            FROM gm_projects p
            LEFT JOIN gm_users u ON p.user_id = u.user_id
            {$where_sql}
            ORDER BY p.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('get_recent_projects_admin')) {
    /**
     * Mengambil proyek terbaru di seluruh platform untuk Dashboard Admin.
     */
    function get_recent_projects_admin(PDO $pdo, int $limit = 5): array
    {
        $stmt = $pdo->prepare("
            SELECT p.*, u.username, u.nickname,
                   (SELECT COUNT(*) FROM gm_frames f WHERE f.project_id = p.project_id) AS frame_count
            FROM gm_projects p
            LEFT JOIN gm_users u ON p.user_id = u.user_id
            ORDER BY p.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
