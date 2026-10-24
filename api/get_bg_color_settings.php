<?php
header("Content-Type: application/json");
require "../includes/db.php";

if (!isset($_GET["project_id"]) || !is_numeric($_GET["project_id"])) {
    echo json_encode(["status" => "ERROR", "msg" => "INVALID PROJECT ID"]);
    exit;
}

$projectId = (int)$_GET["project_id"];

try {
    $stmt = $pdo->prepare("
        SELECT bg_color_settings, color_tokens
        FROM GM_PROJECTS
        WHERE project_id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(["status" => "NOT_FOUND"]);
        exit;
    }

    $settings = [];
    if (!empty($project["bg_color_settings"])) {
        $decoded = json_decode($project["bg_color_settings"], true);
        if (is_array($decoded)) {
            $settings = $decoded;
        }
    }

    // Default tokens jika belum ada
    if (empty($settings["tokens"])) {
        $settings["tokens"] = [];
        // Cek apakah ada tokens di color_tokens yang bisa dijadikan referensi awal
        if (!empty($project["color_tokens"])) {
            $cTokens = json_decode($project["color_tokens"], true);
            if (is_array($cTokens)) {
                $settings["tokens"] = $cTokens;
            }
        }
    }

    if (!isset($settings["recent"]) || !is_array($settings["recent"])) {
        $settings["recent"] = [];
    }

    echo json_encode([
        "status" => "OK",
        "settings" => $settings
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "ERROR", "msg" => $e->getMessage()]);
}
