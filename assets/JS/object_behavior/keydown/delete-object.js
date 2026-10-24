// Listener Delete key
document.addEventListener("keydown", e => {
    if (e.key === "Delete") deleteActiveObjects();
});

// Listener tombol delete dari menu
document.querySelectorAll(".gm-delete-object").forEach(btn => {
    btn.addEventListener("click", () => deleteActiveObjects());
});

// Fungsi deleteActiveObjects tetap seperti lama
function deleteActiveObjects() {
    const activeObjects = Array.from(document.querySelectorAll(".gm-object.gm-active"));
    if (activeObjects.length === 0) return;

    const withChildren = activeObjects.filter(obj => obj.querySelector(".gm-object"));
    const withoutChildren = activeObjects.filter(obj => !obj.querySelector(".gm-object"));

    withoutChildren.forEach(obj => obj.remove());
    // perbarui DOM tree
    renderTree();

    if (withChildren.length > 0) showDeletePopup(withChildren);
    // perbarui DOM tree
    renderTree();
}

// Fungsi popup tetap
function showDeletePopup(objects) {
    const overlay = document.querySelector(".gm-popup-overlay");
    overlay.style.display = "flex";

    overlay.querySelector(".gm-delete-this").onclick = () => {
        objects.forEach(obj => {
            const parent = obj.parentElement;
            const children = Array.from(obj.children);
            children.forEach(child => parent.insertBefore(child, obj));
            obj.remove();
            renderTree();
        });
        overlay.style.display = "none";
    };

    overlay.querySelector(".gm-delete-all").onclick = () => {
        objects.forEach(obj => obj.remove());
        overlay.style.display = "none";
        renderTree();

    };
}
