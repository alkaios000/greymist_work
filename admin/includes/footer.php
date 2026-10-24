    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const menuToggle = document.getElementById('menuToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (menuToggle && adminSidebar) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            adminSidebar.classList.toggle('open');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('active');
            }
            document.body.style.overflow = adminSidebar.classList.contains('open') ? 'hidden' : '';
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            adminSidebar.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            adminSidebar.classList.remove('open');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Close Alert
    document.querySelectorAll('.close-alert').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.alert').remove();
        });
    });
    
    // Auto close alerts after 3 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 3000);
});

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

// Live Thumbnail Logic
function applyThumbnailLogic() {
    const coreW = window.innerWidth;
    const coreH = window.innerHeight;

    document.querySelectorAll('.live-thumbnail-wrapper').forEach(wrapper => {
        const preview = wrapper.querySelector('.mini-preview');
        if (preview && preview.innerHTML.trim() !== '') {
            preview.style.width = coreW + 'px';
            preview.style.height = coreH + 'px';
            preview.style.transformOrigin = 'center center';

            const bw = wrapper.clientWidth;
            const bh = wrapper.clientHeight;
            const scale = Math.min(bw / coreW, bh / coreH);

            preview.style.position = 'absolute';
            preview.style.top = '50%';
            preview.style.left = '50%';
            preview.style.transform = `translate(-50%, -50%) scale(${scale})`;
            
            // Show preview and hide loader
            wrapper.style.opacity = '1';
            const loader = wrapper.parentElement.querySelector('.thumbnail-loader');
            if (loader) loader.style.opacity = '0';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const lazyObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const wrapper = entry.target;
                const preview = wrapper.querySelector('.mini-preview');
                
                if (preview && !preview.innerHTML.trim()) {
                    const content = wrapper.getAttribute('data-content');
                    if (content) {
                        preview.innerHTML = content;
                        wrapper.removeAttribute('data-content');
                        setTimeout(applyThumbnailLogic, 20);
                    }
                }
                obs.unobserve(wrapper);
            }
        });
    }, { rootMargin: '300px 0px' });

    document.querySelectorAll('.lazy-live-thumbnail').forEach(wrapper => {
        lazyObserver.observe(wrapper);
    });
});

window.addEventListener('resize', applyThumbnailLogic);
window.addEventListener('load', applyThumbnailLogic);
setTimeout(applyThumbnailLogic, 100);
</script>
</body>
</html>