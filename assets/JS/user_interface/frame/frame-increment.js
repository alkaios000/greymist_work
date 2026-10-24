document.addEventListener('DOMContentLoaded', () => {
    let copiedFrame = null;
    let isObjectClicked = false;

    const frameThumbnailsContainer = document.getElementById('gm-frame-thumbnails');
    const framePreviewContainer = document.getElementById('gm-frame-preview');
    const addFrameBtn = document.getElementById('gm-add-frame-btn');

    // ======================= Object Active / Multi-Select =======================
    // contoh integrasi (tetap gunakan event delegation di framePreviewContainer)
    framePreviewContainer.addEventListener('click', function (e) {
        if (document.body.classList.contains('gm-interaction-mode-on')) {
            const core = e.target.closest('.gm-object-core');
            if (core) {
                core.querySelectorAll('.gm-object').forEach(o => o.classList.remove('gm-active'));
            }
            if (typeof GMObjectHandler !== 'undefined') {
                GMObjectHandler.detach();
            }
            return;
        }

        const obj = e.target.closest('.gm-object');
        if (!obj) return;

        const core = obj.closest('.gm-object-core');
        if (!core) return;

        e.stopPropagation();
        isObjectClicked = true;

        const objects = core.querySelectorAll('.gm-object');

        if (e.shiftKey) {
            obj.classList.add('gm-active');
        } else {
            objects.forEach(o => o.classList.remove('gm-active'));
            obj.classList.add('gm-active');
        }

        // attach handler
        GMObjectHandler.attachTo(obj);

        setTimeout(() => isObjectClicked = false, 0);
    });


    // ======================= Klik di luar untuk hapus active =======================
    document.addEventListener('click', function (e) {
        if (isObjectClicked) return; // jangan hapus saat klik object

        const safeAreas = [
            '.gm-top-menu-right',
            '.gm-color-dropdown',
            '#colorMenuBtn',
            '#gmGradientModal',
            '.gm-object-menu',
            '.gm-object-info-modal',
            '.gm-handler',
            '.gm-label',
            // Tambahan baru:
            '.gm-aside-left',
            '.gm-aside-right',
            '#gm-display-modal',
            '.gm-object-attribute',
            'body'

        ];

        const clickedInSafeArea = safeAreas.some(sel => e.target.closest(sel));
        if (!clickedInSafeArea) {
            document.querySelectorAll('.gm-object.gm-active').forEach(o => o.classList.remove('gm-active'));
        }
    });

    function getAllActiveObjects() {
        return Array.from(document.querySelectorAll('.gm-object-core .gm-object.gm-active'));
    }

    // ======================= Frame Management =======================
    function getNextFrameId() {
        const existing = Array.from(document.querySelectorAll('.gm-frame-thumbnail'))
            .map(f => parseInt(f.dataset.frame))
            .sort((a, b) => a - b);
        let id = 1;
        while (existing.includes(id)) id++;
        return id;
    }

    function generateCopyName(originalName) {
        let base = originalName.replace(/ - copy(?: \(\d+\))?$/, '');
        let newName = base + ' - copy';
        let counter = 1;
        while ([...document.querySelectorAll('.gm-frame-name')].some(el => el.textContent === newName)) {
            newName = `${base} - copy (${counter++})`;
        }
        return newName;
    }

    function updateCoreScale(core) {
        const wrapper = document.querySelector('.gm-frame-preview');
        if (!core || !wrapper) return;
        const scaleX = wrapper.clientWidth / 1920;
        const scaleY = wrapper.clientHeight / 1080;
        const scale = Math.min(scaleX, scaleY);
        core.style.transform = `scale(${scale})`;
    }

    function switchFrame(frameId) {
        frameId = frameId.toString();
        // Thumbnail
        document.querySelectorAll('.gm-frame-thumbnail').forEach(th => {
            th.classList.toggle('actived', th.dataset.frame === frameId);
        });
        // Core
        document.querySelectorAll('.gm-object-core').forEach(core => {
            if (core.dataset.frame === frameId) {
                core.classList.add('actived');
                core.style.display = 'block';
                updateCoreScale(core);
            } else {
                core.classList.remove('actived');
                core.style.display = 'none';
            }
        });
    }

    frameThumbnailsContainer.addEventListener('click', function (e) {
        const target = e.target.closest('.gm-frame-thumbnail');
        if (!target) return;
        switchFrame(target.dataset.frame);
    });

    addFrameBtn.addEventListener('click', function () {
        const newFrameId = getNextFrameId();
        // addNewFrame(newFrameId);
    });

    // habis dirubah dari add new, jadi dicopy
    // function addNewFrame(frameId) {
    //     // Hapus active di semua thumbnail
    //     document.querySelectorAll('.gm-frame-thumbnail').forEach(th => th.classList.remove('actived'));

    //     // === Thumbnail baru ===
    //     const newThumb = document.createElement('div');
    //     newThumb.className = 'gm-frame-thumbnail actived';
    //     newThumb.dataset.frame = frameId;
    //     newThumb.innerHTML = `
    //     <div class="gm-preview-box"></div>
    //     <div class="gm-frame-name">Frame ${frameId}</div>
    // `;
    //     frameThumbnailsContainer.appendChild(newThumb);

    //     // === Core baru ===
    //     const newCore = document.createElement('div');
    //     newCore.className = 'gm-object-core actived';
    //     newCore.dataset.frame = frameId;
    //     newCore.id = `object-core-${frameId}`;
    //     newCore.style.display = 'block';

    //     framePreviewContainer.appendChild(newCore);

    //     // Sembunyikan core lain
    //     document.querySelectorAll('.gm-object-core').forEach(core => {
    //         if (core !== newCore) {
    //             core.classList.remove('actived');
    //             core.style.display = 'none';
    //         }
    //     });

    //     // Init droppable untuk core baru
    //     if (typeof reInitDroppableCore === 'function') reInitDroppableCore(newCore);

    //     updateCoreScale(newCore);
    // }


    // ======================= Copy / Paste Frame =======================
    document.addEventListener('contextmenu', function (e) {
        const target = e.target.closest('.gm-frame-thumbnail');
        const frameMenu = document.getElementById('gm-frame-thumbnails');
        if (!target && !frameMenu.contains(e.target)) return;

        e.preventDefault();

        // let menu = document.getElementById('gm-context-menu');
        // if (!menu) {
        //     menu = document.createElement('div');
        //     menu.id = 'gm-context-menu';
        //     menu.classList.add('gm-context-menu');
        //     document.body.appendChild(menu);
        // }

        // let menuHtml = '<ul>';
        // if (target) {
        //     menuHtml += '<li id="gm-copy-frame">Copy</li>';
        //     menuHtml += '<li id="gm-rename-frame">Rename</li>';
        //     menuHtml += '<li id="gm-delete-frame">Delete</li>';
        // }
        // if (copiedFrame && (target || frameMenu.contains(e.target))) {
        //     menuHtml += '<li id="gm-paste-frame">Paste</li>';
        // }
        // menuHtml += '</ul>';
        // menu.innerHTML = menuHtml;
        // menu.style.top = `${e.pageY}px`;
        // menu.style.left = `${e.pageX}px`;
        // menu.style.display = 'block';

        // --- Event Copy ---
        if (target && document.getElementById('gm-copy-frame')) {
            document.getElementById('gm-copy-frame').onclick = () => {
                copiedFrame = target.dataset.frame;
                menu.style.display = 'none';
            };
        }

        // --- Event Paste ---
        if (copiedFrame && document.getElementById('gm-paste-frame')) {
            document.getElementById('gm-paste-frame').onclick = () => {
                const newFrameId = getNextFrameId();

                // Clone thumbnail
                const origThumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${copiedFrame}"]`);
                const newThumb = origThumb.cloneNode(true);
                newThumb.dataset.frame = newFrameId;
                newThumb.querySelector('.gm-frame-name').textContent =
                    generateCopyName(origThumb.querySelector('.gm-frame-name').textContent);
                document.querySelectorAll('.gm-frame-thumbnail').forEach(th => th.classList.remove('actived'));
                newThumb.classList.add('actived');
                frameThumbnailsContainer.appendChild(newThumb);

                // Clone core
                const origCore = document.querySelector(`.gm-object-core[data-frame="${copiedFrame}"]`);
                const newCore = origCore.cloneNode(true);
                newCore.dataset.frame = newFrameId;
                newCore.id = `object-core-${newFrameId}`;
                document.querySelectorAll('.gm-object-core').forEach(core => {
                    core.classList.remove('actived');
                    core.style.display = 'none';
                });
                newCore.classList.add('actived');
                newCore.style.display = 'block';
                framePreviewContainer.appendChild(newCore);

                // Re-init droppable core baru
                if (typeof reInitDroppableCore === 'function') reInitDroppableCore(newCore);
                updateCoreScale(newCore);

                menu.style.display = 'none';
            };
        }

        // --- Event Rename / Delete ---
        if (target && document.getElementById('gm-rename-frame')) {
            document.getElementById('gm-rename-frame').onclick = () => {
                const currentName = target.querySelector('.gm-frame-name').textContent;
                const newName = prompt('Rename frame:', currentName);
                if (newName) target.querySelector('.gm-frame-name').textContent = newName;
                menu.style.display = 'none';
            };
        }

        // if (target && document.getElementById('gm-delete-frame')) {
        //     document.getElementById('gm-delete-frame').onclick = () => {
        //         if (confirm('Are you sure to delete this frame?')) {
        //             target.remove();
        //             document.querySelector(`.gm-object-core[data-frame="${target.dataset.frame}"]`)?.remove();
        //             const remaining = document.querySelectorAll('.gm-frame-thumbnail');
        //             if (remaining.length > 0) switchFrame(remaining[0].dataset.frame);
        //         }
        //         menu.style.display = 'none';
        //     };
        // }
    });

    // Klik di luar menu → tutup
    // document.addEventListener('click', () => {
    //     const menu = document.getElementById('gm-context-menu');
    //     if (menu) menu.style.display = 'none';
    // });

    // Inisialisasi pertama
    // if (document.querySelectorAll('.gm-frame-thumbnail').length === 0) {
    //     addNewFrame(getNextFrameId());
    // }
});
