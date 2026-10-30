/**
 * File: assets/js/components/notification.js
 * Fungsi: Utilitas pemberitahuan ringan (toast) untuk aksi asinkron.
 */

window.GreymistNotify = {
    show: function (message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'alert alert-' + type;
        toast.style.position = 'fixed';
        toast.style.bottom = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '2000';
        toast.style.minWidth = '260px';
        toast.style.boxShadow = '0 4px 12px rgba(15, 23, 42, 0.12)';
        toast.innerHTML = '<div class="alert-content"><i class="fas fa-info-circle"></i><span></span></div>';
        toast.querySelector('span').textContent = message;

        document.body.appendChild(toast);
        setTimeout(function () {
            toast.remove();
        }, 3500);
    }
};

