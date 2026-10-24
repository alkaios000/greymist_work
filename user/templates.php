<?php
// Mulai output buffering
ob_start();

require '../includes/db.php';
require '../includes/header.php';

$user_id = $_SESSION['user_id'] ?? null;

// Handle clone template dari halaman templates
if (isset($_POST['clone_template']) && isset($_POST['template_id']) && $user_id) {
    $templateId = $_POST['template_id'];

    // Ambil data template asli (project dengan is_template = 1)
    $getTemplate = $pdo->prepare('
        SELECT project_name, thumbnail, project_status 
        FROM GM_PROJECTS 
        WHERE project_id = ? AND is_template = 1
    ');
    $getTemplate->execute([$templateId]);
    $originalTemplate = $getTemplate->fetch();

    if ($originalTemplate) {
        // Buat project baru dari template
        $newProjectName = 'Copy of ' . $originalTemplate['project_name'];
        $insertProject = $pdo->prepare("
            INSERT INTO GM_PROJECTS (user_id, project_name, thumbnail, project_status, is_template)
            VALUES (?, ?, ?, 'unpublished', 0)
        ");
        $insertProject->execute([$user_id, $newProjectName, $originalTemplate['thumbnail']]);
        $newProjectId = $pdo->lastInsertId();

        // Clone semua frames dari template
        $getFrames = $pdo->prepare('SELECT frame_name, core_content FROM GM_FRAMES WHERE project_id = ?');
        $getFrames->execute([$templateId]);
        $frames = $getFrames->fetchAll();

        $insertFrame = $pdo->prepare('
            INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
            VALUES (?, ?, ?)
        ');

        foreach ($frames as $frame) {
            $insertFrame->execute([$newProjectId, $frame['frame_name'], $frame['core_content']]);
        }

        ob_end_clean();
        header("Location: ../index.php?project_id=$newProjectId&cloned=1");
        exit;
    }
}

// Search dan Sort
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Ambil semua template (project dengan is_template = 1)
$sql = '
    SELECT p.project_id, p.project_name, p.thumbnail, p.created_at, p.user_id, p.project_status,
           u.username as owner_name,
           (SELECT COUNT(*) FROM GM_FRAMES WHERE project_id = p.project_id) as frame_count,
           (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
    FROM GM_PROJECTS p
    LEFT JOIN GM_USERS u ON p.user_id = u.user_id
    WHERE p.is_template = 1
';

if (!empty($search)) {
    $sql .= ' AND p.project_name LIKE ?';
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
if (!empty($search)) {
    $stmt->execute(["%$search%"]);
} else {
    $stmt->execute();
}
$templates = $stmt->fetchAll();

$totalTemplates = count($templates);
$myTemplates = count(array_filter($templates, fn($t) => $t['user_id'] == $user_id));
?>

<main class="gm-project-page">

    <!-- Dribbble Style Search/Filter Control Bar -->
    <div class="dash-control-bar">
        <div class="control-wrapper">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Eksplorasi template (misal: Landing Page)..." class="search-input" value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                <button class="clear-search" id="clearSearch" style="display: <?= $search ? 'block' : 'none' ?>;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="sort-box">
                <span class="sort-label"><i class="fas fa-fire"></i> Terpopuler:</span>
                <select id="sortSelect" class="elegant-select">
                    <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Terbaru Rilis</option>
                    <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Legendaris</option>
                    <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Kategori (A-Z)</option>
                    <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Kategori (Z-A)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="gm-project-grid" id="templatesGrid">
        <?php if (count($templates) > 0): ?>
            <?php
            foreach ($templates as $index => $t):
                $thumbnail = !empty($t['thumbnail']) ? '../' . $t['thumbnail'] : '../assets/img/dummy_thumbnail.png';
                $isOwner = ($user_id && $t['user_id'] == $user_id);
                ?>
                <!-- Elegant UI Card -->
                <div class="gm-project-card template-card-clickable" data-id="<?= $t['project_id'] ?>" data-name="<?= htmlspecialchars($t['project_name']) ?>" style="animation-delay: <?= ($index % 12) * 0.05 ?>s" ondblclick="document.querySelector('.preview-btn-action[data-id=\'<?= $t['project_id'] ?>\']').click()">
                    
                    <form method="post" id="clone-form-<?= $t['project_id'] ?>">
                        <input type="hidden" name="template_id" value="<?= $t['project_id'] ?>">
                        <input type="hidden" name="clone_template" value="1">
                    </form>

                    <!-- Card Image Area -->
                    <div class="card-image-wrapper">
                        <!-- Modern Loader -->
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
                        
                        <!-- Double click support instead of hover button -->
                        <div class="card-glass-hover" style="display:none;" ondblclick="document.querySelector('.preview-btn-action[data-id=\'<?= $t['project_id'] ?>\']').click()">
                            <!-- Removed visual hover button, rely on dropdown -->
                        </div>
                    </div>

                    <!-- Card Details Area -->
                    <div class="card-bottom">
                        <div class="card-titles">
                            <h3 class="card-title"><?= htmlspecialchars($t['project_name']) ?></h3>
                            <div class="card-meta">
                                <span class="meta-creator"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($t['owner_name'] ?? 'Guest') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="meta-date">
                        <i class="far fa-clock"></i> <?= date('d M Y', strtotime($t['created_at'])) ?>
                    </div>

                    <?php if ($isOwner): ?>
                        <div class="badge badge-owner">
                            <i class="fas fa-star"></i> Milik Anda
                        </div>
                    <?php endif; ?>

                    <div class="badge badge-template">
                        <i class="fa-solid fa-diagram-project"></i>
                    </div>

                    <!-- Dots Menu Context -->
                    <div class="card-menu">
                        <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="card-dropdown">
                            <button type="button" class="dropdown-item preview-btn-action" data-id="<?= $t['project_id'] ?>" data-name="<?= htmlspecialchars($t['project_name']) ?>">
                                <i class="fas fa-eye"></i> Preview Template
                            </button>
                            <?php if ($user_id): ?>
                                <button type="button" class="dropdown-item clone-btn-action" data-id="<?= $t['project_id'] ?>" data-name="<?= htmlspecialchars($t['project_name']) ?>" onclick="openCloneModal(this.getAttribute('data-id'), this.getAttribute('data-name'))">
                                    <i class="fas fa-copy"></i> Clone ke Workspace
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Elegant Empty State -->
            <div class="empty-state" style="grid-column: 1 / -1;">
                <div class="empty-icon"><i class="fas fa-drafting-compass"></i></div>
                <h3>Dunia Template Masih Sunyi</h3>
                <p>Belum ada satu-pun kreator yang merilis karyanya secara publik.</p>
                <div style="margin-top:2rem;">
                    <button onclick="window.location.href='project.php'" class="btn-premium">
                        <i class="fas fa-plus"></i> Menjadi Kreator Pertama
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Glass Modals System -->
<div id="previewModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-muted" style="background:#1e293b; color:white;"><i class="fas fa-eye"></i></div>
        <h3>Preview Template</h3>
        <div class="modal-glass-body">
            <!-- Loading State -->
            <div id="previewLoading" style="padding:1.5rem 0;">
                <div class="pulse-spinner"></div>
                <p style="margin-top:1rem;">Menghubungkan ke asset server...</p>
            </div>
            
            <!-- Result Data -->
            <div id="previewContent" style="display: none; text-align:left;">
                <h4 id="previewTitle" style="font-family: var(--font-heading); font-size:1.2rem; color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;"></h4>
                
                <div style="display:flex; gap:15px; margin-bottom:15px; font-size:0.85rem; color:#64748b;">
                    <span id="previewFrames"></span> 
                    <span id="previewOwner"></span>
                </div>
                
                <!-- Mock Frames Render -->
                <div id="previewFramesList" style="display:flex; flex-direction:column; gap:8px;"></div>
            </div>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="closePreviewBtn">Tutup</button>
            <?php if ($user_id): ?>
                <button class="btn-confirm" id="cloneFromPreviewBtn" style="background:#0f172a;">Gunakan Template</button>
            <?php else: ?>
                <button onclick="window.location.href='../session/login.php'" class="btn-confirm" style="background:#22c55e;">Login to Clone</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="cloneModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-success"><i class="fas fa-copy"></i></div>
        <h3>Clone & Modifikasi</h3>
        <div class="modal-glass-body">
            <p>Duplicate template <strong><span id="cloneTemplateName"></span></strong>?</p>
            <p>It will be saved as a private project in your workspace.</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelCloneBtn">Cancel</button>
            <button class="btn-confirm" id="confirmCloneBtn">Clone Project</button>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   GITHUB STYLE UI - TEMPLATES
   ========================================================================== */

:root {
    --bg-main: #f8f9fa;
    --text-main: #333333;
    --text-muted: #6c757d;
    --border-soft: rgba(0, 0, 0, 0.08);
    --shadow-soft: 0 4px 15px rgba(0, 0, 0, 0.02);
    --font-heading: 'Poppins', sans-serif;
    --accent: #0d6efd;
    --link: #0d6efd;
}

body { background: var(--bg-main); color: var(--text-main); font-family: var(--font-heading); }

.gm-project-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    min-height: 100vh;
}

/* --- HEADER SECTION --- */
.dash-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-soft);
}

.dash-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-main);
}

.dash-subtitle {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 12px;
}

.dash-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--text-muted);
}

.meta-item i {
    margin-right: 4px;
}

/* --- CONTROL BAR --- */
.dash-control-bar { margin-bottom: 24px; }
.control-wrapper {
    display: flex; align-items: center; justify-content: space-between; gap: 16px; background: transparent; flex-wrap: wrap;
}

.search-box { position: relative; flex: 1; max-width: 400px; }
.search-icon { position: absolute; left: 8px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 14px; }
#searchInput {
    width: 100%; padding: 5px 30px 5px 28px; border-radius: 6px; border: 1px solid var(--border-soft); background: #f6f8fa; font-size: 14px; outline: none; color: var(--text-main);
}
#searchInput:focus { background: white; border-color: var(--link); box-shadow: inset 0 0 0 1px var(--link); }

.clear-search { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0; font-size: 14px; }

.sort-box { display: flex; align-items: center; gap: 8px; }
.sort-label { font-size: 14px; font-weight: 500; color: var(--text-main); }
.elegant-select {
    appearance: none; background: #f6f8fa; border: 1px solid var(--border-soft); padding: 5px 28px 5px 12px; border-radius: 6px; font-size: 14px; font-weight: 500; color: var(--text-main); cursor: pointer; outline: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2324292f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 8px center; box-shadow: var(--shadow-soft);
}
.elegant-select:hover { background: #ffffff; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.05); }

/* --- CARDS GRID (CSS GRID) --- */
/* Global .gm-project-card CSS is handled in header.php */

/* --- EMPTY --- */
.empty-state { text-align: center; padding: 40px 20px; background: white; border-radius: 6px; border: 1px solid var(--border-soft); }
.empty-icon { font-size: 24px; color: var(--text-muted); margin-bottom: 16px; }
.empty-state h3 { font-size: 16px; margin-bottom: 8px; color: var(--text-main);}
.empty-state p { color: var(--text-muted); font-size: 14px; }

/* CSS Modal now handled in header.php */

/* SPINNER */
.pulse-spinner {
    width: 24px; height: 24px; margin: 0 auto;
    border: 2px solid #e2e8f0; border-top-color: var(--link); border-radius: 50%;
    animation: spinner-rot 1s linear infinite;
}
@keyframes spinner-rot { to { transform: rotate(360deg); } }

.preview-frame-item {
    background: #ffffff; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight:500; color: var(--text-main); display:flex; align-items:center; gap:8px; border: 1px solid var(--border-soft);
}
.preview-frame-item i { color: var(--text-muted); }

/* --- RESPONSIVENSESS --- */
@media (max-width: 1024px) { .gm-project-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .dash-header { padding: 16px; margin-bottom: 16px; }
    .dash-title { font-size: 20px; }
    .control-wrapper { flex-direction: column; align-items: stretch; }
    .sort-box { justify-content: space-between; }
    .gm-project-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== SEARCH & SORT ==========
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const sortSelect = document.getElementById('sortSelect');
    let searchTimeout;

    function performSearchAndSort() {
        const searchTerm = searchInput.value.trim();
        const sortValue = sortSelect.value;
        window.location.href = `templates.php?search=${encodeURIComponent(searchTerm)}&sort=${sortValue}`;
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearchAndSort, 500);
            if (clearSearch) clearSearch.style.display = this.value ? 'block' : 'none';
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            performSearchAndSort();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', performSearchAndSort);
    }

    // ========== PREVIEW MODAL LOGIC ==========
    const previewModal = document.getElementById('previewModal');
    const closePreviewBtn = document.getElementById('closePreviewBtn');
    let currentPreviewId = null;
    let currentPreviewName = null;
    
    document.querySelectorAll('.preview-btn-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const tid = this.getAttribute('data-id');
            const tname = this.getAttribute('data-name');
            currentPreviewId = tid;
            currentPreviewName = tname;

            document.getElementById('previewLoading').style.display = 'block';
            document.getElementById('previewContent').style.display = 'none';
            document.getElementById('previewTitle').textContent = tname;
            previewModal.style.display = 'flex';

            // Simulate AJAX fetch rendering
            setTimeout(() => {
                document.getElementById('previewLoading').style.display = 'none';
                document.getElementById('previewContent').style.display = 'block';
                document.getElementById('previewFrames').innerHTML = '<i class="fas fa-layer-group"></i> Default 3 Frames';
                document.getElementById('previewOwner').innerHTML = '<i class="fas fa-certificate"></i> Verified Structural';
                
                document.getElementById('previewFramesList').innerHTML = `
                    <div class="preview-frame-item"><i class="fas fa-file-code"></i> Main App UI</div>
                    <div class="preview-frame-item"><i class="fas fa-columns"></i> Component Layouts</div>
                    <div class="preview-frame-item"><i class="fas fa-scroll"></i> Documentation Notes</div>
                `;
            }, 600);
        });
    });

    closePreviewBtn?.addEventListener('click', () => previewModal.style.display = 'none');
    previewModal?.addEventListener('click', (e) => { if(e.target === previewModal) previewModal.style.display = 'none'; });

    // Link Preview's "Gunakan Template" directly to open Clone Confirmation Modal
    document.getElementById('cloneFromPreviewBtn')?.addEventListener('click', () => {
        previewModal.style.display = 'none';
        openCloneModal(currentPreviewId, currentPreviewName);
    });

    // ========== CLONE CONFIRMATION LOGIC ==========
    const cloneModal = document.getElementById('cloneModal');
    let targetCloneId = null;

    function openCloneModal(id, name) {
        targetCloneId = id;
        document.getElementById('cloneTemplateName').textContent = name;
        cloneModal.style.display = 'flex';
    }

    // Bind full card click to Clone (like Dribbble shot click expands or clones)
    document.querySelectorAll('.template-card-clickable').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.closest('.preview-btn-action')) return;
            const tid = this.getAttribute('data-id');
            const tname = this.getAttribute('data-name');
            openCloneModal(tid, tname);
        });
    });

    document.getElementById('cancelCloneBtn')?.addEventListener('click', () => cloneModal.style.display = 'none');
    document.getElementById('confirmCloneBtn')?.addEventListener('click', () => {
        if (targetCloneId) document.getElementById('clone-form-' + targetCloneId).submit();
    });
    cloneModal?.addEventListener('click', (e) => { if (e.target === cloneModal) cloneModal.style.display = 'none'; });

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
    setTimeout(applyThumbnailLogic, 100);
</script>

<?php
require '../includes/footer.php';
if (ob_get_level())
    ob_end_flush();
?>