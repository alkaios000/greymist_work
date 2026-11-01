(function () {
    function initRightMenu() {
        const menus = document.querySelectorAll(".gm-right-menu p");
        const contents = document.querySelectorAll(".gm-content-right-menu");

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
            localStorage.setItem("gm_active_right_menu_index", index);

            // Selalu reset scroll position agar tab baru tidak tertinggal scroll dari tab sebelumnya
            const container = document.querySelector(".gm-bottom-menu-right");
            if (container) {
                container.scrollTop = 0;
            }

            // Jika tab Box (index 3) diaktifkan, pastikan sub-tab pertama aktif dan terlihat
            if (index === 3) {
                if (typeof window.gm_syncBoxMenu === "function") {
                    window.gm_syncBoxMenu();
                } else {
                    const boxTabs = document.querySelectorAll(".gm-box-menu > div");
                    const boxPanels = document.querySelectorAll(".gm-content-box-menu");
                    if (boxTabs.length && boxPanels.length) {
                        const hasActive = Array.from(boxTabs).some(t => t.classList.contains("gm-clicked"));
                        if (!hasActive) {
                            boxTabs[0].classList.add("gm-clicked");
                            boxPanels[0].classList.add("gm-clicked");
                        }
                    }
                }
            }
        }

        menus.forEach((menu, index) => {
            menu.addEventListener("click", () => activateMenu(index));
        });

        // Set default menu aktif dari localStorage atau default 0
        let savedIndex = parseInt(localStorage.getItem("gm_active_right_menu_index"));
        if (isNaN(savedIndex) || savedIndex < 0 || savedIndex >= menus.length) {
            savedIndex = 0;
        }
        activateMenu(savedIndex);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initRightMenu);
    } else {
        initRightMenu();
    }
})();
