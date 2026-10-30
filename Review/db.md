# Arsitektur Database GREYMISTBETA

Database ini menggunakan nama **`GREYMISTBETA`** dengan pola arsitektur **Relational Database** yang menggunakan *Storage Engine* InnoDB. InnoDB dipilih karena memiliki pengamanan relasi (*Foreign Key Constraints*).

Berikut penjelasan jelas mengenai struktur tiap tabel beserta relasinya (tanpa menyertakan isi/value data):

## 1. Entitas Induk (Hirarki Ke-1)

Kedua tabel ini bersifat *independent* dan berada di hirarki tertinggi. Keduanya tidak bergantung pada tabel lain manapun.

### Tabel `GM_USERS` (Tabel Pengguna Aplikasi)

Menyimpan data identitas pelanggan/user yang menggunakan platform ini.

* **`user_id`** (INT, Primary Key, Auto Increment): ID Unik untuk membedakan secara mutlak setiap user (ID Root).
* **`nickname`** (VARCHAR): Nama panggilan/tampilan di UI.
* **`username`** (VARCHAR, Unique): Username unik untuk autentikasi login.
* **`password`** (VARCHAR): Kata sandi terenkripsi (Hash).
* **`phone`** (VARCHAR): Nomor telepon operasional.
* **`email`** (VARCHAR, Unique): Email unik untuk kontak dan autentikasi.
* **`profile`** (VARCHAR): URL atau *path* file untuk foto profil akun.
* **`created_at`** (TIMESTAMP): Mendata kapan akun dibuat.
* **`last_login`** (TIMESTAMP): Waktu login terakhir untuk dilacak oleh sistem admin.

### Tabel `GM_ADMIN` (Tabel Administrator)

Terpisah total dari `GM_USERS` untuk menghindari *privilege escalation* (Kerentanan user memanjat hak akses menjadi admin).

* *Struktur kolom utama*: `admin_id` (PK), `nickname`, `username`, `password`, dan `profile`. Tabel ini sepenuhnya terisolasi dan memiliki otoritas monitoring secara global terhadap sistem.

---

## 2. Entitas Aset & Data (Hirarki Ke-2)

Tabel-tabel di tingkat ini secara langsung dimiliki (berelasi *Foreign Key*) kepada tabel `GM_USERS`.

### Tabel `GM_PROJECTS` (Berkas Proyek Editor)

Mewadahi proyek web/layout yang dikerjakan oleh user.

* **`project_id`** (INT, Primary Key): Identifier absolut proyek tersebut.
* **`user_id`** (INT, Foreign Key $\rightarrow$ `GM_USERS`): Mencatat siapa pemilik sah proyek ini. Menggunakan skema `ON DELETE CASCADE` (Jika user dihapus, proyeknya ikut binasa otomatis).
* **`project_name`** (VARCHAR): Nama judul dari proyek.
* **`thumbnail`** (VARCHAR): Path atau *snapshot* gambar preview dari proyek tersebut untuk UI Dashboard.
* **`created_at`** (TIMESTAMP): Waktu pertama kali proyek dibuat.

### Tabel `GM_BG_IMAGES` (Bank Gambar Pengguna)

Aset multimedia mandiri yang diunggah pengguna untuk digunakan ke dalam web-nya, seperti Background atau Object gambar.

* **`image_id`** (INT, Primary Key).
* **`user_id`** (INT, Foreign Key $\rightarrow$ `GM_USERS`): Karena gambar bersifat privasi dan hak properti, tabel ini merujuk asalnya kepada akun user yang mengupload (`ON DELETE CASCADE`).
* **`image`** (VARCHAR): Nama file imagenya.
* **`path`** (VARCHAR): Alamat lokasi penyimpanan file pada server lokal direktori `/assets/`.

---

## 3. Entitas Engine / Komponen Editor (Hirarki Ke-3)

Tabel-tabel di tingkat bawah ini dimiliki secara eksklusif oleh suatu tabel `GM_PROJECTS`.

### Tabel `GM_FRAMES` (Komponen Kanvas Utama)

Ini adalah **Tabel Paling Vital** bagi Workspace Editor (Engine). Tabel ini menyimpan halaman dan kumpulan DOM tree *drag-and-drop*.

* **`frame_id`** (INT, Primary Key).
* **`project_id`** (INT, Foreign Key $\rightarrow$ `GM_PROJECTS`): Menandakan bahwa kumpulan frame/halaman ini menempel ke spesifik 1 proyek saja (`ON DELETE CASCADE`).
* **`frame_name`** (VARCHAR): Nama spesifik layer atau halamannya.
* **`core_content`** (`LONGTEXT`): Secara konseptual di sinilah seluruh barisan modifikasi tag HTML, layout flexbox/grid, dan interaksi yang dibuat user disimpan mentah-mentah ke database menggunakan tipe string yang panjangnya tak berhingga untuk menampung seluruh DOM Node Element.
* **`created_at`** (TIMESTAMP).

### Tabel `GM_COLORS` (Variabel Warna Tematik)

Menyimpan manajemen "Color Library" yang biasa di-pin oleh desainer.

* **`color_id`** (INT, Primary Key).
* **`project_id`** (INT, Foreign Key $\rightarrow$ `GM_PROJECTS`): Diikat ke project. Artinya "Skema warna/Palet Desain" akan berbeda dan tersendiri untuk setiap proyek (`ON DELETE CASCADE`).
* **`color_variable`** (VARCHAR): Menyimpan metadata warna (misal format HEX/RGBA) yang akan diterjemahkan menjadi utilitas sistem `var()` CSS buatan Greymist.

---

## Peta Visual Relasi Struktur

```text
[ ADMIN_GLOBAL_ACCESS ] (GM_ADMIN)

GM_USERS (Akun Utama)
  │
  ├── GM_BG_IMAGES (Aset gambar-gambar upload user terikat disini)
  │
  └── GM_PROJECTS (1 User bisa punya banyak project)
        │
        ├── GM_COLORS (Penyimpanan library warna spesifik per-project itu)
        │
        └── GM_FRAMES (Penyimpanan hasil drag-n-drop DOM HTML per-project)
```

**Kesimpulan Cara Kerjanya:**
Saat admin menghapus sebuah akun `GM_USERS` yang nakal, maka *Cascade System* SQL akan meledakkan/menghapus struktur menjalar ke bawah ini dengan otomatis. Seluruh gambar di `GM_BG_IMAGES` (dari database-nya), semua proyeknya (`GM_PROJECTS`), yang kemudian otomatis men-trigger terhapusnya seluruh library warnanya (`GM_COLORS`) beserta kanvas halamannya (`GM_FRAMES`) tanpa meninggalkan jejak atau *orphaned data* (data yatim-piatu) yang bisa memberatkan memori server. Ini adalah logika **Data Cleansing** yang sangat cemerlang.
