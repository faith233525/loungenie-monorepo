/**
 * Company Profile Enhancements
 * Handles inline ticket reply modal, audit log viewer, and service notes
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeTicketReplyModal();
    initializeAuditLog();
    initializeServiceNotes();
});

/**
 * Initialize Ticket Reply Modal
 */
function initializeTicketReplyModal() {
    const modal = document.getElementById('reply-modal');
    const form = document.getElementById('reply-form');
    const ticketIdInput = document.getElementById('reply-ticket-id');
    
    if (!modal || !form) return;

    // Reply button click handlers
    document.querySelectorAll('.reply-ticket-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const ticketId = this.getAttribute('data-ticket-id');
            ticketIdInput.value = ticketId;
            modal.style.display = 'flex';
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const ticketId = ticketIdInput.value;
        const content = document.getElementById('reply-content').value;

        if (!ticketId || !content.trim()) {
            alert('Please provide a reply message.');
            return;
        }

        // Send AJAX request to add reply
        fetch(`/wp-json/lgp/v1/tickets/${ticketId}/reply`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.wpApiSettings?.nonce || ''
            },
            body: JSON.stringify({
                content: content,
                created_by: window.currentUserId || 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success || data.id) {
                alert('Reply added successfully!');
                modal.style.display = 'none';
                form.reset();
                // Reload page or refresh ticket list
                location.reload();
            } else {
                alert('Error adding reply: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding reply. Please try again.');
        });
    });

    // Modal close button
    const closeBtn = modal.querySelector('.lgp-modal-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
}

/**
 * Initialize Audit Log Viewer
 */
function initializeAuditLog() {
    if (!window.lgpIsSupport) return;

    const filterActionInput = document.getElementById('audit-filter-action');
    const filterDateInput = document.getElementById('audit-filter-date');
    const auditLogBody = document.getElementById('audit-log-body');

    if (!auditLogBody) return;

    // Load audit logs on page load
    loadAuditLogs();

    // Add filter event listeners
    if (filterActionInput) {
        filterActionInput.addEventListener('change', loadAuditLogs);
        filterActionInput.addEventListener('keyup', loadAuditLogs);
    }
    if (filterDateInput) {
        filterDateInput.addEventListener('change', loadAuditLogs);
    }
}

function loadAuditLogs() {
    const auditLogBody = document.getElementById('audit-log-body');
    const companyId = window.lgpCompanyId;
    
    if (!auditLogBody || !companyId) return;

    const filterAction = document.getElementById('audit-filter-action')?.value || '';
    const filterDate = document.getElementById('audit-filter-date')?.value || '';

    // Build query string
    const params = new URLSearchParams({
        company_id: companyId,
        per_page: 100
    });
    
    if (filterAction) params.append('action', filterAction);
    if (filterDate) params.append('date', filterDate);

    fetch(`/wp-json/lgp/v1/audit-log?${params}`, {
        headers: {
            'X-WP-Nonce': window.wpApiSettings?.nonce || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data)) {
            renderAuditLogs(data, auditLogBody);
        } else {
            auditLogBody.innerHTML = '<tr><td colspan="4">No audit logs found.</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error loading audit logs:', error);
        auditLogBody.innerHTML = '<tr><td colspan="4">Error loading audit logs.</td></tr>';
    });
}

function renderAuditLogs(logs, container) {
    if (!logs || logs.length === 0) {
        container.innerHTML = '<tr><td colspan="4">No audit logs found.</td></tr>';
        return;
    }

    const rows = logs.map(log => {
        const timestamp = new Date(log.created_at).toLocaleString();
        const user = log.user_login || `User #${log.user_id}`;
        const action = (log.action || '').replace(/_/g, ' ').toUpperCase();
        const metadata = typeof log.meta === 'string' ? JSON.parse(log.meta) : log.meta || {};
        const details = JSON.stringify(metadata).substring(0, 100) + '...';

        return `
            <tr>
                <td>${escapeHtml(timestamp)}</td>
                <td>${escapeHtml(user)}</td>
                <td><span class="lgp-badge lgp-badge-info">${escapeHtml(action)}</span></td>
                <td title="${escapeHtml(JSON.stringify(metadata))}">${escapeHtml(details)}</td>
            </tr>
        `;
    });

    container.innerHTML = rows.join('');
}

/**
 * Initialize Service Notes
 */
function initializeServiceNotes() {
    if (!window.lgpIsSupport) return;

    const addBtn = document.getElementById('add-service-note-btn');
    const form = document.getElementById('add-service-note-form');
    const formContainer = document.getElementById('service-note-form');
    const cancelBtn = document.getElementById('cancel-service-note-btn');
    const noteBody = document.getElementById('service-notes-body');

    if (!addBtn || !form || !noteBody) return;

    // Show/hide form
    addBtn.addEventListener('click', function() {
        formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            formContainer.style.display = 'none';
            form.reset();
        });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const noteData = {
            company_id: window.lgpCompanyId,
            unit_id: document.getElementById('service-note-unit').value || null,
            service_type: document.getElementById('service-note-type').value,
            technician_name: document.getElementById('service-note-technician').value,
            notes: document.getElementById('service-note-notes').value,
            travel_time: parseInt(document.getElementById('service-note-travel-time').value) || 0,
            service_date: document.getElementById('service-note-date').value
        };

        if (!noteData.service_date || !noteData.technician_name || !noteData.service_type || !noteData.notes) {
            alert('Please fill in all required fields.');
            return;
        }

        fetch('/wp-json/lgp/v1/service-notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.wpApiSettings?.nonce || ''
            },
            body: JSON.stringify(noteData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.id || data.success) {
                alert('Service note saved successfully!');
                formContainer.style.display = 'none';
                form.reset();
                loadServiceNotes();
            } else {
                alert('Error saving service note: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving service note. Please try again.');
        });
    });

    // Load service notes on page load
    loadServiceNotes();
}

function loadServiceNotes() {
    const companyId = window.lgpCompanyId;
    const noteBody = document.getElementById('service-notes-body');

    if (!noteBody || !companyId) return;

    fetch(`/wp-json/lgp/v1/service-notes?company_id=${companyId}`, {
        headers: {
            'X-WP-Nonce': window.wpApiSettings?.nonce || ''
        }
    })
    .then(response => response.json())
    .then(data => {
        if (Array.isArray(data) && data.length > 0) {
            renderServiceNotes(data, noteBody);
        } else {
            noteBody.innerHTML = '<tr><td colspan="6">No service notes found.</td></tr>';
        }
    })
    .catch(error => {
        console.error('Error loading service notes:', error);
        noteBody.innerHTML = '<tr><td colspan="6">Error loading service notes.</td></tr>';
    });
}

function renderServiceNotes(notes, container) {
    if (!notes || notes.length === 0) {
        container.innerHTML = '<tr><td colspan="6">No service notes found.</td></tr>';
        return;
    }

    const rows = notes.map(note => {
        const date = new Date(note.service_date).toLocaleDateString();
        const travelTime = note.travel_time ? `${note.travel_time} min` : '—';

        return `
            <tr>
                <td>${escapeHtml(date)}</td>
                <td>${escapeHtml(note.technician_name)}</td>
                <td><span class="lgp-badge lgp-badge-info">${escapeHtml(note.service_type)}</span></td>
                <td>${note.unit_id ? `#${note.unit_id}` : '—'}</td>
                <td>${travelTime}</td>
                <td>${escapeHtml(note.notes.substring(0, 50))}${note.notes.length > 50 ? '...' : ''}</td>
            </tr>
        `;
    });

    container.innerHTML = rows.join('');
}

/**
 * Utility: Escape HTML for safe display
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}
