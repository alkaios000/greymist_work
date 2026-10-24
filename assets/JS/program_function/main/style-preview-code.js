(() => {
    const preview = document.querySelector('.gm-object-style-preview');
    const objectCore = document.querySelector('.gm-object-core');

    if (!preview || !objectCore) return;

    // ===== Properti CSS wajar =====
    const allowedProps = [
        "width", "height", "min-width", "min-height", "max-width", "max-height",
        "background", "background-color", "background-image", "color", "font-size", "font-family",
        "font-weight", "line-height", "letter-spacing", "text-align",
        "margin", "margin-top", "margin-right", "margin-bottom", "margin-left",
        "padding", "padding-top", "padding-right", "padding-bottom", "padding-left",
        "border-radius",
        "overflow", "overflow-x", "overflow-y",
        "top", "right", "bottom", "left", "z-index", "box-shadow", "opacity"
    ];

    // ===== Properti wajib tampil selalu =====
    const mandatoryProps = ["display", "position"];

    // ===== Properti terkait flex/grid =====
    const flexProps = ["flex-direction", "justify-content", "align-items", "gap", "flex-wrap"];
    const gridProps = ["grid-template-columns", "grid-template-rows", "grid-gap", "justify-items", "align-items"];

    function updateStylePreview() {
        const activeObj = objectCore.querySelector('.gm-object.gm-active');

        if (!activeObj) {
            preview.innerHTML = "Klik object untuk melihat style";
            return;
        }

        // ===== dummy baseline =====
        const tag = activeObj.tagName.toLowerCase();
        const dummy = document.createElement(tag);
        document.body.appendChild(dummy);
        const defaultStyle = getComputedStyle(dummy);

        const computed = getComputedStyle(activeObj);
        let styleText = '';

        // ===== Border handling =====
        const borders = ["border-top", "border-right", "border-bottom", "border-left"];
        const borderValues = borders.map(b => computed.getPropertyValue(b));
        const defaultBorders = borders.map(b => defaultStyle.getPropertyValue(b));

        const allSame = borderValues.every((v, i, arr) => v === arr[0]);
        const allDefault = borderValues.every((v, i) => v === defaultBorders[i]);

        if (!allDefault) {
            if (allSame) {
                styleText += `border: ${borderValues[0]};<br>`;
            } else {
                borders.forEach((b, i) => {
                    if (borderValues[i] !== defaultBorders[i]) {
                        styleText += `${b}: ${borderValues[i]};<br>`;
                    }
                });
            }
        }

        // ===== Mandatory props =====
        mandatoryProps.forEach(prop => {
            const value = computed.getPropertyValue(prop);
            styleText += `${prop}: ${value};<br>`;
        });

        // ===== Properti wajar =====
        allowedProps.forEach(prop => {
            if (prop.startsWith("border")) return; // skip border
            const value = computed.getPropertyValue(prop);
            const defaultValue = defaultStyle.getPropertyValue(prop);
            if (value && value !== defaultValue && value !== 'initial' && value !== 'auto') {
                styleText += `${prop}: ${value};<br>`;
            }
        });

        // ===== Properti display-specific =====
        const display = computed.getPropertyValue('display');
        if (display.includes('flex')) {
            flexProps.forEach(prop => {
                const value = computed.getPropertyValue(prop);
                const defaultValue = defaultStyle.getPropertyValue(prop);
                if (value && value !== defaultValue && value !== 'initial' && value !== 'auto') {
                    styleText += `${prop}: ${value};<br>`;
                }
            });
        } else if (display.includes('grid')) {
            gridProps.forEach(prop => {
                const value = computed.getPropertyValue(prop);
                const defaultValue = defaultStyle.getPropertyValue(prop);
                if (value && value !== defaultValue && value !== 'initial' && value !== 'auto') {
                    styleText += `${prop}: ${value};<br>`;
                }
            });
        }

        document.body.removeChild(dummy);
        preview.innerHTML = styleText;
    }

    // ===== Observer super realtime =====
    if (!window.gmStyleObserver) {
        window.gmStyleObserver = new MutationObserver(updateStylePreview);
        window.gmStyleObserver.observe(objectCore, {
            attributes: true,
            subtree: true,
            attributeFilter: ['style', 'class']
        });
    }

    // Inisialisasi langsung
    updateStylePreview();
})();
