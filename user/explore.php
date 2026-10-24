<?php
// Mulai output buffering
ob_start();
require '../includes/db.php';
require '../includes/header.php';

// Pastikan user login untuk clone
$user_id = $_SESSION['user_id'] ?? null;

// Handle clone project dari dashboard
if (isset($_POST['clone_from_dashboard']) && isset($_POST['project_id']) && $user_id) {
    $originalProjectId = $_POST['project_id'];
    
    // Ambil data project asli (hanya yang published)
    $getProject = $pdo->prepare("SELECT project_name, thumbnail FROM GM_PROJECTS WHERE project_id = ? AND project_status = 'published'");
    $getProject->execute([$originalProjectId]);
    $originalProject = $getProject->fetch();
    
    if ($originalProject) {
        // Buat project baru dengan nama "Copy of ..." dan status unpublished
        $newProjectName = "Copy of " . $originalProject['project_name'];
        $insertProject = $pdo->prepare("
            INSERT INTO GM_PROJECTS (user_id, project_name, thumbnail, project_status)
            VALUES (?, ?, ?, 'unpublished')
        ");
        $insertProject->execute([$user_id, $newProjectName, $originalProject['thumbnail']]);
        $newProjectId = $pdo->lastInsertId();
        
        // Clone semua frames dari project asli
        $getFrames = $pdo->prepare("SELECT frame_name, core_content FROM GM_FRAMES WHERE project_id = ?");
        $getFrames->execute([$originalProjectId]);
        $frames = $getFrames->fetchAll();
        
        $insertFrame = $pdo->prepare("
            INSERT INTO GM_FRAMES (project_id, frame_name, core_content)
            VALUES (?, ?, ?)
        ");
        
        foreach ($frames as $frame) {
            $insertFrame->execute([$newProjectId, $frame['frame_name'], $frame['core_content']]);
        }
        
        ob_end_clean();
        header("Location: ../index.php?project_id=$newProjectId&cloned=1");
        exit;
    }
}

// Ambil project publik yang sudah dipublish (published)
$stmt = $pdo->query("
    SELECT p.project_id, p.project_name, p.thumbnail, p.created_at, p.user_id,
           u.username as owner_name,
           (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
    FROM GM_PROJECTS p
    LEFT JOIN GM_USERS u ON p.user_id = u.user_id
    WHERE p.project_status = 'published' AND p.is_deleted = 0
    ORDER BY p.created_at DESC
    LIMIT 24
");
$projects = $stmt->fetchAll();

// Hitung statistik hanya untuk project yang published
$totalProjects = $pdo->query("SELECT COUNT(*) FROM GM_PROJECTS WHERE project_status = 'published' AND is_deleted = 0")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM GM_PROJECTS WHERE project_status = 'published' AND is_deleted = 0")->fetchColumn();
?>

<main class="gm-dashboard">

    <!-- Page Title -->
    <div class="dash-header" style="text-align: center; border-bottom: none; margin-bottom: 0;">
        <h1 class="dash-title" style="font-size: 2rem; background: linear-gradient(135deg, #0d6efd, #00d4ff); -webkit-background-clip: text; color: transparent;">Explore Inspirasi</h1>
        <p class="dash-subtitle" style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 1.5rem;">Temukan ribuan karya kreatif dari komunitas Greymist. Jadikan referensi untuk mahakarya Anda selanjutnya!</p>
    </div>

    <!-- Elegant Search & Filter Control Bar -->
    <div class="dash-control-bar">
        <div class="control-wrapper">
            
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" placeholder="Cari karya inspiratif..." autocomplete="off">
                <button class="clear-search" id="clearSearch" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sort-box">
                <span class="sort-label"><i class="fas fa-sort-amount-down"></i> Urutkan:</span>
                <select id="sortMenu" class="elegant-select">
                    <option value="terbaru">Terbaru (Paling Baru)</option>
                    <option value="terlama">Terlama</option>
                    <option value="az">Nama (A - Z)</option>
                    <option value="za">Nama (Z - A)</option>
                </select>
            </div>

        </div>
    </div>

    <!-- Projects Grid -->
    <div class="gm-project-grid" id="projectGrid">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $index => $p): 
                // Pengkondisian thumbnail
                $thumbnail = !empty($p['thumbnail']) ? '../' . $p['thumbnail'] : '../assets/img/dummy_thumbnail.png';
                $isOwner = ($user_id && $p['user_id'] == $user_id);
            ?>
                <!-- Modifikasi atribut data-* untuk search & sort -->
                <div class="gm-project-card" 
                     data-name="<?= htmlspecialchars(strtolower($p['project_name'])) ?>"
                     data-date="<?= strtotime($p['created_at']) ?>"
                     data-id="<?= $p['project_id'] ?>"
                     <?php if ($isOwner): ?>ondblclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'"<?php else: ?>ondblclick="document.querySelector('.clone-btn[data-id=\'<?= $p['project_id'] ?>\']').click()"<?php endif; ?>
                     style="animation-delay: <?= ($index % 12) * 0.05 ?>s">
                     
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
                            <img src="<?= htmlspecialchars($thumbnail) ?>" 
                                 alt="<?= htmlspecialchars($p['project_name']) ?>"
                                 loading="lazy"
                                 onload="this.parentElement.querySelector('.thumbnail-loader').style.opacity='0'"
                                 onerror="this.src='../assets/img/dummy_thumbnail.png'">
                        <?php endif; ?>
                        
                        <!-- Double click support instead of hover button -->
                        <div class="card-glass-hover" style="display:none;" <?php if ($isOwner): ?>ondblclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'"<?php else: ?>ondblclick="document.querySelector('.clone-btn[data-id=\'<?= $p['project_id'] ?>\']').click()"<?php endif; ?>>
                            <!-- Removed visual hover button, rely on dropdown -->
                        </div>
                    </div>

                    <div class="card-bottom">
                        <div class="card-titles">
                            <h3 class="card-title"><?= htmlspecialchars($p['project_name']) ?></h3>
                            <div class="card-meta">
                                <span class="meta-creator"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($p['owner_name'] ?? 'Anonymous') ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="meta-date">
                        <i class="far fa-clock"></i> <?= date('d M Y', strtotime($p['created_at'])) ?>
                    </div>
                    
                    <?php if ($isOwner): ?>
                        <div class="badge badge-owner">
                            <i class="fas fa-star"></i> Milik Anda
                        </div>
                    <?php else: ?>
                        <div class="badge badge-owner">
                            <i class="fas fa-globe"></i> Public
                        </div>
                    <?php endif; ?>

                    <!-- Dots Menu Context -->
                    <div class="card-menu">
                        <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="card-dropdown">
                            <?php if ($isOwner): ?>
                                <button type="button" class="dropdown-item" onclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
                                    <i class="fas fa-pen-nib"></i> Buka Editor
                                </button>
                            <?php else: ?>
                                <button type="button" class="dropdown-item clone-btn" data-id="<?= $p['project_id'] ?>" data-name="<?= htmlspecialchars($p['project_name']) ?>">
                                    <i class="fas fa-clone"></i> Clone & Modifikasi
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>Belum Ada Project Publik</h3>
                <p>Jadilah yang pertama untuk membagikan mahakarya Anda.</p>
                <a href="project.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">
                    <i class="fas fa-plus"></i> Buat Project Pertama
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- No Results State -->
    <div class="no-results" id="noResults" style="display: none;">
        <i class="fas fa-search-minus"></i>
        <h3>Tidak Ditemukan</h3>
        <p>Gunakan kata kunci lain atau periksa ejaan Anda.</p>
    </div>
</main>

<!-- Clone Modal (Elegant) -->
<div id="cloneModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <div class="modal-icon text-success"><i class="fas fa-layer-group"></i></div>
        <h3>Clone Project ke Workspace</h3>
        <div class="modal-glass-body">
            <p>Anda akan menduplikasi <strong><span id="cloneProjectName"></span></strong> secara penuh.</p>
            <p>Project ini akan tersimpan dengan status anonim (Unpublished) di Dashboard Anda sebelum siap dipublikasi ulang.</p>
        </div>
        <div class="modal-glass-footer">
            <button type="button" class="btn-cancel" id="cancelCloneBtn">Batal</button>
            <button type="button" class="btn-confirm" id="confirmCloneBtn">Jalankan Clone</button>
        </div>
    </div>
</div>

<form id="cloneForm" method="post" style="display: none;">
    <input type="hidden" name="clone_from_dashboard" value="1">
    <input type="hidden" name="project_id" id="cloneProjectId">
</form>

<style>
/* ==========================================================================
   GITHUB STYLE UI - DASHBOARD
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

.gm-dashboard {
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

/* --- CONTROL BAR (Search & SORT) --- */
.dash-control-bar {
    margin-bottom: 24px;
}

.control-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: transparent;
    flex-wrap: wrap;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 400px;
}

.search-icon {
    position: absolute;
    left: 8px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 14px;
}

#searchInput {
    width: 100%;
    padding: 5px 30px 5px 28px;
    border-radius: 6px;
    border: 1px solid var(--border-soft);
    background: #f6f8fa;
    font-size: 14px;
    outline: none;
    color: var(--text-main);
}

#searchInput:focus {
    background: white;
    border-color: var(--link);
    box-shadow: inset 0 0 0 1px var(--link);
}

.clear-search {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0;
    font-size: 14px;
}

/* GitHub Select Sort Menu */
.sort-box {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sort-label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-main);
}

.elegant-select {
    appearance: none;
    background: #f6f8fa;
    border: 1px solid var(--border-soft);
    padding: 5px 28px 5px 12px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-main);
    cursor: pointer;
    outline: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2324292f' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    box-shadow: var(--shadow-soft);
}
.elegant-select:hover {
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.05);
}

/* Global .gm-project-card CSS is handled in header.php */

.card-meta {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.meta-creator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-main);
}

.creator-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
}

.meta-date {
    font-size: 0.75rem;
    color: #64748b;
    font-weight: 500;
}

/* --- EMPTY / ERROR STATES --- */
.empty-state, .no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px 20px;
    background: white;
    border-radius: 6px;
    border: 1px solid var(--border-soft);
}

.empty-state i, .no-results i {
    font-size: 24px;
    color: var(--text-muted);
    margin-bottom: 16px;
}

.empty-state h3, .no-results h3 {
    font-size: 16px;
    margin-bottom: 8px;
}

/* CSS Modal now handled in header.php */

/* --- RESPONSIVENSESS --- */
@media (max-width: 768px) {
    .dash-header { padding: 16px; margin-bottom: 16px; }
    .control-wrapper { flex-direction: column; align-items: stretch; }
    .sort-box { justify-content: space-between; }
    .gm-project-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const projectGrid = document.getElementById('projectGrid');
    const noResults = document.getElementById('noResults');
    const sortMenu = document.getElementById('sortMenu');
    
    let debounceTimer;

    // --- Core logic: Sortir Menu (JS Toggling DOM Nodes) ---
    function executeSort() {
        if(!projectGrid) return;
        const sortValue = sortMenu.value;
        let cards = Array.from(projectGrid.querySelectorAll('.gm-project-card'));
        
        cards.sort((a, b) => {
            const dateA = parseInt(a.getAttribute('data-date') || 0);
            const dateB = parseInt(b.getAttribute('data-date') || 0);
            const nameA = a.getAttribute('data-name') || '';
            const nameB = b.getAttribute('data-name') || '';

            if (sortValue === 'terbaru') return dateB - dateA; // Descending
            if (sortValue === 'terlama') return dateA - dateB; // Ascending
            if (sortValue === 'az') return nameA.localeCompare(nameB);
            if (sortValue === 'za') return nameB.localeCompare(nameA);
        });

        // Re-append to grid triggers reflow in correct order
        // display is not altered, so filter logic holds.
        cards.forEach(card => projectGrid.appendChild(card));
    }

    // --- Core logic: Filtering by Search ---
    function filterProjects() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const cards = Array.from(document.querySelectorAll('.gm-project-card'));
        let visibleCount = 0;

        if (clearSearch) clearSearch.style.display = searchTerm ? 'block' : 'none';

        cards.forEach(card => {
            const projectName = card.getAttribute('data-name') || '';
            if (projectName.includes(searchTerm)) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (noResults && projectGrid) {
            if (visibleCount === 0 && cards.length > 0) {
                noResults.style.display = 'block';
                projectGrid.style.display = 'none';
            } else {
                noResults.style.display = 'none';
                projectGrid.style.display = 'grid';
            }
        }
    }

    // Bind Search Input
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => filterProjects(), 300);
        });
    }

    // Bind Clear Search
    if (clearSearch) {
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            filterProjects();
            searchInput.focus();
        });
    }

    // Bind Sort Menu Event
    if (sortMenu) {
        sortMenu.addEventListener('change', executeSort);
    }

    // --- Clone Handling logic ---
    const cloneModal = document.getElementById('cloneModal');
    const cloneForm = document.getElementById('cloneForm');
    const cloneProjectIdInput = document.getElementById('cloneProjectId');
    const cloneProjectNameSpan = document.getElementById('cloneProjectName');
    const confirmCloneBtn = document.getElementById('confirmCloneBtn');
    const cancelCloneBtn = document.getElementById('cancelCloneBtn');
    let currentCloneId = null;

    // Attach event listeners dynamically to cards
    function attachCardListeners() {
        const btnsOpen = document.querySelectorAll('.open-own-btn');
        const btnsClone = document.querySelectorAll('.clone-btn');
        
        btnsOpen.forEach(btn => {
            btn.removeEventListener('click', handleOpenOwn); // prevent dups
            btn.addEventListener('click', handleOpenOwn);
        });

        btnsClone.forEach(btn => {
            btn.removeEventListener('click', handleCloneInit); // prevent dups
            btn.addEventListener('click', handleCloneInit);
        });
    }

    function handleOpenOwn(e) {
        e.stopPropagation();
        const projectId = this.getAttribute('data-id');
        window.location.href = `../index.php?project_id=${projectId}`;
    }

    function handleCloneInit(e) {
        e.stopPropagation();
        currentCloneId = this.getAttribute('data-id');
        if (cloneProjectNameSpan) cloneProjectNameSpan.textContent = this.getAttribute('data-name').toUpperCase();
        if (cloneModal) cloneModal.style.display = 'flex';
    }

    attachCardListeners();

    if (confirmCloneBtn) {
        confirmCloneBtn.addEventListener('click', function() {
            if (currentCloneId && cloneProjectIdInput && cloneForm) {
                cloneProjectIdInput.value = currentCloneId;
                cloneForm.submit();
            }
        });
    }

    if (cancelCloneBtn) {
        cancelCloneBtn.addEventListener('click', function() {
            if (cloneModal) cloneModal.style.display = 'none';
            currentCloneId = null;
        });
    }

    // Toast logic from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('cloned')) {
        const toast = document.createElement('div');
        // reuse standard toast styling or define a simple inline logic
        toast.innerHTML = `<div style="position:fixed; bottom:30px; right:30px; background:#22c55e; color:white; padding:15px 25px; border-radius:100px; font-weight:bold; box-shadow:0 10px 30px rgba(34,197,94,0.3); z-index:999999; animation:modalPop 0.5s ease;">
                              <i class="fas fa-check-circle"></i> Karya asyik ini telah di-clone! (Tersimpan sebagai Unpublished)
                           </div>`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.4s ease';
            setTimeout(() => toast.remove(), 400);
        }, 4000);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
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

<?php require '../includes/footer.php'; 
if (ob_get_level()) {
    ob_end_flush();
}
?>