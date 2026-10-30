<?php
/**
 * File: app/controllers/project_action.php
 * Fungsi: Menangani seluruh aksi CRUD proyek, publikasi, template, duplikasi,
 *         tempat sampah (trash), serta endpoint API JSON proyek & pengaturan warna.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/project_model.php';
require_once ROOT_PATH . '/models/frame_model.php';

$project_action = $_GET['action'] ?? $_POST['action'] ?? '';
$return_page    = $_POST['return_page'] ?? $_GET['return_page'] ?? 'projects';

switch ($project_action) {
    case 'new_project':
        require_auth();
        verify_csrf_middleware();
        $user_id      = current_user_id();
        $project_name = trim($_POST['project_name'] ?? 'Untitled Project');
        $new_id       = create_project_with_default_frame($pdo, $user_id, $project_name);
        redirect(route_url('workspace', ['project_id' => $new_id]));
        break;

    case 'rename_project':
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        $is_api_call  = str_contains($content_type, 'application/json')
            || isset($_GET['json'])
            || !isset($_POST['csrf_token']);

        require_auth($is_api_call);
        $user_id = current_user_id();
        $input   = json_decode((string)file_get_contents('php://input'), true);

        $project_id = (int)($input['project_id'] ?? $_POST['project_id'] ?? 0);
        $new_name   = trim((string)($input['project_name'] ?? $_POST['project_name'] ?? ''));

        if ($is_api_call) {
            if ($project_id <= 0) {
                json_response(['status' => 'ERROR', 'success' => false]);
            }
            if ($new_name === '') {
                json_response(['status' => 'EMPTY', 'success' => false]);
            }
            $stmt = $pdo->prepare("UPDATE gm_projects SET project_name = ? WHERE project_id = ?");
            $stmt->execute([$new_name, $project_id]);
            json_response([
                'status'       => 'OK',
                'success'      => true,
                'project_name' => $new_name,
            ]);
        } else {
            verify_csrf_middleware();
            if ($project_id > 0 && $new_name !== '') {
                rename_user_project($pdo, $project_id, $user_id, $new_name);
                $_SESSION['flash_success'] = 'Nama proyek berhasil diubah.';
            }
            redirect(route_url($return_page));
        }
        break;

    case 'toggle_status':
        require_auth();
        verify_csrf_middleware();
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            toggle_user_project_status($pdo, $project_id, current_user_id());
            $_SESSION['flash_success'] = 'Status publikasi proyek berhasil diperbarui.';
        }
        redirect(route_url($return_page));
        break;

    case 'toggle_template':
        require_auth();
        verify_csrf_middleware();
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            toggle_user_project_template($pdo, $project_id, current_user_id());
            $_SESSION['flash_success'] = 'Status template proyek berhasil diperbarui.';
        }
        redirect(route_url($return_page));
        break;

    case 'clone_project':
        require_auth();
        verify_csrf_middleware();
        $source_id = (int)($_POST['project_id'] ?? 0);
        if ($source_id > 0) {
            $new_id = clone_existing_project($pdo, $source_id, current_user_id(), 'published_or_template', ' (Copy)');
            if ($new_id) {
                $_SESSION['flash_success'] = 'Proyek berhasil diduplikasi ke koleksi Anda.';
                redirect(route_url('workspace', ['project_id' => $new_id]));
            }
        }
        $_SESSION['flash_error'] = 'Gagal menduplikasi proyek.';
        redirect(route_url('explore'));
        break;

    case 'clone_template':
        require_auth();
        verify_csrf_middleware();
        $source_id = (int)($_POST['project_id'] ?? 0);
        if ($source_id > 0) {
            $new_id = clone_existing_project($pdo, $source_id, current_user_id(), 'template', ' (From Template)');
            if ($new_id) {
                $_SESSION['flash_success'] = 'Proyek baru berhasil dibuat dari template.';
                redirect(route_url('workspace', ['project_id' => $new_id]));
            }
        }
        $_SESSION['flash_error'] = 'Gagal menggunakan template.';
        redirect(route_url('templates'));
        break;

    case 'delete_project':
        require_auth();
        verify_csrf_middleware();
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            soft_delete_user_project($pdo, $project_id, current_user_id());
            $_SESSION['flash_success'] = 'Proyek dipindahkan ke Tempat Sampah.';
        }
        redirect(route_url($return_page));
        break;

    case 'restore_project':
        require_auth();
        verify_csrf_middleware();
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            restore_user_project($pdo, $project_id, current_user_id());
            $_SESSION['flash_success'] = 'Proyek berhasil dipulihkan.';
        }
        redirect(route_url('trash'));
        break;

    case 'permanent_delete':
        require_auth();
        verify_csrf_middleware();
        $project_id = (int)($_POST['project_id'] ?? 0);
        if ($project_id > 0) {
            permanent_delete_project($pdo, $project_id, current_user_id());
            $_SESSION['flash_success'] = 'Proyek telah dihapus secara permanen.';
        }
        redirect(route_url('trash'));
        break;

    case 'get_project':
        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (int)($_GET['id'] ?? 0);
        if ($project_id <= 0) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID PROJECT ID']);
        }
        $stmt = $pdo->prepare("SELECT * FROM gm_projects WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        if (!$project) {
            json_response(['status' => 'NOT_FOUND']);
        }
        $frames = get_project_frames($pdo, $project_id);
        json_response([
            'status'       => 'OK',
            'success'      => true,
            'project_name' => $project['project_name'],
            'project'      => $project,
            'frames'       => $frames,
        ]);
        break;

    case 'save_project':
        $data = json_decode((string)file_get_contents('php://input'), true);
        if (!isset($data['frames']) || !is_array($data['frames'])) {
            json_response(['status' => 'error', 'msg' => 'invalid payload'], 400);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE gm_frames
                SET frame_name = ?, core_content = ?, sort_order = ?
                WHERE frame_id = ?
            ");
            foreach ($data['frames'] as $index => $f) {
                $stmt->execute([
                    $f['frame_name'] ?? '',
                    $f['core_content'] ?? '',
                    $index + 1,
                    $f['frame_id'],
                ]);
            }
            $pdo->commit();
            json_response(['status' => 'ok', 'success' => true]);
        } catch (Throwable $e) {
            $pdo->rollBack();
            json_response(['status' => 'error', 'msg' => $e->getMessage()], 500);
        }
        break;

    case 'get_color_tokens':
        if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID PROJECT ID']);
        }
        $project_id = (int)$_GET['project_id'];
        $stmt = $pdo->prepare("SELECT color_tokens FROM gm_projects WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        if (!$project) {
            json_response(['status' => 'NOT_FOUND']);
        }

        $tokens_array = [];
        if (!empty($project['color_tokens'])) {
            $decoded = json_decode($project['color_tokens'], true);
            if (is_array($decoded)) {
                $tokens_array = $decoded;
            }
        }
        if (empty($tokens_array)) {
            $stmt_c = $pdo->prepare("SELECT color_variable FROM gm_colors WHERE project_id = ?");
            $stmt_c->execute([$project_id]);
            $colors = $stmt_c->fetchAll(PDO::FETCH_COLUMN);
            foreach ($colors as $c) {
                if (str_contains($c, ':')) {
                    [$k, $v] = explode(':', $c, 2);
                    $tokens_array[trim($k)] = trim($v);
                } else {
                    $tokens_array[trim($c)] = '#000000';
                }
            }
        }
        json_response([
            'status' => 'OK',
            'tokens' => (object)$tokens_array,
        ]);
        break;

    case 'save_color_tokens':
        $data = json_decode((string)file_get_contents('php://input'), true);
        if (!isset($data['project_id']) || !is_numeric($data['project_id']) || !isset($data['tokens']) || !is_array($data['tokens'])) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID DATA']);
        }
        $project_id  = (int)$data['project_id'];
        $tokens_json = json_encode($data['tokens']);

        $stmt = $pdo->prepare("UPDATE gm_projects SET color_tokens = ? WHERE project_id = ?");
        $result = $stmt->execute([$tokens_json, $project_id]);

        try {
            $del = $pdo->prepare("DELETE FROM gm_colors WHERE project_id = ?");
            $del->execute([$project_id]);
            $ins = $pdo->prepare("INSERT INTO gm_colors (project_id, color_variable) VALUES (?, ?)");
            foreach ($data['tokens'] as $name => $val) {
                $var_name = str_starts_with($name, '--') ? $name : "--{$name}";
                $ins->execute([$project_id, "{$var_name}: {$val}"]);
            }
        } catch (Throwable $e) {}

        json_response(['status' => $result ? 'OK' : 'ERROR']);
        break;

    case 'get_bg_color_settings':
        if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID PROJECT ID']);
        }
        $project_id = (int)$_GET['project_id'];
        $stmt = $pdo->prepare("SELECT bg_color_settings, color_tokens FROM gm_projects WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        if (!$project) {
            json_response(['status' => 'NOT_FOUND']);
        }

        $settings = [];
        if (!empty($project['bg_color_settings'])) {
            $decoded = json_decode($project['bg_color_settings'], true);
            if (is_array($decoded)) {
                $settings = $decoded;
            }
        }
        if (empty($settings['tokens'])) {
            $settings['tokens'] = [];
            if (!empty($project['color_tokens'])) {
                $c_tokens = json_decode($project['color_tokens'], true);
                if (is_array($c_tokens)) {
                    $settings['tokens'] = $c_tokens;
                }
            }
        }
        if (!isset($settings['recent']) || !is_array($settings['recent'])) {
            $settings['recent'] = [];
        }
        json_response([
            'status'   => 'OK',
            'settings' => $settings,
        ]);
        break;

    case 'save_bg_color_settings':
        $data = json_decode((string)file_get_contents('php://input'), true);
        if (!isset($data['project_id']) || !is_numeric($data['project_id'])) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID PROJECT ID']);
        }
        $project_id = (int)$data['project_id'];
        $settings   = $data['settings'] ?? [];
        if (!is_array($settings)) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID SETTINGS DATA']);
        }

        $stmt = $pdo->prepare("UPDATE gm_projects SET bg_color_settings = ? WHERE project_id = ?");
        $result = $stmt->execute([json_encode($settings), $project_id]);

        if (!empty($settings['tokens']) && is_array($settings['tokens'])) {
            $stmt_ex = $pdo->prepare("SELECT color_tokens FROM gm_projects WHERE project_id = ?");
            $stmt_ex->execute([$project_id]);
            $existing = $stmt_ex->fetchColumn();
            $existing_tokens = $existing ? json_decode($existing, true) : [];
            if (!is_array($existing_tokens)) {
                $existing_tokens = [];
            }
            $merged_tokens = array_merge($existing_tokens, $settings['tokens']);
            $stmt_up = $pdo->prepare("UPDATE gm_projects SET color_tokens = ? WHERE project_id = ?");
            $stmt_up->execute([json_encode($merged_tokens), $project_id]);

            try {
                $del = $pdo->prepare("DELETE FROM gm_colors WHERE project_id = ?");
                $del->execute([$project_id]);
                $ins = $pdo->prepare("INSERT INTO gm_colors (project_id, color_variable) VALUES (?, ?)");
                foreach ($merged_tokens as $name => $val) {
                    $var_name = str_starts_with($name, '--') ? $name : "--{$name}";
                    $ins->execute([$project_id, "{$var_name}: {$val}"]);
                }
            } catch (Throwable $e) {}
        }

        json_response(['status' => $result ? 'OK' : 'ERROR', 'settings' => $settings]);
        break;

    default:
        redirect(route_url('projects'));
}
