const GMObjectHandler = (() => {
    let activeObj = null;
    let handlers = {};
    let labels = {};
    let scale = 1;
    let dragging = null;
    let isScaling = false;
    let observer = null;

    function getCurrentScale() {
        const core = activeObj?.closest('.gm-object-core');
        if (!core) return 1;
        const transform = getComputedStyle(core).transform;
        if (transform === 'none') return 1;
        const match = transform.match(/matrix\(([\d.\-]+)/);
        return match ? parseFloat(match[1]) : 1;
    }

    function createHandler(name, iconClass, cursor) {
        const el = document.createElement('div');
        el.className = `gm-handler gm-handler-${name}`;
        el.innerHTML = `<i class="fa-solid ${iconClass}"></i>`;
        Object.assign(el.style, {
            position: 'absolute',
            cursor,
            fontSize: '12px',
            color: '#4b4b4b',
            userSelect: 'none',
            zIndex: 9999,
        });
        el.dataset.type = name;
        document.body.appendChild(el);
        return el;
    }

    function createLabel(name) {
        const el = document.createElement('div');
        el.className = `gm-label gm-label-${name}`;
        Object.assign(el.style, {
            position: 'absolute',
            fontSize: '11px',
            fontFamily: 'monospace',
            padding: '2px 2px',
            background: 'rgba(0,0,0,0.6)',
            color: '#fff',
            borderRadius: '4px',
            zIndex: 9999,
            pointerEvents: 'auto',
        });
        el.contentEditable = true;
        document.body.appendChild(el);
        return el;
    }

    function attachTo(obj) {
        detach();
        activeObj = obj;
        scale = getCurrentScale();

        // ====== Handlers ======
        handlers = {
            top: createHandler('top', 'fa-up-down', 'ns-resize'),
            right: createHandler('right', 'fa-left-right', 'ew-resize'),
            bottom: createHandler('bottom', 'fa-up-down', 'ns-resize'),
            left: createHandler('left', 'fa-left-right', 'ew-resize'),
            radius: createHandler('radius', 'fa-circle-notch', 'pointer')
        };

        // ====== Labels / Inputs ======
        labels = {
            width: createInputLabel('width'),
            height: createInputLabel('height'),
            radius: createInputLabel('radius')
        };

        // ====== Fungsi bantu auto-resize input ======
        function updateInputWidth(input) {
            const tmp = document.createElement('span');
            tmp.style.visibility = 'hidden';
            tmp.style.position = 'absolute';
            tmp.style.whiteSpace = 'pre';
            tmp.style.font = getComputedStyle(input).font;
            tmp.textContent = input.value || input.placeholder || '0';
            document.body.appendChild(tmp);
            const width = tmp.getBoundingClientRect().width;
            document.body.removeChild(tmp);
            input.style.width = `${width + 16}px`; // + buffer 16px untuk padding
        }

        // ====== Set value awal dari computed style ======
        const style = getComputedStyle(activeObj);
        labels.width.value = activeObj.style.width || style.width;
        updateInputWidth(labels.width);

        labels.height.value = activeObj.style.height || style.height;
        updateInputWidth(labels.height);

        labels.radius.value = activeObj.style.borderRadius || style.borderRadius || '0px';
        updateInputWidth(labels.radius);

        // ====== Enter / Blur → apply value ======
        ['width', 'height', 'radius'].forEach(type => {
            const input = labels[type];

            input.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                applyInputValue(type);
                input.blur();
            });

            input.addEventListener('blur', () => {
                updateHandlerPositions();
            });

            input.addEventListener('input', () => {
                updateInputWidth(input);
            });
        });

        // ====== Apply input value ke object ======
        function applyInputValue(type) {
            if (!activeObj) return;
            const val = labels[type].value.trim();
            if (!val) return;

            // langsung set value ke style
            // contoh: "100px", "fit-content", "calc(100% - 20px)", "auto"
            if (type === 'width') activeObj.style.width = val;
            else if (type === 'height') activeObj.style.height = val;
            else if (type === 'radius') activeObj.style.borderRadius = val;
            input.addEventListener('keydown', e => {
                if (e.key !== 'Enter') return;
                e.preventDefault();
                applyInputValue(type);
                input.blur();
            });

            input.addEventListener('blur', () => {
                applyInputValue(type);
                updateHandlerPositions();
            });

            updateHandlerPositions();
        }

        // ====== Update posisi label & handlers ======
        updateHandlerPositions();
        startFollowLoop();
        observeDeletion();



        function applyInputValue(type) {
            if (!activeObj) return;
            const val = labels[type].value.trim();
            if (!val) return;
            const match = val.match(/^([\d.\-]+)([a-z%]*)$/i);
            if (!match) return;
            const num = parseFloat(match[1]);
            const unit = match[2] || 'px';
            if (type === 'width') activeObj.style.width = `${num}${unit}`;
            else if (type === 'height') activeObj.style.height = `${num}${unit}`;
            else if (type === 'radius') activeObj.style.borderRadius = `${num}${unit}`;
            updateHandlerPositions();
        }
    }

    function createInputLabel(name) {
        const input = document.createElement('input');
        input.type = 'text';
        input.className = `gm-label gm-label-${name}`;
        Object.assign(input.style, {
            position: 'absolute',
            fontSize: '11px',
            fontFamily: 'monospace',
            padding: '2px 5px',
            background: 'rgba(0,0,0,0.6)',
            color: '#fff',
            borderRadius: '4px',
            border: 'none',
            outline: 'none',
            zIndex: 9999,
            width: 'auto',
            minWidth: '20px',   // jangan terlalu kecil
            boxSizing: 'content-box' // supaya padding dihitung terpisah
        });
        document.body.appendChild(input);

        // update width otomatis sesuai teks
        function updateWidth() {
            const tmp = document.createElement('span');
            tmp.style.visibility = 'hidden';
            tmp.style.position = 'absolute';
            tmp.style.whiteSpace = 'pre';
            tmp.style.font = getComputedStyle(input).font;
            tmp.textContent = input.value || '0';
            document.body.appendChild(tmp);
            input.style.width = (tmp.getBoundingClientRect().width + 10) + 'px'; // +10px buffer
            document.body.removeChild(tmp);
        }

        input.addEventListener('input', updateWidth);
        updateWidth(); // langsung update saat dibuat
        return input;
    }



    function detach() {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
        for (const h in handlers) handlers[h]?.remove();
        for (const l in labels) labels[l]?.remove();
        handlers = {};
        labels = {};
        activeObj = null;
    }

    function observeDeletion() {
        if (!activeObj) return;
        const parent = activeObj.parentNode;
        if (!parent) return;
        observer = new MutationObserver(mutations => {
            for (const m of mutations) {
                m.removedNodes.forEach(node => {
                    if (node === activeObj) detach();
                });
            }
        });
        observer.observe(parent, { childList: true });
    }

    function updateHandlerPositions() {
        if (!activeObj) return;
        const rect = activeObj.getBoundingClientRect();
        const offset = 6;
        const iconSize = 12;
        const halfIcon = iconSize / 2;

        // Posisi handler
        const pos = {
            top: [rect.left + rect.width / 2 - halfIcon, rect.top - offset - halfIcon],
            right: [rect.right + offset - halfIcon, rect.top + rect.height / 2 - halfIcon],
            bottom: [rect.left + rect.width / 2 - halfIcon, rect.bottom + offset - halfIcon],
            left: [rect.left - offset - halfIcon, rect.top + rect.height / 2 - halfIcon],
            radius: [rect.right + offset - halfIcon, rect.top - offset - halfIcon]
        };

        for (const k in handlers) {
            const h = handlers[k];
            h.style.left = `${pos[k][0]}px`;
            h.style.top = `${pos[k][1]}px`;
        }

        // =================== label values ===================
        ['width', 'height', 'radius'].forEach(type => {
            const input = labels[type];
            // Jangan overwrite saat sedang edit (focus)
            if (document.activeElement !== input) {
                // Ambil style asli dari object, termasuk unit (%/em/rem)
                input.value = activeObj.style[type] || getComputedStyle(activeObj)[type];
                // Auto fit content
                input.style.width = `${Math.max(input.value.length + 1, 3)}ch`;
            }
        });
        if (document.activeElement !== labels.radius) {
            labels.radius.value = activeObj.style.borderRadius || getComputedStyle(activeObj).borderRadius || '0px';
            labels.radius.style.width = `${Math.max(labels.radius.value.length + 1, 3)}ch`;
        }

        // Posisi label
        labels.width.style.left = `${rect.left + rect.width / 2 - 20}px`;
        labels.width.style.top = `${rect.top - 25}px`;

        labels.height.style.left = `${rect.right + 10}px`;
        labels.height.style.top = `${rect.top + rect.height / 2 - 10}px`;
        labels.height.style.transform = 'rotate(90deg)';

        labels.radius.style.left = `${rect.right + 15}px`;
        labels.radius.style.top = `${rect.top - 25}px`;
    }


    function startFollowLoop() {
        function loop() {
            if (!activeObj) return;
            updateHandlerPositions();
            requestAnimationFrame(loop);
        }
        requestAnimationFrame(loop);
    }

    function startDrag(e, type) {
        if (!activeObj) return;
        isScaling = true;
        dragging = { type, startX: e.clientX, startY: e.clientY };
        const style = getComputedStyle(activeObj);
        dragging.startW = parseFloat(style.width);
        dragging.startH = parseFloat(style.height);
        dragging.startR = parseFloat(style.borderRadius) || 0;
        dragging.startL = parseFloat(style.left) || activeObj.offsetLeft;
        dragging.startT = parseFloat(style.top) || activeObj.offsetTop;
        scale = getCurrentScale();
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', stopDrag);
    }

    function onDrag(e) {
        if (!dragging || !activeObj) return;
        const dx = (e.clientX - dragging.startX) / scale;
        const dy = (e.clientY - dragging.startY) / scale;

        if (dragging.type === 'right') activeObj.style.width = `${dragging.startW + dx}px`;
        else if (dragging.type === 'left') {
            activeObj.style.width = `${dragging.startW - dx}px`;
            activeObj.style.left = `${dragging.startL + dx}px`;
        } else if (dragging.type === 'bottom') activeObj.style.height = `${dragging.startH + dy}px`;
        else if (dragging.type === 'top') {
            activeObj.style.height = `${dragging.startH - dy}px`;
            activeObj.style.top = `${dragging.startT + dy}px`;
        } else if (dragging.type === 'radius') {
            let newR = dragging.startR + (dragging.startY - e.clientY) / 2;
            if (newR < 0) newR = 0;
            activeObj.style.borderRadius = `${newR}px`;
        }

        updateHandlerPositions();
    }

    function stopDrag() {
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', stopDrag);
        dragging = null;
        setTimeout(() => isScaling = false, 100);
    }

    document.addEventListener('mousedown', e => {
        const h = e.target.closest('.gm-handler');
        if (!h) return;
        e.stopPropagation();
        startDrag(e, h.dataset.type);
    });

    return {
        attachTo,
        detach,
        isScaling: () => isScaling,
        getActive: () => activeObj   // <-- getter
    };


})();

window.GMObjectHandler = GMObjectHandler;

// ================= Global click handler =================
document.addEventListener('mousedown', e => {
    const active = GMObjectHandler.getActive(); // buat getter aktif object
    if (!active) return;

    const clickedInsideObj = active.contains(e.target);
    const clickedHandler = e.target.closest('.gm-handler');
    const clickedLabel = e.target.closest('.gm-label');
    const clickedAsideLeft = e.target.closest('.gm-aside-left');
    const clickedMenu = e.target.closest('.gm-object-menu, .gm-object-info-modal');

    if (clickedInsideObj || clickedHandler || clickedLabel || clickedAsideLeft || clickedMenu || GMObjectHandler.isScaling()) {
        // klik di dalam object / handler / label / aside-left / menu / sedang scaling → tetap aktif
        return;
    }

    // klik di luar → detach
    GMObjectHandler.detach();
});
