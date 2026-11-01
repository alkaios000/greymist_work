// ============================================================
// GREYMIST OBJECT DELETION ENGINE & POPUP HANDLER
// ============================================================

// Listener Delete key (aman terhadap input & text editing)
document.addEventListener("keydown", e => {
    if (e.target && (
        e.target.closest("input, textarea, select") || 
        e.target.isContentEditable || 
        e.target.hasAttribute("data-gm-editing")
    )) {
        return;
    }

    if (e.key === "Delete") {
        deleteActiveObjects();
    }
});

// Listener tombol delete dari menu / context menu
document.querySelectorAll(".gm-delete-object").forEach(btn => {
    btn.addEventListener("click", () => deleteActiveObjects());
});

/**
 * Hapus objek aktif. Jika ada elemen yang memiliki child,
 * tampilkan modal konfirmasi minimalis terlebih dahulu.
 */
function deleteActiveObjects() {
    const activeObjects = Array.from(document.querySelectorAll(".gm-object.gm-active"));
    if (activeObjects.length === 0) return;

    const withChildren = activeObjects.filter(obj => obj.querySelector(".gm-object"));
    const withoutChildren = activeObjects.filter(obj => !obj.querySelector(".gm-object"));

    if (withChildren.length > 0) {
        showDeletePopup(withChildren, withoutChildren);
    } else {
        withoutChildren.forEach(obj => obj.remove());
        if (typeof renderTree === "function") renderTree();
    }
}

/**
 * Tampilkan modal konfirmasi delete object
 * Mendukung pembatalan (Escape, click overlay, tombol batal, tombol X),
 * Hapus wadah saja (unwrap child), atau Hapus semua beserta child.
 */
function showDeletePopup(objects, withoutChildren = []) {
    const overlay = document.querySelector(".gm-popup-overlay");
    if (!overlay) return;

    // Hitung total child & info tag
    const badgeEl = overlay.querySelector(".gm-popup-child-badge");
    let totalChildren = 0;
    objects.forEach(obj => {
        totalChildren += obj.querySelectorAll(".gm-object").length;
    });
    if (badgeEl) {
        badgeEl.textContent = `${totalChildren} child`;
    }

    const infoObjEl = overlay.querySelector(".gm-popup-obj-info");
    if (infoObjEl) {
        if (objects.length === 1) {
            const tagName = objects[0].tagName.toLowerCase();
            const id = objects[0].id ? `#${objects[0].id}` : "";
            const customClass = Array.from(objects[0].classList || [])
                .filter(c => c !== "gm-object" && c !== "gm-active")
                .slice(0, 1)
                .map(c => `.${c}`)
                .join("");
            infoObjEl.textContent = `<${tagName}${id}${customClass}>`;
        } else {
            infoObjEl.textContent = `${objects.length} objek`;
        }
    }

    // Fungsi tutup modal (Cancel / selesai)
    const closePopup = () => {
        overlay.classList.remove("gm-active");
        setTimeout(() => {
            overlay.style.display = "none";
        }, 150);
        document.removeEventListener("keydown", onModalKeyDown);
    };

    const onModalKeyDown = (e) => {
        if (e.key === "Escape") {
            e.preventDefault();
            closePopup();
        }
    };

    document.addEventListener("keydown", onModalKeyDown);

    // Klik overlay luar untuk menutup modal
    overlay.onclick = (e) => {
        if (e.target === overlay) {
            closePopup();
        }
    };

    // Tombol close (X)
    const closeBtn = overlay.querySelector(".gm-popup-close");
    if (closeBtn) {
        closeBtn.onclick = (e) => {
            e.preventDefault();
            closePopup();
        };
    }

    // Tombol Batal
    const cancelBtn = overlay.querySelector(".gm-popup-cancel");
    if (cancelBtn) {
        cancelBtn.onclick = (e) => {
            e.preventDefault();
            closePopup();
        };
    }

    // Opsi 1: Delete this only (Unwrap wadah, pertahankan child dinaikkan ke parent)
    const deleteThisBtn = overlay.querySelector(".gm-delete-this");
    if (deleteThisBtn) {
        deleteThisBtn.onclick = () => {
            if (Array.isArray(withoutChildren)) {
                withoutChildren.forEach(obj => obj.remove());
            }
            objects.forEach(obj => {
                const parent = obj.parentElement;
                if (parent) {
                    const children = Array.from(obj.children);
                    children.forEach(child => parent.insertBefore(child, obj));
                }
                obj.remove();
            });
            closePopup();
            if (typeof renderTree === "function") renderTree();
        };
    }

    // Opsi 2: Delete all including child (Hapus wadah beserta seluruh child)
    const deleteAllBtn = overlay.querySelector(".gm-delete-all");
    if (deleteAllBtn) {
        deleteAllBtn.onclick = () => {
            if (Array.isArray(withoutChildren)) {
                withoutChildren.forEach(obj => obj.remove());
            }
            objects.forEach(obj => obj.remove());
            closePopup();
            if (typeof renderTree === "function") renderTree();
        };
    }

    // Buka popup dengan transisi smooth
    overlay.style.display = "flex";
    requestAnimationFrame(() => {
        overlay.classList.add("gm-active");
    });
}

// Ekspos ke global window untuk kompatibilitas menu actions & shortcut lain
window.deleteActiveObjects = deleteActiveObjects;
window.showDeletePopup = showDeletePopup;
