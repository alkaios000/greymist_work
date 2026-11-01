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
            localStorage.setItem("gm_active_box_menu_index", index);
        }

        menus.forEach((menu, index) => {
            menu.addEventListener("click", () => activateMenu(index));
        });

        // Set default menu aktif dari localStorage atau default 0
        let savedIndex = parseInt(localStorage.getItem("gm_active_box_menu_index"));
        if (isNaN(savedIndex) || savedIndex < 0 || savedIndex >= menus.length) {
            savedIndex = 0;
        }
        activateMenu(savedIndex);

        window.gm_syncBoxMenu = function () {
            let activeIdx = Array.from(menus).findIndex(m => m.classList.contains("gm-clicked"));
            if (activeIdx < 0) {
                activeIdx = parseInt(localStorage.getItem("gm_active_box_menu_index"));
                if (isNaN(activeIdx) || activeIdx < 0 || activeIdx >= menus.length) {
                    activeIdx = 0;
                }
            }
            activateMenu(activeIdx);
        };
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initBoxMenu);
    } else {
        initBoxMenu();
    }
})();
