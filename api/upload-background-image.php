<?php
session_start();
require "../includes/db.php";

$user_id = $_SESSION['user_id'];

$rootPath = dirname(__DIR__);
$baseDir  = $rootPath . "/assets/img/$user_id/";
$dbPath   = "assets/img/$user_id/";

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$filename = time() . "_" . basename($_FILES['image']['name']);

move_uploaded_file($_FILES['image']['tmp_name'], $baseDir . $filename);

// WAJIB pakai $dbPath
$stmt = $pdo->prepare("INSERT INTO gm_bg_images (user_id, image, path) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $filename, $dbPath]);

echo json_encode([
    "success"=>true,
    "path"=>$dbPath . $filename
]);