// ============================================================
// GREYMIST CONTEXT MENU & INFO MODAL TRIGGER MODULE
// ============================================================

(function () {
    let menu = null;
    let infoModal = null;
    let framePreview = null;

    function getElements() {
        if (!menu) menu = document.querySelector(".gm-object-menu");
        if (!infoModal) infoModal = document.querySelector(".gm-object-info-modal");
        if (!framePreview) framePreview = document.getElementById('gm-frame-preview');
    }

    function closeMenu(closeInfoModal = false) {
        getElements();
        if (menu) {
            menu.style.display = "none";
            menu.querySelectorAll(".gm-menu-item.open").forEach(i => i.classList.remove("open"));
            document.querySelectorAll(".gm-submenu").forEach(sm => sm.style.display = "none");
        }
        if (closeInfoModal && infoModal) {
            infoModal.style.display = "none";
        }
    }
    window.GMCloseContextMenu = closeMenu;

    // Buka menu konteks untuk targetObj pada koordinat viewport (clientX, clientY)
    window.GMOpenContextMenu = function (targetObj, clientX, clientY) {
        getElements();
        if (!menu) return;

        const core = (targetObj && targetObj.closest(".gm-object-core")) || 
                     document.querySelector(".gm-object-core.actived") || 
                     document.querySelector(".gm-object-core") || 
                     document.getElementById("gm-frame-preview")?.querySelector(".gm-object-core");

        // Otomatis aktifkan objek yang di-klik kanan jika itu elemen .gm-object
        if (targetObj) {
            // Pastikan frame/core aktif
            if (core && !core.classList.contains("actived")) {
                document.querySelectorAll(".gm-object-core").forEach(c => c.classList.remove("actived"));
                core.classList.add("actived");
                if (core.dataset.frame) {
                    document.querySelectorAll(".gm-frame-thumbnail").forEach(f => {
                        f.classList.toggle("actived", f.dataset.frame === core.dataset.frame);
                    });
                }
            }

            if (core) {
                core.querySelectorAll(".gm-object.gm-active").forEach(el => {
                    if (el !== targetObj) el.classList.remove("gm-active");
                });
            } else {
                document.querySelectorAll(".gm-object.gm-active").forEach(el => {
                    if (el !== targetObj) el.classList.remove("gm-active");
                });
            }
            targetObj.classList.add("gm-active");
            window.lastSelectedGmObject = targetObj;

            // Pasang transform handler jika tersedia
            const handler = window.GMObjectHandler || (typeof GMObjectHandler !== "undefined" ? GMObjectHandler : null);
            if (handler && typeof handler.attachTo === "function") {
                handler.attachTo(targetObj);
            }

            // Pastikan objek tampak pada canvas
            try {
                targetObj.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            } catch (err) {}

            // Sinkronkan ke DOM tree
            if (typeof syncActiveToTree === "function") {
                syncActiveToTree(targetObj);
            }

            const detail = { target: targetObj };
            window.dispatchEvent(new CustomEvent('gm-selection-change', { detail }));
            document.dispatchEvent(new CustomEvent('gm-selection-change', { detail }));
            window.dispatchEvent(new CustomEvent('gm-active-tick', { detail }));
            document.dispatchEvent(new CustomEvent('gm-active-tick', { detail }));
        }

        const activeObjects = core ? core.querySelectorAll(".gm-object.gm-active") : document.querySelectorAll(".gm-object.gm-active");

        // ===== Tampilkan menu =====
        menu.style.display = "block";
        menu.style.zIndex = "999999";
        menu.style.position = "fixed";

        // Posisi menu
        let posX = clientX;
        let posY = clientY;
        menu.style.left = posX + "px";
        menu.style.top = posY + "px";

        const menuRect = menu.getBoundingClientRect();
        if (posX + menuRect.width > window.innerWidth) {
            posX = window.innerWidth - menuRect.width - 12;
        }
        if (posY + menuRect.height > window.innerHeight) {
            posY = window.innerHeight - menuRect.height - 12;
        }
        menu.style.left = Math.max(10, posX) + "px";
        menu.style.top = Math.max(10, posY) + "px";

        // Menu aktif / tidak aktif
        if (activeObjects.length > 0) {
            menu.querySelectorAll(".gm-active-menu").forEach(i => i.style.display = "block");
            menu.querySelectorAll(".gm-no-active-menu").forEach(i => i.style.display = "none");
        } else {
            menu.querySelectorAll(".gm-active-menu").forEach(i => i.style.display = "none");
            menu.querySelectorAll(".gm-no-active-menu").forEach(i => i.style.display = "block");
        }

        // ===== INTEGRASI INCLUDE COMPONENT ENGINE (PHP-LIKE INCLUDE) =====
        const makeIncludeItem = document.getElementById("gmMakeIncludeMenuItem");
        const insertIncludeItem = document.getElementById("gmInsertIncludeMenuItem");
        const syncIncludeItem = document.getElementById("gmSyncIncludeMenuItem");
        const unlinkIncludeItem = document.getElementById("gmUnlinkIncludeMenuItem");
        const includeSubmenuList = document.getElementById("gmIncludeSubmenuList");
        const existingIncludeEl = targetObj ? targetObj.closest("[data-gm-include]") : null;

        if (makeIncludeItem) {
            makeIncludeItem.style.display = targetObj ? "block" : "none";
            if (existingIncludeEl) {
                const incName = existingIncludeEl.dataset.gmIncludeName || "Komponen";
                makeIncludeItem.innerHTML = `<i class="fa-solid fa-link" style="color: #0284c7; margin-right: 6px; font-size: 13px;"></i> Pengaturan Include: "${incName}"...`;
            } else {
                makeIncludeItem.innerHTML = `<i class="fa-solid fa-link" style="color: #0284c7; margin-right: 6px; font-size: 13px;"></i> Jadikan Include Component (PHP Include)...`;
            }
            makeIncludeItem.onclick = function(e) {
                e.preventDefault();
                closeMenu();
                const elToInclude = existingIncludeEl || targetObj;
                if (window.gmIncludeEngine) window.gmIncludeEngine.openModal(elToInclude);
                else if (window.gmSyncNavbarEngine) window.gmSyncNavbarEngine.openModal(elToInclude);
            };
        }

        if (syncIncludeItem) {
            syncIncludeItem.style.display = existingIncludeEl ? "block" : "none";
            if (existingIncludeEl) {
                syncIncludeItem.onclick = function(e) {
                    e.preventDefault();
                    closeMenu();
                    if (window.gmIncludeEngine) window.gmIncludeEngine.propagate(existingIncludeEl);
                    else if (window.gmSyncNavbarEngine) window.gmSyncNavbarEngine.propagate(existingIncludeEl);
                };
            }
        }

        if (unlinkIncludeItem) {
            unlinkIncludeItem.style.display = existingIncludeEl ? "block" : "none";
            if (existingIncludeEl) {
                unlinkIncludeItem.onclick = function(e) {
                    e.preventDefault();
                    closeMenu();
                    if (confirm("Lepas kaitan Include pada elemen ini di halaman ini? Perubahan selanjutnya tidak akan disinkronkan lagi ke halaman lain.")) {
                        if (window.gmIncludeEngine) window.gmIncludeEngine.unlink(existingIncludeEl);
                    }
                };
            }
        }

        if (insertIncludeItem && includeSubmenuList) {
            insertIncludeItem.style.display = targetObj ? "block" : "none";
            const includes = (window.gmIncludeEngine && typeof window.gmIncludeEngine.getIncludes === "function") 
                ? window.gmIncludeEngine.getIncludes() 
                : [];
            
            includeSubmenuList.innerHTML = "";
            if (includes.length === 0) {
                includeSubmenuList.innerHTML = `<li class="gm-menu-item" style="color: #94a3b8; font-size: 11px; padding: 6px 10px;">(Belum ada include di proyek)</li>`;
            } else {
                includes.forEach(inc => {
                    const li = document.createElement("li");
                    li.className = "gm-menu-item";
                    li.style.cssText = "display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; padding: 7px 12px;";
                    li.innerHTML = `
                        <span style="font-weight: 600; color: #1e293b;">${inc.name}</span>
                        <span style="font-size: 10px; background: #e0f2fe; color: #0284c7; padding: 1px 5px; border-radius: 4px;">Include</span>
                    `;
                    li.addEventListener("click", function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        closeMenu();
                        if (window.gmIncludeEngine) {
                            window.gmIncludeEngine.insertIncludeAtTarget(inc.id, targetObj);
                        }
                    });
                    includeSubmenuList.appendChild(li);
                });
            }
        }

        // Tampilkan opsi Sinkronisasi Navbar jika target berupa / di dalam Navbar (Backward Compatibility)
        const syncNavMenuItem = document.getElementById("gmSyncNavbarMenuItem");
        if (syncNavMenuItem) {
            const hasNav = targetObj && (targetObj.closest(".gm-navbar") || targetObj.closest("nav") || (targetObj.classList && targetObj.classList.contains("gm-navbar")) || (targetObj.classList && targetObj.classList.contains("gm-synced-navbar")));
            syncNavMenuItem.style.display = hasNav ? "block" : "none";
            if (hasNav) {
                syncNavMenuItem.onclick = function(e) {
                    e.preventDefault();
                    closeMenu();
                    const nav = targetObj.closest(".gm-navbar") || targetObj.closest("nav") || targetObj;
                    if (window.gmIncludeEngine) window.gmIncludeEngine.openModal(nav);
                    else if (window.gmSyncNavbarEngine) window.gmSyncNavbarEngine.openModal(nav);
                };
            }
        }

        // ===== Info Modal (Tag name & Class info) =====
        if (infoModal && activeObjects.length === 1) {
            const obj = activeObjects[0];
            infoModal.style.display = "block";
            infoModal.style.zIndex = "999999";
            infoModal.style.position = "fixed";

            const tagEl = infoModal.querySelector(".gm-object-tag");
            if (tagEl) tagEl.textContent = obj.tagName.toLowerCase();
            const propVal = infoModal.querySelector(".gm-object-prop-value");
            if (propVal) propVal.value = obj.className;

            const updatedMenuRect = menu.getBoundingClientRect();
            const infoRect = infoModal.getBoundingClientRect();
            let infoX = updatedMenuRect.left;
            let infoY = updatedMenuRect.top - infoRect.height - 4;
            if (infoY < 0) infoY = updatedMenuRect.bottom + 4;
            if (infoX + infoRect.width > window.innerWidth) infoX = window.innerWidth - infoRect.width - 10;

            infoModal.style.left = Math.max(10, infoX) + "px";
            infoModal.style.top = Math.max(10, infoY) + "px";
        } else if (infoModal) {
            infoModal.style.display = "none";
        }

        // Tutup semua submenu saat muncul menu baru
        document.querySelectorAll(".gm-submenu").forEach(sm => sm.style.display = "none");
        document.querySelectorAll(".gm-menu-item.open").forEach(i => i.classList.remove("open"));
    };

    function initListeners() {
        getElements();

        // 1. Nonaktifkan klik kanan default browser di framePreview
        if (framePreview) {
            framePreview.addEventListener("contextmenu", e => {
                e.preventDefault();
                const clickedObj = e.target.closest(".gm-object");
                window.GMOpenContextMenu(clickedObj, e.clientX, e.clientY);
            });

            framePreview.addEventListener("click", e => {
                const obj = e.target.closest(".gm-object.gm-active");
                if (!obj) return;
                closeMenu(true);
            });
        }

        // 2. Klik kanan di DOM Tree node -> memicu menu yang sama persis
        document.addEventListener("contextmenu", e => {
            const nodeDiv = e.target.closest(".tree-node");
            if (!nodeDiv) return;

            e.preventDefault();
            e.stopPropagation();

            const id = nodeDiv.dataset.ref;
            let targetObj = nodeDiv._gmElement;
            if (!targetObj && id) {
                const core = document.querySelector(".gm-object-core.actived") || 
                             document.querySelector(".gm-object-core") || 
                             document.getElementById("gm-frame-preview");
                targetObj = core?.querySelector(`.gm-object[data-id='${id}']`) || 
                            document.querySelector(`.gm-object[data-id='${id}']`);
            }

            if (targetObj && window.GMOpenContextMenu) {
                window.GMOpenContextMenu(targetObj, e.clientX, e.clientY);
            }
        });

        // 3. Klik di menu -> toggle submenu atau aksi
        if (menu) {
            menu.addEventListener("click", e => {
                const target = e.target.closest(".gm-menu-item");
                if (!target) return;

                const submenu = target.querySelector(".gm-submenu");
                if (submenu) {
                    document.querySelectorAll(".gm-submenu").forEach(sm => {
                        if (sm !== submenu) {
                            sm.style.display = "none";
                            const parentItem = sm.closest(".gm-menu-item");
                            if (parentItem) parentItem.classList.remove("open");
                        }
                    });
                    const isOpen = submenu.style.display === "block";
                    submenu.style.display = isOpen ? "none" : "block";
                    target.classList.toggle("open", !isOpen);

                    if (!isOpen) {
                        const subRect = submenu.getBoundingClientRect();
                        if (subRect.right > window.innerWidth) {
                            submenu.style.left = "-100%";
                        } else {
                            submenu.style.left = "100%";
                        }
                    }
                } else {
                    if (target.classList.contains("gm-save-component")) {
                        if (window.GMComponentManager && typeof window.GMComponentManager.openSaveModal === "function") {
                            window.GMComponentManager.openSaveModal();
                        }
                    }
                    closeMenu(true);
                }
                e.stopPropagation();
            });
        }

        if (infoModal) {
            infoModal.addEventListener("click", e => e.stopPropagation());
        }

        // 4. Klik di luar menu -> tutup menu
        document.addEventListener("click", e => {
            if (e.target.closest(".gm-object-menu") || e.target.closest(".gm-object-info-modal")) return;
            closeMenu(true);
        });

        // 5. Escape = tutup menu
        document.addEventListener("keydown", e => {
            if (e.key === "Escape") closeMenu(true);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initListeners);
    } else {
        initListeners();
    }
})();
