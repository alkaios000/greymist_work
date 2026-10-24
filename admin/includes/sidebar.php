<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-icon">
            <i class="fas fa-layer-group"></i>
        </div>
        <h2>Greymist</h2>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">Team Workspace</div>
        
        <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </div>
        </a>
        
        <a href="users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </div>
        </a>
        
        <div class="nav-section">Management</div>
        
        <a href="projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <i class="fas fa-folder-open"></i>
                <span>Projects</span>
            </div>
        </a>
        
        <a href="templates.php" class="<?= basename($_SERVER['PHP_SELF']) == 'templates.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <i class="fa-solid fa-diagram-project"></i>
                <span>Templates</span>
            </div>
            <span class="nav-badge">New</span>
        </a>
        
        <div class="nav-section">General</div>
        
        <a href="../index.php">
            <div class="nav-item-left">
                <i class="fas fa-globe"></i>
                <span>View Site</span>
            </div>
        </a>
        
        <a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <div class="nav-item-left">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </div>
        </a>
        
        <a href="logout.php" style="margin-top: 1rem;">
            <div class="nav-item-left">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </div>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="trial-card">
            <div class="trial-header">
                <span>20 / 30 Days</span>
                <span class="trial-badge">Free Trial</span>
            </div>
            <div class="progress-bar"><div class="progress-fill"></div></div>
        </div>
        
        <div class="user-profile">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['admin_nickname'] ?? 'A', 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($_SESSION['admin_nickname'] ?? 'Admin') ?></div>
                <div class="user-role">admin@greymist.com</div>
            </div>
            <i class="fas fa-chevron-up" style="color: #94A3B8; font-size: 0.8rem; margin-left: auto;"></i>
        </div>
    </div>
</aside>