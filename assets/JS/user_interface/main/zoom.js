$(document).ready(function() {
    let scale = 0.8;
    const zoomStep = 0.05;
    const minScale = 0.2;
    const maxScale = 5;

    const preview = $("#gm-frame-preview");
    if (!preview.length) return;
    const container = preview.parent();
    const previewEl = preview.get(0);

    let isMouseDown = false;
    let isDragging = false;
    let isSpacePressed = false;
    let startX = 0, startY = 0;
    let lastX = 0, lastY = 0;
    let targetLeft = 0, targetTop = 0;
    let isRafPending = false;
    let hasUserInteracted = false;

    // ================= Center & Transform Initialization =================
    function resetToCenter() {
        if (!previewEl) return;
        const computedStyle = window.getComputedStyle(previewEl);
        const originalWidth = parseFloat(computedStyle.width) || 1200;
        const originalHeight = parseFloat(computedStyle.height) || 800;

        scale = 0.8;

        const containerOffset = container.offset() || { left: 0, top: 0 };
        const containerWidth = container.innerWidth() || window.innerWidth;
        const containerHeight = container.innerHeight() || window.innerHeight;

        targetLeft = containerOffset.left + (containerWidth - originalWidth * scale) / 2;
        targetTop = containerOffset.top + (containerHeight - originalHeight * scale) / 2;

        preview.css({
            "transform-origin": "0 0",
            "transform": `scale(${scale})`
        });

        preview.offset({
            left: targetLeft,
            top: targetTop
        });

        // Sinkronkan kembali nilai aktual dari DOM
        const actualOff = preview.offset();
        if (actualOff) {
            targetLeft = actualOff.left;
            targetTop = actualOff.top;
        }
    }

    // Inisialisasi posisi awal saat DOM siap
    resetToCenter();

    // Re-sync saat seluruh asset (gambar/font) selesai termuat jika belum ada interaksi pengguna
    $(window).on("load", function() {
        if (!hasUserInteracted) {
            resetToCenter();
        }
    });

    // Track status Space key untuk pan ala Figma
    $(document).on("keydown", function(e) {
        if (e.code === "Space" && !$(e.target).is("input, textarea, select, [contenteditable='true']")) {
            if (!isSpacePressed) {
                isSpacePressed = true;
                if (!isDragging) {
                    preview.css("cursor", "grab");
                }
            }
        }
    });

    $(document).on("keyup", function(e) {
        if (e.code === "Space") {
            isSpacePressed = false;
            if (!isDragging) {
                preview.css("cursor", "default");
            }
        }
    });

    // ================= Raf Render Batching =================
    function scheduleRender() {
        if (!isRafPending) {
            isRafPending = true;
            requestAnimationFrame(renderTransform);
        }
    }

    function renderTransform() {
        isRafPending = false;

        preview.offset({
            left: targetLeft,
            top: targetTop
        });

        preview.css({
            "transform-origin": "0 0",
            "transform": `scale(${scale})`
        });
    }

    // ================= Smooth Zoom & Scroll Handler =================
    preview.on("wheel", function(event) {
        if (document.body.classList.contains("gm-fullscreen-preview")) return;

        const origEvent = event.originalEvent;

        // 1. Jika tombol Shift ditekan: trigger scroll di dalam .gm-object-core (demon core)
        if (origEvent.shiftKey) {
            const targetCore = event.target.closest(".gm-object-core");
            if (targetCore) {
                const delta = origEvent.deltaY !== 0 ? origEvent.deltaY : origEvent.deltaX;
                if (targetCore.scrollHeight > targetCore.clientHeight) {
                    targetCore.scrollTop += delta;
                } else if (targetCore.scrollWidth > targetCore.clientWidth) {
                    targetCore.scrollLeft += delta;
                }
                event.preventDefault();
                return;
            }
        }

        // 2. Scroll 2 jari biasa (tanpa Shift): Zoom In / Zoom Out mengikuti posisi cursor
        event.preventDefault();

        // Pastikan koordinat terkalibrasi sebelum zoom pertama kali
        if (!hasUserInteracted) {
            hasUserInteracted = true;
            const actualOff = preview.offset();
            if (actualOff) {
                targetLeft = actualOff.left;
                targetTop = actualOff.top;
            }
        }

        const rect = previewEl.getBoundingClientRect();
        const cursorX = event.clientX - rect.left;
        const cursorY = event.clientY - rect.top;
        const prevScale = scale;

        // Normalisasi delta scroll
        const delta = -origEvent.deltaY / 100;
        const zoomFactor = 1 + delta * zoomStep;

        scale = Math.min(Math.max(scale * zoomFactor, minScale), maxScale);

        const factor = scale / prevScale;

        targetLeft = targetLeft - (cursorX * (factor - 1));
        targetTop = targetTop - (cursorY * (factor - 1));

        scheduleRender();
    });

    // ================= Drag / Pan Canvas =================
    preview.on("mousedown", function(event) {
        if (document.body.classList.contains("gm-fullscreen-preview")) return;

        // Jangan drag jika berinteraksi dengan resize handler, dimension label, atau form editing
        const isInteractiveHandle = !!(
            event.target.closest(".gm-handler") ||
            event.target.closest(".gm-label") ||
            event.target.closest("input, textarea, select, button") ||
            event.target.closest("[contenteditable='true']") ||
            event.target.closest("[data-gm-editing]")
        );
        if (isInteractiveHandle) return;

        // Drag canvas diperbolehkan dengan:
        // 1. Klik kiri (which === 1) di mana saja pada canvas maupun object core
        // 2. Klik tengah mouse (which === 2)
        // 3. Spasi + klik kiri
        const isLeftClick = (event.which === 1);
        const isMiddleClick = (event.which === 2);

        if (isLeftClick || isMiddleClick || isSpacePressed) {
            hasUserInteracted = true;
            isMouseDown = true;
            isDragging = false;
            startX = event.clientX;
            startY = event.clientY;
            lastX = event.clientX;
            lastY = event.clientY;

            // Pastikan posisi target ter-sync dengan koordinat elemen terkini
            const off = preview.offset() || { left: 0, top: 0 };
            targetLeft = off.left;
            targetTop = off.top;

            if (isSpacePressed || isMiddleClick) {
                preview.css("cursor", "grabbing");
                event.preventDefault();
            }
        }
    });

    $(document).on("mousemove", function(event) {
        if (document.body.classList.contains("gm-fullscreen-preview")) return;

        if (isMouseDown) {
            if (!isDragging) {
                // Threshold 3px agar klik biasa (seleksi objek) tetap responsif tanpa tertukar drag
                if (Math.hypot(event.clientX - startX, event.clientY - startY) > 3) {
                    isDragging = true;
                    document.body.classList.add("gm-is-dragging");
                    preview.css("cursor", "grabbing");
                }
            }

            if (isDragging) {
                event.preventDefault();
                const dx = event.clientX - lastX;
                const dy = event.clientY - lastY;

                targetLeft += dx;
                targetTop += dy;

                lastX = event.clientX;
                lastY = event.clientY;

                scheduleRender();
            }
        }
    });

    $(document).on("mouseup", function(event) {
        if (isMouseDown) {
            if (isDragging) {
                isDragging = false;
                document.body.classList.remove("gm-is-dragging");
                preview.css("cursor", isSpacePressed ? "grab" : "default");

                // Cegah click event yang tidak disengaja tepat setelah melakukan drag
                window.addEventListener("click", function suppressClick(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    window.removeEventListener("click", suppressClick, true);
                }, true);
            }
            isMouseDown = false;
        }
    });

    // Mencegah drag ghost bawaan browser pada gambar atau tautan
    preview.on("dragstart", function(e) {
        e.preventDefault();
    });

    // ================= Shortcut Ctrl + \ (Reset Scale & Center) =================
    $(document).on("keydown", function(event) {
        if (event.ctrlKey && event.key === "\\") {
            event.preventDefault();
            resetToCenter();
            scheduleRender();
            preview.css("cursor", "default");
        }
    });

    // Nonaktifkan menu konteks klik kanan native
    preview.on("contextmenu", function(e) {
        e.preventDefault();
    });
});
