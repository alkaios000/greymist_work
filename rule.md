# Root Folder

* Standar Kerangka Arsitektur (Boilerplate Architecture)
> Sistem mengadopsi pola arsitektur **Modern Native (Front Controller & Clean MVC-Hybrid)** yang modular, terisolasi, aman, dan mudah diterapkan ulang (*reusable*) untuk berbagai sistem web. Seluruh permintaan pengguna (*request*) dipusatkan pada satu berkas entry point di root, sementara logika controller, model data, antarmuka tampilan, middleware keamanan, helper utilitas, dan aset statis dipisahkan secara tegas (*Separation of Concerns*).

* Pohon Kerangka Direktori Standar (Directory Skeleton Tree)
```
<root_project>/
├── .env.example                     # Template variabel lingkungan (database, app key, base url)
├── .env                             # Kredensial rahasia & konfigurasi lokal (JANGAN di-commit ke Git)
├── .gitignore                       # Berkas pengecualian Git (.env, uploads lokal, cache)
├── .htaccess                        # Aturan Apache (proteksi direktori, routing index.php, keamanan header)
├── index.php                        # SINGLE ENTRY POINT (Front Controller tunggal sistem)
├── README.md                        # Dokumentasi resmi proyek, panduan instalasi & spesifikasi
├── rule.md                          # Standar tata kelola, arsitektur folder, konvensi kode & keamanan
│
├── config/                          # Konfigurasi Global & Inisialisasi Sistem
│   └── config.php                   # Pemuat .env, inisialisasi koneksi PDO, session, & timezone
│
├── app/                             # Core Application Layer
│   └── controllers/                 # Penangan Aksi & Request Formulir (Action/Controller Layer)
│       └── [fitur]_action.php       # Handler POST/GET CRUD, validasi input, logika aksi, & redirect/JSON
│
├── models/                          # Data Access Layer (Model & Query Basis Data)
│   └── [fitur]_model.php            # Fungsi/class query database PDO murni dengan prepared statement
│
├── views/                           # Presentation Layer (Antarmuka Pengguna)
│   ├── layouts/                     # Komponen pembungkus antarmuka bersama (shell components)
│   │   ├── header.php               # Head HTML, meta tags, stylesheet loader, & topbar
│   │   ├── sidebar.php              # Navigasi menu utama sistem
│   │   ├── footer.php               # Hak cipta, penutup layout, & script loader
│   │   └── [komponen_global].php    # Modal global bersama (e.g. modal_logout, modal_konfirmasi)
│   ├── pages/                       # Konten tampilan spesifik per fitur/halaman
│   │   ├── [nama_halaman].php       # Antarmuka fitur spesifik (e.g. dashboard, manajemen_data)
│   │   ├── login.php                # Antarmuka formulir autentikasi masuk
│   │   └── logout.php               # Handler pembersihan sesi keluar
│   └── errors/                      # Halaman respon penanganan status error HTTP
│       ├── 403.php                  # Halaman Akses Ditolak (Forbidden)
│       ├── 404.php                  # Halaman Tidak Ditemukan (Not Found)
│       └── 500.php                  # Halaman Kesalahan Server (Internal Server Error)
│
├── middlewares/                     # Lapisan Penjaga Keamanan Akses (Guard Layers)
│   ├── auth_middleware.php          # Validasi sesi login aktif & proteksi rute privat
│   ├── role_middleware.php          # Otorisasi hak akses pengguna berbasis peran (RBAC)
│   └── csrf_middleware.php          # Validasi token keamanan anti-CSRF pada mutasi data
│
├── helpers/                         # Pustaka Fungsi Pembantu Reusable (Utility Helper Library)
│   ├── security_helper.php          # Sanitasi XSS (e()), generator & validator token CSRF
│   ├── url_helper.php               # Generator tautan rute route_url(), action_url(), redirect(), asset_url()
│   ├── date_helper.php              # Format penanggalan & waktu lokal
│   └── upload_helper.php            # Validasi MIME type, ekstensi berkas, batas ukuran, & upload handler
│
├── database/                        # Tata Kelola Basis Data & Versi Skema
│   ├── migrations/                  # Berkas DDL skema terurut nomor versi (e.g. 01_initial.sql, 02_add_field.sql)
│   ├── seeders/                     # Berkas pengisian data awal/default (e.g. seed_users.php)
│   ├── database.sql                 # Arsip skema master basis data
│   └── migrate.php                  # Skrip runner migrasi skema otomatis (CLI/Web)
│
├── uploads/                         # Direktori Penyimpanan Berkas Dinamis Pengguna
│   └── [kategori_unggahan]/         # Subdirektori per modul/entitas (e.g. foto_profil/, dokumen/, lampiran/)
│
└── assets/                          # Aset Statis Front-End
    ├── css/                         # Arsitektur CSS Modular
    │   ├── base.css                 # Reset CSS, font resmi, & token desain dasar (variabel warna, radius)
    │   ├── layout.css               # Grid shell, layout container, header/sidebar spacing, & breakpoint
    │   ├── scrollbar.css            # Desain scrollbar custom modern
    │   ├── components/              # CSS per elemen UI mandiri (buttons, cards, forms, tables, modals, badges)
    │   │   └── [komponen].css
    │   └── modules/                 # CSS khusus spesifik halaman tertentu
    │       └── [halaman].css
    ├── js/                          # Skrip JavaScript Modular
    │   ├── main.js                  # Inisialisasi logika umum, handler global, & event listener dasar
    │   ├── components/              # Skrip pendukung interaksi elemen UI (modal.js, sidebar.js, dropdown.js)
    │   └── modules/                 # Skrip interaktivitas khusus halaman/modul (e.g. chart, dynamic table)
    └── img/                         # Berkas gambar statis identitas (logo, brand icon, ilustrasi)
```

* Aturan & Tanggung Jawab Direktori (Directory Rules & Separation of Concerns)
> 1. **Root Directory**: Hanya boleh berisi berkas konfigurasi lingkungan, berkas server (`.htaccess`), dokumentasi, dan satu-satunya berkas PHP yang dieksekusi langsung yaitu `index.php`. Dilarang menempatkan file PHP logika acak di root.
> 2. **Front Controller (`index.php`)**: Berperan sebagai pintu masuk tunggal (*Single Entry Point*). Memuat bootstrap sistem, middleware autentikasi, serta memetakan rute aksi (`action`/`controller`) dan rute halaman (`page`) melalui mekanisme *whitelist* yang aman.
> 3. **Konfigurasi (`config/`)**: Pusat inisialisasi lingkungan, pembacaan `.env`, pembentukan koneksi PDO MySQL, session handler, timezone, dan pengaturan error reporting. Dilarang meletakkan output HTML atau logika query bisnis di folder ini.
> 4. **Controllers (`app/controllers/`)**: Bertanggung jawab menerima request formulir (POST/GET), memanggil sanitasi input, memanggil fungsi pada Model untuk transaksi data, dan melakukan pengalihan halaman (*Post/Redirect/Get*) atau mengembalikan respons JSON. Dilarang memuat markup HTML tampilan langsung.
> 5. **Models (`models/`)**: Lapisan khusus interaksi data ke database menggunakan PDO prepared statements. Seluruh query SQL terisolasi di sini. Kembalikan data dalam bentuk array asosiatif atau boolean status. Dilarang memproses request HTTP atau menghasilkan output HTML.
> 6. **Views (`views/`)**: Lapisan presentasi antarmuka. Terbagi rapi menjadi `layouts/` (elemen shell umum seperti header, sidebar, footer), `pages/` (konten dinamis spesifik per halaman), dan `errors/` (halaman status HTTP 403, 404, 500). Dilarang menulis query database SQL langsung di dalam berkas view.
> 7. **Middlewares (`middlewares/`)**: Lapisan pencegat dan penjaga akses yang dieksekusi sebelum controller atau view diproses. Bertugas memvalidasi sesi aktif (`auth`), memeriksa hak akses peran pengguna (`role`), dan mengecek validitas token proteksi (`csrf`).
> 8. **Helpers (`helpers/`)**: Pustaka fungsi utilitas mandiri (*pure functions*) yang dapat digunakan berulang di seluruh sistem (e.g. pembersih XSS, pembentuk tautan URL rute, format tanggal lokal, validasi berkas upload).
> 9. **Database (`database/`)**: Skrip terstruktur untuk migrasi skema tabel (`migrations/`) dan data awal (`seeders/`). Setiap perubahan struktur basis data wajib didokumentasikan dalam migrasi bernomor urut agar dapat direplikasi pada berbagai lingkungan sistem.
> 10. **Uploads (`uploads/`)**: Folder penyimpanan media dinamis yang diunggah pengguna. Wajib dikelompokkan ke subfolder sesuai kategori entitas, dilindungi dengan aturan non-executable di `.htaccess`, dan tidak boleh digunakan untuk menyimpan berkas kode program.
> 11. **Assets (`assets/`)**: Aset antarmuka statis yang terpisah secara modular antara `css/`, `js/`, dan `img/`. Menerapkan pemisahan antara gaya dasar (*base*), tata letak (*layout*), komponen UI (*components*), dan gaya spesifik halaman (*modules*).

# Code
* Kerapihan
> pastikan code rapih, gunakan spasi, enter, tab, dll dengan benar. jangan membuat code yang berantakan. dan pastikan code mudah di baca.
* Komentar
> selalu gunakan komentar untuk menjelaskan code yang kompleks atau penting. Jangan terlalu banyak menggunakan komentar, gunakan secukupnya.
* Konsistensi Penamaan (Naming Conventions)
> Selalu gunakan seperti "nama_folder", atau "nama_file", bukan "namaFolder" atau "namaFile".
* Penulisan Kode Bersih (Clean Code & DRY)
> Hindari pengulangan kode (prinsip DRY - Don't Repeat Yourself) dengan mengekstrak logika yang sama ke dalam fungsi, method, atau service/helper yang dapat digunakan kembali. Pastikan kode mudah dibaca (readable) dan dipahami.
* Struktur Direktori
> Terapkan struktur folder yang logis dan mudah dipahami, pisahkan antara frontend dan backend, serta variasikan folder berdasarkan fitur atau jenis file sesuai best practice framework yang digunakan.
* git
> jangan commit apapun, aku yang akan commit secara manual.

# Layouting
* workspace
> tidak ada overflow konten yang membuat object atau konten menjadi tidak konsisten dan memperburuk layout.
* scrollbar
> scrollbar design tidak menggunakan style default tetapi menggunakan style custom, tidak tebal, tidak mempengaruhi layouting dalam arti tidak mempengaruhi width atau height object.
* card
> jangan terlalu sering menggunakan card, hanya gunakan jika benar benar diperlukan, dan gunakanlah dengan rapih. margin dan padding pada card jangan terlalu besar.
* responsive
> saat membuat komponen baru pastikan definisikan juga style responsive nya saat itu juga. gunakan breakpoint untuk tablet dan lg untuk desktop. pastikan semua komponen terlihat bagus di semua ukuran layar.
* SEO (Search Engine Optimization)
> pastikan dalam penulisan struktur selalu ramah untuk SEO atau SEO friendly.


# Design
* icon
> tidak ada icon dengan emoticon, dan tidak semua melulu menggunakan icon, jika menu atau suatu tombol fungsi mengunakan icon maka gunakan dari font awesome.
* warna
> warna pastikan tidak terlalu kontras dengan warna lainnya, latar belakang menggunakan warna putih polos pada awal, semuanya, customisasi warna akan dilakukan di akhir, semua nya. header sidebar dll, akan dibuat menjadi white, kecuali tombol interaksi dibuat menjadi soft blue pada awal.
* animasi
> card di hover tidak perlu ada animasi kecuali nanti ada customisasi dari developer.
* font
> selalu gunakan font yang terlihat resmi, ukuran sesuai dengan h1 h2 h3 h4 h5 h6, gunakan sesuai dengan konteks nya.

# Security
* Validasi & Sanitasi Input
> Selalu lakukan validasi dan sanitasi untuk setiap data yang masuk dari pengguna (user input), baik melalui form, URL parameter, maupun request API, untuk mencegah serangan SQL Injection, XSS (Cross-Site Scripting), dan CSRF.
* Autentikasi & Otorisasi
> Terapkan sistem authentication yang aman (misalnya hashing password menggunakan Bcrypt/Argon2) dan pastikan setiap route atau endpoint memiliki authorization (hak akses) yang jelas sesuai peran (role-based access control).
* Keamanan Konfigurasi & Lingkungan
> Jangan pernah menulis kredensial sensitif (seperti API Key, database password, secret token) secara langsung di dalam kode (hardcoding). Selalu gunakan file .env dan pastikan data sensitif tidak terekspos ke publik (debug mode dimatikan pada lingkungan produksi).

# Backend
* Arsitektur & Struktur Kode (MVC / Clean Architecture)
> Terapkan pemisahan tanggung jawab (separation of concerns) dengan baik. Jaga agar Controller tetap bersih (lean controller), letakkan logika bisnis yang kompleks pada Service Class atau Model, dan gunakan Form Request untuk validasi terpusat.
* Efisiensi Database & Query
> Tulis query database yang efisien. Hindari masalah N+1 query problem dengan menggunakan teknik Eager Loading (with() pada Eloquent). Gunakan database index pada kolom yang sering dijadikan parameter pencarian atau relasi.
* Penanganan Error & Logging
> Gunakan blok try-catch yang tepat untuk menangani error tak terduga. Sediakan pesan response yang ramah bagi pengguna di sisi frontend, sambil mencatat (logging) detail error aslinya di sisi backend untuk kebutuhan debugging.
* RESTful API & Response Konsisten
> Jika membangun endpoint API, gunakan standar penamaan RESTful yang konsisten (misalnya menggunakan HTTP methods GET, POST, PUT, DELETE yang sesuai) dan kembalikan struktur format data JSON yang seragam (menyertakan status, pesan, dan data).

# Frontend
* Manajemen Keadaan & Performa (State Management & Optimization)
> Optimalkan performa frontend dengan meminimalkan ukuran asset (gambar/ikon), melakukan lazy loading pada komponen atau gambar berat, serta menghindari re-render yang tidak perlu pada komponen antarmuka.

# Database
* Desain Skema & Relasi (Schema Design & Relationships)
> Rancang struktur tabel database (schema) dengan normalisasi yang baik untuk menghindari redundansi data. Tentukan tipe data yang paling sesuai (misalnya ENUM untuk status terbatas, DATETIME untuk timestamp, atau UUID untuk primary key) dan definisikan relasi tabel (One-to-One, One-to-Many, Many-to-Many) menggunakan Foreign Key dengan benar.
* Migrations & Seeding
> Selalu gunakan fitur migrasi database (database migrations) untuk membuat atau mengubah struktur tabel secara terkelola dan terversioning. Hindari mengubah struktur tabel secara manual di database. Manfaatkan fitur seeding untuk membuat data awal (dummy data) untuk keperluan pengembangan.

# Aturan Eksekusi Terminal (Windows PowerShell)
- **Environment:** Sistem operasi saat ini adalah **Windows**. Selalu gunakan sintaks PowerShell yang valid, bukan perintah Linux (Unix-like).
- **Perintah yang Dilarang:** JANGAN gunakan `ls`, `grep`, `cat`, `touch`, `rm -rf`, atau `cp`/`mv` ala bash.
- **Perintah Pengganti (PowerShell):**
  - Untuk melihat isi folder: Gunakan `Get-ChildItem` atau `dir`.
  - Untuk mencari teks di file: Gunakan `Select-String`.
  - Untuk membaca file: Gunakan `Get-Content`.
- **Penulisan Path:** Selalu gunakan tanda kutip ganda (`"..."`) pada path direktori atau file, terutama jika path tersebut memiliki potensi spasi.