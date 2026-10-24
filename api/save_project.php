<?php
require '../includes/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['frames']) || !is_array($data['frames'])) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'msg' => 'invalid payload']);
  exit;
}

$pdo->beginTransaction();

try {

  $stmt = $pdo->prepare("
    UPDATE GM_FRAMES
    SET frame_name = ?, core_content = ?, sort_order = ?
    WHERE frame_id = ?
  ");

  foreach ($data['frames'] as $index => $f) {
    $stmt->execute([
      $f['frame_name'],
      $f['core_content'],
      $index + 1,       // sort_order = posisi dalam array DOM (1-based)
      $f['frame_id']
    ]);
  }

  $pdo->commit();
  echo json_encode(['status' => 'ok']);

} catch (Exception $e) {

  $pdo->rollBack();
  http_response_code(500);
  echo json_encode([
    'status' => 'error',
    'msg' => $e->getMessage()
  ]);
}

