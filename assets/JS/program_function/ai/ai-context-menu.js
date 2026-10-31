/**
 * File: assets/JS/program_function/ai/ai-context-menu.js
 * Modul: Integrasi Prompt AI pada Menu Klik Kanan (.gm-object-menu) untuk Objek Aktif (.gm-active).
 */
(function (window) {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};

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
                const getSelectedObjFn = typeof getSelectedObject === "function" ? getSelectedObject : (window.getSelectedObject || (() => null));
                const activeObj = getSelectedObjFn();
                if (activeObj && tagBadge) {
                    tagBadge.textContent = `<${activeObj.tagName.toLowerCase()}>`;
                }
                if (ctxStatus) {
                    ctxStatus.style.display = "none";
                }
            }, 20);
        });

        if (submitBtn) {
            submitBtn.addEventListener("click", executeContextAiEdit);
        }
        if (promptInput) {
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
    }

    /**
     * Menjalankan instruksi prompt pengeditan objek aktif melalui API Greymist
     */
    function executeContextAiEdit() {
        const promptInput = document.getElementById("gm-ai-ctx-prompt");
        const submitBtn = document.getElementById("gm-ai-ctx-submit");
        const ctxStatus = document.getElementById("gm-ai-ctx-status");

        const getSelectedObjFn = typeof getSelectedObject === "function" ? getSelectedObject : (window.getSelectedObject || (() => null));
        const targetObj = getSelectedObjFn();
        if (!targetObj) {
            if (ctxStatus) {
                ctxStatus.style.display = "block";
                ctxStatus.style.color = "#dc2626";
                ctxStatus.textContent = "Tidak ada objek aktif yang dipilih.";
            }
            return;
        }

        const instruction = promptInput ? promptInput.value.trim() : "";
        if (!instruction) {
            if (promptInput) promptInput.focus();
            return;
        }

        const selectedModelEl = document.getElementById("gm-ai-model-select");
        const selectedModel = selectedModelEl ? selectedModelEl.value : "gemini-3.5-flash-lite";
        const getActiveCoreFn = typeof getActiveObjectCore === "function" ? getActiveObjectCore : (window.getActiveObjectCore || (() => null));
        const activeCore = getActiveCoreFn();
        const currentFrameHtml = activeCore ? activeCore.innerHTML : "";
        const selectedObjectHtml = targetObj.outerHTML;
        const getProjIdFn = typeof getCurrentProjectId === "function" ? getCurrentProjectId : (window.getCurrentProjectId || (() => 0));
        const projectId = getProjIdFn();
        const getActiveFrameIdFn = typeof getActiveFrameId === "function" ? getActiveFrameId : (window.getActiveFrameId || (() => ""));
        const activeFrameId = getActiveFrameIdFn();
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

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i>`;
        }
        if (ctxStatus) {
            ctxStatus.style.display = "block";
            ctxStatus.style.color = "#2563eb";
            ctxStatus.textContent = "AI memproses modifikasi atribut objek...";
        }

        fetch("api/ai_generate.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                project_id: projectId,
                active_frame_id: activeFrameId,
                prompt: instruction,
                model: selectedModel,
                target_mode: "edit_selected",
                read_memory: false,
                save_to_library: false,
                current_frame_html: currentFrameHtml,
                selected_object_html: selectedObjectHtml,
                selected_object_attrs: selectedAttrs,
                frames_snapshot: framesSnapshot,
                reference_images: (typeof attachedReferenceImages !== "undefined" && Array.isArray(attachedReferenceImages)) ? attachedReferenceImages.map(img => ({
                    data: img.data,
                    mime_type: img.mime_type,
                    name: img.name
                })) : [],
                reference_image: (typeof attachedReferenceImages !== "undefined" && Array.isArray(attachedReferenceImages) && attachedReferenceImages.length > 0) ? {
                    data: attachedReferenceImages[0].data,
                    mime_type: attachedReferenceImages[0].mime_type
                } : null
            })
        })
            .then(r => r.json())
            .then(res => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fas fa-bolt"></i>`;
                }

                if (res.status !== "OK") {
                    if (ctxStatus) {
                        ctxStatus.style.color = "#dc2626";
                        ctxStatus.textContent = res.msg || "Gagal memodifikasi objek.";
                    }
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
                    if (typeof window.cachedAiMemory !== "undefined") window.cachedAiMemory = res.ai_memory;
                    try {
                        localStorage.setItem("gm_ai_memory_proj_" + projectId, JSON.stringify(res.ai_memory));
                    } catch (_) {}
                    if (typeof updateMemoryBadgeUI === "function") updateMemoryBadgeUI(res.ai_memory);
                    else if (typeof window.updateMemoryBadgeUI === "function") window.updateMemoryBadgeUI(res.ai_memory);
                }

                if (promptInput) promptInput.value = "";
                if (typeof window.GMCloseContextMenu === "function") {
                    window.GMCloseContextMenu(true);
                }

                const mainStatusEl = document.getElementById("gm-ai-status-msg");
                const showStatusFn = typeof showStatus === "function" ? showStatus : (window.showStatus || (() => {}));

                // Jika AI mengembalikan instruksi perubahan atribut (tanpa buat elemen baru dari 0)
                if (res.operation === "patch_attributes" && res.patch) {
                    const applyPatchFn = typeof applyAttributePatchInPlace === "function" ? applyAttributePatchInPlace : (window.applyAttributePatchInPlace || null);
                    if (applyPatchFn) {
                        applyPatchFn(targetObj, res.patch, mainStatusEl, () => {
                            if (mainStatusEl) {
                                showStatusFn(mainStatusEl, "success", "✓ " + (res.patch.summary || "Objek aktif berhasil diperbarui."));
                            }
                            if (typeof syncProjectAiMemory === "function") syncProjectAiMemory();
                            else if (typeof window.syncProjectAiMemory === "function") window.syncProjectAiMemory();
                        });
                    }
                    return;
                }

                const replaceObjFn = typeof replaceSelectedObjectProgressive === "function" ? replaceSelectedObjectProgressive : (window.replaceSelectedObjectProgressive || null);
                if (replaceObjFn) {
                    replaceObjFn(targetObj, res.html || "", mainStatusEl, () => {
                        if (mainStatusEl) {
                            showStatusFn(mainStatusEl, "success", "✓ Objek aktif berhasil diperbarui.");
                        }
                    });
                }
            })
            .catch(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<i class="fas fa-bolt"></i>`;
                }
                if (ctxStatus) {
                    ctxStatus.style.color = "#dc2626";
                    ctxStatus.textContent = "Gagal menghubungi endpoint AI.";
                }
            });
    }

    const ContextMenu = {
        setupRightClickAiObjectEditor,
        executeContextAiEdit
    };

    window.GreymistAI.ContextMenu = ContextMenu;
    Object.assign(window, ContextMenu);
    window.setupRightClickAiObjectEditor = setupRightClickAiObjectEditor;
    window.executeContextAiEdit = executeContextAiEdit;

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupRightClickAiObjectEditor);
    } else {
        setupRightClickAiObjectEditor();
    }

})(window);
