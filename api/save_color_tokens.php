<?php
header("Content-Type: application/json");
require "../includes/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["project_id"]) || !is_numeric($data["project_id"])) {
  echo json_encode(["status" => "ERROR", "msg" => "INVALID PROJECT ID"]);
  exit;
}

if (!isset($data["tokens"]) || !is_array($data["tokens"])) {
    echo json_encode(["status" => "ERROR", "msg" => "INVALID TOKENS DATA"]);
    exit;
}

$projectId = (int)$data["project_id"];
$tokensJson = json_encode($data["tokens"]);

$stmt = $pdo->prepare("
  UPDATE GM_PROJECTS
  SET color_tokens = ?
  WHERE project_id = ?
");
$result = $stmt->execute([$tokensJson, $projectId]);

// Sinkronkan ke tabel GM_COLORS agar konsisten di seluruh aplikasi
try {
    $del = $pdo->prepare("DELETE FROM GM_COLORS WHERE project_id = ?");
    $del->execute([$projectId]);

    $ins = $pdo->prepare("INSERT INTO GM_COLORS (project_id, color_variable) VALUES (?, ?)");
    foreach ($data["tokens"] as $name => $val) {
        $varName = str_starts_with($name, "--") ? $name : "--$name";
        $ins->execute([$projectId, "$varName: $val"]);
    }
} catch (Exception $e) {
    // Non-fatal logging
}

if ($result) {
    echo json_encode(["status" => "OK"]);
} else {
    echo json_encode(["status" => "ERROR", "msg" => "FAILED TO UPDATE DATABASE"]);
}
