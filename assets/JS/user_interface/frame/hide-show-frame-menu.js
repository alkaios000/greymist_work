// Ambil elemen tombol dan menu frame
const hideFrameBtn = document.getElementById('gm-hide-frame-btn');
const frameMenu = document.getElementById('gm-frame-menu');

// Load saved state
if (hideFrameBtn && frameMenu) {
  const isHidden = localStorage.getItem('gm_frame_menu_hidden') === 'true';
  const icon = hideFrameBtn.querySelector('i');
  
  if (isHidden) {
    frameMenu.classList.add('hide');
    document.body.classList.add('gm-frame-hidden');
    icon.classList.remove('fa-angle-up');
    icon.classList.add('fa-angle-down');
  }

  hideFrameBtn.addEventListener('click', function() {
    // Toggle class 'hide' pada menu frame untuk menggesernya ke atas 100px
    frameMenu.classList.toggle('hide');
    const currentlyHidden = frameMenu.classList.contains('hide');
    
    // Sync class on body for other floating elements (e.g. toggles, toolbars)
    document.body.classList.toggle('gm-frame-hidden', currentlyHidden);
    
    // Save state
    localStorage.setItem('gm_frame_menu_hidden', currentlyHidden);
    
    // Ubah ikon sesuai keadaan
    if (currentlyHidden) {
      icon.classList.remove('fa-angle-up');
      icon.classList.add('fa-angle-down');
    } else {
      icon.classList.remove('fa-angle-down');
      icon.classList.add('fa-angle-up');
    }
  });
}
