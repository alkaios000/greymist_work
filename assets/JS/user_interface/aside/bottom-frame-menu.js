document.addEventListener("DOMContentLoaded", function () {
    const menus = document.querySelectorAll(".gm-menu-bottom-frame div");
    const contents = document.querySelectorAll(".gm-content-bottom-menu");

    function activateMenu(index) {
        menus.forEach((menu, i) => {
            if (i === index) {
                menu.classList.add("gm-clicked");
                contents[i].classList.add("gm-clicked");
            } else {
                menu.classList.remove("gm-clicked");
                contents[i].classList.remove("gm-clicked");
            }
        });
    }

    menus.forEach((menu, index) => {
        menu.addEventListener("click", () => activateMenu(index));
    });

    // Set default menu aktif
    activateMenu(0);
});
