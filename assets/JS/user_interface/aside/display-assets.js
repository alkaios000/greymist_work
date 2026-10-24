// Ambil elemen menu assets
const assetsMenu = document.getElementById('gm-assets-cont');
// Ambil seluruh asset item (menggunakan data-target untuk menentukan display tujuan)
const assetItems = document.querySelectorAll('#gm-assets-list > div');

assetItems.forEach(item => {
  item.addEventListener('click', () => {
    // Sembunyikan menu assets
    assetsMenu.style.display = 'none';

    // Tampilkan display yang sesuai (data-target berisi id display)
    const targetId = item.getAttribute('data-target');
    const targetDisplay = document.getElementById(targetId);
    if (targetDisplay) {
      targetDisplay.style.display = 'flex';
    }
  });
});

// Fungsi untuk kembali ke menu assets
const backButtons = document.querySelectorAll('.gm-back-to-assets');
backButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    // Sembunyikan semua display
    document.querySelectorAll('.gm-display').forEach(display => {
      display.style.display = 'none';
    });
    // Tampilkan kembali menu assets
    assetsMenu.style.display = 'flex';
  });
});