// swap.js
document.addEventListener('keydown', e => {
  // Alt + Arrow, tanpa Shift
  if (!e.altKey || e.shiftKey) return;
  if (!['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.code)) return;

  e.preventDefault();
  e.stopPropagation();

  const activeEls = Array.from(document.querySelectorAll('.gm-object.gm-active'));
  if (!activeEls.length) return;

  activeEls.forEach(el => {
    const parent = el.parentElement;
    if (!parent) return;

    let target;
    switch(e.code){
      case 'ArrowDown':
      case 'ArrowRight':
        target = el.nextElementSibling;
        if(target && target.classList.contains('gm-object')) swapElements(el, target);
        break;
      case 'ArrowUp':
      case 'ArrowLeft':
        target = el.previousElementSibling;
        if(target && target.classList.contains('gm-object')) swapElements(target, el);
        break;
    }
  });
});

function swapElements(a,b){
  const aRect = a.getBoundingClientRect();
  const bRect = b.getBoundingClientRect();

  a.style.transition = 'transform 150ms';
  b.style.transition = 'transform 150ms';
  a.style.transform = `translate(${bRect.left - aRect.left}px, ${bRect.top - aRect.top}px)`;
  b.style.transform = `translate(${aRect.left - bRect.left}px, ${aRect.top - bRect.top}px)`;

  requestAnimationFrame(() => {
    setTimeout(() => {
      b.style.transform = '';
      a.style.transform = '';
      if(a.nextSibling === b) a.parentNode.insertBefore(b,a);
      else a.parentNode.insertBefore(a,b);
      a.style.transition = '';
      b.style.transition = '';
    },150);
  });
}

