---
name: greymist-ai-builder
description: Baca isi frame, buat/edit komponen UI (.gm-object), atau hapus komponen langsung di dalam Object Core (#object-core) Greymist Workspace Editor menggunakan akun langganan Gemini di Antigravity IDE. Mendukung penuh sistem Action (pindah frame/scroll/modal), Animasi, dan Atribut.
---

# Greymist Workspace AI Component Builder, Reader & Remover Skill

Skill ini memungkinkan Anda (Antigravity) membaca isi frame saat ini (`core_content`), merancang komponen HTML/CSS `.gm-object`, mengedit elemen yang sudah ada, atau menghapus komponen dari frame proyek pengguna dengan kompatibilitas penuh terhadap 3 sistem editor bawah Greymist: **Action**, **Animasi**, dan **Atribut**.

---

## 1. Kompatibilitas Sistem Interaksi & Menu Bawah Greymist

### A. Fitur Action (`part/main/frame/attribute/object-action.html`)
Gunakan atribut `data-action-type` untuk menghubungkan navigasi dan aksi interaktif:
1. **Pindah Halaman / Frame (`goto`)**:
   - Atribut: `data-action-type="goto"` dan `data-action-target="<frame_id_atau_nama_frame>"`
   - Gunakan pada link navbar, tombol multi-halaman, atau footer link untuk berpindah ke frame tujuan.
   - *Contoh:*
     ```html
     <a class="gm-object" href="#" data-action-type="goto" data-action-target="2" title="Layanan & Harga">Layanan & Harga</a>
     ```
2. **Scroll Halus ke Section (`scroll`)**:
   - Atribut: `data-action-type="scroll"` dan `data-action-scroll-target="<id_section>"`
   - Pastikan section penampung memiliki `id="<id_section>"` (misal `id="features"`, `id="pricing"`, `id="contact"`).
   - *Contoh:*
     ```html
     <a class="gm-object" href="#pricing" data-action-type="scroll" data-action-scroll-target="pricing" title="Lihat Paket Harga">Harga</a>
     ```
3. **Popup Modal Interaktif (`modal`)**:
   - Atribut:
     - `data-action-type="modal"`
     - `data-action-modal-title="Judul Modal"`
     - `data-action-modal-content="Isi pesan / informasi modal..."`
     - `data-action-modal-width="440px"`
     - `data-action-modal-bg="#ffffff"`
     - `data-action-modal-color="#0f172a"`
   - *Contoh:*
     ```html
     <button class="gm-object" data-action-type="modal" data-action-modal-title="Mulai Uji Coba Gratis" data-action-modal-content="Nikmati seluruh fitur editor tanpa batas selama 14 hari gratis." data-action-modal-width="440px" data-action-modal-bg="#ffffff" data-action-modal-color="#0f172a" title="Daftar Akun Baru">Mulai Gratis</button>
     ```
*(Catatan: Pengguna dapat menguji aksi ini di editor dengan mengaktifkan Mode Interaksi di pojok kiri atas kanvas atau dengan tombol Alt + Klik).*

---

### B. Fitur Animasi (`part/main/frame/attribute/object-animation.html`)
Setiap elemen visual utama (headline, tombol CTA, kartu harga/fitur, form) harus menyertakan styling dan atribut animasi Greymist:
- **Class CSS**: `gm-object gm-anim-base <nama_animasi>`
- **Pilihan Nama Animasi**:
  - `gm-anim-fade-in`
  - `gm-anim-slide-up`
  - `gm-anim-slide-down`
  - `gm-anim-slide-left`
  - `gm-anim-slide-right`
  - `gm-anim-zoom-in`
  - `gm-anim-bounce-in`
- **Atribut Dataset**:
  - `data-gm-anim="gm-anim-slide-up"`
  - `data-gm-anim-dur="0.8"` (durasi dalam detik)
  - `data-gm-anim-del="0.1"` (delay dalam detik)
  - `data-gm-anim-loop="1"` (atau `"infinite"`)
- **CSS Variables Inline**:
  `style="... --gm-anim-duration: 0.8s; --gm-anim-delay: 0.1s; animation-iteration-count: 1;"`

---

### C. Fitur Atribut (`part/main/frame/attribute/object-attribute.html`)
Gunakan tag semantik HTML dengan atribut lengkap agar terbaca sempurna di tab Atribut:
- `<a>`: sertakan `href="..."` dan `title="..."`.
- `<button>`: sertakan `type="button"` dan `title="..."`.
- `<input>`: sertakan `type="..."`, `name="..."`, `placeholder="..."`, `id="..."`.
- `<textarea>`: sertakan `rows="..."`, `name="..."`, `placeholder="..."`, `id="..."`.
- `<img>`: sertakan `src="..."` dan `alt="..."`.
- Kontainer Section: sertakan `id="..."` yang jelas (`id="hero"`, `id="features"`, `id="pricing"`, `id="contact"`).

---

## 2. Perintah CLI Helper (`database/inject_component.php`)

1. **Lihat Daftar Proyek & Frame:**
   ```powershell
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --list
   ```

2. **Baca Isi Frame Saat Ini (`--read`):**
   ```powershell
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --read --project_id=4 --frame_id=16
   ```

3. **Hapus Komponen Tertentu atau Kosongkan Frame (`--delete` / `--clear`):**
   ```powershell
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --delete="navbar"
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --clear
   ```

4. **Tambah atau Ganti Komponen di Dalam Frame (`--html` / `--replace`):**
   ```powershell
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --name="Hero Section" --mode=both --html="<section class='gm-object gm-anim-base gm-anim-fade-in' id='hero' data-gm-anim='gm-anim-fade-in' data-gm-anim-dur='0.8' data-gm-anim-del='0' data-gm-anim-loop='1' style='...'>...</section>"
   ```
   *(Gunakan `--replace` bila ingin mengganti seluruh `core_content` frame setelah membaca dan memodifikasinya).*
