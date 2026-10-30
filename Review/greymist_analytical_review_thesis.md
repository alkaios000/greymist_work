# Laporan Analisis Arsitektur Sistem: Greymist Framework

## ABSTRAK
Dokumen ini merupakan tinjauan analitis komprehensif tingkat lanjut (setara skripsi) terhadap platform *Greymist Framework (versi 1.1 - refactoring_1)*. Greymist adalah sebuah *platform web-builder* yang dirancang untuk merakit antarmuka grafis dalam bentuk kanvas bersistem "*frames*". Dokumen ini akan membedah secara menyeluruh setiap *layer* dari aplikasi: struktur basis data rasional, rancang bangun antarmuka, rekayasa interaktivitas *frontend* skala besar, dan *pipelines* komunikasi antar-server.

---

## BAB 1: PENDAHULUAN
### 1.1 Konsep Sistem
Greymist menggunakan arsitektur semi-*Single Page Application* (SPA). Alih-alih merender ulang antarmuka *builder* setiap ada perubahan data, sistem merender kerangka (HTML/CSS) pada fase rute peluncuran via PHP, lalu menyerahkan sepenuhnya status manajemen *(*state management*)* kepada mesin *JavaScript Runtime* melalui injeksi data *Global Object* (`window.GREYMIST`).

### 1.2 Stack Teknologi
1. **Representasional/View**: DOM (Document Object Model) HTML murni.
2. **Behavior/Logika Antarmuka**: JavaScript Terpisah (Modular ES5/ES6) yang membagi fungsi logis berdasarkan hirarki (event keyboard, klik kanan, render DOM).
3. **Komunikasi Eksternal**: Fetch API bawaan JS.
4. **Endpoint/Controller**: PHP murni yang beroperasi secara *stateless* pada folder tertentu merespons HTTP Request lokal dengan manipulasi Data JSON.
5. **Konfigurator/Model Data**: MySQL Database, dimanipulasi dengan menggunakan PDO (PHP Data Objects).

---

## BAB 2: ARSITEKTUR BASIS DATA RELASIONAL (RDBMS)
Sistem menggunakan pangkalan data `greymistbeta` dan tabel-tabel utamanya dibangun menggunakan `InnoDB` *engine* untuk memanfaatkan *foreign keys constraints* (seperti `ON DELETE CASCADE`).

### 2.1 Struktur Tabel Inti

#### 1. `gm_users` (Manajemen Pengguna)
Berfungsi sebagai akar hierarki.
- **`user_id`** (INT, Primary Key, Auto Increment)
- **`username`, `nickname`, `email`, `password`**, dll.
*Catatan*: Mengelola autentikasi dengan status waktu pelacakan (created_at).

#### 2. `gm_projects` (Entitas Proyek)
- **`project_id`** (INT, PK)
- **`user_id`** (INT, FK merujuk ke tabel `gm_users`)
- **`project_name`**, **`project_status`** (published/unpublished)
*Penjelasan*: Menyimpan kepala meta-data sebuah proyek yang dimiliki oleh pengguna.

#### 3. `gm_frames` (Entitas Kanvas / Halaman)
Tabel ini merupakan yang paling menanggung muatan sistem (Heavy Payload Table).
- **`frame_id`** (INT, PK)
- **`project_id`** (INT, FK merujuk ke tabel `gm_projects`)
- **`frame_name`** (VARCHAR)
- **`core_content`** (LONGTEXT) : Menyimpan kompilasi *stringified* DOM Tree HTML mentah (div, span, style attribut) dari hasil suntingan pengguna.
*Penjelasan*: Inilah letak mesin *builder* bekerja; semua elemen ditarik menjadi satu string panjang yang terus-menerus di *Patch/Update* via AJAX.

#### 4. `gm_colors` & `gm_bg_images` (Manajemen Aset)
- **`gm_colors`**: Menyimpan referensi Hex Code Palet (berelasi logis ke `project_id`) untuk sistem variabel UI.
- **`gm_bg_images`**: Memetakan letak *path* gambar per spesifik UID (`user_id`).

---

## BAB 3: ARSITEKTUR BACKEND (KONTROLER REST-LIKE API)
Sebagai ganti dari framework PHP besar (seperti Laravel atau CI), Greymist merakit modul API murni pada *routing* berkas berakhiran `.php` di dalam direktori `/api`.

| EndPoint (File Target) | Kegunaan Konseptual | Alur Logis Pemrosesan |
|-----------------------|---------------------|-----------------------|
| `save-frame.php`      | Menyinkronkan Kanvas| Menerima raw `php://input` JSON -> Verifikasi bahwa `frame_id` adalah milik sesi `user_id` -> `UPDATE core_content` pada `GM_FRAMES`. |
| `add_frame.php`       | Membentuk Kanvas    | INSERT parameter baru ke `GM_FRAMES` di bawah `project_id`. |
| `copy_frame.php`      | Kloning Kanvas      | `SELECT core_content` dari frame_id eksisting > `INSERT` kembali `core_content` tersebut menggunakan row ID baru di DB. |
| `get_frames.php`      | Mengakses Kanvas    | Memuati (Load) list object secara *on-demand*. |
| `get-images.php` dkk  | Interaksi Aset Lokal| Membaca isi tabel `GM_BG_IMAGES` dikemas dalam bentuk *array json objects*. |

> [!CAUTION] 
> **Krusial Keamanan**: Skrip dalam folder `api/` secara ketat memantau Session ID pengguna (`require '../includes/auth.php'`) maupun verifikasi parameter (`$_SERVER['REQUEST_METHOD'] !== 'POST'`). Pencegahan manipulasi via metode `prepare()` oleh PDO juga terlihat rapi di sini.

---

## BAB 4: ARSITEKTUR ANTARMUKA FRONTEND
### 4.1 Mekanisme Injeksi *State* (Initial State Load)
Saat user menekan satu *project* (`index.php?project_id=...`), PHP menjalankan *queries query* lalu menaruh nilainya ke dalam tag `<script> window.GREYMIST = { project: [..], frames: [..], colors: [..] } </script>`. 
Metode ini adalah replikasi klasik dari konsep yang kini disebut sistem manajemen *State Global* tanpa perlu *Fetching API* pada detik 0 pemuatan halaman, mengurangi latensi secara brutal.

### 4.2 Pemisahan Modul JavaScript (Decoupled JS Modules)
Analisis mendasar pada `script.html` yang merepresentasikan struktur di folder `assets/JS/`. Ini adalah pola pengkategorian kelas dan entitas yang sangat masif:

1. **/user_interface** (Domain UI/UX Khusus): 
   - Modul `aside/` (Operasi animasi navigasi kotak, panel ikon, dan form pencarian). 
   - Modul `main/` (Logika UI utama seperti persentase Zoom-in).
2. **/program_function** (Domain Logika Pengubahan Meta): 
   - Mekanisme Undo/Redo di Editor.
   - Pemicu fungsi shortcut *transparent*, *box-shadow*. 
3. **/object_define** (Domain Perilaku Element Builder): 
   - Mengendalikan spesifikasi penambahan/pencabutan DOM Node (`object-define.js`, `clone-object.js`).
4. **/object_behavior** (Behavior Event Listeners): 
   - Mengidentifikasi rekayasa *Keydown* secara sistematis (Seperti fungsi spesifik *Delete object*).
5. **/right_click_menu** (Rekayasa *Context Menu* Builder): 
   - Sistem cegat default window click (`menu-trigger.js`) diubah spesifik untuk melacak node `Select-parent` dan `copy-paste.js` (komprehensif untuk *Clipboard logic*).

### 4.3 Deklarasi Komponen (Folder `part/`)
Alih-alih menulis kode ke `index.php` sampai tembus belasan ribu kode HTML secara linier, platform ini memuat kerangka dasar (*Layout Base*) layaknya sistem Blade pada Laravel:
- `aside-left.html` (Panel Alat Kiri)
- `main.html` (Pusat Kanvas/Area Kerja)
- `aside-right.html` (Properti Inspektor Objek/CSS Editor Visual)
- `modal.html` (Formulir Notifikasi Universal)

---

## BAB 5: ALUR SISTEM TRANSAKSIONAL (USE CASE)
Berikut adalah gambaran sistem siklus dalam Greymist:

### 5.1 Siklus "Clone Public Project" (Cloning Ekosistem)
1. **Trigger**: Pengguna yang berstatus *Logged-In* membuka `dashboard.php`. Terdapat kueri untuk mengambil status "published" dari akun manapun (`LEFT JOIN gm_users`).
2. **Action**: Saat tombol `Clone` ditekan, sebuah *hidden form* melempar `POST`.
3. **Execution**: Skrip mengeksekusi duplikasi pada dua tingkat kedalaman Relasi Rekursif (Memindah entitas `project` menjadi status unpublished sekaligus me-*looping* duplikasi isi `gm_frames` ke proyek kloningan).

### 5.2 Siklus "Builder Canvas Autosave" (Sinkronisasi Tanpa Gangguan)
1. Perubahan dilakukan secara *native DOM* JavaScript oleh fungsi-fungsi di folder `/object_define/`.
2. Sebuah fungsi deteksi reaktif merekam isi aktual dari `#body` HTML.
3. Fungsi asinkron `fetch()` menembak parameter JSON `{'frame_id': id, 'content': raw_dom}` ke file `api/save-frame.php`.
4. *Backend Update*, sementara *Frontend* terus berfungsi mulus.

---

## KESIMPULAN
**Greymist Framework** merupakan suatu *masterpiece* berarsitektur prosedural *Native* yang menyamarkan dirinya menjadi antarmuka responsif tanpa *page reloading*. 
Arsitektur perikatan API *REST-Style*, Pemisahan Modul Direktori JavaScript berdasarkan Domain (*Behavior, Component, RightClick*), serta pemanfaatan teknik *Global JS Injection via PHP* menghadirkan interaksi kelas atas untuk editor *Drag&Drop/Content Builder* walau tanpa campur tangan bundler modern semisal React/Next.js. Kesan stabilitas transaksional juga diekspresikan dengan baik lewat validasi silang pada setiap baris berkas di direktori `/api`.
