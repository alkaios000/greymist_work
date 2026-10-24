<?php
require '../includes/db.php';

$frameId = (int)$_POST['frame_id'];

// ambil frame lama
$stmt = $pdo->prepare("
  SELECT project_id, frame_name, core_content
  FROM GM_FRAMES
  WHERE frame_id = ?
");
$stmt->execute([$frameId]);
$frame = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$frame) {
  echo json_encode(['error' => 'Frame not found']);
  exit;
}

// insert frame baru
$stmt = $pdo->prepare("
  INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
  VALUES (?, ?, ?)
");

$newName = $frame['frame_name'] . ' - copy';

$stmt->execute([
  $frame['project_id'],
  $newName,
  $frame['core_content']
]);

echo json_encode([
  'status' => 'ok',
  'frame' => [
    'frame_id' => $pdo->lastInsertId(),
    'frame_name' => $newName,
    'core_content' => $frame['core_content']
  ]
]);
