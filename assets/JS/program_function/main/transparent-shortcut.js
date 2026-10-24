// =====================================
// === CTRL + T → background transparent ===
// =====================================

document.addEventListener("keydown", e => {
  // hanya tangani jika Ctrl ditekan
  if (!e.altKey) return;

  // abaikan jika sedang mengetik di input / textarea / contenteditable
  const tag = e.target.tagName?.toLowerCase();
  if (["input", "textarea", "select"].includes(tag) || e.target.isContentEditable) return;

  // pastikan core & object aktif
  const activeCore = document.querySelector(".gm-object-core.actived");
  if (!activeCore) return;

  const activeObjects = activeCore.querySelectorAll(".gm-object.gm-active");
  if (!activeObjects.length) return;

  const key = e.key.toLowerCase();
  if (key !== "t") return;

  // mencegah tab baru browser
  e.preventDefault();

  // set background transparent untuk semua gm-active
  activeObjects.forEach(obj => {
    obj.style.background = "transparent";
  });
});
