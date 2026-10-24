<?php
session_start();
require "../includes/db.php";

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'];
$name = $data['name'];

if (isset($_SESSION['user_id']) && $id && $name) {
    $stmt = $pdo->prepare("UPDATE gm_bg_images SET image_name = ? WHERE image_id = ? AND user_id = ?");
    $stmt->execute([$name, $id, $_SESSION['user_id']]);
    echo json_encode(["success"=>true]);
} else {
    echo json_encode(["success"=>false, "message"=>"Invalid data"]);
}
