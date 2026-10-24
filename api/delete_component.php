<?php
header("Content-Type: application/json");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../includes/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "ERROR", "msg" => "Method not allowed"]);
    exit;
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$user_id = $_SESSION["user_id"] ?? ($data["user_id"] ?? null);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "ERROR", "msg" => "Unauthorized"]);
    exit;
}

$component_id = (int)($data["component_id"] ?? 0);

if (!$component_id) {
    echo json_encode(["status" => "ERROR", "msg" => "Invalid component ID"]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM gm_components WHERE component_id = ? AND user_id = ?");
    $stmt->execute([$component_id, $user_id]);

    echo json_encode(["status" => "OK", "msg" => "Komponen berhasil dihapus"]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "ERROR", "msg" => "Database error: " . $e->getMessage()]);
}
