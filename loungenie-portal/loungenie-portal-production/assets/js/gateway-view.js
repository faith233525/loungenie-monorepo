/**
 * Gateway Management JavaScript
 * Handles gateway actions, modals, and AJAX
 */

(function() {
    'use strict';

    // Search and filter
    const searchInput = document.getElementById('gateway-search');
    const callButtonFilter = document.getElementById('gateway-call-button-filter');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(filterGateways, 300));
    }
    
    if (callButtonFilter) {
        callButtonFilter.addEventListener('change', filterGateways);
    }

    function filterGateways() {
        const search = searchInput ? searchInput.value.toLowerCase() : '';
        const callButton = callButtonFilter ? callButtonFilter.value : '';
        
        document.querySelectorAll('.lgp-gateway-group').forEach(group => {
            const rows = group.querySelectorAll('.lgp-gateway-table tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const channel = row.children[0]?.textContent.toLowerCase() || '';
                const address = row.children[1]?.textContent.toLowerCase() || '';
                const company = group.dataset.company?.toLowerCase() || '';
                const hasCallButton = row.classList.contains('lgp-call-button-enabled') ? '1' : '0';
                
                const matchesSearch = !search || channel.includes(search) || address.includes(search) || company.includes(search);
                const matchesCallButton = !callButton || hasCallButton === callButton;
                
                if (matchesSearch && matchesCallButton) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Hide entire group if no visible rows
            group.style.display = visibleCount > 0 ? '' : 'none';
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // View Units modal
    document.querySelectorAll('.lgp-btn-view-units').forEach(btn => {
        btn.addEventListener('click', async function() {
            const gatewayId = this.dataset.gatewayId;
            showUnitsModal(gatewayId);
        });
    });

    async function showUnitsModal(gatewayId) {
        const modal = document.getElementById('lgp-units-modal');
        const body = document.getElementById('lgp-units-modal-body');
        
        if (!modal || !body) return;
        
        body.innerHTML = '<p>Loading...</p>';
        modal.style.display = 'block';
        
        try {
            const response = await fetch(`${window.lgpData.restUrl}gateways/${gatewayId}/units`, {
                headers: {
                    'X-WP-Nonce': window.lgpData.nonce
                }
            });
            
            const result = await response.json();
            
            if (result.success && result.data.length) {
                let html = '<table class="lgp-table"><thead><tr><th>Unit ID</th><th>Address</th><th>Lock Type</th><th>Status</th></tr></thead><tbody>';
                result.data.forEach(unit => {
                    html += `<tr>
                        <td>${unit.id}</td>
                        <td>${unit.address || '—'}</td>
                        <td>${unit.lock_type || '—'}</td>
                        <td>${unit.status || '—'}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                body.innerHTML = html;
            } else {
                body.innerHTML = '<p>No units found for this gateway.</p>';
            }
        } catch (error) {
            body.innerHTML = '<p class="error">Failed to load units.</p>';
            console.error('View units error:', error);
        }
    }

    // Audit Logs modal
    document.querySelectorAll('.lgp-btn-audit-logs').forEach(btn => {
        btn.addEventListener('click', function() {
            const gatewayId = this.dataset.gatewayId;
            showAuditLogsModal(gatewayId);
        });
    });

    function showAuditLogsModal(gatewayId) {
        const modal = document.getElementById('lgp-audit-logs-modal');
        const body = document.getElementById('lgp-audit-logs-modal-body');
        
        if (!modal || !body) return;
        
        // Placeholder: in production, fetch real audit logs
        body.innerHTML = `<p>Audit logs for gateway ${gatewayId}:</p><ul><li>Created on 2024-12-15</li><li>Last tested: 2024-12-16</li><li>No recent issues</li></ul>`;
        modal.style.display = 'block';
    }

    // Test Signal button
    document.querySelectorAll('.lgp-btn-test-signal').forEach(btn => {
        btn.addEventListener('click', async function() {
            const gatewayId = this.dataset.gatewayId;
            await testSignal(gatewayId, this);
        });
    });

    async function testSignal(gatewayId, button) {
        button.disabled = true;
        button.textContent = 'Testing...';
        
        try {
            const response = await fetch(`${window.lgpData.restUrl}gateways/${gatewayId}/test-signal`, {
                method: 'POST',
                headers: {
                    'X-WP-Nonce': window.lgpData.nonce,
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert(result.message || 'Signal test initiated successfully.');
            } else {
                alert('Test failed: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Test signal failed. Please try again.');
            console.error('Test signal error:', error);
        } finally {
            button.disabled = false;
            button.textContent = 'Test Signal';
        }
    }

    // Modal close handlers
    document.querySelectorAll('.lgp-modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.lgp-modal').style.display = 'none';
        });
    });

    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('lgp-modal')) {
            e.target.style.display = 'none';
        }
    });
})();
