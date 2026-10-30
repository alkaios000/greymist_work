document.addEventListener("DOMContentLoaded", () => {

    window.runObjectAction = function(el) {
        if (!el || !el.dataset.actionType) return;
        
        const type = el.dataset.actionType;
        
        if (type === "goto") {
            const targetId = (el.dataset.actionTarget || "").trim();
            if (targetId) {
                // 1. Cari berdasarkan frame_id numerik
                let thumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${targetId}"]`);
                // 2. Jika bukan ID numerik, cari berdasarkan nama frame (case-insensitive)
                if (!thumb) {
                    const cleanTarget = targetId.toLowerCase();
                    const allThumbs = Array.from(document.querySelectorAll('.gm-frame-thumbnail'));
                    thumb = allThumbs.find(t => {
                        const nameEl = t.querySelector('.gm-frame-name');
                        const fName = (nameEl ? nameEl.textContent : '').trim().toLowerCase();
                        return fName === cleanTarget || fName.includes(cleanTarget) || cleanTarget.includes(fName);
                    });
                }
                if (thumb) {
                    thumb.click();
                } else if (typeof window.activateFrame === "function") {
                    window.activateFrame(targetId);
                }
            }
        } else if (type === "scroll") {
            const scrollId = el.dataset.actionScrollTarget || el.dataset.actionTarget;
            if(scrollId) {
                // Gunakan class atau id dari preview jika dibutuhkan, atau query di document
                // Tapi ini dijalankan di Main Editor
                let scrollEl = document.getElementById(scrollId) || document.querySelector("." + scrollId);
                // Jika tidak ketemu di global, cari di dalam active core
                if (!scrollEl) {
                    const activeCore = document.querySelector(".gm-object-core.actived");
                    if (activeCore) {
                        scrollEl = activeCore.querySelector("#" + scrollId) || activeCore.querySelector("." + scrollId);
                    }
                }
                if (scrollEl) {
                    scrollEl.scrollIntoView({ behavior: "smooth", block: "start" });
                }
            }
        } else if (type === "modal") {
            showModalPreview(el);
        }
    };

    function showModalPreview(el) {
        // Hapus modal lama jika ada
        let existing = document.getElementById("gm-test-action-modal");
        if(existing) existing.remove();

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

    // Intercept click: direct click untuk tombol slide nav, atau Alt + Click / mode interaksi untuk objek lain
    document.addEventListener("click", (e) => {
        // 1. Jika tombol navigasi slide PPT (.gm-slide-nav-btn atau dalam .slide-navigation), aktifkan langsung tanpa perlu Alt
        const slideNavBtn = e.target.closest(".gm-slide-nav-btn, .slide-navigation [data-action-type='goto']");
        if (slideNavBtn) {
            e.preventDefault();
            e.stopPropagation();
            window.runObjectAction(slideNavBtn);
            return;
        }

        // 2. Untuk objek umum: Alt + Click atau mode interaksi
        const isInteractionMode = document.body.classList.contains('gm-interaction-mode-on');
        if (e.altKey || isInteractionMode) {
            const obj = e.target.closest("[data-action-type]");
            if (obj) {
                e.preventDefault();
                e.stopPropagation();
                window.runObjectAction(obj);
            }
        }
    }, true); // Capture phase agar menangkap event lebih dulu

    // Navigasi Keyboard Presentasi (Panah Kanan = Next Slide, Panah Kiri = Prev Slide)
    document.addEventListener("keydown", (e) => {
        // Jangan aktifkan jika pengguna sedang mengetik teks di input/textarea/editor teks
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
