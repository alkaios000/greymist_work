# Analytical Review: Sisi Administrator (Admin Context)

Area Administrator berada seluruhnya dalam kapsul lingkup lokal yang terisolasi di dalam direktori `admin/`. Ini adalah semacam Panel Sentris (*Control Center*) untuk moderasi konten dan pengguna pada Greymist Framework.

## 🛡️ 1. Proteksi Skala Modul
Direktori `admin/` memiliki salinan ruter yang mirip dengan antarmuka utama, dengan memisahkan halaman menjadi beberapa segmen (`index.php`, `users.php`, `projects.php`). Terdapat modul autensikasi terpisah yaitu `admin/login.php` yang khusus membaca nilai tabel database di mana kolom entitas `is_admin = 1`. Sesi ini diisolasi untuk memastikan manipulasi peretasan melalui cookie *user reguler* tidak dapat naik jabatan secara tak sadar.

## 📈 2. Engine Telemetri (Tinjauan Dashboard)
Berkas: `admin/index.php`
Di sinilah analisis data kasar (*raw data*) milik basis data global SQL diproses menjadi wujud statistik visual.
- *Queries* berantai dieksekusi via sistem hitung asinkron:
  - Jumlah total *user* (terdaftar) vs Total penambahan interval mingguan (kalkulasi `DATE_SUB(NOW(), INTERVAL 7 DAY)` basis MYSQL).
  - Skala komparasi *Project* (`total proyek keseluruhan`, versus total proyek `published`, versus total proyek berbasis *Template Publikasi Kreator* `is_template = 1`).

## 🗃️ 3. Manajemen Database Super-User (Tabel `gm_users`)
Berkas: `admin/users.php`
Terdapat sebuah skema Tabel Administrator Kompleks (Datatables). Seorang administrator berkuasa penuh atas identitas reguler dengan hak-hak sebagai berikut:
- **Pendaftaran Akseleratif**: Dapat membuat profil dan pendaftaran Bypass melalui panel (*Tambah User Baru*).
- **CRUD Purna Waktu & Reset Kata Sandi**: Fungsi spesifik `Reset Password` dikodekan dengan logika sederhana me-*replace* nilai enkripsi hash default dengan string konstan *"password123"* sehingga memulihkan kontrol atas akun macet reguler.
- **Role Togling**: Mengonversi `is_admin` pada user-ID spesifik dari 0 (User biasa) ke 1 (Admin), dan menolak secara preventif interupsi (*suicide prevention*) terhadap akun Master/Admin Utama (ID khusus, eg: `user_id == 1`).

## 👁️‍🗨️ 4. Moderasi Konten Kanvas (Tabel `gm_projects` & `gm_frames`)
Berkas: `admin/projects.php`
Fitur paling *powerful* ada pada pengelolaan berkas kanvas pengguna tanpa sepengetahuan mereka.
- Melalui panel AJAX di layar modal *Overview*, Admin dapat mengunduh daftar *stringified* DOM Tree (`frame_id` dan properti turunannya `core_content`) secara pratinjau yang diserap langsung *real-time* ke browser.
- Pemberian anugerah *Template* (`toggle_template`) ke Proyek yang bagus, atau pembungkaman (*Status Flag Override*) dari status "*Published*" menjadi "*Unpublished*". Penghapusan di sini terintegrasi pada relasi kuat SQL; membinasakan Entitas Induk Project sekaligus membakar semua rekam jejak Kanvas miliknya (`ON DELETE CASCADE` via API maupun `DELETE` paksa kueri bertali).
