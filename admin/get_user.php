<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'User ID required']);
    exit;
}

$userId = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT user_id, nickname, username, email, profile, created_at, is_admin,
           (SELECT COUNT(*) FROM gm_projects WHERE user_id = gm_users.user_id) as total_projects,
           (SELECT COUNT(*) FROM gm_projects WHERE user_id = gm_users.user_id AND is_template = 1) as total_templates,
           DATE_FORMAT(created_at, '%d %M %Y') as created_at_formatted
    FROM gm_users WHERE user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode([
        'success' => true,
        'user' => [
            'user_id' => $user['user_id'],
            'nickname' => $user['nickname'],
            'username' => $user['username'],
            'email' => $user['email'],
            'profile' => $user['profile'],
            'is_admin' => $user['is_admin'],
            'total_projects' => $user['total_projects'],
            'total_templates' => $user['total_templates'],
            'created_at' => $user['created_at_formatted']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'User not found']);
}
?>