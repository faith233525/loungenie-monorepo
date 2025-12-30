(function () {
    'use strict';

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        const wrapper = document.getElementById('lgp-tickets-view');
        if (!wrapper) { return; }

        const restUrl = (wrapper.getAttribute('data-rest-url') || '').replace(/\/?$/, '/');
        const restNonce = wrapper.getAttribute('data-rest-nonce') || (window.lgpData && window.lgpData.restNonce) || '';
        const isSupport = wrapper.getAttribute('data-is-support') === '1';
        const companyName = wrapper.getAttribute('data-company-name') || '';

        const searchInput = document.getElementById('lgp-ticket-search');
        const statusSelect = document.getElementById('lgp-ticket-status-filter');
        const prioritySelect = document.getElementById('lgp-ticket-priority-filter');
        const typeSelect = document.getElementById('lgp-ticket-type-filter');
        const companySelect = document.getElementById('lgp-ticket-company-filter');
        const refreshBtn = document.getElementById('lgp-refresh-tickets');

        const table = document.getElementById('lgp-tickets-table');
        const tbody = document.getElementById('lgp-tickets-table-body');
        const loading = document.getElementById('lgp-tickets-loading');
        const emptyState = document.getElementById('lgp-tickets-empty');

        const detailCard = document.getElementById('lgp-ticket-detail');
        const detailTitle = document.getElementById('lgp-ticket-detail-title');
        const detailMeta = document.getElementById('lgp-ticket-detail-meta');
        const detailStatus = document.getElementById('lgp-ticket-detail-status');
        const detailId = document.getElementById('lgp-ticket-detail-id');
        const detailType = document.getElementById('lgp-ticket-detail-type');
        const detailPriority = document.getElementById('lgp-ticket-detail-priority');
        const detailUnit = document.getElementById('lgp-ticket-detail-unit');
        const detailCompany = document.getElementById('lgp-ticket-detail-company');
        const detailCreated = document.getElementById('lgp-ticket-detail-created');
        const detailThread = document.getElementById('lgp-ticket-thread');
        const replyForm = document.getElementById('lgp-ticket-reply-form');
        const replyMessage = document.getElementById('lgp-ticket-reply-message');
        const statusSelectDetail = document.getElementById('lgp-ticket-status-select');
        const statusApplyBtn = document.getElementById('lgp-ticket-status-apply');

        const state = {
            tickets: [],
            filtered: []
        };

        function notify(msg, type) {
            if (typeof window.lgpShowNotification === 'function') {
                window.lgpShowNotification(msg, type || 'info');
            } else {
                // eslint-disable-next-line no-alert
                alert(msg);
            }
        }

        function setLoading(isLoading) {
            if (isLoading) {
                loading.classList.remove('lgp-hidden');
                table.classList.add('lgp-hidden');
                emptyState.classList.add('lgp-hidden');
            } else {
                loading.classList.add('lgp-hidden');
            }
        }

        function fetchTickets() {
            setLoading(true);
            fetch(restUrl + 'tickets', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': restNonce
                }
            })
                .then(checkResponse)
                .then(data => {
                    state.tickets = Array.isArray(data.tickets) ? data.tickets : [];
                    applyFilters();
                    setLoading(false);
                })
                .catch(err => {
                    console.error(err);
                    setLoading(false);
                    notify('Unable to load tickets right now.', 'error');
                });
        }

        function checkResponse(res) {
            if (!res.ok) {
                return res.json().catch(() => ({ message: res.statusText || 'Request failed' }))
                    .then(body => Promise.reject(new Error(body.message || res.statusText)));
            }
            return res.json();
        }

        function applyFilters() {
            const term = (searchInput && searchInput.value || '').toLowerCase();
            const status = (statusSelect && statusSelect.value) || 'all';
            const priority = (prioritySelect && prioritySelect.value) || 'all';
            const type = (typeSelect && typeSelect.value) || 'all';
            const company = (companySelect && companySelect.value) || 'all';

            state.filtered = state.tickets.filter(t => {
                if (status !== 'all' && (t.status || '').toLowerCase() !== status) return false;
                if (priority !== 'all' && (t.priority || '').toLowerCase() !== priority) return false;
                if (type !== 'all' && (t.request_type || '').toLowerCase() !== type) return false;
                if (isSupport && company !== 'all' && String(t.company_id || '') !== company) return false;

                if (term) {
                    const blob = [t.id, t.request_type, t.priority, t.status, t.notes, t.thread_history, t.company_name]
                        .map(v => (v || '').toString().toLowerCase())
                        .join(' ');
                    if (blob.indexOf(term) === -1) { return false; }
                }
                return true;
            });

            renderTable();
        }

        function renderTable() {
            tbody.innerHTML = '';
            if (!state.filtered.length) {
                table.classList.add('lgp-hidden');
                emptyState.classList.remove('lgp-hidden');
                return;
            }

            emptyState.classList.add('lgp-hidden');
            table.classList.remove('lgp-hidden');

            state.filtered.forEach(ticket => {
                const tr = document.createElement('tr');
                tr.setAttribute('data-ticket-id', ticket.id);
                tr.setAttribute('data-status', ticket.status || '');
                tr.setAttribute('data-priority', ticket.priority || '');
                tr.setAttribute('data-type', ticket.request_type || '');
                tr.setAttribute('data-company', ticket.company_id || '');

                tr.innerHTML = `
          <td>#${escapeHtml(ticket.id)}</td>
          <td>${escapeHtml(capitalize(ticket.request_type || 'general'))}</td>
          <td>${badge(ticket.priority)}</td>
          <td>${statusBadge(ticket.status)}</td>
          ${isSupport ? `<td>${escapeHtml(ticket.company_name || ticket.company_id || '')}</td>` : ''}
          <td>${ticket.unit_id ? '#' + escapeHtml(ticket.unit_id) : '—'}</td>
          <td>${formatDate(ticket.created_at)}</td>
          <td>${formatDate(ticket.updated_at || ticket.created_at)}</td>
          <td><button class="lgp-btn lgp-btn-secondary lgp-btn-sm" data-action="view" data-ticket-id="${escapeHtml(ticket.id)}">View</button></td>
        `;
                tbody.appendChild(tr);
            });
        }

        function escapeHtml(val) {
            return (val === undefined || val === null) ? '' : String(val)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function capitalize(str) {
            if (!str) return '';
            return String(str).charAt(0).toUpperCase() + String(str).slice(1);
        }

        function badge(priority) {
            const normalized = (priority || 'normal').toLowerCase();
            let tone = 'info';
            if (normalized === 'high') tone = 'warning';
            if (normalized === 'urgent') tone = 'error';
            return `<span class="lgp-badge lgp-badge-${tone}">${escapeHtml(capitalize(normalized))}</span>`;
        }

        function statusBadge(status) {
            const normalized = (status || 'open').toLowerCase();
            let tone = 'info';
            if (normalized === 'open') tone = 'warning';
            if (normalized === 'closed') tone = 'success';
            return `<span class="lgp-badge lgp-badge-${tone}">${escapeHtml(capitalize(normalized))}</span>`;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return escapeHtml(dateStr);
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        }

        tbody.addEventListener('click', function (evt) {
            const btn = evt.target.closest('button[data-action="view"]');
            if (!btn) return;
            const ticketId = btn.getAttribute('data-ticket-id');
            openTicket(ticketId);
        });

        [searchInput, statusSelect, prioritySelect, typeSelect, companySelect].forEach(el => {
            if (!el) return;
            el.addEventListener(el.tagName === 'INPUT' ? 'input' : 'change', applyFilters);
        });

        if (refreshBtn) {
            refreshBtn.addEventListener('click', fetchTickets);
        }

        function openTicket(ticketId) {
            if (!ticketId) return;
            detailCard.classList.remove('lgp-hidden');
            detailCard.setAttribute('data-ticket-id', ticketId);
            detailThread.innerHTML = '<p class="lgp-text-muted">Loading thread...</p>';

            fetch(restUrl + 'tickets/' + ticketId, {
                method: 'GET',
                headers: { 'X-WP-Nonce': restNonce }
            })
                .then(checkResponse)
                .then(ticket => {
                    hydrateDetail(ticket);
                })
                .catch(err => {
                    console.error(err);
                    notify('Unable to load ticket detail.', 'error');
                });
        }

        function hydrateDetail(ticket) {
            detailTitle.textContent = `Ticket #${ticket.id}`;
            detailId.textContent = `#${ticket.id}`;
            detailType.textContent = capitalize(ticket.request_type || 'General');
            detailPriority.innerHTML = badge(ticket.priority);
            detailUnit.textContent = ticket.unit_id ? `#${ticket.unit_id}` : '—';
            detailCompany.textContent = ticket.company_name || companyName || '—';
            detailCreated.textContent = formatDate(ticket.created_at);
            detailStatus.innerHTML = statusBadge(ticket.status);
            detailMeta.textContent = `${capitalize(ticket.priority || 'normal')} • ${capitalize(ticket.status || 'open')}`;

            if (statusSelectDetail) {
                statusSelectDetail.value = (ticket.status || 'open').toLowerCase();
            }

            renderThread(ticket.thread_history);
        }

        function renderThread(thread) {
            let history = [];
            if (typeof thread === 'string') {
                try { history = JSON.parse(thread) || []; } catch (e) { history = []; }
            } else if (Array.isArray(thread)) {
                history = thread;
            }

            if (!history.length) {
                detailThread.innerHTML = '<p class="lgp-text-muted">No replies yet.</p>';
                return;
            }

            detailThread.innerHTML = '';
            history.forEach(entry => {
                const item = document.createElement('div');
                item.className = 'lgp-thread-item';
                item.innerHTML = `
          <div class="lgp-thread-meta">
            <strong>${escapeHtml(entry.user || 'User')}</strong>
            <span class="lgp-text-muted" style="margin-left: 8px;">${formatDate(entry.timestamp || '')}</span>
          </div>
          <div class="lgp-thread-message">${escapeHtml(entry.message || '')}</div>
        `;
                detailThread.appendChild(item);
            });
        }

        if (replyForm) {
            replyForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const ticketId = detailCard.getAttribute('data-ticket-id');
                const message = (replyMessage && replyMessage.value || '').trim();
                if (!ticketId || !message) {
                    notify('Please enter a reply message.', 'warning');
                    return;
                }

                replyForm.classList.add('is-loading');
                fetch(restUrl + 'tickets/' + ticketId + '/reply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce
                    },
                    body: JSON.stringify({ message })
                })
                    .then(checkResponse)
                    .then(() => {
                        notify('Reply sent.', 'success');
                        replyMessage.value = '';
                        openTicket(ticketId);
                        fetchTickets();
                    })
                    .catch(err => {
                        console.error(err);
                        notify('Unable to send reply right now.', 'error');
                    })
                    .finally(() => {
                        replyForm.classList.remove('is-loading');
                    });
            });
        }

        if (isSupport && statusApplyBtn && statusSelectDetail) {
            statusApplyBtn.addEventListener('click', function () {
                const ticketId = detailCard.getAttribute('data-ticket-id');
                const newStatus = statusSelectDetail.value;
                if (!ticketId || !newStatus) { return; }

                statusApplyBtn.disabled = true;
                fetch(restUrl + 'tickets/' + ticketId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': restNonce
                    },
                    body: JSON.stringify({ status: newStatus })
                })
                    .then(checkResponse)
                    .then(() => {
                        notify('Status updated.', 'success');
                        openTicket(ticketId);
                        fetchTickets();
                    })
                    .catch(err => {
                        console.error(err);
                        notify('Unable to update status right now.', 'error');
                    })
                    .finally(() => {
                        statusApplyBtn.disabled = false;
                    });
            });
        }

        // Initial load
        fetchTickets();
    }
})();
