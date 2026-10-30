/**
 * File: assets/js/main.js
 * Fungsi: Inisialisasi interaksi global (toggle sidebar responsif, penutupan otomatis alert).
 */

document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (backdrop) {
                backdrop.classList.toggle('active');
            }
        });
    }

    if (backdrop && sidebar) {
        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('active');
        });
    }

    // Tutup otomatis pesan flash setelah 5 detik
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss="true"]');
    alerts.forEach(function (alertEl) {
        setTimeout(function () {
            alertEl.style.opacity = '0';
            alertEl.style.transition = 'opacity 0.25s ease';
            setTimeout(function () {
                alertEl.remove();
            }, 260);
        }, 5000);
    });
});

