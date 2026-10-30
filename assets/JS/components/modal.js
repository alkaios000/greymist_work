/**
 * File: assets/js/components/modal.js
 * Fungsi: Pengendali pembukaan dan penutupan dialog modal secara modular.
 */

window.GreymistModal = {
    open: function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    },
    close: function (modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // Tutup modal saat klik tombol close atau area overlay luar
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-modal-close');
            if (targetId) {
                window.GreymistModal.close(targetId);
            } else {
                const parentModal = btn.closest('.modal-overlay');
                if (parentModal) parentModal.classList.remove('active');
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
});

