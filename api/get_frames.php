<?php
require '../includes/db.php';

$project_id = $_GET['project_id'];

$stmt = $pdo->prepare("
  SELECT frame_id, frame_name, core_content, sort_order
  FROM GM_FRAMES
  WHERE project_id = ?
  ORDER BY sort_order ASC, frame_id ASC
");
$stmt->execute([$project_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

