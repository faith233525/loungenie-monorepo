/**
 * Map View JavaScript
 * Handles map initialization, filtering, and REST data loading
 */

(function () {
	'use strict';

	const MapView = {
		map: null,
		markers: [],
		units: [],
		tickets: [],
		filters: {
			urgency: window.lgpMapData?.urgency || '',
			status: window.lgpMapData?.status || '',
			type: window.lgpMapData?.mapType || 'all',
			search: '',
		},
		sortBy: 'urgency-desc',

		init() {
			this.cacheDom();
			this.initMap();
			this.bindEvents();
			this.loadData();
		},

		cacheDom() {
			this.mapEl = document.getElementById('map');
			this.urgencyFilter = document.getElementById('urgency-filter');
			this.statusFilter = document.getElementById('status-filter');
			this.typeFilter = document.getElementById('type-filter');
			this.sortSelect = document.getElementById('sort-select');
			this.resetBtn = document.getElementById('reset-filters');
			this.modal = document.getElementById('detail-modal');
			this.modalClose = this.modal?.querySelector('.lgp-modal-close');
			this.modalBody = document.getElementById('modal-body');
			this.listContainer = document.getElementById('units-list');
		},

		initMap() {
			if (!this.mapEl || typeof L === 'undefined') return;
			this.map = L.map(this.mapEl).setView([39.5, -98.35], 4); // US center default
			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; OpenStreetMap contributors',
				maxZoom: 19,
			}).addTo(this.map);
		},

		bindEvents() {
			this.urgencyFilter?.addEventListener('change', (e) => {
				this.filters.urgency = e.target.value;
				this.applyFilters();
			});

			this.statusFilter?.addEventListener('change', (e) => {
				this.filters.status = e.target.value;
				this.applyFilters();
			});

			this.typeFilter?.addEventListener('change', (e) => {
				this.filters.type = e.target.value;
				this.applyFilters();
			});

			this.sortSelect?.addEventListener('change', (e) => {
				this.sortBy = e.target.value;
				this.renderList();
			});

			this.resetBtn?.addEventListener('click', () => this.resetFilters());

			this.modalClose?.addEventListener('click', () => this.hideModal());
			this.modal?.addEventListener('click', (e) => {
				if (e.target === this.modal) {
					this.hideModal();
				}
			});
		},

		async loadData() {
			const loading = document.querySelector('.lgp-loading');
			if (loading) loading.textContent = 'Loading...';

			const headers = {};
			const restNonce = window.lgpMapData?.restNonce;
			if (restNonce) headers['X-WP-Nonce'] = restNonce;

			try {
				const response = await fetch('/wp-json/lgp/v1/map/units', {
					method: 'GET',
					headers,
					credentials: 'same-origin',
				});

				const data = await response.json();
				if (Array.isArray(data?.units)) {
					this.units = data.units || [];
					this.tickets = data.tickets || [];
					this.renderMap();
					this.renderList();
					if (loading) loading.textContent = '';
				} else {
					if (loading) loading.textContent = 'Failed to load data';
				}
			} catch (err) {
				console.error('Error loading map data', err);
				if (loading) loading.textContent = 'Error loading data';
			}
		},

		renderMap() {
			if (!this.map) return;
			this.markers.forEach(marker => this.map.removeLayer(marker));
			this.markers = [];

			const filteredUnits = this.getFilteredUnits();
			const bounds = [];

			filteredUnits.forEach(unit => {
				if (!unit.latitude || !unit.longitude) return;
				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				const urgency = this.getHighestUrgency(unitTickets);
				const color = this.getUrgencyColor(urgency);

				const marker = L.circleMarker([unit.latitude, unit.longitude], {
					radius: 8,
					fillColor: color,
					color: '#fff',
					weight: 2,
					fillOpacity: 0.9,
				});

				const popup = `
					<div style="min-width: 200px;">
						<h4 style="margin:0 0 8px 0; color:#333;">${this.escapeHtml(unit.name)}</h4>
						<p style="margin:0 0 4px 0; font-size:0.9rem; color:#666;"><strong>Type:</strong> ${this.escapeHtml(unit.type || 'N/A')}</p>
						<p style="margin:0 0 4px 0; font-size:0.9rem; color:#666;"><strong>Tickets:</strong> ${unitTickets.length}</p>
						<button onclick="MapView.showUnitDetails(${unit.id})" style="margin-top:8px; padding:6px 12px; background:#667eea; color:#fff; border:none; border-radius:3px; cursor:pointer;">View Details</button>
					</div>`;

				marker.bindPopup(popup);
				marker.addTo(this.map);
				this.markers.push(marker);
				bounds.push([unit.latitude, unit.longitude]);
			});

			if (bounds.length === 1) {
				this.map.setView(bounds[0], 12);
			} else if (bounds.length > 1) {
				this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
			}
		},

		renderList() {
			if (!this.listContainer) return;
			const filteredUnits = this.getFilteredUnits();
			const sortedUnits = this.sortUnits(filteredUnits);

			if (!sortedUnits.length) {
				this.listContainer.innerHTML = '<div class="lgp-loading">No units found matching filters</div>';
				return;
			}

			this.listContainer.innerHTML = sortedUnits.map(unit => {
				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				const urgency = this.getHighestUrgency(unitTickets) || 'neutral';
				return `
					<div class="lgp-unit-item" onclick="MapView.showUnitDetails(${unit.id})">
						<div class="lgp-unit-name">${this.escapeHtml(unit.name)}</div>
						<div class="lgp-unit-location">${this.escapeHtml(unit.location || 'N/A')}</div>
						<div class="lgp-unit-tickets">
							${unitTickets.length} ticket${unitTickets.length !== 1 ? 's' : ''}
							${unitTickets.length ? `<span class="lgp-urgency-badge ${urgency}">${urgency.toUpperCase()}</span>` : ''}
						</div>
					</div>`;
			}).join('');
		},

		getFilteredUnits() {
			return this.units.filter(unit => {
				if (this.filters.type !== 'all' && unit.type !== this.filters.type) return false;
				if (this.filters.search) {
					const s = this.filters.search.toLowerCase();
					if (!unit.name?.toLowerCase().includes(s) && !unit.location?.toLowerCase().includes(s)) return false;
				}

				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				if (this.filters.urgency && !unitTickets.some(t => t.urgency === this.filters.urgency)) return false;
				if (this.filters.status && !unitTickets.some(t => t.status === this.filters.status)) return false;

				return true;
			});
		},

		sortUnits(units) {
			const withTickets = units.map(unit => ({ ...unit, tickets: this.tickets.filter(t => t.unit_id === unit.id) }));
			switch (this.sortBy) {
				case 'urgency-desc':
					return withTickets.sort((a, b) => {
						const order = { critical: 0, high: 1, medium: 2, low: 3, neutral: 4 };
						return (order[this.getHighestUrgency(a.tickets) || 'neutral']) - (order[this.getHighestUrgency(b.tickets) || 'neutral']);
					});
				case 'date-desc':
					return withTickets.sort((a, b) => {
						const aDate = Math.max(...a.tickets.map(t => new Date(t.created_at).getTime() || 0), 0);
						const bDate = Math.max(...b.tickets.map(t => new Date(t.created_at).getTime() || 0), 0);
						return bDate - aDate;
					});
				case 'date-asc':
					return withTickets.sort((a, b) => {
						const aDate = Math.min(...a.tickets.map(t => new Date(t.created_at).getTime() || Infinity), Infinity);
						const bDate = Math.min(...b.tickets.map(t => new Date(t.created_at).getTime() || Infinity), Infinity);
						return aDate - bDate;
					});
				case 'location':
					return withTickets.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
				default:
					return withTickets;
			}
		},

		applyFilters() {
			this.renderMap();
			this.renderList();
		},

		resetFilters() {
			this.filters = { urgency: '', status: '', type: 'all', search: '' };
			this.sortBy = 'urgency-desc';
			if (this.urgencyFilter) this.urgencyFilter.value = '';
			if (this.statusFilter) this.statusFilter.value = '';
			if (this.typeFilter) this.typeFilter.value = 'all';
			if (this.sortSelect) this.sortSelect.value = 'urgency-desc';
			this.applyFilters();
		},

		showUnitDetails(unitId) {
			const unit = this.units.find(u => u.id === unitId);
			if (!unit || !this.modal || !this.modalBody) return;
			const unitTickets = this.tickets.filter(t => t.unit_id === unitId);

			let html = `
				<h2>${this.escapeHtml(unit.name)}</h2>
				<div style="margin-bottom:16px; padding-bottom:16px; border-bottom:1px solid #e0e0e0;">
					<p><strong>Type:</strong> ${this.escapeHtml(unit.type || 'N/A')}</p>
					<p><strong>Location:</strong> ${this.escapeHtml(unit.location || 'N/A')}</p>
					<p><strong>Active Tickets:</strong> ${unitTickets.length}</p>
				</div>`;

			if (unitTickets.length) {
				html += '<h3>Service Tickets</h3><div style="max-height:400px; overflow-y:auto;">';
				unitTickets.forEach(ticket => {
					const urgencyColor = this.getUrgencyColor(ticket.urgency);
					html += `
						<div style="margin-bottom:12px; padding:12px; background:#f9f9f9; border-radius:4px;">
							<div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
								<strong>${this.escapeHtml(ticket.title)}</strong>
								<span style="background:${urgencyColor}; color:#fff; padding:2px 8px; border-radius:3px; font-size:0.8rem;">${(ticket.urgency || '').toUpperCase()}</span>
							</div>
							<p style="margin:0 0 4px 0; color:#666; font-size:0.9rem;">${this.escapeHtml(ticket.description || 'No description')}</p>
							<p style="margin:0; color:#999; font-size:0.85rem;">Status: <strong>${this.escapeHtml(ticket.status || 'unknown')}</strong></p>
						</div>`;
				});
				html += '</div>';
			} else {
				html += '<p style="color:#999; font-style:italic;">No active service tickets</p>';
			}

			this.modalBody.innerHTML = html;
			this.modal.style.display = 'flex';
		},

		hideModal() {
			if (this.modal) this.modal.style.display = 'none';
		},

		getHighestUrgency(tickets) {
			if (!tickets || !tickets.length) return null;
			const order = { critical: 0, high: 1, medium: 2, low: 3, neutral: 4 };
			return tickets.reduce((highest, ticket) => {
				const tUrgency = ticket.urgency || 'neutral';
				if (!highest) return tUrgency;
				return order[tUrgency] < order[highest] ? tUrgency : highest;
			}, null);
		},

		getUrgencyColor(urgency) {
			const colors = {
				critical: '#d32f2f',
				high: '#f57c00',
				medium: '#fbc02d',
				low: '#388e3c',
				neutral: '#9e9e9e',
			};
			return colors[urgency] || colors.neutral;
		},

		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text ?? '';
			return div.innerHTML;
		},
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => MapView.init());
	} else {
		MapView.init();
	}

	window.MapView = MapView;
})();
