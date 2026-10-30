<?php
/**
 * File: app/controllers/profile_action.php
 * Fungsi: Menangani pembaruan profil pengguna, penggantian password, dan upload/hapus foto profil.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/user_model.php';

require_auth();
verify_csrf_middleware();

$user_id        = current_user_id();
$profile_action = $_GET['action'] ?? $_POST['action'] ?? '';
$current_user   = get_user_by_id($pdo, $user_id);

if (!$current_user) {
    redirect(route_url('logout'));
}

switch ($profile_action) {
    case 'update_profile':
        $nickname = trim($_POST['nickname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if ($username === '' || $email === '') {
            $_SESSION['flash_error'] = 'Username dan alamat email wajib diisi.';
            redirect(route_url('profile'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'Format alamat email tidak valid.';
            redirect(route_url('profile'));
        }

        if (is_username_or_email_taken($pdo, $username, $email, $user_id)) {
            $_SESSION['flash_error'] = 'Username atau alamat email sudah digunakan oleh akun lain.';
            redirect(route_url('profile'));
        }

        update_user_profile($pdo, $user_id, $nickname, $username, $email, $phone);
        $_SESSION['username']      = $username;
        $_SESSION['nickname']      = $nickname;
        $_SESSION['email']         = $email;
        $_SESSION['flash_success'] = 'Profil berhasil diperbarui.';
        redirect(route_url('profile'));
        break;

    case 'update_password':
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $_SESSION['flash_error'] = 'Seluruh kolom password wajib diisi.';
            redirect(route_url('profile'));
        }

        if (!password_verify($current_password, $current_user['password'])) {
            $_SESSION['flash_error'] = 'Password saat ini tidak sesuai.';
            redirect(route_url('profile'));
        }

        if ($new_password !== $confirm_password) {
            $_SESSION['flash_error'] = 'Konfirmasi password baru tidak cocok.';
            redirect(route_url('profile'));
        }

        if (strlen($new_password) < 6) {
            $_SESSION['flash_error'] = 'Password baru minimal harus terdiri dari 6 karakter.';
            redirect(route_url('profile'));
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        update_user_password($pdo, $user_id, $hashed);
        $_SESSION['flash_success'] = 'Password berhasil diperbarui.';
        redirect(route_url('profile'));
        break;

    case 'upload_profile_picture':
        if (empty($_FILES['profile_picture'])) {
            $_SESSION['flash_error'] = 'Silakan pilih file foto profil terlebih dahulu.';
            redirect(route_url('profile'));
        }

        $upload = validate_and_upload_file(
            $_FILES['profile_picture'],
            'foto_profil',
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            5,
            'profile_' . $user_id . '_'
        );

        if (!$upload['success']) {
            $_SESSION['flash_error'] = $upload['error'];
            redirect(route_url('profile'));
        }

        // Hapus file foto profil lama jika ada
        if (!empty($current_user['profile'])) {
            $old_clean = preg_replace('#^(\.\./)+#', '', $current_user['profile']);
            $old_full  = ROOT_PATH . '/' . ltrim($old_clean, '/');
            if (is_file($old_full)) {
                @unlink($old_full);
            }
        }

        update_user_avatar($pdo, $user_id, $upload['path']);
        $_SESSION['flash_success'] = 'Foto profil berhasil diperbarui.';
        redirect(route_url('profile'));
        break;

    case 'delete_profile_picture':
        if (!empty($current_user['profile'])) {
            $old_clean = preg_replace('#^(\.\./)+#', '', $current_user['profile']);
            $old_full  = ROOT_PATH . '/' . ltrim($old_clean, '/');
            if (is_file($old_full)) {
                @unlink($old_full);
            }
            update_user_avatar($pdo, $user_id, null);
            $_SESSION['flash_success'] = 'Foto profil berhasil dihapus.';
        }
        redirect(route_url('profile'));
        break;

    default:
        redirect(route_url('profile'));
}

