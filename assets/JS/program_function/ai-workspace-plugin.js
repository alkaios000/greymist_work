/**
 * Greymist Workspace — Minimalist Draggable Gemini AI Builder
 * Fitur:
 * 1. Posisi tombol toggle di bawah tombol Interaksi (top: 145px, left: 15px) sehingga TIDAK menutupi Breakpoint Toolbar.
 * 2. Tanpa tombol hapus di daftar Isi Frame (penghapusan hanya lewat instruksi Prompt agar aman dari salah klik).
 * 3. Menu Klik Kanan pada Objek Aktif (.gm-object.gm-active): Memiliki input prompt langsung di context menu untuk mengubah objek yang sedang aktif.
 * 4. Persistent AI Memory & Architecture Caching: Menyimpan & memuat ingatan AI (jumlah pages/frames, susunan komponen tiap frame, dan riwayat perubahan) di Database (gm_projects.ai_memory) + localStorage saat aplikasi dimuat.
 * 5. Real-Time Unstyled-to-Styled Construction tanpa pembatasan token output.
 */
(function () {
    "use strict";

    let cachedAiMemory = null;

    const PRESET_PROMPTS = [
        { label: "Hero", prompt: "Buat Hero Section modern dengan badge, judul besar, deskripsi singkat, dan 2 tombol CTA" },
        { label: "Navbar", prompt: "Buat Navbar navigasi horizontal dengan logo brand di kiri, menu link di tengah, dan tombol CTA di kanan" },
        { label: "Pricing", prompt: "Buat bagian kartu harga (Pricing Table) 3 kolom: Starter, Pro (Populer), dan Enterprise" },
        { label: "Kontak", prompt: "Buat kartu Formulir Kontak minimalis dengan input Nama, Email, Pesan, dan tombol Kirim" },
        { label: "📄 +Page Baru", prompt: "Buat frame halaman baru untuk halaman Login lengkap dengan Navbar dan Form Login" },
        { label: "📑 Multi-Page", prompt: "Buat 3 frame halaman website terpisah: Beranda, Layanan & Harga, dan Hubungi Kami" },
        { label: "🔍 Baca Frame", prompt: "Baca dan jelaskan komponen apa saja yang ada di dalam proyek dan frame ini sekarang" }
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
    }

    function updateMemoryBadgeUI(memory) {
        const badgeEl = document.getElementById("gm-ai-memory-badge");
        if (!badgeEl || !memory) return;

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
        renderFrameComponentsList();
    }

    /**
     * Membangun pohon elemen di dalam kanvas secara instan & tanpa throttling (kecepatan penuh).
     */
    function buildElementsLiveUnstyledToStyled(sourceRootElements, targetContainer, statusEl, onComplete, insertBeforeNode = null) {
        const createdRoots = [];

        sourceRootElements.forEach(srcRoot => {
            const clone = srcRoot.cloneNode(true);
            if (!["script", "style", "br", "hr"].includes(clone.tagName.toLowerCase())) {
                clone.classList.add("gm-object");
            }
            clone.classList.add("gm-ai-fade-in");

            if (insertBeforeNode && insertBeforeNode.parentNode === targetContainer) {
                targetContainer.insertBefore(clone, insertBeforeNode);
            } else {
                targetContainer.appendChild(clone);
            }

            if (typeof window.initLoadedObjects === "function") {
                window.initLoadedObjects(clone);
            }

            createdRoots.push(clone);
        });

        if (typeof onComplete === "function") {
            onComplete(createdRoots);
        }
    }

    /**
     * Mem-parsing atribut inline style="..." menjadi objek key-value { property: value }
     * agar perbandingan atribut CSS akurat dan hanya properti yang berubah saja yang di-update.
     */
    function parseCssStyleMap(styleStr) {
        const map = {};
        if (!styleStr || typeof styleStr !== "string") return map;
        const declarations = styleStr.split(/;(?![^(]*\))/);
        declarations.forEach(decl => {
            const colonIdx = decl.indexOf(":");
            if (colonIdx > 0) {
                const prop = decl.slice(0, colonIdx).trim().toLowerCase();
                const val = decl.slice(colonIdx + 1).trim().replace(/\s+/g, " ");
                if (prop && val !== "") {
                    map[prop] = val;
                }
            }
        });
        return map;
    }

    /**
     * Membandingkan elemen DOM yang sudah ada (liveEl) dengan elemen hasil edit AI (sourceEl).
     * Mengembalikan daftar perubahan spesifik (style, atribut, teks) tanpa menghancurkan/membuat ulang elemen dari 0.
     */
    function computeElementAttributePatch(liveEl, sourceEl) {
        const oldStyleMap = parseCssStyleMap(liveEl.getAttribute("style") || "");
        const newStyleMap = parseCssStyleMap(sourceEl.getAttribute("style") || "");

        const changedStyles = {};
        const removedStyles = [];
        const changedLabels = [];

        Object.keys(newStyleMap).forEach(prop => {
            const oldVal = oldStyleMap[prop];
            const newVal = newStyleMap[prop];
            if (oldVal === undefined || oldVal.toLowerCase() !== newVal.toLowerCase()) {
                changedStyles[prop] = newVal;
                changedLabels.push(prop);
            }
        });

        Object.keys(oldStyleMap).forEach(prop => {
            if (newStyleMap[prop] === undefined) {
                removedStyles.push(prop);
                changedLabels.push("-" + prop);
            }
        });

        // Bandingkan atribut non-style (misal href, src, placeholder, type, id)
        const changedAttrs = {};
        Array.from(sourceEl.attributes).forEach(attr => {
            const name = attr.name.toLowerCase();
            if (name === "style" || name === "class") return;
            if (liveEl.getAttribute(attr.name) !== attr.value) {
                changedAttrs[attr.name] = attr.value;
                changedLabels.push(name);
            }
        });

        // Bandingkan teks langsung (TEXT_NODE) pada elemen tanpa merusak child elements
        const liveTextNodes = Array.from(liveEl.childNodes).filter(
            n => n.nodeType === Node.TEXT_NODE && n.nodeValue.trim() !== ""
        );
        const sourceTextValues = Array.from(sourceEl.childNodes)
            .filter(n => n.nodeType === Node.TEXT_NODE && n.nodeValue.trim() !== "")
            .map(n => n.nodeValue);

        let textChanged = false;
        if (liveTextNodes.length !== sourceTextValues.length) {
            textChanged = true;
        } else {
            for (let i = 0; i < liveTextNodes.length; i++) {
                if (liveTextNodes[i].nodeValue.trim() !== sourceTextValues[i].trim()) {
                    textChanged = true;
                    break;
                }
            }
        }
        if (textChanged) {
            changedLabels.push("teks");
        }

        const hasChanges = changedLabels.length > 0;

        return {
            hasChanges: hasChanges,
            changedLabels: changedLabels,
            applyInPlace: () => {
                liveEl.classList.add("gm-ai-live-morph");

                // 1. Update hanya properti CSS yang berubah pada atribut style elemen yang sudah ada
                removedStyles.forEach(prop => {
                    liveEl.style.removeProperty(prop);
                });
                Object.keys(changedStyles).forEach(prop => {
                    liveEl.style.setProperty(prop, changedStyles[prop]);
                });

                // 2. Update atribut HTML non-style yang berubah
                Object.keys(changedAttrs).forEach(attrName => {
                    liveEl.setAttribute(attrName, changedAttrs[attrName]);
                });

                // Pastikan class gm-object tetap terjaga
                if (!["script", "style", "br", "hr"].includes(liveEl.tagName.toLowerCase())) {
                    liveEl.classList.add("gm-object");
                }

                // 3. Update teks langsung jika ada perubahan teks
                if (textChanged) {
                    if (liveEl.children.length === 0 && sourceEl.children.length === 0) {
                        liveEl.textContent = sourceEl.textContent;
                    } else if (liveTextNodes.length === sourceTextValues.length) {
                        for (let i = 0; i < liveTextNodes.length; i++) {
                            liveTextNodes[i].nodeValue = sourceTextValues[i];
                        }
                    } else {
                        liveTextNodes.forEach(tn => tn.remove());
                        sourceTextValues.forEach(txt => {
                            liveEl.insertBefore(document.createTextNode(txt), liveEl.firstChild);
                        });
                    }
                }

                // Highlight halus sesaat pada elemen yang atributnya baru saja diubah
                const prevOutline = liveEl.style.outline;
                liveEl.style.outline = "2px solid rgba(0, 120, 255, 0.55)";
                setTimeout(() => {
                    liveEl.style.outline = prevOutline || "";
                    liveEl.classList.remove("gm-ai-live-morph");
                }, 380);
            }
        };
    }

    /**
     * Mengumpulkan langkah-langkah patch atribut in-place secara rekursif antara pohon DOM yang ada
     * dan pohon DOM baru dari AI. Elemen yang sudah ada TIDAK dibuat ulang dari 0, melainkan hanya
     * atribut/style/teks yang berubah saja yang diperbarui langsung pada elemen DOM tersebut.
     */
    function collectDomTreePatchOperations(liveContainer, sourceElements, opsQueue) {
        const liveChildren = Array.from(liveContainer.children);
        const srcChildren = Array.from(sourceElements);

        let i = 0;
        let j = 0;

        while (i < liveChildren.length || j < srcChildren.length) {
            const liveChild = i < liveChildren.length ? liveChildren[i] : null;
            const srcChild = j < srcChildren.length ? srcChildren[j] : null;

            if (liveChild && srcChild && liveChild.tagName === srcChild.tagName) {
                const patchInfo = computeElementAttributePatch(liveChild, srcChild);
                if (patchInfo.hasChanges) {
                    opsQueue.push({
                        type: "patch_attributes",
                        el: liveChild,
                        tag: liveChild.tagName.toLowerCase(),
                        labels: patchInfo.changedLabels,
                        apply: patchInfo.applyInPlace
                    });
                }
                // Lanjutkan cek anak-anak di dalamnya secara rekursif
                collectDomTreePatchOperations(liveChild, srcChild.children, opsQueue);
                i++;
                j++;
            } else if (liveChild && !srcChild) {
                // Elemen lama dihapus (misal pengguna meminta hapus komponen/elemen tertentu)
                opsQueue.push({
                    type: "remove_element",
                    el: liveChild,
                    tag: liveChild.tagName.toLowerCase()
                });
                i++;
            } else if (!liveChild && srcChild) {
                // Elemen baru ditambahkan di akhir container
                opsQueue.push({
                    type: "insert_new_element",
                    parent: liveContainer,
                    srcNode: srcChild,
                    beforeNode: null,
                    tag: srcChild.tagName.toLowerCase()
                });
                j++;
            } else {
                // Tag berbeda: cek apakah ada penghapusan atau penyisipan di tengah urutan
                const nextLiveMatchesSrc =
                    i + 1 < liveChildren.length && liveChildren[i + 1].tagName === srcChild.tagName;
                const nextSrcMatchesLive =
                    j + 1 < srcChildren.length && srcChildren[j + 1].tagName === liveChild.tagName;

                if (nextLiveMatchesSrc) {
                    opsQueue.push({
                        type: "remove_element",
                        el: liveChild,
                        tag: liveChild.tagName.toLowerCase()
                    });
                    i++;
                } else if (nextSrcMatchesLive) {
                    opsQueue.push({
                        type: "insert_new_element",
                        parent: liveContainer,
                        srcNode: srcChild,
                        beforeNode: liveChild,
                        tag: srcChild.tagName.toLowerCase()
                    });
                    j++;
                } else {
                    // Penggantian tag langsung pada posisi yang sama
                    opsQueue.push({
                        type: "replace_tag_node",
                        parent: liveContainer,
                        oldNode: liveChild,
                        srcNode: srcChild,
                        tag: srcChild.tagName.toLowerCase()
                    });
                    i++;
                    j++;
                }
            }
        }
    }

    /**
     * Menjalankan antrean operasi patch secara instan (tanpa penundaan buatan).
     */
    function executeDomPatchOperations(opsQueue, statusEl, onDone) {
        if (!Array.isArray(opsQueue) || opsQueue.length === 0) {
            if (typeof onDone === "function") onDone(0);
            return;
        }

        opsQueue.forEach(op => {
            if (op.type === "patch_attributes") {
                op.apply();
            } else if (op.type === "remove_element") {
                if (op.el && op.el.parentNode) {
                    op.el.remove();
                }
            } else if (op.type === "insert_new_element") {
                const clone = op.srcNode.cloneNode(true);
                clone.classList.add("gm-ai-fade-in");
                if (op.beforeNode && op.beforeNode.parentNode === op.parent) {
                    op.parent.insertBefore(clone, op.beforeNode);
                } else {
                    op.parent.appendChild(clone);
                }
                if (typeof window.initLoadedObjects === "function") {
                    window.initLoadedObjects(clone);
                }
            } else if (op.type === "replace_tag_node") {
                const clone = op.srcNode.cloneNode(true);
                clone.classList.add("gm-ai-fade-in");
                if (op.oldNode && op.oldNode.parentNode === op.parent) {
                    op.parent.replaceChild(clone, op.oldNode);
                } else {
                    op.parent.appendChild(clone);
                }
                if (typeof window.initLoadedObjects === "function") {
                    window.initLoadedObjects(clone);
                }
            }
        });

        if (statusEl) {
            showStatus(statusEl, "success", `✓ Selesai menerapkan ${opsQueue.length} perubahan.`);
        }
        if (typeof onDone === "function") onDone(opsQueue.length);
    }

    /**
     * Memperbarui atribut/style elemen aktif secara langsung di tempatnya (In-Place).
     * TIDAK membuat elemen baru dari 0, TIDAK menduplikasi di atasnya, TIDAK menghapus elemen bawah.
     */
    function applyAttributePatchInPlace(targetEl, patch, statusEl, onDone) {
        if (!targetEl) {
            targetEl = getSelectedObject() || getActiveObjectCore();
        }
        if (!targetEl) {
            if (onDone) onDone(false);
            return;
        }

        let destEl = targetEl;
        if (patch && patch.target && patch.target !== "self") {
            const foundChild = targetEl.querySelector(patch.target);
            if (foundChild) destEl = foundChild;
        }

        // Terapkan transisi CSS halus agar perubahan warna/font/ukuran terlihat elegan
        destEl.style.transition = "background-color 0.28s ease, color 0.28s ease, font-size 0.2s ease, border-radius 0.2s ease, box-shadow 0.25s ease, padding 0.2s ease, border-color 0.25s ease";

        // 1. Terapkan Styles
        if (patch && patch.styles && typeof patch.styles === "object") {
            Object.keys(patch.styles).forEach(prop => {
                const val = patch.styles[prop];
                if (!val) return;

                if (prop === "__auto_color__") {
                    const tag = destEl.tagName.toLowerCase();
                    if (tag === "button" || destEl.style.backgroundColor || destEl.classList.contains("gm-bg")) {
                        destEl.style.backgroundColor = val;
                    } else {
                        destEl.style.color = val;
                    }
                } else if (prop === "font-family") {
                    destEl.style.fontFamily = val;
                    // Wariskan ke seluruh anak teks di dalam komponen agar konsisten
                    destEl.querySelectorAll("*").forEach(ch => {
                        ch.style.fontFamily = val;
                    });
                } else if (prop === "color") {
                    destEl.style.color = val;
                    // Jika destEl adalah navbar/card/header, sesuaikan juga elemen teks di dalamnya
                    destEl.querySelectorAll("a, span, p, h1, h2, h3, h4, div").forEach(ch => {
                        if (ch.children.length === 0) ch.style.color = val;
                    });
                } else if (prop === "background" || prop === "background-color") {
                    destEl.style.backgroundColor = val;
                } else {
                    destEl.style.setProperty(prop, val);
                }
            });
        }

        // 2. Terapkan Atribut HTML Non-Style jika ada
        if (patch && patch.attributes && typeof patch.attributes === "object") {
            Object.keys(patch.attributes).forEach(attr => {
                if (attr !== "style" && attr !== "class") {
                    destEl.setAttribute(attr, patch.attributes[attr]);
                }
            });
        }

        // 3. Terapkan Teks Baru jika ada
        if (patch && typeof patch.text === "string" && patch.text.trim() !== "") {
            const heading = destEl.querySelector("h1, h2, h3, h4, strong, span, a") || destEl;
            heading.textContent = patch.text.trim();
        }

        // Efek highlight seleksi halus sesaat
        const prevOutline = destEl.style.outline;
        destEl.style.outline = "2px solid #0078ff";
        setTimeout(() => {
            destEl.style.outline = prevOutline || "";
        }, 350);

        // Update handler seleksi Greymist agar kotak seleksi dan label dimensi menyesuaikan
        if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
            window.GMObjectHandler.attachTo(destEl);
        }

        refreshWorkspaceAfterChange();

        const summary = (patch && patch.summary) || "Atribut elemen berhasil diperbarui.";
        if (statusEl) {
            showStatus(statusEl, "success", "✓ " + summary);
        }

        if (onDone) onDone(true);
    }

    /**
     * Memperbarui objek aktif (.gm-object.gm-active) secara in-place.
     * Jika tag elemen tetap sama (misal mengubah font, warna, ukuran, border, atau teks),
     * elemen TIDAK dibuat ulang dari 0 melainkan hanya atribut/style-nya saja yang langsung di-patch!
     */
    function replaceSelectedObjectProgressive(targetObj, htmlString, statusEl, callback) {
        if (!targetObj || !targetObj.parentElement) {
            if (callback) callback(false);
            return;
        }

        const parent = targetObj.parentElement;
        const temp = document.createElement("div");
        temp.innerHTML = (htmlString || "").trim();
        const sourceRoots = Array.from(temp.children);

        if (sourceRoots.length === 0) {
            targetObj.remove();
            refreshWorkspaceAfterChange();
            if (callback) callback(true);
            return;
        }

        // Jika hanya 1 elemen root (kasus ubah item):
        // Jangan pernah membuat baru di atasnya lalu menghapus yang bawah!
        if (sourceRoots.length === 1) {
            const newRoot = sourceRoots[0];
            // Jika tag sama, langsung patch atribut & teks tanpa merusak node DOM
            if (newRoot.tagName === targetObj.tagName) {
                const opsQueue = [];
                const rootPatch = computeElementAttributePatch(targetObj, newRoot);
                if (rootPatch.hasChanges) {
                    opsQueue.push({
                        type: "patch_attributes",
                        el: targetObj,
                        tag: targetObj.tagName.toLowerCase(),
                        labels: rootPatch.changedLabels,
                        apply: rootPatch.applyInPlace
                    });
                }
                collectDomTreePatchOperations(targetObj, newRoot.children, opsQueue);
                executeDomPatchOperations(opsQueue, statusEl, () => {
                    targetObj.classList.add("gm-active");
                    if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                        window.GMObjectHandler.attachTo(targetObj);
                    }
                    refreshWorkspaceAfterChange();
                    if (callback) callback(true);
                });
                return;
            } else {
                // Jika tag berbeda (misal div diganti section), ganti langsung tanpa animasi bertumpuk
                parent.replaceChild(newRoot, targetObj);
                if (typeof window.initLoadedObjects === "function") {
                    window.initLoadedObjects(newRoot);
                }
                newRoot.classList.add("gm-active");
                if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                    window.GMObjectHandler.attachTo(newRoot);
                }
                refreshWorkspaceAfterChange();
                if (callback) callback(true);
                return;
            }
        }

        // Hanya jika hasil berupa beberapa elemen baru terpisah
        buildElementsLiveUnstyledToStyled(sourceRoots, parent, statusEl, createdRoots => {
            targetObj.remove();
            if (Array.isArray(createdRoots) && createdRoots.length > 0) {
                createdRoots.forEach(root => {
                    if (typeof window.initLoadedObjects === "function") {
                        window.initLoadedObjects(root);
                    }
                });
                document.querySelectorAll(".gm-object.gm-active").forEach(el => el.classList.remove("gm-active"));
                createdRoots[0].classList.add("gm-active");
                if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                    window.GMObjectHandler.attachTo(createdRoots[0]);
                }
            }
            refreshWorkspaceAfterChange();
            if (callback) callback(true);
        }, targetObj);
    }

    function ensureWorkspaceFrameMounted(frameData) {
        const frameId = String(frameData.frame_id);
        let core = document.getElementById("object-core-" + frameId);

        if (!core) {
            if (typeof window.createFrameUI === "function") {
                window.createFrameUI(
                    {
                        frame_id: frameData.frame_id,
                        frame_name: frameData.frame_name || ("Frame " + frameId),
                        core_content: ""
                    },
                    true
                );
                core = document.getElementById("object-core-" + frameId);
            } else {
                const preview = document.getElementById("gm-frame-preview");
                const thumbs = document.getElementById("gm-frame-thumbnails");
                if (preview && thumbs) {
                    core = document.createElement("div");
                    core.className = "gm-object-core actived";
                    core.id = "object-core-" + frameId;
                    core.dataset.frame = frameId;
                    preview.appendChild(core);

                    const thumb = document.createElement("div");
                    thumb.className = "gm-frame-thumbnail actived";
                    thumb.dataset.frame = frameId;
                    thumb.innerHTML = `<div class="gm-preview-box"></div><div class="gm-frame-name">${frameData.frame_name || ("Frame " + frameId)}</div>`;
                    thumb.onclick = () => {
                        if (typeof window.activateFrame === "function") window.activateFrame(frameId);
                    };
                    thumbs.appendChild(thumb);
                }
            }
        }

        if (typeof window.activateFrame === "function") {
            window.activateFrame(frameData.frame_id);
        }

        return core;
    }

    /**
     * Membangun seluruh frame yang baru dibuat secara instan dan simultan (tanpa penundaan buatan).
     */
    function buildCreatedFramesSequentially(createdFrames, statusEl, onAllDone) {
        if (!Array.isArray(createdFrames) || createdFrames.length === 0) {
            if (typeof onAllDone === "function") onAllDone(0);
            return;
        }

        // Render seluruh frame secara instan tanpa pembatasan atau delay buatan
        createdFrames.forEach((frameInfo, idx) => {
            const newCore = ensureWorkspaceFrameMounted(frameInfo);
            if (newCore) {
                newCore.innerHTML = (frameInfo.core_content || "").trim();
                if (typeof window.initLoadedObjects === "function") {
                    window.initLoadedObjects(newCore);
                }
                refreshWorkspaceAfterChange(frameInfo.frame_id);
            }
        });

        // Aktifkan frame pertama yang baru dibuat agar pengguna langsung melihat hasilnya
        if (createdFrames[0] && typeof window.activateFrame === "function") {
            window.activateFrame(createdFrames[0].frame_id);
        }

        refreshWorkspaceAfterChange();
        if (typeof onAllDone === "function") onAllDone(createdFrames.length);
    }

    function injectHtmlIntoCanvasProgressive(htmlString, targetMode, statusEl, callback) {
        const container = getTargetContainer(targetMode);
        if (!container) {
            alert("Frame atau Object Core tidak ditemukan. Buat atau pilih frame terlebih dahulu.");
            if (callback) callback(0);
            return;
        }

        const trimmed = (htmlString || "").trim();
        if (trimmed === "") {
            if (callback) callback(0);
            return;
        }

        const temp = document.createElement("div");
        temp.innerHTML = trimmed;
        const sourceRoots = Array.from(temp.children);

        sourceRoots.forEach(node => {
            node.classList.add("gm-ai-fade-in");
            container.appendChild(node);
            if (typeof window.initLoadedObjects === "function") {
                window.initLoadedObjects(node);
            }
        });

        refreshWorkspaceAfterChange();
        if (callback) callback(sourceRoots.length);
    }

    /**
     * Memperbarui isi Frame Aktif secara cerdas (In-Place Diffing & Attribute Patching).
     * TIDAK mengosongkan core.innerHTML atau membuat ulang dari 0 jika frame sudah memiliki elemen:
     * hanya elemen/atribut/style yang berubah saja yang di-update secara langsung di tempatnya!
     */
    function replaceFrameHtmlProgressive(htmlString, statusEl, callback) {
        const core = getActiveObjectCore();
        if (!core) {
            if (callback) callback(false);
            return;
        }

        const trimmed = (htmlString || "").trim();

        // Hanya jika respons kosong (pengguna eksplisit meminta "kosongkan frame" / "hapus semua")
        if (trimmed === "") {
            core.innerHTML = "";
            refreshWorkspaceAfterChange();
            if (callback) callback(true);
            return;
        }

        const temp = document.createElement("div");
        temp.innerHTML = trimmed;
        const sourceRoots = Array.from(temp.children);

        // Jika frame sebelumnya sudah memiliki elemen, gunakan In-Place DOM Diff & Attribute Patcher
        // sehingga perubahan font, warna, ukuran, atau penghapusan 1 item TIDAK meriset elemen lain dari 0!
        if (core.children.length > 0) {
            const opsQueue = [];
            collectDomTreePatchOperations(core, sourceRoots, opsQueue);

            executeDomPatchOperations(opsQueue, statusEl, () => {
                const activeObj = getSelectedObject();
                if (activeObj && typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                    window.GMObjectHandler.attachTo(activeObj);
                }
                refreshWorkspaceAfterChange();
                if (callback) callback(true);
            });
            return;
        }

        // Jika frame memang masih kosong (0 elemen), render langsung secara instan
        core.innerHTML = trimmed;
        if (typeof window.initLoadedObjects === "function") {
            window.initLoadedObjects(core);
        }
        refreshWorkspaceAfterChange();
        if (callback) callback(true);
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

    /**
     * Menyuntikkan fitur Prompt AI langsung ke dalam Menu Klik Kanan (.gm-object-menu)
     * ketika sebuah objek aktif (.gm-object.gm-active) diklik kanan.
     */
    function setupRightClickAiObjectEditor() {
        const contextMenu = document.querySelector(".gm-object-menu");
        if (!contextMenu || document.getElementById("gm-ai-context-editor-item")) return;

        const li = document.createElement("li");
        li.id = "gm-ai-context-editor-item";
        li.className = "gm-active-menu gm-ai-context-box";
        li.innerHTML = `
            <div class="gm-ai-ctx-header">
                <i class="fas fa-wand-magic-sparkles"></i>
                <span>Ubah Objek Aktif dengan AI</span>
                <code id="gm-ai-ctx-tag-badge">&lt;div&gt;</code>
            </div>
            <div class="gm-ai-ctx-input-row">
                <input type="text" id="gm-ai-ctx-prompt" placeholder="Misal: ubah warna jadi biru, tambah icon..." autocomplete="off">
                <button type="button" id="gm-ai-ctx-submit" title="Jalankan Perubahan pada Objek Ini (Enter)">
                    <i class="fas fa-bolt"></i>
                </button>
            </div>
            <div id="gm-ai-ctx-status" style="display:none;font-size:10px;margin-top:4px;line-height:1.3;"></div>
        `;

        // Masukkan di urutan paling atas pada menu klik kanan objek aktif
        contextMenu.insertBefore(li, contextMenu.firstChild);

        // Cegah klik atau ketikan di dalam kotak prompt menutup context menu
        ["click", "mousedown", "mouseup", "keydown", "keyup"].forEach(evtName => {
            li.addEventListener(evtName, e => {
                e.stopPropagation();
            });
        });

        const promptInput = document.getElementById("gm-ai-ctx-prompt");
        const submitBtn = document.getElementById("gm-ai-ctx-submit");
        const ctxStatus = document.getElementById("gm-ai-ctx-status");
        const tagBadge = document.getElementById("gm-ai-ctx-tag-badge");

        // Update badge tag setiap kali klik kanan dibuka pada objek
        document.addEventListener("contextmenu", () => {
            setTimeout(() => {
                const activeObj = getSelectedObject();
                if (activeObj && tagBadge) {
                    tagBadge.textContent = `<${activeObj.tagName.toLowerCase()}>`;
                }
                if (ctxStatus) {
                    ctxStatus.style.display = "none";
                }
            }, 20);
        });

        function executeContextAiEdit() {
            const targetObj = getSelectedObject();
            if (!targetObj) {
                ctxStatus.style.display = "block";
                ctxStatus.style.color = "#dc2626";
                ctxStatus.textContent = "Tidak ada objek aktif yang dipilih.";
                return;
            }

            const instruction = promptInput.value.trim();
            if (!instruction) {
                promptInput.focus();
                return;
            }

            const selectedModelEl = document.getElementById("gm-ai-model-select");
            const selectedModel = selectedModelEl ? selectedModelEl.value : "gemini-3.5-flash-lite";
            const activeCore = getActiveObjectCore();
            const currentFrameHtml = activeCore ? activeCore.innerHTML : "";
            const selectedObjectHtml = targetObj.outerHTML;
            const projectId = getCurrentProjectId();
            const framesSnapshot = collectAllFramesSnapshot();

            // Baca atribut dan computed style komponen terlebih dahulu
            const computed = window.getComputedStyle(targetObj);
            const selectedAttrs = {
                tag: targetObj.tagName.toLowerCase(),
                background: computed.backgroundColor,
                color: computed.color,
                fontFamily: computed.fontFamily,
                fontSize: computed.fontSize,
                padding: computed.padding,
                borderRadius: computed.borderRadius
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
            ctxStatus.style.display = "block";
            ctxStatus.style.color = "#2563eb";
            ctxStatus.textContent = "Membaca & memodifikasi atribut objek...";

            fetch("api/ai_generate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    project_id: projectId,
                    prompt: instruction,
                    model: selectedModel,
                    target_mode: "edit_selected",
                    save_to_library: false,
                    current_frame_html: currentFrameHtml,
                    selected_object_html: selectedObjectHtml,
                    selected_object_attrs: selectedAttrs,
                    frames_snapshot: framesSnapshot
                })
            })
                .then(r => r.json())
                .then(res => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fas fa-bolt"></i>`;

                    if (res.status !== "OK") {
                        ctxStatus.style.color = "#dc2626";
                        ctxStatus.textContent = res.msg || "Gagal memodifikasi objek.";
                        return;
                    }

                    if (res.ai_memory) {
                        cachedAiMemory = res.ai_memory;
                        try {
                            localStorage.setItem("gm_ai_memory_proj_" + projectId, JSON.stringify(cachedAiMemory));
                        } catch (_) {}
                        updateMemoryBadgeUI(cachedAiMemory);
                    }

                    promptInput.value = "";
                    if (typeof window.GMCloseContextMenu === "function") {
                        window.GMCloseContextMenu(true);
                    }

                    const mainStatusEl = document.getElementById("gm-ai-status-msg");

                    // Jika AI mengembalikan instruksi perubahan atribut (tanpa buat elemen baru dari 0)
                    if (res.operation === "patch_attributes" && res.patch) {
                        applyAttributePatchInPlace(targetObj, res.patch, mainStatusEl, () => {
                            if (mainStatusEl) {
                                showStatus(mainStatusEl, "success", "✓ " + (res.patch.summary || "Objek aktif berhasil diperbarui."));
                            }
                            syncProjectAiMemory();
                        });
                        return;
                    }

                    replaceSelectedObjectProgressive(targetObj, res.html || "", mainStatusEl, () => {
                        if (mainStatusEl) {
                            showStatus(mainStatusEl, "success", "✓ Objek aktif berhasil diperbarui.");
                        }
                    });
                })
                .catch(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fas fa-bolt"></i>`;
                    ctxStatus.style.color = "#dc2626";
                    ctxStatus.textContent = "Gagal menghubungi endpoint AI.";
                });
        }

        submitBtn.addEventListener("click", executeContextAiEdit);
        promptInput.addEventListener("keydown", e => {
            if (e.key === "Enter") {
                e.preventDefault();
                executeContextAiEdit();
            } else if (e.key === "Escape") {
                if (typeof window.GMCloseContextMenu === "function") {
                    window.GMCloseContextMenu(true);
                }
            }
        });
    }

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
                <!-- Inspector Isi Frame Aktif (Tanpa Tombol Hapus — Hanya Baca & Pilih, Hapus via Prompt) -->
                <div class="gm-ai-frame-box">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;">
                        <div style="display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#475569;">
                            <i class="fas fa-layer-group" style="color:#0078ff;"></i>
                            <span>Isi Frame (<span id="gm-ai-frame-count">0</span>)</span>
                        </div>
                        <span id="gm-ai-memory-badge" class="gm-ai-memory-pill" title="Memori Konteks Proyek AI">🧠 Memori AI Aktif</span>
                    </div>
                    <div class="gm-ai-frame-items" id="gm-ai-frame-items-list"></div>
                </div>

                <!-- Preset Cepat -->
                <div class="gm-ai-chip-list">
                    ${presetChipsHtml}
                </div>

                <!-- Prompt Field (Untuk Buat, Edit, Tambah Frame, maupun Hapus Komponen) -->
                <textarea id="gm-ai-prompt-input" class="gm-ai-field" rows="2" placeholder="Ketik instruksi: buat komponen/page baru, edit, atau 'Hapus bagian...'..." style="resize:vertical;line-height:1.4;"></textarea>

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

        // Pasang juga fitur AI Edit Object pada menu klik kanan (.gm-object-menu)
        setupRightClickAiObjectEditor();

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

        // Jalankan AI dengan Memori Proyek & Tanpa Pembatasan
        const generateBtn = document.getElementById("gm-ai-generate-btn");
        generateBtn.addEventListener("click", () => {
            const prompt = document.getElementById("gm-ai-prompt-input").value.trim();
            const selectedModel = document.getElementById("gm-ai-model-select").value;
            const targetMode = document.getElementById("gm-ai-target-mode").value;
            const compName = document.getElementById("gm-ai-comp-name").value.trim() || "AI Component";
            const saveToLibrary = document.getElementById("gm-ai-save-library").checked;

            if (!prompt) {
                showStatus(statusEl, "error", "Isi instruksi pada kolom prompt terlebih dahulu.");
                return;
            }

            const activeCore = getActiveObjectCore();
            const currentFrameHtml = activeCore ? activeCore.innerHTML : "";
            let selectedObj = getSelectedObject();
            let effectiveTargetMode = targetMode;

            // Deteksi apakah pengguna meminta modifikasi atribut/style pada komponen yang sudah ada
            const isAttributeEditPrompt = /\b(ubah|ganti|rubah|font|warna|color|background|bg|ukuran|size|teks|tulisan|radius|rounded|padding|margin|shadow|jadikan)\b/i.test(prompt);

            // Jika belum ada elemen aktif yang dipilih, tetapi prompt merujuk ke elemen tertentu di frame (misal navbar, header, footer, tombol, judul)
            if (!selectedObj && activeCore && isAttributeEditPrompt) {
                if (/\b(navbar|header|nav)\b/i.test(prompt)) {
                    selectedObj = activeCore.querySelector("header, nav, [class*='nav']");
                } else if (/\b(footer|kaki)\b/i.test(prompt)) {
                    selectedObj = activeCore.querySelector("footer");
                } else if (/\b(tombol|button|cta)\b/i.test(prompt)) {
                    selectedObj = activeCore.querySelector("button");
                } else if (/\b(judul|heading|title|h1)\b/i.test(prompt)) {
                    selectedObj = activeCore.querySelector("h1, h2, h3");
                }
                if (selectedObj) {
                    document.querySelectorAll(".gm-object.gm-active").forEach(el => el.classList.remove("gm-active"));
                    selectedObj.classList.add("gm-active");
                    if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
                        window.GMObjectHandler.attachTo(selectedObj);
                    }
                }
            }

            if (selectedObj && isAttributeEditPrompt && effectiveTargetMode === "core") {
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
                    borderRadius: computed.borderRadius
                };
            }

            const projectId = getCurrentProjectId();
            const framesSnapshot = collectAllFramesSnapshot();

            generateBtn.disabled = true;
            generateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Proses...</span>`;
            showStatus(statusEl, "info", "AI membaca memori proyek & memproses instruksi...");

            fetch("api/ai_generate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    project_id: projectId,
                    prompt: prompt,
                    model: selectedModel,
                    target_mode: effectiveTargetMode,
                    component_name: compName,
                    save_to_library: saveToLibrary,
                    current_frame_html: currentFrameHtml,
                    selected_object_html: selectedObjectHtml,
                    selected_object_attrs: selectedAttrs,
                    frames_snapshot: framesSnapshot
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

                    const op = res.operation || "append";

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
                            showStatus(statusEl, "success", `✓ Selesai dibangun (${inserted} komponen utama).`);
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
