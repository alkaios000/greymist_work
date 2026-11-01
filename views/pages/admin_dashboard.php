<?php
/**
 * File: views/pages/admin_dashboard.php
 * Fungsi: Tampilan Dashboard Administrator Greymist yang efisien, berorientasi aksi langsung,
 *         dan bebas dari elemen dekoratif yang tidak fungsional (no fluff).
 */

$page_title    = 'Dashboard';
$page_subtitle = '';
$active_page   = 'admin_dashboard';
$is_admin_view = true;

require ROOT_PATH . '/views/layouts/header.php';

// Ekstraksi & Kalkulasi Metrik Platform Utama
$total_users       = (int)($platform_stats['total_users'] ?? 0);
$total_admins      = (int)($platform_stats['total_admins'] ?? 0);
$total_regular     = max(0, $total_users - $total_admins);
$admin_ratio       = $total_users > 0 ? round(($total_admins / $total_users) * 100, 1) : 0;
$user_ratio        = $total_users > 0 ? (100 - $admin_ratio) : 100;

$total_projects    = (int)($platform_stats['total_projects'] ?? 0);
$published_count   = (int)($platform_stats['published_count'] ?? 0);
$draft_count       = max(0, $total_projects - $published_count);
$pub_ratio         = $total_projects > 0 ? round(($published_count / $total_projects) * 100, 1) : 0;
$draft_ratio       = $total_projects > 0 ? (100 - $pub_ratio) : 100;

$template_count    = (int)($platform_stats['template_count'] ?? 0);
$template_creators = (int)($platform_stats['template_creators'] ?? 0);
$template_ratio    = $total_projects > 0 ? round(($template_count / $total_projects) * 100, 1) : 0;

$total_frames      = (int)($platform_stats['total_frames'] ?? 0);
$avg_frames        = $total_projects > 0 ? round($total_frames / $total_projects, 1) : 0;

$total_components  = (int)($platform_stats['total_components'] ?? 0);
$total_colors      = (int)($platform_stats['total_colors'] ?? 0);
$total_assets      = (int)($platform_stats['total_assets'] ?? 0);
$total_design_pool = $total_components + $total_colors + $total_assets;
?>

<!-- 1. Empat Pilar Metrik Utama Platform (Ultra-Ringkas & Hemat Ruang) -->
<section class="compact-metric-grid" style="margin-bottom: 14px;">
    <!-- Card 1: Pengguna -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Pengguna Terdaftar</span>
            <div class="metric-value">
                <span><?= number_format($total_users) ?></span>
                <span class="metric-sub">Akun</span>
            </div>
        </div>
        <span class="metric-pill"><?= $total_admins ?> Admin • <?= $total_regular ?> User</span>
    </div>

    <!-- Card 2: Proyek Kanvas -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Proyek Kanvas</span>
            <div class="metric-value">
                <span><?= number_format($total_projects) ?></span>
                <span class="metric-sub">Workspace</span>
            </div>
        </div>
        <span class="metric-pill <?= $published_count > 0 ? 'active' : '' ?>"><?= $published_count ?> Publik • <?= $draft_count ?> Draf</span>
    </div>

    <!-- Card 3: Frame Kanvas -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Densitas Frame</span>
            <div class="metric-value">
                <span><?= number_format($total_frames) ?></span>
                <span class="metric-sub">Total Frame</span>
            </div>
        </div>
        <span class="metric-pill">~<?= $avg_frames ?> Frame/Proyek</span>
    </div>

    <!-- Card 4: Aset & Komponen -->
    <div class="metric-card">
        <div class="metric-info">
            <span class="metric-label">Pustaka Aset</span>
            <div class="metric-value">
                <span><?= number_format($total_design_pool) ?></span>
                <span class="metric-sub">Aset</span>
            </div>
        </div>
        <span class="metric-pill"><?= $total_components ?> Komponen</span>
    </div>
</section>

<!-- 2. Action Bar Ringkas & Fungsional -->
<div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="font-size: 0.82rem; font-weight: 700; color: var(--text-primary);">Aktivitas Inti Platform</span>
        <span style="font-size: 0.72rem; color: var(--text-muted);">(Real-time monitoring)</span>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="btn btn-outline btn-sm" onclick="exportPlatformAuditCSV()">
            Unduh Audit CSV
        </button>
        <form method="POST" action="<?= e(action_url('admin_clear_cache')) ?>" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-soft-blue btn-sm">Segarkan Cache</button>
        </form>
        <a href="<?= e(route_url('admin_users')) ?>" class="btn btn-primary btn-sm">+ Tambah Pengguna</a>
    </div>
</div>

<!-- 3. Dua Grid Data Utama: Pengguna & Proyek (Fokus Utama Admin) -->
<div class="admin-two-col">
    <!-- Grid Kiri: Direktori Pengguna Terkini -->
    <section class="admin-feed-card">
        <div class="admin-feed-header">
            <div class="admin-feed-title-wrap">
                <h3 class="admin-feed-title">Pengguna Terkini</h3>
                <span class="admin-feed-count" id="userCountBadge"><?= count($recent_users) ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="table-search-wrap">
                    <input type="text" class="table-search-input" id="searchUserInput" placeholder="Cari akun..." onkeyup="filterEnterpriseTable('userTableBody', this.value, 'userCountBadge')">
                </div>
                <a href="<?= e(route_url('admin_users')) ?>" class="btn btn-outline btn-sm">Semua</a>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table-pro">
                <thead>
                    <tr>
                        <th>Akun</th>
                        <th>Role</th>
                        <th>Proyek</th>
                        <th>Status</th>
                        <th style="text-align: right;">Terdaftar</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <?php if (empty($recent_users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada data pengguna.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td>
                                    <div class="user-identity">
                                        <div class="user-avatar-badge"><?= e(strtoupper(substr($u['username'], 0, 1))) ?></div>
                                        <div>
                                            <div class="user-name-text"><?= e($u['nickname'] ?: $u['username']) ?></div>
                                            <div class="user-email-text">@<?= e($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ((int)$u['is_admin'] === 1): ?>
                                        <span class="badge-role-admin">ADMIN</span>
                                    <?php else: ?>
                                        <span class="badge-role-user">USER</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="date-mono"><strong><?= (int)($u['project_count'] ?? 0) ?></strong></span>
                                </td>
                                <td>
                                    <span style="font-size: 0.74rem; color: var(--color-success); font-weight: 600;">
                                        <span class="online-dot"></span>Aktif
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <span class="date-mono"><?= e(format_date($u['created_at'])) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Grid Kanan: Pipeline Proyek & Kanvas Terkini -->
    <section class="admin-feed-card">
        <div class="admin-feed-header">
            <div class="admin-feed-title-wrap">
                <h3 class="admin-feed-title">Proyek Kanvas Terkini</h3>
                <span class="admin-feed-count" id="projCountBadge"><?= count($recent_projects) ?></span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div class="table-search-wrap">
                    <input type="text" class="table-search-input" id="searchProjInput" placeholder="Cari proyek..." onkeyup="filterEnterpriseTable('projTableBody', this.value, 'projCountBadge')">
                </div>
                <a href="<?= e(route_url('admin_projects')) ?>" class="btn btn-outline btn-sm">Semua</a>
            </div>
        </div>
        <div style="overflow-x: auto;">
            <table class="admin-table-pro">
                <thead>
                    <tr>
                        <th>Nama Proyek</th>
                        <th>Pemilik</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="projTableBody">
                    <?php if (empty($recent_projects)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada proyek yang dibuat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_projects as $p): ?>
                            <tr>
                                <td>
                                    <div class="user-name-text"><?= e($p['project_name']) ?></div>
                                    <div class="project-frame-chip"><?= e(format_date($p['created_at'])) ?></div>
                                </td>
                                <td>
                                    <span class="date-mono">@<?= e($p['username'] ?? 'unknown') ?></span>
                                </td>
                                <td>
                                    <?php if ($p['project_status'] === 'published'): ?>
                                        <span class="badge-status-pub">PUBLIK</span>
                                    <?php else: ?>
                                        <span class="badge-status-draft">DRAF</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="<?= e(route_url('admin_preview_project', ['id' => $p['project_id']])) ?>" class="btn-action-pro">
                                        Pratinjau
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Toast Notifikasi Ringan -->
<div id="adminToast" class="admin-toast"></div>

<script>
    /**
     * Filter Realtime untuk Data Table Enterprise (Tanpa reload halaman)
     */
    function filterEnterpriseTable(tbodyId, query, badgeId) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        const q = query.toLowerCase().trim();
        const rows = tbody.getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const text = row.innerText.toLowerCase();
            if (q === '' || text.includes(q)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }

        if (badgeId) {
            const badge = document.getElementById(badgeId);
            if (badge) badge.innerText = visibleCount;
        }
    }

    /**
     * Export Snapshot Audit Platform ke file CSV
     */
    function exportPlatformAuditCSV() {
        const dateStr = new Date().toISOString().replace(/[:.]/g, '-');
        const rows = [
            ["GREYMIST ENTERPRISE PLATFORM AUDIT REPORT"],
            ["Generated At", new Date().toLocaleString()],
            ["Total Users", "<?= $total_users ?>"],
            ["Total Admins", "<?= $total_admins ?>"],
            ["Total Projects", "<?= $total_projects ?>"],
            ["Published Projects", "<?= $published_count ?>"],
            ["Draft Projects", "<?= $draft_count ?>"],
            ["Total Canvas Frames", "<?= $total_frames ?>"],
            ["Design System Assets", "<?= $total_design_pool ?>"],
            ["Active Templates", "<?= $template_count ?>"],
            ["PHP Version", "<?= PHP_VERSION ?>"],
            ["Memory Usage", "<?= round(memory_get_usage(true)/1024/1024, 2) ?> MB"],
            [],
            ["USER DIRECTORY SNAPSHOT"],
            ["Username", "Nickname", "Email", "Role", "Project Count", "Created At"]
        ];

        <?php foreach ($recent_users as $ru): ?>
        rows.push([
            <?= json_encode($ru['username']) ?>,
            <?= json_encode($ru['nickname'] ?: $ru['username']) ?>,
            <?= json_encode($ru['email']) ?>,
            <?= json_encode((int)$ru['is_admin'] === 1 ? 'ADMIN' : 'USER') ?>,
            <?= json_encode((int)($ru['project_count'] ?? 0)) ?>,
            <?= json_encode($ru['created_at']) ?>
        ]);
        <?php endforeach; ?>

        let csvContent = "data:text/csv;charset=utf-8," 
            + rows.map(e => e.map(cell => '"' + ('' + cell).replace(/"/g, '""') + '"').join(",")).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `greymist_platform_audit_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showEnterpriseToast("Laporan audit platform berhasil diunduh.");
    }

    function showEnterpriseToast(msg) {
        const toast = document.getElementById('adminToast');
        if (!toast) return;
        toast.innerText = msg;
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3200);
    }
</script>

<?php require ROOT_PATH . '/views/layouts/footer.php'; ?>
