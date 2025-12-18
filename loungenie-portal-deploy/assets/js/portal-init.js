/**
 * Portal Initialization
 * Handles sidebar toggle and other DOM interactions
 * 
 * @package LounGenie Portal
 */

(function () {
    'use strict';

    /**
     * Initialize portal functionality
     */
    function initPortal() {
        // Mobile sidebar toggle
        const sidebarToggle = document.getElementById('lgp-sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                const sidebar = document.querySelector('.lgp-sidebar');
                if (sidebar) {
                    sidebar.classList.toggle('lgp-sidebar-open');
                }
            });
        }

        // Close sidebar when clicking a nav link on mobile
        const navLinks = document.querySelectorAll('.lgp-nav-link');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                const sidebar = document.querySelector('.lgp-sidebar');
                if (sidebar && window.innerWidth < 1024) {
                    sidebar.classList.remove('lgp-sidebar-open');
                }
            });
        });

        // Edit Company button alert
        const editCompanyBtn = document.getElementById('lgp-edit-company-btn');
        if (editCompanyBtn) {
            editCompanyBtn.addEventListener('click', function (e) {
                e.preventDefault();
                alert('Edit company feature coming soon');
            });
        }

        // Reply modal controls
        const replyModalClose = document.getElementById('lgp-reply-modal-close');
        const replyModalCancel = document.getElementById('lgp-reply-modal-cancel');
        const replyModal = document.getElementById('reply-modal');

        if (replyModalClose && replyModal) {
            replyModalClose.addEventListener('click', function () {
                replyModal.style.display = 'none';
            });
        }

        if (replyModalCancel && replyModal) {
            replyModalCancel.addEventListener('click', function () {
                replyModal.style.display = 'none';
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPortal);
    } else {
        initPortal();
    }
})();
