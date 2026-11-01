/**
 * Greymist Component Manager
 * Mengatur penyimpanan objek kanvas sebagai komponen yang bisa digunakan kembali (reusable)
 * di semua project berdasarkan ID Pengguna (user_id).
 */
(function () {
    let activeCleanHtml = "";
    let activeTarget = null;
    let selectedCategory = "Custom";

    const GMComponentManager = {
        init: function () {
            this.bindEvents();
            this.loadComponents();
            document.addEventListener('gm-includes-updated', () => self.renderProjectIncludes());
        },

        bindEvents: function () {
            const self = this;

            // 1. Klik menu Save as Component dari Context Menu
            document.addEventListener("click", function (e) {
                const saveBtn = e.target.closest(".gm-save-component");
                if (saveBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    self.openSaveModal();
                }
            }, true); // Gunakan capture phase agar tidak diblokir stopPropagation dari menu-trigger

            // 2. Tombol Tutup / Batal Modal
            const closeBtn = document.getElementById("gmCloseSaveCompBtn");
            const cancelBtn = document.getElementById("gmCancelSaveCompBtn");
            const modalOverlay = document.getElementById("gm-save-comp-modal");

            if (closeBtn) closeBtn.addEventListener("click", () => self.closeSaveModal());
            if (cancelBtn) cancelBtn.addEventListener("click", () => self.closeSaveModal());

            if (modalOverlay) {
                modalOverlay.addEventListener("click", function (e) {
                    if (e.target === modalOverlay) {
                        self.closeSaveModal();
                    }
                });
            }

            // 3. Shortcut Keyboard: ESC untuk tutup, ENTER untuk simpan
            document.addEventListener("keydown", function (e) {
                const viewModal = document.getElementById("gm-view-comp-modal");
                if (e.key === "Escape") {
                    if (viewModal && (viewModal.classList.contains("active") || viewModal.style.display === "flex")) {
                        self.closeViewModal();
                        return;
                    }
                    if (modalOverlay && (modalOverlay.classList.contains("active") || modalOverlay.style.display === "flex")) {
                        self.closeSaveModal();
                        return;
                    }
                } else if (e.key === "Enter" && e.target.id === "gmCompNameInput") {
                    e.preventDefault();
                    self.saveComponent();
                }
            });

            // 4. Kategori Pills Switcher
            const pillsContainer = document.getElementById("gmCompCategoryPills");
            if (pillsContainer) {
                pillsContainer.addEventListener("click", function (e) {
                    const pill = e.target.closest(".gm-comp-pill");
                    if (!pill) return;

                    pillsContainer.querySelectorAll(".gm-comp-pill").forEach(p => p.classList.remove("active"));
                    pill.classList.add("active");
                    selectedCategory = pill.dataset.category || "Custom";
                });
            }

            // 5. Tombol Konfirmasi Simpan
            const confirmBtn = document.getElementById("gmConfirmSaveCompBtn");
            if (confirmBtn) {
                confirmBtn.addEventListener("click", function () {
                    self.saveComponent();
                });
            }

            // 6. Handler Hapus Komponen dari List
            document.addEventListener("click", function (e) {
                const delBtn = e.target.closest(".gm-comp-card-del-btn");
                if (delBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const compId = delBtn.dataset.id;
                    self.confirmDelete(compId);
                }
            });

            // 7. Handler Buka View Modal Detail Komponen (Icon Mata di Label atau Thumbnail)
            document.addEventListener("click", function (e) {
                const viewBtn = e.target.closest(".gm-comp-card-view-btn, .gm-thumb-quick-view");
                if (viewBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const compId = viewBtn.dataset.id;
                    self.openViewModal(compId);
                }
            });

            // 8. Handler Tutup View Modal
            const closeViewBtn = document.getElementById("gmCloseViewCompBtn");
            const closeViewBottomBtn = document.getElementById("gmViewCloseBottomBtn");
            const viewModalOverlay = document.getElementById("gm-view-comp-modal");

            if (closeViewBtn) closeViewBtn.addEventListener("click", () => self.closeViewModal());
            if (closeViewBottomBtn) closeViewBottomBtn.addEventListener("click", () => self.closeViewModal());
            if (viewModalOverlay) {
                viewModalOverlay.addEventListener("click", function (e) {
                    if (e.target === viewModalOverlay) {
                        self.closeViewModal();
                    }
                });
            }

            // 9. Tab Switcher di View Modal (Preview, HTML, CSS)
            document.addEventListener("click", function (e) {
                const tab = e.target.closest(".gm-view-tab");
                if (tab && tab.closest("#gm-view-comp-modal")) {
                    e.preventDefault();
                    const tabName = tab.dataset.tab;
                    self.switchViewTab(tabName);
                }
            });

            // 10. Viewport Device Switcher di View Modal
            document.addEventListener("click", function (e) {
                const btn = e.target.closest("#gmViewPreviewControls .gm-view-ctrl-btn[data-viewport]");
                if (btn) {
                    e.preventDefault();
                    const group = btn.closest(".gm-view-btn-group");
                    if (group) group.querySelectorAll(".gm-view-ctrl-btn").forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                    const viewport = document.getElementById("gmViewStageViewport");
                    if (viewport) {
                        viewport.style.width = btn.dataset.viewport || "100%";
                    }
                }
            });

            // 11. Background Switcher di View Modal
            document.addEventListener("click", function (e) {
                const btn = e.target.closest("#gmViewPreviewControls .gm-view-ctrl-btn[data-bg]");
                if (btn) {
                    e.preventDefault();
                    const group = btn.closest(".gm-view-btn-group");
                    if (group) group.querySelectorAll(".gm-view-ctrl-btn").forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                    const stage = document.getElementById("gmViewStageWrapper");
                    if (stage) {
                        stage.classList.remove("bg-grid", "bg-light", "bg-dark");
                        stage.classList.add("bg-" + btn.dataset.bg);
                    }
                }
            });

            // 12. Tombol Salin Kode (HTML / CSS)
            const copyCodeBtn = document.getElementById("gmViewCopyBtn");
            if (copyCodeBtn) {
                copyCodeBtn.addEventListener("click", function () {
                    const activeTab = document.querySelector(".gm-view-tab.active");
                    const tabName = activeTab ? activeTab.dataset.tab : "html";
                    let codeText = "";

                    if (tabName === "html") {
                        const htmlEl = document.getElementById("gmViewHtmlCode");
                        codeText = htmlEl ? htmlEl.textContent : "";
                    } else if (tabName === "css") {
                        const cssEl = document.getElementById("gmViewCssCode");
                        codeText = cssEl ? cssEl.textContent : "";
                    }

                    if (!codeText) return;

                    navigator.clipboard.writeText(codeText).then(() => {
                        copyCodeBtn.classList.add("copied");
                        copyCodeBtn.innerHTML = `<i class="fa-solid fa-check"></i> <span>Tersalin!</span>`;
                        setTimeout(() => {
                            copyCodeBtn.classList.remove("copied");
                            copyCodeBtn.innerHTML = `<i class="fa-regular fa-copy"></i> <span>Salin Kode</span>`;
                        }, 2000);
                        self.showToast("Kode berhasil disalin ke clipboard!", "success");
                    }).catch(() => {
                        self.showToast("Gagal menyalin kode", "error");
                    });
                });
            }

            // 13. Tombol Sisipkan ke Canvas
            const insertBtn = document.getElementById("gmViewInsertBtn");
            if (insertBtn) {
                insertBtn.addEventListener("click", function () {
                    if (!self.activeViewComponent || !self.activeViewComponent.html_content) {
                        self.showToast("Tidak ada komponen yang dipilih", "warning");
                        return;
                    }
                    self.insertComponentToCanvas(self.activeViewComponent.html_content);
                });
            }
        },

        /**
         * Buka modal Save as Component dan persiapkan data objek aktif
         */
        openSaveModal: function () {
            // Ambil objek yang sedang aktif di kanvas
            let active = document.querySelector(".gm-object.gm-active") || document.querySelector(".gm-active");
            
            // Fallback: cek GMObjectHandler jika ada
            if (!active && typeof GMObjectHandler !== "undefined" && GMObjectHandler.getActive) {
                active = GMObjectHandler.getActive();
            }

            // Fallback: periksa objek di bawah kursor jika aktif belum tersimpan
            if (!active) {
                const hoveredObj = document.querySelector("#gm-frame-preview .gm-object:hover");
                if (hoveredObj) {
                    active = hoveredObj;
                    active.classList.add("gm-active");
                }
            }

            if (!active) {
                this.showToast("Pilih objek di kanvas terlebih dahulu sebelum menyimpan sebagai komponen.", "warning");
                return;
            }

            activeTarget = active;

            // Clone dan bersihkan atribut editor
            const clone = active.cloneNode(true);

            // Bersihkan kelas seleksi editor
            clone.classList.remove("gm-active");
            clone.querySelectorAll(".gm-active").forEach(el => el.classList.remove("gm-active"));

            // Bersihkan drop hover & preview
            clone.classList.remove("gm-drop-hover", "gm-drag-preview", "gm-live-preview");
            clone.querySelectorAll(".gm-drop-hover, .gm-drag-preview, [data-preview], .gm-live-preview").forEach(el => el.remove());

            // Bersihkan status contenteditable
            if (clone.getAttribute("contenteditable") === "true") {
                clone.setAttribute("contenteditable", "false");
            }
            clone.querySelectorAll('[contenteditable="true"]').forEach(el => el.setAttribute("contenteditable", "false"));
            clone.removeAttribute("data-gm-editing");
            clone.querySelectorAll("[data-gm-editing]").forEach(el => el.removeAttribute("data-gm-editing"));

            // Pastikan setiap elemen memiliki class gm-object agar interaktif saat di-drop kembali
            if (!clone.classList.contains("gm-object")) {
                clone.classList.add("gm-object");
            }
            clone.querySelectorAll("*").forEach(child => {
                if (!child.classList.contains("gm-object")) {
                    child.classList.add("gm-object");
                }
            });

            activeCleanHtml = clone.outerHTML;

            // Update Metadata di Modal
            const tagBadge = document.getElementById("gmCompTagBadge");
            const childBadge = document.getElementById("gmCompChildBadge");
            const nameInput = document.getElementById("gmCompNameInput");
            const stageInner = document.getElementById("gmCompStageInner");

            if (tagBadge) tagBadge.textContent = active.tagName.toUpperCase();
            if (childBadge) {
                const childCount = active.children.length;
                childBadge.textContent = `${childCount} child element${childCount === 1 ? "" : "s"}`;
            }

            // Tentukan saran nama komponen secara otomatis
            let defaultName = "";
            if (active.id && !active.id.startsWith("gm-")) {
                defaultName = active.id.replace(/[-_]/g, " ").replace(/\b\w/g, l => l.toUpperCase());
            } else {
                const tagName = active.tagName.toLowerCase();
                const formattedTag = tagName.charAt(0).toUpperCase() + tagName.slice(1);
                defaultName = `My ${formattedTag} Component`;
            }
            if (nameInput) {
                nameInput.value = defaultName;
            }

            // Set kategori default berdasarkan tag
            this.syncDefaultCategory(active.tagName.toLowerCase());

            // Render miniature preview di stage modal
            if (stageInner) {
                try {
                    stageInner.innerHTML = activeCleanHtml;
                    // Auto-scale miniature agar pas di stage
                    setTimeout(() => {
                        try {
                            const stageW = stageInner.parentElement.offsetWidth || 400;
                            const stageH = stageInner.parentElement.offsetHeight || 120;
                            const child = stageInner.firstElementChild;
                            if (child) {
                                const childW = child.offsetWidth || 300;
                                const childH = child.offsetHeight || 100;
                                let scale = Math.min((stageW - 30) / Math.max(childW, 200), (stageH - 20) / Math.max(childH, 60));
                                if (scale > 1) scale = 1;
                                if (scale < 0.2) scale = 0.2;
                                stageInner.style.transform = `scale(${scale.toFixed(2)})`;
                            }
                        } catch (err) {}
                    }, 50);
                } catch (e) {}
            }

            // Tampilkan Modal dengan animasi
            const modalOverlay = document.getElementById("gm-save-comp-modal");
            if (modalOverlay) {
                modalOverlay.style.display = "flex";
                modalOverlay.classList.add("active");
                if (nameInput) {
                    setTimeout(() => {
                        nameInput.focus();
                        nameInput.select();
                    }, 50);
                }
            }
        },

        syncDefaultCategory: function (tagName) {
            const pillsContainer = document.getElementById("gmCompCategoryPills");
            if (!pillsContainer) return;

            let targetCat = "Custom";
            if (tagName === "header" || tagName === "nav") targetCat = "Header";
            else if (tagName === "footer") targetCat = "Footer";
            else if (tagName === "section" || tagName === "main") targetCat = "Section";
            else if (tagName === "button" || tagName === "a") targetCat = "Button";
            else if (activeTarget && (activeTarget.classList.contains("card") || activeTarget.classList.contains("box"))) targetCat = "Card";

            selectedCategory = targetCat;
            pillsContainer.querySelectorAll(".gm-comp-pill").forEach(p => {
                if (p.dataset.category === targetCat) {
                    p.classList.add("active");
                } else {
                    p.classList.remove("active");
                }
            });
        },

        closeSaveModal: function () {
            const modalOverlay = document.getElementById("gm-save-comp-modal");
            if (!modalOverlay) return;

            modalOverlay.classList.remove("active");
            setTimeout(() => {
                modalOverlay.style.display = "none";
                activeCleanHtml = "";
                activeTarget = null;
            }, 250);
        },

        /**
         * Kirim payload ke API save_component.php
         */
        saveComponent: function () {
            const self = this;
            const nameInput = document.getElementById("gmCompNameInput");
            const compName = nameInput ? nameInput.value.trim() : "";

            if (!compName) {
                if (nameInput) {
                    nameInput.focus();
                    nameInput.style.borderColor = "#e53e3e";
                    setTimeout(() => nameInput.style.borderColor = "", 1500);
                }
                self.showToast("Silakan masukkan nama komponen.", "warning");
                return;
            }

            if (!activeCleanHtml) {
                self.showToast("Konten komponen kosong.", "error");
                return;
            }

            // Tampilkan state loading
            const confirmBtn = document.getElementById("gmConfirmSaveCompBtn");
            const btnText = document.getElementById("gmSaveCompBtnText");
            if (confirmBtn) confirmBtn.disabled = true;
            if (btnText) btnText.textContent = "Menyimpan...";

            const payload = {
                component_name: compName,
                html_content: activeCleanHtml,
                category: selectedCategory,
                user_id: window.GM_USER_ID || null
            };

            fetch("api/save_component.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "OK") {
                    self.showToast("Komponen berhasil disimpan ke list Komponen!", "success");
                    self.closeSaveModal();
                    self.loadComponents();

                    // Otomatis arahkan left sidebar ke tab Komponen (tab index 3)
                    self.activateKomponenTab();
                } else {
                    self.showToast("Gagal menyimpan: " + (data.msg || "Terjadi kesalahan"), "error");
                }
            })
            .catch(err => {
                console.error("Error saving component:", err);
                self.showToast("Gagal menghubungi server", "error");
            })
            .finally(() => {
                if (confirmBtn) confirmBtn.disabled = false;
                if (btnText) btnText.textContent = "Simpan Komponen";
            });
        },

        /**
         * Ambil list komponen dari backend untuk user saat ini
         */
        loadComponents: function () {
            const self = this;
            const url = "api/get_components.php" + (window.GM_USER_ID ? `?user_id=${window.GM_USER_ID}` : "");

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.status === "OK") {
                        self.savedComponents = data.components || [];
                        self.renderComponents(self.savedComponents);
                    }
                })
                .catch(err => {
                    console.error("Error loading components:", err);
                });
        },

        /**
         * Deteksi apakah suatu elemen adalah elemen kecil / kompak (seperti button, input, badge, icon)
         */
        isCompactElement: function (htmlContent, category) {
            if (category === "Button" || category === "Input" || category === "Badge" || category === "Icon") {
                return true;
            }
            try {
                const temp = document.createElement("div");
                temp.innerHTML = (htmlContent || "").trim();
                const root = temp.firstElementChild;
                if (!root) return false;
                const tag = root.tagName.toUpperCase();
                const compactTags = ["BUTTON", "INPUT", "A", "SPAN", "I", "LABEL", "SELECT", "TEXTAREA", "P", "H1", "H2", "H3", "H4", "H5", "H6", "IMG", "SVG"];
                if (compactTags.includes(tag)) return true;

                const styleWidth = root.style.width || "";
                if (styleWidth && parseInt(styleWidth) <= 260 && !styleWidth.includes("%")) {
                    return true;
                }
                if (root.children.length <= 1 && styleWidth !== "100%") {
                    return true;
                }
            } catch (e) {}
            return false;
        },

        /**
         * Render komponen ke list di part/aside-left/bottom/template.html
         */
        /**
         * Render daftar Include Components proyek (PHP-like Includes)
         */
        renderProjectIncludes: function () {
            const container = document.getElementById("gm-project-includes-container");
            const listEl = document.getElementById("gm-project-includes-list");
            const countBadge = document.getElementById("gm-project-includes-count");
            if (!container || !listEl) return;

            const includes = (window.gmIncludeEngine && typeof window.gmIncludeEngine.getIncludes === "function")
                ? window.gmIncludeEngine.getIncludes()
                : [];

            if (countBadge) countBadge.textContent = includes.length;

            if (includes.length === 0) {
                container.style.display = "none";
                listEl.innerHTML = "";
                return;
            }

            container.style.display = "block";
            listEl.innerHTML = "";

            includes.forEach(inc => {
                const card = document.createElement("div");
                card.className = "gm-include-sidebar-card";
                card.style.cssText = "background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 8px 10px; display: flex; flex-direction: column; gap: 6px;";
                card.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 12px; font-weight: 700; color: #0369a1; display: flex; align-items: center; gap: 5px;">
                            <i class="fa-solid fa-link"></i> ${inc.name}
                        </span>
                        <span style="font-size: 10px; color: #0284c7; background: #e0f2fe; padding: 1px 5px; border-radius: 4px;">
                            ${(inc.frames || []).length} frames
                        </span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button type="button" class="gm-btn-insert-include" data-id="${inc.id}" style="flex: 1; padding: 4px 8px; font-size: 11px; font-weight: 600; background: #0284c7; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                            Sisipkan ke Lokasi Aktif
                        </button>
                    </div>
                `;

                card.querySelector(".gm-btn-insert-include").addEventListener("click", function (e) {
                    e.preventDefault();
                    const activeObj = document.querySelector(".gm-object.gm-active") || document.querySelector(".gm-object-core.actived");
                    if (window.gmIncludeEngine) {
                        window.gmIncludeEngine.insertIncludeAtTarget(inc.id, activeObj);
                    }
                });

                listEl.appendChild(card);
            });
        },

        renderComponents: function (components) {
            const self = this;
            self.savedComponents = components;
            const container = document.getElementById("gm-custom-components-list");
            const countBadge = document.getElementById("gm-custom-comp-count");

            if (countBadge) {
                countBadge.textContent = components.length;
            }
            self.renderProjectIncludes();

            if (!container) return;

            if (components.length === 0) {
                container.innerHTML = `
                    <div class="gm-tmpl-empty-state" style="text-align: center; padding: 36px 16px; color: #a0aec0;">
                        <i class="fa-solid fa-cube" style="font-size: 32px; margin-bottom: 12px; color: #cbd5e0; display: block;"></i>
                        <p style="font-size: 13px; font-weight: 700; color: #4a5568; margin: 0 0 6px 0;">Belum Ada Komponen</p>
                        <p style="font-size: 11px; line-height: 1.5; color: #718096; margin: 0;">Klik kanan pada objek di canvas, lalu pilih <strong>"Save as Component"</strong> untuk menyimpan komponen buatanmu di sini.</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = "";

            components.forEach(comp => {
                const item = document.createElement("div");
                item.className = "gm-template-dropable gm-custom-component-item";
                item.setAttribute("draggable", "true");
                item.dataset.type = "template";
                item.dataset.componentId = comp.component_id;
                item.dataset.htmlContent = encodeURIComponent(comp.html_content);

                const escapedName = this.escapeHtml(comp.component_name);
                const isCompact = this.isCompactElement(comp.html_content, comp.category);

                let thumbHtml = "";
                if (isCompact) {
                    thumbHtml = `
                        <div class="gm-mini-thumb gm-custom-thumb gm-thumb-compact">
                            <div class="gm-thumb-compact-inner">
                                ${comp.html_content}
                            </div>
                            <button type="button" class="gm-thumb-quick-view" data-id="${comp.component_id}" title="Lihat Detail (Pratinjau & Kode)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    `;
                } else {
                    thumbHtml = `
                        <div class="gm-mini-thumb gm-custom-thumb gm-thumb-wide">
                            <div class="gm-thumb-scaler">
                                ${comp.html_content}
                            </div>
                            <button type="button" class="gm-thumb-quick-view" data-id="${comp.component_id}" title="Lihat Detail (Pratinjau & Kode)">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    `;
                }

                item.innerHTML = `
                    ${thumbHtml}
                    <div class="gm-template-label">
                        <span class="gm-comp-title-text" title="${escapedName}">${escapedName}</span>
                        <div class="gm-comp-card-actions">
                            <button type="button" class="gm-comp-card-view-btn" data-id="${comp.component_id}" title="Lihat Detail, HTML & CSS">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button type="button" class="gm-comp-card-del-btn" data-id="${comp.component_id}" title="Hapus Komponen">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                    <template>${comp.html_content}</template>
                `;

                container.appendChild(item);
            });
        },

        /**
         * Buka modal View & Inspect Component Detail
         */
        openViewModal: function (componentId) {
            const comp = (this.savedComponents || []).find(c => String(c.component_id) === String(componentId));
            if (!comp) {
                this.showToast("Data komponen tidak ditemukan", "warning");
                return;
            }

            this.activeViewComponent = comp;

            const modal = document.getElementById("gm-view-comp-modal");
            const titleEl = document.getElementById("gmViewCompTitle");
            const catEl = document.getElementById("gmViewCompCategory");
            const metaEl = document.getElementById("gmViewCompMeta");
            const renderContainer = document.getElementById("gmViewRenderContainer");
            const htmlCodeEl = document.getElementById("gmViewHtmlCode");
            const cssCodeEl = document.getElementById("gmViewCssCode");

            if (titleEl) titleEl.textContent = comp.component_name || "Detail Komponen";
            if (catEl) catEl.textContent = comp.category || "Custom";

            // Hitung tag dan child
            let tagName = "div";
            let childCount = 0;
            try {
                const temp = document.createElement("div");
                temp.innerHTML = (comp.html_content || "").trim();
                if (temp.firstElementChild) {
                    tagName = temp.firstElementChild.tagName.toLowerCase();
                    childCount = temp.firstElementChild.querySelectorAll("*").length;
                }
            } catch (e) {}
            const dateStr = comp.created_at ? ` • Disimpan pada ${comp.created_at}` : "";
            if (metaEl) {
                metaEl.textContent = `<${tagName}> elemen • ${childCount} child element${childCount === 1 ? "" : "s"}${dateStr}`;
            }

            // Render live preview
            if (renderContainer) {
                renderContainer.innerHTML = comp.html_content;
                renderContainer.querySelectorAll(".gm-active").forEach(el => el.classList.remove("gm-active"));
            }

            // Tampilkan HTML terformat
            const formattedHtml = this.formatHtml(comp.html_content);
            if (htmlCodeEl) htmlCodeEl.textContent = formattedHtml;

            // Ekstrak & tampilkan CSS
            const extractedCss = this.extractCss(comp.html_content, comp.component_name);
            if (cssCodeEl) cssCodeEl.textContent = extractedCss;

            // Reset tab & viewport
            this.switchViewTab("preview");

            const vp = document.getElementById("gmViewStageViewport");
            if (vp) vp.style.width = "100%";
            document.querySelectorAll("#gmViewPreviewControls .gm-view-ctrl-btn[data-viewport]").forEach(b => {
                b.classList.toggle("active", b.dataset.viewport === "100%");
            });

            const stage = document.getElementById("gmViewStageWrapper");
            if (stage) {
                stage.classList.remove("bg-light", "bg-dark");
                stage.classList.add("bg-grid");
            }
            document.querySelectorAll("#gmViewPreviewControls .gm-view-ctrl-btn[data-bg]").forEach(b => {
                b.classList.toggle("active", b.dataset.bg === "grid");
            });

            // Tampilkan modal
            if (modal) {
                modal.style.display = "flex";
                modal.offsetHeight;
                modal.classList.add("active");
            }
        },

        closeViewModal: function () {
            const modal = document.getElementById("gm-view-comp-modal");
            if (!modal) return;
            modal.classList.remove("active");
            setTimeout(() => {
                modal.style.display = "none";
                const renderContainer = document.getElementById("gmViewRenderContainer");
                if (renderContainer) renderContainer.innerHTML = "";
                this.activeViewComponent = null;
            }, 250);
        },

        switchViewTab: function (tabName) {
            const tabs = document.querySelectorAll(".gm-view-tab");
            const panels = document.querySelectorAll(".gm-view-panel");
            const previewControls = document.getElementById("gmViewPreviewControls");
            const codeControls = document.getElementById("gmViewCodeControls");

            tabs.forEach(t => {
                if (t.dataset.tab === tabName) t.classList.add("active");
                else t.classList.remove("active");
            });

            panels.forEach(p => {
                if (p.id === `gm-view-panel-${tabName}`) p.classList.add("active");
                else p.classList.remove("active");
            });

            if (tabName === "preview") {
                if (previewControls) previewControls.style.display = "flex";
                if (codeControls) codeControls.style.display = "none";
            } else {
                if (previewControls) previewControls.style.display = "none";
                if (codeControls) codeControls.style.display = "flex";
            }
        },

        formatHtml: function (html) {
            if (!html) return "";
            let cleaned = html
                .replace(/\s*data-gm-[a-zA-Z0-9_-]+(="[^"]*")?/g, "")
                .replace(/\s*contenteditable="false"/g, "")
                .trim();

            let formatted = "";
            let pad = 0;
            const tokens = cleaned.replace(/>\s*</g, "><").match(/(<\/?[^>]+>|[^<]+)/g) || [];
            const voidTags = ["area", "base", "br", "col", "embed", "hr", "img", "input", "link", "meta", "param", "source", "track", "wbr"];

            tokens.forEach(token => {
                token = token.trim();
                if (!token) return;

                if (token.startsWith("</")) {
                    pad = Math.max(0, pad - 1);
                    formatted += "  ".repeat(pad) + token + "\n";
                } else if (token.startsWith("<") && !token.startsWith("<!")) {
                    const tagMatch = token.match(/^<([a-zA-Z0-9_-]+)/);
                    const tagName = tagMatch ? tagMatch[1].toLowerCase() : "";
                    const isVoid = voidTags.includes(tagName) || token.endsWith("/>");

                    formatted += "  ".repeat(pad) + token + "\n";
                    if (!isVoid) {
                        pad++;
                    }
                } else {
                    formatted += "  ".repeat(pad) + token + "\n";
                }
            });

            return formatted.trim();
        },

        extractCss: function (html, componentName) {
            if (!html) return "/* Tidak ada styling */";
            try {
                const temp = document.createElement("div");
                temp.innerHTML = html.trim();
                const root = temp.firstElementChild;
                if (!root) return "/* Elemen kosong */";

                const safeName = (componentName || "component")
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, "-")
                    .replace(/^-|-$/g, "");
                const rootSelector = `.${safeName}`;

                let cssOutput = `/* ========================================================\n   Komponen: ${componentName || "Custom"}\n   Target Class: ${rootSelector}\n   ======================================================== */\n\n`;

                function formatDeclarations(styleAttr) {
                    if (!styleAttr) return "";
                    return styleAttr
                        .split(";")
                        .map(rule => rule.trim())
                        .filter(rule => rule.length > 0)
                        .map(rule => {
                            const parts = rule.split(":");
                            if (parts.length < 2) return null;
                            const prop = parts[0].trim();
                            const val = parts.slice(1).join(":").trim();
                            return `  ${prop}: ${val};`;
                        })
                        .filter(Boolean)
                        .join("\n");
                }

                // Root element styles
                const rootStyle = root.getAttribute("style");
                if (rootStyle) {
                    const decs = formatDeclarations(rootStyle);
                    if (decs) {
                        cssOutput += `/* Root Element */\n${rootSelector} {\n${decs}\n}\n\n`;
                    }
                }

                // Child elements
                let childIndex = 1;
                const allChildren = root.querySelectorAll("*");
                allChildren.forEach(child => {
                    const childStyle = child.getAttribute("style");
                    if (childStyle) {
                        const decs = formatDeclarations(childStyle);
                        if (decs) {
                            let childSelector = "";
                            const childId = child.id;
                            const childClasses = Array.from(child.classList).filter(c => !c.startsWith("gm-") && c !== safeName);

                            if (childId && !childId.startsWith("gm-")) {
                                childSelector = `${rootSelector} #${childId}`;
                            } else if (childClasses.length > 0) {
                                childSelector = `${rootSelector} .${childClasses.join(".")}`;
                            } else {
                                const tag = child.tagName.toLowerCase();
                                childSelector = `${rootSelector} ${tag}`;
                            }

                            cssOutput += `${childSelector} {\n${decs}\n}\n\n`;
                            childIndex++;
                        }
                    }
                });

                // Cek apakah ada inline style tag bawaan
                const styleTags = temp.querySelectorAll("style");
                styleTags.forEach(st => {
                    cssOutput += `/* Embedded Stylesheet */\n${st.textContent.trim()}\n\n`;
                });

                return cssOutput.trim();
            } catch (err) {
                console.error("Error extracting CSS:", err);
                return "/* Gagal mengekstrak CSS */";
            }
        },

        insertComponentToCanvas: function (htmlContent) {
            const activeCore = document.querySelector(".gm-object-core.actived") || document.querySelector(".gm-object-core");
            if (!activeCore) {
                this.showToast("Tidak ada frame kanvas aktif untuk menyisipkan komponen.", "warning");
                return;
            }

            try {
                const temp = document.createElement("div");
                temp.innerHTML = (htmlContent || "").trim();
                const children = Array.from(temp.children);
                if (!children.length) return;

                children.forEach(el => {
                    activeCore.appendChild(el);
                    if (typeof window.initLoadedObjects === "function") {
                        window.initLoadedObjects(el);
                    }
                });

                this.showToast("Komponen berhasil disisipkan ke kanvas!", "success");
                this.closeViewModal();
            } catch (err) {
                console.error("Error inserting component to canvas:", err);
                this.showToast("Gagal menyisipkan komponen ke kanvas", "error");
            }
        },


        confirmDelete: function (componentId) {
            const self = this;
            if (!componentId) return;

            if (confirm("Apakah Anda yakin ingin menghapus komponen ini dari daftar?")) {
                fetch("api/delete_component.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                        component_id: componentId,
                        user_id: window.GM_USER_ID || null
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "OK") {
                        self.showToast("Komponen berhasil dihapus", "info");
                        self.loadComponents();
                    } else {
                        self.showToast("Gagal menghapus: " + (data.msg || "Terjadi kesalahan"), "error");
                    }
                })
                .catch(err => {
                    console.error("Error deleting component:", err);
                    self.showToast("Gagal menghapus komponen", "error");
                });
            }
        },

        /**
         * Aktifkan panel Komponen di sidebar kiri
         */
        activateKomponenTab: function () {
            // Tab Komponen adalah menu ke-4 (index 3)
            const leftMenus = document.querySelectorAll(".gm-left-menu p");
            if (leftMenus && leftMenus.length > 3) {
                leftMenus[3].click();
            }

            // Aktifkan tab "Custom" di switcher template
            const customTabBtn = document.querySelector('.gm-tmpl-tab-btn[data-panel="custom"]');
            if (customTabBtn && typeof window.switchTmplTab === "function") {
                window.switchTmplTab(customTabBtn, "custom");
            }
        },

        escapeHtml: function (str) {
            if (!str) return "";
            return str
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        },

        showToast: function (msg, type = "info") {
            let toast = document.getElementById("gm-comp-toast");
            if (!toast) {
                toast = document.createElement("div");
                toast.id = "gm-comp-toast";
                toast.style.cssText = `
                    position: fixed;
                    bottom: 24px;
                    right: 24px;
                    padding: 12px 20px;
                    border-radius: 10px;
                    font-size: 13px;
                    font-weight: 600;
                    color: #fff;
                    z-index: 1000003;
                    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    opacity: 0;
                    transform: translateY(12px);
                    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                    pointer-events: none;
                    font-family: 'Inter', sans-serif;
                `;
                document.body.appendChild(toast);
            }

            let bg = "#2b6cb0";
            let icon = "fa-circle-info";
            if (type === "success") {
                bg = "#2f855a";
                icon = "fa-circle-check";
            } else if (type === "warning") {
                bg = "#c05621";
                icon = "fa-triangle-exclamation";
            } else if (type === "error") {
                bg = "#c53030";
                icon = "fa-circle-xmark";
            }

            toast.style.background = bg;
            toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${msg}</span>`;
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0)";

            clearTimeout(this._toastTimeout);
            this._toastTimeout = setTimeout(() => {
                toast.style.opacity = "0";
                toast.style.transform = "translateY(12px)";
            }, 3000);
        }
    };

    // Ekspos ke global
    window.GMComponentManager = GMComponentManager;

    // Inisialisasi saat DOM siap
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => GMComponentManager.init());
    } else {
        GMComponentManager.init();
    }
})();
