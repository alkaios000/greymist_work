<?php
/**
 * File: app/controllers/ai/ai_memory.php
 * Modul: Manajemen Cache Memori Konteks Proyek & Riwayat Arsitektur Greymist AI.
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

        // Ambil susunan seluruh frame/halaman terkini
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

