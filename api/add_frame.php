<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require "../includes/db.php";

if (!isset($_POST["project_id"])) {
  echo json_encode(["status" => "ERROR", "msg" => "project_id missing"]);
  exit;
}

$projectId = (int) $_POST["project_id"];

// hitung nomor frame berikutnya
$stmt = $pdo->prepare("
  SELECT COUNT(*) FROM GM_FRAMES WHERE project_id = ?
");
$stmt->execute([$projectId]);
$index = (int)$stmt->fetchColumn() + 1;

$frameName = "Frame $index";

// insert frame
$stmt = $pdo->prepare("
  INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
  VALUES (?, ?, '')
");
$stmt->execute([$projectId, $frameName]);

$frameId = $pdo->lastInsertId();

// response lengkap
echo json_encode([
  "status" => "OK",
  "frame" => [
    "frame_id" => $frameId,
    "frame_name" => $frameName,
    "core_content" => ""
  ]
]);
