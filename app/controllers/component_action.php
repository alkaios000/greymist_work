<?php
/**
 * File: app/controllers/component_action.php
 * Fungsi: Menangani endpoint API JSON untuk komponen kustom pengguna (get, save, delete).
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/component_model.php';

$user_id = current_user_id() ?? (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);
if (!$user_id) {
    json_response(['status' => 'ERROR', 'msg' => 'Unauthorized: User not logged in', 'components' => []], 401);
}

$component_action = $_GET['action'] ?? $_POST['action'] ?? '';
$raw_input        = file_get_contents('php://input');
$data             = json_decode((string)$raw_input, true);
if (!is_array($data)) {
    $data = $_POST;
}

switch ($component_action) {
    case 'get_components':
        $components = get_user_components($pdo, (int)$user_id);
        json_response([
            'status'     => 'OK',
            'success'    => true,
            'components' => $components,
            'data'       => $components,
        ]);
        break;

    case 'save_component':
        $name     = trim((string)($data['component_name'] ?? ''));
        $html     = trim((string)($data['html_content'] ?? ''));
        $category = trim((string)($data['category'] ?? 'Custom'));

        if ($name === '' || $html === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Nama komponen dan konten HTML wajib diisi']);
        }
        $new_id = create_user_component($pdo, (int)$user_id, $name, $html, $category !== '' ? $category : 'Custom');
        $comp_payload = [
            'component_id'   => $new_id,
            'user_id'        => (int)$user_id,
            'component_name' => $name,
            'html_content'   => $html,
            'category'       => $category !== '' ? $category : 'Custom',
            'created_at'     => date('Y-m-d H:i:s'),
        ];
        json_response([
            'status'    => 'OK',
            'success'   => true,
            'msg'       => 'Komponen berhasil disimpan',
            'component' => $comp_payload,
            'data'      => $comp_payload,
        ]);
        break;

    case 'delete_component':
        $component_id = (int)($data['component_id'] ?? 0);
        if ($component_id <= 0) {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Invalid component ID']);
        }
        delete_user_component($pdo, $component_id, (int)$user_id);
        json_response([
            'status'  => 'OK',
            'success' => true,
            'msg'     => 'Komponen berhasil dihapus',
        ]);
        break;

    default:
        json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Aksi komponen tidak dikenali.'], 400);
}
