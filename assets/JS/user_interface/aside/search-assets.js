  // Simpan isi HTML asli ikon search
    const searchCont = document.getElementById('gm-assets-search-cont');
    const originalIconHTML = searchCont.innerHTML;

    // Fungsi untuk mengembalikan tampilan ikon search
    function revertSearchIcon() {
      searchCont.innerHTML = originalIconHTML;
      addSearchIconListener();
      // Hapus pesan "tidak ada aset" jika ada
      const noMatchElem = document.getElementById('no-match');
      if(noMatchElem) {
        noMatchElem.remove();
      }
      // Tampilkan kembali semua aset
      document.querySelectorAll('#gm-assets-list > div').forEach(asset => {
        asset.style.display = '';
      });
    }

    // Fungsi untuk membuka input pencarian
    function openSearchInput() {
      const searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.placeholder = 'Cari aset...';
      searchInput.className = 'gm-assets-input';

      // Ganti isi container dengan input
      searchCont.innerHTML = '';
      searchCont.appendChild(searchInput);
      searchInput.focus();

      // Jika input kehilangan fokus, kembali ke ikon search.
      searchInput.addEventListener('blur', function() {
        // Delay agar klik pada aset tidak tertimpa oleh blur
        setTimeout(revertSearchIcon, 200);
      });

      // Event listener untuk input pencarian
      searchInput.addEventListener('input', function() {
        const filter = this.value.trim().toUpperCase();
        const assets = document.querySelectorAll('#gm-assets-list > div');
        let anyMatch = false;
        
        assets.forEach(asset => {
          const titleElem = asset.querySelector('.gm-asset-title p');
          if (titleElem) {
            const title = titleElem.innerText.toUpperCase();
            if (title.startsWith(filter)) {
              asset.style.display = '';
              anyMatch = true;
            } else {
              asset.style.display = 'none';
            }
          }
        });
        
        // Ambil elemen pesan "tidak ada aset"
        let noMatchElem = document.getElementById('no-match');
        
        // Jika ada filter yang tidak kosong dan tidak ada aset yang cocok,
        // tampilkan pesan, jika tidak hapus pesan jika ada.
        if (filter !== '' && !anyMatch) {
          if (!noMatchElem) {
            noMatchElem = document.createElement('div');
            noMatchElem.id = 'no-match';
            noMatchElem.innerText = 'Tidak ada aset yang sesuai';
            document.getElementById('gm-assets-list').appendChild(noMatchElem);
          }
        } else {
          if (noMatchElem) {
            noMatchElem.remove();
          }
        }
    
      
      });
    }

    // Tambah listener ke ikon search
    function addSearchIconListener() {
      const icon = searchCont.querySelector('.gm-assets-search');
      if (icon) {
        icon.addEventListener('click', openSearchInput);
      }
    }

    addSearchIconListener();