document.addEventListener("DOMContentLoaded", () => {

    let copiedFrameId = null;

    const thumbs = document.getElementById("gm-frame-thumbnails");
    const preview = document.getElementById("gm-frame-preview");

    thumbs.addEventListener("contextmenu", e => {
        e.preventDefault();

        const thumb = e.target.closest(".gm-frame-thumbnail");
        if (!thumb) return;

        const frameId = thumb.dataset.frame;

        let menu = document.getElementById("gm-context-menu");
        if (!menu) {
            menu = document.createElement("div");
            menu.id = "gm-context-menu";
            menu.className = "gm-context-menu";
            document.body.appendChild(menu);
        }

        menu.innerHTML = `
      <ul>
        <li id="gm-copy">Copy</li>
        <li id="gm-rename">Rename</li>
        <li id="gm-delete-frame">Delete</li>
      </ul>
    `;

        menu.style.top = e.pageY + "px";
        menu.style.left = e.pageX + "px";
        menu.style.display = "block";

        // COPY
        menu.querySelector("#gm-copy").onclick = () => {
            fetch("api/copy_frame.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "frame_id=" + frameId
            })
                .then(r => r.json())
                .then(res => {
                    createFrameUI(res.frame, false);
                    if (menu) menu.style.display = "none";
                    
                    const newThumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${res.frame.frame_id}"]`);
                    if(newThumb) newThumb.click();
                });
        };

        // RENAME
        menu.querySelector("#gm-rename").onclick = (e) => {
            e.stopPropagation();

            const nameEl = thumb.querySelector(".gm-frame-name");
            const newName = prompt("Rename frame:", nameEl.textContent);
            if (!newName) return;

            fetch("api/rename_frame.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `frame_id=${encodeURIComponent(frameId)}&frame_name=${encodeURIComponent(newName)}`
            })
                .then(r => r.json())
                .then(res => {
                    console.log("RENAME RES:", res);

                    if (res.status !== "OK") {
                        alert("Rename gagal");
                        return;
                    }

                    nameEl.textContent = newName;
                    menu.style.display = "none";
                });
        };

        menu.querySelector("#gm-delete-frame").onclick = () => {
            // DELETE
            fetch("api/delete_frame.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    frame_id: Number(frameId)
                })
            })
                .then(r => r.json())
                .then(res => {
                    console.log("DELETE RES:", res);

                    if (res.status !== "DELETED") {
                        console.error("Delete gagal", res);
                        return;
                    }

                    thumb.remove();
                    document.getElementById(`object-core-${frameId}`)?.remove();

                    const next =
                        document.querySelector(".gm-frame-thumbnail.actived") ||
                        document.querySelector(".gm-frame-thumbnail");

                    next?.click();
                });
        }

        console.log("DELETE FRAME ID:", frameId);

    });

    document.addEventListener("click", () => {
        const menu = document.getElementById("gm-context-menu");
        if (menu) menu.style.display = "none";
    });


});



document.addEventListener("DOMContentLoaded", () => {

    const addBtn = document.getElementById("gm-add-frame-btn");
    if (!addBtn) return;

    const params = new URLSearchParams(location.search);
    const projectId = params.get("project_id");

    addBtn.addEventListener("click", () => {

        fetch("api/add_frame.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: "project_id=" + encodeURIComponent(projectId)
        })
            .then(r => r.json())
            .then(res => {
                if (res.status !== "OK") {
                    console.error("ADD FRAME FAILED", res);
                    return;
                }

                // buat UI frame + core
                createFrameUI(res.frame, false);

                // Aktifkan frame baru dengan menekan thumbnailnya
                const newThumb = document.querySelector(`.gm-frame-thumbnail[data-frame="${res.frame.frame_id}"]`);
                if(newThumb) newThumb.click();
            });

    });


});



