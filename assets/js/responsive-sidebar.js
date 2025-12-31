/**
 * LounGenie Portal - Responsive Sidebar Controller
 * Handles hamburger menu, off-canvas drawer, and sidebar collapse
 * 
 * @package LounGenie Portal
 * @version 1.7.1
 */

(function ($) {
    'use strict';

    class ResponsiveSidebar {
        constructor() {
            this.sidebar = $('.lgp-sidebar');
            this.overlay = null;
            this.hamburger = null;
            this.isOpen = false;
            this.breakpoints = {
                mobile: 767,
                tablet: 1199
            };

            this.init();
        }

        init() {
            this.createHamburgerButton();
            this.createOverlay();
            this.bindEvents();
            this.checkBreakpoint();

            console.log('✅ ResponsiveSidebar initialized');
        }

        /**
         * Create hamburger menu button
         */
        createHamburgerButton() {
            if ($('.lgp-hamburger-btn').length > 0) return;

            const hamburger = $('<button>', {
                class: 'lgp-hamburger-btn',
                'aria-label': 'Toggle navigation menu',
                'aria-expanded': 'false',
                html: `
                    <span></span>
                    <span></span>
                    <span></span>
                `
            });

            // Insert after logo in header
            $('.lgp-logo').after(hamburger);
            this.hamburger = hamburger;
        }

        /**
         * Create overlay for mobile/tablet
         */
        createOverlay() {
            if ($('.lgp-sidebar-overlay').length > 0) {
                this.overlay = $('.lgp-sidebar-overlay');
                return;
            }

            this.overlay = $('<div>', {
                class: 'lgp-sidebar-overlay'
            });

            $('body').append(this.overlay);
        }

        /**
         * Bind all event listeners
         */
        bindEvents() {
            // Hamburger click
            $(document).on('click', '.lgp-hamburger-btn', (e) => {
                e.preventDefault();
                this.toggleSidebar();
            });

            // Overlay click (close sidebar)
            this.overlay.on('click', () => {
                this.closeSidebar();
            });

            // Window resize
            let resizeTimer;
            $(window).on('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    this.checkBreakpoint();
                }, 250);
            });

            // Keyboard navigation (ESC key)
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.closeSidebar();
                }
            });

            // Close on navigation link click (mobile only)
            this.sidebar.on('click', '.lgp-nav-link', () => {
                if (this.getCurrentBreakpoint() === 'mobile') {
                    this.closeSidebar();
                }
            });
        }

        /**
         * Toggle sidebar open/closed
         */
        toggleSidebar() {
            if (this.isOpen) {
                this.closeSidebar();
            } else {
                this.openSidebar();
            }
        }

        /**
         * Open sidebar
         */
        openSidebar() {
            const breakpoint = this.getCurrentBreakpoint();

            if (breakpoint === 'mobile') {
                this.sidebar.addClass('mobile-open');
                this.overlay.addClass('active');
                $('body').css('overflow', 'hidden'); // Prevent scroll
            } else if (breakpoint === 'tablet') {
                this.sidebar.addClass('tablet-open');
                this.overlay.addClass('active');
            }

            this.hamburger.addClass('active')
                .attr('aria-expanded', 'true');

            this.isOpen = true;

            // Announce to screen readers
            this.announceToScreenReader('Navigation menu opened');
        }

        /**
         * Close sidebar
         */
        closeSidebar() {
            this.sidebar.removeClass('mobile-open tablet-open');
            this.overlay.removeClass('active');
            this.hamburger.removeClass('active')
                .attr('aria-expanded', 'false');

            $('body').css('overflow', ''); // Restore scroll
            this.isOpen = false;

            // Announce to screen readers
            this.announceToScreenReader('Navigation menu closed');
        }

        /**
         * Get current breakpoint
         * @returns {string} 'mobile', 'tablet', or 'desktop'
         */
        getCurrentBreakpoint() {
            const width = $(window).width();

            if (width <= this.breakpoints.mobile) {
                return 'mobile';
            } else if (width <= this.breakpoints.tablet) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }

        /**
         * Check breakpoint and adjust UI
         */
        checkBreakpoint() {
            const breakpoint = this.getCurrentBreakpoint();

            // Close sidebar when switching to desktop
            if (breakpoint === 'desktop' && this.isOpen) {
                this.closeSidebar();
            }

            // Add breakpoint class to body
            $('body')
                .removeClass('lgp-mobile lgp-tablet lgp-desktop')
                .addClass(`lgp-${breakpoint}`);

            console.log(`📱 Breakpoint: ${breakpoint} (${$(window).width()}px)`);
        }

        /**
         * Announce changes to screen readers
         * @param {string} message
         */
        announceToScreenReader(message) {
            if (!$('#lgp-sr-announcer').length) {
                $('body').append('<div id="lgp-sr-announcer" class="sr-only" aria-live="polite"></div>');
            }

            $('#lgp-sr-announcer').text(message);

            // Clear after announcement
            setTimeout(() => {
                $('#lgp-sr-announcer').text('');
            }, 1000);
        }
    }

    // Initialize on document ready
    $(document).ready(function () {
        if ($('.lgp-sidebar').length > 0) {
            window.lgpResponsiveSidebar = new ResponsiveSidebar();
        }
    });

})(jQuery);
