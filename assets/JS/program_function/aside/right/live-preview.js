(function () {
  const previewBtn = document.querySelector('.gm-scale-preview');
  if (!previewBtn) return;

  // Hapus semua logika window open sebelumnya dan ganti dengan In-Place Fullscreen
  // Buat tombol Exit
  const exitBtn = document.createElement('button');
  exitBtn.id = 'gm-exit-fullscreen';
  exitBtn.title = 'Keluar dari Preview (ESC)';
  exitBtn.innerHTML = '<i class="fa-solid fa-down-left-and-up-right-to-center"></i>';
  document.body.appendChild(exitBtn);

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
  }

  function exitFullscreenPreview() {
      document.body.classList.remove('gm-fullscreen-preview');
      document.body.classList.remove('gm-interaction-mode-on');
  }

  previewBtn.addEventListener('click', enterFullscreenPreview);
  exitBtn.addEventListener('click', exitFullscreenPreview);

  document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && document.body.classList.contains('gm-fullscreen-preview')) {
          exitFullscreenPreview();
      }
  });

})();
