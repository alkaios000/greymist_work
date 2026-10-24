// ============================================================
// GREYMIST DOM TREE & LAYER INSPECTOR (PRO ENGINE)
// ============================================================

const treeRoot = document.getElementById("tree");
let searchInput = document.getElementById("gm-dom-search") || document.querySelector(".gm-dom-search");

// ============ HELPERS ============
function getActiveCore() {
    return document.querySelector(".gm-object-core.actived") || 
           document.querySelector(".gm-object-core") || 
           document.getElementById("gm-frame-preview")?.querySelector(".gm-object-core");
}

function newId() {
    return "node-" + Math.random().toString(36).substr(2, 9);
}

// Icon mapper by HTML tag
function getTagIconClass(tagName, hasChild, isCollapsed) {
    if (hasChild) {
        return isCollapsed ? "fa-solid fa-folder" : "fa-solid fa-folder-open";
    }
    const tag = tagName.toUpperCase();
    switch (tag) {
        case "DIV":
            return "fa-regular fa-square";
        case "BUTTON":
            return "fa-solid fa-square-check";
        case "A":
            return "fa-solid fa-link";
        case "IMG":
        case "PICTURE":
        case "SVG":
            return "fa-regular fa-image";
        case "H1":
        case "H2":
        case "H3":
        case "H4":
        case "H5":
        case "H6":
            return "fa-solid fa-heading";
        case "P":
        case "SPAN":
            return "fa-solid fa-font";
        case "HEADER":
        case "FOOTER":
        case "NAV":
            return "fa-solid fa-bars-staggered";
        case "SECTION":
        case "ARTICLE":
        case "MAIN":
        case "ASIDE":
            return "fa-solid fa-cubes";
        case "INPUT":
        case "TEXTAREA":
        case "SELECT":
        case "FORM":
            return "fa-regular fa-keyboard";
        case "UL":
        case "OL":
        case "LI":
            return "fa-solid fa-list-ul";
        case "I":
            return "fa-solid fa-icons";
        default:
            return "fa-solid fa-code";
    }
}

// ============ TREE RENDER ============
function renderTree(filter = "") {
    if (!treeRoot) return;
    treeRoot.innerHTML = "";

    const core = getActiveCore();
    if (!core) {
        updateNodeCount(0);
        return;
    }

    searchInput = document.getElementById("gm-dom-search") || document.querySelector(".gm-dom-search");
    if (!filter && searchInput) {
        filter = searchInput.value.trim();
    }

    function matchFilter(el) {
        if (!filter) return true;
        const f = filter.toLowerCase();
        return (
            el.tagName.toLowerCase().includes(f) ||
            (el.id && el.id.toLowerCase().includes(f)) ||
            ([...el.classList].join(" ").toLowerCase().includes(f))
        );
    }

    let renderedNodesCount = 0;

    function build(el, container, depth = 0) {
        if (!el.classList.contains("gm-object")) return;

        const isMatch = matchFilter(el);
        // Jika parent tidak cocok tetapi memiliki child yang cocok, tetap tampilkan
        const hasMatchingDescendant = filter ? el.querySelector(filter.toLowerCase()) || 
            [...el.querySelectorAll(".gm-object")].some(d => matchFilter(d)) : true;

        if (filter && !isMatch && !hasMatchingDescendant) return;

        renderedNodesCount++;

        const nodeDiv = document.createElement("div");
        nodeDiv.className = "tree-node";
        nodeDiv.dataset.ref = el.dataset.id || (el.dataset.id = newId());
        nodeDiv.style.paddingLeft = (depth * 14 + 6) + "px";

        // COLLAPSE TOGGLE
        const hasChild = el.querySelector(":scope > .gm-object");
        const isCollapsed = el.classList.contains("collapsed");

        if (hasChild) {
            const toggle = document.createElement("i");
            toggle.className = isCollapsed
                ? "fa-solid fa-caret-right"
                : "fa-solid fa-caret-down";
            toggle.style.marginRight = "6px";
            toggle.style.fontSize = "13px";
            toggle.style.color = "#94a3b8";
            toggle.style.width = "10px";
            toggle.style.cursor = "pointer";
            toggle.style.transition = "transform 0.15s ease";
            toggle.onclick = (e) => {
                e.stopPropagation();
                el.classList.toggle("collapsed");
                renderTree(filter);
            };
            nodeDiv.appendChild(toggle);
        } else {
            const spacer = document.createElement("span");
            spacer.style.display = "inline-block";
            spacer.style.width = "16px";
            nodeDiv.appendChild(spacer);
        }

        // ICON BY TAG TYPE
        const icon = document.createElement("i");
        icon.className = getTagIconClass(el.tagName, hasChild, isCollapsed);
        icon.style.marginRight = "4px";
        icon.style.fontSize = "12px";
        icon.style.color = hasChild ? "#64748b" : "#94a3b8";
        nodeDiv.appendChild(icon);

        // TAG LABEL
        const label = document.createElement("span");
        label.className = "tree-tag-name";
        label.textContent = el.tagName.toLowerCase();
        nodeDiv.appendChild(label);

        // ID BADGE
        if (el.id) {
            const idSpan = document.createElement("span");
            idSpan.textContent = `#${el.id}`;
            idSpan.className = "tree-id";
            nodeDiv.appendChild(idSpan);
        }
        
        // CLASS BADGE (Filter sistem class)
        const systemClasses = ["gm-object", "gm-active", "hover-core", "collapsed", "drag-preview", "dropping", "actived"];
        const visibleClasses = [...el.classList].filter(c => 
            !systemClasses.includes(c) && 
            !c.startsWith("gm-") && 
            !c.startsWith("fa-")
        );
        
        if (visibleClasses.length > 0) {
            const clsSpan = document.createElement("span");
            clsSpan.textContent = `.${visibleClasses[0]}` + (visibleClasses.length > 1 ? ` +${visibleClasses.length - 1}` : "");
            clsSpan.title = visibleClasses.map(c => `.${c}`).join(" ");
            clsSpan.className = "tree-class";
            nodeDiv.appendChild(clsSpan);
        }

        // SINKRON AKTIF
        if (el.classList.contains("gm-active")) {
            nodeDiv.classList.add("active-node");
        }

        nodeDiv._gmElement = el;

        // LEFT-CLICK: Aktifkan objek di demon core seketika
        nodeDiv.onclick = (e) => {
            e.stopPropagation();
            activateObjectFromTree(el, false);
        };

        // RIGHT-CLICK: Aktifkan objek di demon core dan buka context menu
        nodeDiv.oncontextmenu = (e) => {
            e.preventDefault();
            e.stopPropagation();
            activateObjectFromTree(el, true);

            if (typeof window.GMOpenContextMenu === "function") {
                window.GMOpenContextMenu(el, e.clientX, e.clientY);
            }
        };

        container.appendChild(nodeDiv);

        // REKURSIF CHILDREN JIKA TIDAK COLLAPSED
        if (!isCollapsed || (filter && hasMatchingDescendant)) {
            [...el.children].forEach(child => build(child, container, depth + 1));
        }
    }

    [...core.children].forEach(child => build(child, treeRoot));

    updateNodeCount(renderedNodesCount);

    const emptyState = document.getElementById("gm-dom-empty-state");
    if (emptyState) {
        emptyState.style.display = renderedNodesCount === 0 ? "flex" : "none";
    }
}

function updateNodeCount(count) {
    const badge = document.getElementById("gm-dom-node-count");
    if (badge) badge.textContent = `${count} nodes`;
}

// ============ ACTIVE HANDLER (CORE & TREE DUAL-SYNC) ============

function activateObjectFromTree(el, skipScrollTree = false) {
    if (!el) return;

    // Pastikan core parent aktif jika ada multi-frame
    const core = el.closest(".gm-object-core") || getActiveCore();
    if (core && !core.classList.contains("actived")) {
        document.querySelectorAll(".gm-object-core").forEach(c => c.classList.remove("actived"));
        core.classList.add("actived");
        if (core.dataset.frame) {
            document.querySelectorAll(".gm-frame-thumbnail").forEach(f => {
                f.classList.toggle("actived", f.dataset.frame === core.dataset.frame);
            });
        }
    }

    // Bersihkan gm-active pada objek lain di core
    if (core) {
        core.querySelectorAll(".gm-object.gm-active").forEach(o => {
            if (o !== el) o.classList.remove("gm-active");
        });
    } else {
        document.querySelectorAll(".gm-object.gm-active").forEach(o => {
            if (o !== el) o.classList.remove("gm-active");
        });
    }

    // Tandai target sebagai gm-active di canvas
    el.classList.add("gm-active");

    // Simpan referensi global untuk kompatibilitas modul (seperti bg-color.js)
    window.lastSelectedGmObject = el;

    // Pasang transform handler (resize / border handles)
    const handler = window.GMObjectHandler || (typeof GMObjectHandler !== "undefined" ? GMObjectHandler : null);
    if (handler && typeof handler.attachTo === "function") {
        handler.attachTo(el);
    }

    // Pastikan objek di Demon Core tampak pada canvas (scroll canvas jika berada di luar viewport)
    try {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    } catch (err) {}

    // Sinkronkan highlight dan breadcrumb di DOM Tree
    syncActiveToTree(el, skipScrollTree);

    // Beri tahu modul inspektor properti (background, typography, border, display, dll)
    const detail = { target: el };
    document.dispatchEvent(new CustomEvent('gm-selection-change', { detail }));
    window.dispatchEvent(new CustomEvent('gm-selection-change', { detail }));
    document.dispatchEvent(new CustomEvent('gm-active-tick', { detail }));
    window.dispatchEvent(new CustomEvent('gm-active-tick', { detail }));
}

function setActive(el) {
    activateObjectFromTree(el, false);
}

// Memastikan semua .gm-object punya id
function ensureIds(core) {
    if (!core) return;
    const seen = new Set();
    core.querySelectorAll(".gm-object[data-id]").forEach(el => {
        seen.add(el.dataset.id);
    });

    core.querySelectorAll(".gm-object").forEach(el => {
        if (!el.dataset.id) {
            let id;
            do { id = newId(); } while (seen.has(id));
            el.dataset.id = id;
            seen.add(id);
        }
    });
}

function normalizeAddedNodeIds(core, addedNodeList) {
    if (!core) return;
    const used = new Set(Array.from(core.querySelectorAll(".gm-object[data-id]")).map(el => el.dataset.id));

    if (coreObserver) coreObserver.disconnect();

    try {
        addedNodeList.forEach(added => {
            const candidates = [];
            if (added.nodeType === 1) {
                if (added.classList && added.classList.contains("gm-object")) candidates.push(added);
                added.querySelectorAll && added.querySelectorAll('.gm-object').forEach(d => candidates.push(d));
            }
            candidates.forEach(el => {
                if (!el.dataset.id) {
                    let id;
                    do { id = newId(); } while (used.has(id));
                    el.dataset.id = id;
                    used.add(id);
                } else {
                    const count = core.querySelectorAll(`.gm-object[data-id="${el.dataset.id}"]`).length;
                    if (count > 1) {
                        let id;
                        do { id = newId(); } while (used.has(id));
                        el.dataset.id = id;
                        used.add(id);
                    } else {
                        used.add(el.dataset.id);
                    }
                }
            });
        });
    } finally {
        observeActiveCore();
    }
}

function ensureVisibleInTree(el) {
    syncActiveToTree(el);
}

// Core mutation observer
let coreObserver = null;
function observeActiveCore() {
    if (coreObserver) coreObserver.disconnect();

    const core = getActiveCore();
    if (!core) return;

    ensureIds(core);
    coreObserver = new MutationObserver((mutations) => {
        const added = [];
        let shouldRender = false;
        for (const m of mutations) {
            if (m.type === 'childList' && m.addedNodes.length) {
                m.addedNodes.forEach(n => added.push(n));
                shouldRender = true;
            }
            if (m.type === 'attributes') {
                if (m.attributeName === 'id') {
                    shouldRender = true;
                } else if (m.attributeName === 'class') {
                    const target = m.target;
                    const oldStr = (m.oldValue || "").replace(/gm-active|gm-object-hover|hover-core/g, "").replace(/\s+/g, " ").trim();
                    const newStr = (target.className || "").replace(/gm-active|gm-object-hover|hover-core/g, "").replace(/\s+/g, " ").trim();
                    
                    if (oldStr !== newStr) {
                        shouldRender = true;
                    } else if (target && target.classList && (target.classList.contains('gm-active') || m.oldValue?.includes('gm-active'))) {
                        if (target.classList.contains('gm-active')) {
                            syncActiveToTree(target);
                        } else {
                            syncActiveToTree(null);
                        }
                    }
                }
            }
        }

        if (added.length) {
            normalizeAddedNodeIds(core, added);
            renderTree(searchInput?.value.trim() || "");
            return;
        }

        if (shouldRender) {
            renderTree(searchInput?.value.trim() || "");
        }
    });

    coreObserver.observe(core, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeOldValue: true,
        attributeFilter: ['class', 'data-id', 'id']
    });
}

// ============ SCROLL TO EXACT CENTER IN DOM PANEL ============
function scrollToCenterInPanel(node) {
    if (!node) return;

    function executeScroll() {
        const container = document.getElementById("gm-DOM-cont") || 
                          document.querySelector(".gm-DOM-cont");

        if (container) {
            const containerRect = container.getBoundingClientRect();
            const nodeRect = node.getBoundingClientRect();

            // Offset vertikal aktual node relatif terhadap kontainer scroll
            const currentScroll = container.scrollTop;
            const nodeTopRelative = (nodeRect.top - containerRect.top) + currentScroll;
            
            // Posisi tengah: node tepat berada di tengah ketinggian kontainer
            const targetScroll = nodeTopRelative - (container.clientHeight / 2) + (nodeRect.height / 2);

            container.scrollTo({
                top: Math.max(0, targetScroll),
                behavior: "smooth"
            });
        }

        // Native scrollIntoView sebagai pelengkap
        try {
            node.scrollIntoView({
                behavior: "smooth",
                block: "center",
                inline: "nearest"
            });
        } catch (e) {}
    }

    // Jalankan segera dan ulangi bertahap agar stabil setelah reflow layout
    executeScroll();
    requestAnimationFrame(executeScroll);
    setTimeout(executeScroll, 50);
    setTimeout(executeScroll, 160);
}

// ============ UPDATE BREADCRUMB STATUS BAR ============
function updateBreadcrumbs(el) {
    const bar = document.getElementById("gm-dom-breadcrumbs");
    if (!bar) return;

    if (!el) {
        bar.innerHTML = '<span class="gm-bc-item gm-bc-empty">Belum ada objek aktif</span>';
        return;
    }

    const core = getActiveCore();
    const chain = [];
    let curr = el;
    while (curr && curr !== core && curr.classList && curr.classList.contains("gm-object")) {
        chain.unshift(curr);
        curr = curr.parentElement;
    }

    if (chain.length === 0) {
        bar.innerHTML = '<span class="gm-bc-item gm-bc-empty">Root Core</span>';
        return;
    }

    bar.innerHTML = "";
    chain.forEach((item, idx) => {
        if (idx > 0) {
            const sep = document.createElement("span");
            sep.className = "gm-bc-sep";
            sep.innerHTML = '<i class="fa-solid fa-angle-right"></i>';
            bar.appendChild(sep);
        }

        const span = document.createElement("span");
        span.className = "gm-bc-item" + (idx === chain.length - 1 ? " is-active" : "");
        let name = item.tagName.toLowerCase();
        if (item.id) name += `#${item.id}`;
        span.textContent = name;
        span.title = `Pilih elemen <${item.tagName.toLowerCase()}> (Klik kanan untuk opsi)`;
        span.onclick = (e) => {
            e.stopPropagation();
            activateObjectFromTree(item, false);
        };
        span.oncontextmenu = (e) => {
            e.preventDefault();
            e.stopPropagation();
            activateObjectFromTree(item, true);
            if (typeof window.GMOpenContextMenu === "function") {
                window.GMOpenContextMenu(item, e.clientX, e.clientY);
            }
        };
        bar.appendChild(span);
    });

    bar.scrollLeft = bar.scrollWidth;
}

// ============ SINKRONISASI OBJEK AKTIF KE DOM TREE ============
function syncActiveToTree(targetEl, skipScrollTree = false) {
    const core = (targetEl && targetEl.closest(".gm-object-core")) || getActiveCore();
    if (!core || !treeRoot) return;

    // Jika targetEl tidak dikirimkan, cari objek yang memiliki gm-active di canvas
    if (!targetEl) {
        targetEl = core.querySelector(".gm-object.gm-active") || 
                   document.querySelector("#gm-frame-preview .gm-object.gm-active") ||
                   document.querySelector(".gm-object.gm-active");
    }

    // Jika memang tidak ada objek aktif sama sekali, bersihkan highlight di tree & breadcrumb
    if (!targetEl) {
        treeRoot.querySelectorAll(".tree-node.active-node").forEach(n => {
            n.classList.remove("active-node", "node-flash");
        });
        updateBreadcrumbs(null);
        return;
    }

    // Pastikan objek memiliki dataset.id unik
    if (!targetEl.dataset.id) {
        ensureIds(core);
    }

    // Jika pencarian aktif menyembunyikan objek ini, reset pencarian agar objek tampak
    const searchInputEl = document.getElementById("gm-dom-search") || searchInput;
    if (searchInputEl && searchInputEl.value.trim() !== "") {
        searchInputEl.value = "";
        const clearBtn = document.getElementById("gm-dom-search-clear");
        if (clearBtn) clearBtn.style.display = "none";
    }

    // Periksa dan buka semua parent yang berstatus collapsed di core
    let hasCollapsedAncestor = false;
    let parent = targetEl.parentElement;
    while (parent && parent !== core && parent.classList) {
        if (parent.classList.contains("collapsed")) {
            parent.classList.remove("collapsed");
            hasCollapsedAncestor = true;
        }
        parent = parent.parentElement;
    }

    let id = targetEl.dataset.id;
    let node = id ? treeRoot.querySelector(`.tree-node[data-ref='${id}']`) : null;

    // Jika ada parent yang dibuka atau node belum dirender di tree, render ulang tree
    if (hasCollapsedAncestor || !node) {
        renderTree(searchInputEl ? searchInputEl.value.trim() : "");
        if (!targetEl.dataset.id) ensureIds(core);
        id = targetEl.dataset.id;
        node = id ? treeRoot.querySelector(`.tree-node[data-ref='${id}']`) : null;
    }

    // Hapus active-node dari node lain di tree
    treeRoot.querySelectorAll(".tree-node.active-node").forEach(n => {
        if (n !== node) n.classList.remove("active-node", "node-flash");
    });

    if (node) {
        node.classList.add("active-node");

        // Efek animasi highlight / pulse agar langsung tampak menonjol
        node.classList.remove("node-flash");
        void node.offsetWidth; // trigger reflow
        node.classList.add("node-flash");

        // Auto-scroll paksa ke tengah kontainer DOM tree jika tidak diskip
        if (!skipScrollTree) {
            scrollToCenterInPanel(node);
        }
    }

    // Update breadcrumb path
    updateBreadcrumbs(targetEl);
}

// Alias backwards-compatibility
function renderActiveSync(target) {
    syncActiveToTree(target);
}

// ===== EVENT LISTENERS SINKRONISASI =====

// 1. Capture-phase click listener pada document
document.addEventListener("click", (e) => {
    const clicked = e.target.closest(".gm-object");
    if (!clicked) return;

    const core = clicked.closest(".gm-object-core");
    if (!core) return;

    setTimeout(() => {
        if (clicked.classList.contains("gm-active")) {
            syncActiveToTree(clicked);
        }
    }, 10);
}, true);

// 2. Custom event ketika seleksi objek berubah
document.addEventListener("gm-selection-change", (e) => {
    const target = e.detail?.target;
    syncActiveToTree(target);
});
window.addEventListener("gm-selection-change", (e) => {
    const target = e.detail?.target;
    syncActiveToTree(target);
});

// 3. Custom event ketika tab DOM di sidebar kiri dibuka kembali
document.addEventListener("gm-dom-tab-activated", () => {
    setTimeout(() => {
        syncActiveToTree();
    }, 50);
});
window.addEventListener("gm-dom-tab-activated", () => {
    setTimeout(() => {
        syncActiveToTree();
    }, 50);
});

// 4. MutationObserver global di #gm-frame-preview untuk mendeteksi kapanpun .gm-active berpindah
function setupGlobalPreviewObserver() {
    const preview = document.getElementById("gm-frame-preview");
    if (!preview) return;

    const obs = new MutationObserver((mutations) => {
        for (const m of mutations) {
            if (m.type === "attributes" && m.attributeName === "class") {
                const target = m.target;
                if (target && target.classList && target.classList.contains("gm-object")) {
                    if (target.classList.contains("gm-active")) {
                        syncActiveToTree(target);
                    }
                }
            } else if (m.type === "childList" && m.addedNodes.length) {
                for (const node of m.addedNodes) {
                    if (node.nodeType === 1 && (node.classList?.contains("gm-object-core") || node.querySelector?.(".gm-object-core"))) {
                        observeActiveCore();
                        renderTree();
                    }
                }
            }
        }
    });

    obs.observe(preview, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ["class"]
    });
}

// 5. Setup UI Toolbar Handlers
function setupDOMToolbar() {
    // Expand All
    document.getElementById("gm-dom-expand-all")?.addEventListener("click", () => {
        const core = getActiveCore();
        if (!core) return;
        core.querySelectorAll(".gm-object.collapsed").forEach(el => el.classList.remove("collapsed"));
        renderTree(searchInput ? searchInput.value.trim() : "");
        syncActiveToTree();
    });

    // Collapse All
    document.getElementById("gm-dom-collapse-all")?.addEventListener("click", () => {
        const core = getActiveCore();
        if (!core) return;
        const activeObj = core.querySelector(".gm-object.gm-active");
        
        const activeParents = new Set();
        let p = activeObj?.parentElement;
        while (p && p !== core) {
            activeParents.add(p);
            p = p.parentElement;
        }

        core.querySelectorAll(".gm-object").forEach(el => {
            if (el.querySelector(":scope > .gm-object")) {
                if (!activeParents.has(el)) {
                    el.classList.add("collapsed");
                }
            }
        });
        renderTree(searchInput ? searchInput.value.trim() : "");
        syncActiveToTree();
    });

    // Locate Active
    document.getElementById("gm-dom-locate-active")?.addEventListener("click", () => {
        syncActiveToTree();
    });

    // Search Box & Clear
    const searchBox = document.getElementById("gm-dom-search");
    const clearBtn = document.getElementById("gm-dom-search-clear");
    if (searchBox) {
        searchBox.addEventListener("input", () => {
            const val = searchBox.value.trim();
            if (clearBtn) clearBtn.style.display = val ? "flex" : "none";
            renderTree(val);
        });
    }
    if (clearBtn && searchBox) {
        clearBtn.addEventListener("click", () => {
            searchBox.value = "";
            clearBtn.style.display = "none";
            renderTree("");
            syncActiveToTree();
        });
    }

    // Klik kanan pada area kosong kontainer DOM tree -> buka menu konteks global
    const domCont = document.getElementById("gm-DOM-cont");
    if (domCont) {
        domCont.addEventListener("contextmenu", (e) => {
            if (e.target.closest(".tree-node")) return;
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.GMOpenContextMenu === "function") {
                window.GMOpenContextMenu(null, e.clientX, e.clientY);
            }
        });
    }
}

// ============ KLIK FRAME THUMBNAIL ============
document.addEventListener("click", (e) => {
    const frame = e.target.closest(".gm-frame-thumbnail");
    if (!frame) return;
    document.querySelectorAll(".gm-frame-thumbnail").forEach(f => f.classList.remove("actived"));
    frame.classList.add("actived");

    document.querySelectorAll(".gm-object-core").forEach(c => c.classList.remove("actived"));
    const core = document.querySelector(`.gm-object-core[data-frame="${frame.dataset.frame}"]`);
    if (core) core.classList.add("actived");

    observeActiveCore();
    renderTree(searchInput?.value.trim() || "");
    setTimeout(() => {
        syncActiveToTree();
    }, 50);
});

// ============ INIT ============
function initDOMTreeModule() {
    setupGlobalPreviewObserver();
    setupDOMToolbar();
    observeActiveCore();
    renderTree();
    setTimeout(() => {
        syncActiveToTree();
    }, 100);
    setTimeout(() => {
        syncActiveToTree();
    }, 500);
}

if (document.readyState === "loading") {
    window.addEventListener("DOMContentLoaded", initDOMTreeModule);
} else {
    initDOMTreeModule();
}

// --- HOVER SYNC CORE <-> TREE ---
function syncHover(el) {
    if (document.body.classList.contains('gm-interaction-mode-on')) {
        el = null;
    }

    document.querySelectorAll(".hover-core, .hover-node").forEach(h => {
        h.classList.remove("hover-core", "hover-node");
    });

    if (!el) return;

    if (el.classList.contains("tree-node")) {
        const id = el.dataset.ref;
        const coreObj = getActiveCore()?.querySelector(`.gm-object[data-id='${id}']`);
        if (coreObj) coreObj.classList.add("hover-core");
        el.classList.add("hover-node");
    } else if (el.classList.contains("gm-object")) {
        const id = el.dataset.id;
        const treeNode = treeRoot ? treeRoot.querySelector(`.tree-node[data-ref='${id}']`) : null;
        if (treeNode) treeNode.classList.add("hover-node");
        el.classList.add("hover-core");
    }
}

// Core Hover
document.addEventListener("mouseover", (e) => {
    const core = getActiveCore();
    if (!core) return;
    const hovered = e.target.closest(".gm-object");
    if (hovered && core.contains(hovered)) {
        syncHover(hovered);
    }
});
document.addEventListener("mouseout", (e) => {
    const core = getActiveCore();
    if (!core) return;
    const hovered = e.target.closest(".gm-object");
    if (hovered && core.contains(hovered)) {
        syncHover(null);
    }
});

// Tree Hover
if (treeRoot) {
    treeRoot.addEventListener("mouseover", (e) => {
        const hovered = e.target.closest(".tree-node");
        if (hovered) syncHover(hovered);
    });
    treeRoot.addEventListener("mouseout", (e) => {
        const hovered = e.target.closest(".tree-node");
        if (hovered) syncHover(null);
    });
}
