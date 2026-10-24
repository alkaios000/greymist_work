// File: categoryFilter.js
function setupCategoryFilter(iconsData) {
    const categoryElems = document.querySelectorAll('.gm-icon-category p');
    // Set default warna untuk elemen "all"
    categoryElems.forEach(el => {
      if (el.dataset.category === 'all') {
        el.style.color = '#9ad0ff';
      } else {
        el.style.color = '';
      }
    });
  
    categoryElems.forEach(elem => {
      elem.addEventListener('click', () => {
        // Reset warna teks pada semua elemen kategori
        categoryElems.forEach(el => {
          el.style.color = '';
        });
        // Set warna teks pada elemen yang diklik
        elem.style.color = '#9ad0ff';
  
        const selectedCategory = elem.dataset.category;
        const filteredIcons = selectedCategory === 'all'
          ? iconsData
          : iconsData.filter(icon => icon.category.toLowerCase() === selectedCategory);
        renderIcons(filteredIcons);
      });
    });
  }
  