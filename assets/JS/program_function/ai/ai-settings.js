/**
 * File: assets/JS/program_function/ai/ai-settings.js
 * Modul: Manajemen Pengaturan Provider Cloud AI (Groq, Gemini, OpenRouter, Antigravity Bridge),
 *        Penyimpanan API Key / Model, Mode Kilat vs Mode Memori, & Tes Koneksi.
 */
(function (window) {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};

    function setupAiSettings(options = {}) {
        const modalEl = options.modalEl || document.getElementById("gm-ai-plugin-modal");
        const statusEl = options.statusEl || document.getElementById("gm-ai-status-msg");
        // Definisi Konfigurasi Lengkap Provider AI
        const AI_PROVIDER_CONFIG = {
            groq: {
                name: "Groq Cloud",
                badge: "Groq 120B",
                badgeBg: "#fef3c7",
                badgeColor: "#d97706",
                info: "Dijalankan 100% di Cloud LPU Groq. Super cepat (~300 t/s) & 0% beban laptop. Dapatkan API Key gratis di <a href='https://console.groq.com/keys' target='_blank' style='color:#0078ff;text-decoration:underline;'>console.groq.com/keys</a> (tanpa kartu kredit).",
                needsKey: true,
                keyPlaceholder: "gsk_...",
                models: [
                    { id: "openai/gpt-oss-120b", name: "GPT OSS 120B (Super Kuat & Cepat)" },
                    { id: "deepseek-r1-distill-llama-70b", name: "DeepSeek-R1 Distill 70B" },
                    { id: "llama-3.1-8b-instant", name: "Llama 3.1 8B (Ultra Cepat)" }
                ]
            },
            antigravity: {
                name: "Antigravity Cloud Bridge",
                badge: "Antigravity",
                badgeBg: "#e0f2fe",
                badgeColor: "#0284c7",
                info: "Terhubung ke Antigravity IDE Agent di sistem ini. Menggunakan model Cloud tingkat tinggi tanpa perlu API key pribadi Anda & 0% beban laptop.",
                needsKey: false,
                keyPlaceholder: "",
                models: [
                    { id: "antigravity-cloud", name: "Antigravity Cloud Agent (Otomatis)" }
                ]
            },
            openrouter: {
                name: "OpenRouter Cloud",
                badge: "OpenRouter",
                badgeBg: "#f3e8ff",
                badgeColor: "#7e22ce",
                info: "Koleksi model cloud open-source gratis di <a href='https://openrouter.ai/keys' target='_blank' style='color:#0078ff;text-decoration:underline;'>openrouter.ai/keys</a>.",
                needsKey: true,
                keyPlaceholder: "sk-or-v1-...",
                models: [
                    { id: "meta-llama/llama-3.3-70b-instruct:free", name: "Llama 3.3 70B (Free Tier)" },
                    { id: "deepseek/deepseek-r1:free", name: "DeepSeek R1 (Free Tier)" },
                    { id: "google/gemini-2.0-flash-exp:free", name: "Gemini 2.0 Flash (Free Tier)" }
                ]
            },
            gemini: {
                name: "Google Gemini",
                badge: "Gemini",
                badgeBg: "#dbeafe",
                badgeColor: "#1d4ed8",
                info: "Menggunakan API Key Google AI Studio pribadi Anda.",
                needsKey: true,
                keyPlaceholder: "AIzaSy...",
                models: [
                    { id: "gemini-3.5-flash-lite", name: "Gemini 3.5 Flash-Lite (Gratis & Stabil)" },
                    { id: "gemini-3.5-flash", name: "Gemini 3.5 Flash (Standar)" },
                    { id: "gemini-3.1-pro-preview", name: "Gemini 3.1 Pro (Deep Reasoning - Paid Tier)" },
                    { id: "gemini-pro-latest", name: "Gemini Pro Latest (Paid Tier)" }
                ]
            },
            smart_preset: {
                name: "Smart Fallback Lokal",
                badge: "Offline",
                badgeBg: "#f1f5f9",
                badgeColor: "#475569",
                info: "Engine aturan & regex bawaan Greymist. 100% Offline tanpa koneksi internet & 0% beban CPU.",
                needsKey: false,
                keyPlaceholder: "",
                models: [
                    { id: "local-rule-engine", name: "Local Rule Engine (Offline)" }
                ]
            }
        };

        let currentActiveProvider = "groq";
        let currentActiveModel = "openai/gpt-oss-120b";
        let providerKeys = { groq: "", openrouter: "", gemini: "" };
        let providerMasked = { groq: "", openrouter: "", gemini: "" };

        window.currentActiveProvider = currentActiveProvider;
        window.GreymistAI = window.GreymistAI || {};
        window.GreymistAI.currentActiveProvider = currentActiveProvider;
        window.getActiveAiProvider = function() {
            const sel = document.getElementById("gm-ai-provider-select");
            return (sel && sel.value) ? sel.value : (window.currentActiveProvider || currentActiveProvider || "groq");
        };

        const settingsPanel = document.getElementById("gm-ai-settings-panel");
        const toggleSettingsBtn = document.getElementById("gm-ai-toggle-settings-btn");
        const closeSettingsBtn = document.getElementById("gm-ai-close-settings-btn");
        const providerPill = document.getElementById("gm-ai-active-provider-pill");
        const providerSelect = document.getElementById("gm-ai-provider-select");
        const providerInfoBox = document.getElementById("gm-ai-provider-info-box");
        const keyInputRow = document.getElementById("gm-ai-key-input-row");
        const keyLabel = document.getElementById("gm-ai-key-label");
        const keyStatus = document.getElementById("gm-ai-key-status");
        const apiKeyInput = document.getElementById("gm-ai-api-key-input");
        const toggleShowKeyBtn = document.getElementById("gm-ai-toggle-show-key-btn");
        const settingsModelSelect = document.getElementById("gm-ai-settings-model-select");
        const mainModelSelect = document.getElementById("gm-ai-model-select");
        const testConnBtn = document.getElementById("gm-ai-test-conn-btn");
        const saveSettingsBtn = document.getElementById("gm-ai-save-settings-btn");
        const testResultBox = document.getElementById("gm-ai-test-result");

        // Toggle Settings Panel
        function toggleSettings() {
            const isHidden = settingsPanel.style.display === "none";
            settingsPanel.style.display = isHidden ? "block" : "none";
            if (isHidden) {
                testResultBox.style.display = "none";
            }
        }
        if (toggleSettingsBtn) toggleSettingsBtn.addEventListener("click", toggleSettings);
        if (providerPill) providerPill.addEventListener("click", toggleSettings);
        if (closeSettingsBtn) closeSettingsBtn.addEventListener("click", () => { settingsPanel.style.display = "none"; });

        // Toggle Intip Password
        if (toggleShowKeyBtn) {
            toggleShowKeyBtn.addEventListener("click", () => {
                const isPass = apiKeyInput.type === "password";
                apiKeyInput.type = isPass ? "text" : "password";
                toggleShowKeyBtn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        }

        // Render UI Berdasarkan Provider yang Dipilih
        function renderProviderUi(providerId) {
            const conf = AI_PROVIDER_CONFIG[providerId] || AI_PROVIDER_CONFIG.groq;
            currentActiveProvider = providerId;
            window.currentActiveProvider = providerId;
            if (window.GreymistAI) window.GreymistAI.currentActiveProvider = providerId;

            // Update Pill di Header
            if (providerPill) {
                providerPill.textContent = conf.badge;
                providerPill.style.background = conf.badgeBg;
                providerPill.style.color = conf.badgeColor;
            }

            // Update Info Banner
            if (providerInfoBox) {
                providerInfoBox.innerHTML = conf.info;
            }

            // Update Key Input Row
            if (keyInputRow) {
                if (conf.needsKey) {
                    keyInputRow.style.display = "block";
                    keyLabel.textContent = `API KEY (${conf.name.toUpperCase()}):`;
                    apiKeyInput.placeholder = conf.keyPlaceholder || "Masukkan API Key...";
                    const hasKey = !!providerKeys[providerId] || !!providerMasked[providerId];
                    keyStatus.textContent = hasKey ? "✓ Tersimpan" : "Belum diisi";
                    keyStatus.style.color = hasKey ? "#16a34a" : "#dc2626";
                    apiKeyInput.value = providerKeys[providerId] || providerMasked[providerId] || "";
                } else {
                    keyInputRow.style.display = "none";
                }
            }

            // Update Dropdown Model di Settings & Main Panel
            populateModels(settingsModelSelect, providerId);
            populateModels(mainModelSelect, providerId);

            if (conf.models.length > 0) {
                currentActiveModel = conf.models[0].id;
                if (settingsModelSelect) settingsModelSelect.value = currentActiveModel;
                if (mainModelSelect) mainModelSelect.value = currentActiveModel;
            }
        }

        // Update Dropdown Model di Settings & Main Panel
        function populateModels(selectEl, providerId) {
            if (!selectEl) return;
            const pid = providerId || currentActiveProvider || "groq";
            const conf = AI_PROVIDER_CONFIG[pid] || AI_PROVIDER_CONFIG.groq;
            selectEl.innerHTML = "";
            (conf.models || []).forEach(m => {
                const opt = document.createElement("option");
                opt.value = m.id;
                opt.textContent = m.name;
                selectEl.appendChild(opt);
            });
        }

        if (providerSelect) {
            providerSelect.addEventListener("change", (e) => {
                renderProviderUi(e.target.value);
            });
        }

        if (settingsModelSelect) {
            settingsModelSelect.addEventListener("change", (e) => {
                currentActiveModel = e.target.value;
                if (mainModelSelect) mainModelSelect.value = currentActiveModel;
            });
        }

        if (mainModelSelect) {
            mainModelSelect.addEventListener("change", (e) => {
                currentActiveModel = e.target.value;
                if (settingsModelSelect) settingsModelSelect.value = currentActiveModel;
            });
        }

        // Ambil Pengaturan Provider dari Server
        function loadProviderSettings() {
            fetch("index.php?action=ai_get_provider_settings")
                .then(r => r.json())
                .then(res => {
                    if (res && res.status === "OK") {
                        currentActiveProvider = res.provider || "groq";
                        window.currentActiveProvider = currentActiveProvider;
                        if (window.GreymistAI) window.GreymistAI.currentActiveProvider = currentActiveProvider;
                        if (providerSelect) providerSelect.value = currentActiveProvider;

                        providerMasked.groq = res.groq_key_masked || "";
                        providerMasked.openrouter = res.openrouter_key_masked || "";
                        providerMasked.gemini = res.gemini_key_masked || "";

                        renderProviderUi(currentActiveProvider);

                        // Set model tersimpan jika ada
                        if (currentActiveProvider === "groq" && res.groq_model && settingsModelSelect) {
                            settingsModelSelect.value = res.groq_model;
                            if (mainModelSelect) mainModelSelect.value = res.groq_model;
                            currentActiveModel = res.groq_model;
                        } else if (currentActiveProvider === "gemini" && res.gemini_model && settingsModelSelect) {
                            settingsModelSelect.value = res.gemini_model;
                            if (mainModelSelect) mainModelSelect.value = res.gemini_model;
                            currentActiveModel = res.gemini_model;
                        }
                    }
                })
                .catch(() => {
                    renderProviderUi("groq");
                });
        }
        loadProviderSettings();

        // Tes Koneksi Provider
        if (testConnBtn) {
            testConnBtn.addEventListener("click", () => {
                const prov = providerSelect ? providerSelect.value : currentActiveProvider;
                const keyVal = apiKeyInput ? apiKeyInput.value.trim() : "";
                const modelVal = settingsModelSelect ? settingsModelSelect.value : "";

                testResultBox.style.display = "block";
                testResultBox.style.background = "#eff6ff";
                testResultBox.style.color = "#1d4ed8";
                testResultBox.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menghubungi server cloud ${prov}...`;
                testConnBtn.disabled = true;

                fetch("index.php?action=ai_test_provider_connection", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        provider: prov,
                        api_key: keyVal,
                        model: modelVal
                    })
                })
                    .then(r => r.json())
                    .then(res => {
                        testConnBtn.disabled = false;
                        if (res.status === "OK") {
                            testResultBox.style.background = "#f0fdf4";
                            testResultBox.style.color = "#15803d";
                            testResultBox.style.border = "1px solid #bbf7d0";
                            testResultBox.innerHTML = `<strong>✓ Terhubung!</strong> Latensi: <strong>${res.latency_ms} ms</strong>.<br>${res.msg}`;
                            if (keyStatus) {
                                keyStatus.textContent = "✓ Terverifikasi";
                                keyStatus.style.color = "#16a34a";
                            }
                        } else {
                            testResultBox.style.background = "#fef2f2";
                            testResultBox.style.color = "#b91c1c";
                            testResultBox.style.border = "1px solid #fecaca";
                            testResultBox.innerHTML = `<strong>✗ Gagal:</strong> ${res.msg || "Koneksi ditolak."}`;
                        }
                    })
                    .catch(() => {
                        testConnBtn.disabled = false;
                        testResultBox.style.background = "#fef2f2";
                        testResultBox.style.color = "#b91c1c";
                        testResultBox.innerHTML = "Gagal menghubungi endpoint pengujian.";
                    });
            });
        }

        // Simpan Pengaturan Provider ke .env
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener("click", () => {
                const prov = providerSelect ? providerSelect.value : currentActiveProvider;
                const keyVal = apiKeyInput ? apiKeyInput.value.trim() : "";
                const modelVal = settingsModelSelect ? settingsModelSelect.value : "";

                const payload = {
                    provider: prov,
                };
                if (prov === "groq") {
                    if (keyVal && !keyVal.includes("••••")) payload.groq_api_key = keyVal;
                    payload.groq_model = modelVal;
                } else if (prov === "openrouter") {
                    if (keyVal && !keyVal.includes("••••")) payload.openrouter_api_key = keyVal;
                    payload.openrouter_model = modelVal;
                } else if (prov === "gemini") {
                    if (keyVal && !keyVal.includes("••••")) payload.gemini_api_key = keyVal;
                    payload.gemini_model = modelVal;
                }

                saveSettingsBtn.disabled = true;
                saveSettingsBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`;

                fetch("index.php?action=ai_save_provider_settings", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                })
                    .then(r => r.json())
                    .then(res => {
                        saveSettingsBtn.disabled = false;
                        saveSettingsBtn.innerHTML = `<i class="fas fa-floppy-disk"></i> <span>Simpan</span>`;
                        if (res.status === "OK") {
                            currentActiveProvider = prov;
                            window.currentActiveProvider = prov;
                            if (window.GreymistAI) window.GreymistAI.currentActiveProvider = prov;
                            if (keyVal && !keyVal.includes("••••")) {
                                providerKeys[prov] = keyVal;
                            }
                            renderProviderUi(prov);
                            showStatus(statusEl, "success", `✓ Provider AI disetel ke: ${AI_PROVIDER_CONFIG[prov]?.name || prov}`);
                            setTimeout(() => {
                                settingsPanel.style.display = "none";
                            }, 1200);
                        } else {
                            showStatus(statusEl, "error", res.msg || "Gagal menyimpan pengaturan provider.");
                        }
                    })
                    .catch(() => {
                        saveSettingsBtn.disabled = false;
                        saveSettingsBtn.innerHTML = `<i class="fas fa-floppy-disk"></i> <span>Simpan</span>`;
                        showStatus(statusEl, "error", "Gagal menghubungi server untuk menyimpan.");
                    });
            });
        }

        // Sync dari Antigravity DB
        document.getElementById("gm-ai-sync-antigravity-btn").addEventListener("click", () => {
            syncFromAntigravityDB(statusEl);
        });

        // Setup Mode Kilat vs Memori Konteks Proyek (Default: OFF / Mode Kilat)
        const readMemoryCheckbox = document.getElementById("gm-ai-read-memory");
        const speedIndicator = document.getElementById("gm-ai-speed-indicator");
        const memoryBadge = document.getElementById("gm-ai-memory-badge");

        function updateSpeedModeUI() {
            const isChecked = readMemoryCheckbox ? readMemoryCheckbox.checked : false;
            if (speedIndicator) {
                if (isChecked) {
                    speedIndicator.innerHTML = `🧠 Mode Konteks Penuh (Memori On)`;
                    speedIndicator.style.color = "#7c3aed";
                } else {
                    speedIndicator.innerHTML = `⚡ Mode Kilat (Memori Off)`;
                    speedIndicator.style.color = "#0284c7";
                }
            }
            if (memoryBadge) {
                if (isChecked) {
                    memoryBadge.innerHTML = `🧠 Memori AI Aktif`;
                    memoryBadge.style.background = "#ede9fe";
                    memoryBadge.style.color = "#7c3aed";
                    memoryBadge.style.borderColor = "#c4b5fd";
                    memoryBadge.title = "Mode Konteks Penuh aktif: AI membaca riwayat dan seluruh frame proyek (Klik untuk mematikan / Mode Kilat)";
                } else {
                    memoryBadge.innerHTML = `⚡ Mode Kilat (Memori Off)`;
                    memoryBadge.style.background = "#f0f9ff";
                    memoryBadge.style.color = "#0284c7";
                    memoryBadge.style.borderColor = "#bae6fd";
                    memoryBadge.title = "Mode Kilat aktif: AI merespons cepat tanpa membaca memori proyek (Klik untuk mengaktifkan Memori Konteks)";
                }
            }
        }

        if (readMemoryCheckbox) {
            readMemoryCheckbox.addEventListener("change", updateSpeedModeUI);
        }
        if (memoryBadge) {
            memoryBadge.addEventListener("click", () => {
                if (readMemoryCheckbox) {
                    readMemoryCheckbox.checked = !readMemoryCheckbox.checked;
                    updateSpeedModeUI();
                }
            });
        }
        updateSpeedModeUI();



        return {
            AI_PROVIDER_CONFIG,
            toggleSettings,
            renderProviderUi,
            populateModels,
            loadProviderSettings,
            updateSpeedModeUI
        };
    }

    window.GreymistAI.Settings = {
        setup: setupAiSettings
    };
    window.setupAiSettings = setupAiSettings;

})(window);
