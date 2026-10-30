---
name: greymist-ai-builder
description: Baca isi frame, buat/edit komponen UI (.gm-object), atau hapus komponen langsung di dalam Object Core (#object-core) Greymist Workspace Editor menggunakan akun langganan Gemini di Antigravity IDE.
---

# Greymist Workspace AI Component Builder, Reader & Remover Skill

Skill ini memungkinkan Anda (Antigravity) membaca isi frame saat ini (`core_content`), merancang komponen HTML/CSS `.gm-object`, mengedit elemen yang sudah ada, atau menghapus komponen dari frame proyek pengguna.

## Perintah CLI Helper (`database/inject_component.php`)

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
   php "c:\laragon\www\Greymist_work\database\inject_component.php" --project_id=4 --frame_id=16 --name="Hero Section" --mode=both --html="<section class='gm-object' style='...'>...</section>"
   ```
   *(Gunakan `--replace` bila ingin mengganti seluruh `core_content` frame setelah membaca dan memodifikasinya).*
