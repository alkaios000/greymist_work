# GREYMIST FRAMEWORK v1.1: ANALITICAL & ARCHITECTURAL REVIEW

<!-- *Dokumen Analisis Terintegrasi - Perspektif User, Admin, Workspace Engine, dan Database* -->

---

## 1. PENDAHULUAN

Dokumen ini merupakan hasil analisis mendalam (analytical review) terhadap struktur direktori, file, dan arsitektur kode dari **Greymist Framework v1.1**. Sistem ini dirancang sebagai sebuah platform **Web-Based Visual Page Builder**, di mana pengguna dapat membuat, mengedit, dan menyimpan desain layout web (berbasis UI node/frame) secara visual dan efisien.

Framework ini telah mengadopsi pola arsitektur **Modular & Feature-Based**, memisahkan domain secara ketat antara fungsionalitas Publik/User, Manajemen Admin, Engine Workspace, dan lapisan API.

---

## 2. ARSITEKTUR DATABASE (LAPISAN DATA)

Database `GREYMISTBETA` dirancang dengan arsitektur relasional yang solid, difokuskan pada integritas data, efisiensi penyimpanan *state* editor, dan pemisahan hak akses.

### 2.1 Skema Inti & Relasi Antar Tabel

* **`GM_USERS` (Entitas Pengguna)**: Menyimpan kredensial pengguna, profil, dan pelacakan login. Semua entitas produktivitas selalu bermuara dan berelasi pada tabel ini.
* **`GM_ADMIN` (Entitas Administrator)**: Tabel terpisah dari User, yang memiliki *privilege* tertinggi tanpa terikat ke project pengguna, meningkatkan sisi keamanan (Mencegah kerentanan ekskalasi akses/Leveling).
* **`GM_PROJECTS` (Wadah Utama)**: Berelasi (*Foreign Key*) dengan `GM_USERS`. Merepresentasikan 1 file project yang memiliki `project_name` dan path ke `thumbnail`.
* **`GM_FRAMES` (Core Engine Data)**: Tabel paling krusial. Berelasi dengan `GM_PROJECTS`. Menyimpan state HTML/Dom Tree dalam kolom `core_content` (bertipe `LONGTEXT`). Setiap project dapat memiliki banyak frame/kanvas halaman.
* **`GM_COLORS` & `GM_BG_IMAGES` (Assets Database)**: Metadata untuk penyimpanan custom color variable dan asset background pribadi pengguna. Berelasi ke entitas Project dan User agar asset tetap terisolasi dengan aman per akun.

### 2.2 Efisiensi Basis Data

* **Cascade Deletion**: Menggunakan *Constraint `ON DELETE CASCADE`*, yang artinya apabila pengguna (`User`) atau `Project` dihapus, maka secara otomatis semua frame, warna, dan aset terkait akan terhapus oleh mesin MySQL. Ini meminimalisir adanya *Orphaned Data* (Sampah data).
* **Penyimpanan State Sederhana**: Alih-alih menyimpan setiap *node dom* ke tabel yang berbeda, Greymist menyimpan keseluruhan *Tree Node* dalam kolom `LONGTEXT` di `GM_FRAMES`. Hal ini menyeimbangkan kompleksitas struktur dan kecepatan performa saat memuat Workspace (Load-time rendering).

---

## 3. SISI PENGGUNA / USER PORTAL (`/user/`)

Direktori `/user/` ditujukan sebagai **User Dashboard** yang mengelola produktivitas dan kepemilikan aset personal pengguna.

### 3.1 Fungsionalitas Utama Pengguna

* **Dashboard (`user/dashboard.php`)**: Berfungsi sebagai beranda utama setelah berhasil login. Menyajikan statistik ringkas mengenai aktivitas terbaru pengguna.
* **Project Management (`user/project.php`)**: Sebuah antarmuka di mana pengguna bisa membuat (*Create*), menampilkan daftar (*Read*), mengelola, dan menginisiasi Workspace editor untuk project tertentu.
* **Template Hub (`user/templates.php`)**: Memungkinkan pengguna untuk melihat dan mungkin menyalin template bawaan sistem untuk mempercepat proses pembuatan UI.
* **Profil Management (`user/profile.php`)**: Mengelola data dasar profil (`GM_USERS`).

### 3.2 Alur Kerja (Workflow)

Pengguna login via `/api/login.php` (Session Token di set di `includes/auth.php`) $\rightarrow$ Diarahkan ke `/user/dashboard.php` $\rightarrow$ Membuat project pada `/user/project.php` (Menyuntikkan data pada `GM_PROJECTS` dan trigger `GM_FRAMES` awal) $\rightarrow$ Klik Edit yang akan membuka Workspace (`/index.php` engine utama).

---

## 4. SISI ADMINISTRATOR (`/admin/`)

Direktori `/admin/` merupakan pusat kendali terisolasi yang diperuntukkan bagi manajer sistem untuk mengaudit dan mengendalikan performa ekosistem aplikasi.

### 4.1 Fungsionalitas Administrator

* **Monitor Pengguna (`admin/users.php`)**: UI Table untuk membaca dan memanajemen seluruh data pengguna (CRUD). Admin dapat memonitor kapan user terakhir aktif dan statistik pemakaian mereka.
* **Inspeksi Project (`admin/projects.php` & `admin/preview_project.php`)**: Admin dapat melihat, memantau *thumbnail*, dan menghapus proyek pengguna apabila melanggar pedoman platform atau menghabiskan kapasitas berlebihan. Preview khusus digunakan agar Admin bisa menginspeksi hasil kanvas tanpa mengganggu state *Editor* asli di DB.
* **Pelaporan dan Konfigurasi (`admin/reports.php`, `admin/settings.php`)**: Laporan secara komprehensif terkait jumlah proyek dan fungsionalitas internal.

---

## 5. WORKSACE ENGINE / CORE EDITOR (The Heart of Greymist)

Lapisan paling kompleks dan merupakan inti dari produk Greymist, terletak pada perpaduan folder `/part/`, `/assets/`, dan file root `/index.php`.

### 5.1 Dekomposisi UI Editor (`/part/`)

Workspace dipecah menjadi modul-modul HTML terpisah agar main-file tidak terlalu besar, di-include-kan via PHP.

* **`part/aside-left/`**: Lapisan **Layer, DOM Tree Management, dan Asset Upload UI**. Menampilkan struktur hirarki elemen yang sedang digambar. Terintegrasi dengan Drag-and-Drop library (`assets/shortable/`).
* **`part/main/`**: Lapisan **Kanvas / Viewport**. Tempat hasil *drag & edit* ter-render secara visual menyerupai *Browser-View*.
* **`part/aside-right/`**: Lapisan **Property Inspector**. Memanipulasi *Styling* (Padding, Margin, Color, Font Size, Shadow dll). Di sinilah `CSS Variable` dimodifikasi secara dinamis.
* **`part/modal/`**: Floating interface (misal: panel modifikasi *Grid*, *Flexbox*, atau *Settings*) yang bersifat asinkron untuk kenyamanan *user experience*.

### 5.2 Manajemen Interaksi dan State (JavaScript pada `/assets/JS/`)

Editor UI bukan sekadar tampilan statis; ini adalah **Single Page Application (SPA)-Lite Engine**:

* Di dalam direktori `assets/JS/`, terdapat pemisahan tugas berdasarkan file-file kecil yang spesifik (misal: *Undo-Redo Engine*, *Zoom-Pan behavior*, *Right-Click Context Menu*).
* **Manipulasi Native DOM**: Skrip memantau setiap event *click*, *drag*, dan ketikan pada *Aside Right*, lalu langsung menyuntikkan (Inject) *Inline CSS variable* ke node DOM yang terpilih (Elemen aktif) di *Kanvas*.
* Pendekatan **No-Framework Vanilla JS** menjamin memori sistem (*heap/RAM*) dikelola dengan efisien dan performa drag-and-drop tidak mengalami *Lag*.

### 5.3 Sistem Desain Ekosistem (`/assets/GREYMIST_CSS_VAR/` & Custom CSS)

Greymist tidak memakai library UI Bootstrap atau Tailwind for its core builder. Sistem ini memetakan nilai desain menggunakan struktur CSS Variables (`var(--name)`):

* Keuntungan: Modifikasi di *Properties Panel (Aside-right)* hanya mengubah root variable, yang kemudian merambatkan perubahannya (`Cascading Effect`) secara kilatis ke object di Canvas tanpa me-render ulang seluruh memori window HTML. Sisi ini yang membuat Greymist disebut "Premium Grade Editor".

---

## 6. SISTEM API & PERTUKARAN DATA (`/api/`)

Alur penyimpanan data dari *Workspace Edge/Browser* ke Database Server menggunakan pertukaran asinkronous (AJAX/Fetch) di folder `/api/` (Aplikasi *Stateless REST-Lite*).

### 6.1 Siklus Komunikasi Workspace

* **Autosave / Manual Save Mechanism (`api/save-frame.php`, `api/save_project.php`)**: JavaScript di Workspace merangkum struktur DOM Node hasil editan (diformat sebagai `String/LONGTEXT`), dan mengirimkannya *via HTTP POST Request*.
* **Manajemen Frame Layer (`api/add_frame.php`, `api/copy_frame.php`, dll)**: Saat user menambah/menduplikasi halaman (*Screen*), API akan mengelola injeksi row DB baru dan merespon kembali menggunakan format JSON, yang oleh Workspace di-decode menjadi UI Halaman yang baru.
* **Sistem Manajemen Aset Dinamis (`api/upload-assets-image.php`)**: Memproses upload file fisik gambar dari PC pengguna, memvalidasi MimeType, menyimpannya di `/assets/img/user_{id}/`, dan mendaftarkannya ke DB (`GM_BG_IMAGES`).

---

## KESIMPULAN & EXECUTIVE SUMMARY

Greymist Framework 1.1 telah bermetafosis menjadi arsitektur aplikasi berskala *pro-grade* / *enterprise-lite*.

* **Untuk Pengguna**: Framework menyuguhkan stabilitas. UI memisahkan dashboard administratif dan editor secara tegas. Kecepatan editor *(Real-time canvas re-paint)* sangat dioptimalkan berkat Native JS (Tanpa DOM diffing overhead dari React/Vue).
* **Untuk Administrator**: Modularitas yang ketat antar role memastikan panel moderasi admin bebas dari gangguan query data layer-user, memastikan aplikasi sangat aman.
* **Dari Sisi Codebase**: Pengorganisasian folder (`/admin`, `/api`, `/assets`, `/DB`, `/part`, `/user`) mencerminkan pola **Separation of Concerns (SoC) MVC/Modular murni**, yang mana view, state logic, server-API, dan core utilities terpisah letaknya, memudahkan on-boarding engineer lanjutan *untuk fase maintainability* berikutnya.

> Draf dokumen ini sangat ideal digunakan untuk penjelasan Presentasi Teknikal maupun *Pitch Deck* teknis terkait kekuatan fundamental sistem aplikasi Greymist.
