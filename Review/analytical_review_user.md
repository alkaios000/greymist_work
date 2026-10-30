# Analytical Review: Sisi Pengguna (User Context)

Sistem Greymist pada sisi *User* merepresentasikan antarmuka kolektif (seolah-olah media sosial untuk web-builder) di mana pengguna dapat mengelola ruang kerjanya mandiri sekaligus melihat kreasi orang lain.

## 👥 1. Alur Autentikasi dan Registrasi
Folder: `session/`
- **Register (`register.php`)**: Memproses pendataan pengguna baru. Melakukan sanitasi `email` dan melakukan binarisasi `password` dengan enkripsi Native `PASSWORD_DEFAULT`.
- **Login (`login.php`)**: Memvalidasi input dan menghasilkan sesi PHP native (`$_SESSION['user_id']`). Sesi ini berfungsi sebagai gerbang utama sebelum pengguna bisa mengotak-atik sistem. Di sistem backend, berkas `includes/auth.php` akan disisipkan di atas semua file *route* untuk menendang kembali ke beranda jika belum *login*.

## 🏘️ 2. Eksplorasi Ekosistem (Dashboard Publik)
Folder/Berkas: `dashboard.php`
Ini adalah halaman *landing* kolektif di mana pengguna akan disajikan deretan proyek yang memiliki status `project_status = 'published'` milik orang lain.
- **Siklus Kloning (Cloning Flow)**: Inilah fitur terpenting untuk pengguna. Ketika tombol tipe *Clone* ditekan, sebuah *form* melakukan POST secara hulu. Skrip akan menarik `core_content` (data kanvas desain HTML mentah) berdasarkan ID target, dan membuat salinan baru di bawah nama akun yang mengkloning tersebut. Di database `gm_projects`, ia akan mendapatkan status `unpublished` default dengan nama awalan *"Copy of ..."*.

## 🗂️ 3. Manajemen Ruang Kerja Kolektif
Berkas: `project.php`
Pengguna diatur dengan manajemen ruang kerjanya lewat halaman ini. Basis data akan mencocokkan sesi `user_id` dengan relasi `project_id`.
- Pembuataan Proyek Baru akan menginsersi tabel `gm_projects`.
- Menekan proyek yang spesifik di panel ini akan membuat sistem melakukan pemanggilan GET parameter menuju ruang kerja sejati. (yaitu, memanggil `index.php?project_id=xxxx`). Ketergantungan *Primary Key* ini merupakan inti navigasi milik pengguna.
- Tersedia logika CRUD dasar sisi pengguna, yaitu mengganti nama proyek secara asinkron atau menghapusnya.

## 👤 4. Manajemen Akun
Berkas: `profile.php`
Pengguna mendapatkan kebebasan personalisasi. Ia menyimpan jalur gambar (*Path*) yang direngkuh melalui `api/upload-images` lalu dikaitkan pada gambar `profile_picture` khusus untuk mempercantik katalognya pada halaman *public dashboard*.
