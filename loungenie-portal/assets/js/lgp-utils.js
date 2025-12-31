/**
 * LounGenie Portal - Shared Utilities Library
 * Version: 1.8.0 (Refactored & Optimized)
 * 
 * Consolidated helper functions used across the portal.
 * Eliminates code duplication and provides consistent behavior.
 * 
 * @package LounGenie Portal
 */

(function (window) {
    'use strict';

    /**
     * LounGenie Portal Utilities
     * Global utilities object attached to window
     */
    window.LGP_Utils = window.LGP_Utils || {};

    /* ============================================================================
     * PERFORMANCE UTILITIES
     * ============================================================================ */

    /**
     * Debounce function - Delays execution until after wait time has elapsed
     * Prevents excessive function calls (e.g., during rapid scroll/resize)
     * 
     * @param   {Function} func - Function to debounce
     * @param   {Number} wait - Milliseconds to wait
     * @param   {Boolean} immediate - Trigger on leading edge
     * @returns {Function} Debounced function
     */
    LGP_Utils.debounce = function (func, wait, immediate) {
        var timeout;
        wait = wait || 300;

        return function executedFunction()
        {
            var context = this;
            var args = arguments;

            var later = function () {
                timeout = null;
                if (!immediate) { func.apply(context, args);
                }
            };

            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);

            if (callNow) { func.apply(context, args);
            }
        };
    };

    /**
     * Throttle function - Ensures function is only called once per time period
     * Useful for continuous events like scroll/resize
     * 
     * @param   {Function} func - Function to throttle
     * @param   {Number} limit - Milliseconds between calls
     * @returns {Function} Throttled function
     */
    LGP_Utils.throttle = function (func, limit) {
        var inThrottle;
        limit = limit || 200;

        return function () {
            var context = this;
            var args = arguments;

            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(
                    function () {
                        inThrottle = false;
                    }, limit
                );
            }
        };
    };

    /* ============================================================================
     * DATE & TIME UTILITIES
     * ============================================================================ */

    /**
     * Format date for display
     * 
     * @param   {String|Date} date - Date to format
     * @param   {String} format - Format type: 'short', 'long', 'relative'
     * @returns {String} Formatted date string
     */
    LGP_Utils.formatDate = function (date, format) {
        if (!date) { return '';
        }

        var d = new Date(date);
        if (isNaN(d.getTime())) { return date; // Invalid date
        }

        format = format || 'short';

        // Relative date (e.g., "2 hours ago")
        if (format === 'relative') {
            return LGP_Utils.getRelativeTime(d);
        }

        var options = {};
        if (format === 'long') {
            options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
        } else {
            // Short format: Dec 18, 2025
            options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            };
        }

        try {
            return d.toLocaleDateString('en-US', options);
        } catch (e) {
            return d.toDateString();
        }
    };

    /**
     * Get relative time string (e.g., "2 hours ago", "just now")
     * 
     * @param   {Date} date - Date to compare
     * @returns {String} Relative time string
     */
    LGP_Utils.getRelativeTime = function (date) {
        var now = new Date();
        var diff = now - new Date(date);
        var seconds = Math.floor(diff / 1000);
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        var days = Math.floor(hours / 24);

        if (seconds < 60) { return 'just now';
        }
        if (minutes < 60) { return minutes + ' minute' + (minutes !== 1 ? 's' : '') + ' ago';
        }
        if (hours < 24) { return hours + ' hour' + (hours !== 1 ? 's' : '') + ' ago';
        }
        if (days < 7) { return days + ' day' + (days !== 1 ? 's' : '') + ' ago';
        }
        if (days < 30) { return Math.floor(days / 7) + ' week' + (Math.floor(days / 7) !== 1 ? 's' : '') + ' ago';
        }
        if (days < 365) { return Math.floor(days / 30) + ' month' + (Math.floor(days / 30) !== 1 ? 's' : '') + ' ago';
        }
        return Math.floor(days / 365) + ' year' + (Math.floor(days / 365) !== 1 ? 's' : '') + ' ago';
    };

    /* ============================================================================
     * STRING UTILITIES
     * ============================================================================ */

    /**
     * Truncate string with ellipsis
     * 
     * @param   {String} str - String to truncate
     * @param   {Number} maxLength - Maximum length
     * @returns {String} Truncated string
     */
    LGP_Utils.truncate = function (str, maxLength) {
        if (!str || str.length <= maxLength) { return str;
        }
        return str.substring(0, maxLength - 3) + '...';
    };

    /**
     * Escape HTML to prevent XSS
     * 
     * @param   {String} text - Text to escape
     * @returns {String} Escaped text
     */
    LGP_Utils.escapeHtml = function (text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(
            /[&<>"']/g, function (m) {
                return map[m]; }
        );
    };

    /**
     * Generate unique ID
     * 
     * @param   {String} prefix - Optional prefix
     * @returns {String} Unique ID
     */
    LGP_Utils.uniqueId = function (prefix) {
        prefix = prefix || 'lgp';
        return prefix + '-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
    };

    /* ============================================================================
     * NOTIFICATION SYSTEM
     * ============================================================================ */

    /**
     * Show notification toast
     * 
     * @param {String} message - Message to display
     * @param {String} type - Type: 'success', 'error', 'warning', 'info'
     * @param {Number} duration - Duration in ms (default: 3000)
     */
    LGP_Utils.showNotification = function (message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;

        // Remove existing notification
        var existing = document.querySelector('.lgp-notification');
        if (existing) {
            existing.remove();
        }

        // Create notification element
        var notification = document.createElement('div');
        notification.className = 'lgp-notification lgp-notification-' + type;
        notification.innerHTML = '<div class="lgp-notification-content">' +
            '<span class="lgp-notification-icon">' + LGP_Utils.getNotificationIcon(type) + '</span>' +
            '<span class="lgp-notification-message">' + LGP_Utils.escapeHtml(message) + '</span>' +
            '<button class="lgp-notification-close" aria-label="Close">&times;</button>' +
            '</div>';

        // Append to body
        document.body.appendChild(notification);

        // Trigger animation
        setTimeout(
            function () {
                notification.classList.add('lgp-notification-show');
            }, 10
        );

        // Close button
        notification.querySelector('.lgp-notification-close').addEventListener(
            'click', function () {
                LGP_Utils.closeNotification(notification);
            }
        );

        // Auto-close
        setTimeout(
            function () {
                LGP_Utils.closeNotification(notification);
            }, duration
        );
    };

    /**
     * Close notification
     * 
     * @param {HTMLElement} notification - Notification element
     */
    LGP_Utils.closeNotification = function (notification) {
        notification.classList.remove('lgp-notification-show');
        setTimeout(
            function () {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300
        );
    };

    /**
     * Get icon for notification type
     * 
     * @param   {String} type - Notification type
     * @returns {String} Icon HTML
     */
    LGP_Utils.getNotificationIcon = function (type) {
        var icons = {
            'success': '✓',
            'error': '✕',
            'warning': '⚠',
            'info': 'ℹ'
        };
        return icons[type] || icons.info;
    };

    /* ============================================================================
     * DOM UTILITIES
     * ============================================================================ */

    /**
     * Safely query DOM element
     * 
     * @param   {String} selector - CSS selector
     * @param   {HTMLElement} context - Context element (default: document)
     * @returns {HTMLElement|null} Found element or null
     */
    LGP_Utils.$ = function (selector, context) {
        context = context || document;
        return context.querySelector(selector);
    };

    /**
     * Safely query multiple DOM elements
     * 
     * @param   {String} selector - CSS selector
     * @param   {HTMLElement} context - Context element (default: document)
     * @returns {Array} Array of elements
     */
    LGP_Utils.$$ = function (selector, context) {
        context = context || document;
        return Array.prototype.slice.call(context.querySelectorAll(selector));
    };

    /**
     * Add event listener with cleanup
     * 
     * @param   {HTMLElement|String} element - Element or selector
     * @param   {String} event - Event type
     * @param   {Function} handler - Event handler
     * @param   {Object} options - Event options
     * @returns {Function} Cleanup function
     */
    LGP_Utils.on = function (element, event, handler, options) {
        if (typeof element === 'string') {
            element = LGP_Utils.$(element);
        }
        if (!element) { return function () { };
        }

        element.addEventListener(event, handler, options);

        // Return cleanup function
        return function () {
            element.removeEventListener(event, handler, options);
        };
    };

    /**
     * Trigger custom event
     * 
     * @param {HTMLElement} element - Element to trigger event on
     * @param {String} eventName - Event name
     * @param {Object} detail - Event detail data
     */
    LGP_Utils.trigger = function (element, eventName, detail) {
        var event = new CustomEvent(
            eventName, {
                detail: detail || {},
                bubbles: true,
                cancelable: true
            }
        );
        element.dispatchEvent(event);
    };

    /* ============================================================================
     * AJAX UTILITIES
     * ============================================================================ */

    /**
     * Make AJAX request
     * 
     * @param   {Object} options - Request options
     * @returns {Promise} Promise that resolves with response
     */
    LGP_Utils.ajax = function (options) {
        return new Promise(
            function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                var method = (options.method || 'GET').toUpperCase();
                var url = options.url;
                var data = options.data || null;
                var headers = options.headers || {};

                xhr.open(method, url, true);

                // Set default headers
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                // Set custom headers
                for (var key in headers) {
                    if (headers.hasOwnProperty(key)) {
                        xhr.setRequestHeader(key, headers[key]);
                    }
                }

                // Handle JSON data
                if (data && typeof data === 'object' && !(data instanceof FormData)) {
                    xhr.setRequestHeader('Content-Type', 'application/json');
                    data = JSON.stringify(data);
                }

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            resolve(response);
                        } catch (e) {
                            resolve(xhr.responseText);
                        }
                    } else {
                        reject(new Error('Request failed with status ' + xhr.status));
                    }
                };

                xhr.onerror = function () {
                    reject(new Error('Network error'));
                };

                xhr.send(data);
            }
        );
    };

    /* ============================================================================
     * VALIDATION UTILITIES
     * ============================================================================ */

    /**
     * Validate email format
     * 
     * @param   {String} email - Email to validate
     * @returns {Boolean} Is valid email
     */
    LGP_Utils.isValidEmail = function (email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    };

    /**
     * Validate phone number
     * 
     * @param   {String} phone - Phone to validate
     * @returns {Boolean} Is valid phone
     */
    LGP_Utils.isValidPhone = function (phone) {
        var regex = /^[\d\s\-\(\)\+]+$/;
        return phone.length >= 10 && regex.test(phone);
    };

    /**
     * Validate URL
     * 
     * @param   {String} url - URL to validate
     * @returns {Boolean} Is valid URL
     */
    LGP_Utils.isValidUrl = function (url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    };

    /* ============================================================================
     * FILE UTILITIES
     * ============================================================================ */

    /**
     * Format file size for display
     * 
     * @param   {Number} bytes - File size in bytes
     * @returns {String} Formatted size (e.g., "1.5 MB")
     */
    LGP_Utils.formatFileSize = function (bytes) {
        if (bytes === 0) { return '0 Bytes';
        }

        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));

        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    /**
     * Get file extension
     * 
     * @param   {String} filename - Filename
     * @returns {String} File extension (lowercase)
     */
    LGP_Utils.getFileExtension = function (filename) {
        return filename.slice((filename.lastIndexOf('.') - 1 >>> 0) + 2).toLowerCase();
    };

    /**
     * Check if file type is allowed
     * 
     * @param   {String} filename - Filename
     * @param   {Array} allowedTypes - Array of allowed extensions
     * @returns {Boolean} Is file type allowed
     */
    LGP_Utils.isAllowedFileType = function (filename, allowedTypes) {
        var ext = LGP_Utils.getFileExtension(filename);
        return allowedTypes.indexOf(ext) !== -1;
    };

    /* ============================================================================
     * LOADING STATES
     * ============================================================================ */

    /**
     * Show loading spinner on element
     * 
     * @param {HTMLElement|String} element - Element or selector
     * @param {String} text - Optional loading text
     */
    LGP_Utils.showLoading = function (element, text) {
        if (typeof element === 'string') {
            element = LGP_Utils.$(element);
        }
        if (!element) { return;
        }

        element.classList.add('lgp-loading');
        element.disabled = true;

        var originalContent = element.innerHTML;
        element.setAttribute('data-original-content', originalContent);

        element.innerHTML = '<span class="lgp-spinner"></span>' +
            (text ? ' <span>' + text + '</span>' : '');
    };

    /**
     * Hide loading spinner on element
     * 
     * @param {HTMLElement|String} element - Element or selector
     */
    LGP_Utils.hideLoading = function (element) {
        if (typeof element === 'string') {
            element = LGP_Utils.$(element);
        }
        if (!element) { return;
        }

        element.classList.remove('lgp-loading');
        element.disabled = false;

        var originalContent = element.getAttribute('data-original-content');
        if (originalContent) {
            element.innerHTML = originalContent;
            element.removeAttribute('data-original-content');
        }
    };

    /* ============================================================================
     * STORAGE UTILITIES
     * ============================================================================ */

    /**
     * Save data to localStorage
     * 
     * @param {String} key - Storage key
     * @param {*} value - Value to store (will be JSON stringified)
     */
    LGP_Utils.saveToStorage = function (key, value) {
        try {
            localStorage.setItem('lgp_' + key, JSON.stringify(value));
        } catch (e) {
            console.warn('Failed to save to localStorage:', e);
        }
    };

    /**
     * Get data from localStorage
     * 
     * @param   {String} key - Storage key
     * @param   {*} defaultValue - Default value if not found
     * @returns {*} Stored value or default
     */
    LGP_Utils.getFromStorage = function (key, defaultValue) {
        try {
            var item = localStorage.getItem('lgp_' + key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    };

    /**
     * Remove data from localStorage
     * 
     * @param {String} key - Storage key
     */
    LGP_Utils.removeFromStorage = function (key) {
        try {
            localStorage.removeItem('lgp_' + key);
        } catch (e) {
            console.warn('Failed to remove from localStorage:', e);
        }
    };

    /* ============================================================================
     * CONSOLE UTILITIES (Dev Mode)
     * ============================================================================ */

    /**
     * Log message (only in dev mode)
     * 
     * @param {String} message - Message to log
     * @param {*} data - Optional data to log
     */
    LGP_Utils.log = function (message, data) {
        if (window.LGP_DEBUG || localStorage.getItem('lgp_debug')) {
            console.log('[LGP]', message, data || '');
        }
    };

    /**
     * Log error
     * 
     * @param {String} message - Error message
     * @param {Error} error - Error object
     */
    LGP_Utils.logError = function (message, error) {
        console.error('[LGP Error]', message, error || '');
    };

    // Expose to window
    window.LGP_Utils = LGP_Utils;

    // Log initialization
    LGP_Utils.log('✅ LGP_Utils library loaded and ready');

})(window);
