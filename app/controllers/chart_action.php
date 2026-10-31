<?php
/**
 * File: app/controllers/chart_action.php
 * Fungsi: Controller untuk mengelola Chart kustom dan Template Chart (Menu Assets -> Charts).
 *         Menyimpan chart ke database gm_user_charts yang terikat pada user_id pengguna,
 *         sehingga chart dapat diakses dan digunakan di project mana pun.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/chart_model.php';

$user_id = current_user_id() ?? (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);
if (!$user_id) {
    json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Silakan login terlebih dahulu untuk mengakses menu Chart.'], 401);
}

$chart_action = $_GET['action'] ?? $_POST['action'] ?? '';
$raw_input    = file_get_contents('php://input');
$data         = json_decode((string)$raw_input, true);
if (!is_array($data)) {
    $data = $_POST;
}

/**
 * Daftar template chart bawaan yang siap pakai (Chart.js, ApexCharts, ECharts)
 */
function get_builtin_chart_templates(): array
{
    return [
        [
            'id'          => 'tmpl-bar-sales',
            'name'        => 'Bar Chart - Penjualan Bulanan',
            'type'        => 'bar',
            'library'     => 'chartjs',
            'library_name'=> 'Chart.js',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/chart.js',
            'desc'        => 'Grafik batang perbandingan target dan realisasi bulanan dengan desain modern.',
            'category'    => 'Statistik & Keuangan',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="bar" data-chart-lib="chartjs" style="width: 100%; max-width: 620px; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-chart-column gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Statistik Penjualan Bulanan</h4>
                <span class="gm-object" style="font-size: 12px; color: #64748b;">Target vs Realisasi (Juta Rp)</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #10b981; background: #ecfdf5; padding: 4px 12px; border-radius: 20px;">+18.4%</span>
    </div>
    <div class="gm-object gm-chart-canvas-wrap" style="position: relative; width: 100%; height: 290px;">
        <canvas class="gm-object gm-chart-canvas"></canvas>
    </div>
    <script data-gm-chart-config="true">
    {
        "library": "chartjs",
        "cdn": "https://cdn.jsdelivr.net/npm/chart.js",
        "type": "bar",
        "data": {
            "labels": ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
            "datasets": [
                {
                    "label": "Realisasi (Juta)",
                    "data": [45, 59, 72, 85, 80, 96, 110, 118, 102, 125, 140, 155],
                    "backgroundColor": "#3b82f6",
                    "borderRadius": 6
                },
                {
                    "label": "Target (Juta)",
                    "data": [50, 60, 70, 75, 80, 85, 95, 100, 105, 115, 130, 145],
                    "backgroundColor": "#e2e8f0",
                    "borderRadius": 6
                }
            ]
        },
        "options": {
            "responsive": true,
            "maintainAspectRatio": false,
            "plugins": {
                "legend": { "position": "bottom" }
            }
        }
    }
    </script>
</div>
HTML
        ],
        [
            'id'          => 'tmpl-line-traffic',
            'name'        => 'Line Chart - Tren Pengunjung',
            'type'        => 'line',
            'library'     => 'chartjs',
            'library_name'=> 'Chart.js',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/chart.js',
            'desc'        => 'Grafik garis kurva halus dengan visualisasi tren pengunjung mingguan.',
            'category'    => 'Traffic & Analitik Web',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="line" data-chart-lib="chartjs" style="width: 100%; max-width: 620px; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: #fdf2f8; color: #ec4899; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-chart-line gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Tren Kunjungan Website</h4>
                <span class="gm-object" style="font-size: 12px; color: #64748b;">Pengunjung Unik vs Halaman Dilihat</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #3b82f6; background: #eff6ff; padding: 4px 12px; border-radius: 20px;">Minggu Ini</span>
    </div>
    <div class="gm-object gm-chart-canvas-wrap" style="position: relative; width: 100%; height: 290px;">
        <canvas class="gm-object gm-chart-canvas"></canvas>
    </div>
    <script data-gm-chart-config="true">
    {
        "library": "chartjs",
        "cdn": "https://cdn.jsdelivr.net/npm/chart.js",
        "type": "line",
        "data": {
            "labels": ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"],
            "datasets": [
                {
                    "label": "Unique Visitors",
                    "data": [3200, 4100, 3900, 5200, 6100, 7800, 8400],
                    "borderColor": "#3b82f6",
                    "backgroundColor": "rgba(59, 130, 246, 0.12)",
                    "tension": 0.38,
                    "fill": true
                },
                {
                    "label": "Page Views",
                    "data": [8500, 11200, 9800, 14200, 16800, 21500, 24100],
                    "borderColor": "#ec4899",
                    "backgroundColor": "rgba(236, 72, 153, 0.08)",
                    "tension": 0.38,
                    "fill": true
                }
            ]
        },
        "options": {
            "responsive": true,
            "maintainAspectRatio": false,
            "plugins": {
                "legend": { "position": "bottom" }
            }
        }
    }
    </script>
</div>
HTML
        ],
        [
            'id'          => 'tmpl-doughnut-budget',
            'name'        => 'Doughnut Chart - Alokasi Anggaran',
            'type'        => 'doughnut',
            'library'     => 'chartjs',
            'library_name'=> 'Chart.js',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/chart.js',
            'desc'        => 'Grafik donat modern dengan palet warna elegan untuk distribusi proporsi kategori.',
            'category'    => 'Distribusi & Rasio',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="doughnut" data-chart-lib="chartjs" style="width: 100%; max-width: 540px; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-chart-pie gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Alokasi Anggaran Proyek</h4>
                <span class="gm-object" style="font-size: 12px; color: #64748b;">Distribusi Budget Q1-Q4</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 4px 12px; border-radius: 20px;">Total 100%</span>
    </div>
    <div class="gm-object gm-chart-canvas-wrap" style="position: relative; width: 100%; height: 260px;">
        <canvas class="gm-object gm-chart-canvas"></canvas>
    </div>
    <script data-gm-chart-config="true">
    {
        "library": "chartjs",
        "cdn": "https://cdn.jsdelivr.net/npm/chart.js",
        "type": "doughnut",
        "data": {
            "labels": ["Pengembangan", "Pemasaran", "Operasional", "Desain UI/UX", "Riset & AI"],
            "datasets": [
                {
                    "data": [35, 25, 15, 15, 10],
                    "backgroundColor": ["#3b82f6", "#10b981", "#f59e0b", "#8b5cf6", "#ec4899"],
                    "borderWidth": 2,
                    "borderColor": "#ffffff"
                }
            ]
        },
        "options": {
            "responsive": true,
            "maintainAspectRatio": false,
            "plugins": {
                "legend": { "position": "right" }
            },
            "cutout": "68%"
        }
    }
    </script>
</div>
HTML
        ],
        [
            'id'          => 'tmpl-apex-area',
            'name'        => 'ApexCharts - Area Spline Realtime',
            'type'        => 'area',
            'library'     => 'apexcharts',
            'library_name'=> 'ApexCharts',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/apexcharts',
            'desc'        => 'Grafik area interaktif mutakhir dengan tooltip dinamis dan tema gradient halus.',
            'category'    => 'Dashboard Modern',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="area" data-chart-lib="apexcharts" style="width: 100%; max-width: 640px; padding: 24px; background: #0f172a; color: #f8fafc; border-radius: 14px; box-shadow: 0 8px 30px rgba(0,0,0,0.18); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: rgba(56, 189, 248, 0.15); color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-chart-area gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #f8fafc;">Server Load & Latency</h4>
                <span class="gm-object" style="font-size: 12px; color: #94a3b8;">Real-time Cloud Metrics</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #38bdf8; background: rgba(56, 189, 248, 0.12); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(56, 189, 248, 0.3);">Live 24h</span>
    </div>
    <div class="gm-object gm-apex-chart-container" style="min-height: 280px; width: 100%;"></div>
    <script data-gm-chart-config="true">
    {
        "library": "apexcharts",
        "cdn": "https://cdn.jsdelivr.net/npm/apexcharts",
        "options": {
            "chart": { "type": "area", "height": 280, "toolbar": { "show": false }, "background": "transparent" },
            "theme": { "mode": "dark" },
            "colors": ["#38bdf8", "#818cf8"],
            "dataLabels": { "enabled": false },
            "stroke": { "curve": "smooth", "width": 2.5 },
            "series": [
                { "name": "Throughput (RPS)", "data": [310, 420, 560, 680, 610, 820, 950, 890, 1100, 1250, 1340, 1420] },
                { "name": "Latency (ms)", "data": [45, 42, 38, 40, 36, 34, 30, 32, 28, 25, 24, 22] }
            ],
            "xaxis": {
                "categories": ["00:00", "02:00", "04:00", "06:00", "08:00", "10:00", "12:00", "14:00", "16:00", "18:00", "20:00", "22:00"]
            },
            "grid": { "borderColor": "#1e293b" }
        }
    }
    </script>
</div>
HTML
        ],
        [
            'id'          => 'tmpl-apex-radial',
            'name'        => 'ApexCharts - Radial Bar / Progress',
            'type'        => 'gauge',
            'library'     => 'apexcharts',
            'library_name'=> 'ApexCharts',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/apexcharts',
            'desc'        => 'Pengukur target progres berbentuk radial lingkaran bertingkat (KPI Tracker).',
            'category'    => 'KPI & Pencapaian',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="radialBar" data-chart-lib="apexcharts" style="width: 100%; max-width: 480px; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-bullseye gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Capaian Target KPI</h4>
                <span class="gm-object" style="font-size: 12px; color: #64748b;">Evaluasi Semester Ini</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 4px 12px; border-radius: 20px;">On Track</span>
    </div>
    <div class="gm-object gm-apex-chart-container" style="min-height: 260px; width: 100%;"></div>
    <script data-gm-chart-config="true">
    {
        "library": "apexcharts",
        "cdn": "https://cdn.jsdelivr.net/npm/apexcharts",
        "options": {
            "chart": { "type": "radialBar", "height": 260 },
            "plotOptions": {
                "radialBar": {
                    "dataLabels": {
                        "name": { "fontSize": "18px" },
                        "value": { "fontSize": "15px" },
                        "total": { "show": true, "label": "Rata-rata", "formatter": "function() { return '82%'; }" }
                    }
                }
            },
            "colors": ["#10b981", "#3b82f6", "#8b5cf6"],
            "series": [90, 82, 74],
            "labels": ["Kualitas Produk", "Retensi Klien", "Efisiensi Biaya"]
        }
    }
    </script>
</div>
HTML
        ],
        [
            'id'          => 'tmpl-chart-radar',
            'name'        => 'Radar Chart - Evaluasi Performa',
            'type'        => 'radar',
            'library'     => 'chartjs',
            'library_name'=> 'Chart.js',
            'cdn_url'     => 'https://cdn.jsdelivr.net/npm/chart.js',
            'desc'        => 'Grafik jaring laba-laba/radar untuk matriks perbandingan kompetensi dan fitur.',
            'category'    => 'Evaluasi & Matriks',
            'code'        => <<<'HTML'
<div class="gm-object gm-chart-card" data-chart-type="radar" data-chart-lib="chartjs" style="width: 100%; max-width: 540px; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); box-sizing: border-box; margin: 16px auto; font-family: 'Inter', sans-serif;">
    <div class="gm-object" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div class="gm-object" style="display: flex; align-items: center; gap: 10px;">
            <div class="gm-object" style="width: 38px; height: 38px; border-radius: 10px; background: #faf5ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 17px;"><i class="fa-solid fa-compass-drafting gm-object"></i></div>
            <div class="gm-object">
                <h4 class="gm-object" style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Matriks Evaluasi Kinerja</h4>
                <span class="gm-object" style="font-size: 12px; color: #64748b;">Standar Industri vs Tim Saat Ini</span>
            </div>
        </div>
        <span class="gm-object" style="font-size: 12px; font-weight: 700; color: #8b5cf6; background: #faf5ff; padding: 4px 12px; border-radius: 20px;">Score A+</span>
    </div>
    <div class="gm-object gm-chart-canvas-wrap" style="position: relative; width: 100%; height: 280px;">
        <canvas class="gm-object gm-chart-canvas"></canvas>
    </div>
    <script data-gm-chart-config="true">
    {
        "library": "chartjs",
        "cdn": "https://cdn.jsdelivr.net/npm/chart.js",
        "type": "radar",
        "data": {
            "labels": ["Kecepatan", "Keamanan", "Skalabilitas", "Akurasi UI", "Responsif", "Aksesibilitas"],
            "datasets": [
                {
                    "label": "Tim Saat Ini",
                    "data": [95, 90, 85, 98, 92, 88],
                    "borderColor": "#8b5cf6",
                    "backgroundColor": "rgba(139, 92, 246, 0.22)",
                    "pointBackgroundColor": "#8b5cf6"
                },
                {
                    "label": "Standar Industri",
                    "data": [75, 80, 70, 85, 78, 70],
                    "borderColor": "#94a3b8",
                    "backgroundColor": "rgba(148, 163, 184, 0.15)",
                    "pointBackgroundColor": "#94a3b8"
                }
            ]
        },
        "options": {
            "responsive": true,
            "maintainAspectRatio": false,
            "plugins": {
                "legend": { "position": "bottom" }
            }
        }
    }
    </script>
</div>
HTML
        ],
    ];
}

switch ($chart_action) {
    case 'get_chart_templates':
        $templates = get_builtin_chart_templates();
        json_response([
            'status'    => 'OK',
            'success'   => true,
            'templates' => $templates,
            'count'     => count($templates),
        ]);
        break;

    case 'get_user_charts':
        $charts = get_user_charts($pdo, (int)$user_id);
        $templates = get_builtin_chart_templates();
        json_response([
            'status'     => 'OK',
            'success'    => true,
            'charts'     => $charts,
            'count'      => count($charts),
            'templates'  => $templates,
            'user_id'    => (int)$user_id,
        ]);
        break;

    case 'save_user_chart':
        $chart_id        = (int)($data['chart_id'] ?? 0);
        $chart_name      = trim((string)($data['chart_name'] ?? ''));
        $chart_type      = trim((string)($data['chart_type'] ?? 'bar'));
        $library_cdn     = trim((string)($data['library_cdn'] ?? 'chartjs'));
        $cdn_url         = trim((string)($data['cdn_url'] ?? ''));
        $chart_code      = trim((string)($data['chart_code'] ?? ''));
        $chart_data_json = isset($data['chart_data_json']) ? (is_string($data['chart_data_json']) ? $data['chart_data_json'] : json_encode($data['chart_data_json'])) : null;

        if ($chart_name === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Nama chart wajib diisi.'], 400);
        }

        if ($chart_code === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Kode chart HTML / JS wajib diisi.'], 400);
        }

        // Tentukan CDN bawaan jika kosong
        if ($cdn_url === '') {
            if ($library_cdn === 'apexcharts') {
                $cdn_url = 'https://cdn.jsdelivr.net/npm/apexcharts';
            } elseif ($library_cdn === 'echarts') {
                $cdn_url = 'https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js';
            } else {
                $cdn_url = 'https://cdn.jsdelivr.net/npm/chart.js';
            }
        }

        if ($chart_id > 0) {
            $updated = update_user_chart($pdo, $chart_id, (int)$user_id, $chart_name, $chart_type, $library_cdn, $cdn_url, $chart_code, $chart_data_json);
            if ($updated) {
                json_response([
                    'status'   => 'OK',
                    'success'  => true,
                    'chart_id' => $chart_id,
                    'msg'      => "Chart \"{$chart_name}\" berhasil diperbarui.",
                ]);
            } else {
                json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Gagal memperbarui chart.'], 500);
            }
        } else {
            $new_id = create_user_chart($pdo, (int)$user_id, $chart_name, $chart_type, $library_cdn, $cdn_url, $chart_code, $chart_data_json);
            json_response([
                'status'   => 'OK',
                'success'  => true,
                'chart_id' => $new_id,
                'msg'      => "Chart \"{$chart_name}\" berhasil disimpan ke pustaka akun Anda.",
            ]);
        }
        break;

    case 'delete_user_chart':
        $chart_id = (int)($data['chart_id'] ?? $_GET['chart_id'] ?? 0);
        if ($chart_id <= 0) {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'ID chart tidak valid.'], 400);
        }

        $deleted = delete_user_chart($pdo, $chart_id, (int)$user_id);
        if ($deleted) {
            json_response([
                'status'   => 'OK',
                'success'  => true,
                'msg'      => 'Chart berhasil dihapus dari pustaka akun Anda.',
            ]);
        } else {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Chart tidak ditemukan atau Anda tidak memiliki akses untuk menghapusnya.'], 404);
        }
        break;

    default:
        json_response(['status' => 'ERROR', 'success' => false, 'msg' => "Aksi '{$chart_action}' tidak valid pada chart_action."], 400);
        break;
}

