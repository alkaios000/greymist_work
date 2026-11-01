/**
 * File: assets/JS/program_function/ai/ai-utils.js
 * Modul: Utilitas DOM, Selektor Frame, Cache Memori Konteks AI,
 *        Daftar Komponen Frame, Image Compressor, Status Helper, & Draggable.
 */
(function (window) {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};
    window.cachedAiMemory = null;
    let cachedAiMemory = null;

    const PRESET_PROMPTS = [
        { label: "Hero", prompt: "Buat Hero Section modern dengan badge, judul besar, deskripsi singkat, dan 2 tombol CTA" },
        { label: "Navbar", prompt: "Buat Navbar navigasi horizontal dengan logo brand di kiri, menu link di tengah, dan tombol CTA di kanan" },
        { label: "Pricing", prompt: "Buat bagian kartu harga (Pricing Table) 3 kolom: Starter, Pro (Populer), dan Enterprise" },
        { label: "Kontak", prompt: "Buat kartu Formulir Kontak minimalis dengan input Nama, Email, Pesan, dan tombol Kirim" },
        { label: "Web PPT", prompt: "Buat 5 slide presentasi web (Web PPT) interaktif dengan topik [Tulis Topik Di Sini], masing-masing slide menjadi frame terpisah (Slide 1 s/d Slide 5), visible (tanpa display: none), lengkap dengan tombol navigasi Sebelumnya & Selanjutnya yang bisa diklik untuk berpindah slide." },
        { label: "Page Baru", prompt: "Buat frame halaman baru untuk halaman Login lengkap dengan Navbar dan Form Login" },
        { label: "Multi-Page", prompt: "Buat 3 frame halaman website terpisah: Beranda, Layanan & Harga, dan Hubungi Kami" },
        { label: "Baca Proyek", prompt: "Baca dan jelaskan susunan proyek, halaman-halaman, serta komponen di dalamnya" },
        { label: "Clone Foto", prompt: "Buat antarmuka (UI) website modern yang meniru tata letak dan estetika foto referensi yang diunggah" },
        { label: "Ganti Nama", prompt: "Ganti nama project jadi " }
    ];

    function getCurrentProjectId() {
        const params = new URLSearchParams(window.location.search);
        const fromUrl = params.get("project_id");
        if (fromUrl) return parseInt(fromUrl, 10) || 0;
        const hiddenInput = document.getElementById("project_id");
        if (hiddenInput && hiddenInput.value) return parseInt(hiddenInput.value, 10) || 0;
        if (window.GREYMIST && window.GREYMIST.project && window.GREYMIST.project.project_id) {
            return parseInt(window.GREYMIST.project.project_id, 10) || 0;
        }
        return 0;
    }

    function getActiveObjectCore() {
        const activeThumb = document.querySelector(".gm-frame-thumbnail.actived");
        if (activeThumb && activeThumb.dataset.frame) {
            const coreByThumb = document.getElementById("object-core-" + activeThumb.dataset.frame);
            if (coreByThumb) return coreByThumb;
        }
        const visibleCores = Array.from(document.querySelectorAll("#gm-frame-preview .gm-object-core"));
        const visible = visibleCores.find(el => {
            const parentFrame = el.closest(".gm-frame");
            return parentFrame ? getComputedStyle(parentFrame).display !== "none" : getComputedStyle(el).display !== "none";
        });
        if (visible) return visible;
        return document.querySelector(".gm-object-core");
    }

    function getActiveFrameId() {
        const activeThumb = document.querySelector(".gm-frame-thumbnail.actived");
        if (activeThumb && activeThumb.dataset.frame) return activeThumb.dataset.frame;
        const core = getActiveObjectCore();
        return core ? (core.dataset.frame || "") : "";
    }

    function cleanEmojis(str) {
        if (!str || typeof str !== "string") return str || "";
        return str.replace(/[\u{1F600}-\u{1F64F}\u{1F300}-\u{1F5FF}\u{1F680}-\u{1F6FF}\u{1F700}-\u{1F77F}\u{1F780}-\u{1F7FF}\u{1F800}-\u{1F8FF}\u{1F900}-\u{1F9FF}\u{1FA00}-\u{1FA6F}\u{1FA70}-\u{1FAFF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{2300}-\u{23FF}\u{2B50}\u{20E3}\u{FE0F}]/gu, "");
    }

    function getSelectedObject() {
        const selectedObj = document.querySelector(".gm-object.gm-active");
        if (selectedObj && !selectedObj.classList.contains("gm-object-core")) {
            return selectedObj;
        }
        return null;
    }

    function getTargetContainer(mode) {
        if (mode === "selected") {
            const selected = getSelectedObject();
            if (selected) return selected;
        }
        return getActiveObjectCore();
    }

    /**
     * Mengumpulkan snapshot seluruh frame & susunan halaman yang ada di Workspace saat ini
     * untuk dikirim ke sistem caching memori AI.
     */
    function collectAllFramesSnapshot() {
        const snapshot = [];
        const thumbs = document.querySelectorAll(".gm-frame-thumbnail");
        thumbs.forEach(thumb => {
            const fid = thumb.dataset.frame;
            if (!fid) return;
            const nameEl = thumb.querySelector(".gm-frame-name");
            const core = document.getElementById("object-core-" + fid);
            snapshot.push({
                frame_id: fid,
                frame_name: nameEl ? nameEl.textContent.trim() : ("Frame " + fid),
                core_content: core ? core.innerHTML : ""
            });
        });
        return snapshot;
    }

    /**
     * Memuat & menyinkronkan Cache Memori AI proyek saat aplikasi dimuat atau setelah perubahan.
     */
    function syncProjectAiMemory() {
        const projectId = getCurrentProjectId();
        if (!projectId) return;

        const executeSync = () => {
            // Coba pulihkan dari localStorage terlebih dahulu agar instan saat baru dimuat
            try {
                const localRaw = localStorage.getItem("gm_ai_memory_proj_" + projectId);
                if (localRaw) {
                    cachedAiMemory = JSON.parse(localRaw);
                    updateMemoryBadgeUI(cachedAiMemory);
                }
            } catch (_) {}

            const snapshot = collectAllFramesSnapshot();

            fetch("index.php?action=ai_get_memory&project_id=" + encodeURIComponent(projectId), {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    project_id: projectId,
                    frames_snapshot: snapshot
                })
            })
                .then(r => r.json())
                .then(res => {
                    if (res && res.status === "OK" && res.ai_memory) {
                        cachedAiMemory = res.ai_memory;
                        try {
                            localStorage.setItem("gm_ai_memory_proj_" + projectId, JSON.stringify(cachedAiMemory));
                        } catch (_) {}
                        updateMemoryBadgeUI(cachedAiMemory);
                    }
                })
                .catch(() => {});
        };

        if (typeof window.requestIdleCallback === "function") {
            window.requestIdleCallback(executeSync, { timeout: 2000 });
        } else {
            executeSync();
        }
    }

    function updateMemoryBadgeUI(memory) {
        const badgeEl = document.getElementById("gm-ai-memory-badge");
        if (!badgeEl) return;

        const readMemoryCheckbox = document.getElementById("gm-ai-read-memory");
        const isMemOn = readMemoryCheckbox ? readMemoryCheckbox.checked : false;

        if (!isMemOn) {
            badgeEl.textContent = `⚡ Mode Kilat (Memori Off)`;
            badgeEl.title = `Mode Kilat aktif: AI merespons super cepat tanpa membaca riwayat & seluruh frame.\n(Klik badge atau centang opsi di panel bawah untuk mengaktifkan Memori Konteks)`;
            return;
        }

        if (!memory) {
            badgeEl.textContent = `🧠 Memori AI Aktif`;
            badgeEl.title = `Memori Konteks Proyek AI Aktif`;
            return;
        }

        const pagesCount = memory.total_pages || (Array.isArray(memory.pages_map) ? memory.pages_map.length : 0);
        const historyCount = Array.isArray(memory.history) ? memory.history.length : 0;

        badgeEl.textContent = `🧠 Memori AI: ${pagesCount} Page • ${historyCount} Riwayat`;

        const pageDetails = (memory.pages_map || []).map(p => {
            const compCount = Array.isArray(p.components) ? p.components.length : 0;
            return `• ${p.frame_name} (${compCount} blok)`;
        }).join("\n");

        const lastAction = historyCount > 0
            ? memory.history[historyCount - 1].prompt
            : "Belum ada riwayat prompt";

        badgeEl.title = `Ingatan Sistem AI (${memory.project_name || "Proyek"}):\n`
            + (pageDetails ? pageDetails + "\n" : "")
            + `Instruksi Terakhir: "${lastAction}"`;
    }

    function refreshWorkspaceAfterChange(frameId) {
        document.dispatchEvent(new CustomEvent("gm-dom-tab-activated"));
        if (frameId && typeof window.updateThumbnail === "function") {
            window.updateThumbnail(frameId);
        } else {
            const core = getActiveObjectCore();
            if (core && core.dataset.frame && typeof window.updateThumbnail === "function") {
                window.updateThumbnail(core.dataset.frame);
            }
        }
        if (typeof window.saveProject === "function") {
            window.saveProject();
        }
        if (typeof window.initGmCharts === "function") {
            setTimeout(window.initGmCharts, 40);
        }
        renderFrameComponentsList();
    }


    function summarizeElementLabel(el, idx) {
        const tag = el.tagName.toLowerCase();
        const heading = el.querySelector("h1, h2, h3, h4, strong, span, a");
        let snippet = heading ? heading.textContent.trim() : el.textContent.trim();
        snippet = snippet.replace(/\s+/g, " ");
        if (snippet.length > 30) {
            snippet = snippet.substring(0, 30) + "…";
        }
        return `${idx + 1}. <${tag}> ${snippet || "(elemen visual)"}`;
    }

    /**
     * Menampilkan daftar komponen di dalam Frame Aktif secara Read-Only / Selectable
     * (Tanpa tombol hapus agar tidak berisiko terhapus secara tidak sengaja; penghapusan hanya lewat prompt)
     */
    function renderFrameComponentsList() {
        const listEl = document.getElementById("gm-ai-frame-items-list");
        const countBadge = document.getElementById("gm-ai-frame-count");
        if (!listEl) return;

        const core = getActiveObjectCore();
        if (!core) {
            listEl.innerHTML = `<div style="font-size:10.5px;color:#888;padding:4px 0;">Tidak ada frame aktif.</div>`;
            if (countBadge) countBadge.textContent = "0";
            return;
        }

        const children = Array.from(core.children);
        if (countBadge) countBadge.textContent = String(children.length);

        if (children.length === 0) {
            listEl.innerHTML = `<div style="font-size:10.5px;color:#888;padding:4px 0;">Frame masih kosong.</div>`;
            return;
        }

        listEl.innerHTML = "";
        children.forEach((child, idx) => {
            const row = document.createElement("div");
            row.className = "gm-ai-frame-item";
            if (child.classList.contains("gm-active")) {
                row.style.borderColor = "#0078ff";
                row.style.background = "#eff6ff";
            }

            const labelBtn = document.createElement("span");
            labelBtn.className = "gm-ai-frame-item-label";
            labelBtn.textContent = summarizeElementLabel(child, idx);
            labelBtn.title = "Klik untuk memilih komponen ini di kanvas (Untuk menghapus, ketik di prompt AI)";
            labelBtn.addEventListener("click", () => {
                document.querySelectorAll(".gm-object.gm-active").forEach(el => el.classList.remove("gm-active"));
                child.classList.add("gm-active");
                if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                    window.GMObjectHandler.attachTo(child);
                }
                child.scrollIntoView({ behavior: "smooth", block: "nearest" });
                renderFrameComponentsList();
            });

            row.appendChild(labelBtn);
            listEl.appendChild(row);

            // Tampilkan juga sub-kartu di dalam section ini agar pengguna bisa langsung klik memilih kartu spesifik
            const subCards = findSpecificCards(child);
            subCards.forEach((cItem, cIdx) => {
                const subRow = document.createElement("div");
                subRow.className = "gm-ai-frame-item";
                subRow.style.paddingLeft = "16px";
                subRow.style.background = "#fafafa";
                if (cItem.classList.contains("gm-active")) {
                    subRow.style.borderColor = "#0078ff";
                    subRow.style.background = "#eff6ff";
                }
                const subLabel = document.createElement("span");
                subLabel.className = "gm-ai-frame-item-label";
                const headingEl = cItem.querySelector("h1, h2, h3, h4, strong, a");
                const cardTitle = headingEl ? headingEl.textContent.trim().replace(/\s+/g, " ") : (cItem.id || `Kartu ${cIdx + 1}`);
                subLabel.innerHTML = `<i class="fa-regular fa-square" style="font-size:9px;color:#0078ff;margin-right:4px;"></i> <span>Kartu ${cIdx + 1}: ${cardTitle.substring(0, 24)}</span>`;
                subLabel.title = "Klik untuk memilih kartu ini di kanvas";
                subLabel.addEventListener("click", () => {
                    document.querySelectorAll(".gm-object.gm-active").forEach(el => el.classList.remove("gm-active"));
                    cItem.classList.add("gm-active");
                    if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                        window.GMObjectHandler.attachTo(cItem);
                    }
                    cItem.scrollIntoView({ behavior: "smooth", block: "nearest" });
                    renderFrameComponentsList();
                });
                subRow.appendChild(subLabel);
                listEl.appendChild(subRow);
            });
        });
    }

    function syncFromAntigravityDB(statusEl) {
        const projectId = getCurrentProjectId();
        if (!projectId) {
            showStatus(statusEl, "error", "Project ID tidak ditemukan.");
            return;
        }

        showStatus(statusEl, "info", "Menyinkronkan dari database...");

        Promise.all([
            fetch("api/get_frames.php?project_id=" + encodeURIComponent(projectId)).then(r => r.json()),
            fetch("api/get_components.php").then(r => r.json())
        ])
            .then(([frameRes, compRes]) => {
                const frameList = Array.isArray(frameRes)
                    ? frameRes
                    : (frameRes && Array.isArray(frameRes.frames) ? frameRes.frames : []);

                frameList.forEach(f => {
                    let coreEl = document.getElementById("object-core-" + f.frame_id);
                    if (!coreEl && typeof window.createFrameUI === "function") {
                        window.createFrameUI(f, false);
                        coreEl = document.getElementById("object-core-" + f.frame_id);
                    }
                    if (coreEl && typeof f.core_content === "string") {
                        coreEl.innerHTML = f.core_content;
                        if (typeof window.initLoadedObjects === "function") {
                            window.initLoadedObjects(coreEl);
                        }
                    }
                });

                refreshWorkspaceAfterChange();
                syncProjectAiMemory();

                if (compRes && compRes.status === "OK") {
                    document.dispatchEvent(new CustomEvent("gm-refresh-components"));
                }

                showStatus(statusEl, "success", "✓ Tersinkronisasi ke Object Core.");
            })
            .catch(() => {
                showStatus(statusEl, "error", "Gagal menyinkronkan dari database.");
            });
    }

    function showStatus(el, type, text) {
        if (!el) return;
        el.style.display = "block";
        el.textContent = text;
        if (type === "error") {
            el.style.background = "#fef2f2";
            el.style.color = "#dc2626";
            el.style.borderColor = "#fecaca";
        } else if (type === "success") {
            el.style.background = "#f0fdf4";
            el.style.color = "#16a34a";
            el.style.borderColor = "#bbf7d0";
        } else {
            el.style.background = "#eff6ff";
            el.style.color = "#2563eb";
            el.style.borderColor = "#bfdbfe";
        }
    }

    function makeDraggable(panel, handle) {
        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;

        handle.addEventListener("mousedown", e => {
            if (e.target.closest("button")) return;
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            const rect = panel.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;
            handle.style.cursor = "grabbing";
            document.body.style.userSelect = "none";
            e.preventDefault();
        });

        window.addEventListener("mousemove", e => {
            if (!isDragging) return;
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            const maxLeft = Math.max(0, window.innerWidth - panel.offsetWidth - 8);
            const maxTop = Math.max(0, window.innerHeight - 40);
            const nextLeft = Math.min(Math.max(8, initialLeft + dx), maxLeft);
            const nextTop = Math.min(Math.max(8, initialTop + dy), maxTop);

            panel.style.left = nextLeft + "px";
            panel.style.top = nextTop + "px";
            panel.style.right = "auto";
        });

        window.addEventListener("mouseup", () => {
            if (!isDragging) return;
            isDragging = false;
            handle.style.cursor = "grab";
            document.body.style.userSelect = "";
            try {
                localStorage.setItem("gm_ai_panel_pos", JSON.stringify({
                    left: panel.style.left,
                    top: panel.style.top
                }));
            } catch (_) {}
        });
    }

    function compressImageFile(file, maxWidth = 1600, maxHeight = 1600, quality = 0.85) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type || !file.type.startsWith("image/")) {
                return reject(new Error("File yang dipilih bukan gambar"));
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                const dataUrl = e.target.result;
                if (file.size < 350 * 1024) {
                    const parts = dataUrl.split(",");
                    const mime = file.type || "image/png";
                    return resolve({
                        data: parts[1] || "",
                        mime_type: mime,
                        name: file.name || "referensi.png",
                        sizeStr: (file.size / 1024).toFixed(1) + " KB",
                        dataUrl: dataUrl
                    });
                }
                const img = new Image();
                img.onload = function () {
                    let width = img.width;
                    let height = img.height;
                    if (width > maxWidth || height > maxHeight) {
                        if (width > height) {
                            height = Math.round((height * maxWidth) / width);
                            width = maxWidth;
                        } else {
                            width = Math.round((width * maxHeight) / height);
                            height = maxHeight;
                        }
                    }
                    const canvas = document.createElement("canvas");
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0, width, height);
                    const compressedUrl = canvas.toDataURL("image/jpeg", quality);
                    const parts = compressedUrl.split(",");
                    const estimatedSize = Math.round((parts[1].length * 3) / 4);
                    resolve({
                        data: parts[1] || "",
                        mime_type: "image/jpeg",
                        name: file.name || "referensi.jpg",
                        sizeStr: (estimatedSize / 1024).toFixed(1) + " KB",
                        dataUrl: compressedUrl
                    });
                };
                img.onerror = () => reject(new Error("Gagal membaca gambar"));
                img.src = dataUrl;
            };
            reader.onerror = () => reject(new Error("Gagal membaca file"));
            reader.readAsDataURL(file);
        });
    }



    const Utils = {
        PRESET_PROMPTS,
        getCurrentProjectId,
        getActiveObjectCore,
        getActiveFrameId,
        cleanEmojis,
        getSelectedObject,
        getTargetContainer,
        collectAllFramesSnapshot,
        syncProjectAiMemory,
        updateMemoryBadgeUI,
        refreshWorkspaceAfterChange,
        summarizeElementLabel,
        renderFrameComponentsList,
        syncFromAntigravityDB,
        showStatus,
        makeDraggable,
        compressImageFile
    };

    window.GreymistAI.Utils = Utils;
    Object.assign(window, Utils);

})(window);
