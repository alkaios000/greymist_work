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
 * Memproses penghapusan atau pembacaan komponen secara lokal pada HTML frame (Fallback Cerdas).
 */
if (!function_exists('process_local_frame_command')) {
    function process_local_frame_command(string $prompt, string $current_frame_html): ?array
    {
        $lower = strtolower(trim($prompt));

        // 1. Jika meminta membaca / menjelaskan isi frame
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

        // 2. Jika meminta menghapus semua / mengosongkan frame
        if (preg_match('/\b(hapus semua|kosongkan|bersihkan frame|clear all|delete all)\b/i', $lower)) {
            return [
                'operation' => 'replace_frame',
                'html'      => '',
                'summary'   => 'Seluruh komponen di dalam frame telah dihapus.',
            ];
        }

        // 3. Jika meminta menghapus komponen tertentu (misal: hapus navbar, hapus footer, hapus hero, hapus pricing, hapus form)
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

        return null;
    }
}

/**
 * Memanggil Gemini Interactions API menggunakan model Free Tier (Gratis) dengan konteks isi Frame aktif.
 * Mendukung operasi:
 * - APPEND (menambah komponen baru)
 * - REPLACE_FRAME (menghapus komponen di dalam frame atau mengedit isi frame yang sudah ada)
 * - EXPLAIN (membaca dan menjelaskan isi frame kepada pengguna)
 */
if (!function_exists('call_gemini_interactions_api')) {
    function call_gemini_interactions_api(
        string $api_key,
        string $prompt,
        string $model = 'gemini-3.5-flash-lite',
        string $current_frame_html = '',
        string $selected_object_html = ''
    ): array {
        $system_instruction = "Anda adalah AI Visual Workspace Assistant untuk editor web Greymist.\n"
            . "Anda memiliki kemampuan untuk MEMBACA isi frame saat ini (CURRENT_FRAME_HTML), MENAMBAH komponen baru, MENGEDIT komponen yang ada, atau MENGHAPUS komponen dari dalam frame.\n\n"
            . "PILIH SALAH SATU PREFIX OPERASI DI BARIS PERTAMA RESPONS ANDA:\n"
            . "1. Jika pengguna meminta MEMBACA / MENJELASKAN apa yang ada di dalam frame (tanpa mengubah kode):\n"
            . "   Awali dengan: <!--GM_OP:EXPLAIN--> diikuti penjelasan ringkas isi frame dalam Bahasa Indonesia.\n"
            . "2. Jika pengguna meminta MENGHAPUS komponen tertentu (misal 'hapus navbar', 'hapus bagian harga', 'hapus tombol', 'kosongkan frame') ATAU MENGUBAH/MENGEDIT elemen yang sudah ada di dalam CURRENT_FRAME_HTML:\n"
            . "   Awali dengan: <!--GM_OP:REPLACE_FRAME--> diikuti SELURUH kode HTML isi frame terbaru setelah komponen tersebut dihapus/diedit (jika hasil akhirnya kosong, cukup tulis <!--GM_OP:REPLACE_FRAME--> saja).\n"
            . "3. Jika pengguna meminta MEMBUAT / MENAMBAHKAN komponen baru ke dalam frame:\n"
            . "   Awali dengan: <!--GM_OP:APPEND--> diikuti HANYA kode HTML komponen baru tersebut (sesuaikan gaya/warnanya agar serasi dengan CURRENT_FRAME_HTML).\n\n"
            . "ATURAN KODE HTML WAJIB:\n"
            . "- Jangan gunakan markdown code fences (jangan tulis ```html).\n"
            . "- SETIAP elemen tag HTML (section, div, nav, header, footer, h1, h2, h3, p, span, a, button, input, img, ul, li) WAJIB memiliki class=\"gm-object\".\n"
            . "- Gunakan inline style (style=\"...\") yang rapi, modern, dan responsif.";

        $context_input = "CURRENT_FRAME_HTML:\n" . ($current_frame_html !== '' ? $current_frame_html : "(Frame masih kosong)") . "\n\n";
        if ($selected_object_html !== '') {
            $context_input .= "SELECTED_OBJECT_HTML (Objek yang sedang diklik pengguna):\n" . $selected_object_html . "\n\n";
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
                    'max_output_tokens' => 4096,
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
                CURLOPT_TIMEOUT        => 40,
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

                $operation = 'append';
                if (str_contains($trimmed_out, '<!--GM_OP:EXPLAIN-->')) {
                    $operation   = 'explain';
                    $trimmed_out = trim(str_replace('<!--GM_OP:EXPLAIN-->', '', $trimmed_out));
                    return [
                        'success'    => true,
                        'used_model' => $candidate_model,
                        'operation'  => 'explain',
                        'summary'    => $trimmed_out,
                        'html'       => $current_frame_html,
                    ];
                } elseif (str_contains($trimmed_out, '<!--GM_OP:REPLACE_FRAME-->')) {
                    $operation   = 'replace_frame';
                    $trimmed_out = trim(str_replace('<!--GM_OP:REPLACE_FRAME-->', '', $trimmed_out));
                } elseif (str_contains($trimmed_out, '<!--GM_OP:APPEND-->')) {
                    $operation   = 'append';
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

        if ($prompt === '') {
            json_response(['status' => 'ERROR', 'success' => false, 'msg' => 'Prompt atau instruksi AI wajib diisi.']);
        }

        $api_key = $inline_api_key !== ''
            ? $inline_api_key
            : ((string)($_SESSION['gemini_api_key_override'] ?? env('GEMINI_API_KEY', '')));
        $model   = $requested_model !== ''
            ? $requested_model
            : (string)env('GEMINI_MODEL', 'gemini-3.5-flash-lite');

        $source_mode = 'smart_preset (Gratis Lokal)';
        $api_warning = '';
        $operation   = 'append';
        $summary_msg = '';

        if ($model !== 'local-free' && $api_key !== '') {
            $gemini_res = call_gemini_interactions_api($api_key, $prompt, $model, $current_frame_html, $selected_object_html);
            if ($gemini_res['success']) {
                $generated_html = $gemini_res['html'] ?? '';
                $operation      = $gemini_res['operation'] ?? 'append';
                $summary_msg    = $gemini_res['summary'] ?? '';
                $used_model     = $gemini_res['used_model'] ?? $model;
                $source_mode    = 'Gemini Free Tier (' . $used_model . ')';
            } else {
                $api_warning = $gemini_res['error'];
                $local_cmd   = process_local_frame_command($prompt, $current_frame_html);
                if ($local_cmd !== null) {
                    $operation      = $local_cmd['operation'];
                    $generated_html = $local_cmd['html'];
                    $summary_msg    = $local_cmd['summary'] ?? '';
                } else {
                    $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
                }
            }
        } else {
            $local_cmd = process_local_frame_command($prompt, $current_frame_html);
            if ($local_cmd !== null) {
                $operation      = $local_cmd['operation'];
                $generated_html = $local_cmd['html'];
                $summary_msg    = $local_cmd['summary'] ?? '';
            } else {
                $generated_html = ensure_gm_object_class(generate_smart_preset_component($prompt));
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
            'explain'       => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil dianalisis.',
            'replace_frame' => $summary_msg !== '' ? $summary_msg : 'Isi frame berhasil diperbarui (' . $source_mode . ').',
            default         => 'Komponen berhasil ditambahkan (' . $source_mode . ').',
        };

        json_response([
            'status'      => 'OK',
            'success'     => true,
            'operation'   => $operation,
            'summary'     => $summary_msg,
            'msg'         => $default_msg,
            'source_mode' => $source_mode,
            'api_warning' => $api_warning,
            'html'        => $generated_html,
            'component'   => $saved_component,
        ]);
        break;
}
