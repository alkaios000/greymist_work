document.addEventListener("DOMContentLoaded", () => {
  const navButtons = document.querySelectorAll(".gm-nav-btn");
  const groups = document.querySelectorAll(".gm-template-group");
  const searchInput = document.getElementById("gm-template-search");

  // Fungsi tampilkan kategori
  function showCategory(category) {
    groups.forEach(group => {
      group.style.display = group.dataset.category === category ? "grid" : "none";
    });
  }

  // Inisialisasi tampilan awal
  showCategory("box");

  // Event klik tombol nav
  navButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      navButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
      const category = btn.dataset.category;
      showCategory(category);
      searchInput.value = ""; // reset pencarian
    });
  });

  // Fungsi pencarian
 searchInput.addEventListener("input", () => {
  const query = searchInput.value.toLowerCase().trim();
  const activeBtn = document.querySelector(".gm-nav-btn.active");
  const activeCategory = activeBtn?.dataset.category;

  const allItems = document.querySelectorAll(".gm-object-dropable");

  if (query === "") {
    // ✅ Jika input kosong, tampilkan kategori aktif saja
    groups.forEach(group => {
      group.style.display = group.dataset.category === activeCategory ? "grid" : "none";
    });

    // Semua item dalam kategori aktif tampil kembali
    allItems.forEach(item => (item.style.display = ""));
    return;
  }

  // ✅ Saat pencarian aktif, tampilkan semua group agar hasil bisa muncul lintas kategori
  groups.forEach(group => (group.style.display = "grid"));

  // ✅ Filter item di semua group
  allItems.forEach(item => {
    const text = item.textContent.toLowerCase();
    const type = item.dataset.type?.toLowerCase() || "";
    if (text.includes(query) || type.includes(query)) {
      item.style.display = ""; // gunakan default grid item
    } else {
      item.style.display = "none";
    }
  });


  });
});
