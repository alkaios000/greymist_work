// | Shortcut     | Fungsi                        | Mode Toggle / Cycle                                                |
// | ------------ | ----------------------------- | ------------------------------------------------------------------ |
// |   Ctrl + F   | display: flex / block         | toggle                                                             |
// |   Ctrl + J   | justify-content               | cycle → `start → center → between → around → evenly → end → start` |
// |   Ctrl + E   | justify-content & align-items | toggle → center/start                                              |
// |   Ctrl + R   | flex-direction                | toggle → row/column                                                |
// |   Ctrl + Q   | flex-wrap                     | toggle → wrap/nowrap                                               |
// |   Ctrl + G   | gap                           | cycle → 0 / 10 / 20 / 0                                            |

document.addEventListener("keydown", e => {
  if (!e.ctrlKey) return;

  // ✅ Abaikan jika sedang mengetik di input / textarea / contenteditable
  const tag = e.target.tagName?.toLowerCase();
  if (["input", "textarea", "select"].includes(tag) || e.target.isContentEditable) return;

  const activeCore = document.querySelector(".gm-object-core.actived");
  if (!activeCore) return;

  const activeObjects = activeCore.querySelectorAll(".gm-object.gm-active");
  if (!activeObjects.length) return;

  // ✅ preventDefault hanya jika shortcut valid
  const validKeys = ["f", "j", "e", "r", "q", "g"];
  const key = e.key.toLowerCase();
  if (!validKeys.includes(key)) return;

  e.preventDefault(); // ⬅️ sekarang aman, tidak blokir input biasa

  activeObjects.forEach(obj => {
    const style = getComputedStyle(obj);

    switch (key) {
      case "f": {
        const newDisplay = style.display === "flex" ? "block" : "flex";
        obj.style.display = newDisplay;
        break;
      }
      case "j": {
        const states = [
          "flex-start",
          "center",
          "space-between",
          "space-around",
          "space-evenly",
          "flex-end"
        ];
        const current = style.justifyContent;
        const next =
          states[(states.indexOf(current) + 1) % states.length] || "flex-start";
        obj.style.justifyContent = next;
        break;
      }
      case "e": {
        const isCentered =
          style.justifyContent === "center" && style.alignItems === "center";
        obj.style.justifyContent = isCentered ? "flex-start" : "center";
        obj.style.alignItems = isCentered ? "flex-start" : "center";
        break;
      }
      case "r": {
        const newDir = style.flexDirection === "row" ? "column" : "row";
        obj.style.flexDirection = newDir;
        break;
      }
      case "q": {
        const newWrap = style.flexWrap === "wrap" ? "nowrap" : "wrap";
        obj.style.flexWrap = newWrap;
        break;
      }
      case "g": {
        const gapValues = ["0px", "10px", "20px"];
        const current = style.gap;
        const next =
          gapValues[(gapValues.indexOf(current) + 1) % gapValues.length] ||
          "10px";
        obj.style.gap = next;
        break;
      }
    }
  });
});
