// =====================================
// === Display Settings (data-display) ==
// =====================================
document.addEventListener("DOMContentLoaded", () => {
  const settingsSection = document.querySelector(".gm-settings-section");
  const displaySelect    = document.getElementById("gm-display-select");

  // ambil semua panel berdasarkan atribut data-display
  const panels = Array.from(document.querySelectorAll(".gm-display-panel"))
    .reduce((acc, el) => {
      const key = (el.dataset.display || "").trim();
      if (key) acc[key] = el;
      return acc;
    }, {});

  function hideAllPanels() {
    Object.values(panels).forEach(p => p && (p.style.display = "none"));
  }

  function getActiveObject() {
    return document.querySelector(".gm-object.gm-active");
  }

  // mapping deterministik: computed display -> panel key
  function mapDisplayToKey(display) {
    if (!display) return "";
    display = display.trim();
    if (display === "flex" || display === "inline-flex") return "flex";
    if (display === "grid" || display === "inline-grid") return "grid";
    if (display === "block") return "block";
    if (display === "inline" || display === "inline-block") return "inline";
    // fallback: if there's a panel that exactly matches the computed string, return it
    if (panels[display]) return display;
    return ""; // no matching panel
  }

  // update UI berdasarkan elemen aktif saat ini
  let lastActive = null;
  let lastDisplayKey = null;
  function updateDisplayUI(force = false) {
    const active = getActiveObject();

    if (!active) {
      settingsSection.style.display = "none";
      hideAllPanels();
      lastActive = null;
      lastDisplayKey = null;
      displaySelect.value = "";
      return;
    }

    // jika active sama seperti sebelumnya dan tidak dipaksa update, cek apakah style/display berubah
    if (active === lastActive && !force) {
      return;
    }

    lastActive = active;

    // baca computed display (tepat & pasti)
    const computedDisplay = getComputedStyle(active).display || "";
    const key = mapDisplayToKey(computedDisplay);

    // set dropdown: prioritaskan exact computed value jika tersedia di option
    const opts = Array.from(displaySelect.options).map(o => o.value);
    displaySelect.value = opts.includes(computedDisplay) ? computedDisplay : (opts.includes(key) ? key : "");

    // tampilkan section dan panel yang benar
    settingsSection.style.display = "block";
    hideAllPanels();

    if (key && panels[key]) {
      panels[key].style.display = "block";
      lastDisplayKey = key;
    } else {
      // tidak ada panel cocok -> sembunyikan semua
      lastDisplayKey = null;
    }
  }

  // ketika user ubah select -> terapkan ke elemen aktif dan paksa update UI
  displaySelect.addEventListener("change", (e) => {
    const active = getActiveObject();
    if (!active) return;
    const newVal = e.target.value;
    active.style.display = newVal;
    // paksa re-read computed style (requestAnimationFrame agar style inline sudah dipakai)
    requestAnimationFrame(() => updateDisplayUI(true));
  });

  // paling ringan: hanya observasi perubahan class/style pada elemen .gm-object
  const observer = new MutationObserver((mutations) => {
    let shouldUpdate = false;
    for (const m of mutations) {
      // hanya respons ketika target adalah .gm-object (atau child kelas berubah pada .gm-object)
      const target = m.target;
      if (!target) continue;
      // jika attribute change pada node yang adalah atau berada di .gm-object
      if (m.type === "attributes") {
        const obj = target.classList?.contains?.call ? target : null;
        // jika target adalah .gm-object, atau parentnya .gm-object (safety)
        if (target.classList && target.classList.contains("gm-object")) {
          shouldUpdate = true;
          break;
        }
        const parentObj = target.closest && target.closest(".gm-object");
        if (parentObj) {
          shouldUpdate = true;
          break;
        }
      }
    }
    if (shouldUpdate) {
      // gunakan rAF untuk sinkronisasi aman
      requestAnimationFrame(() => updateDisplayUI());
    }
  });

  observer.observe(document.body, {
    attributes: true,
    subtree: true,
    attributeFilter: ["class", "style"]
  });

  // juga tangani klik pemilihan elemen (safely wait for .gm-active assignment)
  // gabungkan penanganan klik: klik pada .gm-object atau klik di luar
  document.addEventListener("click", (e) => {
    // beri sedikit delay agar mekanisme seleksi internal (.gm-active) selesai diperbarui
    setTimeout(() => {
      const activeNow = getActiveObject();

      // jika ada active object tetap ada -> tampilkan/refresh panel
      if (activeNow) {
        // paksa update UI untuk memastikan dropdown & panel sinkron
        updateDisplayUI(true);
        return;
      }

      // tidak ada active -> sembunyikan section (tetap aman jika klik di dalam settings)
      // namun kalau klik terjadi di dalam settings atau display menu, jangan sembunyikan
      const clickedInsideSettings = e.target.closest && (e.target.closest(".gm-settings-section") || e.target.closest(".gm-display-menu"));
      if (!clickedInsideSettings) {
        settingsSection.style.display = "none";
        hideAllPanels();
        lastActive = null;
        lastDisplayKey = null;
        displaySelect.value = "";
      }
    }, 20); // 20ms cukup untuk update class .gm-active oleh mekanisme selection
  });


  // init
  updateDisplayUI(true);
});
