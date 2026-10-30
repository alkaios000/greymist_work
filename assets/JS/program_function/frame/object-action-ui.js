(function () {
    let actionType, gotoUI, gotoTarget, scrollUI, scrollTarget, modalUI, modalTitle, modalContent, modalImage, modalWidth, modalBg, modalColor, testModalBtn;
    let lastActiveObj = null;

    function getActiveObject() {
        const core = document.querySelector('.gm-object-core.actived');
        if (!core) return null;
        return core.querySelector('.gm-object.gm-active');
    }

    function populateFrames() {
        if (!gotoTarget) return;
        const frames = document.querySelectorAll(".gm-frame-thumbnail");
        gotoTarget.innerHTML = "";
        frames.forEach(frame => {
            const id = frame.dataset.frame;
            const name = frame.querySelector(".gm-frame-name")?.textContent || "Frame " + id;
            const opt = document.createElement("option");
            opt.value = id;
            opt.textContent = name;
            gotoTarget.appendChild(opt);
        });
    }

    function updateVisibility(type) {
        if (!gotoUI || !modalUI || !scrollUI) return;
        gotoUI.style.display = type === "goto" ? "block" : "none";
        scrollUI.style.display = type === "scroll" ? "block" : "none";
        modalUI.style.display = type === "modal" ? "flex" : "none";
    }

    function loadFromActive() {
        const activeObj = getActiveObject();
        
        if (activeObj !== lastActiveObj) {
            lastActiveObj = activeObj;

            if (activeObj) {
                const type = activeObj.dataset.actionType || "";
                if(actionType) actionType.value = type;
                updateVisibility(type);

                if (type === "goto") {
                    populateFrames();
                    if (gotoTarget) {
                        const targetVal = (activeObj.dataset.actionTarget || "").trim();
                        gotoTarget.value = targetVal;
                        // Jika targetVal adalah nama frame, cocokkan dengan option text
                        if (!gotoTarget.value && targetVal) {
                            const matchOpt = Array.from(gotoTarget.options).find(o =>
                                o.textContent.trim().toLowerCase() === targetVal.toLowerCase() ||
                                o.textContent.trim().toLowerCase().includes(targetVal.toLowerCase())
                            );
                            if (matchOpt) {
                                gotoTarget.value = matchOpt.value;
                                activeObj.dataset.actionTarget = matchOpt.value;
                            }
                        }
                    }
                } else if (type === "scroll") {
                    if(scrollTarget) scrollTarget.value = activeObj.dataset.actionScrollTarget || activeObj.dataset.actionTarget || "";
                } else if (type === "modal") {
                    if(modalTitle) modalTitle.value = activeObj.dataset.actionModalTitle || "";
                    if(modalContent) modalContent.value = activeObj.dataset.actionModalContent || "";
                    if(modalImage) modalImage.value = activeObj.dataset.actionModalImage || "";
                    if(modalWidth) modalWidth.value = activeObj.dataset.actionModalWidth || "400px";
                    if(modalBg) modalBg.value = activeObj.dataset.actionModalBg || "#ffffff";
                    if(modalColor) modalColor.value = activeObj.dataset.actionModalColor || "#333333";
                }
            } else {
                if(actionType) actionType.value = "";
                updateVisibility("");
            }
        }
        // requestAnimationFrame removed for centralized polling
    }

    function saveToActive(e) {
        // Prevent writing if the user hasn't selected anything yet
        const activeObj = getActiveObject();
        if (!activeObj || activeObj !== lastActiveObj) return;

        const type = actionType ? actionType.value : "";
        if (!type) {
            delete activeObj.dataset.actionType;
        } else {
            activeObj.dataset.actionType = type;
            if (type === "goto" && gotoTarget) {
                activeObj.dataset.actionTarget = gotoTarget.value;
            } else if (type === "scroll" && scrollTarget) {
                activeObj.dataset.actionScrollTarget = scrollTarget.value;
                activeObj.dataset.actionTarget = scrollTarget.value;
            } else if (type === "modal") {
                if(modalTitle) activeObj.dataset.actionModalTitle = modalTitle.value;
                if(modalContent) activeObj.dataset.actionModalContent = modalContent.value;
                if(modalImage) activeObj.dataset.actionModalImage = modalImage.value;
                if(modalWidth) activeObj.dataset.actionModalWidth = modalWidth.value;
                if(modalBg) activeObj.dataset.actionModalBg = modalBg.value;
                if(modalColor) activeObj.dataset.actionModalColor = modalColor.value;
            }
        }
    }

    function initActionPanel() {
        actionType = document.getElementById("gm-action-type");
        gotoUI = document.getElementById("gm-action-goto-ui");
        gotoTarget = document.getElementById("gm-action-goto-target");
        
        scrollUI = document.getElementById("gm-action-scroll-ui");
        scrollTarget = document.getElementById("gm-action-scroll-target");

        modalUI = document.getElementById("gm-action-modal-ui");
        modalTitle = document.getElementById("gm-action-modal-title");
        modalContent = document.getElementById("gm-action-modal-content");
        modalImage = document.getElementById("gm-action-modal-image");
        modalWidth = document.getElementById("gm-action-modal-width");
        modalBg = document.getElementById("gm-action-modal-bg");
        modalColor = document.getElementById("gm-action-modal-color");
        testModalBtn = document.getElementById("gm-action-test-modal");

        if (!actionType) return;

        actionType.addEventListener("change", (e) => {
            updateVisibility(e.target.value);
            if(e.target.value === "goto") populateFrames();
            saveToActive();
        });
        
        [gotoTarget, scrollTarget, modalTitle, modalContent, modalImage, modalWidth, modalBg, modalColor].forEach(el => {
            if (el) {
                el.addEventListener("input", saveToActive);
                el.addEventListener("change", saveToActive);
            }
        });

        if (testModalBtn) {
            testModalBtn.addEventListener("click", (e) => {
                e.preventDefault();
                const activeObj = getActiveObject();
                if(activeObj && typeof window.runObjectAction === "function") {
                    window.runObjectAction(activeObj);
                }
            });
        }

        // Start polling via central event
        window.addEventListener('gm-active-tick', loadFromActive);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initActionPanel);
    } else {
        initActionPanel();
    }
})();
