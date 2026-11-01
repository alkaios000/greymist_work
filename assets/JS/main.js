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

    // Menutup dropdown menu proyek saat klik di luar
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.project-card-menu')) {
            document.querySelectorAll('.project-card-menu.open').forEach(function (menu) {
                menu.classList.remove('open');
            });
        }
    });

    // Menutup dropdown menu proyek saat tekan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.project-card-menu.open').forEach(function (menu) {
                menu.classList.remove('open');
            });
        }
    });
});

/**
 * Toggle menu dropdown titik 3 pada kartu proyek
 */
function toggleProjectMenu(event, btn) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const cardMenu = btn.closest('.project-card-menu');
    if (!cardMenu) return;

    const wasOpen = cardMenu.classList.contains('open');

    // Tutup semua menu lain yang sedang terbuka
    document.querySelectorAll('.project-card-menu.open').forEach(function (menu) {
        menu.classList.remove('open');
    });

    if (!wasOpen) {
        cardMenu.classList.add('open');
    }
}
window.toggleProjectMenu = toggleProjectMenu;

