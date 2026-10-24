<?php
session_start();
require_once '../includes/db.php';

// Sementara: verifikasi admin
$isAdmin = true; // Ganti dengan validasi role nanti

if (!$isAdmin) {
    die("Access denied");
}

if (!isset($_GET['project_id'])) {
    die("Project ID required");
}

$project_id = (int) $_GET['project_id'];

// Ambil project tanpa validasi user_id
$stmt = $pdo->prepare("
    SELECT p.*, u.username, u.nickname 
    FROM gm_projects p
    LEFT JOIN gm_users u ON p.user_id = u.user_id
    WHERE p.project_id = ?
");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    die("Project not found");
}

// Ambil frames
$stmt = $pdo->prepare("SELECT * FROM gm_frames WHERE project_id = ? ORDER BY frame_id ASC");
$stmt->execute([$project_id]);
$frames = $stmt->fetchAll();

// Ambil colors
$stmt = $pdo->prepare("SELECT color_variable FROM gm_colors WHERE project_id = ?");
$stmt->execute([$project_id]);
$colors = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?= htmlspecialchars($project['project_name']) ?> – Admin</title>
    
    <link rel="icon" href="../assets/web-icon/greymist.svg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .admin-banner {
            background: #f59e0b;
            color: #1e293b;
            padding: 0.5rem 1rem;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 500;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .admin-banner a {
            color: #1e293b;
            margin-left: 1rem;
            text-decoration: none;
        }
        .admin-banner a:hover {
            text-decoration: underline;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        .preview-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .project-info {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9edf2;
        }
        .project-info h2 {
            margin-bottom: 0.5rem;
        }
        .project-info p {
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .frames-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1rem;
        }
        .frame-card {
            background: white;
            border-radius: 0.8rem;
            border: 1px solid #e9edf2;
            overflow: hidden;
        }
        .frame-header {
            padding: 0.8rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e9edf2;
            font-weight: 600;
        }
        .frame-content {
            padding: 1rem;
            font-size: 0.8rem;
            color: #475569;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #f59e0b;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-banner">
        <i class="fas fa-eye"></i> Admin Preview Mode - Viewing project by: <?= htmlspecialchars($project['nickname'] ?? $project['username'] ?? 'Unknown') ?>
        <a href="projects.php"><i class="fas fa-arrow-left"></i> Back to Admin Panel</a>
    </div>
    
    <div class="preview-container">
        <a href="projects.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Projects</a>
        
        <div class="project-info">
            <h2><?= htmlspecialchars($project['project_name']) ?></h2>
            <p><i class="fas fa-user"></i> Owner: <?= htmlspecialchars($project['nickname'] ?? $project['username'] ?? 'Unknown') ?></p>
            <p><i class="fas fa-calendar"></i> Created: <?= date('d M Y H:i', strtotime($project['created_at'])) ?></p>
            <p><i class="fas fa-tag"></i> Status: <?= $project['project_status'] == 'published' ? 'Published' : 'Private' ?></p>
            <p><i class="fa-solid fa-diagram-project"></i> Type: <?= $project['is_template'] ? 'Template' : 'Regular Project' ?></p>
        </div>
        
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-layer-group"></i> Frames (<?= count($frames) ?>)</h3>
        
        <div class="frames-grid">
            <?php foreach ($frames as $frame): ?>
            <div class="frame-card">
                <div class="frame-header">
                    <i class="fas fa-file-alt"></i> <?= htmlspecialchars($frame['frame_name'] ?? 'Untitled') ?>
                </div>
                <div class="frame-content">
                    <?php 
                    $content = $frame['core_content'];
                    if (strlen($content) > 500) {
                        $content = substr($content, 0, 500) . '...';
                    }
                    echo nl2br(htmlspecialchars($content));
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($frames)): ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: 0.8rem;">
            <i class="fas fa-layer-group" style="font-size: 3rem; color: #cbd5e1;"></i>
            <p>No frames found in this project</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>