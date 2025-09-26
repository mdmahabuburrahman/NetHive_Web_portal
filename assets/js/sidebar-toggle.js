// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const body = document.body;
    
    // Handle minimize toggle (desktop)
    const minimizeToggle = document.querySelector('[data-bs-toggle="minimize"]');
    if (minimizeToggle) {
        minimizeToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-icon-only');
        });
    }
    
    // Handle offcanvas toggle (mobile)
    const offcanvasToggle = document.querySelector('[data-bs-toggle="offcanvas"]');
    if (offcanvasToggle) {
        offcanvasToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Handle fullscreen toggle
    const fullscreenButton = document.getElementById('fullscreen-button');
    if (fullscreenButton) {
        fullscreenButton.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
                fullscreenButton.className = 'fa fa-compress';
            } else {
                document.exitFullscreen();
                fullscreenButton.className = 'fa fa-square-o';
            }
        });
    }
    
    // Listen for fullscreen changes (ESC key, etc.)
    document.addEventListener('fullscreenchange', function() {
        if (fullscreenButton) {
            if (document.fullscreenElement) {
                fullscreenButton.className = 'fa fa-compress';
            } else {
                fullscreenButton.className = 'fa fa-square-o';
            }
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 991) {
            if (!sidebar.contains(event.target) && !offcanvasToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
});