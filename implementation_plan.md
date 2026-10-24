# Analisis & Solusi Browser Macet (Not Responding)

## Hasil Analisis
Keluhan yang Anda alami di mana editor macet atau *not responding* setelah ditinggal ke tab/aplikasi lain disebabkan oleh **penggunaan `requestAnimationFrame` yang berlebihan (Antipattern Polling)** pada script UI Greymist.

Saat ini, di dalam file seperti `object-attribute.html`, `flex.html`, dan `object-action-ui.js`, terdapat **puluhan hingga ratusan loop `requestAnimationFrame`** yang berjalan secara mandiri dan bersama-sama tanpa henti. Setiap input atribut (misal: ID, Class, Lang, Href, Style, dll.) memiliki fungsi `watch()` sendiri yang melakukan `document.querySelector` setiap 1 frame (60 kali per detik).
 
**Kenapa macet saat tab ditinggal?**
Ketika Anda pindah ke tab atau aplikasi lain, browser modern akan menunda (pause) eksekusi `requestAnimationFrame` untuk menghemat baterai/CPU. Namun, karena ada ratusan loop yang mengantre, saat Anda kembali ke tab Greymist, browser akan mencoba "mengejar ketertinggalan" dan mengeksekusi semua loop tersebut secara serentak. Akibatnya, CPU langsung *spike* 100%, thread JavaScript terblokir, dan tab menjadi *Not Responding* selama beberapa detik hingga menit.

## Proposed Changes
Untuk mengatasi ini secara permanen tanpa merusak fitur realtime editor, kita harus melakukan refactoring pada cara panel atribut membaca objek aktif.

Kita akan beralih dari **Independent Polling** (puluhan loop terpisah) menjadi **Event-Driven & Single-Loop Synchronization**.

### Pendekatan Solusi
1. **Event-Driven Selection**: Setiap kali user mengklik objek di kanvas, kanvas akan memicu satu CustomEvent (misal `window.dispatchEvent(new Event('gm-active-changed'))`).
2. **Single Global Loop**: Jika polling masih dirasa perlu untuk menangkap perubahan atribut dari luar, kita hanya akan menggunakan **satu buah** `requestAnimationFrame` global yang memantau perubahan objek aktif, bukan 50 buah.

### File yang akan diubah:
Mengingat ada banyak file yang menggunakan metode loop ini, tahap awal kita akan fokus membenahi file dengan jumlah loop terbanyak:
#### [MODIFY] [object-attribute.html](file:///c:/laragon/www/Greymist_framework/Greymist%20%28version%201.1%20-%20refactoring_3%29%20-%20PPTX%205.0/part/main/frame/attribute/object-attribute.html)
- Menghapus semua fungsi `gmAttrXXX_watch()` individual.
- Menggabungkan pembaruan semua input ke dalam satu fungsi `syncAllAttributes(activeObject)`.
- Hanya menggunakan 1 `requestAnimationFrame` utama atau `MutationObserver` untuk mendeteksi `gm-active`.

#### [MODIFY] [object-action-ui.js](file:///c:/laragon/www/Greymist_framework/Greymist%20%28version%201.1%20-%20refactoring_3%29%20-%20PPTX%205.0/assets/JS/program_function/frame/object-action-ui.js)
- Mengubah `loadFromActive()` agar tidak berjalan sendirian, tapi tergabung dalam event listener terpusat.

## User Review Required
> [!WARNING]
> Refactoring ini akan mengubah cara kerja inti sinkronisasi UI di sebelah kanan (panel attributes). Apakah Anda setuju untuk memulai refactoring pada `object-attribute.html` terlebih dahulu sebagai langkah awal penyembuhan memori/CPU ini?
