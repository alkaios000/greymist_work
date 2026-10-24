(function () {

    /* ===============================
       SET SINGLE GM-ACTIVE
    =============================== */
    function setGmActive(target) {
        document.querySelectorAll('.gm-object.gm-active')
            .forEach(el => el.classList.remove('gm-active'));

        if (target) target.classList.add('gm-active');
    }

    /* ===============================
       MAKE AS LINK
    =============================== */
    function makeAsLink() {
        const active = document.querySelector('.gm-object.gm-active');
        if (!active) return;

        // jika parent sudah <a.gm-object>, hentikan
        if (
            active.parentElement &&
            active.parentElement.tagName === 'A' &&
            active.parentElement.classList.contains('gm-object')
        ) {
            return;
        }

        const parent = active.parentElement;
        if (!parent) return;

        // buat <a> sebagai gm-object
        const link = document.createElement('a');
        link.href = '#';
        link.classList.add('gm-object', 'gm-link-wrapper');
        link.style.textDecoration = 'none';
        link.style.width = 'fit-content';
        
        // cegah navigasi di editor
        link.addEventListener('click', e => e.preventDefault());

        // sisipkan <a> di posisi object
        parent.insertBefore(link, active);

        // pindahkan object ke dalam <a>
        link.appendChild(active);

        // pindahkan gm-active ke <a>
        active.classList.remove('gm-active');
        setGmActive(link);
    }

    /* ===============================
       BUTTON HANDLER
    =============================== */
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('.gm-make-as-link');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();

        makeAsLink();

    }, true);

})();
