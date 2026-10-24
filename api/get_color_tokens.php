<?php
header("Content-Type: application/json");
require "../includes/db.php";

if (!isset($_GET["project_id"]) || !is_numeric($_GET["project_id"])) {
  echo json_encode(["status" => "ERROR", "msg" => "INVALID PROJECT ID"]);
  exit;
}

$projectId = (int)$_GET["project_id"];

$stmt = $pdo->prepare("
  SELECT color_tokens
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

$tokens = $project["color_tokens"];
$tokensArray = [];

if ($tokens) {
    $decoded = json_decode($tokens, true);
    if (is_array($decoded)) {
        $tokensArray = $decoded;
    }
}

// Fallback: jika belum ada di color_tokens, periksa GM_COLORS
if (empty($tokensArray)) {
    try {
        $stmtC = $pdo->prepare("SELECT color_variable FROM GM_COLORS WHERE project_id = ?");
        $stmtC->execute([$projectId]);
        $colors = $stmtC->fetchAll(PDO::FETCH_COLUMN);
        foreach ($colors as $c) {
            if (str_contains($c, ':')) {
                [$k, $v] = explode(':', $c, 2);
                $tokensArray[trim($k)] = trim($v);
            } else {
                $tokensArray[trim($c)] = "#000000";
            }
        }
    } catch (Exception $e) {}
}

echo json_encode([
  "status" => "OK",
  "tokens" => (object)$tokensArray
]);
