<?php
/**
 * File: app/controllers/ai/ai_prompt_parser.php
 * Modul: Sistem Prompt AI Greymist dan Parser Output AI.
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
                . "6. ATURAN SEMANTIK TABEL VS DIV (WAJIB AKURAT & TEPAT):\n"
                . "   - KAPAN WAJIB <TABLE>: Jika prompt, teks referensi yang dipaste, atau FOTO/GAMBAR yang diunggah memuat DATA TABULAR, JADWAL (SCHEDULE), MATRIKS PERBANDINGAN FITUR/HARGA, INVOICE, LOG TRANSAKSI, atau LAPORAN BERKOLOM-BARIS:\n"
                . "     * DILARANG KERAS mensimulasikan data tabel menggunakan tumpukan <div> flexbox/grid!\n"
                . "     * WAJIB menggunakan tag semantik tabel murni: <table class=\"gm-object\">, <thead class=\"gm-object\">, <tbody class=\"gm-object\">, <tr class=\"gm-object\">, <th class=\"gm-object\">, <td class=\"gm-object\">.\n"
                . "     * WAJIB bungkus <table> di dalam container responsif: <div class=\"gm-object\" style=\"width:100%;overflow-x:auto;box-sizing:border-box;\"> agar tabel dapat di-scroll horizontal secara mulus di mobile tanpa merusak kanvas!\n"
                . "     * Styling tabel: width:100%;border-collapse:collapse;text-align:left; th berbackground lembut padding 12px 16px, td berborder-bottom tipis 1px solid #e2e8f0 padding 12px 16px.\n"
                . "   - KAPAN MENGGUNAKAN <DIV>: Gunakan <div> HANYA untuk layout container, flex/grid wrapper kartu, dan visual styling box. DILARANG menggunakan <table> untuk tata letak halaman (layouting)!\n"
                . "   - TAG SEMANTIK TERBAIK: Navigasi=<nav>, bagian bab=<section>, tombol=<button>, link/goto=<a>, formulir=<form>/<label>/<input>/<select>, teks hierarki=<h1>-<h6>/<p>/<span>.\n"
                . "7. ATURAN WAJIB ID, CLASS SEMANTIK & ONE-PAGE SMOOTH SCROLL NAVIGATION:\n"
                . "   - Saat membangun landing page (halaman tunggal), SETIAP <section> WAJIB memiliki ID dan class semantik yang unik & deskriptif:\n"
                . "     Contoh: <section id=\"hero\" class=\"gm-object hero-section\">, <section id=\"about\" class=\"gm-object about-section\">, <section id=\"services\" class=\"gm-object services-section\">, <section id=\"portfolio\" class=\"gm-object portfolio-section\">, <section id=\"pricing\" class=\"gm-object pricing-section\">, <section id=\"testimonials\" class=\"gm-object testimonials-section\">, <section id=\"contact\" class=\"gm-object contact-section\">, <footer id=\"footer\" class=\"gm-object footer-section\">.\n"
                . "   - SETIAP LINK NAVIGASI DI NAVBAR (<nav>) WAJIB TERHUBUNG KE ID TERSEBUT dengan format:\n"
                . "     <a class=\"gm-object nav-link\" href=\"#about\" data-action-type=\"scroll\" data-action-target=\"about\">About</a>\n"
                . "     <a class=\"gm-object nav-link\" href=\"#services\" data-action-type=\"scroll\" data-action-target=\"services\">Services</a>\n"
                . "     <a class=\"gm-object nav-link\" href=\"#pricing\" data-action-type=\"scroll\" data-action-target=\"pricing\">Pricing</a>\n"
                . "     <a class=\"gm-object nav-link\" href=\"#contact\" data-action-type=\"scroll\" data-action-target=\"contact\">Contact</a>\n"
                . "   - Tombol CTA (Call to Action) di Hero atau Navbar juga diarahkan dengan data-action-type=\"scroll\":\n"
                . "     <button class=\"gm-object\" data-action-type=\"scroll\" data-action-target=\"contact\">Hubungi Kami</button> atau <a class=\"gm-object\" href=\"#pricing\" data-action-type=\"scroll\" data-action-target=\"pricing\">Lihat Paket</a>.\n"
                . "   - DILARANG membiarkan href=\"#\" kosong tanpa ID target!\n"
                . "8. TARGET MULTI-CARD SIBLINGS & ANTI-ROOT ANIMASI:\n"
                . "   - Saat pengguna meminta animasi pada card/kartu secara umum ('animasikan card', 'semua card kasih slide-up'), WAJIB menganimasikan SELURUH kartu daun saudara (siblings) dalam kontainer dengan staggered delay (0s, 0.15s, 0.3s, dst.)!\n"
                . "   - DILARANG hanya menganimasikan first-child!\n"
                . "   - DILARANG menganimasikan root <section> atau #object-core!\n"
                . "   - 7 Class animasi resmi Greymist: gm-anim-fade-in, gm-anim-slide-up, gm-anim-slide-down, gm-anim-slide-left, gm-anim-slide-right, gm-anim-zoom-in, gm-anim-bounce-in.\n"
                . "9. EDIT ATRIBUT / GAYA IN-PLACE (JANGAN BUAT DARI NOL):\n"
                . "   Jika pengguna meminta mengubah/menambah/menghapus atribut (warna, font, ukuran, border, animasi, text) pada komponen yang sudah ada, kembalikan HANYA format JSON:\n"
                . "   <!--GM_OP:PATCH_ATTRIBUTES-->\n"
                . "   {\"target\":\"self\",\"target_all\":true,\"stagger\":0.15,\"classes_to_add\":[\"gm-anim-base\",\"gm-anim-slide-up\"],\"classes_to_remove\":[\"gm-anim-fade-in\"],\"attributes\":{\"data-gm-anim\":\"gm-anim-slide-up\",\"data-gm-anim-dur\":\"0.8\",\"data-gm-anim-del\":\"0\",\"data-gm-anim-loop\":\"1\"},\"styles\":{\"--gm-anim-duration\":\"0.8s\",\"--gm-anim-delay\":\"0s\"},\"summary\":\"Ringkasan perubahan.\"}\n"
                . "10. FORMAT OPERASI LAINNYA:\n"
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
            . "   Jika gambar / screenshot referensi disertakan atau dipaste:\n"
            . "   - Analisis tata letak (wireframe/layout), hierarki visual, jenis komponen (hero, navbar, cards, pricing, testimonial, form, footer, tabel data), warna, dan tipografi dari gambar referensi tersebut.\n"
            . "   - DETEKSI SEMANTIK PRESISI (TABEL VS KARTU/DIV DARI GAMBAR):\n"
            . "     * Jika pada gambar tampak TABEL DATA, JADWAL, MATRIKS PERBANDINGAN FITUR/HARGA, DAFTAR TRANSAKSI, atau INVOICE: AI WAJIB menghasilkan tag semantik <table class=\"gm-object\"> murni lengkap dengan <thead>, <tbody>, <tr>, <th>, <td> dalam wrapper responsif <div class=\"gm-object\" style=\"overflow-x: auto; width: 100%;\">. DILARANG KERAS mengubah tabel di foto menjadi tumpukan <div>!\n"
            . "     * Jika pada gambar tampak KARTU (CARDS), HERO SECTION, atau GRID VISUAL: gunakan tag semantik <section> dan <div> flexbox/grid. DILARANG menggunakan <table> untuk tata letak kartu!\n"
            . "   - Konversikan setiap elemen visual dari gambar menjadi kode HTML semantik Greymist dengan class .gm-object yang meniru estetika dan layout referensi tersebut secara presisi.\n"
            . "   - Gunakan ikon Font Awesome yang relevan sesuai konten pada foto. Pastikan responsif dan bebas emoji.\n\n"
            . "13. ATURAN SEMANTIK HTML5 & KETEPATAN PENGGUNAAN TABEL (TABLE VS DIV):\n"
            . "   AI WAJIB membedakan secara presisi mana elemen yang harus menggunakan <table> dan mana yang menggunakan <div>:\n"
            . "   A. KAPAN WAJIB MENGGUNAKAN <TABLE> (DILARANG MENGGUNAKAN DIV):\n"
            . "      - Konten berupa DATA TABULAR: Jadwal kegiatan (schedule), perbandingan harga/fitur (feature comparison matrix), invoice/faktur, riwayat transaksi/order log, daftar inventaris/pengguna, laporan keuangan, dan data berbaris-kolom.\n"
            . "      - DILARANG KERAS mensimulasikan data tabel menggunakan rentetan <div> flexbox atau css grid!\n"
            . "      - WAJIB gunakan struktur tabel semantik murni: <table class=\"gm-object\">, <thead class=\"gm-object\">, <tbody class=\"gm-object\">, <tr class=\"gm-object\">, <th class=\"gm-object\">, <td class=\"gm-object\">.\n"
            . "      - WAJIB BUNGKUS DALAM CONTAINER RESPONSIF: Selalu bungkus <table> di dalam <div class=\"gm-object\" style=\"width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; box-sizing: border-box;\"> agar tabel dapat di-scroll horizontal secara mulus di layar HP (Mobile) tanpa memperlebar kanvas utama!\n"
            . "      - Standar Desain Tabel Modern: width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; <th> diberi background lembut (#f8fafc), padding 12px 16px, border-bottom 2px solid #e2e8f0, font-weight 600; <td> diberi padding 12px 16px, border-bottom 1px solid #f1f5f9; setiap baris <tr> rapi.\n"
            . "   B. KAPAN WAJIB MENGGUNAKAN <DIV> (BUKAN TABLE):\n"
            . "      - Gunakan <div> untuk layout container umum, flex/grid wrapper kartu (card grid), hero section, visual flexbox rows, box bayangan, dan popup modal.\n"
            . "      - DILARANG KERAS menggunakan <table> untuk tata letak halaman (layouting)!\n"
            . "   C. KETEPATAN SEMANTIK TAG HTML5 LAINNYA:\n"
            . "      - Navigasi: <nav class=\"gm-object\"> (bukan <div>).\n"
            . "      - Section Bab: <section class=\"gm-object\"> dengan id semantik (id=\"hero\", id=\"pricing\", id=\"schedule\", id=\"contact\").\n"
            . "      - Tombol Aksi: <button class=\"gm-object\"> (DILARANG <div onclick> atau <span> untuk tombol interaktif).\n"
            . "      - Tautan / Navigasi: <a class=\"gm-object\" data-action-type=\"...\">.\n"
            . "      - Formulir: <form class=\"gm-object\">, <label class=\"gm-object\">, <input class=\"gm-object\">, <select class=\"gm-object\">, <textarea class=\"gm-object\">.\n"
            . "      - Judul: <h1> s/d <h6> untuk hierarki heading (DILARANG <div> dengan font tebal untuk judul utama).\n\n"
            . "11. ATURAN KHUSUS WEB PRESENTATION / PPT SLIDES (MULTI-FRAME 100vw x 100vh FULL-SCREEN SLIDES):\n"
            . "   Jika pengguna meminta pembuatan Presentasi / PPT Web / Slides presentasi menjadi beberapa halaman atau frame terpisah (misal: 'bikin PPT', 'slide presentasi', 'masing-masing slide jadi frame/page'):\n"
            . "   A. WAJIB FULL-SCREEN MEMENUHI KANVAS (100% WIDTH x 100vh HEIGHT):\n"
            . "      - DILARANG KERAS membatasi slide menjadi kartu kecil mengambang di tengah (DILARANG max-width: 750px/900px atau min-height: 540px pada container slide)!\n"
            . "      - Kontainer slide terluar WAJIB memenuhi 100% layar kanvas: style=\"display: flex; flex-direction: column; justify-content: space-between; width: 100%; min-width: 100%; height: 100vh; min-height: 100vh; padding: clamp(36px, 4vh, 60px) clamp(48px, 5vw, 96px); box-sizing: border-box; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); margin: 0; position: relative; border-radius: 0; border: none; box-shadow: none;\".\n"
            . "      - Kontainer isi tengah (.slide-content): style=\"flex: 1; display: flex; flex-direction: column; justify-content: center; width: 100%; max-width: 1280px; margin: 0 auto; box-sizing: border-box;\".\n"
            . "   B. DILARANG KERAS MENGGUNAKAN `display: none;` PADA SLIDE MANA PUN (BAIK SLIDE 1 MAUPUN SLIDE 2 S/D SLIDE TERAKHIR)!\n"
            . "      - Di Greymist, setiap slide dibuat menjadi Frame/Page mandiri yang dikelola oleh workspace. Seluruh slide WAJIB berstatus visible.\n"
            . "   C. NAVIGASI SLIDE WAJIB BISA PINDAH-PINDAH (INTERAKTIF & LEBAR):\n"
            . "      Di bagian bawah setiap slide, WAJIB sertakan kontainer navigasi terpadu selebar slide:\n"
            . "      <div class=\"gm-object slide-navigation\" style=\"display: flex; align-items: center; justify-content: space-between; width: 100%; max-width: 1280px; margin: 0 auto; padding-top: 20px; border-top: 1px solid rgba(0,0,0,0.08); box-sizing: border-box; flex-shrink: 0;\">\n"
            . "        - Jika bukan slide pertama: <button class=\"gm-object gm-slide-nav-btn\" data-action-type=\"goto\" data-action-target=\"Slide {N-1}\" style=\"padding: 10px 22px; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.04);\"><i class=\"fa-solid fa-arrow-left gm-object\"></i> Sebelumnya</button>\n"
            . "        - Indikator: <span class=\"gm-object\" style=\"font-size: 14px; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 6px 18px; border-radius: 30px;\">Slide {N} / {Total}</span>\n"
            . "        - Jika bukan slide terakhir: <button class=\"gm-object gm-slide-nav-btn\" data-action-type=\"goto\" data-action-target=\"Slide {N+1}\" style=\"padding: 10px 24px; background: #0078ff; color: #ffffff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,120,255,0.28);\">Selanjutnya <i class=\"fa-solid fa-arrow-right gm-object\"></i></button>\n"
            . "      </div>\n"
            . "   D. KONTEN PRESENTASI BERSIH & BERBOBOT:\n"
            . "      - Format pembungkus: <!--GM_FRAME:Slide 1--> ... <!--GM_FRAME:Slide 2--> ... dst.\n"
            . "      - Tipografi besar jelas (H1/H2 32px-48px, body 15px-17px), kartu poin lega (3-4 kolom auto-fit minmax(280px, 1fr)), dan ikon Font Awesome (<i class=\"fa-solid fa-...\"></i>). DILARANG emotikon.\n\n"
            . "12. ATURAN WAJIB ID, CLASS SEMANTIK & ONE-PAGE SMOOTH SCROLL NAVIGATION:\n"
            . "    - Pada Landing Page 1 Halaman (Single-Page Website), SETIAP <section> WAJIB diberi atribut `id` dan `class` semantik yang jelas dan spesifik (huruf kecil, tanpa spasi):\n"
            . "      * Hero: <section id=\"hero\" class=\"gm-object hero-section\">\n"
            . "      * About / Tentang: <section id=\"about\" class=\"gm-object about-section\">\n"
            . "      * Services / Layanan: <section id=\"services\" class=\"gm-object services-section\">\n"
            . "      * Features / Fitur: <section id=\"features\" class=\"gm-object features-section\">\n"
            . "      * Portfolio / Portofolio / Karya: <section id=\"portfolio\" class=\"gm-object portfolio-section\">\n"
            . "      * Pricing / Harga: <section id=\"pricing\" class=\"gm-object pricing-section\">\n"
            . "      * Testimonials / Ulasan: <section id=\"testimonials\" class=\"gm-object testimonials-section\">\n"
            . "      * FAQ / Tanya Jawab: <section id=\"faq\" class=\"gm-object faq-section\">\n"
            . "      * Contact / Kontak: <section id=\"contact\" class=\"gm-object contact-section\">\n"
            . "      * Footer: <footer id=\"footer\" class=\"gm-object footer-section\">\n"
            . "    - SEMUA LINK MENU NAVBAR (<nav>) WAJIB DITAUTKAN SECARA PRESISI:\n"
            . "      Gunakan atribut ganda: `href=\"#{id}\"` DAN `data-action-type=\"scroll\" data-action-target=\"{id}\"`.\n"
            . "      Contoh Navbar Menu:\n"
            . "      <a class=\"gm-object\" href=\"#about\" data-action-type=\"scroll\" data-action-target=\"about\">Tentang Kami</a>\n"
            . "      <a class=\"gm-object\" href=\"#services\" data-action-type=\"scroll\" data-action-target=\"services\">Layanan</a>\n"
            . "      <a class=\"gm-object\" href=\"#portfolio\" data-action-type=\"scroll\" data-action-target=\"portfolio\">Portofolio</a>\n"
            . "      <a class=\"gm-object\" href=\"#pricing\" data-action-type=\"scroll\" data-action-target=\"pricing\">Paket Harga</a>\n"
            . "      <a class=\"gm-object\" href=\"#contact\" data-action-type=\"scroll\" data-action-target=\"contact\">Hubungi Kami</a>\n"
            . "    - Tombol CTA Utama (misal di Hero / Banner) juga harus diarahkan ke section tujuan (misal ke #contact atau #pricing) dengan data-action-type=\"scroll\".\n"
            . "    - DILARANG KERAS menghasilkan tag <section> tanpa id atau membiarkan href=\"#\" kosong!\n\n"
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
        $reference_images = null
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
        $images_list = [];
        if (!empty($reference_images)) {
            if (isset($reference_images['data'])) {
                $images_list = [$reference_images];
            } elseif (is_array($reference_images)) {
                $images_list = $reference_images;
            }
        }

        if (!empty($images_list)) {
            $img_count = count($images_list);
            if ($img_count === 1) {
                $context_input .= "[1 FOTO / SCREENSHOT REFERENSI VISUAL DISERTAKAN]:\n"
                    . "Gambar referensi desain UI terlampir. Analisis tata letak, hierarki komponen, tipografi, warna, dan estetika visualnya secara teliti.\n";
            } else {
                $context_input .= "[{$img_count} FOTO / SCREENSHOT REFERENSI VISUAL DISERTAKAN]:\n"
                    . "Terdapat {$img_count} foto referensi desain UI terlampir (Foto 1 s/d Foto {$img_count}).\n"
                    . "Pelajari seluruh foto referensi ini secara menyeluruh. Jika foto-foto tersebut menunjukkan bagian halaman yang berbeda (misalnya Foto 1 = Header/Hero, Foto 2 = Tabel Data / Grid Fitur, Foto 3 = Footer), kombinasikan seluruhnya secara harmonis menjadi satu kesatuan halaman yang utuh dan menyatu.\n";
            }
            $context_input .= "PERHATIAN DETEKSI SEMANTIK TABEL VS DIV DARI FOTO REFERENSI:\n"
                . "1. JIKA DI GAMBAR TERLIHAT TABEL DATA, JADWAL (SCHEDULE), MATRIKS PERBANDINGAN FITUR/HARGA, INVOICE, ATAU DAFTAR BERKOLOM-BARIS:\n"
                . "   - WAJIB konversikan menjadi tag semantik murni: <table class=\"gm-object\">, <thead class=\"gm-object\">, <tbody class=\"gm-object\">, <tr class=\"gm-object\">, <th class=\"gm-object\">, <td class=\"gm-object\">.\n"
                . "   - WAJIB bungkus di dalam container responsif: <div class=\"gm-object\" style=\"width:100%;overflow-x:auto;box-sizing:border-box;\"> agar dapat di-scroll horizontal di mobile.\n"
                . "   - DILARANG KERAS mengubah tabel di gambar menjadi deretan <div> kartu!\n"
                . "2. JIKA DI GAMBAR TERLIHAT KARTU (CARDS), GRID FITUR, HERO BANNER, ATAU KONTEN VISUAL:\n"
                . "   - Gunakan <section class=\"gm-object\"> dan <div class=\"gm-object\"> flexbox/grid yang responsif.\n"
                . "   - DILARANG menggunakan <table> untuk tata letak kartu/hero!\n"
                . "Konversikan setiap elemen visual dari gambar menjadi kode UI .gm-object yang meniru referensi tersebut secara presisi dan bersemantik tinggi.\n\n";
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

                // Proteksi mutlak: frame atau slide DILARANG memiliki display: none pada kontainer utamanya!
                if (preg_match('/style="[^"]*display:\s*none;?[^"]*"/i', $f_html)) {
                    $f_html = preg_replace('/style="([^"]*)display:\s*none;?([^"]*)"/i', 'style="$1display: flex; flex-direction: column; justify-content: center; min-height: 540px; padding: 36px 24px; box-sizing: border-box; width: 100%;$2"', $f_html);
                }

                // Auto-suntik navigasi slide jika merupakan presentasi dan belum memiliki navigasi
                $is_slide = preg_match('/slide\s*\d*/i', $f_name) || str_contains($f_html, 'slide-container');
                if ($is_slide && !str_contains($f_html, 'slide-navigation') && !str_contains($f_html, 'data-action-type="goto"')) {
                    $curr_idx = $i + 1;
                    $prev_name = ($curr_idx > 1) ? (trim($frame_matches[1][$i - 1][0]) ?: "Slide " . ($curr_idx - 1)) : "";
                    $next_name = ($curr_idx < $count_matches) ? (trim($frame_matches[1][$i + 1][0]) ?: "Slide " . ($curr_idx + 1)) : "";

                    $prev_btn = $curr_idx > 1
                        ? '<button class="gm-object gm-slide-nav-btn" data-action-type="goto" data-action-target="' . htmlspecialchars($prev_name) . '" style="padding: 9px 18px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"><i class="fa-solid fa-arrow-left gm-object"></i> Sebelumnya</button>'
                        : '<div class="gm-object" style="width: 110px;"></div>';

                    $next_btn = $curr_idx < $count_matches
                        ? '<button class="gm-object gm-slide-nav-btn" data-action-type="goto" data-action-target="' . htmlspecialchars($next_name) . '" style="padding: 9px 20px; background: #0078ff; color: #ffffff; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 6px rgba(0,120,255,0.25); transition: all 0.2s;">Selanjutnya <i class="fa-solid fa-arrow-right gm-object"></i></button>'
                        : '<div class="gm-object" style="width: 110px;"></div>';

                    $nav_box = '
    <div class="gm-object slide-navigation" style="display: flex; align-items: center; justify-content: space-between; margin-top: 36px; padding-top: 18px; border-top: 1px solid #e2e8f0; width: 100%; max-width: 800px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
      ' . $prev_btn . '
      <div class="gm-object" style="text-align: center;">
        <span class="gm-object" style="font-size: 13px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 4px 14px; border-radius: 20px;">Slide ' . $curr_idx . ' / ' . $count_matches . '</span>
      </div>
      ' . $next_btn . '
    </div>';

                    $last_div = strrpos($f_html, '</div>');
                    if ($last_div !== false) {
                        $f_html = substr_replace($f_html, $nav_box . "\n</div>", $last_div, 6);
                    } else {
                        $f_html .= $nav_box;
                    }
                }

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

        // 1. Ekstrak perintah ganti nama proyek terlebih dahulu (jika ada)
        // sehingga jika AI sekaligus me-rename proyek DAN membuat komponen UI, keduanya tereksekusi!
        $renamed_project = null;
        if (preg_match('/<!--\s*GM_OP\s*:\s*RENAME_PROJECT\s*:\s*(.*?)\s*-->/i', $trimmed_out, $rnp_match)) {
            $new_proj_name = trim($rnp_match[1], " \t\n\r\0\x0B\"'.,");
            $trimmed_out   = trim(str_replace($rnp_match[0], '', $trimmed_out));
            if ($new_proj_name !== '') {
                $renamed_project = [
                    'new_name' => $new_proj_name,
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
                    'renamed_project'    => $renamed_project,
                    'summary'            => $patch_data['summary'] ?? 'Atribut elemen berhasil diperbarui.',
                    'breakpoints_to_add' => $breakpoints_to_add,
                    'html'               => '',
                ];
            }
        } elseif (str_contains($trimmed_out, '<!--GM_OP:EXPLAIN-->')) {
            $operation   = 'explain';
            $trimmed_out = trim(str_replace('<!--GM_OP:EXPLAIN-->', '', $trimmed_out));
            return [
                'success'            => true,
                'used_model'         => $candidate_model,
                'operation'          => 'explain',
                'renamed_project'    => $renamed_project,
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

        // Unpack jika AI membungkus komponen dalam <div id="object-core"> redundant
        if (preg_match('#^<div\s+[^>]*\bid=["\']object-core["\'][^>]*>(.*)</div>$#is', $trimmed_out, $oc_match)) {
            $trimmed_out = trim($oc_match[1]);
        }

        // Cek apakah ada elemen HTML valid di dalam $trimmed_out
        $has_html = (str_contains($trimmed_out, '<') && preg_match('/<(?:div|section|nav|header|footer|main|article|table|form|button|h[1-6]|p|a|span|ul|ol|img|figure)\b/i', $trimmed_out));

        if (!$has_html && $renamed_project !== null) {
            // Hanya operasi ganti nama proyek murni (tanpa pembuatan elemen HTML)
            $summary_text = "Nama proyek berhasil diubah menjadi \"{$renamed_project['new_name']}\".";
            $clean_extra = trim(strip_tags($trimmed_out));
            if ($clean_extra !== '' && !str_starts_with($clean_extra, '<!--')) {
                $summary_text .= " " . $clean_extra;
            }
            return [
                'success'         => true,
                'used_model'      => $candidate_model,
                'operation'       => 'rename_project',
                'renamed_project' => $renamed_project,
                'summary'         => $summary_text,
                'html'            => $current_frame_html,
            ];
        }

        $summary_text = null;
        if ($renamed_project !== null) {
            $summary_text = "Nama proyek berhasil diubah menjadi \"{$renamed_project['new_name']}\", dan desain berhasil dibangun.";
        }

        return [
            'success'            => true,
            'used_model'         => $candidate_model,
            'operation'          => $operation,
            'renamed_project'    => $renamed_project,
            'summary'            => $summary_text,
            'breakpoints_to_add' => $breakpoints_to_add,
            'html'               => $trimmed_out !== '' ? ensure_gm_object_class($trimmed_out) : '',
        ];
    }
}