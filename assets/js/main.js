/**
 * Student Information System (SIS) - Main Client JavaScript
 * Vanilla JavaScript (ES6+) - Zero framework dependencies
 */

document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alert notifications after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    });

    // Mobile sidebar toggle handler
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // Confirmation dialog helper for danger actions
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = btn.getAttribute('data-confirm') || 'Are you sure you want to proceed with this action?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Print button handler
    const printButtons = document.querySelectorAll('.btn-print-trigger');
    printButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });
});
