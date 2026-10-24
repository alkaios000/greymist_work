document.addEventListener("DOMContentLoaded", function () {
    const menus = document.querySelectorAll(".gm-left-menu p");
    const contents = document.querySelectorAll(".gm-content-left-menu");

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
        localStorage.setItem("gm_active_left_menu_index", index);
        if (index === 0) {
            document.dispatchEvent(new CustomEvent("gm-dom-tab-activated"));
            window.dispatchEvent(new CustomEvent("gm-dom-tab-activated"));
        }
    }

    menus.forEach((menu, index) => {
        menu.addEventListener("click", () => activateMenu(index));
    });

    // Set default menu aktif dari localStorage atau default 0
    let savedIndex = parseInt(localStorage.getItem("gm_active_left_menu_index"));
    if (isNaN(savedIndex) || savedIndex < 0 || savedIndex >= menus.length) {
        savedIndex = 0;
    }
    activateMenu(savedIndex);
});
