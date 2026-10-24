<?php
// Mulai output buffering
ob_start();
require '../includes/db.php';
require '../includes/header.php';

// Ambil statistik untuk ditampilkan
$totalProjects = $pdo->query("SELECT COUNT(*) FROM GM_PROJECTS")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM GM_PROJECTS")->fetchColumn();
$totalTemplates = $pdo->query("SELECT COUNT(*) FROM GM_PROJECTS WHERE is_template = 1")->fetchColumn();
$totalFrames = $pdo->query("SELECT COUNT(*) FROM GM_FRAMES")->fetchColumn();
?>

<main class="gm-about-page">
    
    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-orb orb-1"></div>
        <div class="hero-orb orb-2"></div>
        
        <div class="hero-content">
            <div class="hero-badge">
                <i class="fas fa-sparkles"></i> Welcoming the future of design
            </div>
            <h1 class="hero-title">
                Unleash Creativity with <br/> 
                <span class="gradient-text">Greymist Engine</span>
            </h1>
            <p class="hero-subtitle">
                The most elegant platform crafted for designers, developers, and visionaries to orchestrate digital masterpieces effortlessly.
            </p>
            <div class="hero-buttons">
                <a href="dashboard.php" class="btn-premium">
                    <span>Mulai Berkarya</span> <i class="fas fa-arrow-right"></i>
                </a>
                <a href="project.php" class="btn-glass">
                    <i class="fas fa-layer-group"></i> Create Project
                </a>
            </div>
        </div>

        <!-- Floating Stats Glass Strip -->
        <div class="hero-stats-glass">
            <div class="stat-item" data-count="<?= $totalProjects ?>">
                <div class="stat-num" id="statProjects">0</div>
                <div class="stat-desc">Total Projects</div>
            </div>
            <div class="divider"></div>
            <div class="stat-item" data-count="<?= $totalUsers ?>">
                <div class="stat-num" id="statUsers">0</div>
                <div class="stat-desc">Active Creators</div>
            </div>
            <div class="divider"></div>
            <div class="stat-item" data-count="<?= $totalTemplates ?>">
                <div class="stat-num" id="statTemplates">0</div>
                <div class="stat-desc">Premium Templates</div>
            </div>
            <div class="divider"></div>
            <div class="stat-item" data-count="<?= $totalFrames ?>">
                <div class="stat-num" id="statFrames">0</div>
                <div class="stat-desc">Frames Rendered</div>
            </div>
        </div>
    </section>

    <!-- Bento Box Features Section -->
    <section class="features-section">
        <div class="section-title-wrapper text-center">
            <h2 class="title-modern">Why Choose Greymist?</h2>
            <p class="subtitle-modern">Experience a workflow designed strictly for elegance and performance.</p>
        </div>

        <div class="bento-grid">
            <!-- Feature 1 (Large spans 2 cols) -->
            <div class="bento-card bento-large bento-gradient-1" data-delay="0.1">
                <div class="bento-icon"><i class="fas fa-clone"></i></div>
                <div class="bento-content">
                    <h3>Clone & Customize</h3>
                    <p>Instantly duplicate public projects and reverse-engineer masterpieces created by the highly-driven community.</p>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="bento-card" data-delay="0.2">
                <div class="bento-icon"><i class="fas fa-layer-group"></i></div>
                <div class="bento-content">
                    <h3>Multi-Frame Engine</h3>
                    <p>Seamlessly interconnect variables across dynamic visual nodes without losing context.</p>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="bento-card" data-delay="0.3">
                <div class="bento-icon"><i class="fa-solid fa-diagram-project"></i></div>
                <div class="bento-content">
                    <h3>Template Hub</h3>
                    <p>Hundreds of highly curated layouts awaiting your final touch.</p>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="bento-card" data-delay="0.4">
                <div class="bento-icon"><i class="fas fa-share-nodes"></i></div>
                <div class="bento-content">
                    <h3>Live Publishing</h3>
                    <p>Click once and share your canvas live globally.</p>
                </div>
            </div>

            <!-- Feature 5 (Large spans 2 cols) -->
            <div class="bento-card bento-large bento-gradient-2" data-delay="0.5">
                <div class="bento-icon"><i class="fas fa-bolt"></i></div>
                <div class="bento-content">
                    <h3>Realtime DOM Injection</h3>
                    <p>Feel the pure performance of vanilla JavaScript without virtual DOM overheads. Lightning fast drag and drop styling.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Elegant Team Section -->
    <section class="team-section">
        <div class="section-title-wrapper text-center">
            <h2 class="title-modern">The Brains Behind</h2>
            <p class="subtitle-modern">Meet the visionaries coding the future.</p>
        </div>

        <div class="team-grid" style="justify-content: center; width: 100%; display: flex;">
            <div class="team-profile-card">
                <div class="profile-avatar"><i class="fas fa-user-astronaut"></i></div>
                <h3 class="profile-name">Ahmad Nasuha</h3>
                <p class="profile-role">Founder & CEO</p>
                <div class="profile-socials">
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
           
        </div>
    </section>

    <!-- Redesigned Roadmap -->
    <section class="roadmap-section">
        <div class="section-title-wrapper text-center">
            <h2 class="title-modern">The Journey</h2>
            <p class="subtitle-modern">How we evolved into an enterprise-lite architecture.</p>
        </div>

        <div class="elegant-timeline">
            <div class="tl-item">
                <div class="tl-content">
                    <span class="tl-date">Q1 2024</span>
                    <h4>Alpha Launch</h4>
                    <p>Core editor functionality deployed with direct Database API injection.</p>
                </div>
            </div>
            <div class="tl-item">
                <div class="tl-content">
                    <span class="tl-date">Q2 2024</span>
                    <h4>Public Dashboard</h4>
                    <p>Community clone functionality and social project visibility released.</p>
                </div>
            </div>
            <div class="tl-item">
                <div class="tl-content">
                    <span class="tl-date">Q3 2024</span>
                    <h4>Premium UI Overhaul</h4>
                    <p>Transition to variables-based CSS and sleek dark/light thematic aesthetics.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Modern CTA -->
    <section class="cta-ultimate">
        <div class="cta-glass-panel">
            <h2>Start your creative engine.</h2>
            <p>Join an exclusive community of elite builders.</p>
            <div class="cta-buttons">
                <a href="project.php" class="btn-cta-dark">Open Workspace</a>
                <a href="../session/register.php" class="btn-cta-light">Create Account</a>
            </div>
        </div>
    </section>

</main>

<style>
/* ==========================================================================
   DRIBBBLE-INSPIRED ELEGANT UI SYSTEM
   ========================================================================== */

:root {
    --bg-main: #fcfcfd;
    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --border-subtle: rgba(226, 232, 240, 0.8);
    --glass-bg: rgba(255, 255, 255, 0.7);
    --glass-border: rgba(255, 255, 255, 0.5);
    --shadow-soft: 0 25px 50px -12px rgba(15, 23, 42, 0.05);
    --shadow-float: 0 10px 20px -5px rgba(15, 23, 42, 0.08);
    --accent-gradient: linear-gradient(135deg, #0d6efd, #00d4ff);
    --font-heading: 'Poppins', sans-serif;
}

body {
    background-color: var(--bg-main);
    color: var(--text-primary);
}

.gm-about-page {
    max-width: 1200px;
    margin: 0 auto;
    padding-top: 2rem;
    overflow-x: hidden;
}

/* --- TYPOGRAPHY --- */
.text-center { text-align: center; }

.title-modern {
    font-family: var(--font-heading);
    font-size: 2.2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.subtitle-modern {
    font-size: 1.1rem;
    color: var(--text-secondary);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.section-title-wrapper {
    margin-bottom: 3.5rem;
}

/* --- HERO SECTION --- */
.about-hero {
    position: relative;
    padding: 8rem 2rem 10rem;
    text-align: center;
    border-radius: 32px;
    background: radial-gradient(circle at top center, #f1f5f9 0%, transparent 100%);
    margin-bottom: 5rem;
    overflow: hidden;
}

/* Artistic mesh orbs */
.hero-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    z-index: -1;
    opacity: 0.6;
    animation: float 10s infinite ease-in-out alternate;
}
.orb-1 { width: 400px; height: 400px; background: #cce5ff; top: -100px; left: -100px; }
.orb-2 { width: 500px; height: 500px; background: #e6f7ff; bottom: -150px; right: -150px; animation-delay: -5s; }

@keyframes float {
    0% { transform: translateY(0) scale(1); }
    100% { transform: translateY(30px) scale(1.05); }
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid var(--border-subtle);
    border-radius: 100px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    backdrop-filter: blur(10px);
}
.hero-badge i { color: #f59e0b; }

.hero-title {
    font-family: var(--font-heading);
    font-size: 4.5rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.04em;
    margin-bottom: 1.5rem;
    color: #0f172a;
}
.gradient-text {
    background: linear-gradient(135deg, #0d6efd 0%, #00d4ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-subtitle {
    font-size: 1.25rem;
    color: var(--text-secondary);
    max-width: 700px;
    margin: 0 auto 3rem;
    line-height: 1.6;
}

.hero-buttons {
    display: flex;
    gap: 1.2rem;
    justify-content: center;
}

/* High-end Buttons */
.btn-premium {
    background: var(--accent-gradient);
    color: white;
    padding: 1rem 2.2rem;
    border-radius: 100px;
    font-weight: 600;
    font-size: 1.05rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 20px -5px rgba(15, 23, 42, 0.3);
}
.btn-premium:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px -5px rgba(13, 110, 253, 0.4);
}
.btn-premium i { transition: transform 0.3s; }
.btn-premium:hover i { transform: translateX(4px); }

.btn-glass {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    backdrop-filter: blur(12px);
    color: var(--text-primary);
    padding: 1rem 2.2rem;
    border-radius: 100px;
    font-weight: 600;
    font-size: 1.05rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}
.btn-glass:hover {
    background: white;
    transform: translateY(-3px);
    box-shadow: var(--shadow-float);
}

/* Glass Stats Strip */
.hero-stats-glass {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    width: 90%;
    max-width: 1000px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 24px;
    padding: 2.5rem;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: var(--shadow-soft);
    z-index: 10;
}

.stat-item { text-align: center; }
.stat-num {
    font-family: var(--font-heading);
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
    margin-bottom: 0.5rem;
}
.stat-desc {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.divider {
    width: 1px;
    height: 40px;
    background: var(--border-subtle);
}

/* --- BENTO BOX FEATURES --- */
.features-section {
    padding: 8rem 2rem 4rem;
}

.bento-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: minmax(280px, auto);
    gap: 1.5rem;
}

.bento-card {
    background: white;
    border-radius: 32px;
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border: 1px solid var(--border-subtle);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.bento-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-soft);
}

.bento-large { grid-column: span 2; }

.bento-icon {
    width: 64px;
    height: 64px;
    background: rgba(13, 110, 253, 0.05);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: #0d6efd;
    margin-bottom: 2rem;
    transition: transform 0.3s ease;
}

.bento-card:hover .bento-icon {
    transform: scale(1.1) rotate(-5deg);
}

.bento-content h3 {
    font-family: var(--font-heading);
    font-size: 1.4rem;
    font-weight: 700;
    margin-bottom: 0.8rem;
    color: var(--text-primary);
}

.bento-content p {
    color: var(--text-secondary);
    font-size: 1rem;
    line-height: 1.6;
}

.bento-gradient-1 {
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
}
.bento-gradient-2 {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* --- TEAM SECTION --- */
.team-section {
    padding: 4rem 2rem;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.team-profile-card {
    background: transparent;
    padding: 2rem 1rem;
    text-align: center;
    border-radius: 24px;
    transition: all 0.3s ease;
}

.team-profile-card:hover {
    background: white;
    box-shadow: var(--shadow-soft);
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    margin: 0 auto 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #cbd5e1;
    box-shadow: var(--shadow-float);
    border: 4px solid #f8fafc;
    transition: transform 0.3s;
}

.team-profile-card:hover .profile-avatar {
    transform: scale(1.05);
    color: #0f172a;
    border-color: white;
}

.profile-name {
    font-family: var(--font-heading);
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.2rem;
}

.profile-role {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-bottom: 1.2rem;
}

.profile-socials {
    display: flex;
    justify-content: center;
    gap: 1rem;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
}

.team-profile-card:hover .profile-socials {
    opacity: 1;
    transform: translateY(0);
}

.profile-socials a {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    transition: all 0.2s;
}

.profile-socials a:hover {
    background: #0f172a;
    color: white;
}

/* --- ROADMAP TIMELINE --- */
.roadmap-section {
    padding: 4rem 2rem;
}

.elegant-timeline {
    max-width: 700px;
    margin: 0 auto;
    position: relative;
}

.elegant-timeline::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, transparent, #cbd5e1, transparent);
}

.tl-item {
    position: relative;
    padding-left: 60px;
    margin-bottom: 3rem;
}

.tl-item::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: white;
    border: 3px solid #0f172a;
    box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.1);
    transition: all 0.3s ease;
}

.tl-item:hover::before {
    transform: scale(1.3);
    background: #0f172a;
}

.tl-content {
    background: white;
    padding: 2rem;
    border-radius: 24px;
    border: 1px solid var(--border-subtle);
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.tl-item:hover .tl-content {
    transform: translateX(10px);
    box-shadow: var(--shadow-soft);
}

.tl-date {
    display: inline-block;
    font-size: 0.8rem;
    font-weight: 700;
    color: #3b82f6; /* slight blue pop for timeline */
    background: #eff6ff;
    padding: 4px 12px;
    border-radius: 100px;
    margin-bottom: 1rem;
}

.tl-content h4 {
    font-family: var(--font-heading);
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
}

.tl-content p {
    color: var(--text-secondary);
    line-height: 1.6;
}

/* --- ULTIMATE CTA --- */
.cta-ultimate {
    padding: 4rem 2rem 6rem;
}

.cta-glass-panel {
    background: linear-gradient(135deg, #0d6efd, #00d4ff);
    border-radius: 40px;
    padding: 6rem 3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(13, 110, 253, 0.25);
}

.cta-glass-panel::after {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 50%);
    opacity: 0.5;
    pointer-events: none;
}

.cta-glass-panel h2 {
    font-family: var(--font-heading);
    font-size: 3.5rem;
    font-weight: 800;
    color: white;
    letter-spacing: -0.03em;
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
}

.cta-glass-panel p {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 3rem;
    position: relative;
    z-index: 2;
}

.cta-buttons {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    position: relative;
    z-index: 2;
}

.btn-cta-dark {
    background: white;
    color: #0d6efd;
    padding: 1.2rem 2.5rem;
    border-radius: 100px;
    font-weight: 700;
    font-size: 1.1rem;
    text-decoration: none;
    transition: transform 0.3s, box-shadow 0.3s;
}

.btn-cta-dark:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255,255,255,0.2);
}

.btn-cta-light {
    background: transparent;
    border: 2px solid rgba(255,255,255,0.2);
    color: white;
    padding: 1.2rem 2.5rem;
    border-radius: 100px;
    font-weight: 600;
    font-size: 1.1rem;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-cta-light:hover {
    background: rgba(255,255,255,0.1);
    border-color: white;
}

/* ==========================================================================
   RESPONSIVENESS
   ========================================================================== */

@media (max-width: 1024px) {
    .bento-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .bento-large {
        grid-column: span 1;
    }
    .hero-title { font-size: 3.5rem; }
}

@media (max-width: 768px) {
    .about-hero {
        padding: 5rem 1rem 8rem;
        border-radius: 24px;
    }
    .hero-title { font-size: 2.8rem; }
    .hero-stats-glass {
        flex-direction: column;
        gap: 1.5rem;
        bottom: -150px;
        padding: 1.5rem;
    }
    .divider {
        width: 100%;
        height: 1px;
    }
    .features-section { padding-top: 12rem; }
    .bento-grid {
        grid-template-columns: 1fr;
    }
    .team-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .cta-glass-panel h2 { font-size: 2.5rem; }
    .cta-buttons { flex-direction: column; }
}

@media (max-width: 480px) {
    .team-grid {
        grid-template-columns: 1fr;
    }
    .hero-buttons { flex-direction: column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elegant Number Counter with Easing
    function animateValue(obj, start, end, duration) {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            // easeOutQuart
            const easeOut = 1 - Math.pow(1 - progress, 4);
            obj.innerHTML = Math.floor(easeOut * (end - start) + start).toLocaleString();
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.innerHTML = end.toLocaleString();
            }
        };
        window.requestAnimationFrame(step);
    }

    const observerOptions = {
        threshold: 0.5,
        rootMargin: "0px"
    };

    const countObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const targetVal = parseInt(entry.target.parentElement.getAttribute('data-count') || 0);
                if (entry.target.innerText === '0') {
                    animateValue(entry.target, 0, targetVal, 2000);
                }
                countObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.stat-num').forEach(el => countObserver.observe(el));

    // Reveal animations mapping
    const revealElements = document.querySelectorAll('.bento-card, .team-profile-card, .tl-item');
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    revealElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        revealObserver.observe(el);
    });
});
</script>

<?php require '../includes/footer.php'; 
if (ob_get_level()) ob_end_flush();
?>