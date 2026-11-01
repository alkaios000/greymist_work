/**
 * Greymist Background Color & Design Tokens Engine
 * Handles background colors, design token variables, live syncing, persistence, and gradient studio.
 */

window.gmColorMode = "solid";

/* ==========================================================================
   GLOBAL BG COLOR & TOKEN ENGINE (SINGLETON)
   ========================================================================== */
window.gmBgTokens = window.gmBgColorEngine = {
    initialized: false,
    tokens: {},
    recent: [],
    activeMode: "solid",
    projectId: null,
    lastAppliedColor: "#0078d7",

    getProjectId: function () {
        if (this.projectId) return this.projectId;
        if (window.GREYMIST && window.GREYMIST.project && window.GREYMIST.project.project_id) {
            this.projectId = window.GREYMIST.project.project_id;
        } else {
            const params = new URLSearchParams(window.location.search);
            this.projectId = params.get("project_id");
        }
        return this.projectId;
    },

    load: async function () {
        // 1. Cek dari window.GREYMIST jika sudah ada
        if (window.GREYMIST && window.GREYMIST.project) {
            if (window.GREYMIST.project.bg_color_settings) {
                try {
                    const parsed = typeof window.GREYMIST.project.bg_color_settings === 'string'
                        ? JSON.parse(window.GREYMIST.project.bg_color_settings)
                        : window.GREYMIST.project.bg_color_settings;
                    if (parsed && typeof parsed === 'object') {
                        if (parsed.tokens) this.tokens = Object.assign({}, parsed.tokens);
                        if (Array.isArray(parsed.recent)) this.recent = [...parsed.recent];
                    }
                } catch (e) { }
            }
        }

        // 2. Fetch dari database API
        const pid = this.getProjectId();
        if (pid) {
            try {
                const res = await fetch(`api/get_bg_color_settings.php?project_id=${pid}`);
                const data = await res.json();
                if (data.status === "OK" && data.settings) {
                    if (data.settings.tokens && typeof data.settings.tokens === 'object') {
                        this.tokens = Object.assign({}, this.tokens, data.settings.tokens);
                    }
                    if (Array.isArray(data.settings.recent)) {
                        this.recent = data.settings.recent;
                    }
                }
            } catch (e) {
                console.warn("Could not fetch bg color settings from API:", e);
            }
        }

        // 3. Fallback jika masih kosong, ambil dari typography tokens atau default
        if (!Object.keys(this.tokens).length) {
            if (window.gmTypographyTokens && window.gmTypographyTokens.tokens && Object.keys(window.gmTypographyTokens.tokens).length) {
                this.tokens = Object.assign({}, window.gmTypographyTokens.tokens);
            } else {
                this.tokens = {
                    "--bg-main": "#ffffff",
                    "--bg-dark": "#0f172a",
                    "--bg-surface": "#f8fafc",
                    "--accent-bg": "#3b82f6"
                };
            }
        }

        if (!this.recent.length) {
            this.recent = ["#ffffff", "#0f172a", "#f8fafc", "#3b82f6"];
        }

        this.render();
        this.applyVariables();
    },

    save: async function () {
        const pid = this.getProjectId();
        if (!pid) return;

        const payload = {
            project_id: pid,
            settings: {
                tokens: this.tokens,
                recent: this.recent,
                active_mode: this.activeMode
            }
        };

        try {
            const res = await fetch("api/save_bg_color_settings.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.status === "OK") {
                if (window.GREYMIST && window.GREYMIST.project) {
                    window.GREYMIST.project.bg_color_settings = JSON.stringify(payload.settings);
                }
                // Sinkronkan juga ke typography tokens jika ada
                if (window.gmTypographyTokens) {
                    window.gmTypographyTokens.tokens = Object.assign({}, window.gmTypographyTokens.tokens, this.tokens);
                    if (typeof window.gmTypographyTokens.render === 'function') {
                        window.gmTypographyTokens.render();
                    }
                }
            }
        } catch (e) {
            console.error("Failed to save bg color settings:", e);
        }
    },

    applyVariables: function () {
        const frameRoot = document.querySelector("#gm-frame-preview") || document.body;
        let css = ":root {\n";

        Object.entries(this.tokens).forEach(([name, value]) => {
            const varName = name.startsWith("--") ? name : `--${name}`;
            document.documentElement.style.setProperty(varName, value);
            if (frameRoot) frameRoot.style.setProperty(varName, value);
            document.querySelectorAll(".gm-object-core").forEach(core => {
                core.style.setProperty(varName, value);
            });
            css += `  ${varName}: ${value};\n`;
        });
        css += "}";

        let styleTag = document.getElementById("gm-bg-color-tokens-style");
        if (!styleTag) {
            styleTag = document.createElement("style");
            styleTag.id = "gm-bg-color-tokens-style";
            document.head.appendChild(styleTag);
        }
        styleTag.textContent = css;

        try {
            const channel = new BroadcastChannel('greymist-live-preview');
            channel.postMessage({ type: 'update-bg-color-tokens', tokens: this.tokens });
            channel.close();
        } catch (e) { }
    },

    render: function () {
        const container = document.getElementById("gm-bg-color-variables");
        const badge = document.getElementById("gmBgTokenCount");

        if (badge) {
            badge.textContent = Object.keys(this.tokens).length;
        }

        if (container) {
            // Hapus token lama kecuali tombol "+ variable"
            container.querySelectorAll(".gm-color-token:not(.gm-add-token)").forEach(el => el.remove());
            const addBtn = container.querySelector(".gm-add-token");

            // Ambil warna aktif sekarang untuk highlight token
            const activeColor = this.getActiveObjectBackground();

            Object.entries(this.tokens).forEach(([name, value]) => {
                const varName = name.startsWith("--") ? name : `--${name}`;
                const token = document.createElement("span");
                token.className = "gm-color-token";
                token.dataset.name = varName;
                token.dataset.color = value;
                token.title = `Apply ${varName} (Right-click to edit/delete)`;

                if (activeColor && (activeColor === `var(${varName})` || activeColor === varName)) {
                    token.classList.add("active-token");
                }

                token.innerHTML = `
                    <input type="color" value="${value.startsWith("#") ? value : '#000000'}" title="Change color value">
                    <span class="gm-token-label">${varName}</span>
                `;

                if (addBtn) {
                    container.insertBefore(token, addBtn);
                } else {
                    container.appendChild(token);
                }
            });
        }

        this.renderRecent();
    },

    renderRecent: function () {
        const recentList = document.getElementById("gmBgRecentList");
        if (!recentList) return;

        recentList.innerHTML = "";
        this.recent.slice(0, 10).forEach(color => {
            const item = document.createElement("div");
            item.className = "gm-recent-item";
            item.style.background = color;
            item.dataset.color = color;
            item.title = color;
            item.addEventListener("click", () => {
                this.setBackgroundToActive(color, false, "solid");
            });
            recentList.appendChild(item);
        });
    },

    addRecentColor: function (color) {
        if (!color || color.startsWith("var(")) return;
        this.recent = this.recent.filter(c => c.toLowerCase() !== color.toLowerCase());
        this.recent.unshift(color);
        if (this.recent.length > 12) this.recent.pop();
        this.renderRecent();
        this.debouncedSave();
    },

    debouncedSaveTimer: null,
    debouncedSave: function () {
        clearTimeout(this.debouncedSaveTimer);
        this.debouncedSaveTimer = setTimeout(() => {
            this.save();
        }, 500);
    },

    getActiveVisuals: function () {
        let actives = Array.from(document.querySelectorAll(".gm-object.gm-active"));
        if (!actives.length && window.lastSelectedGmObject && document.contains(window.lastSelectedGmObject)) {
            window.lastSelectedGmObject.classList.add("gm-active");
            actives = [window.lastSelectedGmObject];
        }
        return actives;
    },

    getActiveObjectBackground: function () {
        const actives = this.getActiveVisuals();
        if (!actives.length) return null;
        const el = actives[0];
        if (el.dataset.gmColor) return el.dataset.gmColor;
        const bg = el.style.background || el.style.backgroundColor;
        if (bg) return bg;
        return window.getComputedStyle(el).backgroundColor;
    },

    setBackgroundToActive: function (colorValue, isVariable = false, mode = "solid") {
        if (!colorValue) return;
        const actives = this.getActiveVisuals();
        if (!actives.length) return;

        actives.forEach(el => {
            el.dataset.gmColor = colorValue;
            el.dataset.gmMode = mode;
            el.style.background = colorValue;

            // Simpan ke dataset breakpoint
            const bp = window.gmActiveBreakpoint || 'desktop';
            if (bp === 'desktop') {
                el.dataset.bpDesktop = el.style.cssText;
            } else {
                el.setAttribute(`data-bp-${bp}`, el.style.cssText);
            }
        });

        // Sinkronisasi breakpoint ke global
        if (typeof window.syncCurrentBreakpoint === 'function') {
            window.syncCurrentBreakpoint();
        }

        // Update UI
        this.lastAppliedColor = colorValue;
        this.updateUIValues(colorValue);

        // Highlight matching token
        this.highlightActiveToken(colorValue);

        // Tambahkan ke recent jika solid bukan variabel
        if (mode === "solid" && !isVariable && !colorValue.startsWith("var(")) {
            this.addRecentColor(colorValue);
        } else if (mode === "gradient") {
            if (typeof window.gmAddRecentGradient === 'function') {
                window.gmAddRecentGradient(colorValue);
            }
        }

        // Trigger events
        document.dispatchEvent(new Event("gm-selection-change"));
        window.dispatchEvent(new CustomEvent('gm-active-tick'));
    },

    highlightActiveToken: function (colorValue) {
        document.querySelectorAll("#gm-bg-color-variables .gm-color-token").forEach(tok => {
            const name = tok.dataset.name;
            if (name && (colorValue === `var(${name})` || colorValue === name)) {
                tok.classList.add("active-token");
            } else {
                tok.classList.remove("active-token");
            }
        });
    },

    updateUIValues: function (colorValue) {
        // Update swatch preview & color menu button
        const btn = document.getElementById("colorMenuBtn") || document.querySelector(".gm-color-button");
        if (btn) btn.style.background = colorValue;

        const swatchPreview = document.getElementById("gmBgSwatchPreview");
        if (swatchPreview) swatchPreview.style.background = colorValue;

        const hexInput = document.getElementById("gm-color-hex");
        if (hexInput && document.activeElement !== hexInput) {
            hexInput.value = colorValue;
        }

        const picker = document.getElementById("gm-color-picker");
        if (picker && document.activeElement !== picker) {
            let hex = this.toHex(colorValue);
            if (hex) picker.value = hex;
        }

        // Update format inputs
        const hex = this.toHex(colorValue);
        if (hex) this.updateFormatInputsFromHex(hex);
    },

    syncWithActiveObject: function () {
        const bg = this.getActiveObjectBackground();
        if (bg) {
            this.updateUIValues(bg);
            this.highlightActiveToken(bg);
        }
    },

    addToken: function (name, color) {
        const finalName = name.startsWith("--") ? name : `--${name}`;
        if (this.tokens[finalName]) {
            alert(`Token "${finalName}" already exists!`);
            return;
        }
        this.tokens[finalName] = color;
        this.save();
        this.render();
        this.applyVariables();
        this.setBackgroundToActive(`var(${finalName})`, true, "solid");
    },

    editToken: function (oldName, newName, newColor) {
        const finalOld = oldName.startsWith("--") ? oldName : `--${oldName}`;
        const finalNew = newName.startsWith("--") ? newName : `--${newName}`;

        if (finalNew !== finalOld && this.tokens[finalNew]) {
            alert(`Token "${finalNew}" already exists!`);
            return;
        }

        delete this.tokens[finalOld];
        this.tokens[finalNew] = newColor;

        // Ganti referensi pada object-object di canvas
        if (finalNew !== finalOld) {
            document.querySelectorAll(".gm-object").forEach(el => {
                if (el.dataset.gmColor === `var(${finalOld})`) {
                    el.dataset.gmColor = `var(${finalNew})`;
                }
                if (el.style.background && el.style.background.includes(`var(${finalOld})`)) {
                    el.style.background = el.style.background.replace(`var(${finalOld})`, `var(${finalNew})`);
                    if (el.dataset.bpDesktop) {
                        el.dataset.bpDesktop = el.dataset.bpDesktop.replace(`var(${finalOld})`, `var(${finalNew})`);
                    }
                }
            });
            if (typeof window.syncCurrentBreakpoint === 'function') {
                window.syncCurrentBreakpoint();
            }
        }

        this.save();
        this.render();
        this.applyVariables();
    },

    deleteToken: function (name) {
        const finalName = name.startsWith("--") ? name : `--${name}`;
        if (!confirm(`Delete variable "${finalName}"? Objects using this color will be reset.`)) return;

        delete this.tokens[finalName];

        // Bersihkan style pada object canvas
        document.querySelectorAll(".gm-object").forEach(el => {
            if (el.dataset.gmColor === `var(${finalName})`) {
                el.dataset.gmColor = "";
            }
            if (el.style.background && el.style.background.includes(`var(${finalName})`)) {
                el.style.background = "";
                if (el.dataset.bpDesktop) {
                    el.dataset.bpDesktop = el.dataset.bpDesktop.replace(new RegExp(`background:[^;]*var\\(${finalName}\\)[^;]*;?`, 'g'), '');
                }
            }
        });
        if (typeof window.syncCurrentBreakpoint === 'function') {
            window.syncCurrentBreakpoint();
        }

        this.save();
        this.render();
        this.applyVariables();
    },

    /* --- Helper Konversi Warna --- */
    toHex: function (colorStr) {
        if (!colorStr) return null;
        colorStr = String(colorStr).trim();
        if (colorStr.startsWith("#")) {
            return colorStr.length === 4
                ? "#" + colorStr[1] + colorStr[1] + colorStr[2] + colorStr[2] + colorStr[3] + colorStr[3]
                : colorStr.toUpperCase();
        }
        if (colorStr.startsWith("var(")) {
            const match = colorStr.match(/var\((--[^)]+)\)/);
            if (match && this.tokens[match[1]]) {
                return this.toHex(this.tokens[match[1]]);
            }
            return null;
        }
        const m = colorStr.match(/rgba?\s*\(\s*(\d+)[^\d]+(\d+)[^\d]+(\d+)/i);
        if (m) {
            const r = Number(m[1]).toString(16).padStart(2, "0");
            const g = Number(m[2]).toString(16).padStart(2, "0");
            const b = Number(m[3]).toString(16).padStart(2, "0");
            return `#${r}${g}${b}`.toUpperCase();
        }
        return null;
    },

    updateFormatInputsFromHex: function (hex) {
        if (!hex) return;
        const rgb = this.hexToRgb(hex);
        if (!rgb) return;
        const hsl = this.rgbToHsl(rgb.r, rgb.g, rgb.b);
        const cmyk = this.rgbToCmyk(rgb.r, rgb.g, rgb.b);

        const rgbInput = document.getElementById("gm-rgb");
        const hslInput = document.getElementById("gm-hsl");
        const cmykInput = document.getElementById("gm-cmyk");

        if (rgbInput && document.activeElement !== rgbInput) rgbInput.value = `${rgb.r}, ${rgb.g}, ${rgb.b}`;
        if (hslInput && document.activeElement !== hslInput) hslInput.value = `${hsl.h}, ${hsl.s}%, ${hsl.l}%`;
        if (cmykInput && document.activeElement !== cmykInput) cmykInput.value = `${cmyk.c}, ${cmyk.m}, ${cmyk.y}, ${cmyk.k}`;
    },

    hexToRgb: function (hex) {
        if (!hex) return null;
        hex = hex.replace("#", "");
        if (hex.length === 3) hex = hex.split("").map(x => x + x).join("");
        const num = parseInt(hex, 16);
        return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
    },

    rgbToHex: function (r, g, b) {
        return "#" + [r, g, b].map(x => Math.round(x).toString(16).padStart(2, "0")).join("").toUpperCase();
    },

    rgbToHsl: function (r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const max = Math.max(r, g, b), min = Math.min(r, g, b);
        let h, s, l = (max + min) / 2;
        if (max === min) { h = s = 0; }
        else {
            const d = max - min;
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            switch (max) {
                case r: h = (g - b) / d + (g < b ? 6 : 0); break;
                case g: h = (b - r) / d + 2; break;
                case b: h = (r - g) / d + 4; break;
            }
            h /= 6;
        }
        return { h: Math.round(h * 360), s: Math.round(s * 100), l: Math.round(l * 100) };
    },

    rgbToCmyk: function (r, g, b) {
        r /= 255; g /= 255; b /= 255;
        const k = 1 - Math.max(r, g, b);
        const c = (1 - r - k) / (1 - k) || 0;
        const m = (1 - g - k) / (1 - k) || 0;
        const y = (1 - b - k) / (1 - k) || 0;
        return { c: Math.round(c * 100), m: Math.round(m * 100), y: Math.round(y * 100), k: Math.round(k * 100) };
    }
};

/* ==========================================================================
   POPUP MODAL & CONTEXT MENU ENGINE
   ========================================================================== */
function openBgTokenEditorPopup(x, y, oldName, oldColor, onSave) {
    document.querySelectorAll(".gm-token-editor-popup").forEach(p => p.remove());
    document.querySelectorAll(".gm-token-popup").forEach(p => p.remove());

    const savedActiveEls = window.gmBgColorEngine.getActiveVisuals();
    if (savedActiveEls.length) {
        window.lastSelectedGmObject = savedActiveEls[0];
    }

    const popup = document.createElement("div");
    popup.className = "gm-token-editor-popup";

    if (x + 200 > window.innerWidth) x = window.innerWidth - 210;
    if (y + 180 > window.innerHeight) y = window.innerHeight - 190;

    Object.assign(popup.style, {
        position: "fixed",
        left: `${x}px`,
        top: `${y}px`,
        background: "#ffffff",
        border: "1px solid #e5e7eb",
        borderRadius: "10px",
        boxShadow: "0 12px 28px -4px rgba(0, 0, 0, 0.16)",
        zIndex: "999999",
        padding: "14px",
        width: "195px",
        fontFamily: "inherit"
    });

    popup.innerHTML = `
        <div style="margin-bottom:10px;">
            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">Variable Name</label>
            <input type="text" class="gm-ed-name" style="width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:6px;padding:6px 8px;font-size:12px;outline:none;" placeholder="bg-primary" value="${(oldName || '').replace(/^--/, '')}">
        </div>
        <div style="margin-bottom:12px;">
            <label style="display:block;font-size:11px;font-weight:600;color:#374151;margin-bottom:4px;">Color Code</label>
            <div style="display:flex;gap:6px;align-items:center;">
                <input type="color" class="gm-ed-picker" style="width:30px;height:30px;padding:0;border:1px solid #d1d5db;border-radius:6px;cursor:pointer;" value="${(oldColor && oldColor.length === 7) ? oldColor : '#2563eb'}">
                <input type="text" class="gm-ed-text" style="width:100%;box-sizing:border-box;flex:1;border:1px solid #d1d5db;border-radius:6px;padding:6px 8px;font-size:12px;outline:none;" placeholder="#2563eb" value="${oldColor || '#2563eb'}">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button class="gm-ed-cancel" style="background:#f3f4f6;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:500;color:#374151;">Cancel</button>
            <button class="gm-ed-save" style="background:#2563eb;color:#fff;border:none;padding:6px 14px;border-radius:6px;cursor:pointer;font-size:12px;font-weight:500;">Save</button>
        </div>
    `;

    document.body.appendChild(popup);

    const nameInput = popup.querySelector(".gm-ed-name");
    const pickerInput = popup.querySelector(".gm-ed-picker");
    const textInput = popup.querySelector(".gm-ed-text");
    const btnCancel = popup.querySelector(".gm-ed-cancel");
    const btnSave = popup.querySelector(".gm-ed-save");

    nameInput.focus();

    pickerInput.addEventListener("input", e => { textInput.value = e.target.value; });
    textInput.addEventListener("input", e => {
        if (/^#[0-9A-Fa-f]{6}$/i.test(e.target.value)) pickerInput.value = e.target.value;
    });

    const close = () => popup.remove();
    btnCancel.addEventListener("click", close);

    const save = () => {
        const rawName = nameInput.value.trim();
        const rawColor = textInput.value.trim();
        if (rawName && rawColor) {
            const finalName = rawName.startsWith("--") ? rawName : `--${rawName}`;
            if (savedActiveEls.length) {
                savedActiveEls.forEach(el => el.classList.add("gm-active"));
            } else if (window.lastSelectedGmObject && document.contains(window.lastSelectedGmObject)) {
                window.lastSelectedGmObject.classList.add("gm-active");
            }
            onSave(finalName, rawColor);
        }
        close();
    };

    btnSave.addEventListener("click", save);
    popup.addEventListener("keydown", e => {
        if (e.key === "Enter") save();
        if (e.key === "Escape") close();
    });

    setTimeout(() => {
        const outsideClick = (eClick) => {
            if (!popup.contains(eClick.target)) {
                close();
                document.removeEventListener("mousedown", outsideClick);
            }
        };
        document.addEventListener("mousedown", outsideClick);
    }, 10);
}

/* ==========================================================================
   INITIALIZATION & EVENT LISTENERS
   ========================================================================== */
document.addEventListener("DOMContentLoaded", () => {
    const dropdown = document.getElementById("gmColorDropdown") || document.querySelector(".gm-color-dropdown");
    const colorButton = document.getElementById("colorMenuBtn") || document.querySelector(".gm-color-button");

    if (!dropdown || !colorButton) {
        console.warn("Background color dropdown or trigger button not found.");
        return;
    }

    // Pindahkan dropdown ke body agar bebas z-index overflow
    if (dropdown.parentElement !== document.body) {
        document.body.appendChild(dropdown);
    }

    // Inisialisasi engine
    window.gmBgColorEngine.load();

    // Toggle dropdown
    colorButton.addEventListener("click", (e) => {
        e.stopPropagation();
        const isShown = dropdown.style.display === "block";
        if (isShown) {
            dropdown.style.display = "none";
        } else {
            if (!dropdown.dataset.hasMoved) {
                const rect = colorButton.getBoundingClientRect();
                dropdown.style.position = "fixed";
                dropdown.style.left = `${Math.min(rect.left - 100, window.innerWidth - 250)}px`;
                dropdown.style.top = `${rect.bottom + 6}px`;
                dropdown.style.right = "auto";
                dropdown.style.bottom = "auto";
            }
            dropdown.style.display = "block";
            window.gmBgColorEngine.syncWithActiveObject();
        }
    });

    // Make Background Dropdown Draggable
    function makeDropdownDraggable(modalEl, handleEl) {
        if (!modalEl || !handleEl) return;

        let isDragging = false;
        let hasMoved = false;
        let startX = 0, startY = 0;
        let initialLeft = 0, initialTop = 0;

        const onPointerDown = (e) => {
            if (e.target.closest("#closeBgDropdownBtn") || e.target.closest("button") || e.target.closest("input")) {
                return;
            }

            isDragging = true;
            hasMoved = false;
            startX = e.clientX;
            startY = e.clientY;

            const rect = modalEl.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;

            if (handleEl.setPointerCapture) {
                try { handleEl.setPointerCapture(e.pointerId); } catch(err) {}
            }
            document.body.style.userSelect = "none";
        };

        const onPointerMove = (e) => {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            if (!hasMoved && Math.hypot(deltaX, deltaY) > 3) {
                hasMoved = true;
                window.isDraggingBgDropdown = true;
                modalEl.dataset.hasMoved = "true";
            }

            if (hasMoved) {
                let newLeft = initialLeft + deltaX;
                let newTop = initialTop + deltaY;

                const modalWidth = modalEl.offsetWidth || 235;
                const minLeft = 10;
                const maxLeft = Math.max(10, window.innerWidth - modalWidth - 10);
                const minTop = 10;
                const maxTop = Math.max(10, window.innerHeight - 50);

                newLeft = Math.max(minLeft, Math.min(newLeft, maxLeft));
                newTop = Math.max(minTop, Math.min(newTop, maxTop));

                modalEl.style.left = `${newLeft}px`;
                modalEl.style.top = `${newTop}px`;
                modalEl.style.right = "auto";
                modalEl.style.bottom = "auto";
                modalEl.style.margin = "0";
            }
        };

        const onPointerUp = (e) => {
            if (!isDragging) return;
            isDragging = false;
            if (handleEl.releasePointerCapture) {
                try { handleEl.releasePointerCapture(e.pointerId); } catch(err) {}
            }
            document.body.style.userSelect = "";
            setTimeout(() => {
                window.isDraggingBgDropdown = false;
            }, 60);
        };

        handleEl.addEventListener("pointerdown", onPointerDown);
        handleEl.addEventListener("pointermove", onPointerMove);
        handleEl.addEventListener("pointerup", onPointerUp);
        handleEl.addEventListener("pointercancel", onPointerUp);
    }

    const bgDragHeader = document.getElementById("gmBgDragHeader") || dropdown.querySelector(".gm-bg-header");
    if (bgDragHeader) {
        makeDropdownDraggable(dropdown, bgDragHeader);
    }

    // Tombol close dropdown background
    const closeBgBtn = document.getElementById("closeBgDropdownBtn");
    if (closeBgBtn) {
        closeBgBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdown.style.display = "none";
        });
    }

    // Tutup saat klik di luar
    document.addEventListener("click", (e) => {
        if (window.isDraggingBgDropdown) return;
        if (!dropdown.contains(e.target) && !colorButton.contains(e.target)) {
            // Cek apakah klik pada popup token editor atau gradient modal
            if (e.target.closest(".gm-token-editor-popup") || e.target.closest(".gm-token-popup") || e.target.closest("#gmGradientModal")) {
                return;
            }
            dropdown.style.display = "none";
        }
    });

    // Tab Switcher
    dropdown.querySelectorAll(".gm-bg-tab").forEach(tab => {
        tab.addEventListener("click", () => {
            dropdown.querySelectorAll(".gm-bg-tab").forEach(t => t.classList.remove("active"));
            dropdown.querySelectorAll(".gm-bg-tab-content").forEach(c => c.classList.remove("active"));

            tab.classList.add("active");
            const tabKey = tab.dataset.tab;
            if (tabKey === "solid") {
                document.getElementById("gmBgTabSolid")?.classList.add("active");
                window.gmColorMode = "solid";
            } else if (tabKey === "gradient") {
                document.getElementById("gmBgTabGradient")?.classList.add("active");
                window.gmColorMode = "gradient";
            }
        });
    });

    // Native Color Picker input & change
    const picker = dropdown.querySelector("#gm-color-picker");
    if (picker) {
        picker.addEventListener("input", (e) => {
            const val = e.target.value.toUpperCase();
            window.gmBgColorEngine.setBackgroundToActive(val, false, "solid");
        });
        picker.addEventListener("change", (e) => {
            const val = e.target.value.toUpperCase();
            window.gmBgColorEngine.setBackgroundToActive(val, false, "solid");
        });
    }

    // Text Input (#gm-color-hex)
    const hexInput = dropdown.querySelector("#gm-color-hex");
    if (hexInput) {
        hexInput.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                const val = hexInput.value.trim();
                if (val) window.gmBgColorEngine.setBackgroundToActive(val, val.startsWith("var("), "solid");
            }
        });
        hexInput.addEventListener("change", () => {
            const val = hexInput.value.trim();
            if (val) window.gmBgColorEngine.setBackgroundToActive(val, val.startsWith("var("), "solid");
        });
        hexInput.addEventListener("blur", () => {
            const val = hexInput.value.trim();
            if (val) window.gmBgColorEngine.setBackgroundToActive(val, val.startsWith("var("), "solid");
        });
    }

    // Quick Swatches
    dropdown.querySelectorAll(".gm-bg-swatch-item").forEach(item => {
        item.addEventListener("click", () => {
            const col = item.dataset.color;
            if (col) {
                window.gmBgColorEngine.setBackgroundToActive(col, false, "solid");
            }
        });
    });

    // Format Inputs (RGB, HSL, CMYK)
    const rgbInput = dropdown.querySelector("#gm-rgb");
    const hslInput = dropdown.querySelector("#gm-hsl");
    const cmykInput = dropdown.querySelector("#gm-cmyk");

    function bindFormatInput(inp, fn) {
        if (!inp) return;
        inp.addEventListener("keydown", (e) => {
            if (e.key === "Enter") {
                const hex = fn(inp.value);
                if (hex) window.gmBgColorEngine.setBackgroundToActive(hex, false, "solid");
            }
        });
        inp.addEventListener("change", () => {
            const hex = fn(inp.value);
            if (hex) window.gmBgColorEngine.setBackgroundToActive(hex, false, "solid");
        });
    }

    bindFormatInput(rgbInput, (v) => {
        const m = v.match(/(\d+)[^\d]+(\d+)[^\d]+(\d+)/);
        return m ? window.gmBgColorEngine.rgbToHex(+m[1], +m[2], +m[3]) : null;
    });

    // Global Selection Tracking
    document.addEventListener("click", (e) => {
        const obj = e.target.closest(".gm-object");
        if (obj) {
            window.lastSelectedGmObject = obj;
            window.gmBgColorEngine.syncWithActiveObject();
        }
    }, true);

    window.addEventListener("gm-active-tick", () => {
        window.gmBgColorEngine.syncWithActiveObject();
    });

    /* ==========================================================================
       TOKEN INTERACTION DELEGATION (ADD, APPLY, EDIT, DELETE)
       ========================================================================== */
    // Klik "+ variable"
    document.body.addEventListener("click", (e) => {
        const addBtn = e.target.closest("#gmBgAddTokenBtn") || (e.target.closest(".gm-add-token") && e.target.closest("#gm-bg-color-variables"));
        if (addBtn) {
            const rect = addBtn.getBoundingClientRect();
            openBgTokenEditorPopup(rect.left, rect.bottom + 8, "", "#3b82f6", (name, color) => {
                window.gmBgColorEngine.addToken(name, color);
            });
            return;
        }

        // Klik token chip untuk menerapkan variabel ke active object
        const tokenChip = e.target.closest(".gm-color-token");
        if (tokenChip && tokenChip.closest("#gm-bg-color-variables") && !tokenChip.classList.contains("gm-add-token")) {
            if (e.target.tagName === "INPUT") return; // biarkan picker dot berjalan
            const varName = tokenChip.dataset.name;
            if (varName) {
                window.gmBgColorEngine.setBackgroundToActive(`var(${varName})`, true, "solid");
            }
        }
    });

    // Input warna pada picker dot chip secara real-time
    document.body.addEventListener("input", (e) => {
        if (e.target.tagName === "INPUT" && e.target.type === "color") {
            const tokenChip = e.target.closest(".gm-color-token");
            if (tokenChip && tokenChip.dataset.name && tokenChip.closest("#gm-bg-color-variables")) {
                const varName = tokenChip.dataset.name;
                window.gmBgColorEngine.tokens[varName] = e.target.value;
                window.gmBgColorEngine.applyVariables();
            }
        }
    });

    // Change event pada picker dot chip (simpan ke database)
    document.body.addEventListener("change", (e) => {
        if (e.target.tagName === "INPUT" && e.target.type === "color") {
            const tokenChip = e.target.closest(".gm-color-token");
            if (tokenChip && tokenChip.closest("#gm-bg-color-variables")) {
                window.gmBgColorEngine.save();
            }
        }
    });

    // Right-click context menu (Edit & Delete)
    document.body.addEventListener("contextmenu", (e) => {
        const tokenChip = e.target.closest(".gm-color-token:not(.gm-add-token)");
        if (!tokenChip || !tokenChip.closest("#gm-bg-color-variables")) return;

        e.preventDefault();
        const name = tokenChip.dataset.name;
        if (!name) return;

        document.querySelectorAll(".gm-token-popup").forEach(p => p.remove());

        const popup = document.createElement("div");
        popup.className = "gm-token-popup";
        popup.innerHTML = `
            <div class="gm-token-action" data-action="edit" style="padding:6px 12px;cursor:pointer;display:flex;align-items:center;gap:8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> Edit Variable
            </div>
            <div class="gm-token-action" data-action="delete" style="padding:6px 12px;cursor:pointer;color:#dc2626;display:flex;align-items:center;gap:8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg> Delete Variable
            </div>
        `;

        let cx = e.clientX;
        let cy = e.clientY;
        if (cx + 140 > window.innerWidth) cx = window.innerWidth - 150;
        if (cy + 80 > window.innerHeight) cy = window.innerHeight - 90;

        Object.assign(popup.style, {
            position: "fixed",
            left: `${cx}px`,
            top: `${cy}px`,
            background: "#ffffff",
            border: "1px solid #e5e7eb",
            borderRadius: "8px",
            boxShadow: "0 10px 20px -3px rgba(0, 0, 0, 0.15)",
            zIndex: "999999",
            padding: "4px 0",
            fontSize: "12px",
            color: "#374151",
            minWidth: "135px",
            fontWeight: "500"
        });

        popup.querySelectorAll(".gm-token-action").forEach(act => {
            act.addEventListener("mouseenter", () => act.style.background = "#f3f4f6");
            act.addEventListener("mouseleave", () => act.style.background = "transparent");
        });

        document.body.appendChild(popup);

        popup.addEventListener("click", ev => {
            const action = ev.target.closest(".gm-token-action")?.dataset.action;
            if (action === "edit") {
                openBgTokenEditorPopup(cx, cy, name, window.gmBgColorEngine.tokens[name], (newName, newColor) => {
                    window.gmBgColorEngine.editToken(name, newName, newColor);
                });
            } else if (action === "delete") {
                window.gmBgColorEngine.deleteToken(name);
            }
            popup.remove();
        });

        setTimeout(() => {
            const close = (eClick) => {
                if (!popup.contains(eClick.target)) {
                    popup.remove();
                    document.removeEventListener("mousedown", close);
                }
            };
            document.addEventListener("mousedown", close);
        }, 10);
    });

    /* ==========================================================================
       GRADIENT STUDIO ENGINE
       ========================================================================== */
    initGradientStudio();
});

function initGradientStudio() {
    const modal = document.getElementById("gmGradientModal");
    const openBtn = document.getElementById("openGradientEditor");
    const closeBtn = document.getElementById("closeGradientModal");
    const cancelBtn = document.getElementById("cancelGradientBtn");
    const applyBtn = document.getElementById("applyGradientBtn");
    const addStopBtn = document.getElementById("addStopBtn");
    const saveBtn = document.getElementById("saveGradientBtn");
    const deleteBtn = document.getElementById("deleteGradientBtn");
    const savedList = document.getElementById("savedGradients");
    const savedListModal = document.getElementById("savedGradientsModal");
    const recentList = document.getElementById("recentGradients");
    const recentListModal = document.getElementById("recentGradientsModal");
    const previewTab = document.getElementById("gradientPreview");
    const previewModal = document.getElementById("gradientPreviewModal");
    const trackBar = document.getElementById("gradientTrackBar");
    const stopsContainer = document.getElementById("gradientStops");
    const angleSlider = document.getElementById("gradientAngle");
    const angleNumber = document.getElementById("gradientAngleInput");
    const angleValue = document.getElementById("angleValue");
    const angleDisplay = document.getElementById("gradientAngleDisplay");
    const cssCodeBox = document.getElementById("gradientCSSCode");
    const copyCSSBtn = document.getElementById("copyGradientCSSBtn");
    const stopCountBadge = document.getElementById("gradientStopCount");
    const footerSummary = document.getElementById("gradientFooterSummary");
    const tokensQuickBar = document.getElementById("gradientTokensQuickBar");
    const angleGroup = document.getElementById("gradientAngleGroup");
    const dragHeader = document.getElementById("gmGradientDragHeader") || modal?.querySelector(".gm-gradient-modal-header");

    if (!modal) return;
    if (modal.parentElement !== document.body) document.body.appendChild(modal);

    function makeModalDraggable(modalEl, handleEl) {
        if (!modalEl || !handleEl) return;

        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;

        handleEl.addEventListener('pointerdown', (e) => {
            // Jangan drag jika yang diklik adalah tombol close atau elemen interaktif
            if (e.target.closest('#closeGradientModal') || e.target.closest('.gm-gradient-close') || e.target.closest('button')) {
                return;
            }

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;

            const rect = modalEl.getBoundingClientRect();
            initialLeft = rect.left;
            initialTop = rect.top;

            handleEl.setPointerCapture(e.pointerId);
            document.body.style.userSelect = 'none';
        });

        handleEl.addEventListener('pointermove', (e) => {
            if (!isDragging) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            let newLeft = initialLeft + deltaX;
            let newTop = initialTop + deltaY;

            // Batas layar agar modal tetap mudah diakses
            const modalWidth = modalEl.offsetWidth || 520;
            const minLeft = 10;
            const maxLeft = Math.max(10, window.innerWidth - modalWidth - 10);
            const minTop = 10;
            const maxTop = Math.max(10, window.innerHeight - 60);

            newLeft = Math.max(minLeft, Math.min(newLeft, maxLeft));
            newTop = Math.max(minTop, Math.min(newTop, maxTop));

            modalEl.style.left = `${newLeft}px`;
            modalEl.style.top = `${newTop}px`;
            modalEl.style.right = 'auto';
            modalEl.style.bottom = 'auto';
            modalEl.style.margin = '0';
        });

        const stopDrag = (e) => {
            if (!isDragging) return;
            isDragging = false;
            try {
                handleEl.releasePointerCapture(e.pointerId);
            } catch (err) {}
            document.body.style.userSelect = '';
        };

        handleEl.addEventListener('pointerup', stopDrag);
        handleEl.addEventListener('pointercancel', stopDrag);
    }

    if (dragHeader) {
        makeModalDraggable(modal, dragHeader);
    }

    let gradientStops = [
        { color: '#2563eb', position: 0 },
        { color: '#9333ea', position: 100 }
    ];
    let gradientType = 'linear';
    let gradientAngle = 90;
    let currentGradient = '';
    let selectedPresetIndex = null;
    let selectedPresetType = null; // 'saved' atau 'recent'
    let lastActiveStopIndex = 0;

    function makeGradientString() {
        const sortedStops = [...gradientStops].sort((a, b) => a.position - b.position);
        const stopsStr = sortedStops.map(s => `${s.color} ${s.position}%`).join(', ');
        let css = '';
        if (gradientType === 'linear') {
            css = `linear-gradient(${gradientAngle}deg, ${stopsStr})`;
        } else if (gradientType === 'radial') {
            css = `radial-gradient(circle, ${stopsStr})`;
        } else if (gradientType === 'conic') {
            css = `conic-gradient(from ${gradientAngle}deg, ${stopsStr})`;
        }
        if (cssCodeBox) cssCodeBox.value = `background: ${css};`;
        return css;
    }

    function renderPreview() {
        currentGradient = makeGradientString();

        if (previewTab) previewTab.style.background = currentGradient;
        if (previewModal) previewModal.style.background = currentGradient;

        // Render visual on horizontal track bar
        if (trackBar) {
            const sortedStops = [...gradientStops].sort((a, b) => a.position - b.position);
            const linearTrack = `linear-gradient(to right, ${sortedStops.map(s => `${s.color} ${s.position}%`).join(', ')})`;
            trackBar.style.background = linearTrack;
        }

        if (angleValue) angleValue.textContent = `${gradientAngle}°`;
        if (angleDisplay) angleDisplay.textContent = gradientType === 'radial' ? 'Radial' : `${gradientAngle}°`;

        const typeLabel = gradientType.charAt(0).toUpperCase() + gradientType.slice(1);
        if (footerSummary) {
            footerSummary.textContent = `${typeLabel} gradient • ${gradientType !== 'radial' ? gradientAngle + '° • ' : ''}${gradientStops.length} color stops`;
        }
        if (stopCountBadge) {
            stopCountBadge.textContent = `${gradientStops.length} stops`;
        }
    }

    function renderStops() {
        if (!stopsContainer) return;
        stopsContainer.innerHTML = '';

        gradientStops.forEach((stop, i) => {
            const item = document.createElement('div');
            item.className = 'gm-gradient-stop-item';
            item.dataset.index = i;

            const hexVal = stop.color.startsWith('#') ? stop.color : '#2563eb';

            item.innerHTML = `
                <div class="gm-stop-picker-wrap" title="Pick color">
                    <input type="color" value="${hexVal}" data-index="${i}" class="gm-stop-color-input">
                    <div class="gm-stop-swatch" style="background:${stop.color};"></div>
                </div>
                <input type="text" value="${stop.color}" data-index="${i}" class="gm-stop-hex-input" placeholder="#hex or var()">
                <input type="range" min="0" max="100" value="${stop.position}" data-index="${i}" class="gm-stop-slider">
                <input type="number" min="0" max="100" value="${stop.position}" data-index="${i}" class="gm-stop-pos-input">
                <span style="font-size:11px;color:#9ca3af;font-weight:600;">%</span>
                <button type="button" class="gm-stop-remove-btn" data-index="${i}" title="Remove stop">&times;</button>
            `;

            // Event Listeners for Stop Row
            const colorPicker = item.querySelector('.gm-stop-color-input');
            const hexInp = item.querySelector('.gm-stop-hex-input');
            const swatch = item.querySelector('.gm-stop-swatch');
            const slider = item.querySelector('.gm-stop-slider');
            const posInp = item.querySelector('.gm-stop-pos-input');
            const removeBtn = item.querySelector('.gm-stop-remove-btn');

            const setStopActive = () => { lastActiveStopIndex = i; };
            hexInp.addEventListener('focus', setStopActive);
            colorPicker.addEventListener('focus', setStopActive);

            colorPicker.addEventListener('input', (e) => {
                setStopActive();
                stop.color = e.target.value.toUpperCase();
                hexInp.value = stop.color;
                swatch.style.background = stop.color;
                renderPreview();
            });

            hexInp.addEventListener('input', (e) => {
                setStopActive();
                stop.color = e.target.value.trim();
                swatch.style.background = stop.color;
                if (/^#[0-9A-Fa-f]{6}$/i.test(stop.color)) {
                    colorPicker.value = stop.color;
                }
                renderPreview();
            });

            slider.addEventListener('input', (e) => {
                setStopActive();
                stop.position = parseInt(e.target.value, 10) || 0;
                posInp.value = stop.position;
                renderPreview();
            });

            posInp.addEventListener('input', (e) => {
                setStopActive();
                let p = parseInt(e.target.value, 10);
                if (isNaN(p)) p = 0;
                if (p < 0) p = 0;
                if (p > 100) p = 100;
                stop.position = p;
                slider.value = p;
                renderPreview();
            });

            removeBtn.addEventListener('click', () => {
                if (gradientStops.length <= 2) {
                    alert('A gradient must have at least 2 color stops.');
                    return;
                }
                gradientStops.splice(i, 1);
                renderStops();
                renderPreview();
            });

            stopsContainer.appendChild(item);
        });

        renderPreview();
    }

    function renderTokensBar() {
        if (!tokensQuickBar) return;
        tokensQuickBar.innerHTML = '';
        const tokens = window.gmBgColorEngine.tokens || {};
        const entries = Object.entries(tokens);

        if (!entries.length) {
            tokensQuickBar.innerHTML = '<span style="font-size:10px;color:#9ca3af;">No variables yet</span>';
            return;
        }

        entries.slice(0, 5).forEach(([name, val]) => {
            const varName = name.startsWith('--') ? name : `--${name}`;
            const pill = document.createElement('div');
            pill.className = 'gm-grad-token-pill';
            pill.title = `Apply var(${varName}) to active stop`;
            pill.innerHTML = `
                <div class="gm-grad-token-dot" style="background:${val};"></div>
                <span>${varName}</span>
            `;
            pill.addEventListener('click', () => {
                const targetIdx = Math.min(lastActiveStopIndex, gradientStops.length - 1);
                if (gradientStops[targetIdx]) {
                    gradientStops[targetIdx].color = `var(${varName})`;
                    renderStops();
                    renderPreview();
                }
            });
            tokensQuickBar.appendChild(pill);
        });
    }

    function setAngle(ang) {
        gradientAngle = parseInt(ang, 10) || 0;
        if (gradientAngle < 0) gradientAngle = (gradientAngle % 360) + 360;
        gradientAngle = gradientAngle % 360;

        if (angleSlider) angleSlider.value = gradientAngle;
        if (angleNumber) angleNumber.value = gradientAngle;

        document.querySelectorAll('.gm-angle-chip').forEach(chip => {
            if (parseInt(chip.dataset.angle, 10) === gradientAngle) {
                chip.classList.add('active');
            } else {
                chip.classList.remove('active');
            }
        });

        renderPreview();
    }

    // Angle Slider & Input Listeners
    if (angleSlider) {
        angleSlider.addEventListener('input', (e) => setAngle(e.target.value));
    }
    if (angleNumber) {
        angleNumber.addEventListener('input', (e) => setAngle(e.target.value));
    }

    // Quick Angle Buttons
    document.querySelectorAll('.gm-angle-chip').forEach(chip => {
        chip.addEventListener('click', () => {
            const a = chip.dataset.angle;
            if (a !== undefined) setAngle(a);
        });
    });

    // Gradient Type Buttons
    modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            modal.querySelectorAll('.gm-gradient-type-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            gradientType = btn.dataset.type;

            if (angleGroup) {
                if (gradientType === 'radial') {
                    angleGroup.style.opacity = '0.4';
                    angleGroup.style.pointerEvents = 'none';
                } else {
                    angleGroup.style.opacity = '1';
                    angleGroup.style.pointerEvents = 'auto';
                }
            }

            renderPreview();
        });
    });

    // Interactive Track Bar Click to Add Stop
    if (trackBar) {
        trackBar.addEventListener('click', (e) => {
            const rect = trackBar.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const pct = Math.max(0, Math.min(100, Math.round((clickX / rect.width) * 100)));

            // Sisipkan warna default baru di persentase tersebut
            gradientStops.push({ color: '#38bdf8', position: pct });
            gradientStops.sort((a, b) => a.position - b.position);
            lastActiveStopIndex = gradientStops.findIndex(s => s.position === pct);
            renderStops();
            renderPreview();
        });
    }

    // Add Stop Button
    if (addStopBtn) {
        addStopBtn.addEventListener('click', () => {
            const lastStop = gradientStops[gradientStops.length - 1];
            const newPos = lastStop ? Math.min(100, lastStop.position + 15) : 50;
            gradientStops.push({ color: '#f43f5e', position: newPos });
            lastActiveStopIndex = gradientStops.length - 1;
            renderStops();
            renderPreview();
        });
    }

    // 1-Click Copy CSS Button
    if (copyCSSBtn) {
        copyCSSBtn.addEventListener('click', () => {
            const css = makeGradientString();
            navigator.clipboard.writeText(`background: ${css};`).then(() => {
                copyCSSBtn.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
                setTimeout(() => {
                    copyCSSBtn.innerHTML = '<i class="fa-regular fa-copy"></i> Copy';
                }, 1500);
            });
        });
    }

    // Parse existing gradient string from element
    function parseGradientString(gradStr) {
        if (!gradStr || typeof gradStr !== 'string' || !gradStr.includes('-gradient')) return null;

        let type = 'linear';
        if (gradStr.includes('radial-gradient')) type = 'radial';
        else if (gradStr.includes('conic-gradient')) type = 'conic';

        let angle = 90;
        const angleMatch = gradStr.match(/(\d+)deg/);
        if (angleMatch) angle = parseInt(angleMatch[1], 10);

        // Cari stop matches misal "#fff 0%", "rgb(0,0,0) 50%", "var(--token) 100%"
        const stopMatches = [];
        const regex = /((?:#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|var\([^)]+\)|[a-zA-Z]+))\s*(\d+)%/g;
        let match;
        while ((match = regex.exec(gradStr)) !== null) {
            stopMatches.push({ color: match[1].trim(), position: parseInt(match[2], 10) });
        }

        if (stopMatches.length >= 2) {
            return { type, angle, stops: stopMatches };
        }
        return null;
    }

    function getSavedGradients() {
        return JSON.parse(localStorage.getItem('gmGradientPresets') || '[]');
    }

    function setSavedGradients(list) {
        localStorage.setItem('gmGradientPresets', JSON.stringify(list));
    }

    function getRecentGradients() {
        return JSON.parse(localStorage.getItem('gmRecentGradients') || '[]');
    }

    function setRecentGradients(list) {
        localStorage.setItem('gmRecentGradients', JSON.stringify(list));
    }

    function addRecentGradient(grad) {
        if (!grad || typeof grad !== 'string' || !grad.includes('-gradient')) return;
        const saved = getSavedGradients();
        if (saved.includes(grad)) return;

        let recent = getRecentGradients().filter(g => g !== grad);
        recent.unshift(grad);
        if (recent.length > 16) recent.pop();
        setRecentGradients(recent);
        loadGradientsLists();
    }
    window.gmAddRecentGradient = addRecentGradient;

    function applyGradientDirectly(grad) {
        // 1. Langsung terapkan ke elemen visual aktif di kanvas
        window.gmBgColorEngine.setBackgroundToActive(grad, false, 'gradient');

        // 2. Simpan metadata gradient pada objek visual aktif
        const actives = window.gmBgColorEngine.getActiveVisuals();
        actives.forEach(el => {
            el.dataset.gmGradient = grad;
            el.dataset.gmMode = 'gradient';
        });

        // 3. Update preview kotak tab gradient di dropdown
        if (previewTab) previewTab.style.background = grad;

        // 4. Update preview modal jika modal sedang dibuka
        if (previewModal) previewModal.style.background = grad;

        // 5. Jika belum tersimpan, pastikan masuk ke Recent Gradients
        if (!getSavedGradients().includes(grad)) {
            addRecentGradient(grad);
        }
    }

    function loadGradientsLists() {
        const saved = getSavedGradients();
        const recent = getRecentGradients();

        // 1. Render Saved Gradients pada Dropdown Biasa (Shortcut langsung menerapkan saat diklik)
        if (savedList) {
            savedList.innerHTML = '';
            if (!saved.length) {
                savedList.innerHTML = '<div class="gm-gradient-empty-hint">Belum ada gradien tersimpan</div>';
            } else {
                saved.forEach((grad, i) => {
                    const d = document.createElement('div');
                    d.className = 'gm-grad-preset-item';
                    d.style.background = grad;
                    d.title = `${grad}\n(Klik untuk menerapkan, klik kanan untuk opsi delete)`;
                    d.addEventListener('click', (e) => {
                        e.stopPropagation();
                        applyGradientDirectly(grad);
                        document.querySelectorAll('#savedGradients .gm-grad-preset-item, #recentGradients .gm-grad-preset-item').forEach(el => el.classList.remove('selected'));
                        d.classList.add('selected');

                        const parsed = parseGradientString(grad);
                        if (parsed) {
                            gradientType = parsed.type;
                            modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
                                btn.classList.toggle('active', btn.dataset.type === gradientType);
                            });
                            setAngle(parsed.angle);
                            gradientStops = parsed.stops;
                            renderStops();
                            renderPreview();
                        }
                    });

                    // Klik kanan untuk delete dengan konfirmasi popup
                    d.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        openGradientContextMenu(e.clientX, e.clientY, grad, i, 'saved');
                    });

                    savedList.appendChild(d);
                });
            }
        }

        // 2. Render Recent Gradients pada Dropdown Biasa (Shortcut langsung menerapkan saat diklik)
        if (recentList) {
            recentList.innerHTML = '';
            if (!recent.length) {
                recentList.innerHTML = '<div class="gm-gradient-empty-hint">Belum ada gradien recent</div>';
            } else {
                recent.forEach((grad, i) => {
                    const d = document.createElement('div');
                    d.className = 'gm-grad-preset-item';
                    d.style.background = grad;
                    d.title = `${grad}\n(Recent - Klik untuk menerapkan, klik kanan untuk opsi delete)`;
                    d.addEventListener('click', (e) => {
                        e.stopPropagation();
                        applyGradientDirectly(grad);
                        document.querySelectorAll('#savedGradients .gm-grad-preset-item, #recentGradients .gm-grad-preset-item').forEach(el => el.classList.remove('selected'));
                        d.classList.add('selected');

                        const parsed = parseGradientString(grad);
                        if (parsed) {
                            gradientType = parsed.type;
                            modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
                                btn.classList.toggle('active', btn.dataset.type === gradientType);
                            });
                            setAngle(parsed.angle);
                            gradientStops = parsed.stops;
                            renderStops();
                            renderPreview();
                        }
                    });

                    // Klik kanan untuk delete dengan konfirmasi popup
                    d.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        openGradientContextMenu(e.clientX, e.clientY, grad, i, 'recent');
                    });

                    recentList.appendChild(d);
                });
            }
        }

        // 3. Render Saved Gradients di Studio Modal (Memuat ke editor)
        if (savedListModal) {
            savedListModal.innerHTML = '';
            if (!saved.length) {
                savedListModal.innerHTML = '<div class="gm-gradient-empty-hint">Belum ada gradien tersimpan. Gunakan tombol "Save Current" di atas.</div>';
            } else {
                saved.forEach((grad, i) => {
                    const d = document.createElement('div');
                    d.className = 'gm-grad-preset-item';
                    d.style.background = grad;
                    d.title = `${grad}\n(Klik untuk menerapkan ke objek, klik kanan untuk menghapus)`;
                    if (selectedPresetType === 'saved' && selectedPresetIndex === i) {
                        d.classList.add('selected');
                    }
                    d.addEventListener('click', () => {
                        modal.querySelectorAll('.gm-grad-preset-item').forEach(p => p.classList.remove('selected'));
                        d.classList.add('selected');
                        selectedPresetIndex = i;
                        selectedPresetType = 'saved';

                        // Langsung terapkan ke objek aktif di kanvas saat diklik
                        applyGradientDirectly(grad);

                        const parsed = parseGradientString(grad);
                        if (parsed) {
                            gradientType = parsed.type;
                            modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
                                btn.classList.toggle('active', btn.dataset.type === gradientType);
                            });
                            setAngle(parsed.angle);
                            gradientStops = parsed.stops;
                            renderStops();
                            renderPreview();
                        } else {
                            currentGradient = grad;
                            renderPreview();
                        }
                    });

                    // Klik kanan untuk delete dengan konfirmasi popup
                    d.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        openGradientContextMenu(e.clientX, e.clientY, grad, i, 'saved');
                    });

                    savedListModal.appendChild(d);
                });
            }
        }

        // 4. Render Recent Gradients di Studio Modal (Memuat ke editor)
        if (recentListModal) {
            recentListModal.innerHTML = '';
            if (!recent.length) {
                recentListModal.innerHTML = '<div class="gm-gradient-empty-hint">Belum ada gradien recent</div>';
            } else {
                recent.forEach((grad, i) => {
                    const d = document.createElement('div');
                    d.className = 'gm-grad-preset-item';
                    d.style.background = grad;
                    d.title = `${grad}\n(Klik untuk menerapkan ke objek, klik kanan untuk menghapus)`;
                    if (selectedPresetType === 'recent' && selectedPresetIndex === i) {
                        d.classList.add('selected');
                    }
                    d.addEventListener('click', () => {
                        modal.querySelectorAll('.gm-grad-preset-item').forEach(p => p.classList.remove('selected'));
                        d.classList.add('selected');
                        selectedPresetIndex = i;
                        selectedPresetType = 'recent';

                        // Langsung terapkan ke objek aktif di kanvas saat diklik
                        applyGradientDirectly(grad);

                        const parsed = parseGradientString(grad);
                        if (parsed) {
                            gradientType = parsed.type;
                            modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
                                btn.classList.toggle('active', btn.dataset.type === gradientType);
                            });
                            setAngle(parsed.angle);
                            gradientStops = parsed.stops;
                            renderStops();
                            renderPreview();
                        } else {
                            currentGradient = grad;
                            renderPreview();
                        }
                    });

                    // Klik kanan untuk delete dengan konfirmasi popup
                    d.addEventListener('contextmenu', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        openGradientContextMenu(e.clientX, e.clientY, grad, i, 'recent');
                    });

                    recentListModal.appendChild(d);
                });
            }
        }
    }

    function openGradientContextMenu(cx, cy, grad, index, type) {
        document.querySelectorAll('.gm-grad-context-popup').forEach(p => p.remove());

        const popup = document.createElement('div');
        popup.className = 'gm-grad-context-popup';
        popup.innerHTML = `
            <div class="gm-grad-context-item" data-action="delete">
                <i class="fa-regular fa-trash-can" style="color:#dc2626;width:14px;"></i>
                <span>Delete ${type === 'saved' ? 'Saved Gradient' : 'Recent Gradient'}</span>
            </div>
        `;

        let left = cx;
        let top = cy;
        if (left + 175 > window.innerWidth) left = window.innerWidth - 185;
        if (top + 60 > window.innerHeight) top = window.innerHeight - 70;

        popup.style.left = `${left}px`;
        popup.style.top = `${top}px`;

        document.body.appendChild(popup);

        popup.addEventListener('click', (e) => {
            const item = e.target.closest('.gm-grad-context-item');
            if (!item) return;
            popup.remove();

            showDeleteConfirmationPopup(grad, type, () => {
                deleteGradientItem(index, type);
            });
        });

        setTimeout(() => {
            const onMouseDown = (ev) => {
                if (!popup.contains(ev.target)) {
                    popup.remove();
                    document.removeEventListener('mousedown', onMouseDown);
                }
            };
            document.addEventListener('mousedown', onMouseDown);
        }, 10);
    }

    function showDeleteConfirmationPopup(grad, type, onConfirm) {
        document.querySelectorAll('.gm-confirm-modal-overlay').forEach(el => el.remove());

        const overlay = document.createElement('div');
        overlay.className = 'gm-confirm-modal-overlay';
        overlay.innerHTML = `
            <div class="gm-confirm-modal-box">
                <div class="gm-confirm-modal-header">
                    <div class="gm-confirm-icon-wrap">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>
                    <div>
                        <h4 class="gm-confirm-modal-title">Delete ${type === 'saved' ? 'Saved' : 'Recent'} Gradient?</h4>
                        <p class="gm-confirm-modal-desc">Are you sure you want to delete this preset from your ${type === 'saved' ? 'saved presets' : 'recent list'}?</p>
                    </div>
                </div>

                <div class="gm-confirm-preview-wrap">
                    <div class="gm-confirm-gradient-swatch" style="background: ${grad};"></div>
                </div>

                <div class="gm-confirm-modal-footer">
                    <button type="button" class="gm-btn-secondary gm-confirm-cancel-btn">Cancel</button>
                    <button type="button" class="gm-confirm-delete-btn">
                        <i class="fa-regular fa-trash-can"></i> Delete
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        const closePopup = () => overlay.remove();

        overlay.querySelector('.gm-confirm-cancel-btn').addEventListener('click', closePopup);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closePopup();
        });

        overlay.querySelector('.gm-confirm-delete-btn').addEventListener('click', () => {
            closePopup();
            if (typeof onConfirm === 'function') onConfirm();
        });
    }

    function deleteGradientItem(index, type) {
        if (type === 'saved') {
            let saved = getSavedGradients();
            if (index >= 0 && index < saved.length) {
                saved.splice(index, 1);
                setSavedGradients(saved);
            }
        } else if (type === 'recent') {
            let recent = getRecentGradients();
            if (index >= 0 && index < recent.length) {
                recent.splice(index, 1);
                setRecentGradients(recent);
            }
        }
        selectedPresetIndex = null;
        selectedPresetType = null;
        loadGradientsLists();
    }

    function loadSavedGradients() {
        loadGradientsLists();
    }

    // Open Modal
    if (openBtn) {
        openBtn.addEventListener('click', () => {
            window.gmColorMode = 'gradient';

            // 1. Sembunyikan dropdown picker warna agar kanvas lebih leluasa
            const dropdown = document.getElementById("gmColorDropdown") || document.querySelector(".gm-color-dropdown");
            if (dropdown) dropdown.style.display = "none";

            // 2. Pastikan modal berada langsung di bawah document.body
            if (modal.parentElement !== document.body) {
                document.body.appendChild(modal);
            }

            // 3. Posisikan di tengah viewport tanpa animasi lompatan dari kiri atas
            if (!modal.dataset.positioned) {
                modal.style.visibility = 'hidden';
                modal.style.display = 'block';
                modal.classList.add('active');

                const w = modal.offsetWidth || 520;
                const h = modal.offsetHeight || 380;
                const left = Math.max(10, Math.round((window.innerWidth - w) / 2));
                const top = Math.max(10, Math.round((window.innerHeight - h) / 2));

                modal.style.left = `${left}px`;
                modal.style.top = `${top}px`;
                modal.style.right = 'auto';
                modal.style.bottom = 'auto';
                modal.style.margin = '0';
                modal.style.visibility = 'visible';
                modal.dataset.positioned = 'true';
            } else {
                const rect = modal.getBoundingClientRect();
                if (rect.left < 0 || rect.left > window.innerWidth - 100 || rect.top < 0 || rect.top > window.innerHeight - 80) {
                    const w = modal.offsetWidth || 520;
                    const h = modal.offsetHeight || 380;
                    const left = Math.max(10, Math.round((window.innerWidth - w) / 2));
                    const top = Math.max(10, Math.round((window.innerHeight - h) / 2));
                    modal.style.left = `${left}px`;
                    modal.style.top = `${top}px`;
                }
                modal.style.display = 'block';
                modal.classList.add('active');
            }

            // Cek apakah elemen aktif memiliki gradien
            const actives = window.gmBgColorEngine.getActiveVisuals();
            if (actives.length) {
                const el = actives[0];
                const activeGrad = el.dataset.gmGradient || el.style.background || window.getComputedStyle(el).backgroundImage;
                const parsed = parseGradientString(activeGrad);
                if (parsed) {
                    gradientType = parsed.type;
                    gradientAngle = parsed.angle;
                    gradientStops = parsed.stops;
                    modal.querySelectorAll('.gm-gradient-type-btn').forEach(btn => {
                        btn.classList.toggle('active', btn.dataset.type === gradientType);
                    });
                    setAngle(gradientAngle);
                }
            }

            renderStops();
            renderTokensBar();
            loadGradientsLists();
            renderPreview();
        });
    }

    function closeModal() {
        modal.classList.remove('active');
        modal.style.display = 'none';
        window.gmColorMode = 'solid';
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);

    // Save Preset Button
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            const grad = makeGradientString();
            let saved = getSavedGradients();
            if (!saved.includes(grad)) {
                saved.unshift(grad);
                if (saved.length > 24) saved.pop();
                setSavedGradients(saved);

                // Hapus dari recent jika sebelumnya ada di recent
                let recent = getRecentGradients().filter(g => g !== grad);
                setRecentGradients(recent);

                loadGradientsLists();

                const orig = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Saved!';
                setTimeout(() => { saveBtn.innerHTML = orig; }, 1200);
            } else {
                alert('Gradien ini sudah ada di dalam Saved Gradients.');
            }
        });
    }

    // Delete Selected Preset Button (jika ada tombol delete yang memicu)
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            if (selectedPresetIndex === null) {
                alert('Silakan klik salah satu preset gradien untuk memilih yang ingin dihapus, atau klik kanan langsung pada preset.');
                return;
            }
            const grad = selectedPresetType === 'saved' ? getSavedGradients()[selectedPresetIndex] : getRecentGradients()[selectedPresetIndex];
            showDeleteConfirmationPopup(grad, selectedPresetType || 'saved', () => {
                deleteGradientItem(selectedPresetIndex, selectedPresetType || 'saved');
            });
        });
    }

    // Apply to Object Button (JANGAN LANGSUNG MENUTUP MODAL)
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            const grad = makeGradientString();
            window.gmBgColorEngine.setBackgroundToActive(grad, false, 'gradient');

            // Simpan juga ke dataset.gmGradient
            const actives = window.gmBgColorEngine.getActiveVisuals();
            actives.forEach(el => {
                el.dataset.gmGradient = grad;
                el.dataset.gmMode = 'gradient';
            });

            // Update preview di dropdown tab
            if (previewTab) previewTab.style.background = grad;

            // Masukkan ke recent jika belum tersimpan di saved
            if (!getSavedGradients().includes(grad)) {
                addRecentGradient(grad);
            }

            // Feedback visual tanpa menutup modal
            const originalHTML = applyBtn.innerHTML;
            applyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Applied!';
            applyBtn.style.background = '#16a34a';
            applyBtn.style.borderColor = '#15803d';
            setTimeout(() => {
                applyBtn.innerHTML = originalHTML;
                applyBtn.style.background = '';
                applyBtn.style.borderColor = '';
            }, 1200);
        });
    }

    loadGradientsLists();
    renderStops();
    renderTokensBar();
    renderPreview();
}
