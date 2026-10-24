<?php
require '../includes/db.php';

$frame_id = $_POST['frame_id'] ?? null;
$core     = $_POST['core_content'] ?? null;

if (!$frame_id) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'msg' => 'frame_id missing']);
  exit;
}

$stmt = $pdo->prepare("
  UPDATE GM_FRAMES
  SET core_content = ?
  WHERE frame_id = ?
");
$stmt->execute([$core, $frame_id]);

echo json_encode(['status' => 'ok']);
