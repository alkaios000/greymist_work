<?php
header("Content-Type: application/json");
require "../includes/db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data["project_id"]) || !is_numeric($data["project_id"])) {
    echo json_encode(["status" => "ERROR", "msg" => "INVALID PROJECT ID"]);
    exit;
}

$projectId = (int)$data["project_id"];
$settings = $data["settings"] ?? [];

if (!is_array($settings)) {
    echo json_encode(["status" => "ERROR", "msg" => "INVALID SETTINGS DATA"]);
    exit;
}

$settingsJson = json_encode($settings);

try {
    $stmt = $pdo->prepare("
        UPDATE GM_PROJECTS
        SET bg_color_settings = ?
        WHERE project_id = ?
    ");
    $result = $stmt->execute([$settingsJson, $projectId]);

    // Jika ada tokens dalam settings, sinkronkan juga ke color_tokens & GM_COLORS agar CSS variable global bekerja
    if (!empty($settings["tokens"]) && is_array($settings["tokens"])) {
        // Ambil token yang sudah ada di color_tokens agar tidak tertimpa tapi dimerge
        $stmtEx = $pdo->prepare("SELECT color_tokens FROM GM_PROJECTS WHERE project_id = ?");
        $stmtEx->execute([$projectId]);
        $existing = $stmtEx->fetchColumn();
        $existingTokens = $existing ? json_decode($existing, true) : [];
        if (!is_array($existingTokens)) $existingTokens = [];

        $mergedTokens = array_merge($existingTokens, $settings["tokens"]);
        $mergedJson = json_encode($mergedTokens);

        $stmtUp = $pdo->prepare("UPDATE GM_PROJECTS SET color_tokens = ? WHERE project_id = ?");
        $stmtUp->execute([$mergedJson, $projectId]);

        // Sinkronkan ke GM_COLORS
        try {
            $del = $pdo->prepare("DELETE FROM GM_COLORS WHERE project_id = ?");
            $del->execute([$projectId]);

            $ins = $pdo->prepare("INSERT INTO GM_COLORS (project_id, color_variable) VALUES (?, ?)");
            foreach ($mergedTokens as $name => $val) {
                $varName = str_starts_with($name, "--") ? $name : "--$name";
                $ins->execute([$projectId, "$varName: $val"]);
            }
        } catch (Exception $e) {}
    }

    if ($result) {
        echo json_encode(["status" => "OK", "settings" => $settings]);
    } else {
        echo json_encode(["status" => "ERROR", "msg" => "FAILED TO SAVE TO DATABASE"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "ERROR", "msg" => $e->getMessage()]);
}
