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
        { label: "+Page Baru", prompt: "Buat frame halaman baru untuk halaman Login lengkap dengan Navbar dan Form Login" },
        { label: "Multi-Page", prompt: "Buat 3 frame halaman website terpisah: Beranda, Layanan & Harga, dan Hubungi Kami" },
        { label: "Baca Proyek", prompt: "Baca dan jelaskan susunan proyek, halaman-halaman, serta komponen di dalamnya" },
        { label: "📸 Clone Foto", prompt: "Buat antarmuka (UI) website modern yang meniru tata letak dan estetika foto referensi yang diunggah" },
        { label: "🏷️ Ganti Nama", prompt: "Ganti nama project jadi " }
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
        renderFrameComponentsList();
    }

    /**
     * Membangun pohon elemen per elemen secara realtime dengan latensi sangat rendah (Ultra-Low Latency 60 FPS).
     * Elemen muncul polos di layar kanvas terlebih dahulu, lalu gayanya (style) teraplikasikan secara realtime secepat kilat.
     */
    function buildElementsLiveUnstyledToStyled(sourceRootElements, targetContainer, statusEl, onComplete, insertBeforeNode = null) {
        const steps = [];

        function collectSteps(sourceNode, liveParent, refBeforeNode = null) {
            const tag = sourceNode.tagName.toLowerCase();
            const liveEl = document.createElement(tag);

            Array.from(sourceNode.attributes).forEach(attr => {
                if (attr.name.toLowerCase() !== "style") {
                    liveEl.setAttribute(attr.name, attr.value);
                }
            });
            if (!["script", "style", "br", "hr"].includes(tag)) {
                liveEl.classList.add("gm-object");
            }

            const directTexts = [];
            Array.from(sourceNode.childNodes).forEach(childNode => {
                if (childNode.nodeType === Node.TEXT_NODE && childNode.nodeValue.trim() !== "") {
                    directTexts.push(childNode.nodeValue);
                }
            });

            const savedStyle = sourceNode.getAttribute("style") || "";

            // Tahap 1: Munculkan elemen mentah/polos di kanvas
            steps.push({
                type: "mount_raw",
                el: liveEl,
                parent: liveParent,
                beforeNode: refBeforeNode,
                directTexts: directTexts,
                tag: tag
            });

            // Tahap 2: Terapkan atribut style secara live
            if (savedStyle.trim() !== "") {
                steps.push({
                    type: "apply_style",
                    el: liveEl,
                    style: savedStyle,
                    tag: tag
                });
            }

            Array.from(sourceNode.children).forEach(childEl => {
                collectSteps(childEl, liveEl, null);
            });

            return liveEl;
        }

        const createdRoots = [];
        sourceRootElements.forEach(srcRoot => {
            const liveRoot = collectSteps(srcRoot, targetContainer, insertBeforeNode);
            createdRoots.push(liveRoot);
        });

        const totalSteps = steps.length;
        if (totalSteps === 0) {
            if (typeof onComplete === "function") onComplete(createdRoots);
            return;
        }

        // Kecepatan adaptif realtime ultra-rendah latensi: 6ms - 14ms per langkah
        const stepDelay = Math.max(6, Math.min(14, Math.floor(420 / totalSteps)));
        let idx = 0;

        function runNextStep() {
            if (idx >= totalSteps) {
                createdRoots.forEach(root => {
                    root.classList.remove("gm-ai-live-morph");
                    root.querySelectorAll(".gm-ai-live-morph").forEach(el => {
                        el.classList.remove("gm-ai-live-morph");
                    });
                });
                if (typeof onComplete === "function") {
                    onComplete(createdRoots);
                }
                return;
            }

            const step = steps[idx];

            if (step.type === "mount_raw") {
                step.directTexts.forEach(txt => {
                    step.el.appendChild(document.createTextNode(txt));
                });
                if (step.beforeNode && step.beforeNode.parentNode === step.parent) {
                    step.parent.insertBefore(step.el, step.beforeNode);
                } else {
                    step.parent.appendChild(step.el);
                }

                if (idx === 0) {
                    try {
                        step.el.scrollIntoView({ behavior: "smooth", block: "nearest" });
                    } catch (_) {}
                }
                if (statusEl && idx % 4 === 0) {
                    showStatus(statusEl, "info", `⚡ Realtime: merakit <${step.tag}>...`);
                }
            } else if (step.type === "apply_style") {
                step.el.classList.add("gm-ai-live-morph");
                step.el.setAttribute("style", step.style);
            }

            idx++;

            // Gunakan timer mikro (6-14ms) agar proses terlihat nyata tapi secepat kilat
            if (stepDelay <= 8) {
                requestAnimationFrame(runNextStep);
            } else {
                setTimeout(() => {
                    requestAnimationFrame(runNextStep);
                }, stepDelay);
            }
        }

        requestAnimationFrame(runNextStep);
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
    function findSpecificCards(container) {
        if (!container) return [];
        const all = Array.from(container.querySelectorAll(".card, [class*='card'], [class*='item'], [class*='box'], div[style*='border']"));
        const filtered = all.filter(el => {
            const idCls = (el.className + " " + (el.id || "")).toLowerCase();
            return !/\b(container|grid|wrapper|row|list|group)\b/i.test(idCls);
        });
        const leaf = filtered.filter(el => {
            const hasChildCard = el.querySelector(".card, [class*='card'], [class*='item'], [class*='box']");
            return !hasChildCard;
        });
        if (leaf.length > 0) return leaf;

        // Fallback: periksa container flex/grid yang memiliki anak langsung >= 2
        const potentialContainers = [container, ...Array.from(container.querySelectorAll("div, section"))].filter(c => {
            if (!c || !c.getAttribute) return false;
            const style = (c.getAttribute("style") || "").toLowerCase();
            const cls = (c.className || "").toLowerCase();
            return style.includes("display: flex") || style.includes("display:flex") ||
                   style.includes("display: grid") || style.includes("display:grid") ||
                   cls.includes("grid") || cls.includes("flex") || cls.includes("row");
        });
        for (const pc of potentialContainers) {
            const directKids = Array.from(pc.children).filter(ch => ch.tagName === "DIV" || ch.tagName === "ARTICLE");
            if (directKids.length >= 2) {
                return directKids;
            }
        }
        return [];
    }

    function resolveSpecificTargetElement(activeCore, selectedObj, prompt) {
        if (!activeCore) return selectedObj;
        const lower = prompt.toLowerCase();

        const isSpecificSingleCard = /\b(ke\s*[-]?\s*[1-9]|kedua|ketiga|keempat|kelima|first|second|third|fourth|pertama|kesatu|terakhir|last|ini|yang\s+dipilih|terpilih|active|selected|pro|populer|popular|enterprise|bisnis|business|basic|starter|pemula|gratis|free)\b/i.test(lower);
        const isPluralCardPrompt = /\b(semua|all|seluruh|tiap|setiap|para|cards|kartu-kartu)\b/i.test(lower);

        // 1. Cek apakah prompt merujuk ke CARD / KARTU / ITEM / BOX / PRICING / HARGA
        if (/\b(card|kartu|kotak|box|item|pricing|harga|paket)\b/i.test(lower)) {
            const scope = (selectedObj && (selectedObj.tagName === "SECTION" || selectedObj.tagName === "DIV" || selectedObj.classList.contains("gm-object"))) ? selectedObj : activeCore;
            let cards = findSpecificCards(scope);
            if (cards.length === 0 && scope !== activeCore) {
                cards = findSpecificCards(activeCore);
            }

            if (cards.length > 0) {
                // Jika pengguna meminta kartu SPESIFIK tunggal (misal card ke-2, card pro, dsb.)
                if (isSpecificSingleCard && !isPluralCardPrompt) {
                    let targetCard = null;
                    if (/\b(ke\s*[-]?\s*2|kedua|second|2(?:nd)?)\b/i.test(lower)) {
                        targetCard = cards[1] || cards[0];
                    } else if (/\b(ke\s*[-]?\s*3|ketiga|third|3(?:rd)?)\b/i.test(lower)) {
                        targetCard = cards[2] || cards[cards.length - 1];
                    } else if (/\b(ke\s*[-]?\s*4|keempat|fourth|4(?:th)?)\b/i.test(lower)) {
                        targetCard = cards[3] || cards[cards.length - 1];
                    } else if (/\b(ke\s*[-]?\s*1|pertama|kesatu|first|1(?:st)?)\b/i.test(lower)) {
                        targetCard = cards[0];
                    } else if (/\b(terakhir|last)\b/i.test(lower)) {
                        targetCard = cards[cards.length - 1];
                    } else if (/\b(ini|dipilih|terpilih|active|selected)\b/i.test(lower) && selectedObj && cards.includes(selectedObj)) {
                        targetCard = selectedObj;
                    } else {
                        for (const c of cards) {
                            const txt = c.textContent.toLowerCase();
                            const idCls = ((c.id || "") + " " + c.className).toLowerCase();
                            if (/\b(pro|populer|popular)\b/i.test(lower) && (txt.includes("pro") || txt.includes("populer") || idCls.includes("pro"))) {
                                targetCard = c; break;
                            }
                            if (/\b(enterprise|bisnis|business)\b/i.test(lower) && (txt.includes("enterprise") || txt.includes("bisnis") || idCls.includes("enterprise"))) {
                                targetCard = c; break;
                            }
                            if (/\b(basic|starter|pemula|gratis|free)\b/i.test(lower) && (txt.includes("basic") || txt.includes("starter") || txt.includes("gratis") || idCls.includes("starter"))) {
                                targetCard = c; break;
                            }
                        }
                    }

                    if (targetCard) return targetCard;
                }

                // Jika pengguna meminta animasi kartu secara umum/jamak (tanpa menunjuk satu kartu spesifik),
                // pertahankan kontainer pembungkus kartu (parent element) agar SEMUA saudara kartu (siblings)
                // ikut terbawa ke backend dan dianimasikan secara berurutan (staggered delay)!
                if (cards.length > 1) {
                    const parentContainer = cards[0].parentElement;
                    if (parentContainer && parentContainer !== activeCore && parentContainer.tagName !== "BODY") {
                        return parentContainer;
                    }
                    return scope;
                }
                return cards[0];
            }
        }

        // 2. Cek apakah prompt merujuk ke TOMBOL / BUTTON / CTA
        if (/\b(tombol|button|cta)\b/i.test(lower)) {
            const scope = selectedObj || activeCore;
            const btn = scope.querySelector("button, .btn, [class*='btn'], a[class*='btn']") || activeCore.querySelector("button, .btn, [class*='btn']");
            if (btn) return btn;
        }

        // 3. Cek apakah prompt merujuk ke JUDUL / HEADING / TITLE / H1 / H2 / H3
        if (/\b(judul|heading|title|h1|h2|h3)\b/i.test(lower)) {
            const scope = selectedObj || activeCore;
            const h = scope.querySelector("h1, h2, h3, h4") || activeCore.querySelector("h1, h2, h3, h4");
            if (h) return h;
        }

        // 4. Cek apakah prompt merujuk ke NAVBAR / HEADER
        if (/\b(navbar|header|nav)\b/i.test(lower)) {
            const nav = activeCore.querySelector("header, nav, [class*='nav']");
            if (nav) return nav;
        }

        // 5. Cek apakah prompt merujuk ke FOOTER
        if (/\b(footer|kaki)\b/i.test(lower)) {
            const foot = activeCore.querySelector("footer, [class*='footer']");
            if (foot) return foot;
        }

        // 6. Jika prompt animasi tapi elemen saat ini adalah root section atau core:
        if (/\b(animasi|anim|animasikan)\b/i.test(lower)) {
            if (!selectedObj || selectedObj === activeCore || selectedObj.classList.contains("gm-object-core") || selectedObj.tagName === "SECTION") {
                const specificCards = findSpecificCards(selectedObj || activeCore);
                if (specificCards.length > 0) {
                    if (specificCards.length > 1 && !isSpecificSingleCard) {
                        const parentContainer = specificCards[0].parentElement;
                        return (parentContainer && parentContainer !== activeCore && parentContainer.tagName !== "BODY") ? parentContainer : (selectedObj || activeCore);
                    }
                    return specificCards[0];
                }
                const specificChild = (selectedObj || activeCore).querySelector("button, h1, h2, [class*='item'], div[style*='border']");
                if (specificChild) return specificChild;
            }
        }

        return selectedObj;
    }

    function applyAttributePatchInPlace(targetEl, patch, statusEl, onDone) {
        if (!targetEl) {
            targetEl = getSelectedObject() || getActiveObjectCore();
        }
        if (!targetEl) {
            if (onDone) onDone(false);
            return;
        }

        const activeCore = getActiveObjectCore();
        let targetElements = [];

        // 1. Selector Presisi Array: Jika patch menyediakan array 'targets' (misal ['#card-1', '#card-2', '#card-3'])
        if (patch && Array.isArray(patch.targets) && patch.targets.length > 0) {
            patch.targets.forEach(sel => {
                if (typeof sel === "string" && sel.trim() !== "") {
                    let el = targetEl.querySelector(sel);
                    if (!el && activeCore) el = activeCore.querySelector(sel);
                    if (!el) el = document.querySelector(sel);
                    if (el && !targetElements.includes(el)) {
                        targetElements.push(el);
                    }
                }
            });
        }

        // 2. Selector Presisi String: Jika patch memberikan target spesifik (bisa comma-separated misal '#card-1, #card-2' atau '.card')
        if (targetElements.length === 0 && patch && patch.target && patch.target !== "self") {
            try {
                let els = targetEl.querySelectorAll(patch.target);
                if (els.length === 0 && activeCore) els = activeCore.querySelectorAll(patch.target);
                if (els.length === 0) els = document.querySelectorAll(patch.target);
                if (els.length > 0) {
                    targetElements = Array.from(els);
                }
            } catch (_) {}
        }

        // 3. Proteksi Anti-Root & Multi-Card: JANGAN PERNAH menganimasikan frame/object-core atau root section jika ada card di dalamnya!
        if (targetElements.length === 0) {
            let destEl = targetEl;
            const isCoreOrSection = destEl.classList.contains("gm-object-core") || destEl.id === "object-core" || destEl.tagName === "SECTION" || destEl.tagName === "BODY";
            const hasAnimPayload = (patch && patch.attributes && patch.attributes["data-gm-anim"]) ||
                                   (patch && Array.isArray(patch.classes_to_add) && patch.classes_to_add.some(c => c.includes("anim"))) ||
                                   (patch && Array.isArray(patch.classes_to_remove) && patch.classes_to_remove.includes("gm-anim-base"));

            const specificCards = findSpecificCards(destEl);
            if ((isCoreOrSection || (patch && patch.target_all)) && specificCards.length > 0 && hasAnimPayload) {
                targetElements = specificCards;
            } else if (isCoreOrSection && hasAnimPayload) {
                const cardChild = destEl.querySelector("button, [class*='item'], div[style*='border'], h1, h2");
                if (cardChild) targetElements = [cardChild];
                else targetElements = [destEl];
            } else {
                targetElements = [destEl];
            }
        }

        const ALL_GM_ANIMS = [
            "gm-anim-fade-in",
            "gm-anim-slide-up",
            "gm-anim-slide-down",
            "gm-anim-slide-left",
            "gm-anim-slide-right",
            "gm-anim-zoom-in",
            "gm-anim-bounce-in"
        ];

        function normalizeGreymistAnimName(name) {
            if (!name) return "";
            const n = String(name).toLowerCase().trim();
            if (n === "none" || n === "false" || n === "null" || n === "") return "";
            if (n.includes("bounce")) return "gm-anim-bounce-in";
            if (n.includes("zoom")) return "gm-anim-zoom-in";
            if (n.includes("slide") && n.includes("down")) return "gm-anim-slide-down";
            if (n.includes("slide") && n.includes("left")) return "gm-anim-slide-left";
            if (n.includes("slide") && n.includes("right")) return "gm-anim-slide-right";
            if (n.includes("slide") || n.includes("up")) return "gm-anim-slide-up";
            if (n.includes("fade")) return "gm-anim-fade-in";
            return n.startsWith("gm-anim-") ? n : "gm-anim-" + n;
        }

        const isRemovingAnim = (patch && Array.isArray(patch.classes_to_remove) && patch.classes_to_remove.includes("gm-anim-base")) ||
                               (patch && patch.attributes && (patch.attributes["data-gm-anim"] === "" || patch.attributes["data-gm-anim"] === "none"));

        let targetAnim = "";
        if (!isRemovingAnim) {
            if (patch && patch.attributes && patch.attributes["data-gm-anim"]) {
                targetAnim = normalizeGreymistAnimName(patch.attributes["data-gm-anim"]);
            } else if (patch && Array.isArray(patch.classes_to_add)) {
                const found = patch.classes_to_add.find(c => c.includes("anim") || c.includes("fade") || c.includes("slide") || c.includes("zoom") || c.includes("bounce"));
                if (found && found !== "gm-anim-base") {
                    targetAnim = normalizeGreymistAnimName(found);
                }
            }
        }

        const baseDur = (patch && patch.attributes && patch.attributes["data-gm-anim-dur"]) || (patch && patch.styles && patch.styles["--gm-anim-duration"]) || "0.8";
        const durNum = String(baseDur).replace(/s$/i, "");
        const rawBaseDel = (patch && patch.attributes && patch.attributes["data-gm-anim-del"]) || (patch && patch.styles && patch.styles["--gm-anim-delay"]) || "0";
        const baseDelNum = parseFloat(String(rawBaseDel).replace(/s$/i, "")) || 0;
        const loopVal = (patch && patch.attributes && patch.attributes["data-gm-anim-loop"]) || (patch && patch.styles && patch.styles["animation-iteration-count"]) || "1";

        const staggerStep = (typeof patch.stagger === "number") ? patch.stagger : (targetElements.length > 1 ? 0.15 : 0);

        // Terapkan patch pada SETIAP elemen target (seluruh kartu saudara / siblings)
        targetElements.forEach((destEl, elIdx) => {
            // Terapkan transisi CSS halus agar perubahan warna/font/ukuran terlihat elegan
            destEl.style.transition = "background-color 0.28s ease, color 0.28s ease, font-size 0.2s ease, border-radius 0.2s ease, box-shadow 0.25s ease, padding 0.2s ease, border-color 0.25s ease";

            // 0. Hapus Class, Atribut, atau Style jika diminta (misal reset warna atau hapus animasi)
            if (patch && Array.isArray(patch.classes_to_remove)) {
                patch.classes_to_remove.forEach(c => c && destEl.classList.remove(c));
            }
            if (patch && Array.isArray(patch.attributes_to_remove)) {
                patch.attributes_to_remove.forEach(attr => {
                    if (attr) {
                        destEl.removeAttribute(attr);
                        if (attr.startsWith("data-")) {
                            const camel = attr.slice(5).replace(/-([a-z])/g, (_, g) => g.toUpperCase());
                            delete destEl.dataset[camel];
                        }
                    }
                });
            }
            if (patch && Array.isArray(patch.styles_to_remove)) {
                patch.styles_to_remove.forEach(prop => {
                    if (prop) destEl.style.removeProperty(prop);
                });
            }

            // 1. Terapkan Styles
            if (patch && patch.styles && typeof patch.styles === "object") {
                Object.keys(patch.styles).forEach(prop => {
                    if (prop.startsWith("--gm-anim-")) return;
                    const val = patch.styles[prop];
                    if (val === null || val === undefined) return;

                    if (prop === "__auto_color__") {
                        const tag = destEl.tagName.toLowerCase();
                        if (tag === "button" || destEl.style.backgroundColor || destEl.classList.contains("gm-bg") || tag === "nav" || tag === "header" || tag === "footer" || tag === "section") {
                            destEl.style.backgroundColor = val;
                        } else {
                            destEl.style.color = val;
                        }
                    } else if (prop === "font-family") {
                        destEl.style.fontFamily = val;
                        destEl.querySelectorAll("*").forEach(ch => {
                            ch.style.fontFamily = val;
                        });
                    } else if (prop === "color") {
                        destEl.style.color = val;
                        destEl.querySelectorAll("a, span, p, h1, h2, h3, h4, div").forEach(ch => {
                            if (ch.children.length === 0) ch.style.color = val;
                        });
                    } else if (prop === "background" || prop === "background-color") {
                        destEl.style.background = val;
                        destEl.style.backgroundColor = val;
                    } else {
                        destEl.style.setProperty(prop, val);
                    }
                });
            }

            // 2. Terapkan Tambahan Class
            if (patch && Array.isArray(patch.classes_to_add)) {
                patch.classes_to_add.forEach(c => {
                    if (c && !c.includes("anim")) destEl.classList.add(c);
                });
            }

            // 3. Terapkan Atribut HTML Non-Style (Action, Animasi, Id, Href, dll.)
            if (patch && patch.attributes && typeof patch.attributes === "object") {
                Object.keys(patch.attributes).forEach(attr => {
                    if (attr.startsWith("data-gm-anim")) return;
                    if (attr === "class") {
                        const classes = String(patch.attributes[attr]).split(/\s+/);
                        classes.forEach(c => c && !c.includes("anim") && destEl.classList.add(c));
                    } else if (attr !== "style" && attr !== "id") {
                        destEl.setAttribute(attr, patch.attributes[attr]);
                    }
                });
            }

            // 4. Sinkronisasi Animasi Kompatibel Greymist (7 class resmi, dataset, CSS vars, & Reflow)
            if (isRemovingAnim) {
                ALL_GM_ANIMS.forEach(c => destEl.classList.remove(c));
                destEl.classList.remove("gm-anim-base");
                delete destEl.dataset.gmAnim;
                delete destEl.dataset.gmAnimDur;
                delete destEl.dataset.gmAnimDel;
                delete destEl.dataset.gmAnimLoop;
                destEl.style.removeProperty("--gm-anim-duration");
                destEl.style.removeProperty("--gm-anim-delay");
                destEl.style.animationIterationCount = "";
            } else if (targetAnim) {
                ALL_GM_ANIMS.forEach(c => destEl.classList.remove(c));
                destEl.classList.add("gm-anim-base", targetAnim);
                destEl.dataset.gmAnim = targetAnim;

                // Hitung Stagger Delay per kartu jika ada beberapa elemen
                const itemDel = Math.round((baseDelNum + (elIdx * staggerStep)) * 100) / 100;

                destEl.dataset.gmAnimDur = durNum;
                destEl.dataset.gmAnimDel = String(itemDel);
                destEl.dataset.gmAnimLoop = loopVal;

                destEl.style.setProperty("--gm-anim-duration", durNum + "s");
                destEl.style.setProperty("--gm-anim-delay", itemDel + "s");
                destEl.style.animationIterationCount = loopVal;

                // Reflow agar animasi dimainkan ulang seketika di canvas
                destEl.classList.remove(targetAnim);
                void destEl.offsetWidth;
                destEl.classList.add(targetAnim);
            }

            // 5. Terapkan Teks Baru jika ada (hanya jika target tunggal)
            if (targetElements.length === 1 && patch && typeof patch.text === "string" && patch.text.trim() !== "") {
                const heading = destEl.querySelector("h1, h2, h3, h4, strong, span, a") || destEl;
                heading.textContent = patch.text.trim();
            }

            // Efek highlight seleksi halus sesaat
            const prevOutline = destEl.style.outline;
            destEl.style.outline = "2px solid #0078ff";
            setTimeout(() => {
                destEl.style.outline = prevOutline || "";
            }, 350 + (elIdx * 80));
        });

        if (typeof BroadcastChannel !== "undefined") {
            try {
                new BroadcastChannel("greymist-live-preview").postMessage({ type: "force-play-anim" });
            } catch (_) {}
        }

        // Update handler seleksi Greymist pada kartu pertama dari target
        const primaryDest = targetElements[0];
        document.querySelectorAll(".gm-object.gm-active").forEach(el => {
            if (el !== primaryDest) el.classList.remove("gm-active");
        });
        primaryDest.classList.add("gm-active");

        if (typeof window.GMObjectHandler !== "undefined" && typeof window.GMObjectHandler.attachTo === "function") {
            window.GMObjectHandler.attachTo(primaryDest);
        }

        refreshWorkspaceAfterChange();

        // Notifikasi ke object-animation.js & panel attribute Greymist agar UI panel bawah sinkron
        document.dispatchEvent(new CustomEvent("objectSelected", { detail: { target: primaryDest } }));
        primaryDest.dispatchEvent(new CustomEvent("gm-active-tick", { bubbles: true }));

        const summary = (patch && patch.summary) || (targetElements.length > 1 ? `${targetElements.length} elemen kartu saudara berhasil diperbarui.` : "Atribut elemen berhasil diperbarui.");
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
        temp.innerHTML = cleanEmojis((htmlString || "").trim());
        const sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });

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
            const isBothContainers = /^(NAV|HEADER|DIV|SECTION|ASIDE|FOOTER)$/.test(newRoot.tagName) && /^(NAV|HEADER|DIV|SECTION|ASIDE|FOOTER)$/.test(targetObj.tagName);
            if (newRoot.tagName === targetObj.tagName || isBothContainers) {
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
        targetObj.style.display = "none";
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
        } else {
            // Frame DOM core sudah ada (misal Main Frame awal yang digunakan ulang) -> sinkronkan judulnya
            if (frameData.frame_name) {
                const thumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${frameId}"]`);
                if (thumb) {
                    const nameEl = thumb.querySelector(".gm-frame-name");
                    if (nameEl) nameEl.textContent = frameData.frame_name;
                }
            }
        }

        if (typeof window.activateFrame === "function") {
            window.activateFrame(frameData.frame_id);
        }

        return core;
    }

    /**
     * Membangun frame baru halaman demi halaman, dan elemen demi elemen secara realtime dengan latensi sangat rendah.
     */
        /**
     * Menambahkan Breakpoint Responsif (Tablet / Mobile / Custom) ke Engine Breakpoint Greymist
     */
    function applyAiBreakpoints(breakpointsToAdd, statusEl) {
        if (!Array.isArray(breakpointsToAdd) || breakpointsToAdd.length === 0) return;
        if (!window.gmBreakpoints) window.gmBreakpoints = [];

        const projParams = new URLSearchParams(location.search);
        const projId = projParams.get("project_id") || "0";
        const savedBpKey = `gm_custom_breakpoints_${projId}`;

        let addedCount = 0;
        let addedNames = [];

        breakpointsToAdd.forEach(bp => {
            if (!bp || !bp.id) return;
            const exists = window.gmBreakpoints.some(b => b.id === bp.id);
            if (!exists) {
                const wVal = parseInt(bp.widthValue || 768);
                const type = bp.type || 'max';
                const rule = bp.rule || (type === 'min' ? `min-width: ${wVal}px` : `max-width: ${wVal}px`);
                const newBp = {
                    id: bp.id,
                    name: bp.name || bp.id,
                    rule: rule,
                    widthValue: wVal,
                    type: type
                };
                window.gmBreakpoints.push(newBp);
                addedNames.push(newBp.name + ` (${wVal}px)`);
                addedCount++;

                try {
                    const existingSaved = JSON.parse(localStorage.getItem(savedBpKey) || "[]");
                    existingSaved.push(newBp);
                    localStorage.setItem(savedBpKey, JSON.stringify(existingSaved));
                } catch (_) {}
            }
        });

        if (addedCount > 0 && typeof window.renderBreakpointUI === "function") {
            window.renderBreakpointUI();
            if (statusEl) {
                showStatus(statusEl, "info", `📱 Breakpoint baru (${addedNames.join(', ')}) ditambahkan ke toolbar.`);
            }
        }
    }

    function buildCreatedFramesSequentially(createdFrames, statusEl, onAllDone) {
        if (!Array.isArray(createdFrames) || createdFrames.length === 0) {
            if (typeof onAllDone === "function") onAllDone(0);
            return;
        }

        let fIdx = 0;

        function buildNextFrame() {
            if (fIdx >= createdFrames.length) {
                // Aktifkan kembali frame pertama di akhir agar kanvas siap dipakai
                if (createdFrames[0] && typeof window.activateFrame === "function") {
                    window.activateFrame(createdFrames[0].frame_id);
                }
                refreshWorkspaceAfterChange();
                if (typeof onAllDone === "function") onAllDone(createdFrames.length);
                return;
            }

            const frameInfo = createdFrames[fIdx];
            if (statusEl) {
                showStatus(
                    statusEl,
                    "info",
                    `⚡ Realtime (${fIdx + 1}/${createdFrames.length}): Merakit elemen di "${frameInfo.frame_name}"...`
                );
            }

            const newCore = ensureWorkspaceFrameMounted(frameInfo);
            if (!newCore) {
                fIdx++;
                buildNextFrame();
                return;
            }

            // Aktifkan frame yang sedang dirakit ke layar kanvas agar pengguna melihat prosesnya secara realtime
            if (typeof window.activateFrame === "function") {
                window.activateFrame(frameInfo.frame_id);
            }

            newCore.innerHTML = "";
            const temp = document.createElement("div");
            temp.innerHTML = cleanEmojis((frameInfo.core_content || "").trim());
            const sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });

            // Alirkan elemen per elemen dengan latensi sangat rendah (Ultra-Low Latency)
            buildElementsLiveUnstyledToStyled(sourceRoots, newCore, statusEl, () => {
                if (typeof window.initLoadedObjects === "function") {
                    window.initLoadedObjects(newCore);
                }
                refreshWorkspaceAfterChange(frameInfo.frame_id);
                fIdx++;
                setTimeout(buildNextFrame, 75);
            });
        }

        buildNextFrame();
    }

    function injectHtmlIntoCanvasProgressive(htmlString, targetMode, statusEl, callback) {
        const container = getTargetContainer(targetMode);
        if (!container) {
            alert("Frame atau Object Core tidak ditemukan. Buat atau pilih frame terlebih dahulu.");
            if (callback) callback(0);
            return;
        }

        const trimmed = cleanEmojis((htmlString || "").trim());
        if (trimmed === "") {
            if (callback) callback(0);
            return;
        }

        const temp = document.createElement("div");
        temp.innerHTML = trimmed;
        const sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });

        // Alirkan elemen per elemen secara realtime dengan latensi sangat rendah
        buildElementsLiveUnstyledToStyled(sourceRoots, container, statusEl, createdRoots => {
            if (Array.isArray(createdRoots)) {
                createdRoots.forEach(root => {
                    if (typeof window.initLoadedObjects === "function") {
                        window.initLoadedObjects(root);
                    }
                });
            }
            refreshWorkspaceAfterChange();
            if (callback) callback(Array.isArray(createdRoots) ? createdRoots.length : 0);
        });
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

        const trimmed = cleanEmojis((htmlString || "").trim());

        // Hanya jika respons kosong (pengguna eksplisit meminta "kosongkan frame" / "hapus semua")
        if (trimmed === "") {
            core.innerHTML = "";
            refreshWorkspaceAfterChange();
            if (callback) callback(true);
            return;
        }

        const temp = document.createElement("div");
        temp.innerHTML = trimmed;
        const sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });

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

        // Jika frame masih kosong, bangun elemen demi elemen secara realtime
        buildElementsLiveUnstyledToStyled(sourceRoots, core, statusEl, () => {
            if (typeof window.initLoadedObjects === "function") {
                window.initLoadedObjects(core);
            }
            refreshWorkspaceAfterChange();
            if (callback) callback(true);
        });
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
            const framesSnapshot = []; // Mode Kilat: tidak scrape seluruh frame saat edit komponen

            // Baca atribut dan computed style komponen terlebih dahulu
            const computed = window.getComputedStyle(targetObj);
            const selectedAttrs = {
                tag: targetObj.tagName.toLowerCase(),
                background: computed.backgroundColor,
                color: computed.color,
                fontFamily: computed.fontFamily,
                fontSize: computed.fontSize,
                padding: computed.padding,
                borderRadius: computed.borderRadius,
                anim: targetObj.dataset.gmAnim || "",
                animDur: targetObj.dataset.gmAnimDur || "",
                animDel: targetObj.dataset.gmAnimDel || "",
                animLoop: targetObj.dataset.gmAnimLoop || ""
            };

            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
            ctxStatus.style.display = "block";
            ctxStatus.style.color = "#2563eb";
            ctxStatus.textContent = "AI memproses modifikasi atribut objek...";

            fetch("api/ai_generate.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    project_id: projectId,
                    active_frame_id: getActiveFrameId(),
                    prompt: instruction,
                    model: selectedModel,
                    target_mode: "edit_selected",
                    read_memory: false,
                    save_to_library: false,
                    current_frame_html: currentFrameHtml,
                    selected_object_html: selectedObjectHtml,
                    selected_object_attrs: selectedAttrs,
                    frames_snapshot: framesSnapshot,
                    reference_image: attachedReferenceImage ? {
                        data: attachedReferenceImage.data,
                        mime_type: attachedReferenceImage.mime_type
                    } : null
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

                    if (res.renamed_frame && res.renamed_frame.frame_id) {
                        const renFid = String(res.renamed_frame.frame_id);
                        const renName = res.renamed_frame.new_name;
                        const thumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${renFid}"]`);
                        if (thumb && renName) {
                            const nameEl = thumb.querySelector(".gm-frame-name");
                            if (nameEl) nameEl.textContent = renName;
                        }
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

    /**
     * Membaca dan mengompres file gambar referensi (Multimodal Vision)
     * agar upload cepat dan hemat bandwidth tanpa mengurangi detail tata letak visual untuk AI.
     */
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
                border-radius: 5px;
                padding: 4px 6px;
            }
            .gm-ai-preview-thumb-wrap {
                display: flex;
                align-items: center;
                gap: 7px;
            }
            .gm-ai-preview-thumb-wrap img {
                width: 36px;
                height: 36px;
                object-fit: cover;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
            }
            .gm-ai-preview-info {
                flex: 1;
                min-width: 0;
                display: flex;
                flex-direction: column;
            }
            .gm-ai-preview-name {
                font-size: 11px;
                font-weight: 600;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .gm-ai-preview-size {
                font-size: 10px;
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
                <!-- Inspector Isi Frame Aktif -->
                <div class="gm-ai-frame-box">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;">
                        <div style="display:flex;align-items:center;gap:5px;font-size:10.5px;font-weight:600;color:#475569;">
                            <i class="fas fa-layer-group" style="color:#0078ff;"></i>
                            <span>Isi Frame (<span id="gm-ai-frame-count">0</span>)</span>
                        </div>
                        <span id="gm-ai-memory-badge" class="gm-ai-memory-pill" style="cursor:pointer;" title="Klik untuk mengaktifkan/menonaktifkan pembacaan memori proyek">⚡ Mode Kilat (Memori Off)</span>
                    </div>
                    <div class="gm-ai-frame-items" id="gm-ai-frame-items-list"></div>
                </div>

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

                <!-- Zona Upload Foto / Screenshot Referensi UI -->
                <div class="gm-ai-upload-zone" id="gm-ai-upload-zone">
                    <div class="gm-ai-upload-bar">
                        <button type="button" class="gm-ai-upload-btn" id="gm-ai-upload-btn" title="Upload foto atau screenshot referensi UI (PNG, JPG, WEBP)">
                            <i class="fa-solid fa-camera"></i> <span>Foto Referensi</span>
                        </button>
                        <input type="file" id="gm-ai-img-file" accept="image/png,image/jpeg,image/webp,image/gif" style="display:none;">
                        <span class="gm-ai-upload-hint"><i class="fa-solid fa-paste"></i> Paste Ctrl+V / Drop foto</span>
                    </div>
                    <div class="gm-ai-img-preview" id="gm-ai-img-preview" style="display:none;">
                        <div class="gm-ai-preview-thumb-wrap">
                            <img id="gm-ai-preview-img" src="" alt="Referensi UI">
                            <div class="gm-ai-preview-info">
                                <span id="gm-ai-preview-name" class="gm-ai-preview-name">ref.png</span>
                                <span id="gm-ai-preview-size" class="gm-ai-preview-size">120 KB</span>
                            </div>
                            <button type="button" class="gm-ai-remove-img-btn" id="gm-ai-remove-img-btn" title="Hapus foto referensi">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Prompt Field -->
                <textarea id="gm-ai-prompt-input" class="gm-ai-field" rows="2" placeholder="Ketik instruksi: buat komponen/page baru, edit, ubah nama project, atau buat UI dari foto..." style="resize:vertical;line-height:1.4;"></textarea>

                <!-- Model & Target -->
                <div class="gm-ai-row">
                    <select id="gm-ai-model-select" class="gm-ai-field" title="Pilih Model Aktif">
                        <option value="llama-3.3-70b-versatile">Llama 3.3 70B (Groq)</option>
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

        // Setup listener Upload Foto Referensi & Clipboard Paste
        let attachedReferenceImage = null;
        const uploadBtn = document.getElementById("gm-ai-upload-btn");
        const fileInput = document.getElementById("gm-ai-img-file");
        const previewBox = document.getElementById("gm-ai-img-preview");
        const previewImg = document.getElementById("gm-ai-preview-img");
        const previewName = document.getElementById("gm-ai-preview-name");
        const previewSize = document.getElementById("gm-ai-preview-size");
        const removeImgBtn = document.getElementById("gm-ai-remove-img-btn");
        const promptInputEl = document.getElementById("gm-ai-prompt-input");

        const setAttachedImage = (imgData) => {
            attachedReferenceImage = imgData;
            previewImg.src = imgData.dataUrl;
            previewName.textContent = imgData.name;
            previewSize.textContent = imgData.sizeStr;
            previewBox.style.display = "block";
            showStatus(statusEl, "info", `📸 Foto referensi "${imgData.name}" siap digunakan.`);
        };

        if (uploadBtn && fileInput) {
            uploadBtn.addEventListener("click", () => fileInput.click());
            fileInput.addEventListener("change", () => {
                const f = fileInput.files && fileInput.files[0];
                if (f) {
                    compressImageFile(f)
                        .then(setAttachedImage)
                        .catch(err => showStatus(statusEl, "error", err.message));
                }
            });
        }

        if (removeImgBtn) {
            removeImgBtn.addEventListener("click", () => {
                attachedReferenceImage = null;
                previewBox.style.display = "none";
                if (fileInput) fileInput.value = "";
                previewImg.src = "";
            });
        }

        // Paste clipboard support (Ctrl+V) langsung pada textarea prompt
        if (promptInputEl) {
            promptInputEl.addEventListener("paste", (e) => {
                const items = (e.clipboardData || window.clipboardData)?.items;
                if (!items) return;
                for (let i = 0; i < items.length; i++) {
                    if (items[i].type && items[i].type.startsWith("image/")) {
                        const blob = items[i].getAsFile();
                        if (blob) {
                            e.preventDefault();
                            compressImageFile(blob)
                                .then(res => {
                                    res.name = "Pasted-Screenshot.png";
                                    setAttachedImage(res);
                                })
                                .catch(err => showStatus(statusEl, "error", err.message));
                            break;
                        }
                    }
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
        panel.addEventListener("drop", (e) => {
            e.preventDefault();
            panel.classList.remove("gm-ai-drop-active");
            const files = e.dataTransfer?.files;
            if (files && files.length > 0 && files[0].type.startsWith("image/")) {
                compressImageFile(files[0])
                    .then(setAttachedImage)
                    .catch(err => showStatus(statusEl, "error", err.message));
            }
        });

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

        // Definisi Konfigurasi Lengkap Provider AI
        const AI_PROVIDER_CONFIG = {
            groq: {
                name: "Groq Cloud",
                badge: "Groq 70B",
                badgeBg: "#fef3c7",
                badgeColor: "#d97706",
                info: "Dijalankan 100% di Cloud LPU Groq. Super cepat (~300 t/s) & 0% beban laptop. Dapatkan API Key gratis di <a href='https://console.groq.com/keys' target='_blank' style='color:#0078ff;text-decoration:underline;'>console.groq.com/keys</a> (tanpa kartu kredit).",
                needsKey: true,
                keyPlaceholder: "gsk_...",
                models: [
                    { id: "llama-3.3-70b-versatile", name: "Llama 3.3 70B (Rekomendasi Cerdas)" },
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
        let currentActiveModel = "llama-3.3-70b-versatile";
        let providerKeys = { groq: "", openrouter: "", gemini: "" };
        let providerMasked = { groq: "", openrouter: "", gemini: "" };

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
            function populateModels(selectEl) {
                if (!selectEl) return;
                selectEl.innerHTML = "";
                conf.models.forEach(m => {
                    const opt = document.createElement("option");
                    opt.value = m.id;
                    opt.textContent = m.name;
                    selectEl.appendChild(opt);
                });
            }
            populateModels(settingsModelSelect);
            populateModels(mainModelSelect);

            if (conf.models.length > 0) {
                currentActiveModel = conf.models[0].id;
                if (settingsModelSelect) settingsModelSelect.value = currentActiveModel;
                if (mainModelSelect) mainModelSelect.value = currentActiveModel;
            }
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

        // Jalankan AI dengan Memori Proyek & Tanpa Pembatasan        // Jalankan AI dengan Memori Proyek & Tanpa Pembatasan
        const generateBtn = document.getElementById("gm-ai-generate-btn");
        generateBtn.addEventListener("click", () => {
            let prompt = document.getElementById("gm-ai-prompt-input").value.trim();
            const selectedModel = document.getElementById("gm-ai-model-select").value;
            const targetMode = document.getElementById("gm-ai-target-mode").value;
            const compName = document.getElementById("gm-ai-comp-name").value.trim() || "AI Component";
            const saveToLibrary = document.getElementById("gm-ai-save-library").checked;

            if (!prompt && attachedReferenceImage) {
                prompt = "Buat tampilan antarmuka (UI) website modern yang meniru tata letak (layout), hierarki visual, dan komponen dari foto referensi ini menggunakan komponen .gm-object Greymist.";
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

            if (selectedObj && (isAttributeEditPrompt || effectiveTargetMode === "selected" || effectiveTargetMode === "edit_selected")) {
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
            const readMemory = readMemoryEl ? readMemoryEl.checked : false;
            const projectId = getCurrentProjectId();
            const framesSnapshot = readMemory ? collectAllFramesSnapshot() : [];

            generateBtn.disabled = true;
            generateBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Proses...</span>`;
            if (readMemory) {
                showStatus(statusEl, "info", "🧠 AI membaca memori proyek & memproses konteks...");
            } else {
                showStatus(statusEl, "info", "⚡ AI memproses instruksi (Mode Kilat)...");
            }

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
                    reference_image: attachedReferenceImage ? {
                        data: attachedReferenceImage.data,
                        mime_type: attachedReferenceImage.mime_type
                    } : null
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
                        showStatus(statusEl, "success", `✓ ${res.summary || res.msg || "Nama proyek berhasil diubah."}`);
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
                                    core.innerHTML = (fu.html || "").trim();
                                    if (typeof window.initLoadedObjects === "function") window.initLoadedObjects(core);
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
