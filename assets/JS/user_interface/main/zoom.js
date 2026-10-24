$(document).ready(function() {
    let scale = 1;
    const zoomStep = 0.05;
    const minScale = 0.3;
    const maxScale = 5;

    const preview = $("#gm-frame-preview");
    const container = preview.parent();

    // ================= Zoom mengikuti cursor =================
  preview.on("wheel", function(event) {
    if (document.body.classList.contains('gm-fullscreen-preview')) return;
    event.preventDefault();

    const rect = this.getBoundingClientRect();
    const cursorX = event.clientX - rect.left;
    const cursorY = event.clientY - rect.top;
    const prevScale = scale;

    // Hitung delta zoom proporsional
    const delta = -event.originalEvent.deltaY / 100; // normalisasi scroll speed
    const zoomFactor = 1 + delta * zoomStep; // faktor zoom dinamis

    scale = Math.min(Math.max(scale * zoomFactor, minScale), maxScale);

    // Hitung perbedaan posisi agar cursor tetap di titik yang sama
    const factor = scale / prevScale;
    const offset = preview.offset();

    preview.offset({
        left: offset.left - (cursorX * (factor - 1)),
        top: offset.top - (cursorY * (factor - 1))
    });

    preview.css({
        "transform-origin": "0 0",
        "transform": `scale(${scale})`
    });
});

    // ================= Drag klik kiri dengan delay 0.2s =================
   let isDragging = false;
let dragTimer = null;
let lastX = 0, lastY = 0;

preview.on("mousedown", function(event) {
    if (document.body.classList.contains('gm-fullscreen-preview')) return;
    
    if (event.which === 1) { // klik kiri
        lastX = event.clientX;
        lastY = event.clientY;

        // Start timer 200ms sebelum drag aktif
        dragTimer = setTimeout(() => {
            isDragging = true;
            preview.css("cursor", "default");
        });
    }
});

$(document).on("mouseup", function(event) {
    if (event.which === 1) {
        clearTimeout(dragTimer); // batalkan drag jika mouse dilepas cepat
        if (isDragging) {
            isDragging = false;
        }
        preview.css("cursor", "default");
    }
});

$(document).on("mousemove", function(event) {
    if (document.body.classList.contains('gm-fullscreen-preview')) return;

    if (isDragging) {
        let dx = event.clientX - lastX;
        let dy = event.clientY - lastY;
        let offset = preview.offset();
        preview.offset({
            left: offset.left + dx,
            top: offset.top + dy
        });
        lastX = event.clientX;
        lastY = event.clientY;
    }
});


    // ================= Shortcut Ctrl + \ =================
    $(document).on("keydown", function(event) {
        if (event.ctrlKey && event.key === "\\") {
            event.preventDefault();

            // Ambil ukuran asli preview (tanpa scale)
            const previewEl = preview.get(0);
            const computedStyle = window.getComputedStyle(previewEl);
            const originalWidth = parseFloat(computedStyle.width);
            const originalHeight = parseFloat(computedStyle.height);

            // Reset scale ke 0.8
            scale = 0.8;
            preview.css("transform", `scale(${scale})`);
            preview.css("transform-origin", "0 0");

            // Hitung offset supaya preview benar-benar tengah di container
            const containerWidth = container.innerWidth();
            const containerHeight = container.innerHeight();

            const left = container.offset().left + (containerWidth - originalWidth * scale) / 2;
            const top = container.offset().top + (containerHeight - originalHeight * scale) / 2;

            preview.offset({ left: left, top: top });

            preview.css("cursor", "default");
        }
    });

    // Nonaktifkan menu konteks klik kanan
    preview.on("contextmenu", function(e) {
        e.preventDefault();
    });
});
