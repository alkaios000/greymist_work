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
    echo json_encode(["status" => "ERROR", "msg" => "Unauthorized: User not logged in"]);
    exit;
}

$component_name = trim($data["component_name"] ?? "");
$html_content   = trim($data["html_content"] ?? "");
$category       = trim($data["category"] ?? "Custom");

if (empty($component_name)) {
    echo json_encode(["status" => "ERROR", "msg" => "Nama komponen wajib diisi"]);
    exit;
}

if (empty($html_content)) {
    echo json_encode(["status" => "ERROR", "msg" => "Konten HTML komponen tidak boleh kosong"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO gm_components (user_id, component_name, html_content, category)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $component_name, $html_content, $category]);
    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        "status" => "OK",
        "msg" => "Komponen berhasil disimpan",
        "component" => [
            "component_id"   => $newId,
            "user_id"        => (int)$user_id,
            "component_name" => $component_name,
            "html_content"   => $html_content,
            "category"       => $category,
            "created_at"     => date("Y-m-d H:i:s")
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "ERROR", "msg" => "Database error: " . $e->getMessage()]);
}
