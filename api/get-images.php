<?php
session_start();
require "../includes/db.php";

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM gm_bg_images WHERE user_id = ? ORDER BY image_id DESC");
$stmt->execute([$user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));