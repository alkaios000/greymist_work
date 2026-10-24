<script>
// Global Context Menu Logic for Cards
let activeDropdown = null;
let dropdownPlaceholder = null;

function openContextMenu(sourceDropdown, x, y, sourceBtn) {
    if (activeDropdown) {
        closeActiveDropdown();
    }
    
    activeDropdown = sourceDropdown;
    dropdownPlaceholder = document.createElement('div');
    dropdownPlaceholder.style.display = 'none';
    dropdownPlaceholder.className = 'dropdown-placeholder';
    
    // Insert placeholder where sourceDropdown is currently
    sourceDropdown.parentNode.insertBefore(dropdownPlaceholder, sourceDropdown);
    
    // Move sourceDropdown to document.body
    document.body.appendChild(sourceDropdown);
    
    sourceDropdown.classList.add('active');
    
    // Set styles for global positioning
    sourceDropdown.style.position = 'fixed';
    sourceDropdown.style.zIndex = '99999';
    
    const dropWidth = 180;
    
    // Reset positioning before measuring
    sourceDropdown.style.top = '0';
    sourceDropdown.style.left = '0';
    sourceDropdown.style.bottom = 'auto';
    sourceDropdown.style.right = 'auto';
    
    const dropHeight = sourceDropdown.offsetHeight;
    
    let posX = x;
    let posY = y;
    
    if (posX + dropWidth > window.innerWidth) posX = window.innerWidth - dropWidth - 10;
    if (posY + dropHeight > window.innerHeight) posY = window.innerHeight - dropHeight - 10;
    
    sourceDropdown.style.left = posX + 'px';
    sourceDropdown.style.top = posY + 'px';
    
    if (sourceBtn) {
        sourceDropdown.dataset.btnTarget = sourceBtn.id;
    } else {
        sourceDropdown.dataset.btnTarget = '';
    }
}

function closeActiveDropdown() {
    if (activeDropdown && dropdownPlaceholder) {
        // Reset inline styles
        activeDropdown.style.position = '';
        activeDropdown.style.zIndex = '';
        activeDropdown.style.top = '';
        activeDropdown.style.left = '';
        activeDropdown.style.bottom = '';
        activeDropdown.style.right = '';
        activeDropdown.classList.remove('active');
        activeDropdown.dataset.btnTarget = '';
        
        // Move back to original place
        dropdownPlaceholder.parentNode.insertBefore(activeDropdown, dropdownPlaceholder);
        dropdownPlaceholder.remove();
        
        activeDropdown = null;
        dropdownPlaceholder = null;
    }
}

function closeAllDropdowns() {
    closeActiveDropdown();
}

function toggleDropdown(btn) {
    if (!btn.id) btn.id = 'menu-btn-' + Math.random().toString(36).substr(2, 9);
    
    if (activeDropdown && activeDropdown.dataset.btnTarget === btn.id) {
        closeActiveDropdown();
        return;
    }
    
    if (activeDropdown) {
        closeActiveDropdown();
    }
    
    const sourceDropdown = btn.nextElementSibling;
    if (!sourceDropdown || !sourceDropdown.classList.contains('card-dropdown')) return;
    
    const rect = btn.getBoundingClientRect();
    openContextMenu(sourceDropdown, rect.right - 180, rect.bottom + 5, btn);
}

// Close when clicking outside
document.addEventListener('click', function(e) {
    if (activeDropdown) {
        if (!e.target.closest('.card-dropdown') && !e.target.closest('.menu-trigger')) {
            closeActiveDropdown();
        }
    }
});

// Hide on scroll to prevent detached menus
window.addEventListener('scroll', function() {
    if (activeDropdown) {
        closeActiveDropdown();
    }
}, true);

// Right click context menu for cards
document.addEventListener('contextmenu', function(e) {
    const card = e.target.closest('.gm-project-card');
    if (card) {
        e.preventDefault(); 
        
        if (activeDropdown) {
            closeActiveDropdown();
        }
        
        const menuTrigger = card.querySelector('.menu-trigger');
        if (menuTrigger) {
            const sourceDropdown = menuTrigger.nextElementSibling;
            if (sourceDropdown && sourceDropdown.classList.contains('card-dropdown')) {
                openContextMenu(sourceDropdown, e.clientX, e.clientY, null);
            }
        }
    }
});
</script>


</body>
</html>
<?php
// Tidak ada spasi atau karakter setelah tag PHP