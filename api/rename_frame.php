<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
require "../includes/db.php";

/* =========================
   VALIDASI INPUT
========================= */
if (
  !isset($_POST["frame_id"]) ||
  !isset($_POST["frame_name"])
) {
  echo json_encode([
    "status" => "ERROR",
    "message" => "POST data missing",
    "post" => $_POST
  ]);
  exit;
}

$frameId = (int) $_POST["frame_id"];
$name    = trim($_POST["frame_name"]);

if ($frameId <= 0 || $name === "") {
  echo json_encode([
    "status" => "ERROR",
    "message" => "Invalid data",
    "frame_id" => $frameId,
    "frame_name" => $name
  ]);
  exit;
}

/* =========================
   UPDATE DB
========================= */
$stmt = $pdo->prepare("
  UPDATE GM_FRAMES
  SET frame_name = ?
  WHERE frame_id = ?
");

$stmt->execute([$name, $frameId]);

echo json_encode([
  "status" => "OK",
  "frame_id" => $frameId,
  "frame_name" => $name,
  "affected" => $stmt->rowCount()
]);
