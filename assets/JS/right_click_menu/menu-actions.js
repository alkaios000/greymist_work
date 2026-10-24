document.addEventListener("click", e => {
    const activeObjects = document.querySelectorAll(".gm-object.gm-active");

    // ===== DELETE =====
    if (e.target.closest(".gm-delete-object")) {
        // Panggil fungsi delete yang sudah ada
        deleteActiveObjects();
        return;
    }

    // ===== COPY OBJECT =====
    if (e.target.closest(".gm-copy-object")) {
        GMMenuActions.copyObject(activeObjects);
        return;
    }

    // ===== PASTE OBJECT =====
    if (e.target.closest(".gm-paste-object")) {
        GMMenuActions.pasteObject(activeObjects);
        return;
    }

    // ===== SAVE AS COMPONENT =====
    if (e.target.closest(".gm-save-component")) {
        if (window.GMComponentManager && typeof window.GMComponentManager.openSaveModal === "function") {
            window.GMComponentManager.openSaveModal();
        }
        return;
    }

    // ===== COPY STYLE =====
    if (e.target.closest(".gm-copy-style")) {
        if (activeObjects.length === 1) {
            GMStyleUX.copy(activeObjects[0]);
        }
        return;
    }

    // ===== PASTE STYLE =====
    if (e.target.closest(".gm-paste-style")) {
        GMStyleUX.paste(activeObjects);
        return;
    }

    // ===== EDIT / Nested Menu Actions =====
    const menuItem = e.target.closest(".gm-menu-item");
    if (menuItem) {
        if (menuItem.classList.contains("gm-select-parent")) {
            GMMenuActions.selectParent(activeObjects);
        } else if (menuItem.classList.contains("gm-add-child")) {
            GMMenuActions.addChild(activeObjects);
        } else if (menuItem.classList.contains("gm-change-object")) {
            GMMenuActions.changeObject(activeObjects);
        }
    }
});
