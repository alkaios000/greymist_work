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
            .gm-ai-plugin-toggle.loading {
                background: #0078ff !important;
                color: #ffffff !important;
                border-color: #0078ff !important;
                box-shadow: 0 0 10px rgba(0, 120, 255, 0.6) !important;
                animation: gmAiPulse 1.6s infinite ease-in-out;
            }
            @keyframes gmAiPulse {
                0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 120, 255, 0.6); }
                50% { transform: scale(1.08); box-shadow: 0 0 0 8px rgba(0, 120, 255, 0); }
                100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 120, 255, 0); }
            }
            .gm-ai-chosen-assets {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                margin-bottom: 6px;
                max-height: 70px;
                overflow-y: auto;
            }
            .gm-ai-asset-marker {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #eff6ff;
                border: 1px solid #bfdbfe;
                border-radius: 6px;
                padding: 3px 8px;
                font-size: 11px;
                color: #1e40af;
                font-weight: 500;
                box-sizing: border-box;
                animation: gmAiFadeIn 0.2s ease;
            }
            .gm-ai-asset-marker .gm-ai-asset-name {
                max-width: 160px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .gm-ai-asset-marker .gm-ai-remove-asset-btn {
                background: none;
                border: none;
                color: #60a5fa;
                cursor: pointer;
                font-size: 14px;
                line-height: 1;
                padding: 0;
                margin: 0;
                display: flex;
                align-items: center;
            }
            .gm-ai-asset-marker .gm-ai-remove-asset-btn:hover {
                color: #1d4ed8;
            }
            #gm-ai-prompt-input.gm-ai-input-dragover {
                border-color: #0078ff !important;
                background-color: #f0f7ff !important;
                box-shadow: 0 0 0 2px rgba(0, 120, 255, 0.2) !important;
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
                    <span style="font-weight:700;">Greymist AI</span>
                    <span id="gm-ai-header-loading" style="display:none;align-items:center;margin-left:4px;" title="AI sedang memproses...">
                        <i class="fa-solid fa-circle-notch fa-spin" style="font-size:11px;color:#0078ff;"></i>
                    </span>
                    <span id="gm-ai-active-provider-pill" class="gm-ai-provider-pill" style="font-size:9.5px;padding:2px 7px;border-radius:10px;background:#fef3c7;color:#d97706;font-weight:700;cursor:pointer;" title="Klik untuk membuka Pengaturan Provider AI">Groq 70B</span>
                </div>
                <div class="gm-ai-header-actions">
                    <button type="button" class="gm-ai-icon-btn" id="gm-ai-toggle-settings-btn" title="Pengaturan Provider AI">
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
                        <span style="font-size:11px;font-weight:700;color:#1e293b;">Pengaturan Provider AI</span>
                        <button type="button" id="gm-ai-close-settings-btn" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:12px;"><i class="fas fa-xmark"></i></button>
                    </div>

                    <!-- Pilihan Provider (Tanpa Emoticon) -->
                    <div style="margin-bottom:7px;">
                        <label style="display:block;font-size:10px;font-weight:700;color:#475569;margin-bottom:3px;">PILIH MESIN AI:</label>
                        <select id="gm-ai-provider-select" class="gm-ai-field" style="width:100%;font-size:11px;padding:4px 6px;font-weight:600;background:#ffffff;border:1px solid #94a3b8;">
                            <option value="groq">Groq Cloud (Cepat ~300 t/s)</option>
                            <option value="antigravity">Antigravity Cloud Bridge</option>
                            <option value="openrouter">OpenRouter Cloud</option>
                            <option value="gemini">Google Gemini</option>
                            <option value="smart_preset">Smart Fallback Lokal</option>
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
                            <span>Tes Koneksi</span>
                        </button>
                        <button type="button" id="gm-ai-save-settings-btn" class="gm-ai-btn-primary" style="flex:1;font-size:10.5px;padding:5px 6px;">
                            <span>Simpan</span>
                        </button>
                    </div>

                    <!-- Hasil Tes Koneksi -->
                    <div id="gm-ai-test-result" style="display:none;margin-top:6px;font-size:10px;padding:5px 7px;border-radius:4px;line-height:1.35;"></div>
                </div>

                <!-- Preset Cepat (Minimalis tanpa emoticon) -->
                <div class="gm-ai-chip-list">
                    ${presetChipsHtml}
                </div>

                <!-- Container Tanda Aset Terpilih (Badge Token Ringkas) -->
                <div id="gm-ai-chosen-assets" class="gm-ai-chosen-assets" style="display:none;flex-wrap:wrap;gap:5px;margin-bottom:6px;"></div>

                <!-- Prompt Field dengan Drag-and-Drop & Mic -->
                <div class="gm-ai-prompt-wrap">
                    <textarea id="gm-ai-prompt-input" class="gm-ai-field" rows="2" placeholder="Ketik instruksi desain, atau drag & drop aset gambar ke sini..." style="width:100%;box-sizing:border-box;padding-right:36px;resize:vertical;line-height:1.4;"></textarea>
                    <button type="button" id="gm-ai-prompt-mic-btn" class="gm-ai-mic-btn" title="Sound to Design">
                        <i class="fa-solid fa-microphone"></i>
                    </button>
                </div>

                <!-- Model & Target (Bersih tanpa emoticon) -->
                <div class="gm-ai-row">
                    <select id="gm-ai-model-select" class="gm-ai-field" title="Pilih Model Aktif">
                        <option value="openai/gpt-oss-120b">GPT OSS 120B (Groq LPU)</option>
                    </select>
                    <select id="gm-ai-target-mode" class="gm-ai-field" title="Target Injeksi">
                        <option value="core">Ke Frame Aktif</option>
                        <option value="new_frame">Ke Frame Baru</option>
                        <option value="selected">Ke Objek Terpilih</option>
                    </select>
                </div>

                <!-- Opsi Simpan ke Pustaka Komponen -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <label style="display:flex;align-items:center;gap:5px;font-size:11px;color:#555555;cursor:pointer;white-space:nowrap;">
                        <input type="checkbox" id="gm-ai-save-library" checked style="accent-color:#0078ff;margin:0;">
                        <span>Simpan Komponen</span>
                    </label>
                    <input type="text" id="gm-ai-comp-name" class="gm-ai-field" placeholder="Nama komponen" style="padding:4px 7px;font-size:11px;max-width:135px;">
                </div>

                <!-- Opsi Memori Konteks Proyek (Default: OFF / Mode Kilat) -->
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;background:#f8fafc;padding:5px 8px;border-radius:5px;border:1px solid #e2e8f0;margin-top:4px;">
                    <label style="display:flex;align-items:center;gap:6px;font-size:11px;color:#334155;cursor:pointer;user-select:none;margin:0;">
                        <input type="checkbox" id="gm-ai-read-memory" style="accent-color:#0078ff;margin:0;">
                        <span id="gm-ai-speed-indicator" style="font-weight:600;color:#0284c7;">Mode Kilat (Memori Off)</span>
                    </label>
                    <span style="font-size:9.5px;color:#64748b;cursor:help;" title="Mode Kilat: Respons cepat ~3-7s tanpa membaca memori proyek. Centang jika butuh AI membaca konteks seluruh halaman.">Bantuan <i class="fa-solid fa-circle-question"></i></span>
                </div>

                <!-- Status Ringkas -->
                <div id="gm-ai-status-msg" style="display:none;padding:5px 8px;border:1px solid transparent;border-radius:5px;font-size:10.5px;line-height:1.35;max-height:85px;overflow-y:auto;"></div>

                <!-- Tombol Aksi (Minimalis & Ringkas) -->
                <div style="display:flex;gap:5px;">
                    <button type="button" id="gm-ai-generate-btn" class="gm-ai-btn-primary" style="flex:1;">
                        <span>Jalankan AI</span>
                    </button>
                    <button type="button" id="gm-ai-sync-antigravity-btn" class="gm-ai-btn-secondary" title="Sinkronkan komponen dari Database">
                        <span>Sync DB</span>
                    </button>
                </div>
            </div>
        `;        document.body.appendChild(panel);

        // Pengelolaan Tanda Aset Terpilih (Token Badge Ringkas untuk AI)
        let chosenAssetTokens = [];
        const chosenAssetsContainer = document.getElementById("gm-ai-chosen-assets");
        const promptInputEl = document.getElementById("gm-ai-prompt-input");

        const renderChosenAssetTokens = () => {
            if (!chosenAssetsContainer) return;
            if (chosenAssetTokens.length === 0) {
                chosenAssetsContainer.style.display = "none";
                chosenAssetsContainer.innerHTML = "";
                return;
            }
            chosenAssetsContainer.style.display = "flex";
            chosenAssetsContainer.innerHTML = chosenAssetTokens.map((item, idx) => `
                <div class="gm-ai-asset-marker" data-idx="${idx}" title="Aset wajib: ${item.url}" style="display:inline-flex;align-items:center;gap:4px;background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;padding:2px 7px;border-radius:12px;font-size:10.5px;font-weight:600;">
                    <i class="fa-solid fa-image" style="font-size:10px;"></i>
                    <span style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${item.name || 'Aset ' + (idx + 1)}</span>
                    <button type="button" class="gm-ai-remove-token-btn" data-idx="${idx}" style="background:none;border:none;cursor:pointer;padding:0 2px;color:#0284c7;font-size:11px;line-height:1;" title="Hapus tanda aset ini">&times;</button>
                </div>
            `).join("");
        };

        const addChosenAssetToken = (asset) => {
            if (!asset || (!asset.url && !asset.path)) return;
            const rawUrl = asset.url || asset.path;
            const url = typeof cleanGreymistAssetUrl === "function" ? cleanGreymistAssetUrl(rawUrl) : rawUrl;
            const existing = chosenAssetTokens.find(a => (a.url === url || a.path === url));
            if (existing) return;
            let name = asset.name || url.split("/").pop().split("?")[0];
            chosenAssetTokens.push({
                id: asset.id || ("token-" + Date.now() + "-" + Math.random().toString(36).substr(2, 4)),
                name: name,
                url: url,
                path: url,
                must_use: true
            });
            renderChosenAssetTokens();
            showStatus(statusEl, "info", `Tanda aset "${name}" ditambahkan. AI akan menggunakannya.`);
        };

        const removeChosenAssetToken = (idx) => {
            if (idx >= 0 && idx < chosenAssetTokens.length) {
                chosenAssetTokens.splice(idx, 1);
                renderChosenAssetTokens();
            }
        };

        if (chosenAssetsContainer) {
            chosenAssetsContainer.addEventListener("click", (e) => {
                const btn = e.target.closest(".gm-ai-remove-token-btn");
                if (btn) {
                    const idx = parseInt(btn.dataset.idx, 10);
                    removeChosenAssetToken(idx);
                }
            });
        }

        // Helper pembersih path URL aset (meringkas ke format uploads/assets/...)
        const cleanGreymistAssetUrl = (rawUrl) => {
            if (!rawUrl) return "";
            let u = String(rawUrl).trim();
            const uploadsIdx = u.indexOf("uploads/");
            if (uploadsIdx !== -1) {
                return u.substring(uploadsIdx);
            }
            try {
                const parsed = new URL(u, window.location.origin);
                return parsed.pathname.replace(/^\/+/, '');
            } catch (_) {
                return u.replace(/^\/+/, '');
            }
        };

        // Global dragstart capture untuk mendeteksi aset dari Menu Assets (Left Sidebar / Assets Gallery)
        document.addEventListener("dragstart", (e) => {
            const card = e.target.closest(".gm-image-uploaded, .gm-image-wrapper, .gm-objectdropable, [data-asset-path], [data-image-path], [data-path]") || e.target;
            const img = card.querySelector ? card.querySelector("img") : (e.target.tagName === "IMG" ? e.target : null);
            const titleInput = card.querySelector ? card.querySelector(".gm-image-title") : null;
            let src = (card && (card.dataset.assetPath || card.dataset.imagePath || card.dataset.path || card.dataset.src)) || 
                      (img && (img.dataset.path || img.src)) || 
                      (e.target.tagName === "IMG" ? (e.target.dataset.path || e.target.src) : null);
            let name = (titleInput && titleInput.value) || 
                       (card && (card.dataset.assetName || card.dataset.name)) || 
                       (img && (img.alt || img.title || (img.dataset && img.dataset.name))) || 
                       (src ? src.split("/").pop().split("?")[0] : "Aset Gambar");
            if (src) {
                const cleanSrc = cleanGreymistAssetUrl(src);
                e.dataTransfer.setData("application/x-greymist-asset", JSON.stringify({ url: cleanSrc, name: name }));
                e.dataTransfer.setData("text/plain", cleanSrc);
            }
        }, true);

        // Handler Drop aset ke Input AI atau Modal AI
        const handleIncomingAssetDrop = async (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (promptInputEl) promptInputEl.classList.remove("gm-ai-input-dragover");
            panel.classList.remove("gm-ai-drop-active");

            // 1. Aset dari Menu Assets Greymist
            const gmAssetJson = e.dataTransfer.getData("application/x-greymist-asset");
            if (gmAssetJson) {
                try {
                    const parsed = JSON.parse(gmAssetJson);
                    if (parsed && (parsed.url || parsed.path)) {
                        addChosenAssetToken(parsed);
                        return;
                    }
                } catch (_) {}
            }

            // 2. URL gambar biasa
            const textData = e.dataTransfer.getData("text/plain");
            if (textData && (textData.startsWith("http://") || textData.startsWith("https://") || textData.startsWith("uploads/"))) {
                addChosenAssetToken({ url: textData, name: textData.split("/").pop() });
                return;
            }

            // 3. File dari explorer komputer (ditambahkan sebagai token tanda aset)
            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                for (const file of files) {
                    if (file.type && file.type.startsWith("image/")) {
                        addChosenAssetToken({
                            name: file.name,
                            url: URL.createObjectURL(file),
                            file: file
                        });
                    }
                }
            }
        };

        if (promptInputEl) {
            promptInputEl.addEventListener("dragover", (e) => {
                e.preventDefault();
                promptInputEl.classList.add("gm-ai-input-dragover");
            });
            promptInputEl.addEventListener("dragleave", (e) => {
                promptInputEl.classList.remove("gm-ai-input-dragover");
            });
            promptInputEl.addEventListener("drop", handleIncomingAssetDrop);
        }

        panel.addEventListener("dragover", (e) => {
            e.preventDefault();
            panel.classList.add("gm-ai-drop-active");
        });
        panel.addEventListener("dragleave", (e) => {
            if (!panel.contains(e.relatedTarget)) {
                panel.classList.remove("gm-ai-drop-active");
            }
        });
        panel.addEventListener("drop", handleIncomingAssetDrop);

        // Expose ke global API Greymist
        if (!window.GreymistAI) window.GreymistAI = {};
        window.GreymistAI.addAssetMarker = addChosenAssetToken;

        // Pasang juga fitur AI Edit Object pada menu klik kanan (.gm-object-menu)
        if (typeof setupRightClickAiObjectEditor === "function") {
            setupRightClickAiObjectEditor();
        } else if (typeof window.setupRightClickAiObjectEditor === "function") {
            window.setupRightClickAiObjectEditor();
        }

        // CATATAN: AI TIDAK memuat aset secara otomatis dari latar belakang.
        // AI hanya memproses aset jika user memasukkannya sendiri lewat drag-and-drop ke modal AI.

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

            // Indikator Loading Global (Tetap aktif meskipun modal di-hide/minimize)
            const menuLoadingIndicator = document.getElementById("gm-ai-menu-loading");
            const headerLoadingIndicator = document.getElementById("gm-ai-header-loading");

            const startAiLoadingIndicators = () => {
                if (menuLoadingIndicator) menuLoadingIndicator.style.display = "inline-flex";
                if (headerLoadingIndicator) headerLoadingIndicator.style.display = "inline-flex";
                if (toggleBtn) {
                    toggleBtn.classList.add("loading");
                    toggleBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
                }
                generateBtn.disabled = true;
                generateBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin" style="margin-right:5px;"></i><span>Proses...</span>';
            };

            const stopAiLoadingIndicators = () => {
                if (menuLoadingIndicator) menuLoadingIndicator.style.display = "none";
                if (headerLoadingIndicator) headerLoadingIndicator.style.display = "none";
                if (toggleBtn) {
                    toggleBtn.classList.remove("loading");
                    toggleBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i>';
                }
                generateBtn.disabled = false;
                generateBtn.innerHTML = '<span>Jalankan AI</span>';
            };

            startAiLoadingIndicators();

            if (isProjectWide) {
                showStatus(statusEl, "info", "AI mendeteksi perubahan selektif di seluruh halaman (mempertahankan konten)...");
            } else if (readMemory) {
                showStatus(statusEl, "info", "AI membaca memori proyek & memproses konteks...");
            } else {
                showStatus(statusEl, "info", "AI memproses instruksi desain...");
            }

            // Jika ada tanda aset terpilih, perkuat instruksi prompt agar AI wajib menggunakannya
            if (chosenAssetTokens.length > 0) {
                const markerNames = chosenAssetTokens.map(a => `${a.name} (${a.url})`).join(", ");
                prompt += `\n\n[TANDA ASET WAJIB DIGUNAKAN]: Pengguna telah menandai aset berikut: ${markerNames}. AI WAJIB menyisipkan dan menampilkan gambar-gambar ini ke dalam komponen yang sesuai (misal di logo, hero/banner, card, avatar, dsb.) dengan tag <img src="URL" alt="..."> atau background-image.`;
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
                    user_assets: (Array.isArray(chosenAssetTokens) && chosenAssetTokens.length > 0) ? chosenAssetTokens : [],
                    user_charts: (typeof window !== "undefined" && Array.isArray(window.gmUserCharts)) ? window.gmUserCharts : []
                })
            })
                .then(r => r.json())
                .then(res => {
                    if (res.status !== "OK") {
                        stopAiLoadingIndicators();
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

                    // Reset tanda aset terpilih setelah eksekusi berhasil agar tidak terbawa ke prompt selanjutnya
                    if (chosenAssetTokens.length > 0) {
                        chosenAssetTokens = [];
                        renderChosenAssetTokens();
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
                        stopAiLoadingIndicators();
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
                        stopAiLoadingIndicators();
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

                        stopAiLoadingIndicators();
                        showStatus(statusEl, "success", `✓ ${res.msg || `Gaya diperbarui secara konsisten di ${countUpdated} halaman proyek.`}`);
                        syncProjectAiMemory();
                        return;
                    }

                    // 1. Jika operasi adalah patch atribut (font, warna, ukuran, dsb.)
                    if (op === "patch_attributes" && res.patch) {
                        applyAttributePatchInPlace(selectedObj || activeCore, res.patch, statusEl, () => {
                            stopAiLoadingIndicators();
                            syncProjectAiMemory();
                        });
                        return;
                    }

                    if (op === "explain") {
                        stopAiLoadingIndicators();
                        showStatus(statusEl, "info", res.summary || res.msg || "Frame dianalisis.");
                        renderFrameComponentsList();
                    } else if (op === "create_frames" && Array.isArray(res.created_frames) && res.created_frames.length > 0) {
                        buildCreatedFramesSequentially(res.created_frames, statusEl, totalCreated => {
                            stopAiLoadingIndicators();
                            showStatus(statusEl, "success", `✓ ${totalCreated} Frame Halaman baru berhasil dibuat & dibangun.`);
                            syncProjectAiMemory();
                        });
                    } else if (op === "replace_selected" && selectedObj) {
                        replaceSelectedObjectProgressive(selectedObj, res.html || "", statusEl, () => {
                            stopAiLoadingIndicators();
                            showStatus(statusEl, "success", `✓ Objek aktif berhasil diperbarui.`);
                            syncProjectAiMemory();
                        });
                    } else if (op === "replace_frame") {
                        replaceFrameHtmlProgressive(res.html || "", statusEl, () => {
                            stopAiLoadingIndicators();
                            showStatus(statusEl, "success", `✓ ${res.msg || "Isi frame berhasil diperbarui."}`);
                            syncProjectAiMemory();
                        });
                    } else {
                        injectHtmlIntoCanvasProgressive(res.html || "", targetMode, statusEl, inserted => {
                            stopAiLoadingIndicators();
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
                    stopAiLoadingIndicators();
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

