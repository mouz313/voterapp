/* VoterApp custom JS */

/* Sidebar toggle for mobile */
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('open');
        });
    }

    /* Close sidebar when clicking a link on mobile */
    sidebar?.querySelectorAll('.sidebar-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });
});

/* Toastr default config */
if (typeof toastr !== 'undefined') {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 4000,
        extendedTimeOut: 2000,
    };
}

/* Show a toast from a data attribute on any element with [data-toast] */
document.addEventListener('DOMContentLoaded', function () {
    const triggers = document.querySelectorAll('[data-toast]');
    triggers.forEach(function (el) {
        el.addEventListener('click', function () {
            const type = el.getAttribute('data-toast') || 'info';
            const message = el.getAttribute('data-toast-message') || 'Done';
            if (toastr && toastr[type]) {
                toastr[type](message);
            }
        });
    });
});

/* Demo: show a welcome toast automatically */
document.addEventListener('DOMContentLoaded', function () {
    if (typeof toastr !== 'undefined') {
        const welcome = document.getElementById('welcomeToast');
        if (welcome) {
            toastr.success(welcome.getAttribute('data-message') || 'Welcome back!');
        }
    }
});

/* Confirm dialog helper for delete/reset actions */
function confirmAction(message, callback) {
    if (window.confirm(message || 'Are you sure?')) {
        callback();
    }
}
