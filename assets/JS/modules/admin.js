/**
 * File: assets/js/modules/admin.js
 * Fungsi: Logika interaksi halaman Panel Administrator (edit user, reset password, detail proyek).
 */

window.openAdminEditUserModal = function (user) {
    const fields = {
        editUserId: user.user_id,
        editUsername: user.username,
        editEmail: user.email,
        editNickname: user.nickname || '',
        editPhone: user.phone || ''
    };
    Object.keys(fields).forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.value = fields[id];
    });
    const adminCheck = document.getElementById('editIsAdmin');
    if (adminCheck) {
        adminCheck.checked = parseInt(user.is_admin, 10) === 1;
    }
    window.GreymistModal.open('adminEditUserModal');
};

window.openAdminResetPasswordModal = function (userId, username) {
    const idEl = document.getElementById('resetPasswordUserId');
    const labelEl = document.getElementById('resetPasswordUsername');
    if (idEl) idEl.value = userId;
    if (labelEl) labelEl.textContent = username;
    window.GreymistModal.open('adminResetPasswordModal');
};

