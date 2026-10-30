<?php
/**
 * File: config/config.php
 * Fungsi: Inisialisasi environment (.env), sesi, koneksi database PDO,
 *         pengaturan platform, serta pemuatan helper dan middleware utama.
 */

if (defined('GREYMIST_CONFIG_LOADED')) {
    return;
}
define('GREYMIST_CONFIG_LOADED', true);

if (PHP_SAPI !== 'cli' && ob_get_level() === 0) {
    ob_start();
}

define('ROOT_PATH', dirname(__DIR__));

/**
 * Memuat variabel dari file .env ke dalam $_ENV dan getenv()
 */
if (!function_exists('load_env_file')) {
    function load_env_file(string $file_path): void
    {
        if (!is_file($file_path) || !is_readable($file_path)) {
            return;
        }
        $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            $parts = explode('=', $trimmed, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                // Hapus tanda kutip pembungkus jika ada
                $val = trim($val, "\"'");
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

load_env_file(ROOT_PATH . '/.env');

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        $lower = strtolower((string)$val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        return $val;
    }
}

// Konfigurasi zona waktu dan pelaporan error sesuai environment
$app_timezone = env('APP_TIMEZONE', 'Asia/Jakarta');
date_default_timezone_set($app_timezone);

$app_debug = env('APP_DEBUG', true);
if ($app_debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Mulai session secara aman jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

// Muat konfigurasi dinamis dari config/settings.json
$settings_file = __DIR__ . '/settings.json';
$default_site_settings = [
    'site_name'         => env('APP_NAME', 'Greymist'),
    'site_description'  => 'Design and collaborate on web projects effortlessly.',
    'allow_registrations' => true,
    'maintenance_mode'  => false,
    'max_projects_user' => 10,
];

$site_settings = $default_site_settings;
if (is_file($settings_file)) {
    $decoded_settings = json_decode((string)file_get_contents($settings_file), true);
    if (is_array($decoded_settings)) {
        $site_settings = array_merge($default_site_settings, $decoded_settings);
    }
}

// Koneksi Database PDO menggunakan kredensial dari .env
$db_host    = env('DB_HOST', 'localhost');
$db_port    = env('DB_PORT', '3306');
$db_name    = env('DB_NAME', 'GREYMISTBETA');
$db_user    = env('DB_USER', 'root');
$db_pass    = env('DB_PASS', '');
$db_charset = env('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset={$db_charset}";
$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, (string)$db_user, (string)$db_pass, $pdo_options);
} catch (PDOException $e) {
    // Fallback jika database belum dibuat saat proses migrasi awal
    if (defined('ALLOW_DB_BOOT_WITHOUT_SCHEMA') && ALLOW_DB_BOOT_WITHOUT_SCHEMA === true) {
        $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset={$db_charset}", (string)$db_user, (string)$db_pass, $pdo_options);
    } else {
        http_response_code(500);
        if ($app_debug) {
            die('Database Connection Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        die('Terjadi gangguan koneksi ke server basis data.');
    }
}

// Muat seluruh helper dan middleware inti
require_once ROOT_PATH . '/helpers/security_helper.php';
require_once ROOT_PATH . '/helpers/url_helper.php';
require_once ROOT_PATH . '/helpers/date_helper.php';
require_once ROOT_PATH . '/helpers/upload_helper.php';

require_once ROOT_PATH . '/middlewares/auth_middleware.php';
require_once ROOT_PATH . '/middlewares/role_middleware.php';
require_once ROOT_PATH . '/middlewares/csrf_middleware.php';

