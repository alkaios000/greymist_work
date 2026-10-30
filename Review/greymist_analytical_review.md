# Analytical Review: Greymist Framework (version 1.1 - refactoring_1)

## 📌 1. Spesifikasi Sistem
Greymist adalah sebuah platform *web-builder* (penyunting halaman/frame secara visual) berbasis antarmuka pengguna grafis. Sistem ini memungkinkan pengguna mendaftar, mengelola "proyek", merancang UI menggunakan "frame", mempublikasikannya, serta membagikan atau men-_clone_ *template* karya orang lain dari komunitas global.

**Stack Teknologi:**
- **Backend**: Native PHP (Model terpusat di `includes`, logika Endpoint di `api/`)
- **Database**: MySQL Server (menggunakan ekstensi PDO)
- **Frontend**: HTML5, CSS3, dan JavaScript (Modular dengan komponen yang dipisah)
- **Penyimpanan**: Penyimpanan lokal untuk media (gambar, profil)

---

## 📊 2. Baris Kode (Lines of Code) & Bahasa Pemrograman
Analisis estimasi ukuran *codebase* berdasarkan ekstensi (belum sepenuhnya dikurangi oleh *asset libraries* seperti Bootstrap/FontAwesome yang digunakan):

| Bahasa Pemrograman / Tipe | Estimasi Baris Kode (LoC) | Peran Utama di Sistem |
|---------------------------|---------------------------|-----------------------|
| **JavaScript (.js)**      | ~40,900 baris | Logika *drag-and-drop*, aksi dinamis di editor (DOM manipulation), pemanggilan API menggunakan Fetch/AJAX. *(Sebagian merupakan vendor library di folder assets)* |
| **CSS (.css)**            | ~37,200 baris | Styling halaman, tema builder khusus, serta modul framework (variabel khusus aplikasi). *(Sebagian library)* |
| **HTML (.html)**          | ~14,000 baris | Templat utama editor/builder modular (terdapat banyak bagian komponen UI di dalam folder `part/`). |
| **PHP (.php)**            | ~9,800 baris | Routing, auth (Sesi), manipulasi data (CRUD Database via folder `api/`), Dashboard, dan integrasi backend ke halaman. |
| **Lainnya (SQL, YML, Py)**| ~120,000+ baris | Konfigurasi, backup database, font set, dan skrip opsional backend (Python). |

> [!NOTE]
> Jumlah baris kode yang besar pada JS dan CSS disebabkan oleh adanya dependensi pihak ketiga atau library *bundling* yang ada pada folder `assets/`.

---

## ⚙️ 3. Transaksi & Fungsi Terkait
Aplikasi ini dipecah berdasarkan daftar transaksi utama atau *endpoint* REST-like (di dalam folder `api/` dan aksi *submission*):

### **Manajemen Autentikasi (Sesi)**
- Login & Register (di folder `session/`)
- Manajemen Profil & Pengaturan (di `profile.php`)

### **Manajemen Proyek & Dashboard**
- `project.php` (Membuat proyek baru, membaca seluruh proyek pengguna)
- `dashboard.php` (Menjelajahi proyek yang dipublikasikan [*public projects*])
- **Cloning Proyek**: Pengguna dapat menduplikasi proyek ke tangannya sendiri (`clone_from_dashboard`, duplikasi frames dari ID lama ke project_id pengguna).
- `rename_project.php`, `save_project.php`, `get_project.php`

### **Komponen Core / Frame Editor API**
- `add_frame.php`: Membuat instansi *frame* di dalam *project_id* tertentu
- `copy_frame.php`: Menduplikasi isi frame dan komponen layoutnya
- `rename_frame.php` / `delete_frame.php`
- `get_frames.php`: Memuat semua list UI per project_id.
- `save_cores.php`, `save-frame.php`: Menyimpan data elemen kanvas ke `core_content` (berupa format HTML/JSON stringifikasi).

### **Manajemen Aset & Warna**
- `upload-assets-image.php` / `upload-background-image.php`: Menangani *upload* berkas gambar dan menulis URL-nya ke dalam Database `GM_BG_IMAGES`.
- `delete-image.php`, `get-images.php`

---

## 🔄 4. Flow & Alur Aplikasi

Alur logis user experience di dalam kerangka kerja ini:

1. **Autentikasi (Pintu Masuk)**: Pengguna melakukan pendaftaran / masuk melalui `session/login.php` -> Sesinya disimpan untuk validasi global di `includes/auth.php`.
2. **Dashboard Publik**: Jika melalui `dashboard.php`, pengguna bisa melihat ribuan *project* pengguna lain yang telah diberi label *Published*. Pengguna memiliki opsi untuk meng-_clone_ project tersebut.
3. **Workspace Kolektif (`project.php`)**: Menampilkan panel semua project buatan/hasil *clone* dari pengguna tersebut.
4. **Alur Editor Utama (`index.php`)**: 
   - Sistem memuat kerangka dasar HTML.
   - PHP mengeksekusi tiga query: Load Project Data, Load Frames terkait, Load Colors yang terkait ID Proyek.
   - Variabel PHP diproses dan disuntikkan ke JavaScript window utama dalam bentuk *JSON_UNESCAPED_UNICODE* (`window.GREYMIST`).
   - UI Editor akan termuat melalui folder `part/` (Sidebar kiri & kanan, komponen tengah/kanvas, dan modal alert).
   - Pengguna berinteraksi dengan DOM menggunakan drag-n-drop (JS murni).
   - Segala perubahan akan di-simpan berulang dengan menembak EndPoint `api/` (seperti `save_cores.php`) via Fetch secara asinkron tanpa mereload *builder* utama.

---

## 🏛️ 5. Struktur & Arsitektur Direktori
Aplikasi Greymist dibuat menggunakan **Pemrograman Prosedural Modular** (Mirip MVC tetapi tanpa Router kelas terpusat). Arsitekturnya terbagi dalam modul berikut:

- `/` (Root Folder): Halaman front-desk dan landing (`dashboard.php`, `project.php`, `index.php`), mewakili *View* & Logika awal (Gateways).
- `admin/`: Antarmuka panel admin di luar jangkauan user biasa untuk *User Management* dan monitor proyek secara terpusat `GM_ADMIN`.
- `api/`: Berisi skrip murni *Backend Actions* (`Controllers`). Semua fungsi CRUD khusus Editor Builder ditempatkan terpisah di sini dan merespons dalam format header/JSON.
- `assets/`: Berisi media statis seperti Style Tema/SCSS, file upload dinamis Gambar, Font, dan Logika JS utama editor `JS/`.
- `config/`: Setup JSON atau preferensi lokal dasar lainnya.
- `DB/`: File pencadangan struktur `.sql` dan catatan *Database Scheme*:
  - *GM_USERS, GM_PROJECTS, GM_COLORS, GM_BG_IMAGES, GM_FRAMES*.
- `includes/`: Penghubung logika sistem utama (`db.php` untuk PDO SQL Koneksi, Middleware Auth, Header Global `header.php`).
- `part/`: Modul-modul *View Components*. (`aside-left.html`, `main.html`, `aside-right.html`, `modal.html`). Potongan HTML ini dimuat dinamis ke `index.php`.
- `session/`: Modul *Account Controller* (Formulir Login Transaksional, Registrasi, dan Pemutus Sesi).

**Kesimpulan:** 
Struktur aplikasi ini dirancang terpusat dengan gaya *Single Page Application-esque API Interactivity* untuk editor utama (`index.php`), sembari tetap menggunakan PHP Rendering Server-Side (`dashboard.php`, `project.php`) untuk performa awal dan kontrol rute halamannya. Skema arsitekturnya difokuskan untuk skalabilitas penambahan plugin komponen baru.
