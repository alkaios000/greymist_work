<?php
/**
 * GREYMIST WORKSPACE - MAIN ENTRY
 * Halaman utama editor Greymist untuk mengelola project dan frames.
 */

// Memuat sistem autentikasi dan koneksi database
require 'includes/auth.php';
require 'includes/db.php';

/* ============================================================
   SECTION 1: VALIDASI PROJECT
   Memastikan project_id tersedia, jika tidak arahkan ke daftar project.
   ============================================================ */
if (!isset($_GET['project_id'])) {
    header("Location: user/project.php");
    exit;
}

$project_id = (int) $_GET['project_id'];
$user_id = $_SESSION['user_id'];

/* ============================================================
   SECTION 2: LOAD DATA PROJECT
   Mengambil informasi detail project dari database berdasarkan ID.
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT * FROM GM_PROJECTS
    WHERE project_id = ? AND user_id = ?
");
$stmt->execute([$project_id, $user_id]);
$project = $stmt->fetch();

// Proteksi jika project tidak ditemukan atau user tidak punya akses
if (!$project) {
    die("Project not found or access denied");
}

/* ============================================================
   SECTION 3: LOAD FRAMES
   Mengambil semua data frame yang terkait dengan project ini.
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT * FROM GM_FRAMES
    WHERE project_id = ?
    ORDER BY frame_id ASC
");
$stmt->execute([$project_id]);
$frames = $stmt->fetchAll();

/* ============================================================
   SECTION 4: LOAD COLOR VARIABLES
   Mengambil daftar variabel warna (Design System) milik project.
   ============================================================ */
$stmt = $pdo->prepare("
    SELECT color_variable
    FROM GM_COLORS
    WHERE project_id = ?
");
$stmt->execute([$project_id]);
$colors = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['project_name']) ?> – Greymist Workspace</title>

    <!-- Logo Fabicon -->
    <link rel="icon" href="assets/web-icon/greymist.svg">

    <!-- CSS / Style Internal (Sistem UI Greymist) -->
    <?php include 'part/style.html' ?>

    <!-- Library Eksternal / CDN (FontAwesome, JS Libs) -->
    <?php include 'part/CDN.html' ?>
</head>

<!-- Injeksi User ID ke window object JS untuk akses global -->
<script>
    window.GM_USER_ID = <?php echo $_SESSION['user_id']; ?>;
</script>

<body class="gm-body">

    <?php
    /* ============================================================
       SECTION 5: LAYOUT COMPONENTS
       Menyusun antarmuka editor menggunakan partials HTML.
       ============================================================ */

    // Panel Sidebar Kiri (Dom Tree & Assets)
    include 'part/aside-left.html';

    // Area Canvas Utama (Editor)
    include 'part/main.html';

    // Panel Sidebar Kanan (Properties & Styling)
    include 'part/aside-right.html';

    // Penampung Modal Global
    include 'part/modal.html';
    ?>

    <!-- ============================================================
         SECTION 6: GREYMIST DATA INJECTION
         Mengubah data PHP menjadi Object Javascript agar bisa diolah oleh Frontend Editor.
         ============================================================ -->
    <script>
        window.GREYMIST = {
            project: <?= json_encode($project, JSON_UNESCAPED_UNICODE) ?>,
            frames: <?= json_encode($frames, JSON_UNESCAPED_UNICODE) ?>,
            colors: <?= json_encode($colors, JSON_UNESCAPED_UNICODE) ?>
        };
    </script>

    <!-- Memasukkan Logika Utama JS (Editor Logic) -->
    <?php include 'part/script.html' ?>

</body>

</html>

<!-- Debugging Log (Dapat dihapus pada tahap produksi) -->
<script>
    console.log('GREYMIST CORE: Loaded with', GREYMIST.frames.length, 'frames.');
</script>