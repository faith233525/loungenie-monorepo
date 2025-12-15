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
    
    /**
     * Initialize service request form
     */
    function initServiceRequestForm() {
        const form = document.getElementById('service-request-form');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = {
                request_type: formData.get('request_type'),
                priority: formData.get('priority'),
                notes: formData.get('notes'),
                unit_id: formData.get('unit_id') || 0
            };
            
            // Submit to REST API
            fetch(lgpData.restUrl + 'tickets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': lgpData.nonce
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.message) {
                    window.lgpShowNotification(result.message, 'success');
                    form.reset();
                    
                    // Reload page after 2 seconds to show updated data
                    setTimeout(() => window.location.reload(), 2000);
                }
            })
            .catch(error => {
                window.lgpShowNotification('Error submitting request. Please try again.', 'error');
                console.error('Error:', error);
            });
        });
    }
    
    // Initialize form on page load
    document.addEventListener('DOMContentLoaded', function() {
        initServiceRequestForm();
        initAdvancedFilters();
        initExportCSV();
    });
    
    /**
     * Initialize advanced filters for units table
     */
    function initAdvancedFilters() {
        const filters = document.querySelectorAll('.lgp-table-filter');
        const clearButton = document.getElementById('lgp-clear-filters');
        const activeFiltersContainer = document.getElementById('active-filters');
        const activeFiltersList = document.getElementById('active-filters-list');
        const table = document.getElementById('units-table');
        
        if (!table || filters.length === 0) return;
        
        const activeFilters = {};
        
        // Apply filters
        function applyFilters() {
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                let show = true;
                
                // Check each active filter
                for (const [filterType, filterValue] of Object.entries(activeFilters)) {
                    if (filterValue === '') continue;
                    
                    const rowValue = (row.getAttribute('data-' + filterType) || '').trim().toLowerCase();
                    const compareValue = filterValue.trim().toLowerCase();
                    
                    if (rowValue !== compareValue) {
                        show = false;
                        break;
                    }
                }
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
            
            // Update counts
            const visibleCountEl = document.getElementById('visible-count');
            if (visibleCountEl) visibleCountEl.textContent = visibleCount;
            
            // Update active filters display
            updateActiveFiltersDisplay();
        }
        
        // Update active filters display
        function updateActiveFiltersDisplay() {
            const hasActiveFilters = Object.values(activeFilters).some(v => v !== '');
            
            if (hasActiveFilters) {
                activeFiltersContainer.style.display = 'block';
                activeFiltersList.innerHTML = '';
                
                for (const [filterType, filterValue] of Object.entries(activeFilters)) {
                    if (filterValue === '') continue;
                    
                    const tag = document.createElement('span');
                    tag.className = 'lgp-filter-tag';
                    tag.innerHTML = `
                        ${filterType}: ${filterValue}
                        <span class="lgp-filter-tag-remove" data-filter="${filterType}">✕</span>
                    `;
                    activeFiltersList.appendChild(tag);
                }
                
                // Add click handlers for remove buttons
                activeFiltersList.querySelectorAll('.lgp-filter-tag-remove').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const filterType = this.getAttribute('data-filter');
                        activeFilters[filterType] = '';
                        
                        // Reset the select
                        const select = document.querySelector(`[data-filter="${filterType}"]`);
                        if (select) select.value = '';
                        
                        applyFilters();
                    });
                });
            } else {
                activeFiltersContainer.style.display = 'none';
            }
        }
        
        // Filter change handler
        filters.forEach(filter => {
            filter.addEventListener('change', function() {
                const filterType = this.getAttribute('data-filter');
                const filterValue = this.value;
                activeFilters[filterType] = filterValue;
                applyFilters();
            });
        });
        
        // Clear all filters
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                filters.forEach(filter => {
                    filter.value = '';
                    const filterType = filter.getAttribute('data-filter');
                    activeFilters[filterType] = '';
                });
                applyFilters();
            });
        }
    }
    
    /**
     * Initialize CSV export functionality
     */
    function initExportCSV() {
        const exportButton = document.getElementById('lgp-export-units');
        if (!exportButton) return;
        
        exportButton.addEventListener('click', function() {
            const table = document.getElementById('units-table');
            if (!table) return;
            
            // Show loading state
            exportButton.disabled = true;
            exportButton.textContent = '⏳ Exporting...';
            
            // Get visible rows only
            const rows = Array.from(table.querySelectorAll('tbody tr'))
                .filter(row => row.style.display !== 'none');
            
            if (rows.length === 0) {
                window.lgpShowNotification('No data to export', 'warning');
                exportButton.disabled = false;
                exportButton.innerHTML = '📥 Export to CSV';
                return;
            }
            
            // Build CSV
            let csv = 'Unit ID,Company,Color,Season,Venue,Lock Brand,Status,Install Date\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                // Get values from cells, using data attributes where available
                const rowData = [
                    cells[0] ? cells[0].textContent.trim() : '',
                    cells[1] ? cells[1].textContent.trim() : '',
                    row.getAttribute('data-color') || '',
                    row.getAttribute('data-season') || '',
                    row.getAttribute('data-venue') || '',
                    row.getAttribute('data-lock-brand') || '',
                    row.getAttribute('data-status') || '',
                    cells[7] ? cells[7].textContent.trim() : ''
                ];
                
                csv += rowData.map(field => '"' + field.replace(/"/g, '""') + '"').join(',') + '\n';
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            const today = new Date();
            const dateString = today.getFullYear() + '-' + 
                              String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(today.getDate()).padStart(2, '0');
            a.href = url;
            a.download = 'loungenie-units-' + dateString + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            // Reset button
            setTimeout(() => {
                exportButton.disabled = false;
                exportButton.innerHTML = '📥 Export to CSV';
                window.lgpShowNotification('Export completed successfully', 'success');
            }, 1000);
        });
    }
    
})();
