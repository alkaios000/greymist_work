(function () {

  /* ===============================
     SET SINGLE GM-ACTIVE
  =============================== */
  function setGmActive(target) {
    document.querySelectorAll('.gm-object.gm-active')
      .forEach(el => el.classList.remove('gm-active'));

    if (target) target.classList.add('gm-active');
  }

  /* ===============================
     GET IMMEDIATE PARENT GM-OBJECT
  =============================== */
  function getImmediateParent(active) {
    if (!active) return null;

    // ambil semua ancestor gm-object
    const parents = active.parentElement
      ? active.parentElement.querySelectorAll(':scope .gm-object')
      : [];

    // ❌ cara di atas SALAH → jangan dipakai
    // kita pakai cara BENAR di bawah
  }

})();