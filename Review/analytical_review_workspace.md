# Analytical Review: Sisi Workspace (Engine Editor Khusus)

Bagian *Workspace* (Direktori terpusat di `/`, `/part`, `/api` dan `/assets/JS/`) adalah tempat inti *software* alias kompilator *drag-and-drop* antarmuka GUI milik Greymist dijalankan. Skalanya adalah yang paling berat dan kompleks.

## 🏛️ 1. Arsitektur Single-Page Dom Injection Layer (SP-DIL)
Berkas Pusat: `/index.php`
Sistem me-_load_ sebuah halaman kosong dasar di mana tidak ada re-loading halaman yang akan memotong sesi berkarya bagi pengguna.
Konsep SP-DIL:
- Sistem PHP mencetak data SQL spesifik (`project_id`) ke sebuah *Array Global Window* dalam kode Javascript sembari menggunakan format `JSON_UNESCAPED_UNICODE` agar penulisan hieroglif, logogram maupun abjad asing tidak terputus (`window.GREYMIST`).
- Sisa tampilan *Workspace* dibangun dari susunan "Include/Require HTML Part":
  - Panel Navigasi Sisi Kiri (`part/aside-left.html`).
  - Inti Kanvas Renderisasi Tengah (`part/main.html`).
  - Atribut Inspektor Kontrol Sisi Kanan (`part/aside-right.html`).

## 🧱 2. Algoritme Behavior JavaScript Modular
Lokasi: `assets/JS/`
Kumpulan ribuan baris JavaScript yang dikategorisasi teramat rapi sesuai Domain Fungsionalitasnya masing-masing.

| Divisi Direktori Script | Deskripsi Interaksi Khusus & Logika Fungsional |
|-------------------------|------------------------------------------------|
| **`/object_define`** | Mendefinisikan instansi elemen. Ketika elemen DOM baru di "drag" (*drag-n-drop* logic), ini akan menciptakan blok `<div class="gm-object">` dan membubuhinya dengan atribut-atribut spesifik bawaan (`data-id`, `data-type`). Modul ini merangkum properti bawaan elemen. |
| **`/user_interface`**| Modul *Visual Output* dan *View Model*. Berfungsi menangani animasi buka-tutup modal inspector box, zoom in zoom-out rendering pada kanvas, panel penjelajah warna, dan tampilan list Ikon-ikon Assets. |
| **`/program_function`**| Inti komputasi kompleks. Memuat logic fitur *Undo-Redo* (berbasis pencatatan Riwayat Memory DOM Cache Array lokal), live rendering CSS Property yang didikte melalui Inspektor Kanan, dan manipulasi class DOM. |
| **`/object_behavior`** | Sinkronisasi Perilaku Perangkat Berjalan. Mengikat pendengar tombol eksternal (Event Listeners seperti *Keydown Delete* shortcut khusus kanvas) yang berinteraksi langsung secara semantik pada `target_node`. |
| **`/right_click_menu`**| Pencegatan logika Default Context-Menu Browser ke arah Custom UI Box. Menghasilkan *Floating Sub-menu* saat pengguna klik kanan pada elemen DOM dengan opsi seleksi orang tua secara travesal DOM tree, beserta *Copy-Paste Clipboard* modifikasi sendiri. |

## 🕹️ 3. Transaksi Data Asinkron Kontinyu (Core APIs)
Folder: `/api/`
Fokus ruang ini adalah mematikan waktu unda sistem (System idle). Pekerjaan pengguna pada kanvas tidak direkam sebagai rentetan "Data Log Event", namun direkam secara Utuh layaknya satu halaman HTML String berformat `core_content`.
- Skrip `/api/save-frame.php` dipanggil asinkron secara pasif di balik layar berulang kali lewat `Fetch API` JSON string payloads.
- Saat fitur manipulasi lain ditarik (seperti penduplikatan komponen Layout / ganti warna tema / Upload image Background Canvas), API di panggil untuk sekadar menaruh nilai *Url Path Upload* / Warna baru dengan segera lalu JavaScript menampungnya dan merender perubahannya pada waktu riil, memberikan ilusi layaknya *software desktop native* yang sangat fluida.
