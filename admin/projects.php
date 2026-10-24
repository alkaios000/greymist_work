<?php
require 'includes/auth.php';
require_once '../includes/db.php';

$page_title = 'Manage Projects';

// Handle Delete Project
if (isset($_POST['delete_project']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("DELETE FROM gm_frames WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $stmt = $pdo->prepare("DELETE FROM gm_projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    header("Location: projects.php?deleted=1");
    exit;
}

// Handle Toggle Status
if (isset($_POST['toggle_status']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("SELECT project_status FROM gm_projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $current = $stmt->fetch();
    if ($current) {
        $newStatus = ($current['project_status'] == 'published') ? 'unpublished' : 'published';
        $update = $pdo->prepare("UPDATE gm_projects SET project_status = ? WHERE project_id = ?");
        $update->execute([$newStatus, $projectId]);
        header("Location: projects.php?status_updated=1");
        exit;
    }
}

// Handle Toggle Template
if (isset($_POST['toggle_template']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("SELECT is_template FROM gm_projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $current = $stmt->fetch();
    if ($current) {
        $newTemplate = ($current['is_template'] == 1) ? 0 : 1;
        $update = $pdo->prepare("UPDATE gm_projects SET is_template = ? WHERE project_id = ?");
        $update->execute([$newTemplate, $projectId]);
        header("Location: projects.php?template_updated=1");
        exit;
    }
}

// Search & Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

$sql = "SELECT p.*, u.nickname, u.username,
               (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
        FROM gm_projects p
        LEFT JOIN gm_users u ON p.user_id = u.user_id
        WHERE p.is_deleted = 0";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.project_name LIKE ? OR u.username LIKE ? OR u.nickname LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($status)) {
    $sql .= " AND p.project_status = ?";
    $params[] = $status;
}
if ($type == 'template') {
    $sql .= " AND p.is_template = 1";
} elseif ($type == 'regular') {
    $sql .= " AND p.is_template = 0";
}

switch ($sort) {
    case 'oldest': $sql .= " ORDER BY p.created_at ASC"; break;
    case 'name_asc': $sql .= " ORDER BY p.project_name ASC"; break;
    case 'name_desc': $sql .= " ORDER BY p.project_name DESC"; break;
    default: $sql .= " ORDER BY p.created_at DESC"; break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
$totalProjects = count($projects);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<main class="admin-main">
    <!-- Sensei Top Bar -->
    <div class="sensei-top-bar">
        <div class="breadcrumbs">
            <span><i class="fas fa-layer-group"></i></span>
            <span><i class="fas fa-arrow-right" style="font-size: 0.6rem; color: #CBD5E1; margin: 0 4px;"></i></span>
            <span style="color: var(--text-muted);">Greymist</span>
            <span style="color: #CBD5E1; margin: 0 4px;">/</span>
            <span style="color: var(--accent);">Management</span>
            <span style="color: #CBD5E1; margin: 0 4px;">/</span>
            <span class="active">Projects</span>
        </div>
        
        <div class="top-actions" style="flex-wrap: wrap;">
            <div style="position: relative;">
                <input type="text" class="search-input" id="searchInput" placeholder="Search project..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 2.2rem; width: 220px;">
                <i class="fas fa-search" style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.85rem; pointer-events: none;"></i>
            </div>
            
            <select id="statusFilter" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="">All Status</option>
                <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                <option value="unpublished" <?= $status == 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
            </select>
            
            <select id="typeFilter" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="">All Types</option>
                <option value="template" <?= $type == 'template' ? 'selected' : '' ?>>Template</option>
                <option value="regular" <?= $type == 'regular' ? 'selected' : '' ?>>Regular</option>
            </select>
            
            <select id="sortSelect" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
            </select>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Project deleted!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['status_updated'])): ?>
    <div class="alert alert-success"><i class="fas fa-sync-alt"></i><span>Status updated!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['template_updated'])): ?>
    <div class="alert alert-success"><i class="fa-solid fa-diagram-project"></i><span>Template status updated!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>

    <!-- Section Header -->
    <div class="section-header">
        <div class="section-title">
            <h3>All Projects</h3>
            <p>Manage templates and user creations</p>
        </div>
        <div style="display: flex; gap: 0.8rem; align-items: center;">
            <div class="nav-badge" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);"><i class="fas fa-folder-open" style="color: var(--accent);"></i> <?= $totalProjects ?> Total</div>
            <?php if (!empty($search) || !empty($status) || !empty($type)): ?>
                <a href="projects.php" style="color: #dc2626; text-decoration: none; font-size: 0.75rem; margin-left: 0.5rem;"><i class="fas fa-times"></i> Clear filters</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="gm-project-grid">
        <?php foreach ($projects as $p): 
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
                    <button class="dropdown-item" onclick="showProjectDetail(<?= $p['project_id'] ?>)">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button class="dropdown-item" onclick="toggleStatus(<?= $p['project_id'] ?>)">
                        <i class="fas fa-sync-alt"></i> Toggle Status
                    </button>
                    <button class="dropdown-item" onclick="toggleTemplate(<?= $p['project_id'] ?>)">
                        <i class="fas fa-diagram-project"></i> Toggle Template
                    </button>
                    <div style="border-top: 1px solid var(--border-soft); margin: 0.25rem 0;"></div>
                    <button class="dropdown-item danger" onclick="showDeleteModal(<?= $p['project_id'] ?>, '<?= htmlspecialchars($p['project_name']) ?>')">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Project Detail Modal - POPUP PREVIEW -->
<div id="projectDetailModal" class="modal-glass" style="display: none; ">
    <div class="modal-glass-content" style="max-width: 900px; width: 90%; max-height: 85vh; overflow-y: auto; padding: 0;">
        <div style="background: linear-gradient(135deg, #1e293b, #334155); color: white; position: sticky; top: 0; z-index: 10; padding: 1.5rem; display:flex; align-items:center; border-radius: 24px 24px 0 0;">
            <i class="fas fa-folder-open" style="font-size: 1.5rem; margin-right: 15px;"></i>
            <h3 style="flex: 1; margin:0; text-align:left; color:white;" id="modal_project_title">Project Details</h3>
            <button id="closeProjectModal" style="background: none; border: none; color: white; font-size: 1.8rem; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-glass-body" style="padding: 1.5rem; text-align:left;">
            <div id="projectDetailContent">
                <div style="text-align: center; padding: 2rem;">
                    <div class="spinner"></div>
                    <p>Loading project data...</p>
                </div>
            </div>
        </div>
        <div class="modal-glass-footer" style="padding: 1rem; border-top: 1px solid #e9edf2; justify-content: flex-end;">
            <button type="button" class="btn-cancel" id="closeProjectFooterBtn" style="flex: 0 0 auto;">Tutup</button>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Delete Project</h3>
        <div class="modal-glass-body">
            <p>Delete <strong id="deleteProjectName"></strong>?</p>
            <p style="color:#ef4444; font-size:0.8rem;">All frames will be deleted!</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">Cancel</button>
            <button class="btn-confirm btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deleteForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="deleteProjectId"><input type="hidden" name="delete_project" value="1"></form>
<form id="statusForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="statusProjectId"><input type="hidden" name="toggle_status" value="1"></form>
<form id="templateForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="templateProjectId"><input type="hidden" name="toggle_template" value="1"></form>

<style>
    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #e2e8f0;
        border-top-color: #f59e0b;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 1rem;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .project-info-card {
        background: #f8fafc;
        border-radius: 0.8rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    .frames-list {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        margin-top: 1rem;
    }
    .frame-item {
        background: white;
        border: 1px solid #e9edf2;
        border-radius: 0.6rem;
        overflow: hidden;
    }
    .frame-header {
        background: #f1f5f9;
        padding: 0.8rem 1rem;
        font-weight: 600;
        border-bottom: 1px solid #e9edf2;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .frame-header:hover {
        background: #e9edf2;
    }
    .frame-content {
        padding: 1rem;
        font-size: 0.8rem;
        color: #475569;
        max-height: 200px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-word;
        display: none;
    }
    .frame-content.show {
        display: block;
    }
    .badge-sm {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
        border-radius: 2rem;
    }
    .info-row {
        display: flex;
        margin-bottom: 0.5rem;
    }
    .info-label {
        width: 120px;
        font-weight: 600;
        color: #64748b;
    }
    .info-value {
        flex: 1;
        color: #1e293b;
    }

    .modal-content::-webkit-scrollbar {
        display: none;
    }
</style>

<script>
// Project Detail Popup
function showProjectDetail(projectId) {
    const modal = document.getElementById('projectDetailModal');
    const contentDiv = document.getElementById('projectDetailContent');
    const titleSpan = document.getElementById('modal_project_title');
    
    modal.style.display = 'flex';
    contentDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner"></div><p>Loading project data...</p></div>';
    
    fetch(`get_project.php?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const project = data.project;
                const frames = data.frames || [];
                titleSpan.innerHTML = `<i class="fas fa-folder-open"></i> ${escapeHtml(project.project_name)}`;
                
                let framesHtml = '';
                frames.forEach((frame, index) => {
                    framesHtml += `
                        <div class="frame-item">
                            <div class="frame-header" onclick="toggleFrame(${index})">
                                <span><i class="fas fa-file-alt"></i> ${escapeHtml(frame.frame_name || 'Untitled Frame')}</span>
                                <i class="fas fa-chevron-down" id="frame_icon_${index}"></i>
                            </div>
                            <div class="frame-content" id="frame_content_${index}">
                                <pre style="white-space: pre-wrap; font-family: monospace; font-size: 0.75rem; margin: 0;">${escapeHtml(frame.core_content || 'Empty content')}</pre>
                            </div>
                        </div>
                    `;
                });
                
                const statusBadge = project.project_status == 'published' 
                    ? '<span class="badge badge-success"><i class="fas fa-globe"></i> Published</span>' 
                    : '<span class="badge badge-danger"><i class="fas fa-lock"></i> Private</span>';
                
                const typeBadge = project.is_template 
                    ? '<span class="badge badge-warning"><i class="fa-solid fa-diagram-project"></i> Template</span>'
                    : '<span class="badge">Regular Project</span>';
                
                const thumbnailHtml = project.thumbnail 
                    ? `<img src="data:image/png;base64,${project.thumbnail}" style="max-width: 100%; max-height: 200px; border-radius: 0.5rem;">`
                    : '<div style="width: 100%; height: 150px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem;"><i class="fas fa-image" style="font-size: 3rem; color: #cbd5e1;"></i></div>';
                
                contentDiv.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem;">
                        <!-- LEFT COLUMN - Thumbnail & Info -->
                        <div>
                            <div class="project-info-card" style="text-align: center;">
                                <h4 style="margin-bottom: 0.8rem;"><i class="fas fa-image"></i> Thumbnail</h4>
                                ${thumbnailHtml}
                            </div>
                            <div class="project-info-card">
                                <h4 style="margin-bottom: 0.8rem;"><i class="fas fa-info-circle"></i> Project Info</h4>
                                <div class="info-row"><div class="info-label">Project ID:</div><div class="info-value">#${project.project_id}</div></div>
                                <div class="info-row"><div class="info-label">Owner:</div><div class="info-value">${escapeHtml(project.owner_name || 'Unknown')}</div></div>
                                <div class="info-row"><div class="info-label">Status:</div><div class="info-value">${statusBadge}</div></div>
                                <div class="info-row"><div class="info-label">Type:</div><div class="info-value">${typeBadge}</div></div>
                                <div class="info-row"><div class="info-label">Created:</div><div class="info-value">${project.created_at}</div></div>
                                <div class="info-row"><div class="info-label">Total Frames:</div><div class="info-value">${frames.length} frames</div></div>
                            </div>
                        </div>
                        
                        <!-- RIGHT COLUMN - Frames List -->
                        <div>
                            <div class="project-info-card">
                                <h4 style="margin-bottom: 0.8rem;"><i class="fas fa-layer-group"></i> Frames (${frames.length})</h4>
                                ${frames.length > 0 ? framesHtml : '<p style="text-align: center; color: #64748b;">No frames found in this project</p>'}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 3rem;"></i><p>${data.error || 'Failed to load project'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 3rem;"></i><p>Failed to load project data</p></div>`;
        });
}

function toggleFrame(index) {
    const content = document.getElementById(`frame_content_${index}`);
    const icon = document.getElementById(`frame_icon_${index}`);
    if (content.classList.contains('show')) {
        content.classList.remove('show');
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        content.classList.add('show');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

// Close Modal
document.getElementById('closeProjectModal')?.addEventListener('click', () => document.getElementById('projectDetailModal').style.display = 'none');
document.getElementById('closeProjectFooterBtn')?.addEventListener('click', () => document.getElementById('projectDetailModal').style.display = 'none');

// Delete Modal
let currentDeleteId = null;
function showDeleteModal(id, name) {
    currentDeleteId = id;
    document.getElementById('deleteProjectName').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
    document.getElementById('deleteProjectId').value = currentDeleteId;
    document.getElementById('deleteForm').submit();
});
document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => document.getElementById('deleteModal').style.display = 'none');

// Toggle Status & Template
function toggleStatus(id) {
    document.getElementById('statusProjectId').value = id;
    document.getElementById('statusForm').submit();
}
function toggleTemplate(id) {
    document.getElementById('templateProjectId').value = id;
    document.getElementById('templateForm').submit();
}

// Search & Filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const typeFilter = document.getElementById('typeFilter');
const sortSelect = document.getElementById('sortSelect');
let timeout;
function applyFilters() {
    window.location.href = `projects.php?search=${encodeURIComponent(searchInput.value)}&status=${statusFilter.value}&type=${typeFilter.value}&sort=${sortSelect.value}`;
}
searchInput?.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(applyFilters, 500); });
statusFilter?.addEventListener('change', applyFilters);
typeFilter?.addEventListener('change', applyFilters);
sortSelect?.addEventListener('change', applyFilters);

// Close alerts
document.querySelectorAll('.close-alert').forEach(btn => btn.addEventListener('click', function() { this.closest('.alert').remove(); }));
setTimeout(() => document.querySelectorAll('.alert').forEach(alert => alert.remove()), 3000);

// Close modal when clicking outside
window.onclick = function(e) {
    if (e.target.classList.contains('modal')) e.target.style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>