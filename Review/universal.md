# UNIVERSAL INTEGRATED REVIEW: GREYMIST FRAMEWORK v1.1

<!-- *(Kompilasi Keseluruhan Dokumentasi Analitis, Spesifikasi Data, dan Rancang Bangun Database)* -->

---

## 💻 1. SPESIFIKASI DATA (SYSTEM SPEC & CODE LINE COUNT)

Data ini diambil dari laporan analyzer spesifikasi sistem (`spec/data.json` & `spec/output_php.json`):

```json
{
    "total_files": 205,
    "total_lines": 582710,
    "total_blank": 22648,
    "total_comments": 3101,
    "total_code": 556961,
    "by_extension": {
        "php": {
            "files": 44,
            "total": 10969,
            "code": 9355,
            "blank": 1313,
            "comment": 301
        },
        "css": {
            "files": 39,
            "total": 50942,
            "code": 34710,
            "blank": 15910,
            "comment": 322
        },
        "html": {
            "files": 47,
            "total": 16951,
            "code": 13293,
            "blank": 2986,
            "comment": 672
        },
        "js": {
            "files": 60,
            "total": 43307,
            "code": 39133,
            "blank": 2373,
            "comment": 1801
        },
        "json": {
            "files": 8,
            "total": 460206,
            "code": 460204,
            "blank": 2,
            "comment": 0
        }
    }
}
```

---

## 🗄️ 2. ARSITEKTUR DATABASE KOMPREHENSIF (`GREYMISTBETA`)

Database ini menggunakan nama **`GREYMISTBETA`** dengan pola arsitektur **Relational Database** yang menggunakan *Storage Engine* InnoDB (*Foreign Key Constraints*).

### 2.1 Entitas Induk (Hirarki Ke-1)

Kedua tabel ini bersifat *independent* dan berada di hirarki tertinggi.

* **`GM_USERS` (Tabel Pengguna Aplikasi)**: Menyimpan `user_id` (PK), `nickname`, `username` (Unique), `password`, `email`, `profile`, `created_at`, dan `last_login`.
* **`GM_ADMIN` (Tabel Administrator)**: Terpisah total dari `GM_USERS` untuk menghindari *privilege escalation*. Berisi `admin_id` (PK), `nickname`, `username`, `password`, dan `profile`. Tabel ini sepenuhnya terisolasi.

### 2.2 Entitas Aset & Data (Hirarki Ke-2)

Tabel-tabel di tingkat ini secara langsung berelasi (*Foreign Key*) kepada `GM_USERS`.

* **`GM_PROJECTS` (Berkas Proyek Editor)**: `project_id` (PK), `user_id` (FK), `project_name`, `thumbnail`, `created_at`.
* **`GM_BG_IMAGES` (Bank Gambar Pengguna)**: `image_id` (PK), `user_id` (FK), `image`, `path`. Karena gambar bersifat privasi, tabel ini merujuk asalnya ke akun pengunggah (`ON DELETE CASCADE`).

### 2.3 Entitas Engine / Komponen Editor (Hirarki Ke-3)

Tabel-tabel ini dimiliki eksklusif oleh `GM_PROJECTS`.

* **`GM_FRAMES` (Komponen Kanvas Utama)**: **Tabel Paling Vital** bagi Workspace Editor (Engine). `frame_id` (PK), `project_id` (FK), `frame_name`, **`core_content`** (`LONGTEXT` - ini menampung DOM Node Element secara keseluruhan), `created_at`.
* **`GM_COLORS` (Variabel Warna Tematik)**: `color_id` (PK), `project_id` (FK), `color_variable`.

### Peta Visual Relasi Struktur

```text
[ ADMIN_GLOBAL_ACCESS ] (GM_ADMIN)

GM_USERS (Akun Utama)
  │
  ├── GM_BG_IMAGES (Aset gambar-gambar upload user terikat disini)
  │
  └── GM_PROJECTS (1 User bisa punya banyak project)
        │
        ├── GM_COLORS (Penyimpanan library warna spesifik per-project)
        │
        └── GM_FRAMES (Penyimpanan hasil drag-n-drop DOM HTML per-project)
```

*(Skema Cascade SQL: Apabila `GM_USERS` atau `GM_PROJECTS` dihapus, seluruh kanvas, warna, aset proyek akan bersih total dari MySQL).*

---

## 👁️ 3. TINJAUAN ANALITIS: SISI ADMINISTRATOR (`/admin/`)

Area Administrator berada seluruhnya dalam kapsul lingkup lokal yang terisolasi di dalam direktori `admin/`. Ini adalah semacam Panel Sentris (*Control Center*) untuk moderasi konten dan pengguna.

* **Proteksi Skala Modul**: Ada autensikasi terpisah `admin/login.php` yang khusus membaca entitas jika di *cookie* memiliki flag khusus, diisolasi dari sesi user biasa.
* **Engine Telemetri (Dashboard)**: Melakukan komputasi visual total user versus total penambahan interval mingguan, komparasi proyek *published vs unpublished vs templates*.
* **CRUD User Akseleratif (`users.php`)**: Memungkinkan admin mereset password akun bermasalah secara *force replace* serta merubah *Role* dari User biasa menjadi Admin Pembantu.
* **Moderasi Konten Kanvas (`projects.php`)**: Melalui panel AJAX di layar Modal, Admin dapat mengunduh daftar *stringified DOM Tree* (`core_content`) sebagai mode **pratinjau real-time** tanpa mengganggu state editor asli milik user. Admin berkuasa penuh mengkategorikan karya pengguna menjadi *Template* atau mencabut publikasinya.

---

## 👥 4. TINJAUAN ANALITIS: SISI PENGGUNA (`/user/`)

Sistem Greymist merepresentasikan antarmuka kolektif pengguna.

* **Autentikasi (Sesi)**: Murni bergantung pada form pendataan `session/register.php` dan divalidasi melalui `includes/auth.php` yang menendang mundur bila sesi kosong.
* **Dashboard Kolektif Publik**: Halaman di mana desain ber-status `published` dari komunitas berkumpul. Terdapat logika unggulan **Siklus Kloning (Cloning Flow)** di mana dengan satu klik, sistem akan mengeruk basis data *core_content* dari Project asli dan menyisipkannya kembali menjadi hak milik pengguna dengan nama *Copy of...*.
* **Manajemen Ruang Kerja (`project.php`)**: Halaman kontrol personal pengguna terhadap semua karya mereka. Menekan tombol edit pada panel ini akan me-routing pengguna ke file `index.php?project_id=...` yang merupakan pusat *Engine*.

---

## 🛠️ 5. TINJAUAN ANALITIS: SISI WORKSPACE ENGINE (`/part`, `/assets`, `/index.php`)

Komponen paling buas dan kompleks. Ini adalah titik tumpu utama produk (*Premium Grade UI Editor*).

* **Arsitektur Single-Page Dom Injection Layer (SP-DIL)**: `/index.php` merender kanvas lewat array Javascript (`window.GREYMIST`) menggunakan fungsi `JSON_UNESCAPED_UNICODE` di PHP. Lalu, modul HTML terlisahkan seperti `aside-left.html` (DOM Tree panel), `main.html` (Kanvas), `aside-right.html` (Inspektur Properti) di injek.
* **Manajemen State Logika (No-Framework / Vanilla)**: Berpusat pada `assets/JS/`. Terdapat puluhan ribu baris kode modular. Dipecah berdasarkan *domain*:
  * `/object_define`: Instansi pembentuk DOM *drag and drop*.
  * `/program_function`: Otak rekam komputasi seperti *Undo/Redo Canvas* dan styling CSS Native *Cascading Variable Injection*.
  * `/right_click_menu`: Mencegat klik kanan default browser dan membikin *Context Menu custom* berisikan opsi hirarki Layer (Parent-Child tracking).
* **Transaksi Data Asinkronus (REST-Lite)**: Skrip dalam profil `/api/` (seperti `api/save-frame.php`) menggunakan *Fetch API* secara pasif merangkum dom node yang diperbarui menjadikannya `url-stringencoded payloads` yang diPOST secara repetitif ke server tanpa *page-reload* sama sekali.

---

## 📜 6. KESIMPULAN AKHIR (LEVEL THESIS / ARSITEKTUR)

**Greymist Framework v1.1** adalah mahakarya rekayasa Arsitektural Prosedural Native. Aplikasi ini menjembatani dua kutub:

1. Skalabilitas Server-Side klasik (PHP/MySQL) untuk rendering rute awal yang cepat, autensikasi yang anti-rentan, serta sinkronisasi direktori *Cascade Relational* (pembersihan mandiri data tak terpakai).
2. Refleksi Fluiditas ala *Single-Page-Application (SPA)* untuk Area Editor (Workspace Builder). Penggunaan `Vanilla JS` yang terdisiplin melalui segmentasi foldering menghapuskan kelemahan performa yang biasa timbul apabila mengawinkan React/DOM Diffing berlebih pada ekosistem drag-and-drop berlapis ganda.

<!-- *(Arsitektur MVC yang didekopel (View di HTML Modules, Controller Logika di API PHP, dan Object-State di Window Runtime JS) mengizinkan platform ini memanjat fase maintenance berkelanjutan ke skala yang lebih enterprise.)* -->

---
**Document**: `universal.md` - *Automated Compilation Module.*
