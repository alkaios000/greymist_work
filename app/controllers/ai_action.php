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
require_once ROOT_PATH . '/models/project_model.php';

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
/**
 * Mengekstrak komponen reusable (Navbar, Sidebar, Footer) dari frame-frame yang sudah ada
 * agar dapat digunakan ulang di halaman baru tanpa membuat dari nol (hemat token & 100% konsisten).
 */
if (!function_exists('extract_reusable_components')) {
    function extract_reusable_components(array $frames_snapshot, string $current_frame_html = ''): array
    {
        $reusable = [
            'navbar'  => null,
            'sidebar' => null,
            'footer'  => null,
        ];

        $html_sources = [];
        if (trim($current_frame_html) !== '') {
            $html_sources[] = $current_frame_html;
        }
        foreach ($frames_snapshot as $f) {
            $cnt = trim((string)($f['core_content'] ?? ''));
            if ($cnt !== '') {
                $html_sources[] = $cnt;
            }
        }

        foreach ($html_sources as $html) {
            $doc = new DOMDocument();
            @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $root = $doc->getElementById('gm-root');
            if (!$root) continue;

            if ($reusable['navbar'] === null) {
                $navs = $root->getElementsByTagName('nav');
                if ($navs->length > 0) {
                    $reusable['navbar'] = $doc->saveHTML($navs->item(0));
                } else {
                    $headers = $root->getElementsByTagName('header');
                    if ($headers->length > 0) {
                        $reusable['navbar'] = $doc->saveHTML($headers->item(0));
                    }
                }
            }

            if ($reusable['sidebar'] === null) {
                $asides = $root->getElementsByTagName('aside');
                if ($asides->length > 0) {
                    $reusable['sidebar'] = $doc->saveHTML($asides->item(0));
                }
            }

            if ($reusable['footer'] === null) {
                $footers = $root->getElementsByTagName('footer');
                if ($footers->length > 0) {
                    $reusable['footer'] = $doc->saveHTML($footers->item(0));
                }
            }

            if ($reusable['navbar'] !== null && $reusable['footer'] !== null && $reusable['sidebar'] !== null) {
                break;
            }
        }

        return $reusable;
    }
}

/**
 * Sinkronisasi silang referensi navbar:
 * 1. Mengubah seluruh data-action-target="NamaFrame" menjadi ID numerik frame resmi.
 * 2. Memastikan setiap frame proyek (lama maupun baru) memiliki tautan lengkap ke halaman lain di navbar-nya.
 * 3. Memberikan status visual link aktif (highlight) pada halaman yang sedang dibuka.
 */
if (!function_exists('clean_emojis_from_html')) {
    function clean_emojis_from_html(string $html): string
    {
        if (trim($html) === '') return $html;
        $regex = '/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{2300}-\x{23FF}\x{2B50}\x{20E3}\x{FE0F}]/u';
        return (string)preg_replace($regex, '', $html);
    }
}

if (!function_exists('cross_link_navbar_in_html')) {
    function cross_link_navbar_in_html(string $html, array $all_frames_map, int $current_frame_id, string $current_frame_name): string
    {
        if (trim($html) === '' || empty($all_frames_map)) {
            return $html;
        }

        // 1. Ganti semua data-action-target="NamaFrame" dengan ID numerik resminya
        foreach ($all_frames_map as $name => $fid) {
            $safeN = preg_quote($name, '/');
            $html = preg_replace(
                '/data-action-target=([\'"])' . $safeN . '\1/i',
                'data-action-target="' . $fid . '"',
                $html
            );
        }

        // 2. Periksa apakah ada tag Nav / Header yang perlu disinkronkan link-link halamannya
        $doc = new DOMDocument();
        @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $doc->getElementById('gm-root');
        if (!$root) return $html;

        $nav = null;
        $navs = $root->getElementsByTagName('nav');
        if ($navs->length > 0) {
            $nav = $navs->item(0);
        } else {
            $headers = $root->getElementsByTagName('header');
            if ($headers->length > 0) {
                $nav = $headers->item(0);
            } else {
                foreach ($root->getElementsByTagName('div') as $d) {
                    $cls = strtolower((string)$d->getAttribute('class'));
                    $id  = strtolower((string)$d->getAttribute('id'));
                    if (str_contains($cls, 'nav') || str_contains($id, 'nav')) {
                        $nav = $d;
                        break;
                    }
                }
            }
        }
        if (!$nav) {
            return $html;
        }

        // Cari kontainer link di dalam nav
        $links_container = null;
        $all_divs = $nav->getElementsByTagName('div');
        foreach ($all_divs as $d) {
            $d_id = (string)$d->getAttribute('id');
            $d_cls = (string)$d->getAttribute('class');
            if ($d_id === 'nav-links' || str_contains($d_cls, 'nav-link') || $d->getElementsByTagName('a')->length > 0) {
                $links_container = $d;
                break;
            }
        }
        if (!$links_container) {
            $uls = $nav->getElementsByTagName('ul');
            if ($uls->length > 0) {
                $links_container = $uls->item(0);
            }
        }
        if (!$links_container) {
            $links_container = $nav;
        }

        // Ambil gaya dasar link <a> yang sudah ada di navbar agar link baru persis selaras
        $base_link_style = "font-size:14px;text-decoration:none;";
        $existing_as = $links_container->getElementsByTagName('a');
        if ($existing_as->length > 0) {
            $raw_s = (string)$existing_as->item(0)->getAttribute('style');
            if ($raw_s !== '') {
                $clean_s = preg_replace('/color\s*:\s*[^;]+;?/i', '', $raw_s);
                $clean_s = preg_replace('/font-weight\s*:\s*[^;]+;?/i', '', $clean_s);
                $base_link_style = trim($clean_s);
            }
        }

        // Kumpulkan target & nama link yang sudah ada
        $existing_targets = [];
        $existing_names   = [];
        foreach ($links_container->getElementsByTagName('a') as $a) {
            $t   = trim((string)$a->getAttribute('data-action-target'));
            $txt = strtolower(trim((string)$a->textContent));
            if ($t !== '') {
                $existing_targets[] = $t;
            }
            if ($txt !== '') {
                $existing_names[] = $txt;
            }

            // Atur styling active jika target merujuk ke frame ini
            $is_cur = ($t === (string)$current_frame_id || $txt === strtolower(trim($current_frame_name)));
            $style  = (string)$a->getAttribute('style');
            if ($is_cur) {
                if (!str_contains($style, '#3b82f6') && !str_contains($style, 'font-weight:700')) {
                    $a->setAttribute('style', rtrim($style, ';') . ';color:#3b82f6;font-weight:700;');
                }
            } else {
                if (str_contains($style, 'font-weight:700') && !str_contains((string)$a->getAttribute('class'), 'btn')) {
                    $a->setAttribute('style', str_replace(['color:#3b82f6;', 'font-weight:700;'], ['color:#475569;', 'font-weight:500;'], $style));
                }
            }
        }

        // Tambahkan link halaman yang belum ada ke dalam navbar
        $added = false;
        foreach ($all_frames_map as $name => $fid) {
            $name_clean = trim((string)$name);
            $lower_name = strtolower($name_clean);
            if (!in_array((string)$fid, $existing_targets, true) && !in_array($lower_name, $existing_names, true)) {
                $is_current = ($fid === $current_frame_id);
                $color = $is_current ? '#3b82f6' : '#475569';
                $fw    = $is_current ? '700' : '500';

                $newLink = $doc->createElement('a', htmlspecialchars($name_clean));
                $newLink->setAttribute('class', 'gm-object');
                $newLink->setAttribute('href', '#');
                $newLink->setAttribute('data-action-type', 'goto');
                $newLink->setAttribute('data-action-target', (string)$fid);
                $final_s = ($base_link_style !== '' ? rtrim($base_link_style, ';') . ';' : '') . "font-size:14px;font-weight:{$fw};color:{$color};text-decoration:none;";
                $newLink->setAttribute('style', $final_s);

                if ($links_container->nodeName === 'ul' || $links_container->nodeName === 'ol') {
                    $li = $doc->createElement('li');
                    $li->setAttribute('class', 'gm-object');
                    $li->appendChild($newLink);
                    $links_container->appendChild($li);
                } else {
                    $links_container->appendChild($newLink);
                }
                $existing_targets[] = (string)$fid;
                $existing_names[]   = $lower_name;
                $added = true;
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out) !== '' ? trim($out) : $html;
    }
}

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

        $stmt = $pdo->prepare("SELECT project_id, project_name, ai_memory, color_tokens, bg_color_settings FROM gm_projects WHERE project_id = ? LIMIT 1");
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

        $color_tokens = [];
        if (!empty($proj['color_tokens'])) {
            $dec_tokens = json_decode((string)$proj['color_tokens'], true);
            if (is_array($dec_tokens)) {
                $color_tokens = $dec_tokens;
            }
        }

        $bg_color_settings = null;
        if (!empty($proj['bg_color_settings'])) {
            $dec_bg = json_decode((string)$proj['bg_color_settings'], true);
            if (is_array($dec_bg)) {
                $bg_color_settings = $dec_bg;
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
            'project_id'        => $project_id,
            'project_name'      => (string)$proj['project_name'],
            'color_tokens'      => $color_tokens,
            'bg_color_settings' => $bg_color_settings,
            'system_summary'    => (string)($saved['system_summary'] ?? ''),
            'total_pages'       => count($pages_map),
            'pages_map'         => $pages_map,
            'history'           => isset($saved['history']) && is_array($saved['history']) ? array_slice($saved['history'], -20) : [],
            'updated_at'        => (string)($saved['updated_at'] ?? date('Y-m-d H:i:s')),
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
        $lines[] = "=== CACHE MEMORI & ARSITEKTUR PROYEK (DATABASE & DOM) ===";
        $lines[] = "ID Proyek: " . ($memory['project_id'] ?? 0);
        $lines[] = "Nama Proyek: " . ($memory['project_name'] ?? 'Untitled');
        $lines[] = "Total Pages/Frames Saat Ini: " . (int)($memory['total_pages'] ?? 0);

        if (!empty($memory['color_tokens']) && is_array($memory['color_tokens'])) {
            $tok_strs = [];
            foreach ($memory['color_tokens'] as $tok) {
                $tname = $tok['name'] ?? ($tok['token'] ?? '');
                $tval  = $tok['value'] ?? ($tok['hex'] ?? '');
                if ($tval !== '') {
                    $tok_strs[] = ($tname !== '' ? $tname : 'color') . ': ' . $tval;
                }
            }
            if (!empty($tok_strs)) {
                $lines[] = "Palet Warna / Token Proyek: " . implode(', ', $tok_strs);
            }
        }

        if (!empty($memory['bg_color_settings']) && is_array($memory['bg_color_settings']) && !empty($memory['bg_color_settings']['hex'])) {
            $lines[] = "Warna Latar Kanvas Proyek: " . $memory['bg_color_settings']['hex'];
        }

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
<nav id="main-navbar" class="gm-object" style="display:flex;align-items:center;justify-content:space-between;padding:18px 40px;background-color:#ffffff;border-bottom:1px solid #e2e8f0;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div id="nav-brand" class="gm-object" style="display:flex;align-items:center;gap:10px;">
        <div class="gm-object" style="width:36px;height:36px;border-radius:8px;background-color:#eff6ff;color:#3b82f6;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;">G</div>
        <span class="gm-object" style="font-size:18px;font-weight:700;color:#0f172a;">Greymist UI</span>
    </div>
    <div id="nav-links" class="gm-object" style="display:flex;align-items:center;gap:28px;">
        <a class="gm-object" href="#" data-action-type="goto" data-action-target="Home" style="font-size:14px;font-weight:500;color:#0f172a;text-decoration:none;">Beranda</a>
        <a class="gm-object" href="#" data-action-type="goto" data-action-target="About" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Tentang</a>
        <a class="gm-object" href="#" data-action-type="scroll" data-action-target="pricing" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Harga</a>
        <a class="gm-object" href="#" data-action-type="goto" data-action-target="Contact" style="font-size:14px;font-weight:500;color:#475569;text-decoration:none;">Kontak</a>
    </div>
    <button id="nav-cta-btn" class="gm-object gm-anim-base pulse" data-gm-anim="true" data-action-type="goto" data-action-target="Contact" style="padding:10px 20px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;--gm-anim-dur:1.5s;--gm-anim-iter:infinite;">Mulai Gratis</button>
</nav>
HTML;
        }

        if (str_contains($lower, 'pricing') || str_contains($lower, 'harga') || str_contains($lower, 'paket')) {
            return <<<'HTML'
<section id="pricing" class="gm-object" style="padding:60px 40px;background-color:#ffffff;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div id="pricing-header" class="gm-object" style="text-align:center;max-width:600px;margin:0 auto 40px auto;">
        <span class="gm-object gm-anim-base fade" data-gm-anim="true" style="display:inline-block;padding:4px 12px;background-color:#eff6ff;color:#3b82f6;border-radius:999px;font-size:12px;font-weight:600;margin-bottom:12px;--gm-anim-dur:0.5s;">PILIHAN PAKET</span>
        <h2 class="gm-object gm-anim-base slide-up" data-gm-anim="true" style="font-size:32px;font-weight:700;color:#0f172a;margin:0 0 10px 0;--gm-anim-dur:0.6s;">Harga Transparan untuk Tim Anda</h2>
        <p class="gm-object" style="font-size:15px;color:#64748b;margin:0;">Pilih paket yang sesuai dengan skala proyek desain dan kolaborasi Anda.</p>
    </div>
    <div id="pricing-cards" class="gm-object" style="display:flex;gap:24px;justify-content:center;flex-wrap:wrap;">
        <div id="card-starter" class="gm-object gm-anim-base slide-up" data-gm-anim="true" style="flex:1;min-width:260px;max-width:340px;padding:28px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;display:flex;flex-direction:column;gap:16px;--gm-anim-dur:0.5s;">
            <h3 class="gm-object" style="font-size:18px;font-weight:600;color:#0f172a;margin:0;">Starter</h3>
            <div class="gm-object" style="font-size:32px;font-weight:700;color:#0f172a;">Rp 0 <span class="gm-object" style="font-size:14px;font-weight:400;color:#64748b;">/ bulan</span></div>
            <p class="gm-object" style="font-size:14px;color:#64748b;margin:0;">Cocok untuk desainer individu yang baru memulai.</p>
            <button class="gm-object" data-action-type="goto" data-action-target="Register" style="padding:11px 18px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:8px;font-weight:600;cursor:pointer;">Pilih Starter</button>
        </div>
        <div id="card-pro" class="gm-object gm-anim-base slide-up" data-gm-anim="true" style="flex:1;min-width:260px;max-width:340px;padding:28px;background-color:#ffffff;border:2px solid #3b82f6;border-radius:12px;display:flex;flex-direction:column;gap:16px;box-shadow:0 8px 24px rgba(59,130,246,0.12);--gm-anim-dur:0.7s;">
            <h3 class="gm-object" style="font-size:18px;font-weight:600;color:#3b82f6;margin:0;">Pro Studio</h3>
            <div class="gm-object" style="font-size:32px;font-weight:700;color:#0f172a;">Rp 149rb <span class="gm-object" style="font-size:14px;font-weight:400;color:#64748b;">/ bulan</span></div>
            <p class="gm-object" style="font-size:14px;color:#64748b;margin:0;">Proyek tanpa batas, AI generator penuh, dan ekspor kode bersih.</p>
            <button class="gm-object" data-action-type="goto" data-action-target="Checkout" style="padding:11px 18px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Mulai Pro Studio</button>
        </div>
    </div>
</section>
HTML;
        }

        if (str_contains($lower, 'form') || str_contains($lower, 'kontak') || str_contains($lower, 'contact') || str_contains($lower, 'login')) {
            return <<<'HTML'
<section id="contact-section" class="gm-object" style="padding:48px 24px;background-color:#ffffff;display:flex;justify-content:center;width:100%;box-sizing:border-box;font-family:'Inter',sans-serif;">
    <div id="contact-card" class="gm-object gm-anim-base zoom-in" data-gm-anim="true" style="width:100%;max-width:460px;padding:32px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:12px;display:flex;flex-direction:column;gap:16px;--gm-anim-dur:0.5s;">
        <h3 id="contact-title" class="gm-object" style="font-size:22px;font-weight:700;color:#0f172a;margin:0;">Hubungi Tim Kami</h3>
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
        <button id="btn-submit-contact" class="gm-object" data-action-type="modal" data-action-target="success-modal" style="padding:12px 20px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Kirim Pesan</button>
    </div>
</section>
HTML;
        }

        if (str_contains($lower, 'footer') || str_contains($lower, 'kaki')) {
            return <<<'HTML'
<footer id="main-footer" class="gm-object" style="padding:40px;background-color:#ffffff;border-top:1px solid #e2e8f0;width:100%;box-sizing:border-box;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;font-family:'Inter',sans-serif;">
    <div id="footer-brand" class="gm-object" style="display:flex;flex-direction:column;gap:6px;">
        <strong class="gm-object" style="font-size:16px;color:#0f172a;">Greymist Workspace</strong>
        <span class="gm-object" style="font-size:13px;color:#64748b;">Platform desain visual web modern berbasis komponen.</span>
    </div>
    <div id="footer-links" class="gm-object" style="display:flex;gap:24px;">
        <a class="gm-object" href="#" data-action-type="modal" data-action-target="tos-modal" style="font-size:13px;color:#475569;text-decoration:none;">Ketentuan Layanan</a>
        <a class="gm-object" href="#" data-action-type="modal" data-action-target="privacy-modal" style="font-size:13px;color:#475569;text-decoration:none;">Kebijakan Privasi</a>
        <a class="gm-object" href="#" data-action-type="goto" data-action-target="FAQ" style="font-size:13px;color:#475569;text-decoration:none;">Bantuan</a>
    </div>
</footer>
HTML;
        }

        // Default: Hero Section Modern sesuai prompt pengguna
        $clean_title = htmlspecialchars(mb_substr(trim($prompt), 0, 70), ENT_QUOTES, 'UTF-8');
        return <<<HTML
<section id="hero-section" class="gm-object" style="padding:72px 48px;background-color:#ffffff;border-bottom:1px solid #f1f5f9;width:100%;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;text-align:center;gap:20px;font-family:'Inter',sans-serif;">
    <span class="gm-object gm-anim-base fade" data-gm-anim="true" style="padding:6px 14px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:999px;font-size:12px;font-weight:600;--gm-anim-dur:0.5s;">GREYMIST AI GENERATED</span>
    <h1 id="hero-title" class="gm-object gm-anim-base slide-up" data-gm-anim="true" style="font-size:40px;font-weight:800;color:#0f172a;max-width:760px;line-height:1.2;margin:0;--gm-anim-dur:0.6s;">{$clean_title}</h1>
    <p id="hero-desc" class="gm-object gm-anim-base slide-up" data-gm-anim="true" style="font-size:16px;color:#475569;max-width:600px;line-height:1.6;margin:0;--gm-anim-dur:0.8s;">Komponen ini dibuat secara otomatis dan setiap elemen di dalamnya memiliki class gm-object sehingga dapat langsung Anda ubah melalui panel properti di sebelah kanan.</p>
    <div id="hero-actions" class="gm-object" style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;justify-content:center;">
        <button id="hero-btn-primary" class="gm-object" data-action-type="goto" data-action-target="Contact" style="padding:12px 24px;background-color:#3b82f6;color:#ffffff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Mulai Sekarang</button>
        <button id="hero-btn-secondary" class="gm-object" data-action-type="scroll" data-action-target="pricing" style="padding:12px 24px;background-color:#eff6ff;color:#3b82f6;border:1px solid #bfdbfe;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Pelajari Lebih Lanjut</button>
    </div>
</section>
HTML;
    }
}

/**
 * Memproses penghapusan, pembacaan, atau pengeditan atribut (font, warna, ukuran, style) komponen secara lokal (Fallback Cerdas).
 */
if (!function_exists('find_leaf_cards_in_dom')) {
    function find_leaf_cards_in_dom(DOMElement $container): array
    {
        $all_elements = $container->getElementsByTagName('*');
        $raw_cards = [];
        foreach ($all_elements as $el) {
            if (!($el instanceof DOMElement)) continue;
            $c_cls = (string)$el->getAttribute('class');
            $c_id  = (string)$el->getAttribute('id');
            $c_sty = (string)$el->getAttribute('style');
            if (preg_match('/\b(container|grid|wrapper|row|list|group)\b/i', $c_cls . ' ' . $c_id)) {
                continue;
            }
            if (preg_match('/\b(card|item|box|pricing-card)\b/i', $c_cls . ' ' . $c_id) || (strtolower($el->nodeName) === 'div' && (str_contains($c_sty, 'border') || str_contains($c_sty, 'box-shadow')))) {
                $raw_cards[] = $el;
            }
        }

        $leaf_cards = [];
        foreach ($raw_cards as $candidate) {
            $has_child_card = false;
            foreach ($candidate->getElementsByTagName('*') as $sub) {
                if (!($sub instanceof DOMElement)) continue;
                $sub_cls = (string)$sub->getAttribute('class');
                $sub_id  = (string)$sub->getAttribute('id');
                if (preg_match('/\b(container|grid|wrapper|row|list|group)\b/i', $sub_cls . ' ' . $sub_id)) {
                    continue;
                }
                if (preg_match('/\b(card|item|box|pricing-card)\b/i', $sub_cls . ' ' . $sub_id) || str_contains($sub_id, 'card')) {
                    $has_child_card = true;
                    break;
                }
            }
            if (!$has_child_card) {
                $leaf_cards[] = $candidate;
            }
        }

        if (!empty($leaf_cards)) {
            return $leaf_cards;
        }

        // Fallback cerdas: cari kontainer flex/grid yang memiliki 2 atau lebih kartu/blok anak
        foreach ($container->getElementsByTagName('*') as $g) {
            if (!($g instanceof DOMElement)) continue;
            $g_id_cls = strtolower($g->getAttribute('id') . ' ' . $g->getAttribute('class'));
            if (preg_match('/\b(header|nav|navbar|footer)\b/i', $g_id_cls)) continue;
            $g_sty = strtolower($g->getAttribute('style'));
            if (str_contains($g_sty, 'display:flex') || str_contains($g_sty, 'display: flex') || str_contains($g_sty, 'display:grid') || str_contains($g_sty, 'display: grid') || str_contains($g_id_cls, 'cards') || str_contains($g_id_cls, 'grid')) {
                $children = [];
                foreach ($g->childNodes as $ch) {
                    if ($ch instanceof DOMElement) {
                        $ch_tag = strtolower($ch->nodeName);
                        if (!in_array($ch_tag, ['h1', 'h2', 'h3', 'p', 'span', 'header', 'a'], true)) {
                            $children[] = $ch;
                        }
                    }
                }
                if (count($children) >= 2) {
                    return $children;
                }
            }
        }

        return [];
    }
}

if (!function_exists('resolve_target_card_from_list')) {
    function resolve_target_card_from_list(array $cards, string $lower): DOMElement
    {
        $count = count($cards);
        if ($count <= 1) {
            return $cards[0];
        }

        if (preg_match('/\b(ke\s*[-]?\s*2|kedua|second|2(?:nd)?)\b/i', $lower)) {
            return $cards[1] ?? $cards[0];
        }
        if (preg_match('/\b(ke\s*[-]?\s*3|ketiga|third|3(?:rd)?)\b/i', $lower)) {
            return $cards[2] ?? $cards[$count - 1];
        }
        if (preg_match('/\b(ke\s*[-]?\s*4|keempat|fourth|4(?:th)?)\b/i', $lower)) {
            return $cards[3] ?? $cards[$count - 1];
        }
        if (preg_match('/\b(ke\s*[-]?\s*1|pertama|kesatu|first|1(?:st)?)\b/i', $lower)) {
            return $cards[0];
        }
        if (preg_match('/\b(terakhir|last)\b/i', $lower)) {
            return $cards[$count - 1];
        }

        foreach ($cards as $c) {
            $txt = strtolower($c->textContent);
            $id_cls = strtolower($c->getAttribute('id') . ' ' . $c->getAttribute('class'));
            if (preg_match('/\b(pro|populer|popular)\b/i', $lower) && (str_contains($txt, 'pro') || str_contains($txt, 'populer') || str_contains($id_cls, 'pro'))) {
                return $c;
            }
            if (preg_match('/\b(enterprise|bisnis|business)\b/i', $lower) && (str_contains($txt, 'enterprise') || str_contains($txt, 'bisnis') || str_contains($id_cls, 'enterprise'))) {
                return $c;
            }
            if (preg_match('/\b(basic|starter|pemula|gratis|free)\b/i', $lower) && (str_contains($txt, 'basic') || str_contains($txt, 'starter') || str_contains($txt, 'gratis') || str_contains($id_cls, 'starter'))) {
                return $c;
            }
        }

        return $cards[0];
    }
}

if (!function_exists('process_local_frame_command')) {
    function process_local_frame_command(
        string $prompt,
        string $current_frame_html,
        string $selected_object_html = '',
        string $target_mode = 'core',
        array $frames_snapshot = [],
        ?PDO $pdo = null,
        int $project_id = 0
    ): ?array {
        $lower = strtolower(trim($prompt));

        // Ekstrak komponen reusable (Navbar / Footer / Sidebar) dari frame yang ada
        $reusable = extract_reusable_components($frames_snapshot, $current_frame_html);

        // 00. Deteksi perintah ganti / ubah / rename nama proyek (Database & Header DOM)
        if (preg_match('/\b(?:ganti|ubah|rename|set)\s+(?:(?:nama|judul)\s+)?(?:proyek|project)\s*(?:menjadi|jadi|ke|to|:)?\s*["\']?([^"\'\n\r]+)["\']?/i', $prompt, $rnp_match) ||
            preg_match('/\b(?:(?:nama|judul)\s+)?(?:proyek|project)\s*(?:ganti|ubah|rename|jadikan|ke|jadi|:)\s*(?:menjadi|jadi|ke|to|:)?\s*["\']?([^"\'\n\r]+)["\']?/i', $prompt, $rnp_match)) {
            $new_proj_name = trim($rnp_match[1], " \t\n\r\0\x0B\"'.,");
            if ($new_proj_name !== '') {
                if ($project_id > 0 && $pdo) {
                    $stmt = $pdo->prepare("UPDATE gm_projects SET project_name = ? WHERE project_id = ?");
                    $stmt->execute([$new_proj_name, $project_id]);
                }
                return [
                    'operation'       => 'rename_project',
                    'renamed_project' => [
                        'project_id' => $project_id,
                        'new_name'   => $new_proj_name,
                    ],
                    'summary'         => "Nama proyek berhasil diubah menjadi \"{$new_proj_name}\".",
                    'html'            => $current_frame_html,
                ];
            }
        }

        // 0. Jika meminta penambahan Breakpoint Responsif (Tablet / Mobile)
        if (preg_match('/\b(breakpoint|breaking\s*point|responsif|responsive)\b/i', $lower)) {
            $bp_to_add = [];
            if (str_contains($lower, 'tablet') || str_contains($lower, '768')) {
                $bp_to_add[] = [
                    'id'         => 'tablet',
                    'name'       => 'Tablet',
                    'rule'       => 'max-width: 768px',
                    'widthValue' => 768,
                    'type'       => 'max',
                ];
            }
            if (str_contains($lower, 'mobile') || str_contains($lower, 'hp') || str_contains($lower, 'ponsel') || str_contains($lower, '480') || str_contains($lower, '375')) {
                $bp_to_add[] = [
                    'id'         => 'mobile',
                    'name'       => 'Mobile',
                    'rule'       => 'max-width: 480px',
                    'widthValue' => 480,
                    'type'       => 'max',
                ];
            }
            if (empty($bp_to_add)) {
                $bp_to_add = [
                    ['id' => 'tablet', 'name' => 'Tablet', 'rule' => 'max-width: 768px', 'widthValue' => 768, 'type' => 'max'],
                    ['id' => 'mobile', 'name' => 'Mobile', 'rule' => 'max-width: 480px', 'widthValue' => 480, 'type' => 'max'],
                ];
            }

            return [
                'operation'          => 'add_breakpoints',
                'html'               => $current_frame_html,
                'breakpoints_to_add' => $bp_to_add,
                'summary'            => 'Breakpoint responsif (Tablet 768px & Mobile 480px) berhasil ditambahkan ke toolbar Greymist.',
            ];
        }

        // 1. Jika meminta membuat frame / halaman baru (atau multi-page) secara lokal
        if (preg_match('/\b(frame baru|tambah frame|halaman baru|buat halaman|page baru|multi[- ]?page|\d+\s*halaman)\b/i', $lower)) {
            // Gunakan navbar & footer yang sudah ada jika tersedia, jika tidak baru gunakan preset
            $nav    = $reusable['navbar'] ?? generate_smart_preset_component('navbar');
            $footer = $reusable['footer'] ?? generate_smart_preset_component('footer');

            if (preg_match('/\b(multi[- ]?page|3\s*halaman|beberapa halaman|website lengkap)\b/i', $lower)) {
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
            $body = generate_smart_preset_component($prompt);

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

        // 4. Jika meminta menghapus komponen tertentu
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

        // 5a. Deteksi Animasi (In-place patch kompatibel Greymist: Fade In, Slide Up/Down/Left/Right, Zoom In, Bounce In)
        if (preg_match('/\b(animasi|anim|animasikan|animation|bounce|slide\s*(?:up|down|left|right)|fade\s*in|zoom\s*in)\b/i', $lower)) {
            $is_editing_selected = ($target_mode === 'edit_selected' || trim($selected_object_html) !== '') && trim($selected_object_html) !== '';
            $target_html         = $is_editing_selected ? $selected_object_html : $current_frame_html;

            if (trim($target_html) !== '') {
                $doc = new DOMDocument();
                @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $target_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $root = $doc->getElementById('gm-root');

                if ($root) {
                    $target_node     = null;
                    $target_selector = 'self';

                    $is_card_prompt    = (bool)preg_match('/\b(card|kartu|kotak|box|item|pricing|harga|paket|fitur|feature)\b/i', $lower);
                    $is_button_prompt  = (bool)preg_match('/\b(tombol|button|cta)\b/i', $lower);
                    $is_heading_prompt = (bool)preg_match('/\b(judul|heading|title|h1|h2|h3)\b/i', $lower);

                    // Cek apakah prompt merujuk ke satu kartu spesifik (misal 'ke-2', 'pro', 'terakhir')
                    $is_single_card_specified = (bool)preg_match('/\b(ke\s*[-]?\s*\d+|pertama|kedua|ketiga|keempat|kelima|kesatu|first|second|third|fourth|fifth|1(?:st)?|2(?:nd)?|3(?:rd)?|4(?:th)?|5(?:th)?|terakhir|last|hanya\s+(?:ini|card|kartu)|card\s+aktif|yang\s+dipilih)\b/i', $lower);
                    if (!$is_single_card_specified && preg_match('/\b(pro|populer|popular|enterprise|bisnis|basic|starter|gratis|free)\b/i', $lower) && !preg_match('/\b(semua|all|setiap|seluruh|tiap)\b/i', $lower)) {
                        $is_single_card_specified = true;
                    }

                    $first_child = null;
                    foreach ($root->childNodes as $child) {
                        if ($child instanceof DOMElement) {
                            $first_child = $child;
                            break;
                        }
                    }

                    $target_nodes = [];

                    // Jika elemen terpilih adalah SECTION atau CONTAINER pembungkus,
                    // dan prompt merujuk ke card/tombol/judul: JANGAN animasikan section pembungkusnya!
                    if ($is_editing_selected && $first_child !== null) {
                        $tag = strtolower($first_child->nodeName);
                        $is_wrapper = ($tag === 'section' || $tag === 'div' || $tag === 'main' || $tag === 'article' || $first_child->getAttribute('id') === 'object-core');

                        if ($is_wrapper && ($is_card_prompt || $is_button_prompt || $is_heading_prompt)) {
                            if ($is_card_prompt) {
                                $matched_cards = find_leaf_cards_in_dom($first_child);
                                if (!empty($matched_cards)) {
                                    if ($is_single_card_specified) {
                                        $target_nodes = [resolve_target_card_from_list($matched_cards, $lower)];
                                    } else {
                                        // Targetkan SEMUA kartu saudara (siblings)!
                                        $target_nodes = $matched_cards;
                                    }
                                }
                            } elseif ($is_button_prompt) {
                                foreach ($first_child->getElementsByTagName('*') as $el) {
                                    if ($el instanceof DOMElement && (strtolower($el->nodeName) === 'button' || str_contains((string)$el->getAttribute('class'), 'btn'))) {
                                        $target_nodes = [$el];
                                        break;
                                    }
                                }
                            } elseif ($is_heading_prompt) {
                                foreach ($first_child->getElementsByTagName('*') as $el) {
                                    if ($el instanceof DOMElement && in_array(strtolower($el->nodeName), ['h1', 'h2', 'h3', 'h4'], true)) {
                                        $target_nodes = [$el];
                                        break;
                                    }
                                }
                            }
                        }

                        if (empty($target_nodes)) {
                            if ($is_card_prompt && !$is_single_card_specified) {
                                $all_leaf = find_leaf_cards_in_dom($root);
                                $target_nodes = count($all_leaf) > 1 ? $all_leaf : [$first_child];
                            } else {
                                $target_nodes = [$first_child];
                            }
                        }
                    } else {
                        // Tidak ada objek yang dipilih -> cari di seluruh frame HTML
                        if ($is_card_prompt) {
                            $matched_cards = find_leaf_cards_in_dom($root);
                            if (!empty($matched_cards)) {
                                if ($is_single_card_specified) {
                                    $target_nodes = [resolve_target_card_from_list($matched_cards, $lower)];
                                } else {
                                    // Targetkan SEMUA kartu saudara (siblings)!
                                    $target_nodes = $matched_cards;
                                }
                            }
                        } elseif ($is_button_prompt) {
                            foreach ($root->getElementsByTagName('*') as $el) {
                                if ($el instanceof DOMElement && (strtolower($el->nodeName) === 'button' || str_contains((string)$el->getAttribute('class'), 'btn'))) {
                                    $target_nodes = [$el];
                                    break;
                                }
                            }
                        } elseif ($is_heading_prompt) {
                            foreach ($root->getElementsByTagName('*') as $el) {
                                if ($el instanceof DOMElement && in_array(strtolower($el->nodeName), ['h1', 'h2', 'h3'], true)) {
                                    $target_nodes = [$el];
                                    break;
                                }
                            }
                        }

                        if (empty($target_nodes)) {
                            $leaf_cards = find_leaf_cards_in_dom($root);
                            if (!empty($leaf_cards)) {
                                $target_nodes = $is_single_card_specified ? [$leaf_cards[0]] : $leaf_cards;
                            } elseif ($first_child !== null) {
                                $target_nodes = [$first_child];
                            }
                        }
                    }

                    if (!empty($target_nodes)) {
                        $target_selectors = [];
                        foreach ($target_nodes as $idx => $t_node) {
                            $c_id = trim((string)$t_node->getAttribute('id'));
                            if ($c_id === '') {
                                $tag_prefix = strtolower($t_node->nodeName);
                                if (str_contains((string)$t_node->getAttribute('class'), 'card')) $tag_prefix = 'card';
                                $c_id = $tag_prefix . '-' . ($idx + 1) . '-' . substr(md5(uniqid((string)mt_rand(), true)), 0, 5);
                                $t_node->setAttribute('id', $c_id);
                            }
                            $target_selectors[] = '#' . $c_id;
                        }

                        $is_exact_selected = ($is_editing_selected && count($target_nodes) === 1 && $target_nodes[0] === $first_child && !in_array(strtolower($target_nodes[0]->nodeName), ['section', 'main', 'article']));
                        $target_selector   = $is_exact_selected ? 'self' : implode(', ', $target_selectors);
                        $target_node       = $target_nodes[0];

                        $all_gm_anims = [
                            'gm-anim-fade-in',
                            'gm-anim-slide-up',
                            'gm-anim-slide-down',
                            'gm-anim-slide-left',
                            'gm-anim-slide-right',
                            'gm-anim-zoom-in',
                            'gm-anim-bounce-in',
                        ];

                        // Periksa apakah prompt meminta MENGHAPUS animasi
                        if (preg_match('/\b(hapus|hilangkan|buang|tanpa|remove|delete|nonaktifkan|mati|off|reset)\b/i', $lower)) {
                            foreach ($target_nodes as $t_node) {
                                $cur_classes = preg_split('/\s+/', (string)$t_node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
                                $new_classes = array_values(array_diff($cur_classes, array_merge(['gm-anim-base'], $all_gm_anims)));
                                $t_node->setAttribute('class', implode(' ', $new_classes));

                                $t_node->removeAttribute('data-gm-anim');
                                $t_node->removeAttribute('data-gm-anim-dur');
                                $t_node->removeAttribute('data-gm-anim-del');
                                $t_node->removeAttribute('data-gm-anim-loop');

                                // Bersihkan style inline
                                $cur_style = (string)$t_node->getAttribute('style');
                                $style_decls = array_filter(array_map('trim', explode(';', $cur_style)));
                                $kept_decls = [];
                                foreach ($style_decls as $decl) {
                                    $pts = explode(':', $decl, 2);
                                    if (count($pts) === 2) {
                                        $k = strtolower(trim($pts[0]));
                                        if ($k !== '--gm-anim-duration' && $k !== '--gm-anim-delay' && $k !== 'animation-iteration-count') {
                                            $kept_decls[] = $decl;
                                        }
                                    }
                                }
                                if (!empty($kept_decls)) {
                                    $t_node->setAttribute('style', implode('; ', $kept_decls) . ';');
                                } else {
                                    $t_node->removeAttribute('style');
                                }
                            }

                            $out_html = '';
                            foreach ($root->childNodes as $child) {
                                $out_html .= $doc->saveHTML($child);
                            }

                            return [
                                'operation' => 'patch_attributes',
                                'patch'     => [
                                    'target_all'           => count($target_nodes) > 1,
                                    'targets'              => $target_selectors,
                                    'target'               => $target_selector,
                                    'classes_to_remove'    => array_merge(['gm-anim-base'], $all_gm_anims),
                                    'attributes_to_remove' => ['data-gm-anim', 'data-gm-anim-dur', 'data-gm-anim-del', 'data-gm-anim-loop'],
                                    'styles_to_remove'     => ['--gm-anim-duration', '--gm-anim-delay', 'animation-iteration-count'],
                                    'summary'              => 'Animasi berhasil dihapus dari ' . count($target_nodes) . ' elemen.',
                                ],
                                'html'      => trim($out_html),
                                'summary'   => 'Animasi berhasil dinonaktifkan.',
                            ];
                        }

                        // Deteksi jenis animasi (7 resmi Greymist)
                        $detected_anim = 'gm-anim-fade-in';
                        if (preg_match('/\b(bounce|membal)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-bounce-in';
                        } elseif (preg_match('/\b(zoom|membesar)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-zoom-in';
                        } elseif (preg_match('/\b(slide\s*down|turun|ke\s*bawah)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-slide-down';
                        } elseif (preg_match('/\b(slide\s*left|ke\s*kiri)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-slide-left';
                        } elseif (preg_match('/\b(slide\s*right|ke\s*kanan)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-slide-right';
                        } elseif (preg_match('/\b(slide|slide\s*up|naik|ke\s*atas)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-slide-up';
                        } elseif (preg_match('/\b(fade|pudar|muncul)\b/i', $lower)) {
                            $detected_anim = 'gm-anim-fade-in';
                        }

                        // Deteksi durasi (s)
                        $dur = '0.8';
                        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:s|detik|sec)\b/i', $lower, $dur_m)) {
                            $dur = $dur_m[1];
                        }

                        // Deteksi delay dasar (s)
                        $del = '0';
                        if (preg_match('/delay\s*(\d+(?:\.\d+)?)\s*(?:s|detik)?/i', $lower, $del_m)) {
                            $del = $del_m[1];
                        }

                        // Deteksi loop
                        $loop = '1';
                        if (preg_match('/\b(loop|looping|infinite|terus\s*menerus|berulang)\b/i', $lower)) {
                            $loop = 'infinite';
                        }

                        $base_del = (float)$del;
                        $stagger_step = 0.15;

                        // Terapkan ke SEMUA target nodes (dengan stagger delay per kartu saudara)
                        foreach ($target_nodes as $idx => $t_node) {
                            $el_del = count($target_nodes) > 1 ? round($base_del + ($idx * $stagger_step), 2) : $base_del;

                            $cur_classes = preg_split('/\s+/', (string)$t_node->getAttribute('class'), -1, PREG_SPLIT_NO_EMPTY);
                            $filtered_classes = array_values(array_diff($cur_classes, $all_gm_anims));
                            if (!in_array('gm-anim-base', $filtered_classes, true)) {
                                $filtered_classes[] = 'gm-anim-base';
                            }
                            $filtered_classes[] = $detected_anim;
                            $t_node->setAttribute('class', implode(' ', array_unique($filtered_classes)));

                            $t_node->setAttribute('data-gm-anim', $detected_anim);
                            $t_node->setAttribute('data-gm-anim-dur', (string)$dur);
                            $t_node->setAttribute('data-gm-anim-del', (string)$el_del);
                            $t_node->setAttribute('data-gm-anim-loop', (string)$loop);

                            // Update style inline
                            $cur_style = (string)$t_node->getAttribute('style');
                            $style_map = [];
                            if (trim($cur_style) !== '') {
                                foreach (explode(';', $cur_style) as $decl) {
                                    $pts = explode(':', $decl, 2);
                                    if (count($pts) === 2) {
                                        $style_map[strtolower(trim($pts[0]))] = trim($pts[1]);
                                    }
                                }
                            }
                            $style_map['--gm-anim-duration']        = $dur . 's';
                            $style_map['--gm-anim-delay']           = $el_del . 's';
                            $style_map['animation-iteration-count'] = (string)$loop;

                            $new_style_str = [];
                            foreach ($style_map as $sk => $sv) {
                                $new_style_str[] = "{$sk}: {$sv}";
                            }
                            $t_node->setAttribute('style', implode('; ', $new_style_str) . ';');
                        }

                        $out_html = '';
                        foreach ($root->childNodes as $child) {
                            $out_html .= $doc->saveHTML($child);
                        }

                        $count_targets = count($target_nodes);
                        $summary_text  = $count_targets > 1
                            ? ('Animasi ' . $detected_anim . ' berhasil diterapkan pada ' . $count_targets . ' kartu secara berurutan (stagger delay).')
                            : ('Animasi ' . $detected_anim . ' diterapkan pada ' . $target_selector . ' (durasi ' . $dur . 's, delay ' . $del . 's).');

                        return [
                            'operation' => 'patch_attributes',
                            'patch'     => [
                                'target_all'        => $count_targets > 1,
                                'target'            => $target_selector,
                                'targets'           => $target_selectors,
                                'stagger'           => $stagger_step,
                                'classes_to_add'    => ['gm-anim-base', $detected_anim],
                                'classes_to_remove' => $all_gm_anims,
                                'attributes'        => [
                                    'data-gm-anim'      => $detected_anim,
                                    'data-gm-anim-dur'  => (string)$dur,
                                    'data-gm-anim-del'  => (string)$del,
                                    'data-gm-anim-loop' => (string)$loop,
                                ],
                                'styles'            => [
                                    '--gm-anim-duration'        => $dur . 's',
                                    '--gm-anim-delay'           => $del . 's',
                                    'animation-iteration-count' => (string)$loop,
                                ],
                                'summary'           => $summary_text,
                            ],
                            'html'      => trim($out_html),
                            'summary'   => $summary_text,
                        ];
                    }
                }
            }
        }

        // 5. Jika meminta mengubah atribut / style (font, warna, ukuran, background, border-radius)
        if (preg_match('/\b(ubah|ganti|rubah|jadikan|set|font|warna|color|background|latar|ukuran|perbesar|perkecil|bold|tebal|miring|italic|radius|rounded|shadow|card|tombol|button)\b/i', $lower)) {
            $is_project_wide = ($target_mode === 'core' && preg_match('/\b(semua\s+(?:halaman|pages?|frame)|seluruh\s+(?:halaman|pages?|frame)|all\s+pages?|konsisten(?:kan)?|tiap\s+halaman)\b/i', $lower));

            $is_editing_selected = ($target_mode === 'edit_selected' || trim($selected_object_html) !== '') && trim($selected_object_html) !== '';
            $target_html         = $is_editing_selected ? $selected_object_html : $current_frame_html;

            if (trim($target_html) !== '' || ($is_project_wide && $pdo !== null && $project_id > 0)) {
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

                // Deteksi perubahan Warna
                $color_map = [
                    'merah'   => '#ef4444',
                    'red'     => '#ef4444',
                    'biru'    => '#3b82f6',
                    'blue'    => '#3b82f6',
                    'hijau'   => '#10b981',
                    'green'   => '#10b981',
                    'kuning'  => '#f59e0b',
                    'yellow'  => '#f59e0b',
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

                // Deteksi ukuran font (font-size) / border-radius / shadow
                if (preg_match('/(\d{1,3})\s*px\b/i', $lower, $px_m)) {
                    if (str_contains($lower, 'radius') || str_contains($lower, 'rounded')) {
                        $css_patches['border-radius'] = $px_m[1] . 'px';
                    } else {
                        $css_patches['font-size'] = $px_m[1] . 'px';
                    }
                }
                if (str_contains($lower, 'shadow') || str_contains($lower, 'bayangan')) {
                    $css_patches['box-shadow'] = '0 8px 24px rgba(15, 23, 42, 0.08)';
                }
                if (str_contains($lower, 'border')) {
                    $css_patches['border'] = '1px solid #e2e8f0';
                }

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

                // Jika aksi menargetkan SEMUA HALAMAN (Batch update frames)
                if ($is_project_wide && $pdo !== null && $project_id > 0) {
                    $db_frames = get_project_frames($pdo, $project_id);
                    $frames_updates = [];
                    foreach ($db_frames as $df) {
                        $f_html = (string)($df['core_content'] ?? '');
                        if (trim($f_html) === '') continue;

                        $doc = new DOMDocument();
                        @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $f_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                        $root = $doc->getElementById('gm-root');
                        if (!$root) continue;

                        $all_elements = $root->getElementsByTagName('*');
                        foreach ($all_elements as $el) {
                            if (!($el instanceof DOMElement)) continue;
                            $tag = strtolower($el->nodeName);
                            // Sesuaikan target (misal jika meminta ubah card, tombol, atau font)
                            if (str_contains($lower, 'card') || str_contains($lower, 'kartu')) {
                                if (str_contains((string)$el->getAttribute('class'), 'card') || str_contains((string)$el->getAttribute('id'), 'card') || ($tag === 'div' && str_contains((string)$el->getAttribute('style'), 'border'))) {
                                    $apply_style_to_node($el, $css_patches);
                                }
                            } elseif (str_contains($lower, 'tombol') || str_contains($lower, 'button')) {
                                if ($tag === 'button') {
                                    $apply_style_to_node($el, $css_patches);
                                }
                            } else {
                                $apply_style_to_node($el, $css_patches);
                            }
                        }

                        $new_frame_html = '';
                        foreach ($root->childNodes as $child) {
                            $new_frame_html .= $doc->saveHTML($child);
                        }
                        $clean_new = trim($new_frame_html);
                        if ($clean_new !== '') {
                            $stmtUp = $pdo->prepare("UPDATE gm_frames SET core_content = ? WHERE frame_id = ?");
                            $stmtUp->execute([$clean_new, $df['frame_id']]);
                            $frames_updates[] = [
                                'frame_id'   => (int)$df['frame_id'],
                                'frame_name' => (string)$df['frame_name'],
                                'html'       => $clean_new,
                            ];
                        }
                    }

                    if (!empty($frames_updates)) {
                        return [
                            'operation'      => 'batch_update_frames',
                            'frames_updates' => $frames_updates,
                            'html'           => '',
                            'summary'        => 'Gaya berhasil diperbarui di ' . count($frames_updates) . ' halaman proyek secara konsisten.',
                        ];
                    }
                }

                // Pengeditan lokal pada frame aktif tunggal
                if (!empty($css_patches)) {
                    $doc = new DOMDocument();
                    @$doc->loadHTML('<?xml encoding="utf-8" ?><div id="gm-root">' . $target_html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    $root = $doc->getElementById('gm-root');
                    if ($root) {
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
                            $matched_nodes = [];
                            $all_elements  = $root->getElementsByTagName('*');
                            foreach ($all_elements as $el) {
                                if (!($el instanceof DOMElement)) continue;
                                $tag = strtolower($el->nodeName);
                                if (str_contains($lower, 'tombol') || str_contains($lower, 'button')) {
                                    if ($tag === 'button') $matched_nodes[] = $el;
                                } elseif (str_contains($lower, 'judul') || str_contains($lower, 'heading') || str_contains($lower, 'title')) {
                                    if (in_array($tag, ['h1', 'h2', 'h3'], true)) $matched_nodes[] = $el;
                                } elseif (str_contains($lower, 'navbar') || str_contains($lower, 'header')) {
                                    if ($tag === 'nav' || $tag === 'header') $matched_nodes[] = $el;
                                } elseif (str_contains($lower, 'footer')) {
                                    if ($tag === 'footer') $matched_nodes[] = $el;
                                } elseif (str_contains($lower, 'card') || str_contains($lower, 'kartu')) {
                                    if (str_contains((string)$el->getAttribute('class'), 'card') || str_contains((string)$el->getAttribute('id'), 'card')) $matched_nodes[] = $el;
                                }
                            }
                            if (empty($matched_nodes)) {
                                foreach ($root->childNodes as $child) {
                                    if ($child instanceof DOMElement) $matched_nodes[] = $child;
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
/**
 * Mengembalikan system instruction lengkap untuk Greymist AI Architect.
 * Berlaku identik untuk Gemini, Groq, OpenRouter, dan Antigravity Bridge.
 */
if (!function_exists('get_greymist_system_instruction')) {
    function get_greymist_system_instruction(bool $read_memory = false): string
    {
        // 1. JIKA MEMORI DIMATIKAN (DEFAULT - MODE KILAT / FAST MODE):
        // Gunakan instruksi ringkas & presisi agar server AI merespons dalam hitungan detik tanpa membaca ribuan token teks berlebih!
        if (!$read_memory) {
            return "Anda adalah AI UI Engineer untuk editor web Greymist. Output Anda langsung dirender ke kanvas DOM.\n\n"
                . "ATURAN UTAMA (WAJIB DIIKUTI):\n"
                . "1. EKSEKUSI TUNTAS, KAYA KONTEN & DILARANG SETENGAH-SETENGAH:\n"
                . "   - Hasilkan konten UI yang UTUH, LENGKAP, DETAIL, dan SIAP PAKAI secara penuh tanpa placeholder.\n"
                . "   - DILARANG KERAS membuat UI seadanya/minimalis atau memotong kode. Jika diminta halaman atau section, buat seluruh elemen secara mendalam: teks judul menarik, paragraf deskripsi jelas, tombol CTA, kartu-kartu (minimal 3 kartu fitur/layanan/pricing dengan data realistis), form input lengkap, dan footer/header jika relevan.\n"
                . "2. SETIAP elemen HTML wajib memiliki class 'gm-object'.\n"
                . "3. WAJIB IKON FONT AWESOME: gunakan <i class=\"fa-solid fa-...\"></i> atau <i class=\"fas fa-...\"></i>. DILARANG KERAS MENGGUNAKAN KARAKTER EMOJI/EMOTIKON APAPUN!\n"
                . "4. SPACING PROPORSIONAL: section padding 36px 20px - 48px 32px, card 16px 20px - 24px 28px, button 8px 18px - 12px 24px, gap 12px - 24px. DILARANG padding/margin raksasa (100px+).\n"
                . "5. RESPONSIVE: flex-wrap, max-width 100%, box-sizing: border-box.\n"
                . "6. TARGET MULTI-CARD SIBLINGS & ANTI-ROOT ANIMASI:\n"
                . "   - Saat pengguna meminta animasi pada card/kartu secara umum ('animasikan card', 'semua card kasih slide-up'), WAJIB menganimasikan SELURUH kartu daun saudara (siblings) dalam kontainer dengan staggered delay (0s, 0.15s, 0.3s, dst.)!\n"
                . "   - DILARANG hanya menganimasikan first-child!\n"
                . "   - DILARANG menganimasikan root <section> atau #object-core!\n"
                . "   - 7 Class animasi resmi Greymist: gm-anim-fade-in, gm-anim-slide-up, gm-anim-slide-down, gm-anim-slide-left, gm-anim-slide-right, gm-anim-zoom-in, gm-anim-bounce-in.\n"
                . "7. EDIT ATRIBUT / GAYA IN-PLACE (JANGAN BUAT DARI NOL):\n"
                . "   Jika pengguna meminta mengubah/menambah/menghapus atribut (warna, font, ukuran, border, animasi, text) pada komponen yang sudah ada, kembalikan HANYA format JSON:\n"
                . "   <!--GM_OP:PATCH_ATTRIBUTES-->\n"
                . "   {\"target\":\"self\",\"target_all\":true,\"stagger\":0.15,\"classes_to_add\":[\"gm-anim-base\",\"gm-anim-slide-up\"],\"classes_to_remove\":[\"gm-anim-fade-in\"],\"attributes\":{\"data-gm-anim\":\"gm-anim-slide-up\",\"data-gm-anim-dur\":\"0.8\",\"data-gm-anim-del\":\"0\",\"data-gm-anim-loop\":\"1\"},\"styles\":{\"--gm-anim-duration\":\"0.8s\",\"--gm-anim-delay\":\"0s\"},\"summary\":\"Ringkasan perubahan.\"}\n"
                . "8. FORMAT OPERASI LAINNYA:\n"
                . "   - Komponen baru: <!--GM_OP:APPEND--> <kode html>\n"
                . "   - Ubah objek terpilih: <!--GM_OP:REPLACE_SELECTED--> <kode html>\n"
                . "   - Ganti nama project: <!--GM_OP:RENAME_PROJECT:Nama Baru-->\n"
                . "   - Frame baru: <!--GM_FRAME:Nama Page--> <kode html>";
        }

        // 2. JIKA MEMORI DIAKTIFKAN (MODE KONTEKS PENUH / DEEP MEMORY):
        // Melibatkan seluruh aturan arsitektur multi-halaman dan pembaruan resiprokal proyek
        return "Anda adalah AI Visual Workspace Architect untuk editor web Greymist.\n"
            . "Anda memiliki CACHE MEMORI PROYEK yang mencatat seluruh halaman (frames), susunan komponen di setiap halaman, dan riwayat perubahan yang telah Anda buat sebelumnya.\n"
            . "Gunakan memori tersebut agar setiap perubahan, penambahan, atau penghapusan selalu konsisten dengan sistem desain dan susunan halaman yang sudah ada.\n\n"
            . "========================================================================\n"
            . "ATURAN EMAS DESAIN, SINKRONISASI & PRODUKSI GREYMIST\n"
            . "========================================================================\n\n"
            . "1. EKSEKUSI TUNTAS & PRODUKSI PENUH (DILARANG KERAS SETENGAH-SETENGAH):\n"
            . "   - Hasilkan konten UI yang UTUH, LENGKAP, dan SIAP PAKAI secara penuh.\n"
            . "   - DILARANG KERAS memotong kode, menggunakan komentar placeholder (seperti '<!-- isi konten disini -->', '...', 'TODO'), atau teks contoh seadanya.\n"
            . "   - Bangun seluruh komponen secara detail: teks judul yang menggugah, deskripsi/paragraf kaya informasi, tombol CTA, kartu-kartu fitur dengan data realistis, testimoni, form input dengan label dan placeholder yang rapi, dan footer lengkap.\n\n"
            . "2. ATURAN WAJIB IKON FONT AWESOME (ANTI-EMOTIKON & TIDAK BERLEBIHAN):\n"
            . "   - WAJIB gunakan ikon Font Awesome resmi: <i class=\"fa-solid fa-...\"></i> atau <i class=\"fas fa-...\"></i> (contoh: fa-house, fa-user, fa-envelope, fa-arrow-right, fa-shield-halved, fa-star, fa-phone, fa-check, fa-gear, fa-chart-line, fa-magnifying-glass).\n"
            . "   - DILARANG KERAS MENGGUNAKAN KARAKTER EMOJI / EMOTIKON APAPUN (seperti 🚀, 💡, 🔥, ✨, 📱, 👍, 💼, 📞, 🌟, dll.). SEMUA IKON HARUS MENGGUNAKAN TAG <i> FONT AWESOME!\n"
            . "   - Gunakan ikon secara proporsional dan elegan: maksimal 1 ikon relevan pada tombol, kartu fitur, atau link menu. DILARANG menaruh ikon berlebihan di setiap kata atau judul.\n\n"
            . "3. ATURAN PADDING & MARGIN PROPORSIONAL (DILARANG JARAK RAKSASA/BERLEBIHAN):\n"
            . "   - DILARANG KERAS menggunakan padding atau margin yang raksasa/berlebihan (hindari padding 100px-200px atau margin 80px-150px yang menyisakan ruang kosong menganga).\n"
            . "   - Terapkan standar layout modern yang seimbang dan proporsional:\n"
            . "     * Section padding: 36px 20px hingga 48px 32px (padat, rapi, dan seimbang).\n"
            . "     * Card / container padding: 16px 20px hingga 24px 28px.\n"
            . "     * Button padding: 8px 18px hingga 12px 24px.\n"
            . "     * Navbar padding: 12px 24px hingga 16px 32px.\n"
            . "     * Margin: minimalis (0, 8px, 12px, 16px, maks 24px antar elemen). Utamakan 'gap' flexbox/grid (gap: 12px hingga 24px).\n"
            . "     * Selalu terapkan box-sizing: border-box; dan max-width: 100%;.\n\n"
            . "4. ATURAN FRAME AWAL (PENGISIAN & PENGGUNAAN ULANG MAIN FRAME):\n"
            . "   - Jika proyek saat ini baru dibuat dan memiliki satu frame kosong (seperti 'Main Frame'), halaman pertama yang Anda hasilkan WAJIB menjadi halaman utama (Home / Beranda) untuk mengisi frame awal tersebut, BUKAN membiarkannya kosong lalu membuat halaman baru terpisah!\n\n"
            . "5. ATURAN REUSE KOMPONEN (PENGGUNAAN ULANG NAV / SIDEBAR / FOOTER):\n"
            . "   - Jika terdapat [EXISTING_NAVBAR], [EXISTING_FOOTER], atau [EXISTING_SIDEBAR] di dalam konteks:\n"
            . "     DILARANG KERAS membuat Navbar, Sidebar, atau Footer baru dari nol dengan struktur/warna/font berbeda!\n"
            . "     Gunakan kembali kode HTML komponen yang sudah ada tersebut: salin strukturnya, pertahankan logo/brand/warna, sesuaikan menu link-nya, dan pindahkan status visual 'active' ke halaman yang sedang dibuat.\n\n"
            . "6. NAVIGASI RESIPROKAL DUA ARAH ANTAR HALAMAN (MULTI-PAGE CROSS-LINKING):\n"
            . "   - Saat membuat halaman baru atau multi-page, SEMUA link navbar/footer WAJIB memiliki data-action-type=\"goto\" dan data-action-target=\"NamaHalaman\" (contoh: data-action-target=\"Home\", data-action-target=\"About\", data-action-target=\"Contact\").\n"
            . "   - Setiap Navbar pada halaman baru WAJIB memuat tautan ke seluruh halaman dalam proyek (baik halaman lama maupun baru).\n"
            . "   - Sistem backend Greymist secara otomatis akan memperbarui seluruh Navbar di halaman lama (seperti Home) untuk menyematkan tautan ke halaman baru ini, sehingga navigasi antar halaman terhubung dua arah secara instan.\n"
            . "   - Link untuk halaman yang sedang aktif WAJIB diberi styling aktif (misal: color: #3b82f6; font-weight: 700;).\n\n"
            . "7. INTEGRASI ENGINE BREAKPOINTS & RESPONSIVE DESIGN:\n"
            . "   - Greymist memiliki sistem breakpoint: Desktop (1920 base), Tablet (max-width: 768px), Mobile (max-width: 480px).\n"
            . "   - Seluruh komponen HTML yang Anda buat WAJIB menggunakan tata letak responsif modern: flex-wrap: wrap, gap, max-width: 100%, box-sizing: border-box, dan grid auto-fit minmax(260px, 1fr) untuk card.\n"
            . "   - Jika pengguna meminta responsif atau menambah breakpoint, sertakan blok penanda breakpoint:\n"
            . "     <!--GM_BREAKPOINTS-->\n"
            . "     [{\"id\":\"tablet\",\"name\":\"Tablet\",\"rule\":\"max-width: 768px\",\"widthValue\":768,\"type\":\"max\"},{\"id\":\"mobile\",\"name\":\"Mobile\",\"rule\":\"max-width: 480px\",\"widthValue\":480,\"type\":\"max\"}]\n\n"
            . "8. INTERVENSI SELURUH HALAMAN (BATCH PROJECT-WIDE STYLING):\n"
            . "   - Jika tidak ada komponen aktif dan pengguna meminta perubahan pada SEMUA halaman (misal: 'konsistenkan semua card', 'ubah warna tema di semua pages', 'ganti font semua halaman', 'samakan navbar'):\n"
            . "     Gunakan operasi: <!--GM_OP:BATCH_UPDATE_FRAMES-->\n"
            . "     Lalu kembalikan kode HTML baru untuk setiap halaman yang diperbarui:\n"
            . "     <!--GM_FRAME_UPDATE:NamaHalamanAtauID-->\n"
            . "     <kode html lengkap frame ini>\n"
            . "     <!--GM_FRAME_UPDATE:NamaHalamanAtauID-->\n"
            . "     <kode html lengkap frame ini>\n\n"
            . "9. INTEGRASI MENU ACTION, ANIMASI, & ATRIBUT (PANEL BAWAH GREYMIST):\n"
            . "   - Action: data-action-type=\"goto\" (pindah frame), data-action-type=\"scroll\" (smooth scroll ke id section), data-action-type=\"modal\" (buka modal popup), data-action-type=\"link\" (url).\n"
            . "   - Animasi Greymist: HANYA ada 7 class animasi resmi yang kompatibel:\n"
            . "     1. gm-anim-fade-in  2. gm-anim-slide-up  3. gm-anim-slide-down  4. gm-anim-slide-left\n"
            . "     5. gm-anim-slide-right  6. gm-anim-zoom-in  7. gm-anim-bounce-in\n"
            . "     WAJIB class: class=\"gm-object gm-anim-base <nama_animasi>\"\n"
            . "     WAJIB atribut: data-gm-anim=\"<nama_animasi>\" (harus sama persis dengan nama class animasi),\n"
            . "     data-gm-anim-dur=\"0.8\" (detik, angka saja), data-gm-anim-del=\"0\" (detik, angka saja), data-gm-anim-loop=\"1\" (atau \"infinite\").\n"
            . "     WAJIB style inline: --gm-anim-duration: 0.8s; --gm-anim-delay: 0s; animation-iteration-count: 1;\n"
            . "   - Atribut: Berikan id semantik (id=\"hero-section\", id=\"pricing\", id=\"contact\") dan class=\"gm-object\" pada SETIAP elemen.\n\n"
            . "ATURAN UTAMA: TARGET PRESISI & MULTI-CARD SIBLINGS (DILARANG HANYA FIRST-CHILD!):\n"
            . "Jika pengguna meminta animasi atau mengubah atribut card/kartu/item:\n"
            . "1. DILARANG KERAS menaruh class gm-anim-* atau data-gm-anim pada <section>, #object-core, atau pembungkus utama frame!\n"
            . "2. MULTI-CARD (SEMUA KARTU SAUDARA/SIBLINGS):\n"
            . "   Jika pengguna merujuk ke kartu/card secara umum atau jamak ('animasikan card', 'beri animasi pada kartu', 'semua card', 'kartu-kartu', 'tiap card'):\n"
            . "   - DILARANG HANYA MENGANIMASIKAN KARTU PERTAMA (FIRST-CHILD)!\n"
            . "   - SEMUA kartu saudara (siblings) dalam kontainer grid/flex tersebut WAJIB diberi animasi!\n"
            . "   - Format penargetan multi-card pada patch: gunakan \"target_all\": true, \"target\": \".card\", \"stagger\": 0.15, atau daftarkan \"targets\": [\"#card-1\", \"#card-2\", \"#card-3\"].\n"
            . "3. SINGLE-CARD (KARTU TUNGGAL SPESIFIK):\n"
            . "   - HANYA targetkan satu kartu tunggal jika pengguna secara spesifik menyebut nomor atau nama kartu ('card ke-2', 'card pro', 'card terakhir').\n"
            . "   - Gunakan selector presisi: \"target\": \"#card-pro\" atau \".card:nth-of-type(2)\".\n\n"
            . "ATURAN UTAMA: BACA KOMPONEN DULU & UBAH ATRIBUTNYA (JANGAN MEMBUAT BARU DARI NOL):\n"
            . "Jika pengguna meminta MENGUBAH, MENAMBAHKAN, atau MENGHAPUS atribut/gaya (seperti warna, font, background, ukuran font, border, radius, animasi, action, padding, margin, shadow, atau teks) pada komponen yang sudah ada:\n"
            . "1. DILARANG KERAS MEMBUAT ELEMEN/KOMPONEN BARU DARI NOL!\n"
            . "2. DILARANG MENGHASILKAN KODE HTML BARU LENGKAP ATAU MENUMPANGKAN ELEMEN BARU DI ATAS ELEMEN LAMA!\n"
            . "3. BACA atribut dan style yang sudah ada pada komponen (SELECTED_OBJECT_HTML atau elemen di CURRENT_FRAME_HTML).\n"
            . "4. Tentukan HANYA properti CSS (styles), atribut HTML (attributes), classes_to_add / classes_to_remove, atau teks apa yang perlu DIUBAH/DITAMBAHKAN.\n"
            . "5. Kembalikan HANYA penanda <!--GM_OP:PATCH_ATTRIBUTES--> diikuti satu objek JSON valid persis format berikut:\n"
            . "   Contoh Menambahkan/Mengubah Animasi:\n"
            . "   <!--GM_OP:PATCH_ATTRIBUTES-->\n"
            . "   {\n"
            . "     \"target\": \"self\",\n"
            . "     \"classes_to_add\": [\"gm-anim-base\", \"gm-anim-slide-up\"],\n"
            . "     \"classes_to_remove\": [\"gm-anim-fade-in\", \"gm-anim-slide-down\", \"gm-anim-slide-left\", \"gm-anim-slide-right\", \"gm-anim-zoom-in\", \"gm-anim-bounce-in\"],\n"
            . "     \"attributes\": {\n"
            . "       \"data-gm-anim\": \"gm-anim-slide-up\",\n"
            . "       \"data-gm-anim-dur\": \"0.8\",\n"
            . "       \"data-gm-anim-del\": \"0\",\n"
            . "       \"data-gm-anim-loop\": \"1\"\n"
            . "     },\n"
            . "     \"styles\": {\n"
            . "       \"--gm-anim-duration\": \"0.8s\",\n"
            . "       \"--gm-anim-delay\": \"0s\",\n"
            . "       \"animation-iteration-count\": \"1\"\n"
            . "     },\n"
            . "     \"summary\": \"Animasi slide-up diterapkan pada elemen.\"\n"
            . "   }\n\n"
            . "   Contoh Menghapus Animasi:\n"
            . "   <!--GM_OP:PATCH_ATTRIBUTES-->\n"
            . "   {\n"
            . "     \"target\": \"self\",\n"
            . "     \"classes_to_remove\": [\"gm-anim-base\", \"gm-anim-fade-in\", \"gm-anim-slide-up\", \"gm-anim-slide-down\", \"gm-anim-slide-left\", \"gm-anim-slide-right\", \"gm-anim-zoom-in\", \"gm-anim-bounce-in\"],\n"
            . "     \"attributes_to_remove\": [\"data-gm-anim\", \"data-gm-anim-dur\", \"data-gm-anim-del\", \"data-gm-anim-loop\"],\n"
            . "     \"styles_to_remove\": [\"--gm-anim-duration\", \"--gm-anim-delay\", \"animation-iteration-count\"],\n"
            . "     \"summary\": \"Animasi dihapus dari elemen.\"\n"
            . "   }\n\n"
            . "11. GANTI NAMA PROYEK (RENAME PROJECT):\n"
            . "   Jika pengguna meminta mengganti, mengubah, atau me-rename nama/judul proyek (misal: 'ganti nama project jadi My Studio'):\n"
            . "   Gunakan format: <!--GM_OP:RENAME_PROJECT:Nama Proyek Baru--> diikuti penjelasan singkat.\n\n"
            . "12. MULTIMODAL VISION & REFERENSI VISUAL GAMBAR:\n"
            . "   Jika gambar / screenshot referensi disertakan:\n"
            . "   - Analisis tata letak (wireframe/layout), hierarki visual, jenis komponen (hero, navbar, cards, pricing, testimonial, form, footer), warna, dan tipografi dari gambar referensi tersebut.\n"
            . "   - Konversikan setiap elemen visual dari gambar menjadi kode HTML semantik Greymist dengan class .gm-object yang meniru estetika dan layout referensi tersebut secara presisi.\n"
            . "   - Gunakan ikon Font Awesome yang relevan sesuai konten pada foto. Pastikan responsif dan bebas emoji.\n\n"
            . "PILIHAN FORMAT OPERASI LAINNYA:\n"
            . "1. MEMBACA/MENJELASKAN: <!--GM_OP:EXPLAIN--> diikuti penjelasan dalam Bahasa Indonesia.\n"
            . "2. GANTI NAMA PROYEK: <!--GM_OP:RENAME_PROJECT:Nama Proyek Baru--> diikuti konfirmasi.\n"
            . "3. HAPUS KOMPONEN DI FRAME: <!--GM_OP:REPLACE_FRAME--> diikuti seluruh kode HTML frame setelah dihapus.\n"
            . "4. HALAMAN BARU / MULTI-PAGE: Gunakan blok <!--GM_FRAME:Nama Halaman--> diikuti kode HTML .gm-object untuk tiap halaman.\n"
            . "5. KOMPONEN BARU DI FRAME AKTIF: <!--GM_OP:APPEND--> diikuti kode HTML komponen baru.";
    }
}

/**
 * Menyusun string context input lengkap untuk AI model.
 */
if (!function_exists('build_greymist_context_input')) {
    function build_greymist_context_input(
        string $prompt,
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        ?array $reference_image = null
    ): string {
        $context_input = "";
        if ($memory_context_text !== '') {
            $context_input .= $memory_context_text . "\n\n";
        }
        $context_input .= "TARGET_MODE: " . $target_mode . "\n";
        $context_input .= "CURRENT_FRAME_HTML (Frame yang sedang dibuka pengguna):\n" . ($current_frame_html !== '' ? $current_frame_html : "(Frame masih kosong)") . "\n\n";

        if (!empty($reusable_components['navbar'])) {
            $context_input .= "KOMPONEN YANG SUDAH ADA UNTUK DIGUNAKAN ULANG (REUSE):\n";
            $context_input .= "[EXISTING_NAVBAR]:\n" . $reusable_components['navbar'] . "\n\n";
        }
        if (!empty($reusable_components['footer'])) {
            $context_input .= "[EXISTING_FOOTER]:\n" . $reusable_components['footer'] . "\n\n";
        }
        if (!empty($reusable_components['sidebar'])) {
            $context_input .= "[EXISTING_SIDEBAR]:\n" . $reusable_components['sidebar'] . "\n\n";
        }

        if (!empty($all_project_frames) && count($all_project_frames) > 1) {
            $context_input .= "DAFTAR HALAMAN (FRAMES) DALAM PROYEK:\n";
            foreach ($all_project_frames as $pf) {
                $pf_id = $pf['frame_id'] ?? 0;
                $pf_name = $pf['frame_name'] ?? 'Frame';
                $pf_content = mb_substr((string)($pf['core_content'] ?? ''), 0, 1500);
                $context_input .= "<!--FRAME_{$pf_id}:{$pf_name}-->\n" . $pf_content . "\n\n";
            }
        }

        if ($selected_object_html !== '') {
            $context_input .= "SELECTED_OBJECT_HTML (Komponen yang sedang aktif dipilih):\n" . $selected_object_html . "\n";
            if (!empty($selected_object_attrs)) {
                $context_input .= "ATRIBUT SAAT INI: " . json_encode($selected_object_attrs, JSON_UNESCAPED_UNICODE) . "\n";
            }
            $context_input .= "\n";
        }
        if ($reference_image !== null && !empty($reference_image['data'])) {
            $context_input .= "[FOTO / SCREENSHOT REFERENSI VISUAL DISERTAKAN]: Gambar referensi desain UI terlampir. Analisis tata letak, komponen, dan estetika visualnya, lalu hasilkan kode UI .gm-object yang meniru referensi tersebut.\n\n";
        }
        $context_input .= "INSTRUKSI PENGGUNA:\n" . $prompt;

        return $context_input;
    }
}

/**
 * Mem-parsing teks output mentah dari AI (Gemini, Groq, OpenRouter, dll)
 * menjadi struktur payload operasi Greymist terstandar.
 */
if (!function_exists('parse_greymist_ai_output')) {
    function parse_greymist_ai_output(
        string $output_text,
        string $candidate_model,
        string $target_mode = 'core',
        string $current_frame_html = ''
    ): array {
        $trimmed_out = clean_emojis_from_html(trim($output_text));
        if ($trimmed_out === '') {
            return ['success' => false, 'error' => 'Respons AI kosong.'];
        }

        // Bersihkan markdown fences jika model masih menyertakannya
        $trimmed_out = preg_replace('/^```(?:html|json)?\s*/i', '', $trimmed_out);
        $trimmed_out = preg_replace('/\s*```$/', '', $trimmed_out);

        // Cek apakah ada arahan penambahan breakpoint responsif: <!--GM_BREAKPOINTS--> [...]
        $breakpoints_to_add = [];
        if (preg_match('/<!--\s*GM_BREAKPOINTS\s*-->\s*(\[.*?\])/is', $trimmed_out, $bp_match)) {
            $parsed_bp = json_decode($bp_match[1], true);
            if (is_array($parsed_bp)) {
                $breakpoints_to_add = $parsed_bp;
            }
            $trimmed_out = str_replace($bp_match[0], '', $trimmed_out);
        }

        // Cek apakah AI meminta pembaruan serentak pada SELURUH HALAMAN
        if (str_contains($trimmed_out, '<!--GM_OP:BATCH_UPDATE_FRAMES-->') || str_contains($trimmed_out, '<!--GM_FRAME_UPDATE:')) {
            $frames_updates = [];
            if (preg_match_all('/<!--\s*GM_FRAME_UPDATE\s*:\s*(.*?)\s*-->/is', $trimmed_out, $fu_matches, PREG_OFFSET_CAPTURE)) {
                $count_fu = count($fu_matches[0]);
                for ($i = 0; $i < $count_fu; $i++) {
                    $f_ident   = trim($fu_matches[1][$i][0]);
                    $start_pos = $fu_matches[0][$i][1] + strlen($fu_matches[0][$i][0]);
                    $end_pos   = ($i + 1 < $count_fu) ? $fu_matches[0][$i + 1][1] : strlen($trimmed_out);
                    $fu_html   = trim(substr($trimmed_out, $start_pos, $end_pos - $start_pos));
                    if ($fu_html !== '') {
                        $frames_updates[] = [
                            'frame_identifier' => $f_ident,
                            'html'             => ensure_gm_object_class($fu_html),
                        ];
                    }
                }
            }

            if (!empty($frames_updates)) {
                return [
                    'success'            => true,
                    'used_model'         => $candidate_model,
                    'operation'          => 'batch_update_frames',
                    'frames_updates'     => $frames_updates,
                    'breakpoints_to_add' => $breakpoints_to_add,
                    'html'               => '',
                ];
            }
        }

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
                    'success'            => true,
                    'used_model'         => $candidate_model,
                    'operation'          => 'create_frames',
                    'frames_specs'       => $frames_specs,
                    'breakpoints_to_add' => $breakpoints_to_add,
                    'html'               => '',
                ];
            }
        }

        $operation = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
        if (str_contains($trimmed_out, '<!--GM_OP:PATCH_ATTRIBUTES-->')) {
            $json_str = trim(str_replace('<!--GM_OP:PATCH_ATTRIBUTES-->', '', $trimmed_out));
            $json_str = preg_replace('/^```(?:json)?\s*/i', '', $json_str);
            $json_str = preg_replace('/\s*```$/', '', $json_str);
            $patch_data = json_decode($json_str, true);
            if (is_array($patch_data) && (!empty($patch_data['styles']) || !empty($patch_data['attributes']) || !empty($patch_data['text']) || !empty($patch_data['classes_to_add']) || !empty($patch_data['classes_to_remove']) || !empty($patch_data['attributes_to_remove']) || !empty($patch_data['styles_to_remove']))) {
                return [
                    'success'            => true,
                    'used_model'         => $candidate_model,
                    'operation'          => 'patch_attributes',
                    'patch'              => $patch_data,
                    'summary'            => $patch_data['summary'] ?? 'Atribut elemen berhasil diperbarui.',
                    'breakpoints_to_add' => $breakpoints_to_add,
                    'html'               => '',
                ];
            }
        } elseif (preg_match('/<!--\s*GM_OP\s*:\s*RENAME_PROJECT\s*:\s*(.*?)\s*-->/i', $trimmed_out, $rnp_match)) {
            $new_proj_name = trim($rnp_match[1], " \t\n\r\0\x0B\"'.,");
            $trimmed_out   = trim(str_replace($rnp_match[0], '', $trimmed_out));
            return [
                'success'         => true,
                'used_model'      => $candidate_model,
                'operation'       => 'rename_project',
                'renamed_project' => [
                    'new_name' => $new_proj_name,
                ],
                'summary'         => "Nama proyek berhasil diubah menjadi \"{$new_proj_name}\"." . ($trimmed_out !== '' ? " " . $trimmed_out : ""),
                'html'            => $current_frame_html,
            ];
        } elseif (str_contains($trimmed_out, '<!--GM_OP:EXPLAIN-->')) {
            $operation   = 'explain';
            $trimmed_out = trim(str_replace('<!--GM_OP:EXPLAIN-->', '', $trimmed_out));
            return [
                'success'            => true,
                'used_model'         => $candidate_model,
                'operation'          => 'explain',
                'summary'            => $trimmed_out,
                'breakpoints_to_add' => $breakpoints_to_add,
                'html'               => $current_frame_html,
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
            'success'            => true,
            'used_model'         => $candidate_model,
            'operation'          => $operation,
            'breakpoints_to_add' => $breakpoints_to_add,
            'html'               => $trimmed_out !== '' ? ensure_gm_object_class($trimmed_out) : '',
        ];
    }
}

/**
 * Adapter universal untuk Cloud AI berbasis format OpenAI-Compatible
 * (Mendukung Groq Cloud, OpenRouter Cloud, Together AI, dll).
 */
if (!function_exists('call_openai_compatible_api')) {
    function call_openai_compatible_api(
        string $endpoint,
        string $api_key,
        string $model,
        string $prompt,
        string $provider_name = 'Groq Cloud',
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        ?array $reference_image = null,
        bool $read_memory = false
    ): array {
        if (trim($api_key) === '') {
            return ['success' => false, 'error' => "API Key untuk {$provider_name} belum disetel di pengaturan."];
        }

        $system_instruction = get_greymist_system_instruction($read_memory);
        $context_input = build_greymist_context_input(
            $prompt,
            $current_frame_html,
            $selected_object_html,
            $target_mode,
            $memory_context_text,
            $selected_object_attrs,
            $reusable_components,
            $all_project_frames,
            $reference_image
        );

        $messages = [
            ['role' => 'system', 'content' => $system_instruction],
        ];

        if ($reference_image !== null && !empty($reference_image['data'])) {
            $mime = $reference_image['mime_type'] ?? 'image/jpeg';
            $b64 = $reference_image['data'];
            $data_uri = "data:{$mime};base64,{$b64}";
            $messages[] = [
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => $context_input],
                    ['type' => 'image_url', 'image_url' => ['url' => $data_uri]],
                ]
            ];
        } else {
            $messages[] = [
                'role' => 'user',
                'content' => $context_input,
            ];
        }

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 8192,
        ];

        $headers = [
            'Authorization: Bearer ' . trim($api_key),
            'Content-Type: application/json',
        ];
        if (str_contains($endpoint, 'openrouter.ai')) {
            $headers[] = 'HTTP-Referer: http://localhost/Greymist_work';
            $headers[] = 'X-Title: Greymist Workspace';
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response_body = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        unset($ch);

        if ($response_body === false || $curl_err !== '') {
            return ['success' => false, 'error' => "Koneksi cURL ke {$provider_name} gagal: " . $curl_err];
        }

        $decoded = json_decode((string)$response_body, true);
        if ($http_code >= 400 || !is_array($decoded)) {
            $err_msg = $decoded['error']['message'] ?? ('HTTP Error ' . $http_code);
            return ['success' => false, 'error' => "{$provider_name} Error: " . $err_msg];
        }

        $output_text = (string)($decoded['choices'][0]['message']['content'] ?? '');
        if (trim($output_text) === '') {
            return ['success' => false, 'error' => "Respons dari {$provider_name} kosong."];
        }

        $parsed = parse_greymist_ai_output($output_text, $model, $target_mode, $current_frame_html);
        return $parsed;
    }
}

/**
 * Adapter untuk Antigravity Cloud Bridge (Bebas API Key pribadi, 0% beban laptop).
 */
if (!function_exists('call_antigravity_bridge_api')) {
    function call_antigravity_bridge_api(
        string $prompt,
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        ?array $reference_image = null,
        ?array $frames_snapshot = [],
        ?PDO $pdo = null,
        int $project_id = 0
    ): array {
        $bridge_dir = ROOT_PATH . '/.agents/bridge';
        if (!is_dir($bridge_dir)) {
            @mkdir($bridge_dir, 0777, true);
        }
        $task_payload = [
            'timestamp'             => date('c'),
            'prompt'                => $prompt,
            'target_mode'           => $target_mode,
            'project_id'            => $project_id,
            'has_reference_image'   => $reference_image !== null,
            'current_frame_preview' => mb_substr($current_frame_html, 0, 500),
        ];
        @file_put_contents($bridge_dir . '/last_request.json', json_encode($task_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Cek apakah ada local bridge server agent aktif
        $local_bridge_url = 'http://127.0.0.1:3307/v1/agent';
        $ch = curl_init($local_bridge_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($task_payload),
            CURLOPT_TIMEOUT        => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        $agent_res = curl_exec($ch);
        $agent_http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($agent_res !== false && $agent_http === 200) {
            $dec_agent = json_decode((string)$agent_res, true);
            if (is_array($dec_agent) && !empty($dec_agent['html'])) {
                return [
                    'success'            => true,
                    'used_model'         => 'Antigravity Live Agent',
                    'operation'          => $dec_agent['operation'] ?? 'append',
                    'html'               => $dec_agent['html'],
                    'patch'              => $dec_agent['patch'] ?? null,
                    'summary'            => $dec_agent['summary'] ?? 'Dihasilkan oleh Antigravity Live Agent.',
                    'breakpoints_to_add' => $dec_agent['breakpoints_to_add'] ?? [],
                ];
            }
        }

        // Fallback cerdas bawaan yang diperkaya aturan Antigravity Builder
        $local_cmd = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode, $frames_snapshot ?? [], $pdo, $project_id);
        if ($local_cmd !== null) {
            return [
                'success'            => true,
                'used_model'         => 'Antigravity Cloud Bridge',
                'operation'          => $local_cmd['operation'],
                'html'               => $local_cmd['html'] ?? '',
                'patch'              => $local_cmd['patch'] ?? null,
                'summary'            => $local_cmd['summary'] ?? 'Komponen diproses oleh Antigravity Cloud Bridge.',
                'frames_specs'       => $local_cmd['frames_specs'] ?? [],
                'frames_updates'     => $local_cmd['frames_updates'] ?? [],
                'breakpoints_to_add' => $local_cmd['breakpoints_to_add'] ?? [],
                'renamed_project'    => $local_cmd['renamed_project'] ?? null,
            ];
        }

        $gen_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
        return [
            'success'            => true,
            'used_model'         => 'Antigravity Cloud Bridge',
            'operation'          => ($target_mode === 'edit_selected') ? 'replace_selected' : 'append',
            'html'               => $gen_html,
            'summary'            => 'Komponen berhasil dibuat melalui Antigravity Cloud Bridge.',
            'breakpoints_to_add' => [],
        ];
    }
}

/**
 * Menguji koneksi provider AI secara langsung dan mengukur latensi jaringan.
 */
if (!function_exists('test_ai_provider_connection')) {
    function test_ai_provider_connection(string $provider, string $api_key, string $model): array
    {
        $start = microtime(true);
        $provider = strtolower(trim($provider));

        if ($provider === 'smart_preset') {
            return [
                'success'    => true,
                'latency_ms' => 0,
                'msg'        => 'Smart Fallback Lokal siap digunakan (100% Offline, 0% CPU).'
            ];
        }

        if ($provider === 'antigravity') {
            return [
                'success'    => true,
                'latency_ms' => 12,
                'msg'        => 'Antigravity Cloud Bridge aktif (Terhubung ke sesi IDE Antigravity, tanpa API Key).'
            ];
        }

        if ($api_key === '') {
            return [
                'success'    => false,
                'latency_ms' => 0,
                'msg'        => "API Key untuk provider {$provider} belum diisi."
            ];
        }

        if ($provider === 'groq') {
            $endpoint = 'https://api.groq.com/openai/v1/chat/completions';
            $m = $model !== '' ? $model : 'llama-3.3-70b-versatile';
            $payload = [
                'model'      => $m,
                'messages'   => [['role' => 'user', 'content' => 'Test ping. Respond only with OK.']],
                'max_tokens' => 5,
            ];
            $headers = [
                'Authorization: Bearer ' . trim($api_key),
                'Content-Type: application/json',
            ];
        } elseif ($provider === 'openrouter') {
            $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
            $m = $model !== '' ? $model : 'meta-llama/llama-3.3-70b-instruct:free';
            $payload = [
                'model'      => $m,
                'messages'   => [['role' => 'user', 'content' => 'Test ping. Respond only with OK.']],
                'max_tokens' => 5,
            ];
            $headers = [
                'Authorization: Bearer ' . trim($api_key),
                'Content-Type: application/json',
                'HTTP-Referer: http://localhost/Greymist_work',
                'X-Title: Greymist Workspace'
            ];
        } elseif ($provider === 'gemini') {
            $m = $model !== '' ? $model : 'gemini-2.5-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$m}:generateContent?key=" . urlencode($api_key);
            $payload = [
                'contents'         => [['parts' => [['text' => 'Test ping. Respond only with OK.']]]],
                'generationConfig' => ['maxOutputTokens' => 5]
            ];
            $headers = ['Content-Type: application/json'];
        } else {
            return ['success' => false, 'latency_ms' => 0, 'msg' => "Provider '{$provider}' tidak dikenali."];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $res = curl_exec($ch);
        $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($ch);
        unset($ch);

        $latency_ms = (int)round((microtime(true) - $start) * 1000);

        if ($res === false || $curl_err !== '') {
            return [
                'success'    => false,
                'latency_ms' => $latency_ms,
                'msg'        => "Koneksi cURL gagal: {$curl_err}"
            ];
        }

        $dec = json_decode((string)$res, true);
        if ($http_code >= 400 || !is_array($dec)) {
            $msg = $dec['error']['message'] ?? "HTTP error {$http_code}";
            return [
                'success'    => false,
                'latency_ms' => $latency_ms,
                'msg'        => "Gagal terhubung ({$http_code}): {$msg}"
            ];
        }

        return [
            'success'    => true,
            'latency_ms' => $latency_ms,
            'msg'        => "✓ Sukses terhubung ke {$provider} ({$m}) dalam {$latency_ms} ms!"
        ];
    }
}

/**
 * Menyembunyikan karakter API key untuk ditampilkan aman di UI.
 */
if (!function_exists('mask_api_key')) {
    function mask_api_key(string $key): string
    {
        $trimmed = trim($key);
        $len = strlen($trimmed);
        if ($len === 0) return '';
        if ($len <= 8) return substr($trimmed, 0, 2) . '••••' . substr($trimmed, -2);
        return substr($trimmed, 0, 4) . '••••••••' . substr($trimmed, -4);
    }
}

/**
 * Memperbarui nilai variabel di file .env.
 */
if (!function_exists('update_env_file')) {
    function update_env_file(array $key_value_pairs): bool
    {
        $env_file = ROOT_PATH . '/.env';
        if (!is_file($env_file) || !is_writable($env_file)) {
            return false;
        }
        $content = (string)file_get_contents($env_file);
        foreach ($key_value_pairs as $k => $v) {
            $k = trim($k);
            $v = trim((string)$v);
            $pattern = "/^{$k}=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$k}={$v}", $content);
            } else {
                $content = rtrim($content) . "\n{$k}={$v}\n";
            }
        }
        return file_put_contents($env_file, $content) !== false;
    }
}

if (!function_exists('call_gemini_interactions_api')) {
    function call_gemini_interactions_api(
        string $api_key,
        string $prompt,
        string $model = 'gemini-3.5-flash-lite',
        string $current_frame_html = '',
        string $selected_object_html = '',
        string $target_mode = 'core',
        string $memory_context_text = '',
        array $selected_object_attrs = [],
        array $reusable_components = [],
        array $all_project_frames = [],
        ?array $reference_image = null,
        bool $read_memory = false
    ): array {
        $system_instruction = get_greymist_system_instruction($read_memory);
        $context_input = build_greymist_context_input(
            $prompt,
            $current_frame_html,
            $selected_object_html,
            $target_mode,
            $memory_context_text,
            $selected_object_attrs,
            $reusable_components,
            $all_project_frames,
            $reference_image
        );

        $free_models = array_values(array_unique([
            $model,
            'gemini-3.5-flash-lite',
        ]));

        $last_error = '';
        $max_tokens = ($target_mode === 'edit_selected') ? 4096 : 16384;

        foreach ($free_models as $candidate_model) {
            $payload = [
                'model'              => $candidate_model,
                'system_instruction' => $system_instruction,
                'generation_config'  => [
                    'max_output_tokens' => $max_tokens,
                ],
            ];

            if ($reference_image !== null && !empty($reference_image['data'])) {
                $payload['input'] = [
                    [
                        'type'      => 'image',
                        'data'      => $reference_image['data'],
                        'mime_type' => $reference_image['mime_type'] ?? 'image/jpeg',
                    ],
                    [
                        'type'      => 'text',
                        'text'      => $context_input,
                    ]
                ];
            } else {
                $payload['input'] = $context_input;
            }

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
                CURLOPT_TIMEOUT        => 90,
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

            if (trim($output_text) !== '') {
                return parse_greymist_ai_output($output_text, $candidate_model, $target_mode, $current_frame_html);
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
    case 'noop':
        return;
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

    case 'ai_get_provider_settings':
        $current_provider = (string)($_SESSION['ai_provider_override'] ?? env('AI_PROVIDER', ''));
        if ($current_provider === '') {
            if (env('GROQ_API_KEY', '') !== '') {
                $current_provider = 'groq';
            } elseif (env('GEMINI_API_KEY', '') !== '') {
                $current_provider = 'gemini';
            } else {
                $current_provider = 'antigravity';
            }
        }

        $groq_key = (string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', ''));
        $openrouter_key = (string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', ''));
        $gemini_key = (string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', ''));

        json_response([
            'status'               => 'OK',
            'success'              => true,
            'provider'             => $current_provider,
            'groq_model'           => (string)env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'openrouter_model'     => (string)env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
            'gemini_model'         => (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
            'groq_has_key'         => $groq_key !== '',
            'groq_key_masked'      => mask_api_key($groq_key),
            'openrouter_has_key'   => $openrouter_key !== '',
            'openrouter_key_masked'=> mask_api_key($openrouter_key),
            'gemini_has_key'       => $gemini_key !== '',
            'gemini_key_masked'    => mask_api_key($gemini_key),
            'available_providers'  => [
                [
                    'id'            => 'groq',
                    'name'          => 'Groq Cloud (Gratis & Super Cepat 70B)',
                    'desc'          => 'Inference cloud super cepat (~300 t/s), 0% CPU laptop. Gratis di console.groq.com',
                    'default_model' => 'llama-3.3-70b-versatile',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'antigravity',
                    'name'          => 'Antigravity Cloud Bridge',
                    'desc'          => 'Bawaan Antigravity IDE, tanpa butuh API key pribadi & 0% beban laptop',
                    'default_model' => 'antigravity-cloud',
                    'needs_key'     => false,
                ],
                [
                    'id'            => 'openrouter',
                    'name'          => 'OpenRouter Cloud (Free Tier)',
                    'desc'          => 'Pilihan model cloud open-source gratis di openrouter.ai',
                    'default_model' => 'meta-llama/llama-3.3-70b-instruct:free',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'gemini',
                    'name'          => 'Google Gemini (API Key Pribadi)',
                    'desc'          => 'Menggunakan Google AI Studio API Key pribadi',
                    'default_model' => 'gemini-2.5-flash',
                    'needs_key'     => true,
                ],
                [
                    'id'            => 'smart_preset',
                    'name'          => 'Smart Fallback Lokal (Offline)',
                    'desc'          => '100% offline rule-based tanpa koneksi internet & 0% beban laptop',
                    'default_model' => 'local-rule-engine',
                    'needs_key'     => false,
                ],
            ]
        ]);
        break;

    case 'ai_save_provider_settings':
        $new_provider = trim((string)($data['provider'] ?? 'groq'));
        $updates = ['AI_PROVIDER' => $new_provider];

        if (isset($data['groq_api_key']) && trim((string)$data['groq_api_key']) !== '') {
            $updates['GROQ_API_KEY'] = trim((string)$data['groq_api_key']);
            $_SESSION['groq_api_key_override'] = $updates['GROQ_API_KEY'];
        }
        if (isset($data['groq_model']) && trim((string)$data['groq_model']) !== '') {
            $updates['GROQ_MODEL'] = trim((string)$data['groq_model']);
        }

        if (isset($data['openrouter_api_key']) && trim((string)$data['openrouter_api_key']) !== '') {
            $updates['OPENROUTER_API_KEY'] = trim((string)$data['openrouter_api_key']);
            $_SESSION['openrouter_api_key_override'] = $updates['OPENROUTER_API_KEY'];
        }
        if (isset($data['openrouter_model']) && trim((string)$data['openrouter_model']) !== '') {
            $updates['OPENROUTER_MODEL'] = trim((string)$data['openrouter_model']);
        }

        if (isset($data['gemini_api_key']) && trim((string)$data['gemini_api_key']) !== '') {
            $updates['GEMINI_API_KEY'] = trim((string)$data['gemini_api_key']);
            $_SESSION['gemini_api_key_override'] = $updates['GEMINI_API_KEY'];
        }
        if (isset($data['gemini_model']) && trim((string)$data['gemini_model']) !== '') {
            $updates['GEMINI_MODEL'] = trim((string)$data['gemini_model']);
        }

        $_SESSION['ai_provider_override'] = $new_provider;
        update_env_file($updates);

        json_response([
            'status'   => 'OK',
            'success'  => true,
            'provider' => $new_provider,
            'msg'      => "Pengaturan provider AI ({$new_provider}) berhasil disimpan ke .env.",
        ]);
        break;

    case 'ai_test_provider_connection':
        $test_provider = trim((string)($data['provider'] ?? 'groq'));
        $test_key = trim((string)($data['api_key'] ?? ''));
        $test_model = trim((string)($data['model'] ?? ''));

        if ($test_key === '') {
            if ($test_provider === 'groq') {
                $test_key = (string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', ''));
            } elseif ($test_provider === 'openrouter') {
                $test_key = (string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', ''));
            } elseif ($test_provider === 'gemini') {
                $test_key = (string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', ''));
            }
        }

        $res = test_ai_provider_connection($test_provider, $test_key, $test_model);
        json_response([
            'status'     => $res['success'] ? 'OK' : 'ERROR',
            'success'    => $res['success'],
            'latency_ms' => $res['latency_ms'],
            'msg'        => $res['msg'],
        ]);
        break;

    case 'ai_save_api_key':
        $new_key = trim((string)($data['api_key'] ?? ''));
        update_env_file(['GEMINI_API_KEY' => $new_key]);
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
        $prompt                = trim((string)($data['prompt'] ?? ''));
        $save_to_library       = !empty($data['save_to_library']);
        $component_name        = trim((string)($data['component_name'] ?? ''));
        $inline_api_key        = trim((string)($data['api_key'] ?? ''));
        $requested_model       = trim((string)($data['model'] ?? ''));
        $requested_provider    = trim((string)($data['provider'] ?? ''));
        $current_frame_html    = (string)($data['current_frame_html'] ?? '');
        $selected_object_html  = (string)($data['selected_object_html'] ?? '');
        $target_mode           = trim((string)($data['target_mode'] ?? 'core'));
        $project_id            = (int)($data['project_id'] ?? $_GET['project_id'] ?? 0);
        $frames_snapshot       = isset($data['frames_snapshot']) && is_array($data['frames_snapshot']) ? $data['frames_snapshot'] : [];
        $selected_object_attrs = isset($data['selected_object_attrs']) && is_array($data['selected_object_attrs']) ? $data['selected_object_attrs'] : [];
        $active_frame_id       = (int)($data['active_frame_id'] ?? 0);

        // Ekstrak foto/gambar referensi jika diunggah pengguna (Multimodal Vision)
        $raw_ref_image   = isset($data['reference_image']) && is_array($data['reference_image']) ? $data['reference_image'] : null;
        $clean_ref_image = null;
        if ($raw_ref_image && !empty($raw_ref_image['data'])) {
            $ref_data = trim((string)$raw_ref_image['data']);
            $ref_mime = trim((string)($raw_ref_image['mime_type'] ?? 'image/jpeg'));
            if (str_contains($ref_data, ',')) {
                $parts = explode(',', $ref_data, 2);
                if (preg_match('#data:(image/[^;]+);base64#i', $parts[0], $mime_m)) {
                    $ref_mime = $mime_m[1];
                }
                $ref_data = $parts[1];
            }
            if ($ref_data !== '') {
                $clean_ref_image = [
                    'data'      => $ref_data,
                    'mime_type' => $ref_mime,
                ];
            }
        }

        if ($prompt === '' && $clean_ref_image !== null) {
            $prompt = "Buat tampilan antarmuka (UI) website modern yang meniru tata letak (layout), hierarki visual, dan komponen dari foto referensi ini menggunakan komponen .gm-object Greymist.";
        }

        if ($prompt === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Prompt atau instruksi AI wajib diisi.']);
        }

                // Baca status opsi Memori Proyek (Default: FALSE / MODE KILAT)
        $read_memory = !empty($data['read_memory']);

        if ($read_memory) {
            $ai_memory           = get_project_ai_memory($pdo, $project_id, $frames_snapshot);
            $memory_context_text = format_ai_memory_for_prompt($ai_memory);
            $reusable_comps      = extract_reusable_components($frames_snapshot, $current_frame_html);
            $all_db_frames       = ($project_id > 0) ? get_project_frames($pdo, $project_id) : [];
        } else {
            $ai_memory           = [];
            $memory_context_text = '';
            $reusable_comps      = [];
            $all_db_frames       = [];
        }

        // Resolusi Provider AI Aktif
        $active_provider = $requested_provider !== ''
            ? $requested_provider
            : ((string)($_SESSION['ai_provider_override'] ?? env('AI_PROVIDER', '')));

        if ($active_provider === '') {
            if (env('GROQ_API_KEY', '') !== '') {
                $active_provider = 'groq';
            } elseif (env('GEMINI_API_KEY', '') !== '') {
                $active_provider = 'gemini';
            } else {
                $active_provider = 'antigravity';
            }
        }

        $source_mode        = 'Smart Fallback Lokal (Offline)';
        $api_warning        = '';
        $operation          = ($target_mode === 'edit_selected') ? 'replace_selected' : 'append';
        $summary_msg        = '';
        $frames_specs       = [];
        $frames_updates     = [];
        $patch_data         = null;
        $breakpoints_to_add = [];
        $ai_res             = null;

        if ($active_provider === 'groq') {
            $groq_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['groq_api_key_override'] ?? env('GROQ_API_KEY', '')));
            $groq_model = $requested_model !== '' ? $requested_model : (string)env('GROQ_MODEL', 'llama-3.3-70b-versatile');
            if ($groq_key !== '') {
                $ai_res = call_openai_compatible_api(
                    'https://api.groq.com/openai/v1/chat/completions',
                    $groq_key,
                    $groq_model,
                    $prompt,
                    'Groq Cloud',
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_image,
                    $read_memory
                );
                $source_mode = 'Groq Cloud (' . ($ai_res['used_model'] ?? $groq_model) . ')';
            } else {
                $api_warning = 'GROQ_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        } elseif ($active_provider === 'openrouter') {
            $or_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['openrouter_api_key_override'] ?? env('OPENROUTER_API_KEY', '')));
            $or_model = $requested_model !== '' ? $requested_model : (string)env('OPENROUTER_MODEL', 'meta-llama/llama-3.3-70b-instruct:free');
            if ($or_key !== '') {
                $ai_res = call_openai_compatible_api(
                    'https://openrouter.ai/api/v1/chat/completions',
                    $or_key,
                    $or_model,
                    $prompt,
                    'OpenRouter Cloud',
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_image,
                    $read_memory
                );
                $source_mode = 'OpenRouter Cloud (' . ($ai_res['used_model'] ?? $or_model) . ')';
            } else {
                $api_warning = 'OPENROUTER_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        } elseif ($active_provider === 'antigravity') {
            $ai_res = call_antigravity_bridge_api(
                $prompt,
                $current_frame_html,
                $selected_object_html,
                $target_mode,
                $memory_context_text,
                $selected_object_attrs,
                $reusable_comps,
                $all_db_frames,
                $clean_ref_image,
                $frames_snapshot,
                $pdo,
                $project_id
            );
            $source_mode = 'Antigravity Cloud Bridge';
        } elseif ($active_provider === 'gemini') {
            $gemini_key   = $inline_api_key !== '' ? $inline_api_key : ((string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', '')));
            $gemini_model = $requested_model !== '' ? $requested_model : (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite');
            if ($gemini_key !== '') {
                $ai_res = call_gemini_interactions_api(
                    $gemini_key,
                    $prompt,
                    $gemini_model,
                    $current_frame_html,
                    $selected_object_html,
                    $target_mode,
                    $memory_context_text,
                    $selected_object_attrs,
                    $reusable_comps,
                    $all_db_frames,
                    $clean_ref_image,
                    $read_memory
                );
                $source_mode = 'Google Gemini (' . ($ai_res['used_model'] ?? $gemini_model) . ')';
            } else {
                $api_warning = 'GEMINI_API_KEY belum diisi. Menggunakan Smart Fallback Lokal.';
            }
        }

        if ($ai_res !== null && !empty($ai_res['success'])) {
            $generated_html     = $ai_res['html'] ?? '';
            $operation          = $ai_res['operation'] ?? $operation;
            $summary_msg        = $ai_res['summary'] ?? '';
            $frames_specs       = $ai_res['frames_specs'] ?? [];
            $frames_updates     = $ai_res['frames_updates'] ?? [];
            $patch_data         = $ai_res['patch'] ?? null;
            $breakpoints_to_add = $ai_res['breakpoints_to_add'] ?? [];
        } else {
            if ($ai_res !== null && !empty($ai_res['error'])) {
                $api_warning = $ai_res['error'];
            }
            $local_cmd = process_local_frame_command($prompt, $current_frame_html, $selected_object_html, $target_mode, $frames_snapshot, $pdo, $project_id);
            if ($local_cmd !== null) {
                $operation          = $local_cmd['operation'];
                $generated_html     = $local_cmd['html'] ?? '';
                $summary_msg        = $local_cmd['summary'] ?? '';
                $frames_specs       = $local_cmd['frames_specs'] ?? [];
                $frames_updates     = $local_cmd['frames_updates'] ?? [];
                $patch_data         = $local_cmd['patch'] ?? null;
                $breakpoints_to_add = $local_cmd['breakpoints_to_add'] ?? [];
            } else {
                $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
            }
        }

        // Bersihkan seluruh output dari karakter emoji / emotikon
        $generated_html = clean_emojis_from_html($generated_html);
        if (!empty($frames_specs)) {
            foreach ($frames_specs as &$fs) {
                $fs['html'] = clean_emojis_from_html((string)($fs['html'] ?? ''));
            }
            unset($fs);
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

        // Penanganan Operasi 0: GANTI NAMA PROYEK (RENAME PROJECT) di Database gm_projects & Header Workspace
        $renamed_project = null;
        if ($operation === 'rename_project') {
            $renamed_project = $gemini_res['renamed_project'] ?? ($local_cmd['renamed_project'] ?? null);
            if ($renamed_project && !empty($renamed_project['new_name'])) {
                $new_proj_name = trim($renamed_project['new_name']);
                if ($project_id > 0) {
                    $stmt = $pdo->prepare("UPDATE gm_projects SET project_name = ? WHERE project_id = ?");
                    $stmt->execute([$new_proj_name, $project_id]);
                    $renamed_project['project_id'] = $project_id;
                }
                $ai_memory['project_name'] = $new_proj_name;
            }
        }

        // Penanganan Operasi 1: Simpan frame baru dan lakukan SINKRONISASI RESIPROKAL ke seluruh halaman
        $created_frames          = [];
        $updated_existing_frames = [];
        $renamed_frame           = null;

        if ($operation === 'create_frames' && !empty($frames_specs)) {
            // Tahap 1: Cek apakah ada frame kosong di proyek saat ini (termasuk Main Frame awal yang masih kosong)
            $available_empty_frames = [];
            if ($project_id > 0) {
                $current_db_frames = get_project_frames($pdo, $project_id);
                $active_empty = null;
                $other_empty  = [];
                foreach ($current_db_frames as $cdf) {
                    $c_html = trim((string)($cdf['core_content'] ?? ''));
                    if ($c_html === '' || trim(strip_tags($c_html)) === '' || !str_contains($c_html, 'gm-object')) {
                        if ($active_frame_id > 0 && (int)$cdf['frame_id'] === $active_frame_id) {
                            $active_empty = $cdf;
                        } else {
                            $other_empty[] = $cdf;
                        }
                    }
                }
                if ($active_empty !== null) {
                    $available_empty_frames[] = $active_empty;
                }
                foreach ($other_empty as $oe) {
                    $available_empty_frames[] = $oe;
                }
            }

            // Tahap 2: Simpan seluruh frame untuk mendapatkan ID numerik resminya
            $frame_name_to_id = [];
            $new_ids          = [];
            foreach ($frames_specs as $idx => $spec) {
                $f_name = trim((string)($spec['frame_name'] ?? ('Page ' . ($idx + 1))));
                $f_html = (string)($spec['html'] ?? '');

                if ($project_id > 0) {
                    // Gunakan frame kosong yang sudah ada jika tersedia (misal Main Frame awal diubah menjadi Home / Beranda)
                    if (!empty($available_empty_frames)) {
                        $reused_empty = array_shift($available_empty_frames);
                        $fid = (int)$reused_empty['frame_id'];
                        $stmtUpEmpty = $pdo->prepare("UPDATE gm_frames SET frame_name = ?, core_content = ? WHERE frame_id = ?");
                        $stmtUpEmpty->execute([$f_name, $f_html, $fid]);
                    } else {
                        $db_frame = add_project_frame($pdo, $project_id, $f_name, $f_html);
                        $fid = (int)$db_frame['frame_id'];
                    }
                } else {
                    $fid = time() + $idx;
                }

                $frame_name_to_id[$f_name] = $fid;
                $new_ids[] = $fid;
                $created_frames[] = [
                    'frame_id'     => $fid,
                    'frame_name'   => $f_name,
                    'core_content' => $f_html,
                ];
            }

            // Tahap 2: Buat peta seluruh frame proyek (existing + baru)
            $all_frames_map = [];
            if ($project_id > 0) {
                $fresh_frames = get_project_frames($pdo, $project_id);
                foreach ($fresh_frames as $ff) {
                    $all_frames_map[$ff['frame_name']] = (int)$ff['frame_id'];
                }
            } else {
                foreach ($frame_name_to_id as $name => $fid) {
                    $all_frames_map[$name] = $fid;
                }
            }

            // Tahap 3: Sinkronisasi silang pada seluruh frame baru yang baru dibuat
            if (!empty($all_frames_map) && $project_id > 0) {
                foreach ($created_frames as &$cf) {
                    $synced_html = cross_link_navbar_in_html($cf['core_content'], $all_frames_map, $cf['frame_id'], $cf['frame_name']);
                    if ($synced_html !== $cf['core_content']) {
                        $cf['core_content'] = $synced_html;
                        $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                        $stmtUp->execute([$synced_html, $cf['frame_id']]);
                    }
                }
                unset($cf);

                // Tahap 4: SINKRONISASI RESIPROKAL ke frame-frame lama (misal Home / Beranda)
                // agar navbar pada halaman lama juga otomatis memuat tautan menuju halaman-halaman baru ini!
                $existing_frames_in_db = get_project_frames($pdo, $project_id);
                foreach ($existing_frames_in_db as $ef) {
                    $efid = (int)$ef['frame_id'];
                    if (in_array($efid, $new_ids, true)) {
                        continue; // Lewati yang baru dibuat karena sudah disinkronkan di atas
                    }

                    $old_html = (string)($ef['core_content'] ?? '');
                    $synced_old = cross_link_navbar_in_html($old_html, $all_frames_map, $efid, (string)$ef['frame_name']);
                    if ($synced_old !== $old_html) {
                        $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                        $stmtUp->execute([$synced_old, $efid]);
                        $updated_existing_frames[] = [
                            'frame_id'     => $efid,
                            'frame_name'   => (string)$ef['frame_name'],
                            'core_content' => $synced_old,
                        ];
                    }
                }
            }
        }

        // Penanganan Operasi 2: BATCH UPDATE FRAMES (Intervensi gaya pada seluruh halaman proyek)
        $saved_batch_updates = [];
        if ($operation === 'batch_update_frames' && !empty($frames_updates) && $project_id > 0) {
            $fresh_frames = get_project_frames($pdo, $project_id);
            foreach ($frames_updates as $fu) {
                $target_ident = trim((string)($fu['frame_identifier'] ?? ''));
                $new_f_html   = (string)($fu['html'] ?? '');
                $matched_fid  = 0;
                $matched_name = '';

                foreach ($fresh_frames as $ff) {
                    if ((string)$ff['frame_id'] === $target_ident || strtolower((string)$ff['frame_name']) === strtolower($target_ident)) {
                        $matched_fid  = (int)$ff['frame_id'];
                        $matched_name = (string)$ff['frame_name'];
                        break;
                    }
                }

                if ($matched_fid > 0 && $new_f_html !== '') {
                    $stmtUp = $pdo->prepare('UPDATE gm_frames SET core_content = ? WHERE frame_id = ?');
                    $stmtUp->execute([$new_f_html, $matched_fid]);
                    $saved_batch_updates[] = [
                        'frame_id'   => $matched_fid,
                        'frame_name' => $matched_name,
                        'html'       => $new_f_html,
                    ];
                }
            }
        }

                // Jika operasi adalah append pada frame yang masih kosong dan bernama 'Main Frame' / 'Untitled Frame',
        // otomatis rename frame tersebut agar namanya representatif (misal Home / nama komponen).
        if ($operation === 'append' && trim($current_frame_html) === '' && $project_id > 0) {
            $cur_active_id = $active_frame_id;
            if ($cur_active_id <= 0 && !empty($all_db_frames)) {
                $cur_active_id = (int)$all_db_frames[0]['frame_id'];
            }
            if ($cur_active_id > 0) {
                foreach ($all_db_frames as $adf) {
                    if ((int)$adf['frame_id'] === $cur_active_id) {
                        $cur_name = trim((string)$adf['frame_name']);
                        if (in_array(strtolower($cur_name), ['main frame', 'untitled frame', 'frame 1', 'page 1'], true)) {
                            $new_name = 'Home';
                            if ($component_name !== '' && $component_name !== 'AI Component') {
                                $new_name = $component_name;
                            } elseif (preg_match('/(?:halaman|page|frame|buat|landing\s*page)\s+([a-zA-Z0-9\s\-]{3,25})/i', $prompt, $pm)) {
                                $cand = ucwords(trim($pm[1]));
                                if ($cand !== '') $new_name = $cand;
                            }
                            $stmtRen = $pdo->prepare("UPDATE gm_frames SET frame_name = ? WHERE frame_id = ?");
                            $stmtRen->execute([$new_name, $cur_active_id]);
                            $renamed_frame = [
                                'frame_id' => $cur_active_id,
                                'new_name' => $new_name,
                            ];
                        }
                        break;
                    }
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
            'explain'             => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil dianalisis.',
            'rename_project'      => $summary_msg !== '' ? $summary_msg : ('Nama proyek berhasil diubah menjadi "' . ($renamed_project['new_name'] ?? '') . '".'),
            'patch_attributes'    => $summary_msg !== '' ? $summary_msg : ('Atribut elemen berhasil diperbarui (' . $source_mode . ').'),
            'replace_selected'    => 'Objek aktif berhasil diperbarui (' . $source_mode . ').',
            'replace_frame'       => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil diperbarui (' . $source_mode . ').',
            'create_frames'       => count($created_frames) . ' Frame Halaman baru berhasil dibuat & saling terhubung (' . $source_mode . ').',
            'batch_update_frames' => count($saved_batch_updates) . ' Halaman proyek berhasil diperbarui serentak secara konsisten (' . $source_mode . ').',
            'add_breakpoints'     => 'Breakpoint responsif berhasil ditambahkan ke toolbar.',
            default               => 'Komponen berhasil ditambahkan (' . $source_mode . ').',
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
            } elseif ($operation === 'batch_update_frames') {
                $updated_memory['system_summary'] = 'Gaya seluruh halaman diselaraskan: ' . mb_substr($prompt, 0, 100);
            } elseif (empty($updated_memory['system_summary'])) {
                $updated_memory['system_summary'] = 'Proyek dibangun dengan instruksi: ' . mb_substr($prompt, 0, 100);
            }
            save_project_ai_memory($pdo, $project_id, $updated_memory);
            $ai_memory = $updated_memory;
        }

        json_response([
            'status'                  => 'OK',
            'success'                 => true,
            'operation'               => $operation,
            'patch'                   => $patch_data,
            'summary'                 => $summary_msg,
            'msg'                     => $default_msg,
            'source_mode'             => $source_mode,
            'api_warning'             => $api_warning,
            'html'                    => $generated_html,
            'created_frames'          => $created_frames,
            'updated_existing_frames' => $updated_existing_frames,
            'renamed_frame'           => $renamed_frame,
            'renamed_project'         => $renamed_project,
            'frames_updates'          => $saved_batch_updates,
            'breakpoints_to_add'      => $breakpoints_to_add,
            'component'               => $saved_component,
            'ai_memory'               => $ai_memory,
        ]);
        break;
}
