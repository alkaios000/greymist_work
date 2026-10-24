<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require "../includes/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["frame_id"]) || !is_numeric($data["frame_id"])) {
  echo json_encode(["status" => "ERROR", "msg" => "INVALID FRAME ID"]);
  exit;
}

$frameId = (int)$data["frame_id"];

$stmt = $pdo->prepare("DELETE FROM GM_FRAMES WHERE frame_id = ?");
$stmt->execute([$frameId]);

echo json_encode([
  "status" => "DELETED",
  "frame_id" => $frameId,
  "affected" => $stmt->rowCount()
]);
exit;
