<?php
/**
 * File: index.php
 * Fungsi: Pintu masuk utama (Front Controller) aplikasi Greymist.
 *         Mengatur routing terpusat berdasarkan whitelist ?action=... dan ?page=...
 */

require_once __DIR__ . '/config/config.php';
require_once ROOT_PATH . '/models/user_model.php';
require_once ROOT_PATH . '/models/project_model.php';
require_once ROOT_PATH . '/models/frame_model.php';
require_once ROOT_PATH . '/models/component_model.php';
require_once ROOT_PATH . '/models/asset_model.php';

// 1. Penanganan Routing Aksi Controller (?action=...)
$requested_action = $_GET['action'] ?? $_POST['action'] ?? '';

$action_whitelist = [
    // Auth Actions
    'login'                  => 'auth_action.php',
    'register'               => 'auth_action.php',
    'logout'                 => 'auth_action.php',
    'admin_login'            => 'auth_action.php',
    'admin_logout'           => 'auth_action.php',
    'delete_account'         => 'auth_action.php',

    // Project Actions
    'new_project'            => 'project_action.php',
    'rename_project'         => 'project_action.php',
    'toggle_status'          => 'project_action.php',
    'toggle_template'        => 'project_action.php',
    'clone_project'          => 'project_action.php',
    'clone_template'         => 'project_action.php',
    'delete_project'         => 'project_action.php',
    'restore_project'        => 'project_action.php',
    'permanent_delete'       => 'project_action.php',
    'get_project'            => 'project_action.php',
    'save_project'           => 'project_action.php',
    'get_color_tokens'       => 'project_action.php',
    'save_color_tokens'      => 'project_action.php',
    'get_bg_color_settings'  => 'project_action.php',
    'save_bg_color_settings' => 'project_action.php',

    // Frame Actions
    'get_frames'             => 'frame_action.php',
    'add_frame'              => 'frame_action.php',
    'copy_frame'             => 'frame_action.php',
    'rename_frame'           => 'frame_action.php',
    'delete_frame'           => 'frame_action.php',
    'save_frame'             => 'frame_action.php',
    'save_cores'             => 'frame_action.php',

    // Component Actions
    'get_components'         => 'component_action.php',
    'save_component'         => 'component_action.php',
    'delete_component'       => 'component_action.php',

    // Gemini AI Workspace Plugin Actions
    'ai_generate_component'      => 'ai_action.php',
    'ai_save_api_key'            => 'ai_action.php',
    'ai_get_memory'              => 'ai_action.php',
    'ai_get_provider_settings'   => 'ai_action.php',
    'ai_save_provider_settings'  => 'ai_action.php',
    'ai_test_provider_connection'=> 'ai_action.php',

    // Asset Actions
    'get_images'             => 'asset_action.php',
    'upload_assets_image'    => 'asset_action.php',
    'upload_background_image'=> 'asset_action.php',
    'update_image_name'      => 'asset_action.php',
    'delete_image'           => 'asset_action.php',

    // Profile Actions
    'update_profile'         => 'profile_action.php',
    'update_password'        => 'profile_action.php',
    'upload_profile_picture' => 'profile_action.php',
    'delete_profile_picture' => 'profile_action.php',

    // Admin Actions
    'admin_get_user'         => 'admin_action.php',
    'admin_get_project'      => 'admin_action.php',
    'admin_add_user'         => 'admin_action.php',
    'admin_update_user'      => 'admin_action.php',
    'admin_reset_password'   => 'admin_action.php',
    'admin_toggle_admin'     => 'admin_action.php',
    'admin_delete_user'      => 'admin_action.php',
    'admin_delete_project'   => 'admin_action.php',
    'admin_toggle_status'    => 'admin_action.php',
    'admin_toggle_template'  => 'admin_action.php',
    'admin_remove_template'  => 'admin_action.php',
    'admin_save_settings'    => 'admin_action.php',
    'admin_clear_cache'      => 'admin_action.php',
];

if ($requested_action !== '' && isset($action_whitelist[$requested_action])) {
    $controller_file = ROOT_PATH . '/app/controllers/' . $action_whitelist[$requested_action];
    if (is_file($controller_file)) {
        require $controller_file;
        exit;
    }
}

// 2. Penanganan Routing Halaman (?page=...)
$page = $_GET['page'] ?? '';
if ($page === '') {
    if (isset($_GET['project_id']) && is_numeric($_GET['project_id'])) {
        $page = 'workspace';
    } else {
        $page = is_logged_in() ? 'dashboard' : 'login';
    }
}

// Daftar halaman yang diizinkan (Whitelist)
$allowed_pages = [
    'login',
    'register',
    'logout',
    'dashboard',
    'projects',
    'explore',
    'templates',
    'trash',
    'profile',
    'about',
    'workspace',
    'admin_login',
    'admin_dashboard',
    'admin_users',
    'admin_projects',
    'admin_templates',
    'admin_settings',
    'admin_reports',
    'admin_preview_project',
];

if (!in_array($page, $allowed_pages, true)) {
    http_response_code(404);
    require ROOT_PATH . '/views/errors/404.php';
    exit;
}

// 3. Eksekusi Middleware & Persiapan Data untuk View
switch ($page) {
    case 'login':
        require_guest();
        require ROOT_PATH . '/views/pages/login.php';
        break;

    case 'register':
        require_guest();
        require ROOT_PATH . '/views/pages/register.php';
        break;

    case 'logout':
        $_GET['action'] = 'logout';
        require ROOT_PATH . '/app/controllers/auth_action.php';
        break;

    case 'admin_login':
        if (is_admin()) {
            redirect(route_url('admin_dashboard'));
        }
        require ROOT_PATH . '/views/pages/admin_login.php';
        break;

    case 'workspace':
        require_auth();
        $user_id    = current_user_id();
        $project_id = isset($_GET['project_id']) && is_numeric($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

        if ($project_id <= 0) {
            $new_id = create_project_with_default_frame($pdo, $user_id, 'Untitled Project');
            redirect(route_url('workspace', ['project_id' => $new_id]));
        }

        // Izinkan pemilik proyek atau Administrator membuka workspace
        $project = is_admin()
            ? get_project_by_id($pdo, $project_id)
            : get_user_project($pdo, $project_id, $user_id);

        if (!$project) {
            http_response_code(403);
            require ROOT_PATH . '/views/errors/403.php';
            exit;
        }

        $frames = get_project_frames($pdo, $project_id);
        $colors = get_project_colors_list($pdo, $project_id);
        require ROOT_PATH . '/views/pages/workspace.php';
        break;

    case 'dashboard':
        require_auth();
        $user_id         = current_user_id();
        $current_user    = get_user_by_id($pdo, $user_id);
        $user_stats      = get_user_dashboard_stats($pdo, $user_id);
        $platform_stats  = get_platform_stats($pdo);
        $recent_projects = get_recent_user_projects($pdo, $user_id, 6);
        require ROOT_PATH . '/views/pages/dashboard.php';
        break;

    case 'projects':
        require_auth();
        $user_id      = current_user_id();
        $current_user = get_user_by_id($pdo, $user_id);
        $search       = trim($_GET['search'] ?? '');
        $status       = trim($_GET['status'] ?? '');
        $sort         = trim($_GET['sort'] ?? 'newest');
        $projects     = get_user_projects_filtered($pdo, $user_id, $search, $status, $sort);
        require ROOT_PATH . '/views/pages/projects.php';
        break;

    case 'explore':
        require_auth();
        $user_id            = current_user_id();
        $current_user       = get_user_by_id($pdo, $user_id);
        $search             = trim($_GET['search'] ?? '');
        $sort               = trim($_GET['sort'] ?? 'newest');
        $platform_stats     = get_platform_stats($pdo);
        $published_projects = get_published_projects_filtered($pdo, $search, $sort);
        require ROOT_PATH . '/views/pages/explore.php';
        break;

    case 'templates':
        require_auth();
        $user_id        = current_user_id();
        $current_user   = get_user_by_id($pdo, $user_id);
        $search         = trim($_GET['search'] ?? '');
        $sort           = trim($_GET['sort'] ?? 'newest');
        $platform_stats = get_platform_stats($pdo);
        $templates      = get_templates_filtered($pdo, $search, $sort);
        require ROOT_PATH . '/views/pages/templates.php';
        break;

    case 'trash':
        require_auth();
        $user_id          = current_user_id();
        $current_user     = get_user_by_id($pdo, $user_id);
        $search           = trim($_GET['search'] ?? '');
        $sort             = trim($_GET['sort'] ?? 'newest');
        $trashed_projects = get_trashed_projects_filtered($pdo, $user_id, $search, $sort);
        require ROOT_PATH . '/views/pages/trash.php';
        break;

    case 'profile':
        require_auth();
        $user_id      = current_user_id();
        $current_user = get_user_by_id($pdo, $user_id);
        $user_stats   = get_user_dashboard_stats($pdo, $user_id);
        require ROOT_PATH . '/views/pages/profile.php';
        break;

    case 'about':
        require_auth();
        $user_id        = current_user_id();
        $current_user   = get_user_by_id($pdo, $user_id);
        $platform_stats = get_platform_stats($pdo);
        require ROOT_PATH . '/views/pages/about.php';
        break;

    case 'admin_dashboard':
        require_admin();
        $current_user    = get_user_by_id($pdo, current_user_id());
        $platform_stats  = get_platform_stats($pdo);
        $recent_users    = get_recent_users_admin($pdo, 5);
        $recent_projects = get_recent_projects_admin($pdo, 5);
        require ROOT_PATH . '/views/pages/admin_dashboard.php';
        break;

    case 'admin_users':
        require_admin();
        $current_user = get_user_by_id($pdo, current_user_id());
        $search       = trim($_GET['search'] ?? '');
        $role         = trim($_GET['role'] ?? '');
        $users_list   = get_all_users_filtered($pdo, $search, $role);
        require ROOT_PATH . '/views/pages/admin_users.php';
        break;

    case 'admin_projects':
        require_admin();
        $current_user = get_user_by_id($pdo, current_user_id());
        $search       = trim($_GET['search'] ?? '');
        $status       = trim($_GET['status'] ?? '');
        $template     = trim($_GET['template'] ?? '');
        $all_projects = get_all_projects_admin($pdo, $search, $status, $template);
        require ROOT_PATH . '/views/pages/admin_projects.php';
        break;

    case 'admin_templates':
        require_admin();
        $current_user = get_user_by_id($pdo, current_user_id());
        $search       = trim($_GET['search'] ?? '');
        $templates    = get_templates_filtered($pdo, $search, 'newest');
        require ROOT_PATH . '/views/pages/admin_templates.php';
        break;

    case 'admin_settings':
        require_admin();
        $current_user   = get_user_by_id($pdo, current_user_id());
        $platform_stats = get_platform_stats($pdo);
        require ROOT_PATH . '/views/pages/admin_settings.php';
        break;

    case 'admin_reports':
        require_admin();
        $current_user   = get_user_by_id($pdo, current_user_id());
        $platform_stats = get_platform_stats($pdo);
        require ROOT_PATH . '/views/pages/admin_reports.php';
        break;

    case 'admin_preview_project':
        require_admin();
        $current_user    = get_user_by_id($pdo, current_user_id());
        $project_id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $preview_project = get_project_by_id($pdo, $project_id);
        if (!$preview_project) {
            redirect(route_url('admin_projects'));
        }
        $preview_frames = get_project_frames($pdo, $project_id);
        require ROOT_PATH . '/views/pages/admin_preview_project.php';
        break;
}
