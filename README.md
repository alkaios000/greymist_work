# Greymist — Web Design & Visual Workspace Platform

Greymist adalah platform visual web builder dan kolaborasi desain berbasis PHP Native dengan arsitektur **MVC-Hybrid + Front Controller** yang mengutamakan keamanan, modularitas, dan antarmuka bersih (Pure White Workspace & Soft Blue Accent).

---

## Arsitektur Direktori (`snake_case`)

```text
Greymist_work/
├── .env                        # Variabel environment lokal (diabaikan oleh Git)
├── .env.example                # Template konfigurasi environment
├── .gitignore                  # Daftar pengecualian Git
├── .htaccess                   # Proteksi direktori & konfigurasi routing Apache
├── index.php                   # Front Controller utama (?page=... & ?action=...)
├── README.md                   # Dokumentasi proyek & panduan instalasi
│
├── config/                     # Konfigurasi sistem & koneksi PDO
│   ├── config.php              # Loader .env, koneksi PDO, session, & konstanta global
│   └── settings.json           # Pengaturan dinamis platform (admin settings)
│
├── database/                   # Skema, migrasi, & data awal database
│   ├── database.sql            # Master dump SQL lengkap
│   ├── migrate.php             # Script eksekutor migrasi database otomatis
│   ├── migrations/             # File perubahan tabel bertahap
│   │   ├── 01_initial_schema.sql
│   │   └── 02_add_color_and_component_columns.sql
│   └── seeders/                # Script pengisian data awal (dummy/admin)
│       └── seed_users.php
│
├── helpers/                    # Fungsi pembantu global (reusable)
│   ├── security_helper.php     # Sanitasi XSS e() & manajemen token CSRF
│   ├── url_helper.php          # Pembuat URL rute, asset, & fungsi redirect
│   ├── date_helper.php         # Pemformatan tanggal & waktu
│   └── upload_helper.php       # Validasi & pemindahan file upload aman
│
├── middlewares/                # Lapisan pemeriksaan izin sebelum akses fitur
│   ├── auth_middleware.php     # Pemeriksaan status login pengguna
│   ├── role_middleware.php     # Pemeriksaan hak akses administrator
│   └── csrf_middleware.php     # Validasi token CSRF pada request POST
│
├── models/                     # Lapisan interaksi data (PDO Prepared Statements)
│   ├── user_model.php          # Query tabel gm_users
│   ├── project_model.php       # Query tabel gm_projects & gm_colors
│   ├── frame_model.php         # Query tabel gm_frames
│   ├── component_model.php     # Query tabel gm_components
│   └── asset_model.php         # Query tabel gm_bg_images
│
├── app/
│   └── controllers/            # Pemroses aksi form & endpoint API JSON
│       ├── auth_action.php     # Login, registrasi, logout, hapus akun
│       ├── project_action.php  # CRUD proyek, duplikasi, token warna, background
│       ├── frame_action.php    # CRUD frame & penyimpanan canvas core
│       ├── component_action.php# CRUD komponen kustom pengguna
│       ├── asset_action.php    # Upload & manajemen gambar aset/background
│       ├── profile_action.php  # Pembaruan profil, password, & foto profil
│       └── admin_action.php    # Aksi manajemen admin (pengguna, proyek, pengaturan)
│
├── views/                      # Lapisan tampilan antarmuka (HTML + PHP bersih tanpa SQL)
│   ├── layouts/                # Komponen tata letak bersama
│   │   ├── header.php
│   │   ├── sidebar.php
│   │   ├── footer.php
│   │   └── modal_konfirmasi.php
│   ├── pages/                  # Halaman utama aplikasi (User, Auth, Workspace, Admin)
│   └── errors/                 # Halaman penanganan error HTTP (403, 404, 500)
│
├── assets/                     # File statis (CSS, JS, gambar, ikon)
│   ├── css/                    # Stylesheet modular (base, layout, scrollbar, components, modules)
│   ├── js/                     # Script modular (main, components, modules)
│   └── images/                 # Aset gambar statis, logo, & ilustrasi
│
└── uploads/                    # Penyimpanan file unggahan pengguna (non-executable)
    ├── foto_profil/
    ├── background/
    ├── assets/
    └── lampiran/
```

---

## Panduan Instalasi

1. **Persyaratan Sistem:**
   - PHP >= 8.0 dengan ekstensi `pdo_mysql`, `mbstring`, `json`, dan `fileinfo`.
   - MySQL / MariaDB (Laragon / XAMPP).

2. **Konfigurasi Environment:**
   - Salin `.env.example` menjadi `.env`.
   - Sesuaikan `DB_HOST`, `DB_NAME` (`GREYMISTBETA`), `DB_USER`, dan `DB_PASS`.

3. **Inisialisasi Database:**
   - Impor `database/database.sql` ke MySQL, atau jalankan migrasi otomatis melalui CLI:
     ```powershell
     php database/migrate.php
     ```

4. **Menjalankan Aplikasi:**
   - Akses melalui Laragon (`http://localhost/Greymist_work/` atau virtual host `http://greymist_work.test/`).
   - Semua rute halaman ditangani melalui `index.php?page=...` dan aksi melalui `index.php?action=...`.

