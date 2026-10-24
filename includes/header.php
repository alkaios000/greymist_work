<?php 
if (session_status() === PHP_SESSION_NONE) session_start(); 

// Ambil data user termasuk profile picture jika user login
$user_profile = null;
if (isset($_SESSION['user_id'])) {
    require_once dirname(__FILE__) . '/db.php';
    $stmt = $pdo->prepare("SELECT profile, nickname, username FROM GM_USERS WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch();
    if ($userData) {
        $user_profile = $userData['profile'];
        
        // Sesuaikan path jika dipanggil dari subdirektori (seperti /user/)
        if ($user_profile) {
            $is_subfolder = basename(dirname($_SERVER['PHP_SELF'])) !== basename(dirname(dirname(__FILE__)));
            // Cara yang lebih simpel: jika file ini dipanggil dari folder 'user', tambahkan ../
            // Namun yang paling aman adalah mengecek keberadaan file dari lokasi sekarang
            if (!file_exists($user_profile) && file_exists('../' . $user_profile)) {
                $user_profile = '../' . $user_profile;
            }
        }

        $_SESSION['nickname'] = $userData['nickname'];
        $_SESSION['username'] = $userData['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Greymist - Platform Kreatif</title>

<!-- Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        user-select: none;
    }
    
    body {
        font-family: 'Poppins', sans-serif;
        background: #f8f9fa;
        color: #333333;
        line-height: 1.5;
    }
    
    /* Modern Header */
    .gm-header {
        background: #ffffff;
        border-bottom: 1px solid #e9edf2;
        padding: 0.75rem 1.5rem;
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .gm-logo {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0d6efd, #00d4ff);
        background-clip: text;
        -webkit-background-clip: text;
        color: transparent;
        text-decoration: none;
        letter-spacing: -0.3px;
    }
    
    .gm-logo::after {
        content: 'Beta';
        font-size: 0.65rem;
        font-weight: 600;
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        padding: 0.2rem 0.5rem;
        border-radius: 20px;
        margin-left: 0.5rem;
        vertical-align: middle;
    }
    
    /* Navigation Links */
    .gm-nav-links {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .gm-nav-links a {
        text-decoration: none;
        color: #333333;
        font-weight: 500;
        font-size: 0.95rem;
        transition: color 0.3s ease;
        position: relative;
        padding: 0.35rem 0;
    }
    
    .gm-nav-links a:hover {
        color: #0d6efd;
    }
    
    .gm-nav-links a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 0;
        height: 2px;
        background: #0d6efd;
        transition: width 0.3s ease;
        border-radius: 2px;
    }
    
    .gm-nav-links a:hover::after, .gm-nav-links a.active-nav::after {
        width: 100%;
    }
    
    .gm-nav-links a.active-nav {
        color: #0d6efd !important;
        font-weight: 600;
    }
    
    /* Header Right Section */
    .gm-header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }
    
    .welcome-text {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: #475569;
        background: #f8fafc;
        padding: 0.4rem 0.8rem;
        border-radius: 2rem;
    }
    
    .welcome-text i {
        color: #0d6efd;
        font-size: 0.7rem;
    }
    
    .welcome-text span {
        font-weight: 600;
        color: #333333;
    }
    
    /* Auth Buttons */
    .btn-outline {
        background: #ffffff;
        border: 1px solid rgba(13, 110, 253, 0.2);
        padding: 0.4rem 1.1rem;
        border-radius: 999px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        color: #0d6efd;
        transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    }
    
    .btn-outline:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.15);
        background: #f8f9fa;
        border-color: rgba(13, 110, 253, 0.4);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #0d6efd, #00d4ff);
        border: none;
        padding: 0.4rem 1.1rem;
        border-radius: 999px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        color: white;
        transition: all .3s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
    }
    
    .btn-logout {
        background: transparent;
        border: 1.5px solid #fee2e2;
        padding: 0.5rem 1rem;
        border-radius: 0.6rem;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        color: #dc2626;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-logout:hover {
        background: #fef2f2;
        border-color: #fecaca;
    }
    
    /* Avatar Dropdown */
    .user-avatar {
        position: relative;
        cursor: pointer;
    }
    
    .avatar-icon {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        transition: transform 0.2s;
        overflow: hidden;
        background: linear-gradient(135deg, #0d6efd, #00d4ff);
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
    }
    
    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 600;
        background: transparent; /* inherits gradient */
        color: white;
    }
    
    .avatar-icon:hover {
        transform: scale(1.05);
    }
    
    .dropdown-menu {
        position: absolute;
        top: 110%;
        right: 0;
        background: white;
        border: 1px solid #e9edf2;
        border-radius: 0.8rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        min-width: 180px;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s;
        z-index: 100;
    }
    
    .user-avatar:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
    }
    
    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1rem;
        text-decoration: none;
        color: #475569;
        font-size: 0.8rem;
        transition: background 0.2s;
    }
    
    .dropdown-menu a:hover {
        background: rgba(13, 110, 253, 0.05);
        color: #0d6efd;
    }
    
    .dropdown-menu hr {
        margin: 0.3rem 0;
        border: none;
        border-top: 1px solid #e9edf2;
    }
    
    /* Card Menu (3 Dots) */
    .card-menu {
        position: relative;
    }
    
    .menu-trigger {
        background: transparent;
        border: none;
        color: #94a3b8;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .menu-trigger:hover {
        background: #f1f5f9;
        color: #1e293b;
    }
    
    .card-dropdown {
        position: absolute;
        bottom: 110%;
        right: 0;
        background: white;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        width: 180px;
        padding: 0.5rem;
        z-index: 50;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: opacity 0.2s cubic-bezier(0.16, 1, 0.3, 1), 
                    visibility 0.2s cubic-bezier(0.16, 1, 0.3, 1), 
                    transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .card-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        padding: 0.7rem 0.8rem;
        background: none;
        border: none;
        text-align: left;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .dropdown-item i {
        width: 1.2rem;
        text-align: center;
        font-size: 0.95rem;
    }
    
    .dropdown-item:hover {
        background: #f8fafc;
        color: #0f172a;
    }
    
    .dropdown-item.danger:hover {
        background: #fef2f2;
        color: #ef4444;
    }

    /* Modern Project Cards (Floating Title Container) */
    .gm-project-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    .gm-project-card {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        background: #f8fafc;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        aspect-ratio: 4/3;
        border: 1px solid rgba(0,0,0,0.05);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
    }
    .gm-project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(13, 110, 253, 0.1);
        border-color: rgba(13, 110, 253, 0.2);
    }
    .card-image-wrapper {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        background: #f1f5f9;
    }
    .card-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Overlay Title Container (Bottom Left) */
    .card-bottom {
        /* position: static so absolute children position relative to .gm-project-card */
        z-index: 5;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        padding: 3px 5px;
        border-top-right-radius: 16px;
        max-width: 80%;
        width: fit-content;
        display: flex;
        flex-direction: column;
        /* gap: 2px; */
        border-top: 1px solid rgba(0,0,0,0.02);
        border-right: 1px solid rgba(0,0,0,0.02);
    }
    .card-titles {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .card-title {
        font-size: 0.85rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .card-meta {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .meta-creator {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
    }
    .meta-date {
        position: absolute;
        top: 8px;
        left: 8px;
        z-index: 10;
        font-size: 0.65rem;
        color: #ffffff;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px);
        padding: 2px 6px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    /* Floating Menu Context (Top Right) */
    .card-menu {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 15;
    }
    .menu-trigger {
        background: transparent;
        border: none;
        color: #ffffff;
        text-shadow: 0 1px 4px rgba(0,0,0,0.8);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: none;
    }
    .menu-trigger:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        transform: scale(1.1);
    }
    
    /* Global Badges for cards */
    .gm-project-card .badge {
        position: absolute;
        z-index: 10;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: none;
    }
    .badge-template { 
        bottom: 8px;
        right: 8px;
        top: auto !important; left: auto !important;
        background: linear-gradient(135deg, #f59e0b, #fbbf24) !important; 
        color: white !important; 
        width: 26px;
        height: 26px;
        border-radius: 50%;
        padding: 0;
        font-size: 0 !important; /* Hide text */
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3) !important;
    }
    .badge-template i { font-size: 0.75rem; margin: 0; }
    
    .badge-owner, .badge-muted { 
        bottom: 8px; 
        right: 8px;
        top: auto !important; left: auto !important;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        padding: 0;
        font-size: 0 !important; /* Hide text */
        background: rgba(255,255,255,0.95) !important;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Move template badge ABOVE the owner/muted badge if both are present */
    .gm-project-card .badge-owner ~ .badge-template,
    .gm-project-card .badge-muted ~ .badge-template {
        bottom: 40px;
    }
    
    .badge-owner i { font-size: 0.75rem; color: #15803d; margin: 0; }
    .badge-muted i { font-size: 0.75rem; color: #64748b; margin: 0; }

    /* Modern Modals */
    .modal-glass {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    /* Thumbnail Loader */
    .thumbnail-loader {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        z-index: 2;
        transition: opacity 0.3s;
    }
    .thumbnail-spinner {
        width: 24px;
        height: 24px;
        border: 2px solid #cbd5e1;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    /* Live Thumbnail */
    .live-thumbnail-wrapper {
        width: 100%;
        height: 100%;
        overflow: hidden;
        position: absolute;
        inset: 0;
        border-radius: inherit;
        opacity: 0;
        transition: opacity 0.4s ease;
        z-index: 3;
    }

    .mini-preview {
        background: #ffffff;
        pointer-events: none;
        overflow: hidden;
    }

    .modal-glass[style*="display: flex"], .modal-glass[style*="display:flex"], .modal-glass.show {
        opacity: 1;
        visibility: visible;
    }

    .modal-glass-content {
        background: #ffffff;
        border-radius: 12px;
        width: 90%;
        max-width: 400px;
        padding: 0;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        transform: scale(0.95) translateY(10px);
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-align: left;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .modal-glass[style*="display: flex"] .modal-glass-content, 
    .modal-glass[style*="display:flex"] .modal-glass-content,
    .modal-glass.show .modal-glass-content {
        transform: scale(1) translateY(0);
    }

    .modal-icon { display: none; }

    .modal-glass-content h3 {
        font-family: var(--font-heading);
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0;
        padding: 1.5rem 1.5rem 0.5rem;
        color: #0f172a;
    }

    .modal-glass-body {
        padding: 0 1.5rem 1.5rem;
    }

    .modal-glass-body p {
        color: #64748b;
        font-size: 0.9rem;
        line-height: 1.5;
        margin: 0;
    }

    .modal-glass-body p + p {
        margin-top: 0.5rem;
    }

    .modal-glass-body strong {
        color: #0f172a;
        font-weight: 600;
    }

    .modal-glass-footer {
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        margin: 0;
    }

    .btn-cancel,
    .btn-confirm {
        flex: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
    }

    .btn-cancel {
        background: transparent;
        color: #64748b;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .btn-danger {
        background: #ef4444 !important;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
    }

    .btn-confirm {
        color: white;
    }

    .btn-confirm:hover {
        filter: brightness(0.9);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Mobile Menu Button */
    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.3rem;
        color: #1e293b;
        cursor: pointer;
        padding: 0.3rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .gm-header {
            padding: 0.75rem 1rem;
        }
        
        .mobile-menu-btn {
            display: block;
        }
        
        .gm-nav-links {
            display: none;
            width: 100%;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.8rem;
            padding: 1rem 0;
            border-top: 1px solid #e9edf2;
            margin-top: 0.5rem;
        }
        
        .gm-nav-links.active {
            display: flex;
        }
        
        .gm-header-right {
            margin-left: auto;
        }
        
        .welcome-text span {
            display: none;
        }
        
        .btn-outline, .btn-primary, .btn-logout {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 480px) {
        .welcome-text {
            display: none;
        }
        
        .gm-header-right {
            gap: 0.5rem;
        }
    }
</style>
</head>
<body>

<header class="gm-header">
    <div class="gm-logo">Greymist</div>
    
    <button class="mobile-menu-btn" id="mobileMenuBtn">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="gm-nav-links" id="navLinks">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active-nav' : '' ?>">Dashboard</a>
        <a href="explore.php" class="<?= $current_page == 'explore.php' ? 'active-nav' : '' ?>">Explore</a>
        <a href="project.php" class="<?= $current_page == 'project.php' ? 'active-nav' : '' ?>">Projects</a>
        <a href="templates.php" class="<?= $current_page == 'templates.php' ? 'active-nav' : '' ?>">Templates</a>
        <a href="trash.php" class="<?= $current_page == 'trash.php' ? 'active-nav' : '' ?>">Trash</a>
        <a href="about.php" class="<?= $current_page == 'about.php' ? 'active-nav' : '' ?>">About</a>
    </div>

    <div class="gm-header-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="welcome-text">
                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                <span>Halo, <?= htmlspecialchars($_SESSION['nickname'] ?? $_SESSION['username'] ?? 'User') ?></span>
            </div>
            
            <div class="user-avatar">
                <div class="avatar-icon">
                    <?php if ($user_profile && file_exists($user_profile)): ?>
                        <img src="<?= htmlspecialchars($user_profile) ?>" alt="Profile" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr(htmlspecialchars($_SESSION['nickname'] ?? $_SESSION['username'] ?? 'U'), 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="dropdown-menu">
                    <a href="../user/profile.php"><i class="fas fa-user"></i> Profil Saya</a>
                    <a href="../user/project.php"><i class="fas fa-folder"></i> Projects Saya</a>
                    <a href="../user/trash.php"><i class="fas fa-trash-can"></i> Sampah</a>
                    <!-- <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a> -->
                    <hr>
                    <a href="../session/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        <?php else: ?>
            <a href="../session/login.php" class="btn-outline">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
            <a href="../session/register.php" class="btn-primary">
                <i class="fas fa-user-plus"></i> Daftar
            </a>
        <?php endif; ?>
    </div>
</header>

<script>
// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            const icon = mobileBtn.querySelector('i');
            if (icon) {
                if (navLinks.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (navLinks && navLinks.classList.contains('active')) {
            if (!navLinks.contains(event.target) && !mobileBtn.contains(event.target)) {
                navLinks.classList.remove('active');
                const icon = mobileBtn.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        }
    });
});
</script>