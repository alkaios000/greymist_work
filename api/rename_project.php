<?php
header("Content-Type: application/json");
require "../includes/db.php";

if (
  !isset($_POST["project_id"]) ||
  !is_numeric($_POST["project_id"]) ||
  !isset($_POST["project_name"])
) {
  echo json_encode(["status" => "ERROR"]);
  exit;
}

$projectId = (int)$_POST["project_id"];
$name = trim($_POST["project_name"]);

if ($name === "") {
  echo json_encode(["status" => "EMPTY"]);
  exit;
}

$stmt = $pdo->prepare("
  UPDATE GM_PROJECTS
  SET project_name = ?
  WHERE project_id = ?
");
$stmt->execute([$name, $projectId]);

echo json_encode([
  "status" => "OK",
  "project_name" => $name
]);
