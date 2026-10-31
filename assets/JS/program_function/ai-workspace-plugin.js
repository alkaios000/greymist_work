/**
 * Greymist Workspace — Minimalist Draggable Gemini AI Builder
 * File: assets/JS/program_function/ai-workspace-plugin.js
 * Modul: Entry Controller Utama & Antarmuka UI Modal Greymist AI.
 * 
 * Modul pendukung yang dimuat di assets/JS/program_function/ai/:
 * - ai-utils.js          : Selektor DOM, cache memori proyek, kompresi gambar, & helper.
 * - ai-dom-engine.js     : Mesin konstruksi live unstyled-to-styled, diffing, & attribute patcher.
 * - ai-context-menu.js   : Prompt editor pada menu klik kanan (.gm-object-menu).
 * - ai-voice-engine.js   : Sound to Design (SpeechRecognition & Groq Whisper fallback).
 * - ai-settings.js       : Panel konfigurasi provider AI (Groq, Gemini, OpenRouter, Antigravity Bridge).
 */
(function () {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};
    window.attachedReferenceImages = [];
    function createPluginUI() {
        if (document.getElementById("gm-ai-plugin-toggle")) return;

        const styleEl = document.createElement("style");
        styleEl.textContent = `
            .gm-ai-live-morph {
                transition: background-color 0.16s ease, color 0.16s ease, padding 0.16s ease, border-radius 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
            }
            .gm-ai-fade-in {
                animation: gmAiFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1) both;
            }
            @keyframes gmAiFadeIn {
                from { opacity: 0; transform: translateY(3px); }
                to { opacity: 1; transform: translateY(0); }
            }
            /* Posisi di bawah #gm-interaction-toggle (top: 145px, left: 15px) agar TIDAK menutupi Breakpoint Toolbar di (top: 105px, left: 95px) */
            .gm-ai-plugin-toggle {
                position: absolute;
                top: 145px;
                left: 15px;
                z-index: 1000;
                width: 34px;
                height: 34px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
                border: 1px solid #d8d8d8;
                border-radius: 6px;
                color: #0078ff;
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
                top: 145px;
                left: 58px;
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
                max-height: 95px;
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
                padding: 4px 7px;
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
            .gm-ai-memory-pill {
                font-size: 10px;
                font-weight: 600;
                color: #0369a1;
                background: #e0f2fe;
                border: 1px solid #bae6fd;
                border-radius: 4px;
                padding: 2px 6px;
                cursor: help;
                white-space: nowrap;
            }

            /* Styling Sound to Design (Voice-to-Design) & Microphone */
            .gm-ai-prompt-wrap {
                position: relative;
                width: 100%;
            }
            .gm-ai-mic-btn {
                position: absolute;
                right: 6px;
                bottom: 8px;
                width: 26px;
                height: 26px;
                border-radius: 50%;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #475569;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11.5px;
                transition: all 0.2s ease;
                box-shadow: 0 1px 3px rgba(0,0,0,0.06);
                z-index: 2;
            }
            .gm-ai-mic-btn:hover {
                background: #eff6ff;
                color: #0078ff;
                border-color: #93c5fd;
                transform: scale(1.08);
            }
            .gm-ai-mic-btn.gm-ai-listening {
                background: #ef4444 !important;
                color: #ffffff !important;
                border-color: #dc2626 !important;
                animation: gm-mic-pulse 1.2s infinite ease-in-out;
                box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.25);
            }
            .gm-ai-sound-btn {
                background: #f1f5f9;
                color: #334155;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
                padding: 4px 8px;
                font-size: 11px;
                font-weight: 500;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                transition: all 0.15s ease;
            }
            .gm-ai-sound-btn:hover {
                background: #f0fdf4;
                color: #16a34a;
                border-color: #86efac;
            }
            .gm-ai-sound-btn.gm-ai-listening {
                background: #fee2e2 !important;
                color: #dc2626 !important;
                border-color: #fca5a5 !important;
                animation: gm-mic-pulse 1.2s infinite ease-in-out;
            }
            @keyframes gm-mic-pulse {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
                50% { transform: scale(1.08); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.15); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
            }

            /* Styling Upload Foto / Screenshot Referensi UI */
            .gm-ai-upload-zone {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .gm-ai-upload-bar {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .gm-ai-upload-btn {
                background: #f1f5f9;
                color: #334155;
                border: 1px dashed #cbd5e1;
                border-radius: 4px;
                padding: 4px 8px;
                font-size: 11px;
                font-weight: 500;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 5px;
                transition: all 0.15s ease;
            }
            .gm-ai-upload-btn:hover {
                background: #e0f2fe;
                color: #0284c7;
                border-color: #38bdf8;
            }
            .gm-ai-upload-hint {
                font-size: 10px;
                color: #94a3b8;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .gm-ai-img-preview {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 6px 8px;
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .gm-ai-preview-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 10.5px;
                font-weight: 600;
                color: #334155;
            }
            .gm-ai-clear-all-btn {
                background: none;
                border: none;
                color: #ef4444;
                font-size: 10px;
                font-weight: 500;
                cursor: pointer;
                padding: 0;
                text-decoration: underline;
            }
            .gm-ai-clear-all-btn:hover {
                color: #b91c1c;
            }
            .gm-ai-preview-list {
                display: flex;
                flex-direction: column;
                gap: 4px;
                max-height: 125px;
                overflow-y: auto;
            }
            .gm-ai-preview-item {
                display: flex;
                align-items: center;
                gap: 7px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 4px;
                padding: 3px 6px;
            }
            .gm-ai-preview-item img {
                width: 32px;
                height: 32px;
                object-fit: cover;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                flex-shrink: 0;
            }
            .gm-ai-preview-info {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
            }
            .gm-ai-preview-name {
                font-size: 10.5px;
                font-weight: 600;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .gm-ai-preview-size {
                font-size: 9.5px;
                color: #64748b;
            }
            .gm-ai-remove-img-btn {
                background: #fee2e2;
                color: #ef4444;
                border: none;
                border-radius: 4px;
                width: 20px;
                height: 20px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                transition: background 0.15s ease;
                flex-shrink: 0;
            }
            .gm-ai-remove-img-btn:hover {
                background: #fecaca;
                color: #dc2626;
            }
            #gm-ai-floating-panel.gm-ai-drop-active {
                outline: 2px dashed #0078ff !important;
                background: #eff6ff !important;
            }

            /* Styling khusus untuk AI Edit Object di dalam Menu Klik Kanan (.gm-object-menu) */
            .gm-ai-context-box {
                padding: 8px 10px;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                min-width: 235px;
                box-sizing: border-box;
                font-family: 'Inter', sans-serif;
            }
            .gm-ai-ctx-header {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 11px;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 6px;
            }
            .gm-ai-ctx-header i {
                color: #0078ff;
            }
            .gm-ai-ctx-header code {
                margin-left: auto;
                background: #e0f2fe;
                color: #0369a1;
                padding: 1px 5px;
                border-radius: 4px;
                font-size: 10px;
            }
            .gm-ai-ctx-input-row {
                display: flex;
                gap: 4px;
            }
            .gm-ai-ctx-input-row input {
                flex: 1;
                padding: 5px 7px;
                font-size: 11.5px;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
                outline: none;
                background: #ffffff;
                color: #0f172a;
            }
            .gm-ai-ctx-input-row input:focus {
                border-color: #0078ff;
            }
            .gm-ai-ctx-input-row button {
                background: #0078ff;
                color: #ffffff;
                border: none;
                border-radius: 4px;
                width: 28px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
            }
            .gm-ai-ctx-input-row button:hover {
                background: #0062d1;
            }
        `;
        document.head.appendChild(styleEl);

        // 1. Tombol Ikon Minimalis di #gm-main (di bawah #gm-interaction-toggle agar bebas dari Breakpoint Toolbar)
        const mainEl = document.getElementById("gm-main") || document.body;
        const toggleBtn = document.createElement("button");
        toggleBtn.id = "gm-ai-plugin-toggle";
        toggleBtn.className = "gm-ai-plugin-toggle";
        toggleBtn.title = "Gemini AI Studio (Baca, Buat, Edit & Tambah Frame Otomatis)";
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
                <div class="gm-ai-header-title" style="display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span>Greymist AI</span>
                    <span id="gm-ai-active-provider-pill" class="gm-ai-provider-pill" style="font-size:9.5px;padding:2px 7px;border-radius:10px;background:#fef3c7;color:#d97706;font-weight:700;cursor:pointer;" title="Klik untuk membuka Pengaturan Provider AI">Groq 70B</span>
                </div>
                <div class="gm-ai-header-actions">
                    <button type="button" class="gm-ai-icon-btn" id="gm-ai-toggle-settings-btn" title="Pengaturan Provider AI (Cloud & Lokal)">
                        <i class="fas fa-sliders-h"></i>
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
                <!-- Badge Memori Tersembunyi untuk Kompatibilitas Sistem -->
                <span id="gm-ai-memory-badge" style="display:none;"></span>

                <!-- Panel Pengaturan Provider AI (Expandable Modal) -->
                <div id="gm-ai-settings-panel" style="display:none;background:#f8fafc;border:1px solid #cbd5e1;border-radius:7px;padding:9px;margin-bottom:8px;box-shadow:0 3px 10px rgba(0,0,0,0.06);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;padding-bottom:5px;border-bottom:1px solid #e2e8f0;">
                        <span style="font-size:11px;font-weight:700;color:#1e293b;"><i class="fas fa-sliders-h" style="color:#0078ff;"></i> Pengaturan Provider AI</span>
                        <button type="button" id="gm-ai-close-settings-btn" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:12px;"><i class="fas fa-xmark"></i></button>
                    </div>

                    <!-- Pilihan Provider -->
                    <div style="margin-bottom:7px;">
                        <label style="display:block;font-size:10px;font-weight:700;color:#475569;margin-bottom:3px;">PILIH MESIN AI:</label>
                        <select id="gm-ai-provider-select" class="gm-ai-field" style="width:100%;font-size:11px;padding:4px 6px;font-weight:600;background:#ffffff;border:1px solid #94a3b8;">
                            <option value="groq">🚀 Groq Cloud (Gratis & Super Cepat ~300 t/s)</option>
                            <option value="antigravity">⚡ Antigravity Cloud Bridge (Tanpa API Key)</option>
                            <option value="openrouter">🌐 OpenRouter Cloud (Free Tier)</option>
                            <option value="gemini">💎 Google Gemini (API Key Pribadi)</option>
                            <option value="smart_preset">🛠️ Smart Fallback Lokal (Offline)</option>
                        </select>
                    </div>

                    <!-- Banner Info / Panduan Provider -->
                    <div id="gm-ai-provider-info-box" style="font-size:10px;color:#334155;background:#f1f5f9;border-left:3px solid #0078ff;padding:5px 8px;border-radius:3px;margin-bottom:7px;line-height:1.4;">
                    </div>

                    <!-- Input API Key -->
                    <div id="gm-ai-key-input-row" style="margin-bottom:7px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px;">
                            <label id="gm-ai-key-label" style="font-size:10px;font-weight:700;color:#475569;">API KEY:</label>
                            <span id="gm-ai-key-status" style="font-size:9.5px;color:#16a34a;font-weight:600;"></span>
                        </div>
                        <div style="display:flex;gap:4px;">
                            <input type="password" id="gm-ai-api-key-input" class="gm-ai-field" placeholder="Masukkan API Key..." style="flex:1;padding:4px 7px;font-size:11px;background:#ffffff;">
                            <button type="button" id="gm-ai-toggle-show-key-btn" class="gm-ai-btn-secondary" style="padding:0 7px;background:#ffffff;" title="Lihat/Sembunyikan"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>

                    <!-- Pilihan Model -->
                    <div id="gm-ai-settings-model-row" style="margin-bottom:7px;">
                        <label style="display:block;font-size:10px;font-weight:700;color:#475569;margin-bottom:3px;">PILIH MODEL:</label>
                        <select id="gm-ai-settings-model-select" class="gm-ai-field" style="width:100%;font-size:11px;padding:4px 6px;background:#ffffff;">
                        </select>
                    </div>

                    <!-- Tombol Aksi Settings -->
                    <div style="display:flex;gap:5px;margin-top:7px;">
                        <button type="button" id="gm-ai-test-conn-btn" class="gm-ai-btn-secondary" style="flex:1;font-size:10.5px;padding:5px 6px;" title="Cek respon server cloud">
                            <i class="fas fa-plug"></i> <span>Tes Koneksi</span>
                        </button>
                        <button type="button" id="gm-ai-save-settings-btn" class="gm-ai-btn-primary" style="flex:1;font-size:10.5px;padding:5px 6px;">
                            <i class="fas fa-floppy-disk"></i> <span>Simpan</span>
                        </button>
                    </div>

                    <!-- Hasil Tes Koneksi -->
                    <div id="gm-ai-test-result" style="display:none;margin-top:6px;font-size:10px;padding:5px 7px;border-radius:4px;line-height:1.35;"></div>
                </div>

                <!-- Preset Cepat -->
                <div class="gm-ai-chip-list">
                    ${presetChipsHtml}
                </div>

                <!-- Zona Upload Foto / Screenshot Referensi UI & Sound to Design -->
                <div class="gm-ai-upload-zone" id="gm-ai-upload-zone">
                    <div class="gm-ai-upload-bar">
                        <button type="button" class="gm-ai-upload-btn" id="gm-ai-upload-btn" title="Upload foto atau screenshot referensi UI (Bisa pilih multi-foto sekaligus)">
                            <i class="fa-solid fa-camera"></i> <span>Foto Referensi</span>
                        </button>
                        <button type="button" class="gm-ai-sound-btn" id="gm-ai-voice-bar-btn" title="Sound to Design: Bicara langsung untuk membuat desain UI!">
                            <i class="fa-solid fa-microphone"></i> <span>Sound to Design</span>
                        </button>
                        <button type="button" class="gm-ai-sound-btn" id="gm-ai-assets-bar-btn" title="Aset Media: Hubungkan & pilih gambar yang sudah diupload di menu assets!">
                            <i class="fa-solid fa-images"></i> <span>Aset (<span id="gm-ai-assets-badge-count">0</span>)</span>
                        </button>
                        <input type="file" id="gm-ai-img-file" accept="image/png,image/jpeg,image/webp,image/gif" multiple style="display:none;">
                        <span class="gm-ai-upload-hint"><i class="fa-solid fa-paste"></i> Paste Ctrl+V</span>
                    </div>

                    <!-- Drawer Koleksi Aset Menu Assets -->
                    <div class="gm-ai-assets-drawer" id="gm-ai-assets-drawer" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:8px;margin-top:6px;box-sizing:border-box;">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                            <span style="font-size:11px;font-weight:600;color:#334155;"><i class="fa-solid fa-folder-open" style="color:#0078ff;"></i> Aset dari Menu Assets (<span id="gm-ai-assets-list-count">0</span>)</span>
                            <div style="display:flex;gap:4px;">
                                <button type="button" id="gm-ai-assets-select-all" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:4px;padding:2px 6px;font-size:10px;cursor:pointer;color:#475569;">Pilih Semua</button>
                                <button type="button" id="gm-ai-assets-clear-sel" style="background:#ffffff;border:1px solid #cbd5e1;border-radius:4px;padding:2px 6px;font-size:10px;cursor:pointer;color:#475569;">Batal</button>
                            </div>
                        </div>
                        <div id="gm-ai-assets-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(70px, 1fr));gap:6px;max-height:120px;overflow-y:auto;padding:2px;">
                            <!-- Kartu thumbnail aset terisi otomatis -->
                        </div>
                        <div style="font-size:10px;color:#64748b;margin-top:6px;line-height:1.35;">
                            <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Seluruh gambar dari menu assets otomatis terhubung. Klik gambar di atas jika ingin memprioritaskan aset tertentu.
                        </div>
                    </div>

                    <div class="gm-ai-img-preview" id="gm-ai-img-preview" style="display:none;">
                        <div class="gm-ai-preview-header" id="gm-ai-preview-header">
                            <span id="gm-ai-preview-count"><i class="fa-solid fa-images"></i> 1 Foto Terpilih</span>
                            <button type="button" class="gm-ai-clear-all-btn" id="gm-ai-clear-all-btn">Hapus Semua</button>
                        </div>
                        <div class="gm-ai-preview-list" id="gm-ai-preview-list"></div>
                    </div>
                </div>

                <!-- Prompt Field dengan Mic Sound to Design Bawaan -->
                <div class="gm-ai-prompt-wrap">
                    <textarea id="gm-ai-prompt-input" class="gm-ai-field" rows="2" placeholder="Ketik instruksi atau klik mic 🎙️ untuk bicara..." style="width:100%;box-sizing:border-box;padding-right:36px;resize:vertical;line-height:1.4;"></textarea>
                    <button type="button" id="gm-ai-prompt-mic-btn" class="gm-ai-mic-btn" title="Sound to Design: Bicara sekarang untuk membuat desain otomatis!">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                </div>

                <!-- Model & Target -->
                <div class="gm-ai-row">
                    <select id="gm-ai-model-select" class="gm-ai-field" title="Pilih Model Aktif">
                        <option value="openai/gpt-oss-120b">GPT OSS 120B (Groq LPU)</option>
                    </select>
                    <select id="gm-ai-target-mode" class="gm-ai-field" title="Target Injeksi">
                        <option value="core">Ke Frame Aktif</option>
                        <option value="new_frame">➕ Ke Frame Baru</option>
                        <option value="selected">Ke Dalam Objek Aktif</option>
                    </select>
                </div>

                <!-- Opsi Simpan ke Pustaka Komponen -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <label style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555555;cursor:pointer;white-space:nowrap;">
                        <input type="checkbox" id="gm-ai-save-library" checked style="accent-color:#0078ff;margin:0;">
                        <span>Simpan Komponen</span>
                    </label>
                    <input type="text" id="gm-ai-comp-name" class="gm-ai-field" placeholder="Nama page/komponen" style="padding:4px 7px;font-size:11px;max-width:135px;">
                </div>

                <!-- Opsi Memori Konteks Proyek (Default: OFF / Mode Kilat) -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;background:#f8fafc;padding:5px 8px;border-radius:5px;border:1px solid #e2e8f0;margin-top:4px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#334155;cursor:pointer;user-select:none;margin:0;">
                        <input type="checkbox" id="gm-ai-read-memory" style="accent-color:#0078ff;margin:0;">
                        <span id="gm-ai-speed-indicator" style="font-weight:600;color:#0284c7;">⚡ Mode Kilat (Memori Off)</span>
                    </label>
                    <span style="font-size:9.5px;color:#64748b;cursor:help;" title="Mode Kilat (Default): Respons super cepat ~3-7s tanpa membaca memori proyek. Centang jika butuh AI membaca riwayat & seluruh halaman lain.">Bantuan <i class="fa-solid fa-circle-question"></i></span>
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
        `;        document.body.appendChild(panel);

        // Setup listener Upload Multi-Foto Referensi & Clipboard Paste
        let attachedReferenceImages = [];
        let pasteCounter = 0;
        const uploadBtn = document.getElementById("gm-ai-upload-btn");
        const fileInput = document.getElementById("gm-ai-img-file");
        const previewBox = document.getElementById("gm-ai-img-preview");
        const previewCount = document.getElementById("gm-ai-preview-count");
        const previewList = document.getElementById("gm-ai-preview-list");
        const clearAllBtn = document.getElementById("gm-ai-clear-all-btn");
        const promptInputEl = document.getElementById("gm-ai-prompt-input");

        const renderAttachedImages = () => {
            if (!previewBox || !previewList) return;
            if (attachedReferenceImages.length === 0) {
                previewBox.style.display = "none";
                previewList.innerHTML = "";
                if (fileInput) fileInput.value = "";
                return;
            }
            previewBox.style.display = "flex";
            if (previewCount) {
                previewCount.innerHTML = `<i class="fa-solid fa-images"></i> ${attachedReferenceImages.length} Foto Referensi`;
            }
            previewList.innerHTML = attachedReferenceImages.map((img, idx) => `
                <div class="gm-ai-preview-item" data-idx="${idx}">
                    <img src="${img.dataUrl}" alt="${img.name}">
                    <div class="gm-ai-preview-info">
                        <span class="gm-ai-preview-name" title="${img.name}">${img.name}</span>
                        <span class="gm-ai-preview-size">${img.sizeStr}</span>
                    </div>
                    <button type="button" class="gm-ai-remove-img-btn" data-idx="${idx}" title="Hapus foto ini">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            `).join("");
        };

        const addImagesFromFiles = async (files, isPasted = false) => {
            const maxTotal = 6;
            const validFiles = Array.from(files).filter(f => f && f.type && f.type.startsWith("image/"));
            if (validFiles.length === 0) return;

            let addedCount = 0;
            for (const f of validFiles) {
                if (attachedReferenceImages.length >= maxTotal) {
                    showStatus(statusEl, "info", `Batas maksimal ${maxTotal} foto referensi tercapai.`);
                    break;
                }
                try {
                    const imgData = await compressImageFile(f);
                    if (isPasted) {
                        pasteCounter++;
                        imgData.name = `Screenshot-${pasteCounter}.png`;
                    }
                    attachedReferenceImages.push(imgData);
                    addedCount++;
                } catch (err) {
                    showStatus(statusEl, "error", err.message || "Gagal mengompresi foto.");
                }
            }
            if (addedCount > 0) {
                renderAttachedImages();
                showStatus(statusEl, "info", `📸 ${attachedReferenceImages.length} foto referensi siap digunakan.`);
            }
        };

        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener("click", () => fileInput.click());
            fileInput.addEventListener("change", async () => {
                const files = fileInput.files;
                if (files && files.length > 0) {
                    await addImagesFromFiles(files);
                    fileInput.value = "";
                }
            });
        }

        if (clearAllBtn) {
            clearAllBtn.addEventListener("click", () => {
                attachedReferenceImages = [];
                renderAttachedImages();
                if (fileInput) fileInput.value = "";
            });
        }

        if (previewList) {
            previewList.addEventListener("click", (e) => {
                const removeBtn = e.target.closest(".gm-ai-remove-img-btn");
                if (removeBtn) {
                    const idx = parseInt(removeBtn.dataset.idx, 10);
                    if (!isNaN(idx) && idx >= 0 && idx < attachedReferenceImages.length) {
                        attachedReferenceImages.splice(idx, 1);
                        renderAttachedImages();
                    }
                }
            });
        }

        // Paste clipboard support (Ctrl+V) langsung pada textarea prompt
        if (promptInputEl) {
            promptInputEl.addEventListener("paste", async (e) => {
                const items = (e.clipboardData || window.clipboardData)?.items;
                if (!items) return;
                const imageFiles = [];
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type && items[i].type.startsWith("image/")) {
                        const blob = items[i].getAsFile();
                        if (blob) imageFiles.push(blob);
                    }
                }
                if (imageFiles.length > 0) {
                    e.preventDefault();
                    await addImagesFromFiles(imageFiles, true);
                }
            });
        }

        // Drag & Drop support on floating panel
        panel.addEventListener("dragover", (e) => {
            e.preventDefault();
            panel.classList.add("gm-ai-drop-active");
        });
        panel.addEventListener("dragleave", (e) => {
            if (!panel.contains(e.relatedTarget)) {
                panel.classList.remove("gm-ai-drop-active");
            }
        });
        panel.addEventListener("drop", async (e) => {
            e.preventDefault();
            panel.classList.remove("gm-ai-drop-active");
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                await addImagesFromFiles(files);
            }
        });

        // Pasang juga fitur AI Edit Object pada menu klik kanan (.gm-object-menu)
        if (typeof setupRightClickAiObjectEditor === "function") {
            setupRightClickAiObjectEditor();
        } else if (typeof window.setupRightClickAiObjectEditor === "function") {
            window.setupRightClickAiObjectEditor();
        }

        // Setup listener Koleksi Asset dari Menu Assets
        let loadedUserAssets = [];
        let selectedUserAssetIds = new Set();
        const assetsBarBtn = document.getElementById("gm-ai-assets-bar-btn");
        const assetsDrawer = document.getElementById("gm-ai-assets-drawer");
        const assetsBadgeCount = document.getElementById("gm-ai-assets-badge-count");
        const assetsListCount = document.getElementById("gm-ai-assets-list-count");
        const assetsGrid = document.getElementById("gm-ai-assets-grid");
        const assetsSelectAllBtn = document.getElementById("gm-ai-assets-select-all");
        const assetsClearSelBtn = document.getElementById("gm-ai-assets-clear-sel");

        const renderUserAssetsGrid = () => {
            if (!assetsGrid) return;
            if (loadedUserAssets.length === 0) {
                assetsGrid.innerHTML = `<div style="grid-column: 1 / -1; font-size: 11px; color: #94a3b8; text-align: center; padding: 12px 0;">Belum ada gambar yang diupload di Menu Assets.</div>`;
                return;
            }
            assetsGrid.innerHTML = loadedUserAssets.map(ast => {
                const isSelected = selectedUserAssetIds.has(ast.id);
                return `
                    <div class="gm-ai-asset-card" data-asset-id="${ast.id}" title="${ast.name} (${ast.url})" style="position:relative;cursor:pointer;border-radius:5px;overflow:hidden;border:2px solid ${isSelected ? '#0078ff' : '#cbd5e1'};background:#fff;aspect-ratio:1/1;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:all 0.15s ease;">
                        <img src="${ast.url}" alt="${ast.name}" style="width:100%;height:100%;object-fit:cover;display:block;" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:18px;color:#94a3b8;background:#f1f5f9;"><i class="fa-solid fa-image"></i></div>
                        ${isSelected ? '<div style="position:absolute;top:2px;right:2px;background:#0078ff;color:#fff;border-radius:50%;width:14px;height:14px;font-size:9px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-check"></i></div>' : ''}
                        <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,0.6);color:#fff;font-size:9px;padding:1px 3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:center;">${ast.name}</div>
                    </div>
                `;
            }).join("");
        };

        const loadUserAssets = async () => {
            try {
                const pId = getCurrentProjectId();
                const resp = await fetch("index.php?action=ai_get_user_assets&project_id=" + encodeURIComponent(pId));
                if (!resp.ok) {
                    console.warn("[Greymist AI] Endpoint aset pengguna mengembalikan HTTP status " + resp.status);
                    return;
                }
                const res = await resp.json();
                if (res && res.success && Array.isArray(res.assets)) {
                    loadedUserAssets = res.assets;
                    if (assetsBadgeCount) assetsBadgeCount.textContent = loadedUserAssets.length;
                    if (assetsListCount) assetsListCount.textContent = loadedUserAssets.length;
                    renderUserAssetsGrid();
                }
            } catch (err) {
                console.warn("[Greymist AI] Gagal memuat aset pengguna:", err);
            }
        };

        if (assetsBarBtn && assetsDrawer) {
            assetsBarBtn.addEventListener("click", () => {
                const isShown = assetsDrawer.style.display !== "none";
                assetsDrawer.style.display = isShown ? "none" : "block";
                assetsBarBtn.style.background = isShown ? "" : "#e0f2fe";
                assetsBarBtn.style.borderColor = isShown ? "" : "#38bdf8";
                assetsBarBtn.style.color = isShown ? "" : "#0284c7";
                if (!isShown && loadedUserAssets.length === 0) {
                    loadUserAssets();
                }
            });
        }

        if (assetsGrid) {
            assetsGrid.addEventListener("click", (e) => {
                const card = e.target.closest(".gm-ai-asset-card");
                if (card) {
                    const id = parseInt(card.dataset.assetId, 10);
                    if (!isNaN(id)) {
                        if (selectedUserAssetIds.has(id)) {
                            selectedUserAssetIds.delete(id);
                        } else {
                            selectedUserAssetIds.add(id);
                        }
                        renderUserAssetsGrid();
                    }
                }
            });
        }

        if (assetsSelectAllBtn) {
            assetsSelectAllBtn.addEventListener("click", () => {
                loadedUserAssets.forEach(a => selectedUserAssetIds.add(a.id));
                renderUserAssetsGrid();
            });
        }

        if (assetsClearSelBtn) {
            assetsClearSelBtn.addEventListener("click", () => {
                selectedUserAssetIds.clear();
                renderUserAssetsGrid();
            });
        }

        // Muat aset pengguna saat panel AI disiapkan
        loadUserAssets();

        // Muat & sinkronkan memori AI proyek setelah frame selesai dimuat
        setTimeout(syncProjectAiMemory, 800);

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
                syncProjectAiMemory();
                const input = document.getElementById("gm-ai-prompt-input");
                if (input) input.focus();
            }
        };

        toggleBtn.addEventListener("click", () => togglePanel());
        document.getElementById("gm-ai-close-btn").addEventListener("click", () => togglePanel(false));

        const minBtn = document.getElementById("gm-ai-minimize-btn");
        minBtn.addEventListener("click", () => {
            panel.classList.toggle("minimized");
            const icon = minBtn.querySelector("i");
            if (icon) {
                icon.className = panel.classList.contains("minimized") ? "fas fa-expand" : "fas fa-minus";
            }
        });

        const menuTrigger = document.getElementById("gm-menu-open-ai-studio");
        if (menuTrigger) {
            menuTrigger.addEventListener("click", () => togglePanel(true));
        }

        document.addEventListener("click", () => {
            if (panel.style.display === "flex") {
                setTimeout(renderFrameComponentsList, 60);
            }
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
                if (preset.label.includes("Page")) {
                    document.getElementById("gm-ai-target-mode").value = "new_frame";
                } else if (!preset.label.includes("Baca")) {
                    document.getElementById("gm-ai-comp-name").value = preset.label;
                }
            });
        });



        // Inisialisasi Sub-Modul AI: Voice Engine & Settings
        if (window.GreymistAI.VoiceEngine && typeof window.GreymistAI.VoiceEngine.setup === "function") {
            window.GreymistAI.VoiceEngine.setup({
                voiceBarBtn: document.getElementById("gm-ai-voice-bar-btn"),
                promptMicBtn: document.getElementById("gm-ai-prompt-mic-btn"),
                promptInput: document.getElementById("gm-ai-prompt-input"),
                statusEl: document.getElementById("gm-ai-status-msg"),
                generateBtn: document.getElementById("gm-ai-generate-btn")
            });
        }

        if (window.GreymistAI.Settings && typeof window.GreymistAI.Settings.setup === "function") {
            window.GreymistAI.Settings.setup({
                modalEl: document.getElementById("gm-ai-plugin-modal"),
                statusEl: document.getElementById("gm-ai-status-msg")
            });
        }

        if (window.GreymistAI.ContextMenu && typeof window.GreymistAI.ContextMenu.setupRightClickAiObjectEditor === "function") {
            window.GreymistAI.ContextMenu.setupRightClickAiObjectEditor();
        }
        // Jalankan AI dengan Memori Proyek & Tanpa Pembatasan        // Jalankan AI dengan Memori Proyek & Tanpa Pembatasan
        const generateBtn = document.getElementById("gm-ai-generate-btn");
        generateBtn.addEventListener("click", () => {
            let prompt = document.getElementById("gm-ai-prompt-input").value.trim();
            const selectedModel = document.getElementById("gm-ai-model-select").value;
            const targetMode = document.getElementById("gm-ai-target-mode").value;
            const compName = document.getElementById("gm-ai-comp-name").value.trim() || "AI Component";
            const saveToLibrary = document.getElementById("gm-ai-save-library").checked;

            if (!prompt && typeof attachedReferenceImages !== "undefined" && Array.isArray(attachedReferenceImages) && attachedReferenceImages.length > 0) {
                if (attachedReferenceImages.length === 1) {
                    prompt = "Buat tampilan antarmuka (UI) website modern yang meniru tata letak (layout), hierarki visual, dan komponen dari foto referensi ini menggunakan komponen .gm-object Greymist.";
                } else {
                    prompt = `Bangun tampilan antarmuka (UI) website modern yang mengombinasikan tata letak, hierarki komponen, dan elemen visual dari ${attachedReferenceImages.length} foto referensi terlampir menggunakan komponen .gm-object Greymist secara utuh dan harmonis.`;
                }
            }

            if (!prompt) {
                showStatus(statusEl, "error", "Isi instruksi pada kolom prompt atau upload foto referensi terlebih dahulu.");
                return;
            }

            const activeCore = getActiveObjectCore();
            const currentFrameHtml = activeCore ? activeCore.innerHTML : "";
            let selectedObj = getSelectedObject();
            let effectiveTargetMode = targetMode;

            // Deteksi apakah pengguna meminta modifikasi atribut/style/animasi pada komponen yang sudah ada
            const isAttributeEditPrompt = /\b(ubah|ganti|rubah|font|warna|color|background|bg|ukuran|size|teks|tulisan|radius|rounded|padding|margin|shadow|jadikan|animasi|anim|animasikan|fade|slide|zoom|bounce|loop|durasi|delay)\b/i.test(prompt);

            // Resolusi cerdas elemen target spesifik (misal card ke-2, tombol, heading) agar animasi TIDAK lari ke root section/frame
            if (activeCore && isAttributeEditPrompt) {
                const specificTarget = resolveSpecificTargetElement(activeCore, selectedObj, prompt);
                if (specificTarget) {
                    selectedObj = specificTarget;
                    document.querySelectorAll(".gm-object.gm-active").forEach(el => {
                        if (el !== selectedObj) el.classList.remove("gm-active");
                    });
                    selectedObj.classList.add("gm-active");
                    if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                        window.GMObjectHandler.attachTo(selectedObj);
                    }
                }
            }

            const isProjectWide = /\b(seluruh\s+(?:pages?|halaman|frame|proyek|project)|semua\s+(?:pages?|halaman|frame|proyek|project)|tiap\s+(?:pages?|halaman|frame)|setiap\s+(?:pages?|halaman|frame)|all\s+pages?|project[- ]wide|lintas\s+(?:halaman|frame))\b/i.test(prompt);
            const isSectionLevelPrompt = /\b(buat|tambah|bikin|halaman|frame|section|footer|navbar|pricing|hero|kontak|rename|ganti nama project|hapus semua|kosongkan)\b/i.test(prompt);

            if (isProjectWide) {
                effectiveTargetMode = "core";
                selectedObj = null;
            } else if (isSectionLevelPrompt && !isAttributeEditPrompt) {
                effectiveTargetMode = "core";
            } else if (selectedObj && (isAttributeEditPrompt || effectiveTargetMode === "selected" || effectiveTargetMode === "edit_selected")) {
                effectiveTargetMode = "edit_selected";
            }

            const selectedObjectHtml = selectedObj ? selectedObj.outerHTML : "";
            let selectedAttrs = null;
            if (selectedObj) {
                const computed = window.getComputedStyle(selectedObj);
                selectedAttrs = {
                    tag: selectedObj.tagName.toLowerCase(),
                    background: computed.backgroundColor,
                    color: computed.color,
                    fontFamily: computed.fontFamily,
                    fontSize: computed.fontSize,
                    padding: computed.padding,
                    borderRadius: computed.borderRadius,
                    anim: selectedObj.dataset.gmAnim || "",
                    animDur: selectedObj.dataset.gmAnimDur || "",
                    animDel: selectedObj.dataset.gmAnimDel || "",
                    animLoop: selectedObj.dataset.gmAnimLoop || ""
                };
            }

            const readMemoryEl = document.getElementById("gm-ai-read-memory");
            const readMemory = (readMemoryEl ? readMemoryEl.checked : false) || isProjectWide;
            const projectId = getCurrentProjectId();
            const framesSnapshot = (readMemory || isProjectWide) ? collectAllFramesSnapshot() : [];

            generateBtn.disabled = true;
            generateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Proses...</span>`;
            if (isProjectWide) {
                showStatus(statusEl, "info", "🌐 AI mendeteksi perubahan selektif di seluruh halaman (mempertahankan konten yang ada)...");
            } else if (readMemory) {
                showStatus(statusEl, "info", "🧠 AI membaca memori proyek & memproses konteks...");
            } else {
                showStatus(statusEl, "info", "⚡ AI memproses instruksi...");
            }

            const currentActiveProvider = (typeof window.getActiveAiProvider === "function" ? window.getActiveAiProvider() : null) ||
                (document.getElementById("gm-ai-provider-select") ? document.getElementById("gm-ai-provider-select").value : "") ||
                window.currentActiveProvider ||
                "groq";

            fetch("api/ai_generate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    project_id: projectId,
                    active_frame_id: getActiveFrameId(),
                    prompt: prompt,
                    provider: currentActiveProvider,
                    model: selectedModel,
                    target_mode: effectiveTargetMode,
                    component_name: compName,
                    read_memory: readMemory,
                    save_to_library: saveToLibrary,
                    current_frame_html: currentFrameHtml,
                    selected_object_html: selectedObjectHtml,
                    selected_object_attrs: selectedAttrs,
                    frames_snapshot: framesSnapshot,
                    reference_images: (typeof attachedReferenceImages !== "undefined" && Array.isArray(attachedReferenceImages)) ? attachedReferenceImages.map(img => ({
                        data: img.data,
                        mime_type: img.mime_type,
                        name: img.name
                    })) : [],
                    reference_image: (typeof attachedReferenceImages !== "undefined" && Array.isArray(attachedReferenceImages) && attachedReferenceImages.length > 0) ? {
                        data: attachedReferenceImages[0].data,
                        mime_type: attachedReferenceImages[0].mime_type
                    } : null,
                    user_assets: (typeof loadedUserAssets !== "undefined" && Array.isArray(loadedUserAssets)) ? (
                        selectedUserAssetIds.size > 0
                            ? loadedUserAssets.filter(a => selectedUserAssetIds.has(a.id))
                            : loadedUserAssets
                    ) : [],
                    user_charts: (typeof window !== "undefined" && Array.isArray(window.gmUserCharts)) ? window.gmUserCharts : []
                })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status !== "OK") {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                        showStatus(statusEl, "error", res.msg || "Gagal memproses instruksi AI.");
                        return;
                    }

                    if (res.ai_memory) {
                        cachedAiMemory = res.ai_memory;
                        try {
                            localStorage.setItem("gm_ai_memory_proj_" + projectId, JSON.stringify(cachedAiMemory));
                        } catch (_) {}
                        updateMemoryBadgeUI(cachedAiMemory);
                    }

                    // 0. Penerapan otomatis breakpoint responsif jika diarahkan oleh AI
                    if (Array.isArray(res.breakpoints_to_add) && res.breakpoints_to_add.length > 0) {
                        applyAiBreakpoints(res.breakpoints_to_add, statusEl);
                    }

                    // 0b. Sinkronisasi resiprokal pada frame yang sudah ada sebelumnya (misal navbar Home diberi link ke About)
                    if (Array.isArray(res.updated_existing_frames) && res.updated_existing_frames.length > 0) {
                        res.updated_existing_frames.forEach(uef => {
                            const core = document.getElementById(`object-core-${uef.frame_id}`) || document.querySelector(`.gm-object-core[data-frame="${uef.frame_id}"]`);
                            if (core) {
                                core.innerHTML = (uef.core_content || "").trim();
                                if (typeof window.initLoadedObjects === "function") window.initLoadedObjects(core);
                                if (typeof updateThumbnail === "function") updateThumbnail(uef.frame_id);
                                else if (typeof window.updateThumbnail === "function") window.updateThumbnail(uef.frame_id);
                            }
                        });
                    }

                    // 0c. Update thumbnail label jika ada frame yang di-rename (misal Main Frame -> Home)
                    if (res.renamed_frame && res.renamed_frame.frame_id) {
                        const renFid = String(res.renamed_frame.frame_id);
                        const renName = res.renamed_frame.new_name;
                        const thumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${renFid}"]`);
                        if (thumb && renName) {
                            const nameEl = thumb.querySelector(".gm-frame-name");
                            if (nameEl) nameEl.textContent = renName;
                        }
                    }

                    // 0d. Update project name di DOM Workspace jika ada permintaan ganti nama proyek
                    if (res.renamed_project && res.renamed_project.new_name) {
                        const projNameEl = document.getElementById("gm-project-name") || document.querySelector(".gm-project-name");
                        if (projNameEl) {
                            projNameEl.textContent = res.renamed_project.new_name;
                            projNameEl.style.transition = "color 0.4s ease, transform 0.2s ease";
                            projNameEl.style.color = "#0078ff";
                            setTimeout(() => { projNameEl.style.color = ""; }, 2500);
                        }
                        if (window.GREYMIST && window.GREYMIST.project) {
                            window.GREYMIST.project.project_name = res.renamed_project.new_name;
                        }
                    }

                    const op = res.operation || "append";

                    // Penanganan operasi ganti nama proyek saja
                    if (op === "rename_project") {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                        let cleanSum = res.summary || res.msg || "Nama proyek berhasil diubah.";
                        if (cleanSum.includes("<") || cleanSum.includes("<!--")) {
                            cleanSum = cleanSum.replace(/<!--.*?-->/gs, "").replace(/<[^>]+>/g, "").trim();
                        }
                        showStatus(statusEl, "success", `✓ ${cleanSum || "Nama proyek berhasil diubah."}`);
                        syncProjectAiMemory();
                        return;
                    }

                    // Penanganan operasi penambahan breakpoint saja
                    if (op === "add_breakpoints") {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                        showStatus(statusEl, "success", `✓ ${res.summary || res.msg || "Breakpoint responsif berhasil ditambahkan."}`);
                        syncProjectAiMemory();
                        return;
                    }

                    // Penanganan operasi BATCH UPDATE FRAMES (Intervensi gaya pada seluruh halaman proyek)
                    if (op === "batch_update_frames" && Array.isArray(res.frames_updates) && res.frames_updates.length > 0) {
                        const activeCore = getActiveObjectCore();
                        const activeFid = activeCore ? String(activeCore.dataset.frame) : null;
                        let countUpdated = 0;

                        res.frames_updates.forEach(fu => {
                            const fid = String(fu.frame_id);
                            const core = document.getElementById(`object-core-${fid}`) || document.querySelector(`.gm-object-core[data-frame="${fid}"]`);
                            if (core) {
                                if (fid === activeFid) {
                                    replaceFrameHtmlProgressive(fu.html || "", statusEl, () => {
                                        if (typeof window.initLoadedObjects === "function") window.initLoadedObjects(core);
                                        refreshWorkspaceAfterChange(fid);
                                    });
                                } else {
                                    if (window.GM_UNDO && typeof window.GM_UNDO.saveState === "function") {
                                        window.GM_UNDO.saveState(core);
                                    }
                                    core.innerHTML = (fu.html || "").trim();
                                    if (typeof window.initLoadedObjects === "function") window.initLoadedObjects(core);
                                    if (typeof window.initGmCharts === "function") window.initGmCharts(core);
                                    if (typeof updateThumbnail === "function") updateThumbnail(fid);
                                    else if (typeof window.updateThumbnail === "function") window.updateThumbnail(fid);
                                }
                                countUpdated++;
                            }
                        });

                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                        showStatus(statusEl, "success", `✓ ${res.msg || `Gaya diperbarui secara konsisten di ${countUpdated} halaman proyek.`}`);
                        syncProjectAiMemory();
                        return;
                    }

                    // 1. Jika operasi adalah patch atribut (font, warna, ukuran, dsb.)
                    if (op === "patch_attributes" && res.patch) {
                        applyAttributePatchInPlace(selectedObj || activeCore, res.patch, statusEl, () => {
                            generateBtn.disabled = false;
                            generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                            syncProjectAiMemory();
                        });
                        return;
                    }

                    if (op === "explain") {
                        generateBtn.disabled = false;
                        generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                        showStatus(statusEl, "info", res.summary || res.msg || "Frame dianalisis.");
                        renderFrameComponentsList();
                    } else if (op === "create_frames" && Array.isArray(res.created_frames) && res.created_frames.length > 0) {
                        buildCreatedFramesSequentially(res.created_frames, statusEl, totalCreated => {
                            generateBtn.disabled = false;
                            generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                            showStatus(statusEl, "success", `✓ ${totalCreated} Frame Halaman baru berhasil dibuat & dibangun.`);
                            syncProjectAiMemory();
                        });
                    } else if (op === "replace_selected" && selectedObj) {
                        replaceSelectedObjectProgressive(selectedObj, res.html || "", statusEl, () => {
                            generateBtn.disabled = false;
                            generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                            showStatus(statusEl, "success", `✓ Objek aktif berhasil diperbarui.`);
                            syncProjectAiMemory();
                        });
                    } else if (op === "replace_frame") {
                        replaceFrameHtmlProgressive(res.html || "", statusEl, () => {
                            generateBtn.disabled = false;
                            generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                            showStatus(statusEl, "success", `✓ ${res.msg || "Isi frame berhasil diperbarui."}`);
                            syncProjectAiMemory();
                        });
                    } else {
                        injectHtmlIntoCanvasProgressive(res.html || "", targetMode, statusEl, inserted => {
                            generateBtn.disabled = false;
                            generateBtn.innerHTML = `<i class="fas fa-bolt"></i> <span>Jalankan AI</span>`;
                            if (res.api_warning) {
                                showStatus(statusEl, "warning", `⚠️ ${res.api_warning}`);
                            } else if (res.summary && typeof res.summary === "string" && !res.summary.includes("<") && !res.summary.includes("<!--")) {
                                showStatus(statusEl, "success", `✓ ${res.summary}`);
                            } else {
                                showStatus(statusEl, "success", `✓ Selesai dibangun (${inserted} komponen utama).`);
                            }
                            syncProjectAiMemory();
                        });
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

