<?php
header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../includes/db.php";

$user_id = $_SESSION["user_id"] ?? ($_GET["user_id"] ?? null);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "ERROR", "msg" => "Unauthorized: User not logged in", "components" => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT component_id, user_id, component_name, html_content, category, created_at
        FROM gm_components
        WHERE user_id = ?
        ORDER BY component_id DESC
    ");
    $stmt->execute([$user_id]);
    $components = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "OK",
        "components" => $components
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "ERROR", "msg" => "Database error: " . $e->getMessage(), "components" => []]);
}
