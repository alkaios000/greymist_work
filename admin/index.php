<?php
require 'includes/auth.php';
require_once '../includes/db.php';

$page_title = 'Dashboard';

// Statistik
$stmt = $pdo->query("SELECT COUNT(*) FROM gm_users");
$totalUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_projects");
$totalProjects = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_projects WHERE project_status = 'published'");
$totalPublished = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_projects WHERE is_template = 1");
$totalTemplates = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_frames");
$totalFrames = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$newUsers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM gm_projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$newProjects = $stmt->fetchColumn();

// 5 user terbaru
$stmt = $pdo->query("SELECT user_id, nickname, username, created_at FROM gm_users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// 5 project terbaru
$stmt = $pdo->query("
    SELECT p.project_id, p.project_name, p.thumbnail, p.project_status, p.is_template, p.created_at,
           u.nickname, u.username,
           (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
    FROM gm_projects p
    LEFT JOIN gm_users u ON p.user_id = u.user_id
    ORDER BY p.created_at DESC LIMIT 5
");
$recentProjects = $stmt->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="admin-main">
    <!-- Sensei Top Bar -->
    <div class="sensei-top-bar">
        <div class="breadcrumbs">
            <span><i class="fas fa-layer-group"></i></span>
            <span><i class="fas fa-arrow-right" style="font-size: 0.6rem; color: #CBD5E1; margin: 0 4px;"></i></span>
            <span style="color: var(--accent);">Greymist</span>
            <span style="color: #CBD5E1; margin: 0 4px;">/</span>
            <span class="active">Dashboard</span>
        </div>
        
        <div class="top-actions">
            <input type="text" class="search-input" placeholder="Search">
            
            <button class="btn-outline">
                <i class="fas fa-calendar"></i> June <i class="fas fa-chevron-down" style="font-size: 0.7rem; margin-left: 4px;"></i>
            </button>
            
            <div style="width: 1px; height: 24px; background: var(--border-soft); margin: 0 4px;"></div>
            
            <button class="btn-outline">
                <i class="fas fa-file-export"></i> Export PDF
            </button>
            
            <button class="btn-solid">
                <i class="fas fa-plus"></i> Add Workspace
            </button>
        </div>
    </div>
    
    <!-- Overview Section -->
    <div class="section-header">
        <div class="section-title">
            <h3>Overview</h3>
            <p>Here is the summary of overall data</p>
        </div>
        <button class="btn-outline" style="border-color: var(--border-soft); color: var(--text-main);">
            <i class="fas fa-sync-alt"></i> Restart Data
        </button>
    </div>
    
    <div class="overview-grid">
        <!-- Card 1: Users -->
        <div class="overview-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-users" style="color: #F59E0B;"></i></div>
                <div class="card-title-area">
                    <h4>Total Users</h4>
                    <p><?= number_format($newUsers) ?> Users this week</p>
                </div>
            </div>
            <div class="card-body">
                <div class="card-value"><?= number_format($totalUsers) ?></div>
                <div class="card-trend"><i class="fas fa-arrow-up"></i> 16,3%</div>
            </div>
            <a href="users.php" class="card-footer">
                <span>See detail</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <!-- Card 2: Projects -->
        <div class="overview-card">
            <div class="card-header">
                <div class="card-icon"><i class="fas fa-folder-open" style="color: #3B82F6;"></i></div>
                <div class="card-title-area">
                    <h4>Total Projects</h4>
                    <p><?= number_format($newProjects) ?> Projects this week</p>
                </div>
            </div>
            <div class="card-body">
                <div class="card-value"><?= number_format($totalProjects) ?></div>
                <div class="card-trend"><i class="fas fa-arrow-up"></i> 12,3%</div>
            </div>
            <a href="projects.php" class="card-footer">
                <span>See detail</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <!-- Card 3: Templates -->
        <div class="overview-card">
            <div class="card-header">
                <div class="card-icon"><i class="fa-solid fa-diagram-project" style="color: #EC4899;"></i></div>
                <div class="card-title-area">
                    <h4>Pro Templates</h4>
                    <p><?= number_format($totalPublished) ?> Published</p>
                </div>
            </div>
            <div class="card-body">
                <div class="card-value"><?= number_format($totalTemplates) ?></div>
                <div class="card-trend"><i class="fas fa-arrow-up"></i> 26,3%</div>
            </div>
            <a href="templates.php" class="card-footer">
                <span>See detail</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Recent Activity Section -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 2rem;">
        
        <!-- Recent Projects -->
        <div>
            <div class="section-header" style="margin-bottom: 1rem;">
                <div class="section-title">
                    <h3>Recent Projects</h3>
                    <p>Latest templates and projects created</p>
                </div>
                <a href="projects.php" class="btn-outline" style="color: var(--accent); border-color: var(--accent-light); text-decoration: none;">
                    View all <i class="fas fa-arrow-right" style="margin-left: 4px;"></i>
                </a>
            </div>
            
            <div class="gm-project-grid" style="grid-template-columns: repeat(2, 1fr);">
                <?php foreach ($recentProjects as $p): 
                    $thumbnail = !empty($p['thumbnail']) ? '../' . $p['thumbnail'] : '../assets/img/dummy_thumbnail.png';
                ?>
                <div class="gm-project-card">
                    <div class="card-image-wrapper">

                        
                        <div class="thumbnail-loader">
                            <div class="thumbnail-spinner"></div>
                        </div>
                        
                        <?php
                        if (!empty($p['first_frame_content'])):
                            $content = str_replace('assets/', '../assets/', $p['first_frame_content']);
                            $content = str_replace('url("assets/', 'url("../assets/', $content);
                            $content = str_replace("url('assets/", "url('../assets/", $content);
                            ?>
                            <div class="live-thumbnail-wrapper lazy-live-thumbnail" data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="mini-preview gm-object-core"></div>
                            </div>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($thumbnail) ?>" 
                                 alt="<?= htmlspecialchars($p['project_name']) ?>"
                                 loading="lazy"
                                 onload="this.parentElement.querySelector('.thumbnail-loader').style.opacity='0'"
                                 onerror="this.src='../assets/img/dummy_thumbnail.png'">
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-bottom">
                        <div class="card-titles">
                            <h3 class="card-title"><?= htmlspecialchars($p['project_name']) ?></h3>
                            <div class="card-meta">
                                <span class="meta-creator"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($p['nickname'] ?? $p['username'] ?? 'Unknown') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="meta-date">
                        <i class="far fa-clock"></i> <?= date('d M Y', strtotime($p['created_at'])) ?>
                    </div>
                    
                    <?php if ($p['project_status'] == 'published'): ?>
                        <div class="badge badge-owner">
                            <i class="fas fa-globe"></i> Public
                        </div>
                    <?php else: ?>
                        <div class="badge badge-muted">
                            <i class="fas fa-lock"></i> Private
                        </div>
                    <?php endif; ?>

                    <?php if ($p['is_template']): ?>
                        <div class="badge badge-template"><i class="fa-solid fa-diagram-project"></i></div>
                    <?php endif; ?>

                    <div class="card-menu">
                        <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="card-dropdown">
                            <button class="dropdown-item" onclick="window.location.href='projects.php'">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Users -->
        <div>
            <div class="section-header" style="margin-bottom: 1rem;">
                <div class="section-title">
                    <h3>New Users</h3>
                    <p>Recently joined members</p>
                </div>
            </div>
            
            <div style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); overflow: hidden; box-shadow: var(--shadow-card);">
                <?php foreach ($recentUsers as $u): ?>
                <div style="display: flex; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-soft);">
                    <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--accent-light); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 600; margin-right: 1rem;">
                        <?= strtoupper(substr($u['nickname'] ?? $u['username'], 0, 1)) ?>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 0.9rem; font-weight: 600; margin-bottom: 2px; color: var(--text-main);"><?= htmlspecialchars($u['nickname'] ?? $u['username']) ?></h4>
                        <p style="font-size: 0.75rem; color: var(--text-muted);">@<?= htmlspecialchars($u['username']) ?></p>
                    </div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">
                        <?= date('d M', strtotime($u['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="padding: 0.8rem; text-align: center;">
                    <a href="users.php" style="font-size: 0.8rem; color: var(--accent); text-decoration: none; font-weight: 500;">Manage All Users</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>