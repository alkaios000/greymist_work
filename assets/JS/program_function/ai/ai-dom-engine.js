/**
 * File: assets/JS/program_function/ai/ai-dom-engine.js
 * Modul: Mesin Konstruksi DOM Real-Time (Ultra-Low Latency Unstyled-to-Styled),
 *        In-Place Attribute Patching, DOM Tree Diffing, Breakpoints, & Progressive Rendering.
 */
(function (window) {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};
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
        let sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });
        if (sourceRoots.length === 1 && (sourceRoots[0].id === "object-core" || sourceRoots[0].getAttribute("id") === "object-core")) {
            sourceRoots = Array.from(sourceRoots[0].children);
        }

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
        let sourceRoots = Array.from(temp.children).filter(el => { const t = el.tagName.toLowerCase(); return t !== "style" && t !== "script"; });
        if (sourceRoots.length === 1 && (sourceRoots[0].id === "object-core" || sourceRoots[0].getAttribute("id") === "object-core")) {
            sourceRoots = Array.from(sourceRoots[0].children);
        }

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



    const DOMEngine = {
        buildElementsLiveUnstyledToStyled,
        parseCssStyleMap,
        computeElementAttributePatch,
        collectDomTreePatchOperations,
        executeDomPatchOperations,
        findSpecificCards,
        resolveSpecificTargetElement,
        applyAttributePatchInPlace,
        normalizeGreymistAnimName,
        replaceSelectedObjectProgressive,
        ensureWorkspaceFrameMounted,
        applyAiBreakpoints,
        buildCreatedFramesSequentially,
        injectHtmlIntoCanvasProgressive,
        replaceFrameHtmlProgressive
    };

    window.GreymistAI.DOMEngine = DOMEngine;
    Object.assign(window, DOMEngine);

})(window);
