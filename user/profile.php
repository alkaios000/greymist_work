<?php
// Mulai output buffering
ob_start();
require '../includes/auth.php';
require '../includes/db.php';

$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $pdo->prepare("SELECT user_id, nickname, username, email, profile, created_at FROM GM_USERS WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../session/login.php");
    exit;
}

// Handle update profile
if (isset($_POST['update_profile'])) {
    $nickname = trim($_POST['nickname']);
    $email = trim($_POST['email']);
    
    $errors = [];
    
    if (empty($nickname)) {
        $errors[] = "Nickname tidak boleh kosong";
    }
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Cek email sudah dipakai user lain
    $checkEmail = $pdo->prepare("SELECT user_id FROM GM_USERS WHERE email = ? AND user_id != ?");
    $checkEmail->execute([$email, $user_id]);
    if ($checkEmail->fetch()) {
        $errors[] = "Email sudah digunakan oleh akun lain";
    }
    
    if (empty($errors)) {
        $update = $pdo->prepare("UPDATE GM_USERS SET nickname = ?, email = ? WHERE user_id = ?");
        $update->execute([$nickname, $email, $user_id]);
        
        $_SESSION['nickname'] = $nickname;
        
        ob_end_clean();
        header("Location: profile.php?success=profile_updated");
        exit;
    }
}

// Handle update password
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Verifikasi password lama
    $checkPass = $pdo->prepare("SELECT password FROM GM_USERS WHERE user_id = ?");
    $checkPass->execute([$user_id]);
    $userPass = $checkPass->fetch();
    
    if (!password_verify($current_password, $userPass['password'])) {
        $errors[] = "Password saat ini salah";
    }
    
    if (strlen($new_password) < 6) {
        $errors[] = "Password baru minimal 6 karakter";
    }
    
    if ($new_password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE GM_USERS SET password = ? WHERE user_id = ?");
        $update->execute([$hashed_password, $user_id]);
        
        ob_end_clean();
        header("Location: profile.php?success=password_updated");
        exit;
    }
}

// Handle upload profile picture
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
        $upload_path = '../assets/profiles/' . $new_filename;
        
        // Buat folder jika belum ada
        if (!is_dir('../assets/profiles')) {
            mkdir('../assets/profiles', 0777, true);
        }
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // Simpan path relatif terhadap root di database
            $db_path = 'assets/profiles/' . $new_filename;
            $update = $pdo->prepare("UPDATE GM_USERS SET profile = ? WHERE user_id = ?");
            $update->execute([$db_path, $user_id]);
            
            ob_end_clean();
            header("Location: profile.php?success=profile_updated");
            exit;
        }
    }
}

// Handle delete profile picture
if (isset($_POST['delete_profile_picture'])) {
    if ($user['profile'] && file_exists('../' . $user['profile'])) {
        unlink('../' . $user['profile']);
    }
    $update = $pdo->prepare("UPDATE GM_USERS SET profile = NULL WHERE user_id = ?");
    $update->execute([$user_id]);
    
    ob_end_clean();
    header("Location: profile.php?success=profile_updated");
    exit;
}

// Ambil statistik user
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN project_status = 'published' THEN 1 ELSE 0 END) as published_projects,
        SUM(CASE WHEN is_template = 1 THEN 1 ELSE 0 END) as templates
    FROM GM_PROJECTS 
    WHERE user_id = ?
");
$stmtStats->execute([$user_id]);
$stats = $stmtStats->fetch();

$totalProjects = $stats['total_projects'] ?? 0;
$publishedProjects = $stats['published_projects'] ?? 0;
$totalTemplates = $stats['templates'] ?? 0;

require '../includes/header.php';
?>

<main class="gm-profile-page">
    <!-- Header -->
    <div class="profile-header">
        <div class="profile-header-content">
            <h1 class="profile-title">
                <i class="fas fa-user-circle"></i>
                My Profile
            </h1>
            <p class="profile-subtitle">Kelola informasi akun dan preferensi Anda</p>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] == 'profile_updated'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Profil berhasil diperbarui!</span>
                <button class="close-alert">&times;</button>
            </div>
        <?php elseif ($_GET['success'] == 'password_updated'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Password berhasil diubah!</span>
                <button class="close-alert">&times;</button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <ul style="margin: 0; padding-left: 1rem;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button class="close-alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="profile-container">
        <!-- Left Column - Profile Picture & Stats -->
        <div class="profile-sidebar">
            <div class="profile-picture-card">
                <div class="profile-picture-wrapper">
                    <?php if ($user['profile'] && file_exists('../' . $user['profile'])): ?>
                        <img src="../<?= htmlspecialchars($user['profile']) ?>" alt="Profile Picture" class="profile-img">
                    <?php else: ?>
                        <div class="profile-img-placeholder">
                            <i class="fas fa-user-astronaut"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-picture-actions">
                        <form method="post" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                            <label for="profile_picture" class="upload-btn">
                                <i class="fas fa-camera"></i>
                                <span>Ganti Foto</span>
                            </label>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display: none;">
                        </form>
                        
                        <?php if ($user['profile'] && file_exists($user['profile'])): ?>
                            <form method="post" class="delete-form" id="deleteForm">
                                <button type="submit" name="delete_profile_picture" class="delete-photo-btn" onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?')">
                                    <i class="fas fa-trash-alt"></i>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="profile-info">
                    <h3><?= htmlspecialchars($user['nickname']) ?></h3>
                    <p class="username">@<?= htmlspecialchars($user['username']) ?></p>
                    <p class="member-since">
                        <i class="far fa-calendar-alt"></i>
                        Bergabung sejak <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
            </div>

            <!-- Stats Card -->
            <div class="stats-card">
                <h4><i class="fas fa-chart-line"></i> Statistik Saya</h4>
                <div class="stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Total Projects</span>
                        <span class="stat-value"><?= $totalProjects ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Published Projects</span>
                        <span class="stat-value"><?= $publishedProjects ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Templates</span>
                        <span class="stat-value"><?= $totalTemplates ?></span>
                    </div>
                </div>
                <div class="stats-actions">
                    <a href="project.php" class="btn-sm">
                        <i class="fas fa-folder-open"></i> Lihat Projects
                    </a>
                </div>
            </div>
        </div>

        <!-- Right Column - Edit Forms -->
        <div class="profile-forms">
            <!-- Edit Profile Form -->
            <div class="form-card">
                <div class="form-header">
                    <i class="fas fa-user-edit"></i>
                    <h3>Informasi Profil</h3>
                </div>
                
                <form method="post" class="profile-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-with-icon">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" disabled class="disabled-input">
                        </div>
                        <small>Username tidak dapat diubah</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="nickname">Nickname</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nickname" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="form-card">
                <div class="form-header">
                    <i class="fas fa-lock"></i>
                    <h3>Ganti Password</h3>
                </div>
                
                <form method="post" class="password-form">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <div class="input-with-icon">
                            <i class="fas fa-key"></i>
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="toggle-password" data-target="current_password">
                                <i class="far fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="new_password" name="new_password" required>
                            <button type="button" class="toggle-password" data-target="new_password">
                                <i class="far fa-eye-slash"></i>
                            </button>
                        </div>
                        <small>Minimal 6 karakter</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="input-with-icon">
                            <i class="fas fa-check-circle"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <button type="button" class="toggle-password" data-target="confirm_password">
                                <i class="far fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn-save">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="form-card danger-zone">
                <div class="form-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Danger Zone</h3>
                </div>
                <p>Hapus akun Anda secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
                <button class="btn-danger" id="deleteAccountBtn">
                    <i class="fas fa-trash-alt"></i> Hapus Akun
                </button>
            </div>
        </div>
    </div>
</main>

<!-- Delete Account Modal -->
<div id="deleteAccountModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header danger-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Hapus Akun</h3>
        </div>
        <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus akun <strong><?= htmlspecialchars($user['username']) ?></strong>?</p>
            <p class="modal-warning">Tindakan ini akan menghapus semua project dan data Anda secara permanen. Tidak dapat dikembalikan!</p>
            
            <form method="post" id="deleteAccountForm" action="../session/delete_account.php">
                <div class="form-group">
                    <label for="confirm_username">Ketik username <strong><?= htmlspecialchars($user['username']) ?></strong> untuk konfirmasi:</label>
                    <input type="text" id="confirm_username" name="confirm_username" required>
                </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancel" id="cancelDeleteAccountBtn">Batal</button>
            <button type="submit" class="btn-danger confirm" id="confirmDeleteAccountBtn" disabled>Ya, Hapus Akun Saya</button>
            </form>
        </div>
    </div>
</div>

<style>
/* ========== PROFILE PAGE STYLES ========== */

.gm-profile-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 1.5rem;
    animation: fadeInUp 0.5s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Profile Header */
.profile-header {
    margin-bottom: 2rem;
}

.profile-title {
    font-size: 1.75rem;
    font-weight: 700;
    background: linear-gradient(135deg, #0d6efd, #00d4ff);
    background-clip: text;
    -webkit-background-clip: text;
    color: transparent;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.profile-subtitle {
    color: #64748b;
    font-size: 0.85rem;
}

/* Alert */
.alert {
    padding: 0.75rem 1rem;
    border-radius: 0.8rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
    font-size: 1rem;
}

.alert span, .alert ul {
    flex: 1;
    font-size: 0.8rem;
}

.close-alert {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    color: inherit;
    opacity: 0.6;
    padding: 0 0.3rem;
}

.close-alert:hover {
    opacity: 1;
}

/* Profile Container */
.profile-container {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 1.5rem;
}

/* Sidebar */
.profile-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-picture-card, .stats-card, .form-card {
    background: white;
    border-radius: 1rem;
    border: 1px solid #e9edf2;
    overflow: hidden;
}

.profile-picture-card {
    text-align: center;
    padding: 1.5rem;
}

.profile-picture-wrapper {
    position: relative;
    margin-bottom: 1rem;
}

.profile-img, .profile-img-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto;
}

.profile-img-placeholder {
    background: linear-gradient(135deg, #e6f7ff, #cce5ff);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #0d6efd;
}

.profile-picture-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 0.5rem;
}

.upload-btn, .delete-photo-btn {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.7rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s;
}

.upload-btn:hover {
    background: #f1f5f9;
}

.delete-photo-btn {
    background: #fef2f2;
    border-color: #fee2e2;
    color: #dc2626;
}

.delete-photo-btn:hover {
    background: #fee2e2;
}

.profile-info h3 {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.profile-info .username {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.member-since {
    font-size: 0.7rem;
    color: #94a3b8;
}

/* Stats Card */
.stats-card {
    padding: 1.2rem;
}

.stats-card h4 {
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    margin-bottom: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9edf2;
}

.stat-label {
    font-size: 0.8rem;
    color: #64748b;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

.stats-actions {
    text-align: center;
}

.btn-sm {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    text-decoration: none;
    color: #475569;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.2s;
}

.btn-sm:hover {
    background: #f1f5f9;
}

/* Forms */
.profile-forms {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-card {
    padding: 1.5rem;
}

.form-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.2rem;
    padding-bottom: 0.8rem;
    border-bottom: 1px solid #e9edf2;
}

.form-header i {
    font-size: 1.2rem;
    color: #1e293b;
}

.form-header h3 {
    font-size: 1rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: #1e293b;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-with-icon i:first-child {
    position: absolute;
    left: 0.8rem;
    color: #94a3b8;
    font-size: 0.8rem;
}

.input-with-icon input {
    width: 100%;
    padding: 0.7rem 0.8rem 0.7rem 2.2rem;
    border: 1.5px solid #e9edf2;
    border-radius: 0.6rem;
    font-size: 0.85rem;
    outline: none;
    transition: all 0.2s;
}

.input-with-icon input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.08);
}

.input-with-icon .disabled-input {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}

.toggle-password {
    position: absolute;
    right: 0.8rem;
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.8rem;
}

.form-group small {
    font-size: 0.65rem;
    color: #94a3b8;
    margin-top: 0.25rem;
    display: block;
}

.btn-save {
    background: linear-gradient(135deg, #0d6efd, #00d4ff);
    color: white;
    border: none;
    padding: 0.7rem 1.2rem;
    border-radius: 0.6rem;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    width: 100%;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.2);
}

.btn-save:hover {
    background: linear-gradient(135deg, #0b5ed7, #00bfff);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
}

/* Danger Zone */
.danger-zone {
    border-color: #fee2e2;
    background: #fef2f2;
}

.danger-zone .form-header i,
.danger-zone .form-header h3 {
    color: #dc2626;
}

.danger-zone p {
    font-size: 0.8rem;
    color: #991b1b;
    margin-bottom: 1rem;
}

.btn-danger {
    background: #dc2626;
    color: white;
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 0.6rem;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-danger:hover {
    background: #b91c1c;
}

/* Modal */
.modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100000;
}

.modal-content {
    background: white;
    border-radius: 0.8rem;
    max-width: 450px;
    width: 90%;
    overflow: hidden;
}

.modal-header {
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    border-bottom: 1px solid #e9edf2;
}

.modal-header.danger-header {
    background: #fef2f2;
    color: #991b1b;
}

.modal-header i {
    font-size: 1.3rem;
}

.modal-header h3 {
    font-size: 1rem;
}

.modal-body {
    padding: 1rem;
}

.modal-body p {
    color: #475569;
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.modal-warning {
    font-size: 0.75rem;
    color: #dc2626;
    background: #fef2f2;
    padding: 0.4rem;
    border-radius: 0.4rem;
    margin-top: 0.5rem;
}

.modal-footer {
    padding: 0.8rem 1rem;
    display: flex;
    gap: 0.6rem;
    border-top: 1px solid #e9edf2;
}

.btn-cancel, .btn-danger.confirm {
    flex: 1;
    padding: 0.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-size: 0.8rem;
}

.btn-cancel {
    background: #f1f5f9;
    color: #475569;
}

.btn-cancel:hover {
    background: #e2e8f0;
}

.btn-danger.confirm {
    background: #dc2626;
    color: white;
}

.btn-danger.confirm:disabled {
    background: #fecaca;
    cursor: not-allowed;
}

.btn-danger.confirm:hover:not(:disabled) {
    background: #b91c1c;
}

/* Responsive */
@media (max-width: 900px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        max-width: 400px;
        margin: 0 auto;
        width: 100%;
    }
}

@media (max-width: 640px) {
    .gm-profile-page {
        padding: 1rem;
    }
    
    .profile-title {
        font-size: 1.4rem;
    }
    
    .form-card {
        padding: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleBtns = document.querySelectorAll('.toggle-password');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    });
    
    // Upload profile picture
    const fileInput = document.getElementById('profile_picture');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                document.getElementById('uploadForm').submit();
            }
        });
    }
    
    // Close alerts
    document.querySelectorAll('.close-alert').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
    
    // Auto close alerts after 3 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
    }, 3000);
    
    // Delete account modal
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const deleteAccountModal = document.getElementById('deleteAccountModal');
    const cancelDeleteAccountBtn = document.getElementById('cancelDeleteAccountBtn');
    const confirmDeleteAccountBtn = document.getElementById('confirmDeleteAccountBtn');
    const confirmUsernameInput = document.getElementById('confirm_username');
    const currentUsername = '<?= htmlspecialchars($user['username']) ?>';
    
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            if (deleteAccountModal) deleteAccountModal.style.display = 'flex';
        });
    }
    
    if (cancelDeleteAccountBtn) {
        cancelDeleteAccountBtn.addEventListener('click', function() {
            if (deleteAccountModal) deleteAccountModal.style.display = 'none';
            if (confirmUsernameInput) confirmUsernameInput.value = '';
            if (confirmDeleteAccountBtn) confirmDeleteAccountBtn.disabled = true;
        });
    }
    
    if (confirmUsernameInput) {
        confirmUsernameInput.addEventListener('input', function() {
            if (confirmDeleteAccountBtn) {
                confirmDeleteAccountBtn.disabled = this.value !== currentUsername;
            }
        });
    }
    
    if (deleteAccountModal) {
        deleteAccountModal.addEventListener('click', function(e) {
            if (e.target === deleteAccountModal) {
                deleteAccountModal.style.display = 'none';
                if (confirmUsernameInput) confirmUsernameInput.value = '';
                if (confirmDeleteAccountBtn) confirmDeleteAccountBtn.disabled = true;
            }
        });
    }
});
</script>

<?php require '../includes/footer.php'; 
if (ob_get_level()) ob_end_flush();
?>