document.addEventListener("DOMContentLoaded", () => {

    // ==============================================================
    // MESIN RESOLUSI FRAME DINAMIS & CERDAS (DYNAMIC FRAME RESOLVER)
    // ==============================================================
    window.gmResolveFrame = function(targetIdentifier, linkEl = null) {
        const allThumbs = Array.from(document.querySelectorAll('.gm-frame-thumbnail'));
        let allFrames = allThumbs.map((t, idx) => {
            const fid = parseInt(t.dataset.frame) || 0;
            const nameEl = t.querySelector('.gm-frame-name');
            const fname = (nameEl ? nameEl.textContent : '').trim();
            return {
                id: fid,
                name: fname,
                lowerName: fname.toLowerCase(),
                index: idx,
                thumb: t,
                core: document.getElementById('object-core-' + fid)
            };
        });

        // Fallback jika thumbnail belum siap, cari langsung di core
        if (allFrames.length === 0) {
            const cores = Array.from(document.querySelectorAll('.gm-object-core'));
            allFrames = cores.map((c, idx) => {
                const fid = parseInt(c.dataset.frame) || 0;
                return {
                    id: fid,
                    name: `Frame #${fid}`,
                    lowerName: `frame #${fid}`,
                    index: idx,
                    thumb: null,
                    core: c
                };
            });
        }

        if (allFrames.length === 0) return null;

        // 1. Cek dari ID frame permanen pada linkEl (Paling Akurat & Kebal Rename)
        const frameIdFromData = linkEl ? parseInt(linkEl.dataset.actionFrameId || linkEl.dataset.actionTarget) : parseInt(targetIdentifier);
        if (frameIdFromData > 0) {
            const byId = allFrames.find(f => f.id === frameIdFromData);
            if (byId) return byId;
        }

        // 2. Kumpulkan kandidat kata kunci pencarian
        const candidates = [];
        if (targetIdentifier && typeof targetIdentifier === 'string') {
            candidates.push(targetIdentifier.trim());
        }
        if (linkEl) {
            if (linkEl.dataset.actionFrameName) candidates.push(linkEl.dataset.actionFrameName.trim());
            if (linkEl.dataset.actionTarget) candidates.push(linkEl.dataset.actionTarget.trim());
            const href = linkEl.getAttribute('href');
            if (href && href !== '#' && href.startsWith('#')) {
                candidates.push(href.replace(/^#/, '').trim());
            }
            const txt = linkEl.textContent.trim();
            if (txt) candidates.push(txt);
            if (linkEl.title) candidates.push(linkEl.title.trim());
        }

        // 3. Kamus Sinonim Cerdas (Bilingual: Indonesia & Inggris)
        const synonymMap = {
            'home': ['beranda', 'main frame', 'home', 'index', 'dashboard', 'awal', 'pertama', '1'],
            'beranda': ['beranda', 'home', 'main frame', 'index', 'dashboard', 'awal', 'pertama', '1'],
            'about': ['tentang', 'tentang kami', 'profil', 'profile', 'about', 'about us', 'company', 'kedua', '2'],
            'tentang': ['tentang', 'tentang kami', 'profil', 'profile', 'about', 'about us', 'company', 'kedua', '2'],
            'services': ['layanan', 'produk', 'fitur', 'service', 'services', 'products', 'features', 'ketiga', '3'],
            'layanan': ['layanan', 'produk', 'fitur', 'service', 'services', 'products', 'features', 'ketiga', '3'],
            'contact': ['kontak', 'hubungi', 'hubungi kami', 'contact', 'contact us', 'bantuan', 'support'],
            'kontak': ['kontak', 'hubungi', 'hubungi kami', 'contact', 'contact us', 'bantuan', 'support'],
            'pricing': ['harga', 'paket', 'biaya', 'pricing', 'plans', 'tarif'],
            'harga': ['harga', 'paket', 'biaya', 'pricing', 'plans', 'tarif'],
            'gallery': ['galeri', 'gallery', 'portfolio', 'portofolio', 'karya'],
            'galeri': ['galeri', 'gallery', 'portfolio', 'portofolio', 'karya']
        };

        for (let rawCand of candidates) {
            const cand = rawCand.toLowerCase().replace(/[^a-z0-9]/g, ' ').trim();
            if (!cand) continue;

            // a. Jika kandidat berupa angka murni
            if (/^\d+$/.test(rawCand)) {
                const num = parseInt(rawCand);
                const byId = allFrames.find(f => f.id === num);
                if (byId) return byId;
                if (num >= 1 && num <= allFrames.length) {
                    return allFrames[num - 1];
                }
            }

            // b. Pencocokan eksak nama frame
            const exact = allFrames.find(f => f.lowerName === cand || f.lowerName.replace(/[^a-z0-9]/g, ' ').trim() === cand);
            if (exact) {
                if (linkEl) linkEl.dataset.actionFrameId = exact.id;
                return exact;
            }

            // c. Pencocokan substring
            const sub = allFrames.find(f => f.lowerName.includes(cand) || cand.includes(f.lowerName));
            if (sub) {
                if (linkEl) linkEl.dataset.actionFrameId = sub.id;
                return sub;
            }

            // d. Pencocokan berdasarkan sinonim
            for (let [key, syns] of Object.entries(synonymMap)) {
                if (cand === key || syns.includes(cand) || syns.some(s => cand.includes(s))) {
                    const matchSyn = allFrames.find(f => {
                        return syns.some(s => f.lowerName.includes(s) || s.includes(f.lowerName));
                    });
                    if (matchSyn) {
                        if (linkEl) linkEl.dataset.actionFrameId = matchSyn.id;
                        return matchSyn;
                    }
                }
            }
        }

        return null;
    };

    // ==============================================================
    // EKSEKUSI AKSI OBJEK (RUN OBJECT ACTION)
    // ==============================================================
    window.runObjectAction = function(el) {
        if (!el) return;
        
        const type = el.dataset.actionType || (el.closest('.gm-navbar, nav, .gm-nav-links') ? 'goto' : null);
        if (!type) return;
        
        if (type === "goto") {
            const targetId = (el.dataset.actionTarget || el.dataset.actionFrameId || el.getAttribute("href") || el.textContent || "").trim();
            const frame = window.gmResolveFrame(targetId, el);
            if (frame) {
                // Permanenkan ID frame agar kebal terhadap rename frame
                el.dataset.actionFrameId = frame.id;
                el.dataset.actionType = "goto";
                el.dataset.actionTarget = frame.id;
                el.dataset.actionFrameName = frame.name;

                if (typeof window.activateFrame === "function") {
                    window.activateFrame(frame.id);
                } else if (frame.thumb) {
                    frame.thumb.click();
                }
                return;
            } else {
                console.warn("[runObjectAction] Tidak dapat menemukan frame untuk target:", targetId);
            }
        } else if (type === "scroll") {
            const rawTarget = el.dataset.actionScrollTarget || el.dataset.actionTarget || el.getAttribute("href") || "";
            const scrollId = rawTarget.replace(/^#/, "").trim();
            if (scrollId) {
                let scrollEl = document.getElementById(scrollId) || document.querySelector("." + scrollId) || document.querySelector(`[name="${scrollId}"]`);
                if (!scrollEl) {
                    const activeThumb = document.querySelector(".gm-frame-thumbnail.actived");
                    const activeFrameId = activeThumb ? activeThumb.dataset.frame : null;
                    const activeCore = (activeFrameId ? document.getElementById("object-core-" + activeFrameId) : null) || document.querySelector(".gm-object-core.actived") || document.querySelector(".gm-object-core");
                    if (activeCore) {
                        scrollEl = activeCore.querySelector("#" + scrollId) || activeCore.querySelector("." + scrollId) || activeCore.querySelector(`[name="${scrollId}"]`);
                    }
                }
                if (scrollEl) {
                    scrollEl.scrollIntoView({ behavior: "smooth", block: "start" });
                    scrollEl.style.transition = "outline 0.3s ease";
                    scrollEl.style.outline = "2px dashed #0078ff";
                    setTimeout(() => { scrollEl.style.outline = ""; }, 1200);
                }
            }
        } else if (type === "modal") {
            showModalPreview(el);
        }
    };

    function showModalPreview(el) {
        let existing = document.getElementById("gm-test-action-modal");
        if (existing) existing.remove();

        const title = el.dataset.actionModalTitle || "Modal Title";
        const content = el.dataset.actionModalContent || "Modal Content";
        const image = el.dataset.actionModalImage || "";
        const width = el.dataset.actionModalWidth || "400px";
        const bg = el.dataset.actionModalBg || "#ffffff";
        const color = el.dataset.actionModalColor || "#333333";

        const overlay = document.createElement("div");
        overlay.id = "gm-test-action-modal";
        Object.assign(overlay.style, {
            position: "fixed",
            top: "0", left: "0", width: "100vw", height: "100vh",
            backgroundColor: "rgba(0,0,0,0.5)",
            display: "flex", justifyContent: "center", alignItems: "center",
            zIndex: "999999"
        });

        const modal = document.createElement("div");
        Object.assign(modal.style, {
            width: width,
            backgroundColor: bg,
            color: color,
            padding: "20px",
            borderRadius: "8px",
            boxShadow: "0 4px 12px rgba(0,0,0,0.3)",
            fontFamily: "poppins, Arial, sans-serif"
        });

        const imageHtml = image ? `<img src="${image}" style="max-width: 100%; border-radius: 4px; margin-bottom: 15px; display: block;">` : "";

        modal.innerHTML = `
            <h2 style="margin-top:0; margin-bottom: 15px;">${title}</h2>
            ${imageHtml}
            <div style="margin-bottom: 20px; white-space: pre-wrap;">${content}</div>
            <div style="text-align: right;">
                <button class="gm-close-test-modal" style="padding: 8px 16px; cursor: pointer; border: none; border-radius: 4px; background: #0078ff; color: white;">Tutup</button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        overlay.querySelector(".gm-close-test-modal").addEventListener("click", () => {
            overlay.remove();
        });
    }

    // ==============================================================
    // EVENT LISTENER KLIK PADA LINK & OBJEK INTERAKTIF
    // ==============================================================
    document.addEventListener("click", (e) => {
        // Jangan picu navigasi jika pengguna sedang aktif mengetik/mengedit teks contenteditable
        if (e.target.isContentEditable || e.target.closest('[contenteditable="true"]')) {
            return;
        }

        // 1. Direct click untuk tombol navigasi slide PPT
        const slideNavBtn = e.target.closest(".gm-slide-nav-btn, .slide-navigation [data-action-type='goto']");
        if (slideNavBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.runObjectAction(slideNavBtn);
            return;
        }

        // 2. Direct click untuk Link Navigasi Navbar & Menu Halaman
        const navLink = e.target.closest('.gm-navbar a, .gm-nav-links a, .gm-nav-link, nav a, .gm-navbar [data-action-type="goto"]');
        if (navLink) {
            // Jika link memiliki target frame atau aksi goto, jalankan navigasi langsung
            e.preventDefault();
            e.stopPropagation();
            window.runObjectAction(navLink);
            return;
        }

        // 3. Link navigasi scroll (data-action-type='scroll' atau a[href^='#'])
        const scrollBtn = e.target.closest("[data-action-type='scroll'], a[href^='#'], .nav-link");
        if (scrollBtn) {
            const rawHref = scrollBtn.getAttribute("href") || "";
            const isInternalHash = rawHref.startsWith("#") && rawHref.length > 1;
            const hasScrollAction = scrollBtn.dataset.actionType === "scroll";

            if (isInternalHash || hasScrollAction) {
                e.preventDefault();
                e.stopPropagation();
                if (hasScrollAction) {
                    window.runObjectAction(scrollBtn);
                } else {
                    const targetKey = rawHref.substring(1).trim();
                    if (targetKey) {
                        let targetEl = document.getElementById(targetKey) || document.querySelector("." + targetKey) || document.querySelector(`[name="${targetKey}"]`);
                        if (!targetEl) {
                            const activeThumb = document.querySelector(".gm-frame-thumbnail.actived");
                            const activeFrameId = activeThumb ? activeThumb.dataset.frame : null;
                            const activeCore = (activeFrameId ? document.getElementById("object-core-" + activeFrameId) : null) || document.querySelector(".gm-object-core.actived") || document.querySelector(".gm-object-core");
                            if (activeCore) {
                                targetEl = activeCore.querySelector("#" + targetKey) || activeCore.querySelector("." + targetKey) || activeCore.querySelector(`[name="${targetKey}"]`);
                            }
                        }
                        if (targetEl) {
                            targetEl.scrollIntoView({ behavior: "smooth", block: "start" });
                            targetEl.style.transition = "outline 0.3s ease";
                            targetEl.style.outline = "2px dashed #0078ff";
                            setTimeout(() => { targetEl.style.outline = ""; }, 1200);
                        }
                    }
                }
                return;
            }
        }

        // 4. Untuk objek umum: Alt + Click atau mode interaksi
        const isInteractionMode = document.body.classList.contains('gm-interaction-mode-on') || document.body.classList.contains('gm-fullscreen-preview');
        if (e.altKey || isInteractionMode) {
            const obj = e.target.closest("[data-action-type]");
            if (obj) {
                e.preventDefault();
                e.stopPropagation();
                window.runObjectAction(obj);
            }
        }
    }, true); // Capture phase agar menangkap event sebelum terblokir

    // Navigasi Keyboard Presentasi (Panah Kanan = Next Slide, Panah Kiri = Prev Slide)
    document.addEventListener("keydown", (e) => {
        const activeTag = (document.activeElement?.tagName || "").toLowerCase();
        if (activeTag === "input" || activeTag === "textarea" || document.activeElement?.isContentEditable) {
            return;
        }

        if (e.key === "ArrowRight" || e.key === "ArrowLeft") {
            const allThumbs = Array.from(document.querySelectorAll('.gm-frame-thumbnail'));
            if (allThumbs.length <= 1) return;

            const activeIdx = allThumbs.findIndex(t => t.classList.contains('actived') || t.classList.contains('active'));
            if (activeIdx !== -1) {
                if (e.key === "ArrowRight" && activeIdx < allThumbs.length - 1) {
                    e.preventDefault();
                    allThumbs[activeIdx + 1].click();
                } else if (e.key === "ArrowLeft" && activeIdx > 0) {
                    e.preventDefault();
                    allThumbs[activeIdx - 1].click();
                }
            }
        }
    });
});