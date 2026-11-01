(function () {
    function initBoxMenu() {
        const menus = document.querySelectorAll(".gm-box-menu > div");
        const contents = document.querySelectorAll(".gm-content-box-menu");

        if (!menus.length || !contents.length) return;

        function activateMenu(index) {
            menus.forEach((menu, i) => {
                if (i === index) {
                    menu.classList.add("gm-clicked");
                    if (contents[i]) contents[i].classList.add("gm-clicked");
                } else {
                    menu.classList.remove("gm-clicked");
                    if (contents[i]) contents[i].classList.remove("gm-clicked");
                }
            });
        }

        menus.forEach((menu, index) => {
            menu.addEventListener("click", () => activateMenu(index));
        });

        // Cek jika sudah ada yang aktif, jika belum aktifkan index 0
        const activeIdx = Array.from(menus).findIndex(m => m.classList.contains("gm-clicked"));
        activateMenu(activeIdx >= 0 ? activeIdx : 0);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initBoxMenu);
    } else {
        initBoxMenu();
    }
})();
