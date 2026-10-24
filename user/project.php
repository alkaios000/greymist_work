<?php
// Mulai output buffering
ob_start();

require '../includes/auth.php';
require '../includes/db.php';

$user_id = $_SESSION['user_id'];

/* CREATE PROJECT */
if (isset($_POST['new_project'])) {
    $pdo->prepare("
        INSERT INTO GM_PROJECTS (user_id, project_name, project_status, is_template)
        VALUES (?, 'Untitled Project', 'unpublished', 0)
    ")->execute([$user_id]);

    $project_id = $pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
        VALUES (?, 'Main Frame', '')
    ")->execute([$project_id]);

    ob_end_clean();
    header("Location: ../index.php?project_id=$project_id");
    exit;
}

/* UPDATE PROJECT NAME (via AJAX) */
if (isset($_POST['ajax_update']) && isset($_POST['project_id']) && isset($_POST['project_name'])) {
    $updateStmt = $pdo->prepare('
        UPDATE GM_PROJECTS 
        SET project_name = ? 
        WHERE project_id = ? AND user_id = ?
    ');
    $result = $updateStmt->execute([$_POST['project_name'], $_POST['project_id'], $user_id]);

    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => $result]);
    exit;
}

/* TOGGLE PROJECT STATUS (Publish/Unpublish) */
if (isset($_POST['toggle_status']) && isset($_POST['project_id'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $getStatus = $pdo->prepare('SELECT project_status FROM GM_PROJECTS WHERE project_id = ? AND user_id = ?');
    $getStatus->execute([$_POST['project_id'], $user_id]);
    $current = $getStatus->fetch();

    if ($current) {
        $newStatus = ($current['project_status'] == 'published') ? 'unpublished' : 'published';
        $updateStatus = $pdo->prepare('
            UPDATE GM_PROJECTS 
            SET project_status = ? 
            WHERE project_id = ? AND user_id = ?
        ');
        $updateStatus->execute([$newStatus, $_POST['project_id'], $user_id]);

        ob_end_clean();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'newStatus' => $newStatus]);
        } else {
            header('Location: project.php?status_updated=1');
        }
        exit;
    }
}

/* TOGGLE IS TEMPLATE (Set as Template / Remove Template) */
if (isset($_POST['toggle_template']) && isset($_POST['project_id'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $getTemplate = $pdo->prepare('SELECT is_template FROM GM_PROJECTS WHERE project_id = ? AND user_id = ?');
    $getTemplate->execute([$_POST['project_id'], $user_id]);
    $current = $getTemplate->fetch();

    if ($current) {
        $newTemplate = ($current['is_template'] == 1) ? 0 : 1;
        $updateTemplate = $pdo->prepare('
            UPDATE GM_PROJECTS 
            SET is_template = ? 
            WHERE project_id = ? AND user_id = ?
        ');
        $updateTemplate->execute([$newTemplate, $_POST['project_id'], $user_id]);

        ob_end_clean();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'newTemplate' => $newTemplate]);
        } else {
            header('Location: project.php?template_updated=1');
        }
        exit;
    }
}

/* CLONE PROJECT */
if (isset($_POST['clone_project']) && isset($_POST['project_id'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $getProject = $pdo->prepare('SELECT project_name, thumbnail, color_tokens FROM GM_PROJECTS WHERE project_id = ? AND user_id = ?');
    $getProject->execute([$_POST['project_id'], $user_id]);
    $originalProject = $getProject->fetch();

    if ($originalProject) {
        $newProjectName = 'Copy of ' . $originalProject['project_name'];
        $insertProject = $pdo->prepare("
            INSERT INTO GM_PROJECTS (user_id, project_name, thumbnail, color_tokens, project_status, is_template)
            VALUES (?, ?, ?, ?, 'unpublished', 0)
        ");
        $insertProject->execute([$user_id, $newProjectName, $originalProject['thumbnail'], $originalProject['color_tokens']]);
        $newProjectId = $pdo->lastInsertId();

        // 1. Copy custom text colors
        $getColors = $pdo->prepare('SELECT color_variable FROM gm_colors WHERE project_id = ?');
        $getColors->execute([$_POST['project_id']]);
        $colors = $getColors->fetchAll();
        
        if (count($colors) > 0) {
            $insertColor = $pdo->prepare('INSERT INTO gm_colors (project_id, color_variable) VALUES (?, ?)');
            foreach ($colors as $color) {
                $insertColor->execute([$newProjectId, $color['color_variable']]);
            }
        }

        // 2. Clone frames & collect ID mappings
        $getFrames = $pdo->prepare('SELECT frame_id, frame_name, core_content FROM GM_FRAMES WHERE project_id = ?');
        $getFrames->execute([$_POST['project_id']]);
        $frames = $getFrames->fetchAll();

        $insertFrame = $pdo->prepare('
            INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
            VALUES (?, ?, ?)
        ');

        $frameMap = [];
        $newFrames = [];

        foreach ($frames as $frame) {
            $insertFrame->execute([$newProjectId, $frame['frame_name'], $frame['core_content']]);
            $newFrameId = $pdo->lastInsertId();
            
            $frameMap[$frame['frame_id']] = $newFrameId;
            $newFrames[] = [
                'new_id' => $newFrameId,
                'core_content' => $frame['core_content']
            ];
        }

        // 3. Update data-action-target to point to new frame IDs
        if (count($frameMap) > 0) {
            $updateFrame = $pdo->prepare('UPDATE GM_FRAMES SET core_content = ? WHERE frame_id = ?');
            foreach ($newFrames as $nf) {
                $updatedContent = $nf['core_content'];
                
                // Replace old frame_id references with new ones in data-action-target="X"
                foreach ($frameMap as $oldId => $newId) {
                    $pattern = '/data-action-target="' . preg_quote($oldId, '/') . '"/';
                    $updatedContent = preg_replace($pattern, 'data-action-target="' . $newId . '"', $updatedContent);
                }
                
                // Save if content was modified
                if ($updatedContent !== $nf['core_content']) {
                    $updateFrame->execute([$updatedContent, $nf['new_id']]);
                }
            }
        }

        ob_end_clean();
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'newProjectId' => $newProjectId, 'newProjectName' => $newProjectName, 'message' => 'Cloned']);
        } else {
            header('Location: project.php?cloned=1');
        }
        exit;
    }
}

/* DELETE PROJECT (Soft Delete) */
if (isset($_POST['delete_project']) && isset($_POST['project_id'])) {
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $deleteStmt = $pdo->prepare('
        UPDATE GM_PROJECTS 
        SET is_deleted = 1 
        WHERE project_id = ? AND user_id = ?
    ');
    $deleteStmt->execute([$_POST['project_id'], $user_id]);

    ob_end_clean();
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Location: project.php?trashed=1');
    }
    exit;
}

/* GET PROJECTS with search & sort */
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

$sql = 'SELECT p.project_id, p.project_name, p.thumbnail, p.created_at, p.project_status, p.is_template,
               (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
        FROM GM_PROJECTS p
        WHERE p.user_id = ? AND p.is_deleted = 0';

$params = [$user_id];

if (!empty($search)) {
    $sql .= ' AND p.project_name LIKE ?';
    $params[] = "%$search%";
}

// Sorting
switch ($sort) {
    case 'oldest':
        $sql .= ' ORDER BY p.created_at ASC';
        break;
    case 'name_asc':
        $sql .= ' ORDER BY p.project_name ASC';
        break;
    case 'name_desc':
        $sql .= ' ORDER BY p.project_name DESC';
        break;
    case 'latest':
    default:
        $sql .= ' ORDER BY p.created_at DESC';
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$totalProjects = count($projects);
$publishedCount = count(array_filter($projects, fn($p) => $p['project_status'] == 'published'));
$unpublishedCount = count(array_filter($projects, fn($p) => $p['project_status'] == 'unpublished'));
$templateCount = count(array_filter($projects, fn($p) => $p['is_template'] == 1));

require '../includes/header.php';
?>

<main class="gm-project-page">

    <!-- Alert Messages -->
    <?php if (isset($_GET['updated']) || isset($_GET['deleted']) || isset($_GET['cloned']) || isset($_GET['status_updated']) || isset($_GET['template_updated'])): ?>
        <div class="alerts-container">
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Nama project berhasil
                        diperbarui!</span><button class="close-alert">&times;</button></div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-danger"><i class="fas fa-trash-alt"></i><span>Project berhasil dihapus!</span><button
                        class="close-alert">&times;</button></div>
            <?php endif; ?>
            <?php if (isset($_GET['cloned'])): ?>
                <div class="alert alert-success"><i class="fas fa-copy"></i><span>Project berhasil diduplikasi!</span><button
                        class="close-alert">&times;</button></div>
            <?php endif; ?>
            <?php if (isset($_GET['status_updated'])): ?>
                <div class="alert alert-success"><i class="fas fa-sync-alt"></i><span>Status visibility
                        diperbarui!</span><button class="close-alert">&times;</button></div>
            <?php endif; ?>
            <?php if (isset($_GET['template_updated'])): ?>
                <div class="alert alert-success"><i class="fa-solid fa-diagram-project"></i><span>Status template diperbarui!</span><button
                        class="close-alert">&times;</button></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Dribbble Style Search/Filter Control Bar -->
    <div class="dash-control-bar">
        <div class="control-wrapper">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari karya Anda..." class="search-input"
                    value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <button class="clear-search" id="clearSearch" style="display: <?= $search ? 'block' : 'none' ?>;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="control-actions" style="display: flex; gap: 1rem; align-items: stretch;">
                <div class="sort-box">
                    <span class="sort-label"><i class="fas fa-sliders-h"></i> Urutkan:</span>
                    <select id="sortSelect" class="elegant-select">
                        <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Terbaru Pertama</option>
                        <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Terlama Pertama</option>
                        <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Abjad (A-Z)</option>
                        <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Abjad (Z-A)</option>
                    </select>
                </div>
                <form method="post" style="margin: 0; display: flex;">
                    <button type="submit" name="new_project" class="btn-premium" style="border-radius: 12px; padding: 0 16px; border: none; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.9rem; white-space: nowrap;">
                        <i class="fas fa-plus"></i> New Project
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    <?php if (count($projects) > 0): ?>
        <div class="gm-project-grid" id="projectGrid">
            <?php
            foreach ($projects as $index => $p):
                $thumbnail = !empty($p['thumbnail']) ? '../' . $p['thumbnail'] : '../assets/img/dummy_thumbnail.png';
                $isPublished = ($p['project_status'] == 'published');
                $isTemplate = ($p['is_template'] == 1);
                ?>
                <!-- Elegant UI Card -->
                <div class="gm-project-card" ondblclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'" style="animation-delay: <?= ($index % 12) * 0.05 ?>s">

                    <!-- Hidden functionality forms -->
                    <form method="post" id="clone-form-<?= $p['project_id'] ?>"><input type="hidden" name="project_id"
                            value="<?= $p['project_id'] ?>"><input type="hidden" name="clone_project" value="1"></form>
                    <form method="post" id="delete-form-<?= $p['project_id'] ?>"><input type="hidden" name="project_id"
                            value="<?= $p['project_id'] ?>"><input type="hidden" name="delete_project" value="1"></form>
                    <form method="post" id="toggle-form-<?= $p['project_id'] ?>"><input type="hidden" name="project_id"
                            value="<?= $p['project_id'] ?>"><input type="hidden" name="toggle_status" value="1"></form>
                    <form method="post" id="toggle-template-form-<?= $p['project_id'] ?>"><input type="hidden" name="project_id"
                            value="<?= $p['project_id'] ?>"><input type="hidden" name="toggle_template" value="1"></form>

                    <!-- Card Image Area (Live Thumbnail) -->
                    <div class="card-image-wrapper">
                        <!-- Modern Loader -->
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
                            <img src="<?= htmlspecialchars($thumbnail) ?>" alt="<?= htmlspecialchars($p['project_name']) ?>"
                                loading="lazy" 
                                onload="this.parentElement.querySelector('.thumbnail-loader').style.opacity='0'"
                                onerror="this.src='../assets/img/dummy_thumbnail.png'">
                        <?php endif; ?>

                        <!-- Add double click to open on card-top -->
                        <div class="card-glass-hover" style="display:none;"
                            onclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
                            <!-- Hidden to keep layout but effectively removed visual hover -->
                        </div>
                    </div>

                    <!-- Card Details Area -->
                    <div class="card-bottom">
                        <div class="card-titles">
                            <h3 class="card-title editable-title" data-id="<?= $p['project_id'] ?>"
                                data-original="<?= htmlspecialchars($p['project_name']) ?>">
                                <?= htmlspecialchars($p['project_name']) ?> <i class="fas fa-pencil-alt edit-icon"></i>
                            </h3>
                            <div class="card-meta">
                                <span class="meta-creator"><i class="fas fa-user-circle"></i> Anda</span>
                            </div>
                        </div>
                    </div>

                    <div class="meta-date">
                        <i class="far fa-clock"></i> <?= date('d M Y', strtotime($p['created_at'])) ?>
                    </div>

                    <!-- Badges -->
                    <?php if ($isPublished): ?>
                        <div class="badge badge-owner">
                            <i class="fas fa-globe"></i> Public
                        </div>
                    <?php else: ?>
                        <div class="badge badge-muted">
                            <i class="fas fa-lock"></i> Private
                        </div>
                    <?php endif; ?>

                    <?php if ($isTemplate): ?>
                        <div class="badge badge-template">
                            <i class="fa-solid fa-diagram-project"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Dots Menu Context -->
                    <div class="card-menu">
                        <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="card-dropdown">
                            <button type="button" class="dropdown-item" onclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
                                <i class="fas fa-pen-nib"></i>
                                <span>Buka Editor</span>
                            </button>
                            <button type="button" class="dropdown-item toggle-status" data-id="<?= $p['project_id'] ?>"
                                data-status="<?= $p['project_status'] ?>">
                                <i class="fas <?= $isPublished ? 'fa-lock' : 'fa-globe' ?>"></i>
                                <span><?= $isPublished ? 'Ubah ke Private' : 'Publish Karya' ?></span>
                            </button>
                            <button type="button" class="dropdown-item toggle-template" data-id="<?= $p['project_id'] ?>"
                                data-template="<?= $p['is_template'] ?>">
                                <i class="fa-solid fa-diagram-project"></i>
                                <span><?= $isTemplate ? 'Cabut dari Template' : 'Set as Template' ?></span>
                            </button>
                            <div style="border-top: 1px solid var(--border-soft); margin: 0.25rem 0;"></div>
                            <button type="button" class="dropdown-item clone-item" data-id="<?= $p['project_id'] ?>">
                                <i class="fas fa-copy"></i>
                                <span>Duplicate</span>
                            </button>
                            <button type="button" class="dropdown-item delete-item danger" data-id="<?= $p['project_id'] ?>"
                                data-name="<?= htmlspecialchars($p['project_name']) ?>">
                                <i class="fas fa-trash-alt"></i>
                                <span>Delete</span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Elegant Empty State -->
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-magic"></i></div>
            <h3>Papan Kanvas Anda Masih Kosong</h3>
            <p>Jadilah kreator berikutnya. Mulai drag & drop elemen ke dalam karya pertamamu.</p>
            <form method="post" style="margin-top:2rem;">
                <button type="submit" name="new_project" class="btn-premium">
                    <i class="fas fa-plus"></i> Ciptakan Project Baru
                </button>
            </form>
        </div>
    <?php endif; ?>
</main>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
<!-- Core Editor Styles for Thumbnail -->
<link rel="stylesheet" href="../assets/Style/main/main.css">

<!-- Modals System -->
<div id="deleteModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Hapus Permanent</h3>
        <div class="modal-glass-body">
            <p>Konfirmasi untuk menghapus <strong><span id="deleteProjectName"></span></strong>?</p>
            <p style="color:#ef4444; font-size:0.8rem; margin-top:5px;">Tindakan ini menghapus seluruh state HTML
                beserta file yang ada.</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">Batalkan</button>
            <button class="btn-confirm btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
        </div>
    </div>
</div>

<div id="publishModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-success"><i class="fas fa-globe-asia"></i></div>
        <h3>Publish Project</h3>
        <div class="modal-glass-body">
            <p>Pamerkan maha karya Anda, <strong><span id="publishProjectName"></span></strong>?</p>
            <p>Semua creator di seluruh dunia bisa melihat layaknya portfolio.</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelPublishBtn">Tunggu Dulu</button>
            <button class="btn-confirm" id="confirmPublishBtn" style="background:#22c55e">Ya, Publish Sekarang</button>
        </div>
    </div>
</div>

<div id="unpublishModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-muted"><i class="fas fa-user-lock"></i></div>
        <h3>Kembalikan Private</h3>
        <div class="modal-glass-body">
            <p>Sembunyikan karya <strong><span id="unpublishProjectName"></span></strong>?</p>
            <p>Orang lain tidak akan lagi bisa meng-clone template/project ini.</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelUnpublishBtn">Batal</button>
            <button class="btn-confirm" id="confirmUnpublishBtn" style="background:#64748b">Set Private</button>
        </div>
    </div>
</div>

<div id="templateModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-warning"><i class="fa-solid fa-diagram-project"></i></div>
        <h3 id="templateModalTitle">Set as Template</h3>
        <div class="modal-glass-body">
            <p id="templateModalMessage">Jadikan <strong><span id="templateProjectName"></span></strong> sebagai
                template publik?</p>
            <p id="templateModalWarning">Akan tampil di daftar "Premium Templates" dan mempercepat workflow user lain.
            </p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelTemplateBtn">Batal</button>
            <button class="btn-confirm" id="confirmTemplateBtn" style="background:#f59e0b">Konfirmasi Template</button>
        </div>
    </div>
</div>

<style>
    /* ==========================================================================
   ELEGANT DRIBBBLE UI - PROJECTS
   ========================================================================== */

    :root {
        --bg-main: #f8f9fa;
        --text-main: #333333;
        --text-muted: #6c757d;
        --border-soft: rgba(0, 0, 0, 0.08);
        --shadow-soft: 0 10px 40px -10px rgba(13, 110, 253, 0.08);
        --shadow-hover: 0 20px 40px -15px rgba(13, 110, 253, 0.15);
        --font-heading: 'Poppins', sans-serif;
        --accent: #0d6efd;
    }

    body {
        background: var(--bg-main);
        color: var(--text-main);
    }

    .gm-project-page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1.5rem 6rem 1.5rem;
        min-height: 100vh;
    }

    /* --- COMPACT HEADER & CANVAS --- */
    .compact-header {
        position: relative;
        background: #ffffff;
        border-radius: 20px;
        padding: 2rem 2.5rem;
        border: 1px solid var(--border-soft);
        margin-bottom: 2rem;
        overflow: hidden;
        box-shadow: 0 4px 20px -10px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    #nodeCanvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        pointer-events: none; /* Let clicks pass through */
    }

    .compact-header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .dash-title {
        font-family: var(--font-heading);
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.2rem;
    }

    .dash-subtitle {
        font-size: 0.95rem;
        color: var(--text-muted);
        margin: 0;
    }

    .compact-stats {
        position: relative;
        z-index: 2;
        display: flex;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    .dash-title {
        font-family: var(--font-heading);
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.04em;
        margin-bottom: 1rem;
    }

    .gradient-text {
        background: linear-gradient(135deg, #0f172a 0%, #64748b 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    .dash-subtitle {
        font-size: 1.15rem;
        color: var(--text-muted);
        max-width: 600px;
        margin: 0 auto 2.5rem;
        line-height: 1.6;
    }

    .hero-stats-row {
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 3rem;
    }

    .stat-pill {
        background: white;
        border: 1px solid var(--border-soft);
        padding: 8px 18px;
        border-radius: 100px;
        font-size: 0.9rem;
        color: var(--text-muted);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }

    .stat-pill strong {
        font-size: 1rem;
        color: #0f172a;
    }

    .stat-pill.success strong {
        color: #22c55e;
    }

    .stat-pill.muted strong {
        color: #64748b;
    }

    .stat-pill.warning strong {
        color: #f59e0b;
    }

    .btn-compact {
        padding: 0.8rem 1.8rem;
        font-size: 1rem;
        border-radius: 12px;
    }
    
    .btn-premium {
        background: linear-gradient(135deg, #0d6efd, #00d4ff) !important;
        color: white !important;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
        transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .btn-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
    }

    .mt-4 {
        margin-top: 2rem;
    }

    /* Alerts */
    .alerts-container {
        margin-bottom: 2rem;
    }

    .alert {
        padding: 1rem 1.5rem;
        border-radius: 16px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .alert-danger {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .alert i {
        font-size: 1.2rem;
    }

    .alert span {
        flex: 1;
    }

    .close-alert {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: inherit;
        opacity: 0.6;
    }

    .close-alert:hover {
        opacity: 1;
    }

    /* --- CONTROL BAR (Search & SORT) --- */
    .dash-control-bar {
        margin-bottom: 2.5rem;
    }

    .control-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        background: white;
        padding: 0.5rem 0.5rem 0.5rem 1rem;
        border-radius: 16px;
        border: 1px solid var(--border-soft);
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.03);
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        flex: 1;
        min-width: 250px;
    }

    .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    #searchInput {
        width: 100%;
        padding: 10px 40px 10px 40px;
        border-radius: 10px;
        border: 1px solid transparent;
        background: transparent;
        font-size: 0.95rem;
        outline: none;
        transition: all 0.3s;
        color: var(--text-main);
        font-weight: 500;
    }

    #searchInput:focus {
        background: white;
        border-color: #cbd5e1;
        box-shadow: 0 0 0 4px rgba(226, 232, 240, 0.6);
    }

    .clear-search {
        position: absolute;
        right: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
    }

    .sort-box {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: transparent;
        padding: 4px 8px;
    }

    .sort-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-muted);
    }

    .elegant-select {
        appearance: none;
        background: #f8fafc;
        border: 1px solid var(--border-soft);
        padding: 8px 30px 8px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-main);
        cursor: pointer;
        outline: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%230f172a' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    /* --- CARDS GRID (CSS GRID) --- */
    /* Global .gm-project-card CSS is handled in header.php */

    /* Global .card-title CSS is handled in header.php, but editable title additions here: */
    .card-title {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .editable-title {
        cursor: pointer;
        padding: 0.1rem 0.2rem;
        margin-left: -0.2rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .editable-title:hover {
        background: #f1f5f9;
        color: #2563eb;
    }

    .edit-icon {
        font-size: 0.75rem;
        color: #94a3b8;
        opacity: 0;
        transform: translateX(-5px);
        transition: all 0.2s ease;
    }

    .editable-title:hover .edit-icon {
        opacity: 1;
        transform: translateX(0);
        color: #2563eb;
    }

    .title-edit-input {
        width: 100%;
        padding: 0.4rem 0.6rem;
        font-size: 0.95rem;
        font-weight: 600;
        font-family: var(--font-heading);
        border: 2px solid #3b82f6;
        border-radius: 6px;
        outline: none;
        background: white;
        color: #0f172a;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    }

    /* Date and icons handled globally */
    /* DOTS MENU */
    /* Dropdown CSS is handled globally in header.php */

    .menu-divider {
        border: none;
        height: 1px;
        background: var(--border-soft);
        margin: 0.4rem 0;
    }

    .delete-item {
        color: #ef4444;
    }

    .delete-item:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    /* --- EMPTY / ERROR STATES --- */
    .empty-state {
        text-align: center;
        padding: 8rem 2rem;
        background: white;
        border-radius: 32px;
        border: 1px solid var(--border-soft);
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        background: #f8fafc;
        border-radius: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #cbd5e1;
        margin: 0 auto 1.5rem;
    }

    .empty-state h3 {
        font-size: 1.8rem;
        font-family: var(--font-heading);
        margin-bottom: 0.5rem;
        color: var(--text-main);
    }

    .empty-state p {
        color: var(--text-muted);
        font-size: 1.1rem;
    }


    @media (max-width: 1024px) {
        .gm-project-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 768px) {
        .dash-hero {
            padding: 4rem 1.5rem 3rem;
        }

        .dash-title {
            font-size: 2.5rem;
        }

        .control-wrapper {
            flex-direction: column;
            align-items: stretch;
        }

        .sort-box {
            justify-content: space-between;
        }

        .gm-project-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ========== SEARCH & SORT ==========
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const sortSelect = document.getElementById('sortSelect');
        let searchTimeout;

        function performSearchAndSort() {
            const searchTerm = searchInput.value.trim();
            const sortValue = sortSelect.value;
            window.location.href = `project.php?search=${encodeURIComponent(searchTerm)}&sort=${sortValue}`;
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearchAndSort, 500);
                clearSearch.style.display = this.value ? 'block' : 'none';
            });
        }

        if (clearSearch) {
            clearSearch.addEventListener('click', function () {
                searchInput.value = '';
                performSearchAndSort();
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', performSearchAndSort);
        }

        // ========== DROPDOWN MENU IS NOW HANDLED IN FOOTER.PHP ==========

        // ========== INLINE EDITING ==========
        const editableTitles = document.querySelectorAll('.editable-title');

        editableTitles.forEach(titleElement => {
            titleElement.addEventListener('click', function (e) {
                e.stopPropagation();
                const projectId = this.getAttribute('data-id');
                const currentText = this.getAttribute('data-original');
                const input = document.createElement('input');
                input.type = 'text';
                input.value = currentText;
                input.className = 'title-edit-input';

                this.style.display = 'none';
                this.parentNode.insertBefore(input, this);
                input.focus();
                input.select();

                const saveName = async () => {
                    const newName = input.value.trim();
                    if (newName === '' || newName === currentText) {
                        input.remove();
                        this.style.display = 'flex';
                        return;
                    }

                    try {
                        const formData = new URLSearchParams();
                        formData.append('ajax_update', '1');
                        formData.append('project_id', projectId);
                        formData.append('project_name', newName);

                        const response = await fetch('project.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.innerHTML = escapeHtml(newName) + ' <i class="fas fa-pencil-alt edit-icon"></i>';
                            this.setAttribute('data-original', newName);
                            showToast('Nama project berhasil diubah!', 'success');
                        } else {
                            showToast('Gagal mengubah nama project', 'error');
                        }
                    } catch (error) {
                        showToast('Terjadi kesalahan', 'error');
                    } finally {
                        input.remove();
                        this.style.display = 'flex';
                    }
                };

                input.addEventListener('keypress', (e) => { if (e.key === 'Enter') { e.preventDefault(); saveName(); } });
                input.addEventListener('blur', () => saveName());
            });
        });

        // ========== MODAL TOGGLERS ==========
        function setupModalListeners(triggerClass, idVarSetter, nameSpanId, modalId, confirmBtnId, formPrefix) {
            const triggers = document.querySelectorAll(triggerClass);
            const modal = document.getElementById(modalId);
            const nameSpan = document.getElementById(nameSpanId);
            const cancelBtn = modal ? modal.querySelector('.btn-cancel') : null;
            const confirmBtn = document.getElementById(confirmBtnId);

            triggers.forEach(btn => {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    closeAllDropdowns();
                    const pid = this.getAttribute('data-id');
                    const card = this.closest('.gm-project-card');
                    const pname = card ? card.querySelector('.editable-title').getAttribute('data-original') : 'Project';

                    idVarSetter(pid);

                    // Specific tweaks before opening
                    if (triggerClass === '.toggle-status') {
                        const status = this.getAttribute('data-status');
                        if (status === 'published') {
                            document.getElementById('unpublishProjectName').textContent = pname;
                            document.getElementById('unpublishModal').style.display = 'flex';
                            return; // breaks to unpublish modal
                        } else {
                            document.getElementById('publishProjectName').textContent = pname;
                            document.getElementById('publishModal').style.display = 'flex';
                            return; // breaks to publish modal
                        }
                    }

                    if (triggerClass === '.toggle-template') {
                        const isTpl = this.getAttribute('data-template') == '1';
                        document.getElementById('templateModalTitle').textContent = isTpl ? 'Hapus dari Template' : 'Set as Template';
                        document.getElementById('templateModalMessage').innerHTML = isTpl
                            ? `Project <strong>${escapeHtml(pname)}</strong> tidak lagi tersedia sbg template.`
                            : `Jadikan <strong>${escapeHtml(pname)}</strong> sebagai template publik?`;
                        document.getElementById('confirmTemplateBtn').textContent = isTpl ? 'Hapus Template' : 'Konfirmasi Template';
                    }

                    if (nameSpan) nameSpan.textContent = pname;
                    if (modal) modal.style.display = 'flex';
                });
            });

            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    const pid = window[`current_${modalId}_id`];
                    if (pid) document.getElementById(`${formPrefix}${pid}`).submit();
                });
            }

            if (cancelBtn) cancelBtn.addEventListener('click', () => modal.style.display = 'none');
            if (modal) modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
        }

        // Initialize logic mapping vars
        window.current_deleteModal_id = null;
        window.current_publishModal_id = null;
        window.current_unpublishModal_id = null;
        window.current_templateModal_id = null;
        window.current_cloneModal_id = null;

        document.querySelectorAll('.clone-item').forEach(item => {
            item.addEventListener('click', function (e) {
                e.stopPropagation();
                closeAllDropdowns();
                const pid = this.getAttribute('data-id');
                document.getElementById(`clone-form-${pid}`).submit();
            });
        });

        setupModalListeners('.delete-item', (id) => window.current_deleteModal_id = id, 'deleteProjectName', 'deleteModal', 'confirmDeleteBtn', 'delete-form-');
        setupModalListeners('.toggle-status', (id) => { window.current_publishModal_id = id; window.current_unpublishModal_id = id; }, null, null, null, null);
        setupModalListeners('.toggle-template', (id) => window.current_templateModal_id = id, 'templateProjectName', 'templateModal', 'confirmTemplateBtn', 'toggle-template-form-');

        // Wire up manual toggles due to combined nature
        document.getElementById('confirmPublishBtn')?.addEventListener('click', () => {
            document.getElementById(`toggle-form-${window.current_publishModal_id}`).submit();
        });
        document.getElementById('confirmUnpublishBtn')?.addEventListener('click', () => {
            document.getElementById(`toggle-form-${window.current_unpublishModal_id}`).submit();
        });

        const pubMod = document.getElementById('publishModal');
        const unpubMod = document.getElementById('unpublishModal');
        document.getElementById('cancelPublishBtn')?.addEventListener('click', () => pubMod.style.display = 'none');
        document.getElementById('cancelUnpublishBtn')?.addEventListener('click', () => unpubMod.style.display = 'none');
        pubMod?.addEventListener('click', (e) => { if (e.target === pubMod) pubMod.style.display = 'none'; });
        unpubMod?.addEventListener('click', (e) => { if (e.target === unpubMod) unpubMod.style.display = 'none'; });

        // ========== HELPER FUNCTIONS ==========
        function escapeHtml(str) {
            return str.replace(/[&<>]/g, function (m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.style.cssText = `position: fixed; bottom: 30px; right: 30px; background: ${type === 'success' ? '#22c55e' : '#ef4444'}; color: white; padding: 12px 22px; border-radius: 100px; display: flex; align-items: center; gap: 0.6rem; z-index: 999999; font-size: 0.9rem; font-weight:bold; box-shadow: 0 8px 25px rgba(0,0,0,0.15);`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.4s'; setTimeout(() => toast.remove(), 400); }, 3000);
        }

        document.querySelectorAll('.close-alert').forEach(btn => {
            btn.addEventListener('click', function () { this.closest('.alert')?.remove(); });
        });
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = 0; alert.style.transition = 'opacity 0.3s';
                setTimeout(() => alert.remove(), 300);
            });
        }, 4000);
    });
</script>

<script>
    // Logic matching the frame thumbnail in top-slide.html
    function applyThumbnailLogic() {
        const coreW = window.innerWidth;
        const coreH = window.innerHeight;

        document.querySelectorAll('.live-thumbnail-wrapper').forEach(wrapper => {
            const preview = wrapper.querySelector('.mini-preview');
            if (preview && preview.innerHTML.trim() !== '') {
                preview.style.width = coreW + 'px';
                preview.style.height = coreH + 'px';
                preview.style.transformOrigin = 'center center';

                const bw = wrapper.clientWidth;
                const bh = wrapper.clientHeight;
                const scale = Math.min(bw / coreW, bh / coreH);

                preview.style.position = 'absolute';
                preview.style.top = '50%';
                preview.style.left = '50%';
                preview.style.transform = `translate(-50%, -50%) scale(${scale})`;
                
                // Show preview and hide loader
                wrapper.style.opacity = '1';
                const loader = wrapper.parentElement.querySelector('.thumbnail-loader');
                if (loader) loader.style.opacity = '0';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const lazyObserver = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const wrapper = entry.target;
                    const preview = wrapper.querySelector('.mini-preview');
                    
                    if (preview && !preview.innerHTML.trim()) {
                        const content = wrapper.getAttribute('data-content');
                        if (content) {
                            preview.innerHTML = content;
                            wrapper.removeAttribute('data-content');
                            // Delay slightly for DOM update
                            setTimeout(applyThumbnailLogic, 20);
                        }
                    }
                    obs.unobserve(wrapper);
                }
            });
        }, { rootMargin: '300px 0px' });

        document.querySelectorAll('.lazy-live-thumbnail').forEach(wrapper => {
            lazyObserver.observe(wrapper);
        });
    });

    window.addEventListener('resize', applyThumbnailLogic);
    window.addEventListener('load', applyThumbnailLogic);
    // Force call
    setTimeout(applyThumbnailLogic, 100);
</script>

<?php
require '../includes/footer.php';
if (ob_get_level())
    ob_end_flush();
?>