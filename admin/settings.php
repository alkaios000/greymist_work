<?php
require 'includes/auth.php';
require_once '../includes/db.php';

$page_title = 'Settings';

// Handle update site settings
$settingsFile = '../config/settings.json';
$settingsDir = '../config/';

// Buat folder config jika belum ada
if (!is_dir($settingsDir)) {
    mkdir($settingsDir, 0777, true);
}

// Default settings
$defaultSettings = [
    'site_name' => 'Greymist',
    'site_description' => 'Platform kreatif untuk desainer, developer, dan kreator',
    'site_keywords' => 'design, development, creative, platform, greymist',
    'maintenance_mode' => false,
    'registration_open' => true,
    'items_per_page' => 12,
    'default_theme' => 'light',
    'default_sort' => 'latest',
    'email_notifications' => true,
    'admin_email' => 'admin@greymist.com'
];

// Load settings
$settings = $defaultSettings;
if (file_exists($settingsFile)) {
    $loadedSettings = json_decode(file_get_contents($settingsFile), true);
    if ($loadedSettings) {
        $settings = array_merge($defaultSettings, $loadedSettings);
    }
}

// Handle save settings
if (isset($_POST['save_settings'])) {
    $settings['site_name'] = trim($_POST['site_name']);
    $settings['site_description'] = trim($_POST['site_description']);
    $settings['site_keywords'] = trim($_POST['site_keywords']);
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? true : false;
    $settings['registration_open'] = isset($_POST['registration_open']) ? true : false;
    $settings['items_per_page'] = (int)$_POST['items_per_page'];
    $settings['default_theme'] = $_POST['default_theme'];
    $settings['default_sort'] = $_POST['default_sort'];
    $settings['email_notifications'] = isset($_POST['email_notifications']) ? true : false;
    $settings['admin_email'] = trim($_POST['admin_email']);
    
    file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    
    header("Location: settings.php?updated=1");
    exit;
}

// Handle clear cache
if (isset($_POST['clear_cache'])) {
    $cacheDir = '../cache/';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    header("Location: settings.php?cache_cleared=1");
    exit;
}

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
            <span style="color: var(--accent);">General</span>
            <span style="color: #CBD5E1; margin: 0 4px;">/</span>
            <span class="active">Settings</span>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>Pengaturan berhasil disimpan!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['cache_cleared'])): ?>
    <div class="alert alert-success"><i class="fas fa-trash-alt"></i><span>Cache berhasil dibersihkan!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>

    <!-- Section Header -->
    <div class="section-header">
        <div class="section-title">
            <h3>System Settings</h3>
            <p>Configure platform preferences and maintenance</p>
        </div>
    </div>

    <form method="post">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
            
            <!-- General Settings -->
            <div style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); box-shadow: 0 10px 25px -5px rgba(108, 72, 245, 0.05); overflow: hidden;">
                <div style="padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-soft);">
                    <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-globe" style="color: var(--accent);"></i> General Settings</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Site Name</label>
                        <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Site Description</label>
                        <textarea name="site_description" rows="3" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit; resize: vertical;"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Site Keywords (SEO)</label>
                        <input type="text" name="site_keywords" value="<?= htmlspecialchars($settings['site_keywords']) ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit;">
                        <small style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-top: 0.3rem;">Pisahkan dengan koma</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Admin Email</label>
                        <input type="email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email']) ?>" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit;">
                    </div>
                </div>
            </div>

            <!-- Appearance Settings -->
            <div style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); box-shadow: 0 10px 25px -5px rgba(108, 72, 245, 0.05); overflow: hidden;">
                <div style="padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-soft);">
                    <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-palette" style="color: var(--accent);"></i> Appearance</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Default Theme</label>
                        <select name="default_theme" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394A3B8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="light" <?= $settings['default_theme'] == 'light' ? 'selected' : '' ?>>Light</option>
                            <option value="dark" <?= $settings['default_theme'] == 'dark' ? 'selected' : '' ?>>Dark</option>
                            <option value="system" <?= $settings['default_theme'] == 'system' ? 'selected' : '' ?>>System Default</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Items Per Page</label>
                        <select name="items_per_page" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394A3B8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="12" <?= $settings['items_per_page'] == 12 ? 'selected' : '' ?>>12 items</option>
                            <option value="24" <?= $settings['items_per_page'] == 24 ? 'selected' : '' ?>>24 items</option>
                            <option value="48" <?= $settings['items_per_page'] == 48 ? 'selected' : '' ?>>48 items</option>
                            <option value="96" <?= $settings['items_per_page'] == 96 ? 'selected' : '' ?>>96 items</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 500; margin-bottom: 0.4rem; color: var(--text-main);">Default Sort</label>
                        <select name="default_sort" style="width:100%; padding:0.7rem 1rem; border:1.5px solid var(--border-soft); border-radius:8px; font-family: inherit; appearance: none; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2394A3B8\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 1rem center;">
                            <option value="latest" <?= $settings['default_sort'] == 'latest' ? 'selected' : '' ?>>Terbaru</option>
                            <option value="oldest" <?= $settings['default_sort'] == 'oldest' ? 'selected' : '' ?>>Terlama</option>
                            <option value="name_asc" <?= $settings['default_sort'] == 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                            <option value="name_desc" <?= $settings['default_sort'] == 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- System Settings -->
            <div style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); box-shadow: 0 10px 25px -5px rgba(108, 72, 245, 0.05); overflow: hidden;">
                <div style="padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-soft);">
                    <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-server" style="color: var(--accent);"></i> System Settings</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer;">
                            <input type="checkbox" name="maintenance_mode" value="1" <?= $settings['maintenance_mode'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent);">
                            <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-main);">Maintenance Mode</span>
                        </label>
                        <small style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-left: 1.8rem; margin-top: 0.2rem;">Jika diaktifkan, hanya admin yang bisa mengakses website</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer;">
                            <input type="checkbox" name="registration_open" value="1" <?= $settings['registration_open'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent);">
                            <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-main);">Registration Open</span>
                        </label>
                        <small style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-left: 1.8rem; margin-top: 0.2rem;">Jika dimatikan, user baru tidak bisa mendaftar</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer;">
                            <input type="checkbox" name="email_notifications" value="1" <?= $settings['email_notifications'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--accent);">
                            <span style="font-size: 0.9rem; font-weight: 500; color: var(--text-main);">Email Notifications</span>
                        </label>
                        <small style="font-size: 0.7rem; color: var(--text-muted); display: block; margin-left: 1.8rem; margin-top: 0.2rem;">Kirim notifikasi email untuk aktivitas penting</small>
                    </div>
                </div>
            </div>

            <!-- Maintenance Tools -->
            <div style="background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); box-shadow: 0 10px 25px -5px rgba(108, 72, 245, 0.05); overflow: hidden;">
                <div style="padding: 1.2rem 1.5rem; border-bottom: 1px solid var(--border-soft);">
                    <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-tools" style="color: var(--accent);"></i> Maintenance Tools</h3>
                </div>
                <div style="padding: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <button type="submit" name="clear_cache" class="btn-outline" style="width: 100%; border-color: var(--border-soft); color: var(--text-main); font-weight: 500; justify-content: center;">
                            <i class="fas fa-trash-alt"></i> Clear Cache
                        </button>
                        <small style="font-size: 0.7rem; color: var(--text-muted); display: block; text-align: center; margin-top: 0.5rem;">Membersihkan semua file cache sistem</small>
                    </div>
                    <div class="form-group">
                        <a href="../index.php" target="_blank" class="btn-outline" style="display: flex; justify-content: center; text-decoration: none; border-color: var(--border-soft); color: var(--text-main); font-weight: 500;">
                            <i class="fas fa-external-link-alt"></i> View Website
                        </a>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div style="grid-column: span 2;">
                <button type="submit" name="save_settings" class="btn-solid" style="width: 100%; justify-content: center; padding: 1rem; font-size: 1rem; border-radius: 10px;">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </div>
    </form>

    <!-- System Info -->
    <div style="margin-top: 2rem; background: var(--bg-card); border-radius: 12px; border: 1px solid var(--border-soft); padding: 1.5rem; box-shadow: 0 10px 25px -5px rgba(108, 72, 245, 0.05);">
        <h4 style="margin-bottom: 1.2rem; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main); font-size: 1rem;"><i class="fas fa-info-circle" style="color: var(--text-muted);"></i> System Information</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.85rem; color: var(--text-muted);">
            <div><strong style="color: var(--text-main);">PHP Version:</strong> <?= phpversion() ?></div>
            <div><strong style="color: var(--text-main);">Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></div>
            <div><strong style="color: var(--text-main);">Database:</strong> MySQL <?= $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) ?></div>
            <div><strong style="color: var(--text-main);">Upload Max Size:</strong> <?= ini_get('upload_max_filesize') ?></div>
            <div><strong style="color: var(--text-main);">Memory Limit:</strong> <?= ini_get('memory_limit') ?></div>
            <div><strong style="color: var(--text-main);">Max Execution Time:</strong> <?= ini_get('max_execution_time') ?>s</div>
        </div>
    </div>
</main>

<style>
    .form-group label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.3rem;
        display: block;
    }
    .btn-save:hover {
        background: #d97706 !important;
        transform: translateY(-1px);
    }
    .btn-outline:hover {
        background: #e2e8f0 !important;
    }
</style>

<script>
// Close alerts
document.querySelectorAll('.close-alert').forEach(btn => {
    btn.addEventListener('click', function() {
        this.closest('.alert').remove();
    });
});

setTimeout(() => {
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 3000);
</script>

<?php include 'includes/footer.php'; ?>