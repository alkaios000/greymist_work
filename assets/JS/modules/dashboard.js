/**
 * File: assets/js/modules/dashboard.js
 * Fungsi: Logika interaksi halaman pengguna (modal ubah nama proyek, modal konfirmasi hapus).
 */

window.openRenameProjectModal = function (projectId, currentName) {
    const idInput = document.getElementById('renameProjectId');
    const nameInput = document.getElementById('renameProjectName');
    if (idInput && nameInput) {
        idInput.value = projectId;
        nameInput.value = currentName;
        window.GreymistModal.open('renameProjectModal');
        setTimeout(function () {
            nameInput.focus();
            nameInput.select();
        }, 50);
    }
};

window.openConfirmActionModal = function (options) {
    const form = document.getElementById('globalConfirmForm');
    const titleEl = document.getElementById('globalConfirmTitle');
    const msgEl = document.getElementById('globalConfirmMessage');
    const idInput = document.getElementById('globalConfirmProjectId');
    const submitBtn = document.getElementById('globalConfirmSubmitBtn');

    if (!form) return;

    if (options.actionUrl) form.action = options.actionUrl;
    if (titleEl) titleEl.textContent = options.title || 'Konfirmasi Tindakan';
    if (msgEl) msgEl.textContent = options.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
    if (idInput) idInput.value = options.projectId || '';
    if (submitBtn) {
        submitBtn.textContent = options.confirmText || 'Lanjutkan';
        submitBtn.className = 'btn ' + (options.buttonClass || 'btn-danger');
    }

    window.GreymistModal.open('globalConfirmModal');
};

