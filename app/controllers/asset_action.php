<?php
/**
 * File: app/controllers/asset_action.php
 * Fungsi: Menangani upload, pengambilan daftar, pengubahan nama, dan penghapusan gambar aset/background.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/asset_model.php';

require_auth(true);
$user_id      = current_user_id();
$asset_action = $_GET['action'] ?? $_POST['action'] ?? '';
$json_input   = json_decode((string)file_get_contents('php://input'), true) ?: [];

switch ($asset_action) {
    case 'get_images':
        $images = get_user_bg_images($pdo, $user_id);
        // Pastikan format path kompatibel dengan img.path + img.image di frontend Workspace
        foreach ($images as &$img) {
            $dir_path = rtrim(str_replace('\\', '/', $img['path']), '/');
            // Jika kolom path menyimpan full path ke file, pisahkan direktori dan nama file
            if (str_ends_with($dir_path, '/' . $img['image'])) {
                $dir_path = substr($dir_path, 0, -strlen($img['image']));
            } else {
                $dir_path .= '/';
            }
            $img['path'] = $dir_path;
        }
        unset($img);
        json_response($images);
        break;

    case 'upload_assets_image':
        // Mendukung bulk upload $_FILES['images'] maupun single $_FILES['image']
        $uploaded_files = [];

        if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $count = count($_FILES['images']['name']);
            for ($i = 0; $i < $count; $i++) {
                $single_file = [
                    'name'     => $_FILES['images']['name'][$i],
                    'type'     => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error'    => $_FILES['images']['error'][$i],
                    'size'     => $_FILES['images']['size'][$i],
                ];
                $res = validate_and_upload_file($single_file, 'assets', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5, 'asset_');
                if ($res['success']) {
                    create_user_bg_image($pdo, $user_id, $res['filename'], $res['original_name'], 'uploads/assets/');
                    $uploaded_files[] = $res['filename'];
                }
            }
            json_response(['success' => !empty($uploaded_files), 'files' => $uploaded_files]);
        } elseif (!empty($_FILES['image'])) {
            $upload = validate_and_upload_file($_FILES['image'], 'assets', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5, 'asset_');
            if (!$upload['success']) {
                json_response(['success' => false, 'message' => $upload['error']]);
            }
            $image_id = create_user_bg_image($pdo, $user_id, $upload['filename'], $upload['original_name'], 'uploads/assets/');
            json_response([
                'success' => true,
                'files'   => [$upload['filename']],
                'data'    => [
                    'id'   => $image_id,
                    'name' => $upload['original_name'],
                    'path' => $upload['path'],
                ],
            ]);
        }

        json_response(['success' => false, 'message' => 'Tidak ada file gambar yang diunggah.']);
        break;

    case 'upload_background_image':
        if (empty($_FILES['image'])) {
            json_response(['success' => false, 'message' => 'Tidak ada file background yang diunggah.']);
        }
        $upload = validate_and_upload_file($_FILES['image'], 'background', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5, 'bg_');
        if (!$upload['success']) {
            json_response(['success' => false, 'message' => $upload['error']]);
        }
        $image_id = create_user_bg_image($pdo, $user_id, $upload['filename'], $upload['original_name'], 'uploads/background/');
        json_response([
            'success' => true,
            'path'    => $upload['path'],
            'data'    => [
                'id'   => $image_id,
                'name' => $upload['original_name'],
                'path' => $upload['path'],
            ],
        ]);
        break;

    case 'update_image_name':
        $image_id = (int)($json_input['image_id'] ?? $json_input['id'] ?? $_POST['image_id'] ?? $_POST['id'] ?? 0);
        $new_name = trim((string)($json_input['new_name'] ?? $json_input['name'] ?? $_POST['new_name'] ?? $_POST['name'] ?? ''));

        if ($image_id <= 0 || $new_name === '') {
            json_response(['success' => false, 'message' => 'ID gambar dan nama baru wajib diisi.']);
        }
        $img = get_user_bg_image_by_id($pdo, $image_id, $user_id);
        if (!$img) {
            json_response(['success' => false, 'message' => 'Gambar tidak ditemukan atau akses ditolak.']);
        }
        update_user_bg_image_name($pdo, $image_id, $user_id, $new_name);
        json_response([
            'success'  => true,
            'message'  => 'Nama gambar berhasil diperbarui.',
            'new_name' => $new_name,
        ]);
        break;

    case 'delete_image':
        $image_id = (int)($json_input['image_id'] ?? $json_input['id'] ?? $_POST['image_id'] ?? $_POST['id'] ?? 0);

        if ($image_id <= 0) {
            json_response(['success' => false, 'message' => 'ID gambar tidak valid.']);
        }
        $img = get_user_bg_image_by_id($pdo, $image_id, $user_id);
        if (!$img) {
            json_response(['success' => false, 'message' => 'Gambar tidak ditemukan atau akses ditolak.']);
        }

        $dir_clean     = rtrim(str_replace('\\', '/', $img['path']), '/');
        $physical_path = str_ends_with($dir_clean, $img['image'])
            ? ROOT_PATH . '/' . ltrim($dir_clean, '/')
            : ROOT_PATH . '/' . ltrim($dir_clean, '/') . '/' . $img['image'];

        if (is_file($physical_path)) {
            @unlink($physical_path);
        }
        delete_user_bg_image($pdo, $image_id, $user_id);
        json_response(['success' => true, 'message' => 'Gambar berhasil dihapus.']);
        break;

    default:
        json_response(['success' => false, 'message' => 'Aksi aset tidak dikenali.'], 400);
}
