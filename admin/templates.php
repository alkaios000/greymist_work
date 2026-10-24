<?php
require 'includes/auth.php';
require_once '../includes/db.php';

$page_title = 'Manage Templates';

// Handle Delete Template
if (isset($_POST['delete_template']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("DELETE FROM gm_frames WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $stmt = $pdo->prepare("DELETE FROM gm_projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    header("Location: templates.php?deleted=1");
    exit;
}

// Handle Remove from Template (set is_template = 0)
if (isset($_POST['remove_template']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $update = $pdo->prepare("UPDATE gm_projects SET is_template = 0 WHERE project_id = ?");
    $update->execute([$projectId]);
    header("Location: templates.php?removed=1");
    exit;
}

// Handle Toggle Status (Publish/Unpublish)
if (isset($_POST['toggle_status']) && isset($_POST['project_id'])) {
    $projectId = (int)$_POST['project_id'];
    $stmt = $pdo->prepare("SELECT project_status FROM gm_projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $current = $stmt->fetch();
    if ($current) {
        $newStatus = ($current['project_status'] == 'published') ? 'unpublished' : 'published';
        $update = $pdo->prepare("UPDATE gm_projects SET project_status = ? WHERE project_id = ?");
        $update->execute([$newStatus, $projectId]);
        header("Location: templates.php?status_updated=1");
        exit;
    }
}

// Search & Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

$sql = "SELECT p.*, u.nickname, u.username,
        (SELECT COUNT(*) FROM gm_frames WHERE project_id = p.project_id) as total_frames,
        (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
        FROM gm_projects p
        LEFT JOIN gm_users u ON p.user_id = u.user_id
        WHERE p.is_template = 1";
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

switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY p.created_at ASC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.project_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.project_name DESC";
        break;
    case 'most_frames':
        $sql .= " ORDER BY total_frames DESC";
        break;
    case 'latest':
    default:
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$templates = $stmt->fetchAll();
$totalTemplates = count($templates);

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
            <span class="active">Templates</span>
        </div>
        
        <div class="top-actions" style="flex-wrap: wrap;">
            <div style="position: relative;">
                <input type="text" class="search-input" id="searchInput" placeholder="Search template..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 2.2rem; width: 220px;">
                <i class="fas fa-search" style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.85rem; pointer-events: none;"></i>
            </div>
            
            <select id="statusFilter" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="">All Status</option>
                <option value="published" <?= $status == 'published' ? 'selected' : '' ?>>Published</option>
                <option value="unpublished" <?= $status == 'unpublished' ? 'selected' : '' ?>>Unpublished</option>
            </select>
            
            <select id="sortSelect" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
                <option value="most_frames" <?= $sort == 'most_frames' ? 'selected' : '' ?>>Most Frames</option>
            </select>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Template berhasil dihapus!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['removed'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Template berhasil dihapus dari daftar template!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['status_updated'])): ?>
    <div class="alert alert-success"><i class="fas fa-sync-alt"></i><span>Status template berhasil diperbarui!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>

    <!-- Section Header -->
    <div class="section-header">
        <div class="section-title">
            <h3>Pro Templates</h3>
            <p>Manage community templates and published works</p>
        </div>
        <div style="display: flex; gap: 0.8rem; align-items: center;">
            <div class="nav-badge" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);"><i class="fa-solid fa-diagram-project" style="color: var(--accent);"></i> <?= $totalTemplates ?> Total</div>
            <?php if (!empty($search) || !empty($status)): ?>
                <a href="templates.php" style="color: #dc2626; text-decoration: none; font-size: 0.75rem; margin-left: 0.5rem;"><i class="fas fa-times"></i> Clear filters</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Templates Grid -->
    <?php if (count($templates) > 0): ?>
    <div class="gm-project-grid">
        <?php foreach ($templates as $t): 
            $thumbnail = !empty($t['thumbnail']) ? '../' . $t['thumbnail'] : '../assets/img/dummy_thumbnail.png';
        ?>
        <div class="gm-project-card">
            <div class="card-image-wrapper">

                
                <div class="thumbnail-loader">
                    <div class="thumbnail-spinner"></div>
                </div>
                
                <?php
                if (!empty($t['first_frame_content'])):
                    $content = str_replace('assets/', '../assets/', $t['first_frame_content']);
                    $content = str_replace('url("assets/', 'url("../assets/', $content);
                    $content = str_replace("url('assets/", "url('../assets/", $content);
                    ?>
                    <div class="live-thumbnail-wrapper lazy-live-thumbnail" data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="mini-preview gm-object-core"></div>
                    </div>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($thumbnail) ?>" 
                         alt="<?= htmlspecialchars($t['project_name']) ?>"
                         loading="lazy"
                         onload="this.parentElement.querySelector('.thumbnail-loader').style.opacity='0'"
                         onerror="this.src='../assets/img/dummy_thumbnail.png'">
                <?php endif; ?>
            </div>
            
            <div class="card-bottom">
                <div class="card-titles">
                    <h3 class="card-title"><?= htmlspecialchars($t['project_name']) ?></h3>
                    <div class="card-meta">
                        <span class="meta-creator"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($t['nickname'] ?? $t['username'] ?? 'Unknown') ?></span>
                    </div>
                </div>
            </div>
            
            <div class="meta-date">
                <i class="far fa-clock"></i> <?= date('d M Y', strtotime($t['created_at'])) ?>
            </div>
            
            <?php if ($t['project_status'] == 'published'): ?>
                <div class="badge badge-owner">
                    <i class="fas fa-globe"></i> Public
                </div>
            <?php else: ?>
                <div class="badge badge-muted">
                    <i class="fas fa-lock"></i> Private
                </div>
            <?php endif; ?>

            <div class="badge badge-template"><i class="fa-solid fa-diagram-project"></i></div>

            <div class="card-menu">
                <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="card-dropdown">
                    <button class="dropdown-item" onclick="showTemplateDetail(<?= $t['project_id'] ?>)">
                        <i class="fas fa-eye"></i> View Details
                    </button>
                    <button class="dropdown-item" onclick="toggleStatus(<?= $t['project_id'] ?>)">
                        <i class="fas fa-globe"></i> Toggle Status
                    </button>
                    <button class="dropdown-item" onclick="showRemoveModal(<?= $t['project_id'] ?>, '<?= htmlspecialchars($t['project_name']) ?>')">
                        <i class="fas fa-times-circle"></i> Remove from Templates
                    </button>
                    <div style="border-top: 1px solid var(--border-soft); margin: 0.25rem 0;"></div>
                    <button class="dropdown-item danger" onclick="showDeleteModal(<?= $t['project_id'] ?>, '<?= htmlspecialchars($t['project_name']) ?>')">
                        <i class="fas fa-trash-alt"></i> Delete Template
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align: center; padding: 4rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); margin-top: 1.5rem;">
        <i class="fa-solid fa-diagram-project" style="font-size: 3.5rem; color: #E2E8F0; margin-bottom: 1rem; display: block;"></i>
        <h3 style="font-size: 1.2rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.5rem;">Belum ada template</h3>
        <p style="font-size: 0.9rem; color: var(--text-muted);">Project yang dijadikan template akan muncul di sini.</p>
    </div>
    <?php endif; ?>
</main>

<!-- Template Detail Modal - POPUP PREVIEW -->
<!-- Template Detail Modal - POPUP PREVIEW -->
<div id="templateDetailModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content" style="max-width: 900px; width: 90%; max-height: 85vh; overflow-y: auto; padding: 0;">
        <div style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white; position: sticky; top: 0; z-index: 10; padding: 1.5rem; display:flex; align-items:center; border-radius: 24px 24px 0 0;">
            <i class="fa-solid fa-diagram-project" style="font-size: 1.5rem; margin-right: 15px;"></i>
            <h3 style="flex: 1; margin:0; text-align:left; color:white;" id="modal_template_title">Template Details</h3>
            <button id="closeTemplateModal" style="background: none; border: none; color: white; font-size: 1.8rem; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-glass-body" style="padding: 1.5rem; text-align:left;">
            <div id="templateDetailContent">
                <div style="text-align: center; padding: 2rem;">
                    <div class="spinner"></div>
                    <p>Loading template data...</p>
                </div>
            </div>
        </div>
        <div class="modal-glass-footer" style="padding: 1rem; border-top: 1px solid #e9edf2; justify-content: flex-end;">
            <button type="button" class="btn-cancel" id="closeTemplateFooterBtn" style="flex: 0 0 auto;">Tutup</button>
        </div>
    </div>
</div>

<!-- Remove from Template Modal -->
<!-- Remove from Template Modal -->
<div id="removeModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-warning"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Hapus dari Template</h3>
        <div class="modal-glass-body">
            <p>Apakah Anda yakin ingin menghapus template <strong id="removeTemplateName"></strong> dari daftar template?</p>
            <p>Project akan tetap ada tetapi tidak akan muncul di halaman templates.</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelRemoveBtn">Batal</button>
            <button class="btn-confirm" id="confirmRemoveBtn" style="background: #f59e0b;">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<!-- Delete Modal -->
<div id="deleteModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <h3>Hapus Template</h3>
        <div class="modal-glass-body">
            <p>Hapus template <strong id="deleteTemplateName"></strong>?</p>
            <p style="color:#ef4444; font-size:0.8rem;">Tindakan ini akan menghapus template beserta semua frames secara permanen!</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">Batal</button>
            <button class="btn-confirm btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deleteForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="deleteProjectId"><input type="hidden" name="delete_template" value="1"></form>
<form id="removeForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="removeProjectId"><input type="hidden" name="remove_template" value="1"></form>
<form id="statusForm" method="post" style="display: none;"><input type="hidden" name="project_id" id="statusProjectId"><input type="hidden" name="toggle_status" value="1"></form>

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
    .template-info-card {
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
    .template-badge-large {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #b45309;
        padding: 0.3rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
</style>

<script>
// Template Detail Popup
function showTemplateDetail(templateId) {
    const modal = document.getElementById('templateDetailModal');
    const contentDiv = document.getElementById('templateDetailContent');
    const titleSpan = document.getElementById('modal_template_title');
    
    modal.style.display = 'flex';
    contentDiv.innerHTML = '<div style="text-align: center; padding: 2rem;"><div class="spinner"></div><p>Loading template data...</p></div>';
    
    fetch(`get_project.php?id=${templateId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const template = data.project;
                const frames = data.frames || [];
                titleSpan.innerHTML = `<i class="fa-solid fa-diagram-project"></i> ${escapeHtml(template.project_name)}`;
                
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
                
                const statusBadge = template.project_status == 'published' 
                    ? '<span class="badge badge-success"><i class="fas fa-globe"></i> Published</span>' 
                    : '<span class="badge badge-danger"><i class="fas fa-lock"></i> Private</span>';
                
                const thumbnailHtml = template.thumbnail 
                    ? `<img src="data:image/png;base64,${template.thumbnail}" style="max-width: 100%; max-height: 200px; border-radius: 0.5rem;">`
                    : '<div style="width: 100%; height: 150px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem;"><i class="fas fa-image" style="font-size: 3rem; color: #cbd5e1;"></i></div>';
                
                contentDiv.innerHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 1.5rem;">
                        <!-- LEFT COLUMN -->
                        <div>
                            <div class="template-info-card" style="text-align: center;">
                                <h4 style="margin-bottom: 0.8rem;"><i class="fas fa-image"></i> Thumbnail</h4>
                                ${thumbnailHtml}
                            </div>
                            <div class="template-info-card">
                                <h4 style="margin-bottom: 0.8rem;"><i class="fas fa-info-circle"></i> Template Info</h4>
                                <div class="info-row"><div class="info-label">Template ID:</div><div class="info-value">#${template.project_id}</div></div>
                                <div class="info-row"><div class="info-label">Owner:</div><div class="info-value">${escapeHtml(template.owner_name || 'Unknown')}</div></div>
                                <div class="info-row"><div class="info-label">Status:</div><div class="info-value">${statusBadge}</div></div>
                                <div class="info-row"><div class="info-label">Created:</div><div class="info-value">${template.created_at}</div></div>
                                <div class="info-row"><div class="info-label">Total Frames:</div><div class="info-value">${frames.length} frames</div></div>
                            </div>
                        </div>
                        
                        <!-- RIGHT COLUMN -->
                        <div>
                            <div class="template-info-card">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
                                    <h4><i class="fas fa-layer-group"></i> Frames (${frames.length})</h4>
                                    <span class="template-badge-large"><i class="fa-solid fa-diagram-project"></i> Template</span>
                                </div>
                                ${frames.length > 0 ? framesHtml : '<p style="text-align: center; color: #64748b;">No frames found in this template</p>'}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                contentDiv.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 3rem;"></i><p>${data.error || 'Failed to load template'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            contentDiv.innerHTML = `<div style="text-align: center; padding: 2rem; color: #dc2626;"><i class="fas fa-exclamation-circle" style="font-size: 3rem;"></i><p>Failed to load template data</p></div>`;
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

// Close Template Modal
document.getElementById('closeTemplateModal')?.addEventListener('click', () => document.getElementById('templateDetailModal').style.display = 'none');
document.getElementById('closeTemplateFooterBtn')?.addEventListener('click', () => document.getElementById('templateDetailModal').style.display = 'none');

// Remove from Template Modal
let currentRemoveId = null;
function showRemoveModal(id, name) {
    currentRemoveId = id;
    document.getElementById('removeTemplateName').textContent = name;
    document.getElementById('removeModal').style.display = 'flex';
}
document.getElementById('confirmRemoveBtn')?.addEventListener('click', () => {
    document.getElementById('removeProjectId').value = currentRemoveId;
    document.getElementById('removeForm').submit();
});
document.getElementById('cancelRemoveBtn')?.addEventListener('click', () => document.getElementById('removeModal').style.display = 'none');

// Delete Modal
let currentDeleteId = null;
function showDeleteModal(id, name) {
    currentDeleteId = id;
    document.getElementById('deleteTemplateName').textContent = name;
    document.getElementById('deleteModal').style.display = 'flex';
}
document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => {
    document.getElementById('deleteProjectId').value = currentDeleteId;
    document.getElementById('deleteForm').submit();
});
document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => document.getElementById('deleteModal').style.display = 'none');

// Toggle Status
function toggleStatus(id) {
    document.getElementById('statusProjectId').value = id;
    document.getElementById('statusForm').submit();
}

// Search & Filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const sortSelect = document.getElementById('sortSelect');
let timeout;
function applyFilters() {
    window.location.href = `templates.php?search=${encodeURIComponent(searchInput.value)}&status=${statusFilter.value}&sort=${sortSelect.value}`;
}
searchInput?.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(applyFilters, 500); });
statusFilter?.addEventListener('change', applyFilters);
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