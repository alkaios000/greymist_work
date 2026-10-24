(function () {

  /* ===============================
     INTERNAL CLIPBOARD (SAFE)
  =============================== */
  let gmClipboard = null;

  /* ===============================
     SET SINGLE GM-ACTIVE
  =============================== */
  function setGmActive(target) {
    document.querySelectorAll('.gm-object.gm-active')
      .forEach(el => el.classList.remove('gm-active'));

    if (target) target.classList.add('gm-active');
  }

  /* ===============================
     FIND REPLACEMENT ACTIVE
  =============================== */
  function findReplacementActive(el) {
    if (!el) return null;

    let next = el.nextElementSibling;
    while (next) {
      if (next.classList.contains('gm-object')) return next;
      next = next.nextElementSibling;
    }

    let prev = el.previousElementSibling;
    while (prev) {
      if (prev.classList.contains('gm-object')) return prev;
      prev = prev.previousElementSibling;
    }

    let parent = el.parentElement;
    while (parent && parent !== document.body) {
      if (parent.classList.contains('gm-object')) return parent;
      parent = parent.parentElement;
    }

    return null;
  }

  /* ===============================
     COPY
  =============================== */
  function copyActiveObject() {
    const active = document.querySelector('.gm-object.gm-active');
    if (!active) return;

    const clone = active.cloneNode(true);
    clone.classList.remove('gm-active');
    clone.querySelectorAll('.gm-active')
      .forEach(el => el.classList.remove('gm-active'));

    gmClipboard = {
      node: clone,
      mode: 'copy'
    };

    console.log('[GM] COPY');
  }

  /* ===============================
     CUT
  =============================== */
  function cutActiveObject() {
    const active = document.querySelector('.gm-object.gm-active');
    if (!active) return;

    const clone = active.cloneNode(true);
    clone.classList.remove('gm-active');
    clone.querySelectorAll('.gm-active')
      .forEach(el => el.classList.remove('gm-active'));

    const replacement = findReplacementActive(active);

    gmClipboard = {
      node: clone,
      mode: 'cut'
    };

    active.remove();
    if (replacement) setGmActive(replacement);

    console.log('[GM] CUT');
  }

  /* ===============================
     PASTE (100% SAFE)
  =============================== */
  function pasteToActiveObject() {
    if (!gmClipboard) return;

    const target = document.querySelector('.gm-object.gm-active');
    if (!target) return;

    const pasted = gmClipboard.node.cloneNode(true);
    target.appendChild(pasted);
    setGmActive(pasted);

    // clipboard habis HANYA jika mode CUT
    if (gmClipboard.mode === 'cut') {
      gmClipboard = null;
    }

    console.log('[GM] PASTE');
  }

  /* ===============================
     BUTTON HANDLER
  =============================== */
  document.addEventListener('click', function (e) {

    if (e.target.closest('.gm-copy-object')) {
      e.preventDefault();
      // e.stopPropagation();
      copyActiveObject();
      return;
    }

    if (e.target.closest('.gm-cut-object')) {
      e.preventDefault();
      // e.stopPropagation();
      cutActiveObject();
      return;
    }

    if (e.target.closest('.gm-paste-object')) {
      e.preventDefault();
      // e.stopPropagation();
      pasteToActiveObject();
      return;
    }

  }, true);

  /* ===============================
     KEYBOARD SHORTCUT
  =============================== */
  document.addEventListener('keydown', function (e) {

    const tag = e.target.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || e.target.isContentEditable) return;

    const key = e.key.toLowerCase();

    if (e.ctrlKey && key === 'c') {
      e.preventDefault();
      copyActiveObject();
    }

    if (e.ctrlKey && key === 'x') {
      e.preventDefault();
      cutActiveObject();
    }

    if (e.ctrlKey && key === 'v') {
      e.preventDefault();
      pasteToActiveObject();
    }

  });

})();
