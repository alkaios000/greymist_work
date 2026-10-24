document.addEventListener("DOMContentLoaded", () => {

  const editBtn = document.querySelector(".gm-edit-project-name");
  const projectNameEl = document.querySelector(".gm-project-name");

  if (!editBtn || !projectNameEl) return;

  const params = new URLSearchParams(location.search);
  const projectId = params.get("project_id");
  if (!projectId) return;

  editBtn.addEventListener("click", () => {

    // cegah input ganda
    if (projectNameEl.querySelector("input")) return;

    const oldName = projectNameEl.textContent.trim();
    const input = document.createElement("input");

    input.type = "text";
    input.value = oldName;
    input.className = "gm-input-project-name";

    projectNameEl.textContent = "";
    projectNameEl.appendChild(input);
    input.focus();
    input.select();

    const save = () => {
      const newName = input.value.trim();

      // rollback jika kosong
      if (!newName || newName === oldName) {
        projectNameEl.textContent = oldName;
        return;
      }

      fetch("api/rename_project.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `project_id=${projectId}&project_name=${encodeURIComponent(newName)}`
      })
        .then(r => r.json())
        .then(res => {
          if (res.status !== "OK") {
            projectNameEl.textContent = oldName;
            return;
          }
          projectNameEl.textContent = res.project_name;
        })
        .catch(() => {
          projectNameEl.textContent = oldName;
        });
    };

    input.addEventListener("blur", save);
    input.addEventListener("keydown", e => {
      if (e.key === "Enter") input.blur();
      if (e.key === "Escape") {
        projectNameEl.textContent = oldName;
      }
    });
  });

});
