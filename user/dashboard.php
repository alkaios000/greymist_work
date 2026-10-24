<?php
ob_start();
require '../includes/db.php';
require '../includes/header.php';

// Pastikan user login
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../session/login.php");
    exit;
}

// Ambil project milik user (maksimal 4 terakhir diedit)
$stmt = $pdo->prepare("
    SELECT p.project_id, p.project_name, p.thumbnail, p.created_at, p.user_id,
           u.username as owner_name,
           (SELECT core_content FROM GM_FRAMES f WHERE f.project_id = p.project_id ORDER BY sort_order ASC, frame_id ASC LIMIT 1) as first_frame_content
    FROM GM_PROJECTS p
    LEFT JOIN GM_USERS u ON p.user_id = u.user_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 4
");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();

// Hitung total project user
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM GM_PROJECTS WHERE user_id = ?");
$stmtTotal->execute([$user_id]);
$totalUserProjects = $stmtTotal->fetchColumn();

// Ambil nickname dari session
$nickname = htmlspecialchars($_SESSION['nickname'] ?? $_SESSION['username'] ?? 'User');
?>

<main class="gm-dashboard-new">
    <!-- Hero Section -->
    <section class="hero-welcome">
        <div class="hero-content">
            <h1>Selamat Datang Kembali, <span class="gradient-text"><?= $nickname ?></span>!</h1>
            <p>Siap untuk membuat mahakarya selanjutnya hari ini?</p>
            
            <div class="quick-actions">
                <a href="../api/new_project.php" class="btn-action primary">
                    <div class="action-icon"><i class="fas fa-plus"></i></div>
                    <div class="action-text">
                        <strong>Buat Project Baru</strong>
                        <span>Mulai canvas kosong</span>
                    </div>
                </a>
                <a href="templates.php" class="btn-action secondary">
                    <div class="action-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="action-text">
                        <strong>Jelajahi Template</strong>
                        <span>Gunakan layout siap pakai</span>
                    </div>
                </a>
                <a href="explore.php" class="btn-action secondary">
                    <div class="action-icon"><i class="fas fa-compass"></i></div>
                    <div class="action-text">
                        <strong>Eksplorasi Inspirasi</strong>
                        <span>Lihat karya komunitas</span>
                    </div>
                </a>
            </div>
        </div>
        <div class="hero-graphics">
            <div class="orb-bg orb-1"></div>
            <div class="orb-bg orb-2"></div>
        </div>
    </section>

    <!-- Recent Projects Section -->
    <section class="recent-projects">
        <div class="section-header">
            <h2>Project Terakhir Anda</h2>
            <?php if ($totalUserProjects > 4): ?>
                <a href="project.php" class="btn-view-all">Lihat Semua (<?= $totalUserProjects ?>) <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>

        <div class="gm-project-grid">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $index => $p): 
                    $thumbnail = !empty($p['thumbnail']) ? '../' . $p['thumbnail'] : '../assets/img/dummy_thumbnail.png';
                ?>
                    <div class="gm-project-card" style="animation-delay: <?= ($index % 4) * 0.1 ?>s" ondblclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
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
                            
                            <div class="card-glass-hover" style="display:none;" ondblclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
                                <!-- Hidden hover button -->
                            </div>
                        </div>

                        <div class="card-bottom">
                            <div class="card-titles">
                                <h3 class="card-title"><?= htmlspecialchars($p['project_name']) ?></h3>
                                <div class="card-meta">
                                    <span class="meta-creator"><i class="fas fa-user-circle"></i> Anda</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="meta-date">
                            <i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                        </div>
                        
                        <!-- Dots Menu Context -->
                        <div class="card-menu">
                            <button type="button" class="menu-trigger" onclick="toggleDropdown(this)">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="card-dropdown">
                                <button type="button" class="dropdown-item" onclick="window.location.href='../index.php?project_id=<?= $p['project_id'] ?>'">
                                    <i class="fas fa-pen-nib"></i> Buka Editor
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-paint-brush"></i></div>
                    <h3>Belum Ada Project</h3>
                    <p>Anda belum memiliki project apapun. Mari buat project pertama Anda sekarang!</p>
                    <a href="../api/new_project.php" class="btn-primary" style="margin-top: 1rem; display: inline-block; padding: 0.8rem 2rem; border-radius: 99px;">
                        <i class="fas fa-plus"></i> Buat Project Pertama
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<style>
/* ==========================================================================
   NEW WELCOME DASHBOARD
   ========================================================================== */
:root {
    --bg-main: #f8f9fa;
    --text-main: #333333;
    --text-muted: #6c757d;
    --font-heading: 'Poppins', sans-serif;
    --accent: #0d6efd;
    --accent-light: #00d4ff;
}

body { background: var(--bg-main); color: var(--text-main); font-family: var(--font-heading); }

.gm-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    min-height: 100vh;
}


/* --- HERO SECTION --- */
.hero-welcome {
    position: relative;
    background: #ffffff;
    border-radius: 24px;
    padding: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 10px 40px -10px rgba(13, 110, 253, 0.08);
    overflow: hidden;
    margin-bottom: 3rem;
    border: 1px solid rgba(13, 110, 253, 0.1);
}

.hero-content {
    position: relative;
    z-index: 2;
    max-width: 700px;
}

.hero-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--text-main);
}

.gradient-text {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-content p {
    font-size: 1.1rem;
    color: var(--text-muted);
    margin-bottom: 2rem;
}

.quick-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-action {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    min-width: 200px;
}

.btn-action.primary {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    color: white;
    box-shadow: 0 10px 25px rgba(13, 110, 253, 0.3);
}

.btn-action.primary .action-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

.btn-action.primary:hover {
    transform: translateY(-4px);
    box-shadow: 0 15px 35px rgba(13, 110, 253, 0.4);
}

.btn-action.secondary {
    background: #ffffff;
    border: 1px solid rgba(13, 110, 253, 0.2);
    color: var(--text-main);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
}

.btn-action.secondary .action-icon {
    background: rgba(13, 110, 253, 0.1);
    color: var(--accent);
}

.btn-action.secondary:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(13, 110, 253, 0.15);
    border-color: rgba(13, 110, 253, 0.4);
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.action-text {
    display: flex;
    flex-direction: column;
}

.action-text strong {
    font-size: 1rem;
    font-weight: 600;
}

.action-text span {
    font-size: 0.8rem;
    opacity: 0.8;
}

.btn-action.secondary .action-text span {
    color: var(--text-muted);
}

.hero-graphics {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 50%;
    pointer-events: none;
    z-index: 1;
    overflow: hidden;
}

.orb-bg {
    position: absolute;
    border-radius: 50%;
    filter: blur(60px);
    opacity: 0.5;
    animation: float 10s infinite alternate ease-in-out;
}

.orb-1 {
    width: 300px;
    height: 300px;
    background: #cce5ff;
    top: -50px;
    right: -50px;
}

.orb-2 {
    width: 250px;
    height: 250px;
    background: #e6f7ff;
    bottom: -80px;
    right: 100px;
    animation-delay: -5s;
}

@keyframes float {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(-20px) scale(1.05); }
}

/* --- RECENT PROJECTS SECTION --- */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-main);
}

.btn-view-all {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--accent);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.2s;
}

.btn-view-all:hover {
    color: #0b5ed7;
}

.btn-view-all i {
    transition: transform 0.2s;
}

.btn-view-all:hover i {
    transform: translateX(4px);
}

/* Global .gm-project-card CSS is handled in header.php */

.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    border: 1px dashed rgba(13, 110, 253, 0.3);
}

.empty-icon {
    font-size: 3rem;
    color: var(--accent);
    opacity: 0.5;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.btn-primary {
    background: linear-gradient(135deg, var(--accent), var(--accent-light));
    border: none;
    color: white;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
    transform: translateY(-2px);
}

/* --- RESPONSIVENSESS --- */
@media (max-width: 768px) {
    .hero-welcome {
        padding: 2rem;
    }
    .hero-content h1 {
        font-size: 2rem;
    }
    .quick-actions {
        flex-direction: column;
    }
    .btn-action {
        width: 100%;
    }
    .hero-graphics {
        display: none;
    }
    .gm-project-grid {
        grid-template-columns: 1fr;
    }
}
</style>

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