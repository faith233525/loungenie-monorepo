/**
 * LounGenie Portal JavaScript
 * Handles table sorting, filtering, searching, and AJAX operations
 */

(function() {
    'use strict';
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        initTableSorting();
        initTableFiltering();
        initTableSearch();
        initPagination();
        initSidebarToggle();
    }
    
    /**
     * Table Sorting
     */
    function initTableSorting() {
        const sortableHeaders = document.querySelectorAll('.lgp-table th.sortable');
        
        sortableHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const table = this.closest('.lgp-table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const columnIndex = Array.from(this.parentElement.children).indexOf(this);
                const currentOrder = this.getAttribute('data-order') || 'asc';
                const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                
                // Remove sort indicators from all headers
                sortableHeaders.forEach(h => {
                    h.removeAttribute('data-order');
                    h.textContent = h.textContent.replace(' ▲', '').replace(' ▼', '');
                });
                
                // Sort rows
                rows.sort((a, b) => {
                    const aValue = a.children[columnIndex].textContent.trim();
                    const bValue = b.children[columnIndex].textContent.trim();
                    
                    // Try numeric comparison first
                    const aNum = parseFloat(aValue);
                    const bNum = parseFloat(bValue);
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
                    }
                    
                    // Fall back to string comparison
                    return newOrder === 'asc' 
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                });
                
                // Update table
                rows.forEach(row => tbody.appendChild(row));
                
                // Add sort indicator
                this.setAttribute('data-order', newOrder);
                this.textContent += newOrder === 'asc' ? ' ▲' : ' ▼';
            });
        });
    }
    
    /**
     * Table Filtering
     */
    function initTableFiltering() {
        const filterSelects = document.querySelectorAll('.lgp-filter-select');
        
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                const tableId = this.getAttribute('data-table');
                const columnIndex = parseInt(this.getAttribute('data-column'));
                const filterValue = this.value.toLowerCase();
                const table = document.getElementById(tableId);
                
                if (!table) return;
                
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const cellValue = row.children[columnIndex].textContent.toLowerCase();
                    
                    if (filterValue === '' || cellValue.includes(filterValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }
    
    /**
     * Table Search
     */
    function initTableSearch() {
        const searchInputs = document.querySelectorAll('.lgp-search-input');
        
        searchInputs.forEach(input => {
            input.addEventListener('input', debounce(function() {
                const searchTerm = this.value.toLowerCase();
                const tableId = this.getAttribute('data-table');
                const table = document.getElementById(tableId);
                
                if (!table) return;
                
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }, 300));
        });
    }
    
    /**
     * Pagination
     */
    function initPagination() {
        const paginationBtns = document.querySelectorAll('.lgp-pagination-btn');
        
        paginationBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                if (this.disabled) return;
                
                const page = parseInt(this.getAttribute('data-page'));
                const endpoint = this.getAttribute('data-endpoint');
                
                if (endpoint) {
                    loadPageData(endpoint, page);
                }
            });
        });
    }
    
    /**
     * Sidebar Toggle (mobile)
     */
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('lgp-sidebar-toggle');
        const sidebar = document.querySelector('.lgp-sidebar');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('open');
            });
            
            // Close sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                    sidebar.classList.remove('open');
                }
            });
        }
    }
    
    /**
     * Load page data via AJAX
     */
    function loadPageData(endpoint, page) {
        const url = lgpData.restUrl + endpoint + '?page=' + page;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': lgpData.nonce
            }
        })
        .then(response => response.json())
        .then(data => {
            // Handle response (implement based on specific needs)
            console.log('Page data loaded:', data);
        })
        .catch(error => {
            console.error('Error loading page data:', error);
        });
    }
    
    /**
     * Debounce helper
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Show notification
     */
    window.lgpShowNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'lgp-notification lgp-notification-' + type;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    };
    
})();
