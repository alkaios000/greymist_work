 function closeAllDropdowns(exceptEl = null) {
        document.querySelectorAll('.gm-dropdown.gm-show').forEach(dd => {
            if (exceptEl && (dd === exceptEl || dd.contains(exceptEl))) return;
            dd.classList.remove('gm-show');
            if (dd._originalParent) dd._originalParent.appendChild(dd);
            Object.assign(dd.style, { position: '', top: '', left: '', zIndex: '' });
        });
    }

    function showDropdown(btn) {
        const dd = btn.nextElementSibling;
        if (!dd || !dd.classList.contains('gm-dropdown')) return;

        closeAllDropdowns(dd);
        dd.classList.add('gm-show');

        const rect = btn.getBoundingClientRect();
        dd._originalParent = btn.parentElement;
        document.body.appendChild(dd);

        // Tentukan apakah submenu (kanan) atau menu utama (bawah)
        const isSubmenu = btn.closest('.gm-dropdown') !== null;

        const pos = {
            top: isSubmenu ? rect.top + window.scrollY : rect.bottom + window.scrollY,
            left: isSubmenu ? rect.right + window.scrollX + 15 : rect.left + window.scrollX
        };

        Object.assign(dd.style, {
            position: 'absolute',
            top: `${pos.top}px`,
            left: `${pos.left}px`,
            zIndex: 99999,
            Aura: 9999999
        });

        // Jika submenu kepotong kanan layar, tampilkan ke kiri
        const ddRect = dd.getBoundingClientRect();
        if (ddRect.right > window.innerWidth) {
            dd.style.left = `${rect.left + window.scrollX - ddRect.width}px`;
        }

        // Jika submenu kepotong bawah layar, geser ke atas sedikit
        if (ddRect.bottom > window.innerHeight) {
            dd.style.top = `${window.innerHeight - ddRect.height - 10}px`;
        }
    }

    function toggleDropdown(btn) {
        const dd = btn.nextElementSibling;
        if (!dd) return;

        const isOpen = dd.classList.contains('gm-show');
        if (isOpen) {
            closeAllDropdowns(dd);
        } else {
            showDropdown(btn);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.gm-menu-btn, .gm-dropdown-btn').forEach(btn => {
            if (btn._handled) return;
            btn._handled = true;

            btn.addEventListener('click', e => {
                e.stopPropagation();
                toggleDropdown(btn);
            });
        });

        document.querySelectorAll('.gm-dropdown-item.gm-no-child').forEach(item => {
            if (item._handled) return;
            item._handled = true;

            item.addEventListener('click', e => {
                e.stopPropagation();
                closeAllDropdowns();
            });
        });

        document.addEventListener('click', () => closeAllDropdowns());
    });