(function () {
  const previewBtn = document.querySelector('.gm-scale-preview');
  if (!previewBtn) return;

  // Tombol Exit Fullscreen
  const exitBtn = document.createElement('button');
  exitBtn.id = 'gm-exit-fullscreen';
  exitBtn.title = 'Keluar dari Preview (ESC)';
  exitBtn.innerHTML = '<i class="fa-solid fa-down-left-and-up-right-to-center"></i>';
  document.body.appendChild(exitBtn);

  // Floating Bar Responsif di Fullscreen
  let fsToolbar = document.getElementById('gm-fullscreen-bp-bar');
  if (!fsToolbar) {
    fsToolbar = document.createElement('div');
    fsToolbar.id = 'gm-fullscreen-bp-bar';
    fsToolbar.style.cssText = `
      position: fixed;
      top: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 100000;
      background: rgba(15, 23, 42, 0.88);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 9999px;
      padding: 5px 14px;
      display: none;
      align-items: center;
      gap: 6px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      user-select: none;
    `;
    fsToolbar.innerHTML = `
      <span style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; padding: 0 4px;">Responsive:</span>
      <button class="gm-fs-bp-btn active" data-mode="fluid" title="Layar Penuh Otomatis (Mengikuti Lebar Jendela)" style="background: #0284c7; color: #fff; border: none; padding: 4px 10px; border-radius: 9999px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.15s;">
        <i class="fas fa-desktop"></i> <span>Fluid</span>
      </button>
      <button class="gm-fs-bp-btn" data-mode="tablet" data-width="768" title="Mode Tablet (768px)" style="background: transparent; color: #cbd5e1; border: none; padding: 4px 10px; border-radius: 9999px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.15s;">
        <i class="fas fa-tablet-alt"></i> <span>Tablet</span>
      </button>
      <button class="gm-fs-bp-btn" data-mode="mobile" data-width="375" title="Mode Ponsel (375px)" style="background: transparent; color: #cbd5e1; border: none; padding: 4px 10px; border-radius: 9999px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: all 0.15s;">
        <i class="fas fa-mobile-alt"></i> <span>Ponsel</span>
      </button>
      <div style="width: 1px; height: 16px; background: rgba(255,255,255,0.2); margin: 0 4px;"></div>
      <span id="gm-fs-width-badge" style="font-size: 11.5px; color: #38bdf8; font-family: monospace; font-weight: 600; min-width: 65px; text-align: center;">100%</span>
    `;
    document.body.appendChild(fsToolbar);
  }

  function setFullscreenDeviceMode(mode, targetWidth) {
    const activeCore = document.querySelector('.gm-object-core.actived') || document.querySelector('.gm-object-core');
    const badge = document.getElementById('gm-fs-width-badge');
    const buttons = fsToolbar.querySelectorAll('.gm-fs-bp-btn');

    buttons.forEach(btn => {
      const isActive = btn.dataset.mode === mode;
      btn.style.background = isActive ? '#0284c7' : 'transparent';
      btn.style.color = isActive ? '#ffffff' : '#cbd5e1';
      btn.classList.toggle('active', isActive);
    });

    if (!activeCore) return;

    if (mode === 'fluid') {
      activeCore.style.removeProperty('width');
      activeCore.style.removeProperty('max-width');
      activeCore.style.removeProperty('margin');
      activeCore.style.removeProperty('box-shadow');
      if (badge) badge.textContent = `${window.innerWidth}px`;
      if (window.gmResponsiveTranslator) {
        window.gmResponsiveTranslator.translate(window.innerWidth);
      }
    } else {
      activeCore.style.setProperty('width', targetWidth + 'px', 'important');
      activeCore.style.setProperty('max-width', '100%', 'important');
      activeCore.style.setProperty('margin', '0 auto', 'important');
      activeCore.style.setProperty('box-shadow', '0 0 50px rgba(0,0,0,0.25)', 'important');
      if (badge) badge.textContent = `${targetWidth}px`;
      if (window.gmResponsiveTranslator) {
        window.gmResponsiveTranslator.translate(targetWidth);
      }
    }
  }

  fsToolbar.addEventListener('click', (e) => {
    const btn = e.target.closest('.gm-fs-bp-btn');
    if (!btn) return;
    const mode = btn.dataset.mode;
    const width = parseInt(btn.dataset.width) || null;
    setFullscreenDeviceMode(mode, width);
  });

  window.addEventListener('resize', () => {
    if (document.body.classList.contains('gm-fullscreen-preview')) {
      const activeBtn = fsToolbar.querySelector('.gm-fs-bp-btn.active');
      if (activeBtn && activeBtn.dataset.mode === 'fluid') {
        const badge = document.getElementById('gm-fs-width-badge');
        if (badge) badge.textContent = `${window.innerWidth}px`;
        if (window.gmResponsiveTranslator) {
          window.gmResponsiveTranslator.translate(window.innerWidth);
        }
      }
    }
  });

  function enterFullscreenPreview() {
      // Tambahkan class state
      document.body.classList.add('gm-fullscreen-preview');
      document.body.classList.add('gm-interaction-mode-on');
      
      // Hapus status aktif dari semua objek
      document.querySelectorAll('.gm-object.gm-active').forEach(o => o.classList.remove('gm-active'));
      
      // Force restart animasi pada canvas utama
      const activeCore = document.querySelector('.gm-object-core.actived');
      if (activeCore) {
          activeCore.querySelectorAll('.gm-anim-base').forEach(el => {
              const val = el.dataset.gmAnim;
              if (val) {
                  el.classList.remove(val);
                  void el.offsetWidth; // Trigger reflow
                  el.classList.add(val);
              }
          });
      }

      // Tampilkan toolbar fullscreen dan reset ke fluid
      if (fsToolbar) {
        fsToolbar.style.display = 'flex';
        setFullscreenDeviceMode('fluid', null);
      }

      // Jalankan penerjemah responsif
      if (window.gmResponsiveTranslator) {
        window.gmResponsiveTranslator.translate(window.innerWidth);
      }
  }

  function exitFullscreenPreview() {
      document.body.classList.remove('gm-fullscreen-preview');
      document.body.classList.remove('gm-interaction-mode-on');

      if (fsToolbar) {
        fsToolbar.style.display = 'none';
      }

      const activeCore = document.querySelector('.gm-object-core.actived') || document.querySelector('.gm-object-core');
      if (activeCore) {
        activeCore.style.removeProperty('width');
        activeCore.style.removeProperty('max-width');
        activeCore.style.removeProperty('margin');
        activeCore.style.removeProperty('box-shadow');
      }

      // Kembalikan penerjemah ke mode aktif editor
      if (window.gmResponsiveTranslator) {
        window.gmResponsiveTranslator.translate();
      }
  }

  previewBtn.addEventListener('click', enterFullscreenPreview);
  exitBtn.addEventListener('click', exitFullscreenPreview);

  document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && document.body.classList.contains('gm-fullscreen-preview')) {
          exitFullscreenPreview();
      }
  });

})();