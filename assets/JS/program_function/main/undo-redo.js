(function () {

  /* ===============================
     CONFIG
  ================================ */
  const CORE_SELECTOR = '.gm-object-core';
  const ACTIVE_CLASS = 'actived';
  const PREVIEW_SELECTOR = '.gm-drag-preview,[data-preview],.gm-live-preview';

  const UNDO_BTN_SELECTOR = '#gm-undo';
  const REDO_BTN_SELECTOR = '#gm-redo';

  /* ===============================
     STATE
  ================================ */
  const histories = new Map();
  let isRestoring = false;
  let debounceTimer = null;

  /* ===============================
     CORE UTILS
  ================================ */
  function getActiveCore() {
    return document.querySelector(`${CORE_SELECTOR}.${ACTIVE_CLASS}`);
  }

  function getCoreId(core) {
    return core.dataset.frame || core.id;
  }

  function getHistory(coreId) {
    if (!histories.has(coreId)) {
      histories.set(coreId, { stack: [], index: -1 });
    }
    return histories.get(coreId);
  }

  /* ===============================
     SNAPSHOT TANPA PREVIEW (KUNCI 🔑)
  ================================ */
  function snapshotWithoutPreview(core) {
    const clone = core.cloneNode(true);
    // Hapus elemen preview
    clone.querySelectorAll(PREVIEW_SELECTOR).forEach(el => el.remove());
    
    // Hapus status active dan status hover/drag agar tidak terekam dalam history
    clone.querySelectorAll('.gm-active').forEach(el => el.classList.remove('gm-active'));
    clone.querySelectorAll('.gm-drop-hover').forEach(el => el.classList.remove('gm-drop-hover'));
    clone.querySelectorAll('[data-hover]').forEach(el => el.removeAttribute('data-hover'));
    
    return clone.innerHTML;
  }

  function saveState(core) {
    if (isRestoring || !core) return;

    const history = getHistory(getCoreId(core));
    const html = snapshotWithoutPreview(core);

    if (history.stack[history.index] === html) return;

    // Jika membuat perubahan setelah undo, potong sisa stack masa depan
    history.stack = history.stack.slice(0, history.index + 1);

    history.stack.push(html);
    
    const MAX_HISTORY = 30;
    if (history.stack.length > MAX_HISTORY) {
      history.stack.shift();
      // index tidak ditambah karena elemen tergeser ke kiri
    } else {
      history.index++;
    }
  }

  /* ===============================
     RESTORE
  ================================ */
  function restore(core, html) {
    isRestoring = true;
    core.innerHTML = html;

    requestAnimationFrame(() => {
      isRestoring = false;
    });
  }

  /* ===============================
     UNDO / REDO
  ================================ */
  function undo() {
    const core = getActiveCore();
    if (!core) return;

    const history = getHistory(getCoreId(core));
    if (history.index <= 0) return;

    history.index--;
    restore(core, history.stack[history.index]);
  }

  function redo() {
    const core = getActiveCore();
    if (!core) return;

    const history = getHistory(getCoreId(core));
    if (history.index >= history.stack.length - 1) return;

    history.index++;
    restore(core, history.stack[history.index]);
  }

  /* ===============================
     OBSERVER
  ================================ */
  const observer = new MutationObserver(() => {
    const core = getActiveCore();
    if (!core || isRestoring) return;

    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      saveState(core);
    }, 120);
  });

  function observeCore(core) {
    observer.disconnect();
    observer.observe(core, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ['style', 'data-hover']
    });

    const history = getHistory(getCoreId(core));
    if (history.stack.length === 0) {
      saveState(core);
    }
  }

  /* ===============================
     CORE SWITCH
  ================================ */
  document.addEventListener('click', () => {
    const core = getActiveCore();
    if (core) observeCore(core);
  });

  /* ===============================
     KEYBOARD SHORTCUT
  ================================ */
  document.addEventListener('keydown', e => {

    if (e.ctrlKey && !e.shiftKey && e.key.toLowerCase() === 'z') {
      e.preventDefault();
      undo();
    }

    if (e.ctrlKey && e.key.toLowerCase() === 'y') {
      e.preventDefault();
      redo();
    }

    if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'z') {
      e.preventDefault();
      redo();
    }

  });

  /* ===============================
     BUTTON INTEGRATION (INI YANG DIMINTA)
  ================================ */
  document.addEventListener('click', e => {

    const undoBtn = e.target.closest(UNDO_BTN_SELECTOR);
    if (undoBtn) {
      e.preventDefault();
      undo();
      return;
    }

    const redoBtn = e.target.closest(REDO_BTN_SELECTOR);
    if (redoBtn) {
      e.preventDefault();
      redo();
      return;
    }

  });

  /* ===============================
     PUBLIC
  ================================ */
  window.GM_UNDO = { 
    undo, 
    redo,
    saveState: function(core) {
      const targetCore = core || getActiveCore();
      if (targetCore) saveState(targetCore);
    }
  };

})();