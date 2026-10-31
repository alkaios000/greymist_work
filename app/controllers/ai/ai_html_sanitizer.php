<?php
/**
 * File: app/controllers/ai/ai_html_sanitizer.php
 * Modul: Pembersihan, Sanitasi, Standarisasi Class gm-object, Penamaan ID Semantik,
 *        dan Sinkronisasi Link Navigasi (Smooth Scroll & Multi-Page).
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
                    if ((str_contains($cls, 'nav') || str_contains($id, 'nav')) && !str_contains($cls, 'slide-navigation') && !str_contains($cls, 'slide-nav') && !str_contains($id, 'slide-nav')) {
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

        return trim(ensure_semantic_ids_and_scroll_links($html));
    }
}

if (!function_exists('ensure_semantic_ids_and_scroll_links')) {
    function ensure_semantic_ids_and_scroll_links(string $html): string
    {
        if (!str_contains($html, '<section') && !str_contains($html, '<nav') && !str_contains($html, '<header') && !str_contains($html, '<footer')) {
            return $html;
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $encoded_html = '<?xml encoding="utf-8" ?><div id="gm-autonav-wrapper">' . $html . '</div>';
        $doc->loadHTML($encoded_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $doc->getElementById('gm-autonav-wrapper');
        if (!$root) {
            return $html;
        }

        // Kamus kata kunci untuk mendeteksi ID & class semantik section
        $section_keywords = [
            'hero'         => ['hero', 'banner', 'selamat datang', 'welcome', 'beranda', 'header'],
            'about'        => ['about', 'tentang', 'profil', 'tentang kami', 'profil kami', 'siapa kami'],
            'services'     => ['service', 'services', 'layanan', 'solusi', 'fitur layanan', 'keahlian', 'skills'],
            'features'     => ['feature', 'features', 'keunggulan', 'fitur', 'mengapa memilih'],
            'portfolio'    => ['portfolio', 'portofolio', 'proyek', 'project', 'karya', 'showcase'],
            'pricing'      => ['pricing', 'harga', 'paket', 'biaya', 'tarif', 'langganan'],
            'testimonials' => ['testimonial', 'testimonials', 'testimoni', 'ulasan', 'review', 'kata klien'],
            'faq'          => ['faq', 'tanya jawab', 'pertanyaan'],
            'contact'      => ['contact', 'kontak', 'hubungi', 'hubungi kami', 'kirim pesan', 'lokasi'],
        ];

        $used_ids = [];
        $sections = $root->getElementsByTagName('section');
        $sec_count = 0;

        foreach ($sections as $sec) {
            $sec_count++;
            $existing_id = trim((string)$sec->getAttribute('id'));
            $existing_cls = (string)$sec->getAttribute('class');

            if ($existing_id !== '') {
                $used_ids[$existing_id] = true;
                continue;
            }

            // Tebak ID terbaik berdasarkan teks heading atau isi teks section
            $sec_text = strtolower($sec->textContent);
            $detected_id = null;

            foreach ($section_keywords as $key => $keywords) {
                if (!isset($used_ids[$key])) {
                    foreach ($keywords as $kw) {
                        if (str_contains($sec_text, $kw)) {
                            $detected_id = $key;
                            break 2;
                        }
                    }
                }
            }

            if (!$detected_id) {
                $detected_id = "section-{$sec_count}";
            }

            $sec->setAttribute('id', $detected_id);
            $used_ids[$detected_id] = true;

            $semantic_cls = "{$detected_id}-section";
            if (!str_contains($existing_cls, $semantic_cls)) {
                $sec->setAttribute('class', trim($existing_cls . ' ' . $semantic_cls));
            }
        }

        // Sambungkan link <a> pada <nav> atau <header> ke ID section yang ada
        $all_as = $root->getElementsByTagName('a');
        foreach ($all_as as $a) {
            $href = trim((string)$a->getAttribute('href'));
            $action_type = trim((string)$a->getAttribute('data-action-type'));
            $action_target = trim((string)$a->getAttribute('data-action-target'));
            $txt = strtolower(trim((string)$a->textContent));

            if ($action_type === '' || $action_type === 'scroll') {
                if ($action_target === '' || $href === '#' || $href === '') {
                    foreach ($section_keywords as $key => $keywords) {
                        if (isset($used_ids[$key])) {
                            foreach ($keywords as $kw) {
                                if (str_contains($txt, $kw)) {
                                    $a->setAttribute('href', "#{$key}");
                                    $a->setAttribute('data-action-type', 'scroll');
                                    $a->setAttribute('data-action-target', $key);
                                    break 2;
                                }
                            }
                        }
                    }
                } elseif (str_starts_with($href, '#') && strlen($href) > 1 && $action_type === '') {
                    $target_key = substr($href, 1);
                    $a->setAttribute('data-action-type', 'scroll');
                    $a->setAttribute('data-action-target', $target_key);
                }
            }
        }

        $res = '';
        foreach ($root->childNodes as $child) {
            $res .= $doc->saveHTML($child);
        }
        return $res !== '' ? $res : $html;
    }
}

