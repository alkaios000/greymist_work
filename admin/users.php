<?php
require 'includes/auth.php';
require_once '../includes/db.php';

$page_title = 'Manage Users';

// Handle Add New User
if (isset($_POST['add_user'])) {
    $nickname = trim($_POST['nickname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    $errors = [];

    if (empty($nickname))
        $errors[] = "Nickname harus diisi";
    if (empty($username))
        $errors[] = "Username harus diisi";
    if (empty($email))
        $errors[] = "Email harus diisi";
    if (empty($password))
        $errors[] = "Password harus diisi";
    if (strlen($password) < 6)
        $errors[] = "Password minimal 6 karakter";

    $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch())
        $errors[] = "Username sudah digunakan";

    $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch())
        $errors[] = "Email sudah digunakan";

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO gm_users (nickname, username, email, password, is_admin, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$nickname, $username, $email, $hashedPassword, $is_admin]);

        header("Location: users.php?user_added=1");
        exit;
    } else {
        $add_error = implode(", ", $errors);
        header("Location: users.php?add_error=" . urlencode($add_error));
        exit;
    }
}

// Handle Update User (via AJAX or POST)
if (isset($_POST['update_user']) && isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];
    $nickname = trim($_POST['nickname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $new_password = trim($_POST['new_password']);

    $errors = [];

    if (empty($nickname))
        $errors[] = "Nickname harus diisi";
    if (empty($username))
        $errors[] = "Username harus diisi";
    if (empty($email))
        $errors[] = "Email harus diisi";

    // Cek username unik (kecuali untuk user ini)
    $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE username = ? AND user_id != ?");
    $stmt->execute([$username, $userId]);
    if ($stmt->fetch())
        $errors[] = "Username sudah digunakan";

    // Cek email unik (kecuali untuk user ini)
    $stmt = $pdo->prepare("SELECT user_id FROM gm_users WHERE email = ? AND user_id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch())
        $errors[] = "Email sudah digunakan";

    if (empty($errors)) {
        $updateSql = "UPDATE gm_users SET nickname = ?, username = ?, email = ?, is_admin = ?";
        $params = [$nickname, $username, $email, $is_admin];

        if (!empty($new_password)) {
            if (strlen($new_password) < 6) {
                $errors[] = "Password minimal 6 karakter";
            } else {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $updateSql .= ", password = ?";
                $params[] = $hashedPassword;
            }
        }

        $updateSql .= " WHERE user_id = ?";
        $params[] = $userId;

        if (empty($errors)) {
            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);
            header("Location: users.php?user_updated=1");
            exit;
        }
    }

    if (!empty($errors)) {
        header("Location: users.php?update_error=" . urlencode(implode(", ", $errors)));
        exit;
    }
}

// Handle Delete User
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];

    if ($userId == 1) {
        header("Location: users.php?error=main_admin");
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM gm_users WHERE user_id = ?");
    $stmt->execute([$userId]);

    header("Location: users.php?deleted=1");
    exit;
}

// Handle Reset Password
if (isset($_POST['reset_password']) && isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];
    $newPassword = password_hash('password123', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE gm_users SET password = ? WHERE user_id = ?");
    $stmt->execute([$newPassword, $userId]);

    header("Location: users.php?reset=1");
    exit;
}

// Handle Toggle Admin Role
if (isset($_POST['toggle_admin']) && isset($_POST['user_id'])) {
    $userId = (int) $_POST['user_id'];

    if ($userId == 1) {
        header("Location: users.php?error=main_admin_toggle");
        exit;
    }

    $stmt = $pdo->prepare("SELECT is_admin FROM gm_users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $current = $stmt->fetch();

    if ($current) {
        $newRole = ($current['is_admin'] == 1) ? 0 : 1;
        $update = $pdo->prepare("UPDATE gm_users SET is_admin = ? WHERE user_id = ?");
        $update->execute([$newRole, $userId]);

        header("Location: users.php?role_updated=1");
        exit;
    }
}

// Search & Filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

$sql = "SELECT user_id, nickname, username, email, profile, created_at, is_admin,
        (SELECT COUNT(*) FROM gm_projects WHERE user_id = gm_users.user_id) as total_projects,
        (SELECT COUNT(*) FROM gm_projects WHERE user_id = gm_users.user_id AND is_template = 1) as total_templates
        FROM gm_users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR nickname LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role === 'admin') {
    $sql .= " AND is_admin = 1";
} elseif ($role === 'user') {
    $sql .= " AND is_admin = 0";
}

switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY username ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY username DESC";
        break;
    case 'most_projects':
        $sql .= " ORDER BY total_projects DESC";
        break;
    case 'latest':
    default:
        $sql .= " ORDER BY created_at DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => $u['is_admin'] == 1));
$userCount = $totalUsers - $adminCount;

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
            <span style="color: var(--accent);">Analytics</span>
            <span style="color: #CBD5E1; margin: 0 4px;">/</span>
            <span class="active">Users</span>
        </div>
        
        <div class="top-actions" style="flex-wrap: wrap;">
            <div style="position: relative;">
                <input type="text" class="search-input" id="searchInput" placeholder="Search user..." value="<?= htmlspecialchars($search) ?>" style="padding-left: 2.2rem; width: 220px;">
                <i class="fas fa-search" style="position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.85rem; pointer-events: none;"></i>
            </div>
            
            <select id="roleFilter" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="">All Roles</option>
                <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="user" <?= $role == 'user' ? 'selected' : '' ?>>User</option>
            </select>
            
            <select id="sortSelect" class="btn-outline" style="appearance: none; padding-right: 2rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%236C48F5\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'6 9 12 15 18 9\'></polyline></svg>'); background-repeat: no-repeat; background-position: right 0.8rem center;">
                <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
                <option value="oldest" <?= $sort == 'oldest' ? 'selected' : '' ?>>Oldest</option>
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Name A-Z</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Name Z-A</option>
                <option value="most_projects" <?= $sort == 'most_projects' ? 'selected' : '' ?>>Most Projects</option>
            </select>
            
            <div style="width: 1px; height: 24px; background: var(--border-soft); margin: 0 4px;"></div>
            
            <button id="showAddUserBtn" class="btn-solid">
                <i class="fas fa-user-plus"></i> Add User
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['user_added'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>User baru berhasil ditambahkan!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['user_updated'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><span>User berhasil diperbarui!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success"><i class="fas fa-trash-alt"></i><span>User berhasil dihapus!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['reset'])): ?>
        <div class="alert alert-success"><i class="fas fa-key"></i><span>Password berhasil direset!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['role_updated'])): ?>
        <div class="alert alert-success"><i class="fas fa-user-shield"></i><span>Role user berhasil diperbarui!</span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['update_error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($_GET['update_error']) ?></span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['add_error'])): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><span><?= htmlspecialchars($_GET['add_error']) ?></span><button class="close-alert">&times;</button></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <?php if ($_GET['error'] == 'main_admin'): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><span>Admin utama tidak dapat dihapus!</span><button class="close-alert">&times;</button></div>
        <?php elseif ($_GET['error'] == 'main_admin_toggle'): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i><span>Role admin utama tidak dapat diubah!</span><button class="close-alert">&times;</button></div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Section Header with Stats -->
    <div class="section-header">
        <div class="section-title">
            <h3>Registered Users</h3>
            <p>Manage and track platform members</p>
        </div>
        <div style="display: flex; gap: 0.8rem; align-items: center;">
            <div class="nav-badge" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);"><i class="fas fa-users" style="color: var(--accent);"></i> <?= $totalUsers ?> Total</div>
            <div class="nav-badge" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);"><i class="fas fa-user-shield" style="color: var(--success);"></i> <?= $adminCount ?> Admins</div>
            <?php if (!empty($search) || !empty($role)): ?>
                <a href="users.php" style="color: #dc2626; text-decoration: none; font-size: 0.75rem; margin-left: 0.5rem;"><i class="fas fa-times"></i> Clear filters</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Users Table -->
    <div class="sensei-table-container">
        <table class="sensei-table">
            <thead>
                <tr>
                    <th>User / Email</th>
                    <th>Username</th>
                    <th style="text-align: center;">Projects</th>
                    <th style="text-align: center;">Templates</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="td-user">
                                <div class="td-avatar" style="cursor: pointer;" onclick="showFullImage('<?= $user['profile'] ? '../' . $user['profile'] : '' ?>', '<?= htmlspecialchars($user['nickname'] ?? $user['username']) ?>')">
                                    <?php if ($user['profile'] && file_exists('../' . $user['profile'])): ?>
                                        <img src="../<?= $user['profile'] ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($user['nickname'] ?? $user['username'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="td-info">
                                    <h4><?= htmlspecialchars($user['nickname'] ?? $user['username']) ?></h4>
                                    <p><?= htmlspecialchars($user['email'] ?? 'No email provided') ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="td-bold">@<?= htmlspecialchars($user['username']) ?></div>
                            <div class="td-muted">#<?= $user['user_id'] ?></div>
                        </td>
                        <td style="text-align: center;">
                            <span class="nav-badge" style="background: #F1F5F9; color: var(--text-main); font-size: 0.75rem; padding: 4px 10px;"><?= $user['total_projects'] ?></span>
                        </td>
                        <td style="text-align: center;">
                            <span class="nav-badge" style="background: var(--accent-light); color: var(--accent); font-size: 0.75rem; padding: 4px 10px;"><?= $user['total_templates'] ?></span>
                        </td>
                        <td>
                            <?php if ($user['is_admin']): ?>
                                <span class="nav-badge" style="background: var(--success-light); color: var(--success); font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-user-shield"></i> Admin</span>
                            <?php else: ?>
                                <span class="nav-badge" style="background: #F1F5F9; color: var(--text-muted); font-size: 0.75rem; padding: 4px 10px;"><i class="fas fa-user"></i> User</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="td-muted"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                        </td>
                        <td style="text-align: right;">
                            <div class="card-menu" style="display: inline-block;">
                                <button type="button" class="menu-trigger" onclick="toggleDropdown(this)" style="margin-left: auto;">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="card-dropdown" style="text-align: left; bottom: auto; top: 100%; right: 0;">
                                    <button class="dropdown-item" onclick="showUserDetail(<?= $user['user_id'] ?>)">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                    <button class="dropdown-item" onclick="showResetModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    <button class="dropdown-item" onclick="toggleAdminRole(<?= $user['user_id'] ?>, <?= $user['is_admin'] ?>)">
                                        <?php if ($user['is_admin']): ?>
                                            <i class="fas fa-user" style="color: #F59E0B;"></i> Demote to User
                                        <?php else: ?>
                                            <i class="fas fa-user-shield" style="color: var(--success);"></i> Promote to Admin
                                        <?php endif; ?>
                                    </button>
                                    <div style="border-top: 1px solid var(--border-soft); margin: 0.25rem 0;"></div>
                                    <button class="dropdown-item danger" onclick="showDeleteModal(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                        <i class="fas fa-trash-alt"></i> Delete User
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<style>
    .modal-content::-webkit-scrollbar {
        display: none;
    }
</style>

<!-- Add User Modal -->
<div id="addUserModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header" style="background: #f0fdf4;"><i class="fas fa-user-plus"></i>
            <h3>Tambah User Baru</h3>
        </div>
        <form method="post">
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1rem;"><label>Nickname</label><input type="text"
                        name="nickname" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;"></div>
                <div class="form-group" style="margin-bottom: 1rem;"><label>Username</label><input type="text"
                        name="username" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;"></div>
                <div class="form-group" style="margin-bottom: 1rem;"><label>Email</label><input type="email"
                        name="email" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;"></div>
                <div class="form-group" style="margin-bottom: 1rem;"><label>Password</label><input type="password"
                        name="password" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;"><small>Minimal
                        6 karakter</small></div>
                <div class="form-group"><label style="display:flex; align-items:center; gap:0.5rem;"><input
                            type="checkbox" name="is_admin" value="1"> <span>Jadikan Admin</span></label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn-cancel"
                    id="cancelAddUserBtn">Batal</button><button type="submit" name="add_user" class="btn-confirm"
                    style="background:#22c55e;">Tambah</button></div>
        </form>
    </div>
</div>

<!-- User Detail Modal (Popup Profile) -->
<div id="userDetailModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px; max-height: 80vh; overflow-y: auto;">
        <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #334155); color: white;">
            <i class="fas fa-user-circle"></i>
            <h3>User Profile Details</h3>
            <button id="closeDetailModal"
                style="position: absolute; right: 1rem; background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <form method="post" id="updateUserForm">
            <input type="hidden" name="user_id" id="detail_user_id">
            <div class="modal-body">
                <!-- Profile Picture -->
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <div class="profile-pic-container" style="position: relative; display: inline-block;">
                        <div id="detail_avatar_small"
                            style="width: 100px; height: 100px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 600; cursor: pointer; overflow: hidden;">
                            <!-- Small avatar -->
                        </div>
                        <div id="detail_avatar_full" style="display: none;"></div>
                        <p style="font-size: 0.7rem; color: #64748b; margin-top: 0.5rem;">Klik foto untuk melihat ukuran
                            penuh</p>
                    </div>
                </div>

                <!-- User Info -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>User ID</label>
                    <div style="padding: 0.5rem; background: #f1f5f9; border-radius: 0.5rem;" id="detail_user_id_text">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Nickname</label>
                    <input type="text" name="nickname" id="detail_nickname" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Username</label>
                    <input type="text" name="username" id="detail_username" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Email</label>
                    <input type="email" name="email" id="detail_email" required
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Password (kosongkan jika tidak diubah)</label>
                    <input type="password" name="new_password" id="detail_password"
                        style="width:100%; padding:0.7rem; border:1.5px solid #e9edf2; border-radius:0.5rem;">
                    <small>Minimal 6 karakter jika diisi</small>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Role</label>
                    <div>
                        <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <input type="radio" name="is_admin" value="1" id="detail_role_admin"> <span><i
                                    class="fas fa-user-shield"></i> Admin</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="is_admin" value="0" id="detail_role_user"> <span><i
                                    class="fas fa-user"></i> User</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Bergabung Sejak</label>
                    <div style="padding: 0.5rem; background: #f1f5f9; border-radius: 0.5rem;" id="detail_created_at">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Statistik</label>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div
                            style="flex: 1; padding: 0.5rem; background: #f1f5f9; border-radius: 0.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700;" id="detail_total_projects">0</div>
                            <div style="font-size: 0.7rem;">Total Projects</div>
                        </div>
                        <div
                            style="flex: 1; padding: 0.5rem; background: #f1f5f9; border-radius: 0.5rem; text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: 700;" id="detail_total_templates">0</div>
                            <div style="font-size: 0.7rem;">Templates</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" id="cancelDetailBtn">Tutup</button>
                <button type="submit" name="update_user" class="btn-confirm" style="background: #f59e0b;">Simpan
                    Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Full Image Modal -->
<div id="fullImageModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 90%; max-height: 90%; background: transparent; box-shadow: none;">
        <button id="closeImageModal"
            style="position: fixed; top: 20px; right: 30px; background: white; border: none; font-size: 2rem; cursor: pointer; border-radius: 50%; width: 40px; height: 40px;">&times;</button>
        <img id="fullImage" src=""
            style="max-width: 100%; max-height: 90vh; margin: auto; display: block; border-radius: 0.5rem; box-shadow: 0 20px 35px -12px rgba(0,0,0,0.3);">
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <h3>Hapus User</h3>
        <div class="modal-glass-body">
            <p>Hapus user <strong id="deleteUserName"></strong>?</p>
            <p style="color:#ef4444; font-size:0.8rem; margin-top:5px;">Semua data user akan terhapus secara permanen!</p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelDeleteBtn">Batal</button>
            <button class="btn-confirm btn-danger" id="confirmDeleteBtn">Hapus User</button>
        </div>
    </div>
</div>

<!-- Reset Modal -->
<div id="resetModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <h3>Reset Password</h3>
        <div class="modal-glass-body">
            <p>Reset password <strong id="resetUserName"></strong>?</p>
            <p>Password baru akan di-set menjadi: <strong>password123</strong></p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelResetBtn">Batal</button>
            <button class="btn-confirm" id="confirmResetBtn">Reset Password</button>
        </div>
    </div>
</div>

<!-- Toggle Admin Modal -->
<div id="toggleAdminModal" class="modal-glass" style="display: none;">
    <div class="modal-glass-content">
        <h3 id="toggleAdminTitle">Ubah Role</h3>
        <div class="modal-glass-body">
            <p id="toggleAdminMessage"></p>
        </div>
        <div class="modal-glass-footer">
            <button class="btn-cancel" id="cancelToggleBtn">Batal</button>
            <button class="btn-confirm" id="confirmToggleBtn" style="background:#f59e0b">Ya, Ubah Role</button>
        </div>
    </div>
</div>

<!-- Hidden Forms -->
<form id="deleteForm" method="post" style="display: none;"><input type="hidden" name="user_id" id="deleteUserId"><input
        type="hidden" name="delete_user" value="1"></form>
<form id="resetForm" method="post" style="display: none;"><input type="hidden" name="user_id" id="resetUserId"><input
        type="hidden" name="reset_password" value="1"></form>
<form id="toggleAdminForm" method="post" style="display: none;"><input type="hidden" name="user_id"
        id="toggleAdminUserId"><input type="hidden" name="toggle_admin" value="1"></form>

<script>
    // User Detail Popup
    let currentUserId = null;

    function showUserDetail(userId) {
        currentUserId = userId;

        // Fetch user data via AJAX
        fetch(`get_user.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('detail_user_id').value = user.user_id;
                    document.getElementById('detail_user_id_text').innerHTML = `<strong>#${user.user_id}</strong>`;
                    document.getElementById('detail_nickname').value = user.nickname || '';
                    document.getElementById('detail_username').value = user.username;
                    document.getElementById('detail_email').value = user.email || '';
                    document.getElementById('detail_created_at').innerHTML = user.created_at;
                    document.getElementById('detail_total_projects').innerHTML = user.total_projects;
                    document.getElementById('detail_total_templates').innerHTML = user.total_templates;

                    // Role radio
                    if (user.is_admin == 1) {
                        document.getElementById('detail_role_admin').checked = true;
                    } else {
                        document.getElementById('detail_role_user').checked = true;
                    }

                    // Avatar
                    const avatarContainer = document.getElementById('detail_avatar_small');
                    if (user.profile && user.profile !== '') {
                        avatarContainer.innerHTML = `<img src="../${user.profile}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onclick="showFullImage('../${user.profile}', '${user.username}')">`;
                    } else {
                        avatarContainer.innerHTML = `<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; font-size: 2rem; font-weight: 600;" onclick="showFullImage(null, '${user.username}')">${(user.nickname?.[0] || user.username[0]).toUpperCase()}</div>`;
                    }

                    document.getElementById('userDetailModal').style.display = 'flex';
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Close Detail Modal
    document.getElementById('closeDetailModal')?.addEventListener('click', () => document.getElementById('userDetailModal').style.display = 'none');
    document.getElementById('cancelDetailBtn')?.addEventListener('click', () => document.getElementById('userDetailModal').style.display = 'none');

    // Full Image Modal
    function showFullImage(imageUrl, userName) {
        const modal = document.getElementById('fullImageModal');
        const img = document.getElementById('fullImage');

        if (imageUrl && imageUrl !== '') {
            img.src = imageUrl;
        } else {
            img.src = '';
            img.alt = `No profile picture for ${userName}`;
        }
        modal.style.display = 'flex';
    }

    document.getElementById('closeImageModal')?.addEventListener('click', () => document.getElementById('fullImageModal').style.display = 'none');
    document.getElementById('fullImageModal')?.addEventListener('click', (e) => {
        if (e.target === document.getElementById('fullImageModal')) {
            document.getElementById('fullImageModal').style.display = 'none';
        }
    });

    // Add User Modal
    const addUserModal = document.getElementById('addUserModal');
    document.getElementById('showAddUserBtn')?.addEventListener('click', () => addUserModal.style.display = 'flex');
    document.getElementById('cancelAddUserBtn')?.addEventListener('click', () => addUserModal.style.display = 'none');

    // Delete Modal
    let currentDeleteId = null;
    function showDeleteModal(id, name) {
        currentDeleteId = id;
        document.getElementById('deleteUserName').textContent = name;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', () => { document.getElementById('deleteUserId').value = currentDeleteId; document.getElementById('deleteForm').submit(); });
    document.getElementById('cancelDeleteBtn')?.addEventListener('click', () => document.getElementById('deleteModal').style.display = 'none');

    // Reset Modal
    let currentResetId = null;
    function showResetModal(id, name) {
        currentResetId = id;
        document.getElementById('resetUserName').textContent = name;
        document.getElementById('resetModal').style.display = 'flex';
    }
    document.getElementById('confirmResetBtn')?.addEventListener('click', () => { document.getElementById('resetUserId').value = currentResetId; document.getElementById('resetForm').submit(); });
    document.getElementById('cancelResetBtn')?.addEventListener('click', () => document.getElementById('resetModal').style.display = 'none');

    // Toggle Admin Modal
    let currentToggleId = null;
    function toggleAdminRole(id, isAdmin) {
        currentToggleId = id;
        const title = document.getElementById('toggleAdminTitle');
        const msg = document.getElementById('toggleAdminMessage');
        if (isAdmin) {
            title.innerHTML = '<i class="fas fa-user"></i> Jadikan User Biasa';
            msg.innerHTML = `Apakah Anda yakin ingin menghapus role admin dari user ini? User akan menjadi regular user.`;
        } else {
            title.innerHTML = '<i class="fas fa-user-shield"></i> Jadikan Admin';
            msg.innerHTML = `Apakah Anda yakin ingin menjadikan user ini sebagai admin? User akan memiliki akses ke panel admin.`;
        }
        document.getElementById('toggleAdminModal').style.display = 'flex';
    }
    document.getElementById('confirmToggleBtn')?.addEventListener('click', () => { document.getElementById('toggleAdminUserId').value = currentToggleId; document.getElementById('toggleAdminForm').submit(); });
    document.getElementById('cancelToggleBtn')?.addEventListener('click', () => document.getElementById('toggleAdminModal').style.display = 'none');

    // Search & Filter
    const searchInput = document.getElementById('searchInput');
    const roleFilter = document.getElementById('roleFilter');
    const sortSelect = document.getElementById('sortSelect');
    let timeout;
    function applyFilters() {
        window.location.href = `users.php?search=${encodeURIComponent(searchInput.value)}&role=${roleFilter.value}&sort=${sortSelect.value}`;
    }
    searchInput?.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(applyFilters, 500); });
    roleFilter?.addEventListener('change', applyFilters);
    sortSelect?.addEventListener('change', applyFilters);

    // Close alerts
    document.querySelectorAll('.close-alert').forEach(btn => btn.addEventListener('click', function () { this.closest('.alert').remove(); }));
    setTimeout(() => document.querySelectorAll('.alert').forEach(alert => alert.remove()), 3000);

    // Close modals when clicking outside
    window.onclick = function (e) {
        if (e.target.classList.contains('modal')) e.target.style.display = 'none';
    }
</script>

<?php include 'includes/footer.php'; ?>