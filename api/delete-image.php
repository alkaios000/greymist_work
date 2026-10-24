<?php
session_start();
require "../includes/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];

$stmt = $pdo->prepare("SELECT * FROM gm_bg_images WHERE image_id = ?");
$stmt->execute([$id]);
$image = $stmt->fetch();

if ($image) {
    $rootPath = dirname(__DIR__);
    $filePath = $rootPath . "/" . ltrim($image['path'], '/') . $image['image'];
    
    if(file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $pdo->prepare("DELETE FROM gm_bg_images WHERE image_id = ?");
    $stmt->execute([$id]);
}

echo json_encode(["success"=>true]);