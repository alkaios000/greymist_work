<?php
/**
 * File: app/controllers/ai/ai_local_operations.php
 * Modul: Smart Fallback Presets dan Operasi Manipulasi Frame / DOM Lokal.
 */

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