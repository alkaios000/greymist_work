<?php
/**
 * File: app/controllers/ai_action.php
 * Fungsi: Controller untuk Plugin Gemini AI Component Generator di Workspace Editor.
 *         Menggunakan Gemini Interactions API (gemini-3.8-flash) dan memastikan setiap elemen
 *         memiliki class .gm-object agar dapat diedit penuh di kanvas Greymist.
 */

require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/models/component_model.php';
require_once ROOT_PATH . '/models/frame_model.php';

@set_time_limit(300);
@ini_set('memory_limit', '1024M');

/**
 * Memastikan kolom ai_memory tersedia di tabel gm_projects untuk menyimpan cache konteks & riwayat AI.
 */
if (!function_exists('ensure_ai_memory_column')) {
    function ensure_ai_memory_column(PDO $pdo): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `gm_projects` LIKE 'ai_memory'");
            if (!$stmt || !$stmt->fetch()) {
                $pdo->exec("ALTER TABLE `gm_projects` ADD COLUMN `ai_memory` LONGTEXT DEFAULT NULL");
            }
        } catch (Throwable $e) {
            // Abaikan jika gagal alter
        }
    }
}

/**
 * Merangkum struktur komponen di dalam satu kode HTML frame menjadi ringkasan arsitektur yang padat.
 */
if (!function_exists('summarize_frame_structure')) {
    function summarize_frame_structure(string $html): array
    {
        if (trim($html) === '') {
            return [];
        }
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $doc->getElementById('gm-root');
        $blocks = [];
        if ($root) {
            $idx = 1;
            foreach ($root->childNodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE) {
                    $tag   = strtolower($node->nodeName);
                    $text  = trim(preg_replace('/\s+/', ' ', $node->textContent));
                    $short = mb_substr($text, 0, 60);
                    $child_count = $node->getElementsByTagName('*')->length;
                    $blocks[] = [
                        'order'       => $idx++,
                        'tag'         => $tag,
                        'preview'     => $short !== '' ? $short : '(elemen visual)',
                        'child_count' => $child_count,
                    ];
                }
            }
        }
        return $blocks;
    }
}

/**
 * Mengambil dan menyinkronkan memori konteks AI proyek dari database gm_projects + gm_frames.
 */
if (!function_exists('get_project_ai_memory')) {
    function get_project_ai_memory(PDO $pdo, int $project_id, array $client_frames_snapshot = []): array
    {
        ensure_ai_memory_column($pdo);

        $default_memory = [
            'project_id'       => $project_id,
            'project_name'     => 'Untitled Project',
            'system_summary'   => '',
            'total_pages'      => 0,
            'pages_map'        => [],
            'history'          => [],
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        if ($project_id <= 0) {
            return $default_memory;
        }

        $stmt = $pdo->prepare("SELECT project_name, ai_memory FROM gm_projects WHERE project_id = ? LIMIT 1");
        $stmt->execute([$project_id]);
        $proj = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proj) {
            return $default_memory;
        }

        $saved = [];
        if (!empty($proj['ai_memory'])) {
            $decoded = json_decode((string)$proj['ai_memory'], true);
            if (is_array($decoded)) {
                $saved = $decoded;
            }
        }

        // Ambil susunan seluruh frame/halaman terkini (prioritaskan snapshot live dari client jika dikirim, fallback ke DB gm_frames)
        $pages_map = [];
        if (!empty($client_frames_snapshot)) {
            foreach ($client_frames_snapshot as $f) {
                $fid   = (int)($f['frame_id'] ?? 0);
                $fname = trim((string)($f['frame_name'] ?? 'Frame'));
                $fhtml = (string)($f['core_content'] ?? '');
                $pages_map[] = [
                    'frame_id'   => $fid,
                    'frame_name' => $fname,
                    'components' => summarize_frame_structure($fhtml),
                ];
            }
        } else {
            $db_frames = get_project_frames($pdo, $project_id);
            foreach ($db_frames as $f) {
                $pages_map[] = [
                    'frame_id'   => (int)$f['frame_id'],
                    'frame_name' => (string)$f['frame_name'],
                    'components' => summarize_frame_structure((string)($f['core_content'] ?? '')),
                ];
            }
        }

        return [
            'project_id'     => $project_id,
            'project_name'   => (string)$proj['project_name'],
            'system_summary' => (string)($saved['system_summary'] ?? ''),
            'total_pages'    => count($pages_map),
            'pages_map'      => $pages_map,
            'history'        => isset($saved['history']) && is_array($saved['history']) ? array_slice($saved['history'], -20) : [],
            'updated_at'     => (string)($saved['updated_at'] ?? date('Y-m-d H:i:s')),
        ];
    }
}

/**
 * Menyimpan pembaruan memori AI ke dalam gm_projects.ai_memory.
 */
if (!function_exists('save_project_ai_memory')) {
    function save_project_ai_memory(PDO $pdo, int $project_id, array $memory): void
    {
        if ($project_id <= 0) {
            return;
        }
        ensure_ai_memory_column($pdo);
        $memory['updated_at'] = date('Y-m-d H:i:s');
        if (isset($memory['history']) && is_array($memory['history'])) {
            $memory['history'] = array_values(array_slice($memory['history'], -20));
        }
        $stmt = $pdo->prepare("UPDATE gm_projects SET ai_memory = ? WHERE project_id = ?");
        $stmt->execute([json_encode($memory, JSON_UNESCAPED_UNICODE), $project_id]);
    }
}

/**
 * Memformat memori proyek menjadi teks konteks yang dibaca oleh Gemini sebelum mengeksekusi prompt.
 */
if (!function_exists('format_ai_memory_for_prompt')) {
    function format_ai_memory_for_prompt(array $memory): string
    {
        $lines = [];
        $lines[] = "=== CACHE MEMORI & ARSITEKTUR PROYEK ===";
        $lines[] = "Nama Proyek: " . ($memory['project_name'] ?? 'Untitled');
        $lines[] = "Total Pages/Frames Saat Ini: " . (int)($memory['total_pages'] ?? 0);

        if (!empty($memory['system_summary'])) {
            $lines[] = "Ringkasan Sistem Terakhir: " . $memory['system_summary'];
        }

        if (!empty($memory['pages_map']) && is_array($memory['pages_map'])) {
            $lines[] = "Susunan Halaman (Frames) & Komponen di Dalamnya:";
            foreach ($memory['pages_map'] as $p) {
                $comp_strs = [];
                foreach (($p['components'] ?? []) as $c) {
                    $comp_strs[] = "#{$c['order']} <{$c['tag']}> \"{$c['preview']}\"";
                }
                $comp_desc = !empty($comp_strs) ? implode('; ', $comp_strs) : '(Kosong)';
                $lines[] = "  - Frame ID {$p['frame_id']} (\"{$p['frame_name']}\"): {$comp_desc}";
            }
        }

        if (!empty($memory['history']) && is_array($memory['history'])) {
            $lines[] = "Riwayat Perubahan AI Sebelumnya (Kronologis):";
            foreach (array_slice($memory['history'], -10) as $h) {
                $lines[] = "  * [" . ($h['time'] ?? '-') . "] Prompt: \"" . ($h['prompt'] ?? '') . "\" -> Hasil: " . ($h['action_summary'] ?? '');
            }
        }

        return implode("\n", $lines);
    }
}

/**
 * Memastikan setiap elemen HTML di dalam output memiliki class "gm-object"
 * agar kompatibel dengan DOM Tree, seleksi objek, dan panel properti Greymist.
 */
if (!function_exists('ensure_gm_object_class')) {
    function ensure_gm_object_class(string $html): string
    {
        // Bersihkan markdown code fences jika ada
        $html = preg_replace('/^```(?:html)?\s*/i', '', trim($html));
        $html = preg_replace('/\s*```$/', '', $html);

        // Tambahkan gm-object ke tag yang sudah memiliki atribut class="..."
        $html = preg_replace_callback(
            '/<([a-zA-Z0-9]+)\b([^>]*?\bclass\s*=\s*(["\']))(.*?)\3([^>]*)>/is',
            function ($matches) {
                $tag     = $matches[1];
                $before  = $matches[2];
                $quote   = $matches[3];
                $classes = trim($matches[4]);
                $after   = $matches[5];

                $class_list = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
                if (!in_array('gm-object', $class_list, true)) {
                    $class_list[] = 'gm-object';
                }
                return '<' . $tag . $before . implode(' ', $class_list) . $quote . $after . '>';
            },
            $html
        );

        // Tambahkan class="gm-object" ke tag pembuka yang belum memiliki atribut class
        $html = preg_replace_callback(
            '/<([a-zA-Z0-9]+)\b(?![^>]*\bclass\s*=)([^>]*?)(\/?>)/is',
            function ($matches) {
                $tag       = strtolower($matches[1]);
                $skip_tags = ['script', 'style', 'br', 'hr', 'meta', 'link'];
                if (in_array($tag, $skip_tags, true)) {
                    return $matches[0];
                }
                return '<' . $matches[1] . ' class="gm-object"' . $matches[2] . $matches[3];
            },
            $html
        );

        return trim($html);
    }
}

/**
 * Generator komponen pintar bawaan (Smart Fallback) jika GEMINI_API_KEY belum diisi
 * atau ketika pengguna memilih preset cepat secara offline.
 */
if (!function_exists('generate_smart_preset_component')) {
    function generate_smart_preset_component(string $prompt): string
    {
        $lower = strtolower($prompt);

        if (str_contains($lower, 'navbar') || str_contains($lower, 'header') || str_contains($lower, 'navigasi')) {
            return <<<'HTML'
<nav class="gm-object" style="display:flex;align-items:center;justify-content:space-between;padding:18px 40px;background-color:#ffffff;border-bottom:1px solid #e2e8f0;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div class="gm-object" style="display:flex;align-items:center;gap:10px;">
        <div class="gm-object" style="width:36px;height:36px;border-radius:8px;background-color:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;">G</div>
        <span class="gm-object" style="font-size:18px;font-weight:700;color:#0f172a;">Greymist UI</span>
    </div>
    <div class="gm-object" style="display:flex;align-items:center;gap:28px;">
        <a class="gm-object" href="#" style="font-size:14px;font-weight:500;color:#0f172a;text-decoration:none;">Beranda</a>
        <a class="gm-object" href="#" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Fitur</a>
        <a class="gm-object" href="#" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Harga</a>
        <a class="gm-object" href="#" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Dokumentasi</a>
    </div>
    <button class="gm-object" style="padding:10px 20px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Mulai Gratis</button>
</nav>
HTML;
        }

        if (str_contains($lower, 'pricing') || str_contains($lower, 'harga') || str_contains($lower, 'paket')) {
            return <<<'HTML'
<section class="gm-object" style="padding:60px 40px;background-color:#ffffff;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div class="gm-object" style="text-align:center;max-width:600px;margin:0 auto 40px auto;">
        <span class="gm-object" style="display:inline-block;padding:4px 12px;background-color:#eff6ff;color:#3b82f6;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:12px;">PILIHAN PAKET</span>
        <h2 class="gm-object" style="font-size:32px;font-weight:700;color:#0f172a;margin:0 0 10px 0;">Harga Transparan untuk Tim Anda</h2>
        <p class="gm-object" style="font-size:15px;color:#64748b;margin:0;">Pilih paket yang sesuai dengan skala proyek desain dan kolaborasi Anda.</p>
    </div>
    <div class="gm-object" style="display:flex;gap:24px;justify-content:center;flex-wrap:wrap;">
        <div class="gm-object" style="flex:1;min-width:260px;max-width:340px;padding:28px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;display:flex;flex-direction:column;gap:16px;">
            <h3 class="gm-object" style="font-size:18px;font-weight:600;color:#0f172a;margin:0;">Starter</h3>
            <div class="gm-object" style="font-size:32px;font-weight:700;color:#0f172a;">Rp 0 <span class="gm-object" style="font-size:14px;font-weight:400;color:#64748b;">/ bulan</span></div>
            <p class="gm-object" style="font-size:14px;color:#64748b;margin:0;">Cocok untuk desainer individu yang baru memulai.</p>
            <button class="gm-object" style="padding:11px 18px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:8px;font-weight:600;cursor:pointer;">Pilih Starter</button>
        </div>
        <div class="gm-object" style="flex:1;min-width:260px;max-width:340px;padding:28px;background-color:#ffffff;border:2px solid #3b82f6;border-radius:12px;display:flex;flex-direction:column;gap:16px;box-shadow:0 8px 24px rgba(59,130,246,0.12);">
            <h3 class="gm-object" style="font-size:18px;font-weight:600;color:#3b82f6;margin:0;">Pro Studio</h3>
            <div class="gm-object" style="font-size:32px;font-weight:700;color:#0f172a;">Rp 149rb <span class="gm-object" style="font-size:14px;font-weight:400;color:#64748b;">/ bulan</span></div>
            <p class="gm-object" style="font-size:14px;color:#64748b;margin:0;">Proyek tanpa batas, AI generator penuh, dan ekspor kode bersih.</p>
            <button class="gm-object" style="padding:11px 18px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Mulai Pro Studio</button>
        </div>
    </div>
</section>
HTML;
        }

        if (str_contains($lower, 'form') || str_contains($lower, 'kontak') || str_contains($lower, 'contact') || str_contains($lower, 'login')) {
            return <<<'HTML'
<section class="gm-object" style="padding:48px 24px;background-color:#ffffff;display:flex;justify-content:center;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div class="gm-object" style="width:100%;max-width:460px;padding:32px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;display:flex;flex-direction:column;gap:16px;">
        <h3 class="gm-object" style="font-size:22px;font-weight:700;color:#0f172a;margin:0;">Hubungi Tim Kami</h3>
        <p class="gm-object" style="font-size:14px;color:#64748b;margin:0;">Kirimkan pesan atau pertanyaan proyek Anda melalui formulir di bawah ini.</p>
        <div class="gm-object" style="display:flex;flex-direction:column;gap:6px;">
            <label class="gm-object" style="font-size:13px;font-weight:600;color:#0f172a;">Nama Lengkap</label>
            <input class="gm-object" type="text" placeholder="Masukkan nama Anda" style="padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>
        <div class="gm-object" style="display:flex;flex-direction:column;gap:6px;">
            <label class="gm-object" style="font-size:13px;font-weight:600;color:#0f172a;">Alamat Email</label>
            <input class="gm-object" type="email" placeholder="nama@perusahaan.com" style="padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;">
        </div>
        <div class="gm-object" style="display:flex;flex-direction:column;gap:6px;">
            <label class="gm-object" style="font-size:13px;font-weight:600;color:#0f172a;">Pesan</label>
            <textarea class="gm-object" rows="4" placeholder="Tuliskan detail kebutuhan Anda..." style="padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:14px;"></textarea>
        </div>
        <button class="gm-object" style="padding:12px 20px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Kirim Pesan</button>
    </div>
</section>
HTML;
        }

        if (str_contains($lower, 'footer') || str_contains($lower, 'kaki')) {
            return <<<'HTML'
<footer class="gm-object" style="padding:40px;background-color:#ffffff;border-top:1px solid #e2e8f0;width:100%;box-sizing:border-box;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;font-family:'Inter',sans-serif;">
    <div class="gm-object" style="display:flex;flex-direction:column;gap:6px;">
        <strong class="gm-object" style="font-size:16px;color:#0f172a;">Greymist Workspace</strong>
        <span class="gm-object" style="font-size:13px;color:#64748b;">Platform desain visual web modern berbasis komponen.</span>
    </div>
    <div class="gm-object" style="display:flex;gap:24px;">
        <a class="gm-object" href="#" style="font-size:13px;color:#475569;text-decoration:none;">Ketentuan Layanan</a>
        <a class="gm-object" href="#" style="font-size:13px;color:#475569;text-decoration:none;">Kebijakan Privasi</a>
        <a class="gm-object" href="#" style="font-size:13px;color:#475569;text-decoration:none;">Bantuan</a>
    </div>
</footer>
HTML;
        }

        // Default: Hero Section Modern sesuai prompt pengguna
        $clean_title = htmlspecialchars(mb_substr(trim($prompt), 0, 70), ENT_QUOTES, 'UTF-8');
        return <<<HTML
<section class="gm-object" style="padding:72px 48px;background-color:#ffffff;border-bottom:1px solid #f1f5f9;width:100%;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;text-align:center;gap:20px;font-family:'Inter',sans-serif;">
    <span class="gm-object" style="padding:6px 14px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:999px;font-size:12px;font-weight:600;">GREYMIST AI GENERATED</span>
    <h1 class="gm-object" style="font-size:40px;font-weight:800;color:#0f172a;max-width:760px;line-height:1.2;margin:0;">{$clean_title}</h1>
    <p class="gm-object" style="font-size:16px;color:#475569;max-width:600px;line-height:1.6;margin:0;">Komponen ini dibuat secara otomatis dan setiap elemen di dalamnya memiliki class gm-object sehingga dapat langsung Anda ubah melalui panel properti di sebelah kanan.</p>
    <div class="gm-object" style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;justify-content:center;">
        <button class="gm-object" style="padding:12px 24px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Mulai Sekarang</button>
        <button class="gm-object" style="padding:12px 24px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Pelajari Lebih Lanjut</button>
    </div>
</section>
HTML;
    }
}

/**
 * Memproses penghapusan, pembacaan, atau pengeditan atribut (font, warna, ukuran, style) komponen secara lokal (Fallback Cerdas).
 */
if (!function_exists('process_local_frame_command')) {
    function process_local_frame_command(
        string $prompt,
        string $current_frame_html,
        string $selected_object_html = '',
        string $target_mode = 'core'
    ): ?array {
        $lower = strtolower(trim($prompt));

        // 1. Jika meminta membuat frame / halaman baru (atau multi-page) secara lokal
        if (preg_match('/\b(frame baru|tambah frame|halaman baru|buat halaman|page baru|multi[- ]?page|\d+\s*halaman)\b/i', $lower)) {
            if (preg_match('/\b(multi[- ]?page|3\s*halaman|beberapa halaman|website lengkap)\b/i', $lower)) {
                $nav = generate_smart_preset_component('navbar');
                $footer = generate_smart_preset_component('footer');
                return [
                    'operation'    => 'create_frames',
                    'html'         => '',
                    'summary'      => '3 Frame Halaman baru berhasil dibuat (Beranda, Layanan & Harga, Kontak).',
                    'frames_specs' => [
                        [
                            'frame_name' => 'Beranda',
                            'html'       => ensure_gm_object_class($nav . "\n" . generate_smart_preset_component('hero') . "\n" . $footer),
                        ],
                        [
                            'frame_name' => 'Layanan & Harga',
                            'html'       => ensure_gm_object_class($nav . "\n" . generate_smart_preset_component('pricing') . "\n" . $footer),
                        ],
                        [
                            'frame_name' => 'Hubungi Kami',
                            'html'       => ensure_gm_object_class($nav . "\n" . generate_smart_preset_component('kontak') . "\n" . $footer),
                        ],
                    ],
                ];
            }

            $page_title = 'Halaman Baru';
            if (preg_match('/(?:halaman|frame|page)\s+(?:baru\s+)?(?:untuk\s+|tentang\s+)?([a-zA-Z0-9\s\-]{2,30})/i', $prompt, $m)) {
                $candidate = trim($m[1]);
                if ($candidate !== '') {
                    $page_title = ucwords($candidate);
                }
            }
            $nav    = generate_smart_preset_component('navbar');
            $body   = generate_smart_preset_component($prompt);
            $footer = generate_smart_preset_component('footer');

            return [
                'operation'    => 'create_frames',
                'html'         => '',
                'summary'      => "Frame baru '{$page_title}' berhasil dibuat.",
                'frames_specs' => [
                    [
                        'frame_name' => $page_title,
                        'html'       => ensure_gm_object_class($nav . "\n" . $body . "\n" . $footer),
                    ],
                ],
            ];
        }

        // 2. Jika meminta membaca / menjelaskan isi frame
        if (preg_match('/\b(baca|isi frame|apa saja|analisis|cek frame|lihat isi)\b/i', $lower)) {
            if (trim($current_frame_html) === '') {
                return [
                    'operation' => 'explain',
                    'html'      => '',
                    'summary'   => 'Frame aktif saat ini masih kosong (belum ada komponen .gm-object).',
                ];
            }
            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $current_frame_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $root = $doc->getElementById('gm-root');
            $items = [];
            if ($root) {
                $idx = 1;
                foreach ($root->childNodes as $node) {
                    if ($node->nodeType === XML_ELEMENT_NODE) {
                        $tag  = strtolower($node->nodeName);
                        $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
                        $short = mb_substr($text, 0, 45);
                        $items[] = "{$idx}. <{$tag}> " . ($short !== '' ? "\"{$short}...\"" : '(tanpa teks)');
                        $idx++;
                    }
                }
            }
            $summary = empty($items)
                ? 'Frame aktif masih kosong.'
                : ('Terdeteksi ' . count($items) . ' komponen utama di frame: ' . implode(' | ', $items));

            return [
                'operation' => 'explain',
                'html'      => $current_frame_html,
                'summary'   => $summary,
            ];
        }

        // 3. Jika meminta menghapus semua / mengosongkan frame
        if (preg_match('/\b(hapus semua|kosongkan|bersihkan frame|clear all|delete all)\b/i', $lower)) {
            return [
                'operation' => 'replace_frame',
                'html'      => '',
                'summary'   => 'Seluruh komponen di dalam frame telah dihapus.',
            ];
        }

        // 4. Jika meminta menghapus komponen tertentu (misal: hapus navbar, hapus footer, hapus hero, hapus pricing, hapus form)
        if (preg_match('/\b(hapus|hilangkan|buang|delete|remove)\b/i', $lower) && trim($current_frame_html) !== '') {
            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $current_frame_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $root = $doc->getElementById('gm-root');
            if ($root) {
                $to_remove = [];
                $children  = [];
                foreach ($root->childNodes as $node) {
                    if ($node->nodeType === XML_ELEMENT_NODE) {
                        $children[] = $node;
                    }
                }

                foreach ($children as $idx => $node) {
                    $tag  = strtolower($node->nodeName);
                    $text = strtolower($node->textContent);
                    $match = false;

                    if ((str_contains($lower, 'nav') || str_contains($lower, 'header') || str_contains($lower, 'menu')) && ($tag === 'nav' || $tag === 'header')) {
                        $match = true;
                    } elseif (str_contains($lower, 'footer') && $tag === 'footer') {
                        $match = true;
                    } elseif (str_contains($lower, 'hero') && ($tag === 'section' || $tag === 'div') && ($idx === 0 || str_contains($text, 'mulai') || $node->getElementsByTagName('h1')->length > 0)) {
                        $match = true;
                    } elseif ((str_contains($lower, 'pricing') || str_contains($lower, 'harga') || str_contains($lower, 'paket')) && (str_contains($text, 'rp') || str_contains($text, 'harga') || str_contains($text, 'starter') || str_contains($text, 'pro'))) {
                        $match = true;
                    } elseif ((str_contains($lower, 'form') || str_contains($lower, 'kontak')) && ($node->getElementsByTagName('input')->length > 0 || $node->getElementsByTagName('textarea')->length > 0 || str_contains($text, 'hubungi'))) {
                        $match = true;
                    } elseif ((str_contains($lower, 'terakhir') || str_contains($lower, 'bawah')) && $idx === count($children) - 1) {
                        $match = true;
                    } elseif ((str_contains($lower, 'pertama') || str_contains($lower, 'atas')) && $idx === 0) {
                        $match = true;
                    }

                    if ($match) {
                        $to_remove[] = $node;
                    }
                }

                // Jika belum ada yang cocok tetapi ada kata kunci teks spesifik
                if (empty($to_remove) && !empty($children)) {
                    $keywords = preg_replace('/\b(hapus|hilangkan|buang|delete|remove|komponen|bagian|section|elemen|di|dalam|frame)\b/i', '', $lower);
                    $keywords = trim($keywords);
                    if ($keywords !== '') {
                        foreach ($children as $node) {
                            if (str_contains(strtolower($node->textContent), $keywords) || strtolower($node->nodeName) === $keywords) {
                                $to_remove[] = $node;
                            }
                        }
                    }
                }

                if (!empty($to_remove)) {
                    foreach ($to_remove as $rem) {
                        $root->removeChild($rem);
                    }
                    $new_html = '';
                    foreach ($root->childNodes as $child) {
                        $new_html .= $doc->saveHTML($child);
                    }
                    return [
                        'operation' => 'replace_frame',
                        'html'      => trim($new_html),
                        'summary'   => count($to_remove) . ' komponen berhasil dihapus dari frame.',
                    ];
                }
            }
        }

        // 5. Jika meminta mengubah atribut / style (font, warna, ukuran, background, border-radius) pada elemen yang sudah ada tanpa membuat ulang dari 0
        if (preg_match('/\b(ubah|ganti|rubah|jadikan|set|font|warna|color|background|latar|ukuran|perbesar|perkecil|bold|tebal|miring|italic|radius|rounded)\b/i', $lower)) {
            $is_editing_selected = ($target_mode === 'edit_selected' || trim($selected_object_html) !== '') && trim($selected_object_html) !== '';
            $target_html         = $is_editing_selected ? $selected_object_html : $current_frame_html;

            if (trim($target_html) !== '') {
                $css_patches = [];

                // Deteksi perubahan Font Family
                $font_map = [
                    'poppins'          => "'Poppins', sans-serif",
                    'roboto'           => "'Roboto', sans-serif",
                    'inter'            => "'Inter', sans-serif",
                    'montserrat'       => "'Montserrat', sans-serif",
                    'open sans'        => "'Open Sans', sans-serif",
                    'lato'             => "'Lato', sans-serif",
                    'playfair'         => "'Playfair Display', serif",
                    'georgia'          => "Georgia, serif",
                    'times'            => "'Times New Roman', serif",
                    'courier'          => "'Courier New', monospace",
                    'monospace'        => "monospace",
                    'serif'            => "serif",
                    'sans-serif'       => "sans-serif",
                    'arial'            => "Arial, sans-serif",
                ];
                foreach ($font_map as $key => $font_val) {
                    if (str_contains($lower, $key)) {
                        $css_patches['font-family'] = $font_val;
                        break;
                    }
                }
                if (!isset($css_patches['font-family']) && str_contains($lower, 'font')) {
                    if (preg_match('/font(?:\s+family)?(?:\s+jadi|\s+menjadi|\s+ke)?\s+([a-zA-Z0-9\s\-]{3,25})/i', $prompt, $fm)) {
                        $f_clean = trim($fm[1]);
                        if ($f_clean !== '') {
                            $css_patches['font-family'] = "'" . ucwords($f_clean) . "', sans-serif";
                        }
                    } else {
                        $css_patches['font-family'] = "Georgia, serif";
                    }
                }

                // Deteksi perubahan Warna (Color / Background Color)
                $color_map = [
                    'merah'   => '#ef4444',
                    'red'     => '#ef4444',
                    'biru'    => '#2563eb',
                    'blue'    => '#2563eb',
                    'hijau'   => '#10b981',
                    'green'   => '#10b981',
                    'kuning'  => '#eab308',
                    'yellow'  => '#eab308',
                    'ungu'    => '#8b5cf6',
                    'purple'  => '#8b5cf6',
                    'oranye'  => '#f97316',
                    'orange'  => '#f97316',
                    'pink'    => '#ec4899',
                    'hitam'   => '#0f172a',
                    'gelap'   => '#0f172a',
                    'dark'    => '#0f172a',
                    'putih'   => '#ffffff',
                    'white'   => '#ffffff',
                    'abu'     => '#64748b',
                    'gray'    => '#64748b',
                ];
                $detected_color = null;
                if (preg_match('/#([0-9a-fA-F]{3,6})\b/', $prompt, $hex_m)) {
                    $detected_color = '#' . $hex_m[1];
                } else {
                    foreach ($color_map as $c_name => $c_hex) {
                        if (str_contains($lower, $c_name)) {
                            $detected_color = $c_hex;
                            break;
                        }
                    }
                }

                if ($detected_color !== null) {
                    if (preg_match('/\b(background|latar|bg|kotak|card|section|navbar|footer)\b/i', $lower)) {
                        $css_patches['background-color'] = $detected_color;
                    } elseif (preg_match('/\b(teks|tulisan|font|judul|heading|title)\b/i', $lower)) {
                        $css_patches['color'] = $detected_color;
                    } else {
                        $css_patches['__auto_color__'] = $detected_color;
                    }
                }

                // Deteksi ukuran font (font-size)
                if (preg_match('/(\d{1,3})\s*px\b/i', $lower, $px_m)) {
                    if (str_contains($lower, 'radius') || str_contains($lower, 'rounded')) {
                        $css_patches['border-radius'] = $px_m[1] . 'px';
                    } else {
                        $css_patches['font-size'] = $px_m[1] . 'px';
                    }
                }

                if (!empty($css_patches)) {
                    $doc = new DOMDocument();
                    @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $target_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $root = $doc->getElementById('gm-root');
                    if ($root) {
                        $apply_style_to_node = function (DOMElement $el, array $patches) {
                            $existing_style = (string)$el->getAttribute('style');
                            $style_map      = [];
                            foreach (explode(';', $existing_style) as $decl) {
                                $parts = explode(':', $decl, 2);
                                if (count($parts) === 2) {
                                    $k = strtolower(trim($parts[0]));
                                    $v = trim($parts[1]);
                                    if ($k !== '' && $v !== '') {
                                        $style_map[$k] = $v;
                                    }
                                }
                            }
                            $tag = strtolower($el->nodeName);
                            foreach ($patches as $prop => $val) {
                                if ($prop === '__auto_color__') {
                                    if ($tag === 'button' || isset($style_map['background-color']) || isset($style_map['background'])) {
                                        $style_map['background-color'] = $val;
                                    } else {
                                        $style_map['color'] = $val;
                                    }
                                } else {
                                    $style_map[$prop] = $val;
                                }
                            }
                            $rebuilt = [];
                            foreach ($style_map as $k => $v) {
                                $rebuilt[] = $k . ':' . $v;
                            }
                            $el->setAttribute('style', implode(';', $rebuilt) . ';');
                        };

                        if ($is_editing_selected) {
                            foreach ($root->childNodes as $child) {
                                if ($child instanceof DOMElement) {
                                    $apply_style_to_node($child, $css_patches);
                                    if (isset($css_patches['font-family'])) {
                                        foreach ($child->getElementsByTagName('*') as $sub) {
                                            if ($sub instanceof DOMElement && str_contains(strtolower((string)$sub->getAttribute('style')), 'font-family')) {
                                                $apply_style_to_node($sub, ['font-family' => $css_patches['font-family']]);
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            // Cari elemen spesifik yang dimaksud di dalam frame (misal tombol, judul/h1, navbar, footer)
                            $matched_nodes = [];
                            $all_elements  = $root->getElementsByTagName('*');
                            foreach ($all_elements as $el) {
                                if (!($el instanceof DOMElement)) {
                                    continue;
                                }
                                $tag = strtolower($el->nodeName);
                                if (str_contains($lower, 'tombol') || str_contains($lower, 'button')) {
                                    if ($tag === 'button') {
                                        $matched_nodes[] = $el;
                                    }
                                } elseif (str_contains($lower, 'judul') || str_contains($lower, 'heading') || str_contains($lower, 'title')) {
                                    if (in_array($tag, ['h1', 'h2', 'h3'], true)) {
                                        $matched_nodes[] = $el;
                                    }
                                } elseif (str_contains($lower, 'navbar') || str_contains($lower, 'header')) {
                                    if ($tag === 'nav' || $tag === 'header') {
                                        $matched_nodes[] = $el;
                                    }
                                } elseif (str_contains($lower, 'footer')) {
                                    if ($tag === 'footer') {
                                        $matched_nodes[] = $el;
                                    }
                                }
                            }
                            if (empty($matched_nodes)) {
                                foreach ($root->childNodes as $child) {
                                    if ($child instanceof DOMElement) {
                                        $matched_nodes[] = $child;
                                    }
                                }
                            }
                            foreach ($matched_nodes as $m_node) {
                                $apply_style_to_node($m_node, $css_patches);
                            }
                        }

                        $out_html = '';
                        foreach ($root->childNodes as $child) {
                            $out_html .= $doc->saveHTML($child);
                        }

                        $target_selector = 'self';
                        if (!$is_editing_selected && !empty($matched_nodes)) {
                            $target_selector = strtolower($matched_nodes[0]->nodeName);
                        }

                        return [
                            'operation' => 'patch_attributes',
                            'patch'     => [
                                'target'  => $target_selector,
                                'styles'  => $css_patches,
                                'summary' => 'Atribut elemen (' . implode(', ', array_keys($css_patches)) . ') berhasil diperbarui.',
                            ],
                            'html'      => trim($out_html),
                            'summary'   => 'Atribut elemen berhasil diperbarui secara langsung.',
                        ];
                    }
                }
            }
        }

        return null;
    }
}

/**
 * Memanggil Gemini Interactions API menggunakan model Free Tier (Gratis) dengan konteks isi Frame aktif.
 * Mendukung operasi:
 * - PATCH_ATTRIBUTES (membaca komponen dulu & hanya mengubah/menambah atribut/style tanpa membuat dari nol)
 * - APPEND (menambah komponen baru ke frame aktif)
 * - REPLACE_SELECTED (mengubah objek yang dipilih jika merombak struktur)
 * - REPLACE_FRAME (menghapus komponen di dalam frame atau mengubah halaman)
 * - CREATE_FRAMES (membuat 1 atau beberapa frame/halaman baru otomatis sesuai kebutuhan pages)
 * - EXPLAIN (membaca dan menjelaskan isi frame kepada pengguna)
 */
if (!function_exists('call_gemini_interactions_api')) {
    function call_gemini_interactions_api(
        string $api_key,
        string $prompt,
        string $model = 'gemini-3.5-flash-lite',
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = []
    ): array {
        $system_instruction = "Anda adalah AI Visual Workspace Architect untuk editor web Greymist.\n"
            . "Anda memiliki CACHE MEMORI PROYEK yang mencatat seluruh halaman (frames), susunan komponen di setiap halaman, dan riwayat perubahan yang telah Anda buat sebelumnya.\n"
            . "Gunakan memori tersebut agar setiap perubahan, penambahan, atau penghapusan selalu konsisten dengan sistem desain dan susunan halaman yang sudah ada.\n\n"
            . "ATURAN UTAMA: BACA KOMPONEN DULU & UBAH ATRIBUTNYA (JANGAN MEMBUAT BARU DARI NOL):\n"
            . "Jika pengguna meminta MENGUBAH atau MENAMBAHKAN atribut/gaya (seperti warna, font, background, ukuran font, border, radius, padding, margin, shadow, atau teks) pada suatu komponen yang sudah ada:\n"
            . "1. DILARANG KERAS MEMBUAT ELEMEN/KOMPONEN BARU DARI NOL!\n"
            . "2. DILARANG MENGHASILKAN KODE HTML BARU LENGKAP ATAU MENUMPANGKAN ELEMEN BARU DI ATAS ELEMEN LAMA!\n"
            . "3. BACA atribut dan style yang sudah ada pada komponen (SELECTED_OBJECT_HTML atau elemen di CURRENT_FRAME_HTML).\n"
            . "4. Tentukan HANYA properti CSS (style) atau teks apa yang perlu DIUBAH atau DITAMBAHKAN sesuai instruksi pengguna.\n"
            . "5. Kembalikan HANYA penanda <!--GM_OP:PATCH_ATTRIBUTES--> diikuti satu objek JSON valid persis format berikut:\n"
            . "   <!--GM_OP:PATCH_ATTRIBUTES-->\n"
            . "   {\n"
            . "     \"target\": \"self\",\n"
            . "     \"styles\": {\n"
            . "       \"background-color\": \"#38bdf8\",\n"
            . "       \"color\": \"#ffffff\"\n"
            . "     },\n"
            . "     \"attributes\": {},\n"
            . "     \"text\": null,\n"
            . "     \"summary\": \"Warna latar belakang diubah menjadi biru muda\"\n"
            . "   }\n\n"
            . "PILIHAN FORMAT OPERASI LAINNYA (Hanya jika BUKAN sekadar ubah atribut/style):\n"
            . "1. Jika pengguna meminta MEMBACA / MENJELASKAN apa yang ada di dalam frame atau proyek (tanpa mengubah kode):\n"
            . "   Awali dengan: <!--GM_OP:EXPLAIN--> diikuti penjelasan ringkas isi frame & struktur halaman dalam Bahasa Indonesia.\n"
            . "2. Jika pengguna meminta MENGHAPUS komponen tertentu lewat prompt (misal 'hapus navbar', 'hapus bagian harga', 'hapus tombol', 'kosongkan frame'):\n"
            . "   Awali dengan: <!--GM_OP:REPLACE_FRAME--> diikuti SELURUH kode HTML frame setelah komponen dihapus.\n"
            . "3. Jika pengguna meminta MEMBUAT HALAMAN BARU / MENAMBAH FRAME BARU, atau meminta BEBERAPA HALAMAN (Multi-Page):\n"
            . "   Gunakan blok penanda frame: <!--GM_FRAME:Nama Halaman--> diikuti kode HTML lengkap .gm-object untuk halaman tersebut.\n"
            . "4. Jika pengguna meminta MEMBUAT / MENAMBAHKAN komponen BARU dari nol (misal 'buat hero section', 'tambah form kontak', 'buat kartu harga'):\n"
            . "   Awali dengan: <!--GM_OP:APPEND--> diikuti HANYA kode HTML komponen baru tersebut.\n"
            . "   Setiap tag WAJIB memiliki class=\"gm-object\" dan style=\"...\". Jangan gunakan markdown code fences.";

        $context_input = "";
        if ($memory_context_text !== '') {
            $context_input .= $memory_context_text . "\n\n";
        }
        $context_input .= "TARGET_MODE: " . $target_mode . "\n";
        $context_input .= "CURRENT_FRAME_HTML (Frame yang sedang dibuka pengguna):\n" . ($current_frame_html !== '' ? $current_frame_html : "(Frame masih kosong)") . "\n\n";
        if ($selected_object_html !== '') {
            $context_input .= "SELECTED_OBJECT_HTML (Komponen yang sedang aktif dipilih):\n" . $selected_object_html . "\n";
            if (!empty($selected_object_attrs)) {
                $context_input .= "ATRIBUT SAAT INI: " . json_encode($selected_object_attrs, JSON_UNESCAPED_UNICODE) . "\n";
            }
            $context_input .= "\n";
        }
        $context_input .= "INSTRUKSI PENGGUNA:\n" . $prompt;

        // Urutan model Free Tier (Gratis) sebagai fallback otomatis jika salah satu terkena limit kuota
        $free_models = array_values(array_unique([
            $model,
            'gemini-3.5-flash-lite',
            'gemini-3.6-flash',
            'gemini-3.1-flash-lite',
            'gemini-2.5-flash-lite',
        ]));

        $last_error = '';

        foreach ($free_models as $candidate_model) {
            $payload = [
                'model'              => $candidate_model,
                'system_instruction' => $system_instruction,
                'input'              => $context_input,
                'generation_config'  => [
                    'max_output_tokens' => 65536,
                ],
            ];

            $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/interactions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'x-goog-api-key: ' . $api_key,
                    'Content-Type: application/json',
                    'Api-Revision: 2026-05-20',
                ],
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response_body = curl_exec($ch);
            $http_code     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_err      = curl_error($ch);
            unset($ch);

            if ($response_body === false || $curl_err !== '') {
                $last_error = 'Koneksi cURL gagal: ' . $curl_err;
                continue;
            }

            $decoded = json_decode((string)$response_body, true);
            if ($http_code >= 400 || !is_array($decoded)) {
                $last_error = $decoded['error']['message'] ?? ('HTTP Error ' . $http_code);
                continue;
            }

            // Ekstrak teks dari struktur steps[] (model_output -> content -> text) pada Interactions API
            $output_text = '';
            if (!empty($decoded['output_text']) && is_string($decoded['output_text'])) {
                $output_text = $decoded['output_text'];
            } elseif (!empty($decoded['steps']) && is_array($decoded['steps'])) {
                foreach ($decoded['steps'] as $step) {
                    if (($step['type'] ?? '') === 'model_output' && !empty($step['content']) && is_array($step['content'])) {
                        foreach ($step['content'] as $part) {
                            if (($part['type'] ?? '') === 'text' && isset($part['text'])) {
                                $output_text .= $part['text'];
                            }
                        }
                    }
                }
            }

            $trimmed_out = trim($output_text);
            if ($trimmed_out !== '') {
                // Bersihkan markdown fences jika model masih menyertakannya
                $trimmed_out = preg_replace('/^```(?:html)?\s*/i', '', $trimmed_out);
                $trimmed_out = preg_replace('/\s*```$/', '', $trimmed_out);

                // Cek apakah AI mengembalikan 1 atau beberapa blok halaman/frame baru: <!--GM_FRAME:Nama Halaman-->
                if (preg_match_all('/<!--\s*GM_FRAME\s*:\s*(.*?)\s*-->/is', $trimmed_out, $frame_matches, PREG_OFFSET_CAPTURE)) {
                    $frames_specs = [];
                    $count_matches = count($frame_matches[0]);
                    for ($i = 0; $i < $count_matches; $i++) {
                        $f_name = trim($frame_matches[1][$i][0]);
                        if ($f_name === '') {
                            $f_name = 'Page ' . ($i + 1);
                        }
                        $start_pos = $frame_matches[0][$i][1] + strlen($frame_matches[0][$i][0]);
                        $end_pos   = ($i + 1 < $count_matches) ? $frame_matches[0][$i + 1][1] : strlen($trimmed_out);
                        $f_html    = trim(substr($trimmed_out, $start_pos, $end_pos - $start_pos));
                        $f_html    = str_replace(['<!--GM_OP:APPEND-->', '<!--GM_OP:REPLACE_FRAME-->'], '', $f_html);
                        if ($f_html !== '') {
                            $frames_specs[] = [
                                'frame_name' => $f_name,
                                'html'       => ensure_gm_object_class($f_html),
                            ];
                        }
                    }

                    if (!empty($frames_specs)) {
                        return [
                            'success'      => true,
                            'used_model'   => $candidate_model,
                            'operation'    => 'create_frames',
                            'frames_specs' => $frames_specs,
                            'html'         => '',
                        ];
                    }
                }

                $operation = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
                if (str_contains($trimmed_out, '<!--GM_OP:PATCH_ATTRIBUTES-->')) {
                    $json_str = trim(str_replace('<!--GM_OP:PATCH_ATTRIBUTES-->', '', $trimmed_out));
                    $json_str = preg_replace('/^```(?:json)?\s*/i', '', $json_str);
                    $json_str = preg_replace('/\s*```$/', '', $json_str);
                    $patch_data = json_decode($json_str, true);
                    if (is_array($patch_data) && (!empty($patch_data['styles']) || !empty($patch_data['attributes']) || !empty($patch_data['text']))) {
                        return [
                            'success'    => true,
                            'used_model' => $candidate_model,
                            'operation'  => 'patch_attributes',
                            'patch'      => $patch_data,
                            'summary'    => $patch_data['summary'] ?? 'Atribut elemen berhasil diperbarui.',
                            'html'       => '',
                        ];
                    }
                } elseif (str_contains($trimmed_out, '<!--GM_OP:EXPLAIN-->')) {
                    $operation   = 'explain';
                    $trimmed_out = trim(str_replace('<!--GM_OP:EXPLAIN-->', '', $trimmed_out));
                    return [
                        'success'    => true,
                        'used_model' => $candidate_model,
                        'operation'  => 'explain',
                        'summary'    => $trimmed_out,
                        'html'       => $current_frame_html,
                    ];
                } elseif (str_contains($trimmed_out, '<!--GM_OP:REPLACE_SELECTED-->')) {
                    $operation   = 'replace_selected';
                    $trimmed_out = trim(str_replace('<!--GM_OP:REPLACE_SELECTED-->', '', $trimmed_out));
                } elseif (str_contains($trimmed_out, '<!--GM_OP:REPLACE_FRAME-->')) {
                    $operation   = 'replace_frame';
                    $trimmed_out = trim(str_replace('<!--GM_OP:REPLACE_FRAME-->', '', $trimmed_out));
                } elseif (str_contains($trimmed_out, '<!--GM_OP:APPEND-->')) {
                    $operation   = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
                    $trimmed_out = trim(str_replace('<!--GM_OP:APPEND-->', '', $trimmed_out));
                }

                return [
                    'success'    => true,
                    'used_model' => $candidate_model,
                    'operation'  => $operation,
                    'html'       => $trimmed_out !== '' ? ensure_gm_object_class($trimmed_out) : '',
                ];
            }

            $last_error = 'Respons Gemini kosong.';
        }

        return ['success' => false, 'error' => $last_error];
    }
}

if (defined('AI_ACTION_LIBRARY_ONLY')) {
    return;
}

$user_id = current_user_id() ?? (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);
if (!$user_id) {
    json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Silakan login terlebih dahulu.'], 401);
}

$ai_action = $_GET['action'] ?? $_POST['action'] ?? 'ai_generate_component';
$raw_input = file_get_contents('php://input');
$data      = json_decode((string)$raw_input, true);
if (!is_array($data)) {
    $data = $_POST;
}

switch ($ai_action) {
    case 'ai_get_memory':
        $project_id = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $snapshot   = isset($data['frames_snapshot']) && is_array($data['frames_snapshot']) ? $data['frames_snapshot'] : [];
        $memory     = get_project_ai_memory($pdo, $project_id, $snapshot);
        json_response([
            'status'    => 'OK',
            'success'   => true,
            'ai_memory' => $memory,
        ]);
        break;

    case 'ai_save_api_key':
        $new_key = trim((string)($data['api_key'] ?? ''));
        $env_file = ROOT_PATH . '/.env';

        if (is_file($env_file) && is_writable($env_file)) {
            $env_content = (string)file_get_contents($env_file);
            if (preg_match('/^GEMINI_API_KEY=.*$/m', $env_content)) {
                $env_content = preg_replace('/^GEMINI_API_KEY=.*$/m', 'GEMINI_API_KEY=' . $new_key, $env_content);
            } else {
                $env_content = rtrim($env_content) . "\nGEMINI_API_KEY=" . $new_key . "\n";
            }
            file_put_contents($env_file, $env_content);
        }
        $_SESSION['gemini_api_key_override'] = $new_key;

        json_response([
            'status'  => 'OK',
            'success' => true,
            'msg'     => 'GEMINI_API_KEY berhasil disimpan ke .env.',
            'has_key' => $new_key !== '',
        ]);
        break;

    case 'ai_generate_component':
    default:
        $prompt               = trim((string)($data['prompt'] ?? ''));
        $save_to_library      = !empty($data['save_to_library']);
        $component_name       = trim((string)($data['component_name'] ?? ''));
        $inline_api_key       = trim((string)($data['api_key'] ?? ''));
        $requested_model      = trim((string)($data['model'] ?? ''));
        $current_frame_html   = (string)($data['current_frame_html'] ?? '');
        $selected_object_html = (string)($data['selected_object_html'] ?? '');
        $target_mode           = trim((string)($data['target_mode'] ?? 'core'));
        $project_id            = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $frames_snapshot       = isset($data['frames_snapshot']) && is_array($data['frames_snapshot']) ? $data['frames_snapshot'] : [];
        $selected_object_attrs = isset($data['selected_object_attrs']) && is_array($data['selected_object_attrs']) ? $data['selected_object_attrs'] : [];

        if ($prompt === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Prompt atau instruksi AI wajib diisi.']);
        }

        // Ambil & bangun cache memori arsitektur proyek sebelum memanggil Gemini
        $ai_memory           = get_project_ai_memory($pdo, $project_id, $frames_snapshot);
        $memory_context_text = format_ai_memory_for_prompt($ai_memory);

        $api_key = $inline_api_key !== ''
            ? $inline_api_key
            : ((string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', '')));
        $model   = $requested_model !== ''
            ? $requested_model
            : (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite');

        $source_mode  = 'smart_preset (Gratis Lokal)';
        $api_warning  = '';
        $operation    = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
        $summary_msg  = '';
        $frames_specs = [];
        $patch_data   = null;

        if ($model !== 'local-free' && $api_key !== '') {
            $gemini_res = call_gemini_interactions_api(
                $api_key,
                $prompt,
                $model,
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs
            );
            if ($gemini_res['success']) {
                $generated_html = $gemini_res['html'] ?? '';
                $operation      = $gemini_res['operation'] ?? $operation;
                $summary_msg    = $gemini_res['summary'] ?? '';
                $frames_specs   = $gemini_res['frames_specs'] ?? [];
                $patch_data     = $gemini_res['patch'] ?? null;
                $used_model     = $gemini_res['used_model'] ?? $model;
                $source_mode    = 'Gemini Free Tier (' . $used_model . ')';
            } else {
                $api_warning = $gemini_res['error'];
                $local_cmd   = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode);
                if ($local_cmd !== null) {
                    $operation      = $local_cmd['operation'];
                    $generated_html = $local_cmd['html'] ?? '';
                    $summary_msg    = $local_cmd['summary'] ?? '';
                    $frames_specs   = $local_cmd['frames_specs'] ?? [];
                    $patch_data     = $local_cmd['patch'] ?? null;
                } else {
                    $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
                }
            }
        } else {
            $local_cmd = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode);
            if ($local_cmd !== null) {
                $operation      = $local_cmd['operation'];
                $generated_html = $local_cmd['html'] ?? '';
                $summary_msg    = $local_cmd['summary'] ?? '';
                $frames_specs   = $local_cmd['frames_specs'] ?? [];
                $patch_data     = $local_cmd['patch'] ?? null;
            } else {
                $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
            }
        }

        // Jika pengguna memilih target_mode = 'new_frame' tetapi AI mengembalikan operasi append biasa,
        // otomatis bungkus menjadi pembuatan frame baru.
        if ($target_mode === 'new_frame' && $operation === 'append' && trim($generated_html) !== '' && empty($frames_specs)) {
            $new_page_name  = $component_name !== '' && $component_name !== 'AI Component'
                ? $component_name
                : ('Page: ' . mb_substr($prompt, 0, 22));
            $operation      = 'create_frames';
            $frames_specs[] = [
                'frame_name' => $new_page_name,
                'html'       => $generated_html,
            ];
        }

        // Simpan frame baru ke database gm_frames jika operation === 'create_frames'
        $created_frames = [];
        if ($operation === 'create_frames' && !empty($frames_specs)) {
            foreach ($frames_specs as $idx => $spec) {
                $f_name = trim((string)($spec['frame_name'] ?? ('Page ' . ($idx + 1))));
                $f_html = (string)($spec['html'] ?? '');
                if ($project_id > 0) {
                    $db_frame = add_project_frame($pdo, $project_id, $f_name, $f_html);
                    $created_frames[] = [
                        'frame_id'     => $db_frame['frame_id'],
                        'frame_name'   => $db_frame['frame_name'],
                        'core_content' => $f_html,
                    ];
                } else {
                    $created_frames[] = [
                        'frame_id'     => time() + $idx,
                        'frame_name'   => $f_name,
                        'core_content' => $f_html,
                    ];
                }
            }
        }

        $saved_component = null;
        if ($save_to_library && $operation === 'append' && trim($generated_html) !== '') {
            $final_name = $component_name !== '' ? $component_name : ('AI: ' . mb_substr($prompt, 0, 32));
            $comp_id    = create_user_component($pdo, (int)$user_id, $final_name, $generated_html, 'AI Generated');
            $saved_component = [
                'component_id'   => $comp_id,
                'user_id'        => (int)$user_id,
                'component_name' => $final_name,
                'html_content'   => $generated_html,
                'category'       => 'AI Generated',
            ];
        }

        $default_msg = match ($operation) {
            'explain'          => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil dianalisis.',
            'patch_attributes' => $summary_msg !== '' ? $summary_msg : ('Atribut elemen berhasil diperbarui (' . $source_mode . ').'),
            'replace_selected' => 'Objek aktif berhasil diperbarui (' . $source_mode . ').',
            'replace_frame'    => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil diperbarui (' . $source_mode . ').',
            'create_frames'    => count($created_frames) . ' Frame Halaman baru berhasil ditambahkan (' . $source_mode . ').',
            default            => 'Komponen berhasil ditambahkan (' . $source_mode . ').',
        };

        // Perbarui & simpan cache memori AI proyek ke database gm_projects
        if ($project_id > 0) {
            $updated_memory = get_project_ai_memory($pdo, $project_id, []);
            $updated_memory['history'][] = [
                'time'           => date('H:i:s'),
                'prompt'         => mb_substr($prompt, 0, 140),
                'operation'      => $operation,
                'action_summary' => $default_msg,
            ];
            if ($operation === 'create_frames' && !empty($created_frames)) {
                $page_names = array_map(fn($cf) => $cf['frame_name'], $created_frames);
                $updated_memory['system_summary'] = 'Sistem memiliki halaman: ' . implode(', ', $page_names) . ' (instruksi: ' . mb_substr($prompt, 0, 100) . ')';
            } elseif (empty($updated_memory['system_summary'])) {
                $updated_memory['system_summary'] = 'Proyek dibangun dengan instruksi: ' . mb_substr($prompt, 0, 100);
            }
            save_project_ai_memory($pdo, $project_id, $updated_memory);
            $ai_memory = $updated_memory;
        }

        json_response([
            'status'         => 'OK',
            'success'        => true,
            'operation'      => $operation,
            'patch'          => $patch_data,
            'summary'        => $summary_msg,
            'msg'            => $default_msg,
            'source_mode'    => $source_mode,
            'api_warning'    => $api_warning,
            'html'           => $generated_html,
            'created_frames' => $created_frames,
            'component'      => $saved_component,
            'ai_memory'      => $ai_memory,
        ]);
        break;
}
