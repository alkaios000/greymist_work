/**
 * Greymist Workspace — Minimalist Draggable Gemini AI Builder, Frame Reader & Component Remover
 * Fitur utama:
 * 1. Membaca seluruh isi frame aktif (#object-core-{frame_id}) dan objek yang dipilih (.gm-object.gm-active).
 * 2. Menambah komponen baru, mengedit elemen yang ada di dalam frame, atau menghapus komponen via instruksi AI (Prompt).
 * 3. Dilengkapi panel "Isi Frame" langsung di floating widget untuk melihat daftar komponen di dalam frame, memilihnya, atau menghapusnya (per komponen / hapus objek aktif / kosongkan frame).
 */
(function () {
    "use strict";

    const PRESET_PROMPTS = [
        { label: "Hero", prompt: "Buat Hero Section modern dengan badge, judul besar, deskripsi singkat, dan 2 tombol CTA" },
        { label: "Navbar", prompt: "Buat Navbar navigasi horizontal dengan logo brand di kiri, menu link di tengah, dan tombol CTA di kanan" },
        { label: "Pricing", prompt: "Buat bagian kartu harga (Pricing Table) 3 kolom: Starter, Pro (Populer), dan Enterprise" },
        { label: "Kontak", prompt: "Buat kartu Formulir Kontak minimalis dengan input Nama, Email, Pesan, dan tombol Kirim" },
        { label: "Footer", prompt: "Buat Footer multi-kolom bersih dengan deskripsi brand, daftar link navigasi, dan hak cipta" },
        { label: "🔍 Baca Frame", prompt: "Baca dan jelaskan komponen apa saja yang ada di dalam frame ini sekarang" },
        { label: "🗑️ Hapus...", prompt: "Hapus bagian " }
    ];

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

    function refreshWorkspaceAfterChange() {
        document.dispatchEvent(new CustomEvent("gm-dom-tab-activated"));
        if (typeof window.saveProject === "function") {
            window.saveProject();
        }
        renderFrameComponentsList();
    }

    function injectHtmlIntoCanvas(htmlString, targetMode) {
        const container = getTargetContainer(targetMode);
        if (!container) {
            alert("Frame atau Object Core tidak ditemukan. Buat atau pilih frame terlebih dahulu.");
            return 0;
        }

        const temp = document.createElement("div");
        temp.innerHTML = htmlString.trim();

        temp.querySelectorAll("*").forEach(el => {
            const tag = el.tagName.toLowerCase();
            if (!["script", "style", "br", "hr"].includes(tag)) {
                el.classList.add("gm-object");
            }
        });

        const children = Array.from(temp.children);
        let insertedCount = 0;

        children.forEach(child => {
            container.appendChild(child);
            if (typeof window.initLoadedObjects === "function") {
                window.initLoadedObjects(child);
            }
            insertedCount++;
        });

        refreshWorkspaceAfterChange();
        return insertedCount;
    }

    function replaceFrameHtml(htmlString) {
        const core = getActiveObjectCore();
        if (!core) return false;

        core.innerHTML = (htmlString || "").trim();
        core.querySelectorAll("*").forEach(el => {
            const tag = el.tagName.toLowerCase();
            if (!["script", "style", "br", "hr"].includes(tag)) {
                el.classList.add("gm-object");
            }
        });

        if (typeof window.initLoadedObjects === "function") {
            window.initLoadedObjects(core);
        }

        refreshWorkspaceAfterChange();
        return true;
    }

    function summarizeElementLabel(el, idx) {
        const tag = el.tagName.toLowerCase();
        const heading = el.querySelector("h1, h2, h3, h4, strong, span, a");
        let snippet = heading ? heading.textContent.trim() : el.textContent.trim();
        snippet = snippet.replace(/\s+/g, " ");
        if (snippet.length > 26) {
            snippet = snippet.substring(0, 26) + "…";
        }
        return `${idx + 1}. <${tag}> ${snippet || "(elemen visual)"}`;
    }

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
            labelBtn.title = "Klik untuk memilih komponen ini di kanvas";
            labelBtn.addEventListener("click", () => {
                document.querySelectorAll(".gm-object.gm-active").forEach(el => el.classList.remove("gm-active"));
                child.classList.add("gm-active");
                child.scrollIntoView({ behavior: "smooth", block: "nearest" });
                renderFrameComponentsList();
            });

            const delBtn = document.createElement("button");
            delBtn.type = "button";
            delBtn.className = "gm-ai-frame-item-del";
            delBtn.title = "Hapus komponen ini dari frame";
            delBtn.innerHTML = `<i class="fas fa-trash-can"></i>`;
            delBtn.addEventListener("click", e => {
                e.stopPropagation();
                child.remove();
                refreshWorkspaceAfterChange();
                const statusEl = document.getElementById("gm-ai-status-msg");
                showStatus(statusEl, "success", `✓ Komponen #${idx + 1} dihapus dari frame.`);
            });

            row.appendChild(labelBtn);
            row.appendChild(delBtn);
            listEl.appendChild(row);
        });
    }

    function syncFromAntigravityDB(statusEl) {
        const params = new URLSearchParams(window.location.search);
        const projectId = params.get("project_id");
        if (!projectId) {
            showStatus(statusEl, "error", "Project ID tidak ditemukan di URL.");
            return;
        }

        showStatus(statusEl, "info", "Menyinkronkan dari database...");

        Promise.all([
            fetch("api/get_frames.php?project_id=" + encodeURIComponent(projectId)).then(r => r.json()),
            fetch("api/get_components.php").then(r => r.json())
        ])
            .then(([frameRes, compRes]) => {
                if (frameRes && frameRes.status === "OK" && Array.isArray(frameRes.frames)) {
                    frameRes.frames.forEach(f => {
                        const coreEl = document.getElementById("object-core-" + f.frame_id);
                        if (coreEl && typeof f.core_content === "string") {
                            coreEl.innerHTML = f.core_content;
                            if (f.core_style) {
                                coreEl.style.cssText = f.core_style;
                            }
                            if (typeof window.initLoadedObjects === "function") {
                                window.initLoadedObjects(coreEl);
                            }
                        }
                    });
                    refreshWorkspaceAfterChange();
                }

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

    function createPluginUI() {
        if (document.getElementById("gm-ai-plugin-toggle")) return;

        const styleEl = document.createElement("style");
        styleEl.textContent = `
            .gm-ai-plugin-toggle {
                position: absolute;
                top: 105px;
                left: 95px;
                z-index: 1000;
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
                border: 1px solid #d8d8d8;
                border-radius: 6px;
                color: #555555;
                cursor: pointer;
                font-size: 13px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.15);
                transition: all 0.2s ease, transform 0.3s ease-in-out;
                box-sizing: border-box;
            }
            .gm-ai-plugin-toggle:hover {
                background: #e8f0fe;
                color: #0078ff;
                border-color: #0078ff;
            }
            .gm-ai-plugin-toggle.on {
                background: #0078ff;
                color: #ffffff;
                border-color: #0078ff;
                box-shadow: 0 0 6px rgba(0, 120, 255, 0.4);
            }
            body.gm-frame-hidden .gm-ai-plugin-toggle {
                transform: translateY(-90px);
            }
            body.gm-fullscreen-preview .gm-ai-plugin-toggle,
            body.gm-fullscreen-preview #gm-ai-floating-panel {
                display: none !important;
            }
            #gm-ai-floating-panel {
                position: fixed;
                top: 148px;
                left: 275px;
                width: 295px;
                background: #ffffff;
                border: 1px solid #dcdcdc;
                border-radius: 8px;
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.14);
                z-index: 9500;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                color: #222222;
                display: none;
                flex-direction: column;
                overflow: hidden;
                box-sizing: border-box;
            }
            #gm-ai-floating-panel.minimized .gm-ai-body {
                display: none !important;
            }
            .gm-ai-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 7px 10px;
                background: #f7f8fa;
                border-bottom: 1px solid #ebebeb;
                cursor: grab;
                user-select: none;
            }
            .gm-ai-header-title {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 11.5px;
                font-weight: 600;
                color: #333333;
            }
            .gm-ai-header-title i {
                color: #0078ff;
                font-size: 12px;
            }
            .gm-ai-header-actions {
                display: flex;
                align-items: center;
                gap: 2px;
            }
            .gm-ai-icon-btn {
                width: 22px;
                height: 22px;
                border: none;
                background: transparent;
                color: #666666;
                border-radius: 4px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                padding: 0;
            }
            .gm-ai-icon-btn:hover {
                background: #e8eaed;
                color: #111111;
            }
            .gm-ai-body {
                padding: 10px;
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .gm-ai-chip-list {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            .gm-ai-chip {
                background: #f3f4f6;
                color: #4b5563;
                border: 1px solid #e5e7eb;
                border-radius: 4px;
                padding: 3px 7px;
                font-size: 10.5px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.12s ease;
            }
            .gm-ai-chip:hover {
                background: #e8f0fe;
                color: #0078ff;
                border-color: #b6d4fe;
            }
            .gm-ai-field {
                width: 100%;
                box-sizing: border-box;
                background: #fafafa;
                color: #222222;
                border: 1px solid #dcdcdc;
                border-radius: 5px;
                padding: 6px 8px;
                font-size: 11.5px;
                outline: none;
                font-family: inherit;
            }
            .gm-ai-field:focus {
                border-color: #0078ff;
                background: #ffffff;
            }
            .gm-ai-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .gm-ai-btn-primary {
                background: #0078ff;
                color: #ffffff;
                border: none;
                border-radius: 5px;
                padding: 7px 10px;
                font-size: 11.5px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 5px;
                transition: background 0.15s ease;
            }
            .gm-ai-btn-primary:hover {
                background: #0062d1;
            }
            .gm-ai-btn-primary:disabled {
                opacity: 0.65;
                cursor: not-allowed;
            }
            .gm-ai-btn-secondary {
                background: #f3f4f6;
                color: #374151;
                border: 1px solid #dcdcdc;
                border-radius: 5px;
                padding: 5px 8px;
                font-size: 10.5px;
                font-weight: 500;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
            }
            .gm-ai-btn-secondary:hover {
                background: #e5e7eb;
            }
            .gm-ai-btn-danger {
                background: #fef2f2;
                color: #dc2626;
                border: 1px solid #fecaca;
                border-radius: 5px;
                padding: 4px 7px;
                font-size: 10px;
                font-weight: 600;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 4px;
            }
            .gm-ai-btn-danger:hover {
                background: #fee2e2;
            }
            .gm-ai-frame-box {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 6px 8px;
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .gm-ai-frame-items {
                max-height: 105px;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                gap: 3px;
            }
            .gm-ai-frame-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 4px;
                padding: 3px 6px;
                font-size: 10.5px;
                color: #334155;
            }
            .gm-ai-frame-item-label {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                cursor: pointer;
                flex: 1;
            }
            .gm-ai-frame-item-label:hover {
                color: #0078ff;
            }
            .gm-ai-frame-item-del {
                background: transparent;
                border: none;
                color: #ef4444;
                cursor: pointer;
                padding: 2px 4px;
                font-size: 10px;
                border-radius: 3px;
            }
            .gm-ai-frame-item-del:hover {
                background: #fee2e2;
            }
        `;
        document.head.appendChild(styleEl);

        // 1. Tombol Ikon Minimalis di #gm-main
        const mainEl = document.getElementById("gm-main") || document.body;
        const toggleBtn = document.createElement("button");
        toggleBtn.id = "gm-ai-plugin-toggle";
        toggleBtn.className = "gm-ai-plugin-toggle";
        toggleBtn.title = "Gemini AI Studio (Baca, Buat & Hapus Komponen Frame)";
        toggleBtn.innerHTML = `<i class="fas fa-wand-magic-sparkles"></i>`;
        mainEl.appendChild(toggleBtn);

        // 2. Panel Floating Minimalis & Draggable
        const panel = document.createElement("div");
        panel.id = "gm-ai-floating-panel";

        try {
            const savedPos = JSON.parse(localStorage.getItem("gm_ai_panel_pos") || "null");
            if (savedPos && savedPos.left && savedPos.top) {
                panel.style.left = savedPos.left;
                panel.style.top = savedPos.top;
            }
        } catch (_) {}

        const presetChipsHtml = PRESET_PROMPTS.map((item, idx) =>
            `<button type="button" class="gm-ai-chip" data-idx="${idx}">${item.label}</button>`
        ).join("");

        panel.innerHTML = `
            <div class="gm-ai-header" id="gm-ai-drag-handle" title="Geser untuk memindahkan panel">
                <div class="gm-ai-header-title">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span>Gemini AI Builder</span>
                </div>
                <div class="gm-ai-header-actions">
                    <button type="button" class="gm-ai-icon-btn" id="gm-ai-toggle-key-btn" title="Pengaturan API Key">
                        <i class="fas fa-key"></i>
                    </button>
                    <button type="button" class="gm-ai-icon-btn" id="gm-ai-minimize-btn" title="Minimize / Expand">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="gm-ai-icon-btn" id="gm-ai-close-btn" title="Tutup">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="gm-ai-body">
                <!-- Inspector Isi Frame Aktif (Baca & Hapus Komponen) -->
                <div class="gm-ai-frame-box">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#475569;">
                            <i class="fas fa-layer-group" style="color:#0078ff;"></i>
                            <span>Isi Frame Aktif (<span id="gm-ai-frame-count">0</span>)</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <button type="button" id="gm-ai-del-selected-btn" class="gm-ai-btn-danger" title="Hapus objek yang sedang dipilih (.gm-active)">
                                <i class="fas fa-eraser"></i> Hapus Aktif
                            </button>
                            <button type="button" id="gm-ai-clear-frame-btn" class="gm-ai-btn-danger" title="Kosongkan seluruh komponen di dalam frame ini">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="gm-ai-frame-items" id="gm-ai-frame-items-list"></div>
                </div>

                <!-- Preset Cepat -->
                <div class="gm-ai-chip-list">
                    ${presetChipsHtml}
                </div>

                <!-- Prompt -->
                <textarea id="gm-ai-prompt-input" class="gm-ai-field" rows="2" placeholder="Buat komponen, edit, atau ketik 'Hapus navbar' / 'Baca isi frame'..." style="resize:vertical;line-height:1.4;"></textarea>

                <!-- Model Gratis & Target -->
                <div class="gm-ai-row">
                    <select id="gm-ai-model-select" class="gm-ai-field" title="Pilih Model Gratis (Free Tier)">
                        <option value="gemini-3.5-flash-lite">3.5 Flash-Lite (Gratis)</option>
                        <option value="gemini-3.6-flash">3.6 Flash (Gratis)</option>
                        <option value="gemini-3.1-flash-lite">3.1 Flash-Lite (Gratis)</option>
                        <option value="gemini-2.5-flash-lite">2.5 Flash-Lite (Gratis)</option>
                        <option value="local-free">Mode Lokal (Offline)</option>
                    </select>
                    <select id="gm-ai-target-mode" class="gm-ai-field" title="Target Injeksi">
                        <option value="core">Ke Frame Aktif</option>
                        <option value="selected">Ke Objek Aktif</option>
                    </select>
                </div>

                <!-- Opsi Simpan ke Pustaka Komponen -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <label style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555555;cursor:pointer;white-space:nowrap;">
                        <input type="checkbox" id="gm-ai-save-library" checked style="accent-color:#0078ff;margin:0;">
                        <span>Simpan Komponen</span>
                    </label>
                    <input type="text" id="gm-ai-comp-name" class="gm-ai-field" placeholder="Nama (opsional)" style="padding:4px 7px;font-size:11px;max-width:135px;">
                </div>

                <!-- Panel API Key (Tersembunyi secara default) -->
                <div id="gm-ai-key-panel" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:5px;padding:7px;">
                    <div style="display:flex;gap:5px;">
                        <input type="password" id="gm-ai-api-key-input" class="gm-ai-field" placeholder="GEMINI_API_KEY..." style="padding:4px 7px;font-size:11px;">
                        <button type="button" id="gm-ai-save-key-btn" class="gm-ai-btn-secondary" style="white-space:nowrap;">Simpan</button>
                    </div>
                </div>

                <!-- Status Ringkas -->
                <div id="gm-ai-status-msg" style="display:none;padding:5px 8px;border:1px solid transparent;border-radius:5px;font-size:10.5px;line-height:1.35;max-height:85px;overflow-y:auto;"></div>

                <!-- Tombol Aksi -->
                <div style="display:flex;gap:5px;">
                    <button type="button" id="gm-ai-generate-btn" class="gm-ai-btn-primary" style="flex:1;">
                        <i class="fas fa-bolt"></i> <span>Jalankan AI</span>
                    </button>
                    <button type="button" id="gm-ai-sync-antigravity-btn" class="gm-ai-btn-secondary" title="Sinkronkan komponen dari Antigravity IDE (Database)">
                        <i class="fas fa-rotate"></i> <span>Sync DB</span>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(panel);

        // Aktifkan fitur Drag pada header panel
        const dragHandle = document.getElementById("gm-ai-drag-handle");
        makeDraggable(panel, dragHandle);

        const statusEl = document.getElementById("gm-ai-status-msg");

        const togglePanel = (forceOpen) => {
            const isVisible = panel.style.display === "flex";
            const nextState = typeof forceOpen === "boolean" ? forceOpen : !isVisible;
            panel.style.display = nextState ? "flex" : "none";
            toggleBtn.classList.toggle("on", nextState);
            if (nextState) {
                panel.classList.remove("minimized");
                renderFrameComponentsList();
                const input = document.getElementById("gm-ai-prompt-input");
                if (input) input.focus();
            }
        };

        toggleBtn.addEventListener("click", () => togglePanel());
        document.getElementById("gm-ai-close-btn").addEventListener("click", () => togglePanel(false));

        // Minimize / Expand
        const minBtn = document.getElementById("gm-ai-minimize-btn");
        minBtn.addEventListener("click", () => {
            panel.classList.toggle("minimized");
            const icon = minBtn.querySelector("i");
            if (icon) {
                icon.className = panel.classList.contains("minimized") ? "fas fa-expand" : "fas fa-minus";
            }
        });

        // Hubungkan dengan menu kiri atas GM -> Gemini AI Studio
        const menuTrigger = document.getElementById("gm-menu-open-ai-studio");
        if (menuTrigger) {
            menuTrigger.addEventListener("click", () => togglePanel(true));
        }

        // Perbarui daftar Isi Frame setiap kali klik di kanvas atau pindah frame
        document.addEventListener("click", () => {
            if (panel.style.display === "flex") {
                setTimeout(renderFrameComponentsList, 60);
            }
        });

        // Tombol Hapus Objek yang Sedang Dipilih (.gm-active)
        document.getElementById("gm-ai-del-selected-btn").addEventListener("click", () => {
            const selected = getSelectedObject();
            if (!selected) {
                showStatus(statusEl, "error", "Pilih/klik objek di kanvas terlebih dahulu.");
                return;
            }
            selected.remove();
            refreshWorkspaceAfterChange();
            showStatus(statusEl, "success", "✓ Objek aktif berhasil dihapus dari frame.");
        });

        // Tombol Kosongkan Frame Aktif
        document.getElementById("gm-ai-clear-frame-btn").addEventListener("click", () => {
            const core = getActiveObjectCore();
            if (!core || core.children.length === 0) {
                showStatus(statusEl, "info", "Frame sudah kosong.");
                return;
            }
            if (!confirm("Kosongkan semua komponen di dalam frame aktif ini?")) return;
            core.innerHTML = "";
            refreshWorkspaceAfterChange();
            showStatus(statusEl, "success", "✓ Seluruh isi frame telah dikosongkan.");
        });

        // Klik Preset
        panel.querySelectorAll(".gm-ai-chip").forEach(chip => {
            chip.addEventListener("click", () => {
                const idx = parseInt(chip.dataset.idx, 10);
                const preset = PRESET_PROMPTS[idx];
                if (!preset) return;
                const promptInput = document.getElementById("gm-ai-prompt-input");
                promptInput.value = preset.prompt;
                promptInput.focus();
                if (!preset.label.includes("Baca") && !preset.label.includes("Hapus")) {
                    document.getElementById("gm-ai-comp-name").value = preset.label;
                }
            });
        });

        // Toggle input API Key
        const keyPanel = document.getElementById("gm-ai-key-panel");
        document.getElementById("gm-ai-toggle-key-btn").addEventListener("click", () => {
            keyPanel.style.display = keyPanel.style.display === "none" ? "block" : "none";
        });

        // Simpan API Key ke .env
        document.getElementById("gm-ai-save-key-btn").addEventListener("click", () => {
            const apiKey = document.getElementById("gm-ai-api-key-input").value.trim();
            if (!apiKey) return;

            fetch("index.php?action=ai_save_api_key", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ api_key: apiKey })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status === "OK") {
                        showStatus(statusEl, "success", "✓ API Key disimpan ke .env");
                        keyPanel.style.display = "none";
                    } else {
                        showStatus(statusEl, "error", res.msg || "Gagal menyimpan API Key.");
                    }
                });
        });

        // Sync dari Antigravity DB
        document.getElementById("gm-ai-sync-antigravity-btn").addEventListener("click", () => {
            syncFromAntigravityDB(statusEl);
        });

        // Jalankan AI (Baca Frame / Tambah Komponen / Edit / Hapus Komponen)
        const generateBtn = document.getElementById("gm-ai-generate-btn");
        generateBtn.addEventListener("click", () => {
            const prompt = document.getElementById("gm-ai-prompt-input").value.trim();
            const selectedModel = document.getElementById("gm-ai-model-select").value;
            const targetMode = document.getElementById("gm-ai-target-mode").value;
            const compName = document.getElementById("gm-ai-comp-name").value.trim() || "AI Component";
            const saveToLibrary = document.getElementById("gm-ai-save-library").checked;

            if (!prompt) {
                showStatus(statusEl, "error", "Isi instruksi atau klik salah satu chip di atas.");
                return;
            }

            const activeCore = getActiveObjectCore();
            const currentFrameHtml = activeCore ? activeCore.innerHTML : "";
            const selectedObj = getSelectedObject();
            const selectedObjectHtml = selectedObj ? selectedObj.outerHTML : "";

            generateBtn.disabled = true;
            generateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Proses...</span>`;
            showStatus(statusEl, "info", "AI sedang membaca frame & memproses instruksi...");

            fetch("api/ai_generate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    prompt: prompt,
                    model: selectedModel,
                    component_name: compName,
                    save_to_library: saveToLibrary,
                    current_frame_html: currentFrameHtml,
                    selected_object_html: selectedObjectHtml
                })
            })
                .then(r => r.json())
                .then(res => {
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;

                    if (res.status !== "OK") {
                        showStatus(statusEl, "error", res.msg || "Gagal memproses instruksi AI.");
                        return;
                    }

                    const op = res.operation || "append";

                    if (op === "explain") {
                        showStatus(statusEl, "info", res.summary || res.msg || "Frame dianalisis.");
                        renderFrameComponentsList();
                    } else if (op === "replace_frame") {
                        replaceFrameHtml(res.html || "");
                        showStatus(statusEl, "success", `✓ ${res.msg || "Isi frame berhasil diperbarui."}`);
                    } else {
                        const inserted = injectHtmlIntoCanvas(res.html || "", targetMode);
                        showStatus(statusEl, "success", `✓ Ditambahkan ke Frame (${inserted} blok).`);
                    }
                })
                .catch(err => {
                    console.error("AI Generate Error:", err);
                    generateBtn.disabled = false;
                    generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                    showStatus(statusEl, "error", "Gagal menghubungi endpoint AI.");
                });
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", createPluginUI);
    } else {
        createPluginUI();
    }
})();
