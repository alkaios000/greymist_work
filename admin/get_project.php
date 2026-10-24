<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Project ID required']);
    exit;
}

$projectId = (int)$_GET['id'];

// Ambil data project
$stmt = $pdo->prepare("
    SELECT p.*, u.nickname, u.username,
           DATE_FORMAT(p.created_at, '%d %M %Y') as created_at_formatted
    FROM gm_projects p
    LEFT JOIN gm_users u ON p.user_id = u.user_id
    WHERE p.project_id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    echo json_encode(['success' => false, 'error' => 'Project not found']);
    exit;
}

// Ambil frames
$stmt = $pdo->prepare("
    SELECT frame_id, frame_name, core_content 
    FROM gm_frames 
    WHERE project_id = ? 
    ORDER BY frame_id ASC
");
$stmt->execute([$projectId]);
$frames = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert thumbnail ke base64 untuk ditampilkan
$thumbnailBase64 = null;
if ($project['thumbnail']) {
    $thumbnailBase64 = base64_encode($project['thumbnail']);
}

echo json_encode([
    'success' => true,
    'project' => [
        'project_id' => $project['project_id'],
        'project_name' => $project['project_name'],
        'project_status' => $project['project_status'],
        'is_template' => $project['is_template'],
        'thumbnail' => $thumbnailBase64,
        'owner_name' => $project['nickname'] ?? $project['username'] ?? 'Unknown',
        'created_at' => $project['created_at_formatted']
    ],
    'frames' => $frames
]);
?>