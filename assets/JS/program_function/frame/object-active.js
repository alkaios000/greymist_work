const framePreview = document.getElementById('gm-frame-preview');
let isObjectClicked = false;

// ===== Tracking element yang sedang di-edit =====
let currentEditingEl = null;

function disableEditing(el) {
    if (!el) return;
    el.contentEditable = 'false';
    el.removeAttribute('data-gm-editing');
    el.blur();
    currentEditingEl = null;
}

function enableEditing(el) {
    if (!el) return;
    // Nonaktifkan elemen sebelumnya jika beda
    if (currentEditingEl && currentEditingEl !== el) disableEditing(currentEditingEl);
    el.contentEditable = 'true';
    el.setAttribute('data-gm-editing', '1');
    currentEditingEl = el;
    // Fokus dan letakkan kursor di akhir teks
    el.focus();
    const range = document.createRange();
    const sel = window.getSelection();
    range.selectNodeContents(el);
    range.collapse(false);
    sel?.removeAllRanges();
    sel?.addRange(range);
}

// ===== Bersihkan contenteditable bawaan dari template =====
// Fungsi ini memastikan tidak ada object yang bisa diedit secara default
// kecuali setelah di-dblclick (yang akan menambahkan data-gm-editing)
function cleanContentEditable(rootNode) {
    if (!rootNode) return;
    
    // Jika root itu sendiri adalah gm-object dan bukan sedang diedit
    if (rootNode.nodeType === 1 && rootNode.classList.contains('gm-object')) {
        if (rootNode.getAttribute('contenteditable') === 'true' && !rootNode.hasAttribute('data-gm-editing')) {
            rootNode.setAttribute('contenteditable', 'false');
        }
    }

    // Cari di dalam child nodes
    if (rootNode.querySelectorAll) {
        rootNode.querySelectorAll('[contenteditable="true"]:not([data-gm-editing])').forEach(el => {
            el.setAttribute('contenteditable', 'false');
        });
    }
}

// Bersihkan awal
cleanContentEditable(document.body);

// Pantau setiap ada elemen baru yang masuk (misal dari drag n drop template)
const ceObserver = new MutationObserver((mutations) => {
    mutations.forEach(m => {
        m.addedNodes.forEach(node => {
            if (node.nodeType === 1) {
                cleanContentEditable(node);
            }
        });
    });
});
if (framePreview) {
    ceObserver.observe(framePreview, { childList: true, subtree: true });
}

// ===== Klik SATU kali = seleksi object saja =====
framePreview?.addEventListener('click', e => {
    if (document.body.classList.contains('gm-interaction-mode-on')) {
        const core = e.target.closest('.gm-object-core');
        if (core) {
            core.querySelectorAll('.gm-object').forEach(o => o.classList.remove('gm-active'));
            window.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: null } }));
            document.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: null } }));
        }
        return; 
    }

    const editingEl = e.target.closest('[data-gm-editing]');
    if (editingEl) return; // Sedang dalam mode edit, biarkan kursor berjalan normal

    const obj = e.target.closest('.gm-object');
    if (!obj) return;

    const core = obj.closest('.gm-object-core');
    if (!core) return;

    const sum = e.target.closest('summary');
    if (sum) {
        e.preventDefault();
        const details = sum.parentElement;
        if (details?.tagName === 'DETAILS') details.open = !details.open;
        return;
    }

    e.preventDefault();
    e.stopPropagation();

    // Jika ada elemen yang sedang diedit dan klik di luar, nonaktifkan dulu
    if (currentEditingEl && !currentEditingEl.contains(obj)) {
        disableEditing(currentEditingEl);
    }

    const objectsInCore = core.querySelectorAll('.gm-object');
    if (e.shiftKey) obj.classList.add('gm-active');
    else {
        objectsInCore.forEach(o => o.classList.remove('gm-active'));
        obj.classList.add('gm-active');
    }

    window.lastSelectedGmObject = obj;
    window.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: obj } }));
    document.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: obj } }));
    window.dispatchEvent(new CustomEvent('gm-active-tick', { detail: { target: obj } }));
    document.dispatchEvent(new CustomEvent('gm-active-tick', { detail: { target: obj } }));

    isObjectClicked = true;
    setTimeout(() => isObjectClicked = false, 0);
});

// ===== Klik DUA KALI = aktifkan mode teks langsung =====
framePreview?.addEventListener('dblclick', e => {
    if (document.body.classList.contains('gm-interaction-mode-on')) return; // Disable edit in interaction mode

    const obj = e.target.closest('.gm-object');
    if (!obj) return;
    const core = obj.closest('.gm-object-core');
    if (!core) return;

    e.preventDefault();
    e.stopPropagation();

    enableEditing(obj);
});

// ===== Klik di luar → matikan edit + hapus active =====
document.addEventListener('click', e => {
    const isHandler = e.target.closest('.gm-handler');
    const isLabel = e.target.closest('.gm-label');
    const scaling = GMObjectHandler.isScaling?.();
    const isEditing = e.target.closest('[data-gm-editing]');

    if (isObjectClicked || isHandler || isLabel || scaling || isEditing) return;

    const safeAreas = [
        '.gm-top-menu-right',
        '.gm-color-dropdown',
        '#colorMenuBtn',
        '#gmGradientModal',
        '.gm-object-menu',
        '.gm-object-info-modal',
        '.gm-handler',
        '.gm-label',
        '.gm-aside-left',
        '.gm-aside-right',
        '#gm-display-modal',
        '.gm-object-attribute',
        '.gm-token-editor-popup',
        '.gm-token-popup',
        '#gm-bp-modal',
        '#gm-save-comp-modal',
        '.gm-comp-modal-overlay',
        '.gm-comp-modal-container',
        '#gm-view-comp-modal',
        '.gm-view-comp-modal-overlay',
        '.gm-view-comp-container',
        '.gm-comp-card-view-btn',
        '.gm-thumb-quick-view',
        '.gm-interaction-toggle',
        '.gm-full-height-toggle',
        '#gm-breakpoint-toolbar',
        '.gm-breakpoint-toolbar'
    ];
    const clickedInSafeArea = safeAreas.some(sel => e.target.closest(sel));

    if (!clickedInSafeArea) {
        // Matikan mode edit
        if (currentEditingEl) disableEditing(currentEditingEl);
        document.querySelectorAll('.gm-object.gm-active').forEach(o => o.classList.remove('gm-active'));
        GMObjectHandler.detach();
        window.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: null } }));
        document.dispatchEvent(new CustomEvent('gm-selection-change', { detail: { target: null } }));
    }
});

// ===== Escape = keluar mode edit tanpa deselect =====
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && currentEditingEl) {
        disableEditing(currentEditingEl);
    }
});

// Helper
function getAllActiveObjects() {
    return Array.from(document.querySelectorAll('.gm-object-core .gm-object.gm-active'));
}
