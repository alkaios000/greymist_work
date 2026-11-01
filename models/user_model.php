<?php
/**
 * File: models/user_model.php
 * Fungsi: Mengelola seluruh query database yang berkaitan dengan tabel gm_users.
 */

if (!function_exists('get_user_by_id')) {
    /**
     * Mengambil data lengkap pengguna berdasarkan user_id.
     */
    function get_user_by_id(PDO $pdo, int $user_id): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM gm_users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

if (!function_exists('get_user_by_username_or_email')) {
    /**
     * Mencari pengguna berdasarkan username atau alamat email (untuk proses login).
     */
    function get_user_by_username_or_email(PDO $pdo, string $identifier): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM gm_users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

if (!function_exists('get_admin_by_username')) {
    /**
     * Mencari pengguna dengan status administrator (is_admin = 1) berdasarkan username.
     */
    function get_admin_by_username(PDO $pdo, string $username): ?array
    {
        $stmt = $pdo->prepare("SELECT * FROM gm_users WHERE username = ? AND is_admin = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}

if (!function_exists('is_username_or_email_taken')) {
    /**
     * Memeriksa apakah username atau email sudah digunakan oleh pengguna lain.
     */
    function is_username_or_email_taken(PDO $pdo, string $username, string $email, ?int $exclude_user_id = null): bool
    {
        if ($exclude_user_id !== null) {
            $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE (username = ? OR email = ?) AND user_id != ? LIMIT 1");
            $stmt->execute([$username, $email, $exclude_user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
        }
        return (bool)$stmt->fetch();
    }
}

if (!function_exists('create_user')) {
    /**
     * Mendaftarkan pengguna baru ke dalam tabel gm_users.
     */
    function create_user(
        PDO $pdo,
        string $nickname,
        string $username,
        string $email,
        string $phone,
        string $hashed_password,
        int $is_admin = 0
    ): int {
        $stmt = $pdo->prepare("
            INSERT INTO gm_users (nickname, username, email, phone, password, is_admin, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nickname, $username, $email, $phone, $hashed_password, $is_admin]);
        return (int)$pdo->lastInsertId();
    }
}

if (!function_exists('update_user_last_login')) {
    /**
     * Memperbarui waktu login terakhir pengguna.
     */
    function update_user_last_login(PDO $pdo, int $user_id): void
    {
        $stmt = $pdo->prepare("UPDATE gm_users SET last_login = NOW() WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
}

if (!function_exists('update_user_profile')) {
    /**
     * Memperbarui informasi profil dasar pengguna (nickname, username, email, phone).
     */
    function update_user_profile(PDO $pdo, int $user_id, string $nickname, string $username, string $email, string $phone): bool
    {
        $stmt = $pdo->prepare("
            UPDATE gm_users
            SET nickname = ?, username = ?, email = ?, phone = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([$nickname, $username, $email, $phone, $user_id]);
    }
}

if (!function_exists('update_user_password')) {
    /**
     * Memperbarui password pengguna yang sudah di-hash.
     */
    function update_user_password(PDO $pdo, int $user_id, string $hashed_password): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_users SET password = ? WHERE user_id = ?");
        return $stmt->execute([$hashed_password, $user_id]);
    }
}

if (!function_exists('update_user_avatar')) {
    /**
     * Memperbarui path foto profil pengguna.
     */
    function update_user_avatar(PDO $pdo, int $user_id, ?string $avatar_path): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_users SET profile = ? WHERE user_id = ?");
        return $stmt->execute([$avatar_path, $user_id]);
    }
}

if (!function_exists('delete_user_by_id')) {
    /**
     * Menghapus akun pengguna beserta seluruh relasi datanya (ON DELETE CASCADE).
     */
    function delete_user_by_id(PDO $pdo, int $user_id): bool
    {
        $stmt = $pdo->prepare("DELETE FROM gm_users WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}

if (!function_exists('get_all_users_filtered')) {
    /**
     * Mengambil daftar seluruh pengguna untuk halaman Admin Users dengan filter pencarian dan role (opsional paginasi).
     */
    function get_all_users_filtered(
        PDO $pdo,
        string $search = '',
        string $role = '',
        int $page = 0,
        int $per_page = 10
    ): array {
        $where_clauses = [];
        $params = [];

        if ($search !== '') {
            $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ? OR u.nickname LIKE ?)";
            $like = "%{$search}%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($role === 'admin') {
            $where_clauses[] = "u.is_admin = 1";
        } elseif ($role === 'user') {
            $where_clauses[] = "u.is_admin = 0";
        }

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        if ($page > 0) {
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM gm_users u {$where_sql}");
            $count_stmt->execute($params);
            $total_count = (int)$count_stmt->fetchColumn();

            $pagination = paginate_data($total_count, $page, $per_page);
            $limit      = (int)$pagination['per_page'];
            $offset     = (int)$pagination['offset'];

            $sql = "
                SELECT u.*,
                       (SELECT COUNT(*) FROM gm_projects p WHERE p.user_id = u.user_id) AS project_count
                FROM gm_users u
                {$where_sql}
                ORDER BY u.created_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return [
                'items'      => $stmt->fetchAll(),
                'pagination' => $pagination,
            ];
        }

        $sql = "
            SELECT u.*,
                   (SELECT COUNT(*) FROM gm_projects p WHERE p.user_id = u.user_id) AS project_count
            FROM gm_users u
            {$where_sql}
            ORDER BY u.created_at DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_update_user_details')) {
    /**
     * Memperbarui detail pengguna dari panel Administrator.
     */
    function admin_update_user_details(
        PDO $pdo,
        int $user_id,
        string $username,
        string $email,
        string $nickname,
        string $phone,
        int $is_admin
    ): bool {
        $stmt = $pdo->prepare("
            UPDATE gm_users
            SET username = ?, email = ?, nickname = ?, phone = ?, is_admin = ?
            WHERE user_id = ?
        ");
        return $stmt->execute([$username, $email, $nickname, $phone, $is_admin, $user_id]);
    }
}

if (!function_exists('toggle_user_admin_role')) {
    /**
     * Mengubah status role administrator seorang pengguna (toggle 0 <-> 1).
     */
    function toggle_user_admin_role(PDO $pdo, int $user_id): bool
    {
        $stmt = $pdo->prepare("UPDATE gm_users SET is_admin = NOT is_admin WHERE user_id = ?");
        return $stmt->execute([$user_id]);
    }
}

if (!function_exists('get_recent_users_admin')) {
    /**
     * Mengambil pengguna terbaru beserta jumlah proyeknya untuk dashboard Admin.
     */
    function get_recent_users_admin(PDO $pdo, int $limit = 5): array
    {
        $stmt = $pdo->prepare("
            SELECT u.*,
                   (SELECT COUNT(*) FROM gm_projects p WHERE p.user_id = u.user_id) AS project_count
            FROM gm_users u
            ORDER BY u.created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

