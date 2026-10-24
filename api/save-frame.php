<?php
require '../includes/auth.php';
require '../includes/db.php';

header('Content-Type: application/json');

/* ===============================
   ONLY POST
   =============================== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

/* ===============================
   READ JSON
   =============================== */
$data = json_decode(file_get_contents('php://input'), true);

if (
    !isset($data['frame_id']) ||
    !isset($data['content'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$frame_id = (int) $data['frame_id'];
$content  = $data['content'];
$user_id  = $_SESSION['user_id'];

/* ===============================
   VALIDATE FRAME OWNERSHIP
   =============================== */
$stmt = $pdo->prepare("
    SELECT f.frame_id
    FROM GM_FRAMES f
    JOIN GM_PROJECTS p ON p.project_id = f.project_id
    WHERE f.frame_id = ?
      AND p.user_id = ?
");
$stmt->execute([$frame_id, $user_id]);
$frame = $stmt->fetch();

if (!$frame) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

/* ===============================
   SAVE CORE CONTENT
   =============================== */
$stmt = $pdo->prepare("
    UPDATE GM_FRAMES
    SET core_content = ?
    WHERE frame_id = ?
");

$stmt->execute([$content, $frame_id]);

echo json_encode([
    'success'   => true,
    'frame_id' => $frame_id
]);
