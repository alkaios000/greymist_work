// ============================
// === SHORTCUT: Box Shadow ===
// ============================
document.addEventListener("keydown", e => {
  // abaikan jika fokus di input/textarea/contenteditable
  if (
    e.target.matches("input, textarea") ||
    e.target.isContentEditable
  ) return;

  // Alt + S → toggle box-shadow
  if (e.altKey && e.key.toLowerCase() === "s") {
    e.preventDefault();

    const activeEls = document.querySelectorAll(".gm-object.gm-active");
    if (!activeEls.length) return;

    activeEls.forEach(el => {
      const current = el.style.boxShadow;
      const shadowValue = "0px 0px 25px -7px rgba(109, 153, 223, 1)";
      // toggle: aktif → hapus, nonaktif → pasang
      if (current && current.trim() !== "") {
        el.style.boxShadow = "";
      } else {
        el.style.boxShadow = shadowValue;
      }
    });
  }
});
