---
name: greymist-ai-builder
description: Baca isi frame, buat/edit komponen UI (.gm-object), atau hapus komponen langsung di dalam Object Core (#object-core) Greymist Workspace Editor menggunakan akun langganan Gemini di Antigravity IDE. Mendukung penuh sistem Action (pindah frame/scroll/modal), Animasi, Atribut, Breakpoints responsif, Penggunaan ulang komponen (Navbar/Sidebar/Footer), serta Intervensi serentak seluruh halaman proyek.
---

# Greymist Workspace AI Component Builder, Reader & Remover Skill

Skill ini memungkinkan Anda (Antigravity) membaca isi frame saat ini (`core_content`), merancang komponen HTML/CSS `.gm-object`, mengedit elemen yang sudah ada, atau menghapus komponen dari frame proyek pengguna dengan kompatibilitas penuh terhadap seluruh fitur inti Greymist:
1. **Action** (Pindah Frame Antar Halaman, Smooth Scroll Section, Modal Popup).
2. **Animasi** (`gm-anim-base`, CSS variables, keyframe engine).
3. **Atribut** (ID semantik, Tag HTML, style inline).
4. **Breakpoints Responsif** (Desktop 1920 base, Tablet 768px, Mobile 480px, custom breakpoints).
5. **Component Reusability** (Penggunaan ulang Navbar, Sidebar, Footer yang sudah ada agar hemat token dan desain konsisten).
6. **Project-Wide Batch Interventions** (Intervensi gaya serentak pada seluruh halaman proyek ketika tidak ada objek yang dipilih).

---

## 1. Kompatibilitas Sistem Interaksi & Menu Bawah Greymist

### A. Fitur Action (`part/main/frame/attribute/object-action.html`)
Gunakan atribut `data-action-type` untuk menghubungkan navigasi dan aksi interaktif:
1. **Pindah Halaman / Frame (`goto`)**:
   - Atribut: `data-action-type="goto"` dan `data-action-target="<frame_id_atau_nama_frame>"`
   - Gunakan pada link navbar, tombol multi-halaman, atau footer link untuk berpindah ke frame tujuan.
   - Sistem Greymist secara otomatis memetakan nama frame (misal `"About"`, `"Contact"`) ke ID frame numerik asli saat disimpan.
   - *Contoh:*
     ```html
     <a class="gm-object" href="#" data-action-type="goto" data-action-target="About" title="Tentang Kami">Tentang</a>
     ```
2. **Scroll Halus ke Section (`scroll`)**:
   - Atribut: `data-action-type="scroll"` dan `data-action-target="<id_section>"` (atau `data-action-scroll-target="<id_section>"`)
   - Pastikan section penampung memiliki `id="<id_section>"` (misal `id="features"`, `id="pricing"`, `id="contact"`).
   - *Contoh:*
     ```html
     <a class="gm-object" href="#pricing" data-action-type="scroll" data-action-target="pricing" title="Lihat Paket Harga">Harga</a>
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

---

### B. Fitur Animasi (`part/main/frame/attribute/object-animation.html`)
Setiap elemen visual utama (headline, tombol CTA, kartu harga/fitur, form) harus menyertakan styling dan atribut animasi Greymist:
- **Class CSS**: `gm-object gm-anim-base <nama_animasi>`
- **Pilihan Nama Animasi**: `fade`, `slide-up`, `slide-down`, `slide-left`, `slide-right`, `zoom-in`, `zoom-out`, `bounce`, `pulse`, `flip`, `rotate`.
- **Atribut Dataset**: `data-gm-anim="true"`
- **CSS Variables Inline**:
  `style="... --gm-anim-dur: 0.6s; --gm-anim-delay: 0.2s; --gm-anim-ease: cubic-bezier(0.16, 1, 0.3, 1);"`

---

### C. Fitur Atribut (`part/main/frame/attribute/object-attribute.html`)
Gunakan tag semantik HTML dengan atribut lengkap agar terbaca sempurna di tab Atribut:
- `<a>`: sertakan `href="..."` dan `title="..."`.
- `<button>`: sertakan `type="button"` dan `title="..."`.
- `<input>`: sertakan `type="..."`, `name="..."`, `placeholder="..."`, `id="..."`.
- `<textarea>`: sertakan `rows="..."`, `name="..."`, `placeholder="..."`, `id="..."`.
- `<img>`: sertakan `src="..."` dan `alt="..."`.
- Kontainer Section: sertakan `id="..."` yang jelas (`id="hero-section"`, `id="features"`, `id="pricing"`, `id="contact"`).

---

## 2. Aturan Penggunaan Ulang Komponen (Navbar, Sidebar, Footer)
Untuk menghemat token dan menjaga konsistensi desain 100% antar halaman:
- Jika konteks memori proyek sudah memiliki Navbar (`[EXISTING_NAVBAR]`), Footer (`[EXISTING_FOOTER]`), atau Sidebar (`[EXISTING_SIDEBAR]`), **DILARANG MERANCANG ULANG DARI NOL**.
- Gunakan kembali struktur HTML tersebut pada halaman baru:
  1. Pertahankan styling, logo, brand, dan warna asli.
  2. Tambahkan link ke halaman baru jika belum ada di dalam menu.
  3. Berikan highlight status visual `active` (`color: #3b82f6; font-weight: 700;`) pada link halaman yang sedang dibuat.

---

## 3. Engine Breakpoints Responsif
- Greymist mengelola breakpoint melalui `window.gmBreakpoints`:
  - `desktop` (1920 base)
  - `tablet` (max-width: 768px)
  - `mobile` (max-width: 480px)
- Semua komponen HTML harus menggunakan tata letak responsif modern:
  - `flex-wrap: wrap; gap: ...;`
  - `max-width: 100%; box-sizing: border-box;`
  - `grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));`
- AI dapat menambahkan breakpoint responsif baru secara otomatis dengan penanda:
  ```json
  <!--GM_BREAKPOINTS-->
  [
    {"id": "tablet", "name": "Tablet", "rule": "max-width: 768px", "widthValue": 768, "type": "max"},
    {"id": "mobile", "name": "Mobile", "rule": "max-width: 480px", "widthValue": 480, "type": "max"}
  ]
  ```

---

## 4. Intervensi Seluruh Halaman (Batch Update Frames)
Ketika tidak ada komponen yang dipilih (`target_mode = core`), dan pengguna memberikan instruksi yang menargetkan seluruh halaman (misal: *"konsistenkan semua card"*, *"ganti warna tema di semua pages"*, *"ganti font semua halaman"*):
- Gunakan operasi serentak:
  ```html
  <!--GM_OP:BATCH_UPDATE_FRAMES-->
  <!--GM_FRAME_UPDATE:NamaHalamanAtauID-->
  <kode html lengkap untuk halaman tersebut>
  <!--GM_FRAME_UPDATE:NamaHalamanAtauID-->
  <kode html lengkap untuk halaman tersebut>
  ```
- Sistem backend akan langsung memperbarui seluruh record di tabel database `gm_frames`, dan editor akan menyinkronkan tampilan live canvas dan seluruh thumbnail.

---

## 5. Perintah CLI Helper (`database/inject_component.php`)

1. **Lihat Daftar Proyek & Frame:**
   ```bash
   php database/inject_component.php list
   ```

2. **Baca Isi HTML Frame:**
   ```bash
   php database/inject_component.php read <frame_id>
   ```

3. **Injeksi / Update HTML Frame:**
   ```bash
   php database/inject_component.php update <frame_id> "path/ke/file_komponen.html"
   ```
