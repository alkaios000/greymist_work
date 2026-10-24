<?php
session_start();
require "../includes/db.php";

$user_id = $_SESSION['user_id'];

$rootPath = dirname(__DIR__); // naik dari /api ke root project
$baseDir  = $rootPath . "/assets/img/$user_id/"; // server absolute
$dbPath   = "assets/img/$user_id/";              // untuk database (WAJIB relative)

if (!file_exists($baseDir)) {
    mkdir($baseDir, 0777, true);
}

foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {

    $originalName = basename($_FILES['images']['name'][$key]);
    $filename = time() . "_" . $originalName;

    move_uploaded_file($tmp_name, $baseDir . $filename);

    // WAJIB pakai $dbPath, BUKAN $baseDir
    $stmt = $pdo->prepare("INSERT INTO gm_bg_images (user_id, image, image_name, path) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $filename, $originalName, $dbPath]);
}

echo json_encode(["success"=>true]);