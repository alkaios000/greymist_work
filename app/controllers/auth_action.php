<?php
/**
 * File: app/controllers/auth_action.php
 * Fungsi: Menangani proses autentikasi pengguna (login, register, logout, admin_login, admin_logout, delete_account)
 *         serta sinkronisasi kredensial default dari file .env.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/user_model.php';

/**
 * Menyinkronkan dan memverifikasi kredensial yang dikonfigurasi di .env
 * agar akun default selalu dapat digunakan login.
 */
if (!function_exists('verify_and_sync_env_credentials')) {
    function verify_and_sync_env_credentials(PDO $pdo, string $identifier, string $password): ?array
    {
        $env_accounts = [
            [
                'nickname' => (string)env('ADMIN_NICKNAME', 'Greymist Admin'),
                'username' => (string)env('ADMIN_USERNAME', 'admin'),
                'email'    => (string)env('ADMIN_EMAIL', 'admin@greymist.com'),
                'password' => (string)env('ADMIN_PASSWORD', 'admin123'),
                'is_admin' => 1,
            ],
            [
                'nickname' => (string)env('ADMIN_NICKNAME', 'Greymist Admin'),
                'username' => (string)env('ADMIN_ALT_USERNAME', 'greymist'),
                'email'    => 'greymist_admin@greymist.com',
                'password' => (string)env('ADMIN_ALT_PASSWORD', 'admin123'),
                'is_admin' => 1,
            ],
            [
                'nickname' => (string)env('USER_NICKNAME', 'Nasuha'),
                'username' => (string)env('USER_USERNAME', 'nasuha'),
                'email'    => (string)env('USER_EMAIL', 'nasuha@greymist.com'),
                'password' => (string)env('USER_PASSWORD', '123456'),
                'is_admin' => 0,
            ],
        ];

        foreach ($env_accounts as $acc) {
            if ($acc['username'] === '' || $acc['password'] === '') {
                continue;
            }
            $matches_id = (strcasecmp($identifier, $acc['username']) === 0 || strcasecmp($identifier, $acc['email']) === 0);
            if ($matches_id && $password === $acc['password']) {
                $existing = get_user_by_username_or_email($pdo, $acc['username'])
                    ?: get_user_by_username_or_email($pdo, $acc['email']);

                $hashed = password_hash($acc['password'], PASSWORD_DEFAULT);
                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE gm_users SET password = ?, is_admin = ? WHERE user_id = ?");
                    $stmt->execute([$hashed, $acc['is_admin'], $existing['user_id']]);
                    return get_user_by_id($pdo, (int)$existing['user_id']);
                }

                $new_id = create_user($pdo, $acc['nickname'], $acc['username'], $acc['email'], '', $hashed, $acc['is_admin']);
                return get_user_by_id($pdo, $new_id);
            }
        }
        return null;
    }
}

$auth_action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($auth_action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(route_url('login'));
        }
        verify_csrf_middleware();

        $identifier = trim($_POST['username'] ?? '');
        $password   = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username/email dan password wajib diisi.';
            redirect(route_url('login'));
        }

        $user = get_user_by_username_or_email($pdo, $identifier);
        if (!$user || !password_verify($password, $user['password'])) {
            $user = verify_and_sync_env_credentials($pdo, $identifier, $password);
        }

        if ($user) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nickname'] = $user['nickname'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['is_admin'] = (int)$user['is_admin'];

            update_user_last_login($pdo, (int)$user['user_id']);

            if ((int)$user['is_admin'] === 1) {
                redirect(route_url('admin_dashboard'));
            }
            redirect(route_url('dashboard'));
        }

        $_SESSION['flash_error'] = 'Username/email atau password salah.';
        redirect(route_url('login'));
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(route_url('register'));
        }
        verify_csrf_middleware();

        if (empty($site_settings['allow_registrations'])) {
            $_SESSION['flash_error'] = 'Pendaftaran pengguna baru saat ini sedang ditutup oleh Administrator.';
            redirect(route_url('register'));
        }

        $nickname         = trim($_POST['nickname'] ?? '');
        $username         = trim($_POST['username'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $phone            = trim($_POST['phone'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($nickname === '' || $username === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
            $_SESSION['flash_error'] = 'Seluruh kolom pendaftaran wajib diisi.';
            redirect(route_url('register'));
        }

        if ($password !== $confirm_password) {
            $_SESSION['flash_error'] = 'Konfirmasi password tidak cocok.';
            redirect(route_url('register'));
        }

        if (strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Password minimal harus terdiri dari 6 karakter.';
            redirect(route_url('register'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format alamat email tidak valid.';
            redirect(route_url('register'));
        }

        if (is_username_or_email_taken($pdo, $username, $email)) {
            $_SESSION['flash_error'] = 'Username atau email sudah terdaftar.';
            redirect(route_url('register'));
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        create_user($pdo, $nickname, $username, $email, $phone, $hashed_password, 0);

        $_SESSION['flash_success'] = 'Pendaftaran berhasil! Silakan login ke akun Anda.';
        redirect(route_url('login'));
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect(route_url('login'));
        break;

    case 'admin_login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(route_url('admin_login'));
        }
        verify_csrf_middleware();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username dan password wajib diisi.';
            redirect(route_url('admin_login'));
        }

        $admin = get_admin_by_username($pdo, $username);
        if (!$admin || !password_verify($password, $admin['password'])) {
            $synced = verify_and_sync_env_credentials($pdo, $username, $password);
            if ($synced && (int)$synced['is_admin'] === 1) {
                $admin = $synced;
            } else {
                $admin = null;
            }
        }

        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$admin['user_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['nickname'] = $admin['nickname'];
            $_SESSION['email']    = $admin['email'];
            $_SESSION['is_admin'] = 1;

            update_user_last_login($pdo, (int)$admin['user_id']);
            redirect(route_url('admin_dashboard'));
        }

        $_SESSION['flash_error'] = 'Kredensial tidak valid atau Anda bukan Administrator.';
        redirect(route_url('admin_login'));
        break;

    case 'admin_logout':
        $_SESSION = [];
        session_destroy();
        redirect(route_url('admin_login'));
        break;

    case 'delete_account':
        require_auth();
        verify_csrf_middleware();
        $uid       = current_user_id();
        $user_data = get_user_by_id($pdo, $uid);

        if ($user_data && !empty($user_data['profile'])) {
            $old_path = ROOT_PATH . '/' . preg_replace('#^(\.\./)+#', '', $user_data['profile']);
            if (is_file($old_path)) {
                @unlink($old_path);
            }
        }

        delete_user_by_id($pdo, $uid);
        session_destroy();
        redirect(route_url('login'));
        break;

    default:
        redirect(route_url('login'));
}
