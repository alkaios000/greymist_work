document.addEventListener('keydown', e => {
  // Hanya aktif jika Alt + Shift + Arrow
  if (!e.altKey || !e.shiftKey) return;
  if (!['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.code)) return;

  e.preventDefault();

  const activeEls = Array.from(document.querySelectorAll('.gm-object.gm-active'));
  if (!activeEls.length) return;

  // Kelompokkan elemen aktif berdasarkan parent
  const groupedByParent = activeEls.reduce((acc, el) => {
    const parent = el.parentElement;
    if (!parent) return acc;
    if (!acc.has(parent)) acc.set(parent, []);
    acc.get(parent).push(el);
    return acc;
  }, new Map());

  function insertAfter(newNode, referenceNode) {
    if(referenceNode.parentNode){
      if(referenceNode.nextSibling)
        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
      else
        referenceNode.parentNode.appendChild(newNode);
    }
  }

  groupedByParent.forEach((els, parent) => {
    let lastEl = els.reduce((last, el) => {
      if (Array.from(parent.children).indexOf(el) > Array.from(parent.children).indexOf(last))
        return el;
      return last;
    }, els[0]);

    els.forEach(el => {
      // ⚠️ Disable drag elemen asli
      el.draggable = false;

      // Buat clone
      const clone = el.cloneNode(true);
      clone.classList.remove('gm-active');
      clone.classList.add(`gm-object-clone-${Date.now()}`);

      // ⚠️ Clone TIDAK boleh draggable
      clone.draggable = false;

      // Styling animasi
      clone.style.transition = 'transform 150ms, opacity 150ms';
      clone.style.opacity = '0.7';
      clone.style.position = 'relative';

      insertAfter(clone, lastEl);
      lastEl = clone;

      requestAnimationFrame(() => {
        clone.style.transform = 'translate(0,0)';
        clone.style.opacity = '1';
      });

      // Re-init agar clone + nested child bisa dropable
      if(typeof reInitDroppableCore === 'function') reInitDroppableCore(clone);
    });
  });
});

// Klik di core tetap normal
document.addEventListener("click", (e) => {
  const core = getActiveCore();
  if (!core) return;

  const clicked = e.target.closest(".gm-object");
  if (!clicked || !core.contains(clicked)) return;

  handleActiveLogic(clicked);
});

function handleActiveLogic(el) {
  // Toggle active
  if (el.classList.contains('gm-active')) {
    el.classList.remove('gm-active');
  } else {
    el.classList.add('gm-active');
  }
}
