<?php
/**
 * File: app/controllers/admin_action.php
 * Fungsi: Menangani seluruh aksi manajemen dari Panel Administrator (pengguna, proyek, template, pengaturan).
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/user_model.php';
require_once ROOT_PATH . '/models/project_model.php';

$admin_action = $_GET['action'] ?? $_POST['action'] ?? '';
$is_json_call = in_array($admin_action, ['admin_get_user', 'admin_get_project'], true);

require_admin($is_json_call);
if (!$is_json_call) {
    verify_csrf_middleware();
}

switch ($admin_action) {
    case 'admin_get_user':
        $target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($target_id <= 0) {
            json_response(['error' => 'ID pengguna tidak valid.'], 400);
        }
        $u = get_user_by_id($pdo, $target_id);
        if (!$u) {
            json_response(['error' => 'Pengguna tidak ditemukan.'], 404);
        }
        unset($u['password']);
        json_response($u);
        break;

    case 'admin_get_project':
        $project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($project_id <= 0) {
            json_response(['error' => 'ID proyek tidak valid.'], 400);
        }
        $p = get_project_by_id($pdo, $project_id);
        if (!$p) {
            json_response(['error' => 'Proyek tidak ditemukan.'], 404);
        }
        json_response($p);
        break;

    case 'admin_add_user':
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $nickname = trim($_POST['nickname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $is_adm   = isset($_POST['is_admin']) ? 1 : 0;

        if ($username === '' || $email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username, email, dan password wajib diisi.';
            redirect(route_url('admin_users'));
        }
        if (is_username_or_email_taken($pdo, $username, $email)) {
            $_SESSION['flash_error'] = 'Username atau email sudah terdaftar.';
            redirect(route_url('admin_users'));
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        create_user($pdo, $nickname, $username, $email, $phone, $hashed, $is_adm);
        $_SESSION['flash_success'] = "Pengguna '{$username}' berhasil ditambahkan.";
        redirect(route_url('admin_users'));
        break;

    case 'admin_update_user':
        $target_id = (int)($_POST['user_id'] ?? 0);
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $nickname  = trim($_POST['nickname'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $is_adm    = isset($_POST['is_admin']) ? 1 : 0;

        if ($target_id <= 0 || $username === '' || $email === '') {
            $_SESSION['flash_error'] = 'Data pembaruan pengguna tidak lengkap.';
            redirect(route_url('admin_users'));
        }
        if (is_username_or_email_taken($pdo, $username, $email, $target_id)) {
            $_SESSION['flash_error'] = 'Username atau email sudah digunakan pengguna lain.';
            redirect(route_url('admin_users'));
        }
        admin_update_user_details($pdo, $target_id, $username, $email, $nickname, $phone, $is_adm);
        $_SESSION['flash_success'] = 'Data pengguna berhasil diperbarui.';
        redirect(route_url('admin_users'));
        break;

    case 'admin_reset_password':
        $target_id    = (int)($_POST['user_id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        if ($target_id <= 0 || strlen($new_password) < 6) {
            $_SESSION['flash_error'] = 'Password baru minimal harus 6 karakter.';
            redirect(route_url('admin_users'));
        }
        update_user_password($pdo, $target_id, password_hash($new_password, PASSWORD_DEFAULT));
        $_SESSION['flash_success'] = 'Password pengguna berhasil direset.';
        redirect(route_url('admin_users'));
        break;

    case 'admin_toggle_admin':
        $target_id = (int)($_POST['user_id'] ?? 0);
        if ($target_id === current_user_id()) {
            $_SESSION['flash_error'] = 'Anda tidak dapat mencopot status Admin milik Anda sendiri.';
            redirect(route_url('admin_users'));
        }
        if ($target_id > 0) {
            toggle_user_admin_role($pdo, $target_id);
            $_SESSION['flash_success'] = 'Hak akses pengguna berhasil diperbarui.';
        }
        redirect(route_url('admin_users'));
        break;

    case 'admin_delete_user':
        $target_id = (int)($_POST['user_id'] ?? 0);
        if ($target_id === current_user_id()) {
            $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri dari panel ini.';
            redirect(route_url('admin_users'));
        }
        if ($target_id > 0) {
            delete_user_by_id($pdo, $target_id);
            $_SESSION['flash_success'] = 'Pengguna beserta seluruh proyeknya berhasil dihapus.';
        }
        redirect(route_url('admin_users'));
        break;

    case 'admin_delete_project':
        $project_id  = (int)($_POST['project_id'] ?? 0);
        $return_page = $_POST['return_page'] ?? 'admin_projects';
        if ($project_id > 0) {
            permanent_delete_project($pdo, $project_id);
            $_SESSION['flash_success'] = 'Proyek berhasil dihapus secara permanen.';
        }
        redirect(route_url($return_page));
        break;

    case 'admin_toggle_status':
        $project_id  = (int)($_POST['project_id'] ?? 0);
        $return_page = $_POST['return_page'] ?? 'admin_projects';
        if ($project_id > 0) {
            toggle_user_project_status($pdo, $project_id);
            $_SESSION['flash_success'] = 'Status proyek berhasil diperbarui.';
        }
        redirect(route_url($return_page));
        break;

    case 'admin_toggle_template':
        $project_id  = (int)($_POST['project_id'] ?? 0);
        $return_page = $_POST['return_page'] ?? 'admin_projects';
        if ($project_id > 0) {
            toggle_user_project_template($pdo, $project_id);
            $_SESSION['flash_success'] = 'Status template berhasil diperbarui.';
        }
        redirect(route_url($return_page));
        break;

    case 'admin_remove_template':
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            remove_project_from_templates($pdo, $project_id);
            $_SESSION['flash_success'] = 'Proyek dihapus dari daftar template.';
        }
        redirect(route_url('admin_templates'));
        break;

    case 'admin_save_settings':
        $new_settings = [
            'site_name'           => trim($_POST['site_name'] ?? 'Greymist'),
            'site_description'    => trim($_POST['site_description'] ?? ''),
            'allow_registrations' => isset($_POST['allow_registrations']),
            'maintenance_mode'    => isset($_POST['maintenance_mode']),
            'max_projects_user'   => max(1, (int)($_POST['max_projects_user'] ?? 10)),
        ];
        file_put_contents(ROOT_PATH . '/config/settings.json', json_encode($new_settings, JSON_PRETTY_PRINT));
        $_SESSION['flash_success'] = 'Pengaturan sistem berhasil disimpan.';
        redirect(route_url('admin_settings'));
        break;

    case 'admin_clear_cache':
        $_SESSION['flash_success'] = 'Cache sesi berhasil dibersihkan.';
        redirect(route_url('admin_settings'));
        break;

    default:
        redirect(route_url('admin_dashboard'));
}

