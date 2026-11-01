<?php
/**
 * File: app/controllers/frame_action.php
 * Fungsi: Menangani seluruh endpoint API JSON terkait manipulasi frame pada Workspace Editor.
 *         Kompatibel penuh dengan ekspektasi payload JS Workspace Editor.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/project_model.php';
require_once ROOT_PATH . '/models/frame_model.php';

$frame_action = $_GET['action'] ?? $_POST['action'] ?? '';
$json_input   = json_decode((string)file_get_contents('php://input'), true) ?: [];

switch ($frame_action) {
    case 'get_frames':
        $project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        if ($project_id <= 0) {
            json_response([]);
        }
        $stmt = $pdo->prepare("
            SELECT frame_id, frame_name, core_content, sort_order, custom_breakpoints, custom_js
            FROM gm_frames
            WHERE project_id = ?
            ORDER BY sort_order ASC, frame_id ASC
        ");
        $stmt->execute([$project_id]);
        json_response($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'add_frame':
        $project_id = (int)($json_input['project_id'] ?? $_POST['project_id'] ?? 0);
        if ($project_id <= 0) {
            json_response(['status' => 'ERROR', 'msg' => 'project_id missing']);
        }

        $stmt_cnt = $pdo->prepare("SELECT COUNT(*) FROM gm_frames WHERE project_id = ?");
        $stmt_cnt->execute([$project_id]);
        $index = (int)$stmt_cnt->fetchColumn() + 1;
        $custom_name = trim((string)($json_input['frame_name'] ?? $_POST['frame_name'] ?? ''));
        $frame_name  = $custom_name !== '' ? $custom_name : "Frame {$index}";

        $stmt = $pdo->prepare("
            INSERT INTO gm_frames (project_id, frame_name, core_content)
            VALUES (?, ?, '')
        ");
        $stmt->execute([$project_id, $frame_name]);
        $frame_id = $pdo->lastInsertId();

        json_response([
            'status' => 'OK',
            'frame'  => [
                'frame_id'     => $frame_id,
                'frame_name'   => $frame_name,
                'core_content' => '',
            ],
        ]);
        break;

    case 'copy_frame':
        $frame_id = (int)($json_input['frame_id'] ?? $_POST['frame_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT project_id, frame_name, core_content FROM gm_frames WHERE frame_id = ?");
        $stmt->execute([$frame_id]);
        $frame = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$frame) {
            json_response(['error' => 'Frame not found']);
        }

        $new_name = $frame['frame_name'] . ' - copy';
        $ins = $pdo->prepare("INSERT INTO gm_frames (project_id, frame_name, core_content) VALUES (?, ?, ?)");
        $ins->execute([$frame['project_id'], $new_name, $frame['core_content']]);

        json_response([
            'status' => 'ok',
            'frame'  => [
                'frame_id'     => $pdo->lastInsertId(),
                'frame_name'   => $new_name,
                'core_content' => $frame['core_content'],
            ],
        ]);
        break;

    case 'rename_frame':
        $frame_id   = (int)($json_input['frame_id'] ?? $_POST['frame_id'] ?? 0);
        $frame_name = trim((string)($json_input['frame_name'] ?? $_POST['frame_name'] ?? ''));

        if ($frame_id <= 0 || $frame_name === '') {
            json_response(['status' => 'ERROR', 'message' => 'Invalid data']);
        }
        $stmt = $pdo->prepare("UPDATE gm_frames SET frame_name = ? WHERE frame_id = ?");
        $stmt->execute([$frame_name, $frame_id]);

        json_response([
            'status'     => 'OK',
            'frame_id'   => $frame_id,
            'frame_name' => $frame_name,
            'affected'   => $stmt->rowCount(),
        ]);
        break;

    case 'delete_frame':
        $frame_id = (int)($json_input['frame_id'] ?? $_POST['frame_id'] ?? 0);
        if ($frame_id <= 0) {
            json_response(['status' => 'ERROR', 'msg' => 'INVALID FRAME ID']);
        }
        $stmt = $pdo->prepare("DELETE FROM gm_frames WHERE frame_id = ?");
        $stmt->execute([$frame_id]);

        json_response([
            'status'   => 'DELETED',
            'frame_id' => $frame_id,
            'affected' => $stmt->rowCount(),
        ]);
        break;

    case 'save_frame':
        require_auth(true);
        $frame_id = (int)($json_input['frame_id'] ?? $_POST['frame_id'] ?? 0);
        $content  = (string)($json_input['content'] ?? $json_input['core_content'] ?? $_POST['content'] ?? '');

        if ($frame_id <= 0) {
            json_response(['error' => 'Invalid payload'], 400);
        }
        $stmt = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE frame_id = ?");
        $stmt->execute([$content, $frame_id]);

        json_response([
            'success'  => true,
            'frame_id' => $frame_id,
        ]);
        break;

    case 'save_cores':
        $frame_id = $_POST['frame_id'] ?? $json_input['frame_id'] ?? null;
        $core     = $_POST['core_content'] ?? $json_input['core_content'] ?? '';

        if (!$frame_id) {
            json_response(['status' => 'error', 'msg' => 'frame_id missing'], 400);
        }
        $stmt = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE frame_id = ?");
        $stmt->execute([$core, $frame_id]);
        json_response(['status' => 'ok']);
        break;

    case 'get_frame_script':
        require_auth(true);
        $user_id    = current_user_id();
        $frame_id   = (int)($_GET['frame_id'] ?? $json_input['frame_id'] ?? 0);
        $project_id = (int)($_GET['project_id'] ?? $json_input['project_id'] ?? 0);

        $frame_bp  = null;
        $frame_js  = '';
        if ($frame_id > 0) {
            $frame = get_frame_with_owner($pdo, $frame_id, $user_id);
            if ($frame) {
                $frame_bp = $frame['custom_breakpoints'] ?? null;
                $frame_js = $frame['custom_js'] ?? '';
                if ($project_id <= 0) {
                    $project_id = (int)$frame['project_id'];
                }
            }
        }

        $project_bp = null;
        $project_js = '';
        if ($project_id > 0) {
            $stmt_p = $pdo->prepare("SELECT custom_breakpoints, custom_js FROM gm_projects WHERE project_id = ? AND user_id = ? LIMIT 1");
            $stmt_p->execute([$project_id, $user_id]);
            $p_data = $stmt_p->fetch(PDO::FETCH_ASSOC);
            if ($p_data) {
                $project_bp = $p_data['custom_breakpoints'] ?? null;
                $project_js = $p_data['custom_js'] ?? '';
            }
        }

        json_response([
            'status'             => 'OK',
            'success'            => true,
            'frame_id'           => $frame_id,
            'project_id'         => $project_id,
            'frame_breakpoints'  => $frame_bp,
            'frame_js'           => $frame_js,
            'project_breakpoints'=> $project_bp,
            'project_js'         => $project_js,
        ]);
        break;

    case 'save_frame_script':
        require_auth(true);
        $user_id      = current_user_id();
        $frame_id     = (int)($json_input['frame_id'] ?? $_POST['frame_id'] ?? 0);
        $project_id   = (int)($json_input['project_id'] ?? $_POST['project_id'] ?? 0);
        $scope        = (string)($json_input['scope'] ?? $_POST['scope'] ?? 'frame'); // 'frame' atau 'project'
        $apply_to_all = !empty($json_input['apply_to_all']) || !empty($_POST['apply_to_all']);

        $custom_bp = isset($json_input['custom_breakpoints']) ? (is_array($json_input['custom_breakpoints']) ? json_encode($json_input['custom_breakpoints']) : (string)$json_input['custom_breakpoints']) : (isset($_POST['custom_breakpoints']) ? (string)$_POST['custom_breakpoints'] : null);
        $custom_js = isset($json_input['custom_js']) ? (string)$json_input['custom_js'] : (isset($_POST['custom_js']) ? (string)$_POST['custom_js'] : null);

        if ($scope === 'project' && $project_id > 0) {
            save_project_custom_script($pdo, $project_id, $user_id, $custom_bp, $custom_js);
            if ($apply_to_all) {
                apply_script_to_all_project_frames($pdo, $project_id, $user_id, $custom_bp, null);
            }
            json_response([
                'status'     => 'OK',
                'success'    => true,
                'scope'      => 'project',
                'project_id' => $project_id,
            ]);
        }

        if ($frame_id > 0) {
            $saved = save_frame_custom_script($pdo, $frame_id, $user_id, $custom_bp, $custom_js);
            if (!$saved) {
                json_response(['status' => 'ERROR', 'message' => 'Frame tidak ditemukan atau akses ditolak.'], 403);
            }

            if ($apply_to_all && $project_id > 0) {
                apply_script_to_all_project_frames($pdo, $project_id, $user_id, $custom_bp, null);
            }

            json_response([
                'status'       => 'OK',
                'success'      => true,
                'frame_id'     => $frame_id,
                'apply_to_all' => $apply_to_all,
            ]);
        }

        json_response(['status' => 'ERROR', 'message' => 'frame_id atau project_id tidak valid.'], 400);
        break;

    default:
        json_response(['status' => 'ERROR', 'message' => 'Aksi frame tidak dikenali.'], 400);
}
