<?php
header("Content-Type: application/json");
require "../includes/db.php";

if (!isset($_GET["project_id"]) || !is_numeric($_GET["project_id"])) {
  echo json_encode(["status" => "ERROR", "msg" => "INVALID PROJECT ID"]);
  exit;
}

$projectId = (int)$_GET["project_id"];

$stmt = $pdo->prepare("
  SELECT project_name
  FROM GM_PROJECTS
  WHERE project_id = ?
  LIMIT 1
");
$stmt->execute([$projectId]);

$project = $stmt->fetch();

if (!$project) {
  echo json_encode(["status" => "NOT_FOUND"]);
  exit;
}

echo json_encode([
  "status" => "OK",
  "project_name" => $project["project_name"]
]);
