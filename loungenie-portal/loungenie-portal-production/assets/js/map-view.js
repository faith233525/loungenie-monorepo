/**
 * Map View JavaScript
 * Handles map initialization, filtering, and AJAX operations
 */

(function () {
	'use strict';

	const MapView = {
		map: null,
		markers: [],
		units: [],
		tickets: [],
		filters: {
			urgency: '',
			status: '',
			type: 'all',
			search: '',
		},
		sortBy: 'urgency-desc',

		/**
		 * Initialize the map view
		 */
		init() {
			this.initMap();
			this.setupEventListeners();
			this.loadData();
		},

		/**
		 * Initialize Leaflet map
		 */
		initMap() {
			const mapElement = document.getElementById('map');
			if (!mapElement) return;

			// Center on US (approximate)
			this.map = L.map('map').setView([39.8283, -98.5795], 4);

			L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '© OpenStreetMap contributors',
				maxZoom: 19,
			}).addTo(this.map);
		},

		/**
		 * Set up event listeners
		 */
		setupEventListeners() {
			const urgencyFilter = document.getElementById('urgency-filter');
			const statusFilter = document.getElementById('status-filter');
			const typeFilter = document.getElementById('type-filter');
			const sortSelect = document.getElementById('sort-select');
			const resetBtn = document.getElementById('reset-filters');
			const modalClose = document.querySelector('.lgp-modal-close');

			if (urgencyFilter) urgencyFilter.addEventListener('change', (e) => {
				this.filters.urgency = e.target.value;
				this.applyFilters();
			});

			if (statusFilter) statusFilter.addEventListener('change', (e) => {
				this.filters.status = e.target.value;
				this.applyFilters();
			});

			if (typeFilter) typeFilter.addEventListener('change', (e) => {
				this.filters.type = e.target.value;
				this.applyFilters();
			});

			if (sortSelect) sortSelect.addEventListener('change', (e) => {
				this.sortBy = e.target.value;
				this.renderList();
			});

			if (resetBtn) resetBtn.addEventListener('click', () => this.resetFilters());

			if (modalClose) modalClose.addEventListener('click', () => {
				document.getElementById('detail-modal').style.display = 'none';
			});

			// Close modal on outside click
			document.getElementById('detail-modal')?.addEventListener('click', (e) => {
				if (e.target.id === 'detail-modal') {
					e.target.style.display = 'none';
				}
			});
		},

		/**
		 * Load units and tickets data
		 */
		loadData() {
			const loading = document.querySelector('.lgp-loading');
			if (loading) loading.textContent = 'Loading...';

			// Fetch units for map via REST API
			const restUrl = '/wp-json/lgp/v1/map/units';
			const headers = {};
			// Prefer core wpApiSettings nonce; fallback to localized lgpData.restNonce if available
			if (typeof window.wpApiSettings !== 'undefined' && wpApiSettings.nonce) {
				headers['X-WP-Nonce'] = wpApiSettings.nonce;
			} else if (typeof window.lgpData !== 'undefined' && lgpData.restNonce) {
				headers['X-WP-Nonce'] = lgpData.restNonce;
			}

			fetch(restUrl, {
				method: 'GET',
				headers,
				credentials: 'same-origin',
			})
				.then(response => response.json())
				.then(data => {
					if (data && Array.isArray(data.units)) {
						this.units = data.units || [];
						// Tickets are optional in REST response; keep empty array if not returned
						this.tickets = data.tickets || [];
						this.renderMap();
						this.renderList();
						if (loading) loading.textContent = '';
					} else {
						console.error('Failed to load map data (unexpected shape):', data);
						if (loading) loading.textContent = 'Failed to load data';
					}
				})
				.catch(error => {
					console.error('Error loading map data:', error);
					if (loading) loading.textContent = 'Error loading data';
				});
		},

		/**
		 * Render markers on map
		 */
		renderMap() {
			// Clear existing markers
			this.markers.forEach(marker => this.map.removeLayer(marker));
			this.markers = [];

			const filteredUnits = this.getFilteredUnits();

			filteredUnits.forEach(unit => {
				if (!unit.latitude || !unit.longitude) return;

				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				const urgency = this.getHighestUrgency(unitTickets);
				const color = this.getUrgencyColor(urgency);

				const marker = L.circleMarker([unit.latitude, unit.longitude], {
					radius: 8,
					fillColor: color,
					color: 'white',
					weight: 3,
					opacity: 1,
					fillOpacity: 0.9,
				});

				const popup = `
					<div style="min-width: 200px;">
						<h4 style="margin: 0 0 8px 0; color: #333;">${this.escapeHtml(unit.name)}</h4>
						<p style="margin: 0 0 4px 0; font-size: 0.9rem; color: #666;">
							<strong>Type:</strong> ${this.escapeHtml(unit.type || 'N/A')}
						</p>
						<p style="margin: 0 0 4px 0; font-size: 0.9rem; color: #666;">
							<strong>Tickets:</strong> ${unitTickets.length}
						</p>
						<button onclick="MapView.showUnitDetails(${unit.id})" style="margin-top: 8px; padding: 6px 12px; background: #667eea; color: white; border: none; border-radius: 3px; cursor: pointer;">
							View Details
						</button>
					</div>
				`;

				marker.bindPopup(popup);
				marker.addTo(this.map);
				this.markers.push(marker);
			});
		},

		/**
		 * Render units list
		 */
		renderList() {
			const listContainer = document.getElementById('units-list');
			if (!listContainer) return;

			const filteredUnits = this.getFilteredUnits();
			const sortedUnits = this.sortUnits(filteredUnits);

			if (sortedUnits.length === 0) {
				listContainer.innerHTML = '<div class="lgp-loading">No units found matching filters</div>';
				return;
			}

			listContainer.innerHTML = sortedUnits.map(unit => {
				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				const urgency = this.getHighestUrgency(unitTickets);
				const urgencyClass = urgency || 'neutral';

				return `
					<div class="lgp-unit-item" onclick="MapView.showUnitDetails(${unit.id})">
						<div class="lgp-unit-name">${this.escapeHtml(unit.name)}</div>
						<div class="lgp-unit-location">${this.escapeHtml(unit.location || 'N/A')}</div>
						<div class="lgp-unit-tickets">
							${unitTickets.length} ticket${unitTickets.length !== 1 ? 's' : ''}
							${unitTickets.length > 0 ? `<span class="lgp-urgency-badge ${urgencyClass}">${urgency.toUpperCase()}</span>` : ''}
						</div>
					</div>
				`;
			}).join('');
		},

		/**
		 * Get filtered units based on current filters
		 */
		getFilteredUnits() {
			return this.units.filter(unit => {
				// Type filter
				if (this.filters.type !== 'all' && unit.type !== this.filters.type) {
					return false;
				}

				// Search filter
				if (this.filters.search) {
					const search = this.filters.search.toLowerCase();
					if (!unit.name.toLowerCase().includes(search) &&
						!unit.location.toLowerCase().includes(search)) {
						return false;
					}
				}

				// Urgency and status filters
				const unitTickets = this.tickets.filter(t => t.unit_id === unit.id);
				if (unitTickets.length === 0) {
					return this.filters.urgency === '' && this.filters.status === '';
				}

				if (this.filters.urgency && !unitTickets.some(t => t.urgency === this.filters.urgency)) {
					return false;
				}

				if (this.filters.status && !unitTickets.some(t => t.status === this.filters.status)) {
					return false;
				}

				return true;
			});
		},

		/**
		 * Sort units by selected option
		 */
		sortUnits(units) {
			const unitsWithTickets = units.map(unit => ({
				...unit,
				tickets: this.tickets.filter(t => t.unit_id === unit.id),
			}));

			switch (this.sortBy) {
				case 'urgency-desc':
					return unitsWithTickets.sort((a, b) => {
						const urgencyOrder = { critical: 0, high: 1, medium: 2, low: 3 };
						const aUrgency = this.getHighestUrgency(a.tickets) || 'low';
						const bUrgency = this.getHighestUrgency(b.tickets) || 'low';
						return urgencyOrder[aUrgency] - urgencyOrder[bUrgency];
					});
				case 'date-desc':
					return unitsWithTickets.sort((a, b) => {
						const aDate = Math.max(...a.tickets.map(t => new Date(t.created_at)), 0);
						const bDate = Math.max(...b.tickets.map(t => new Date(t.created_at)), 0);
						return bDate - aDate;
					});
				case 'date-asc':
					return unitsWithTickets.sort((a, b) => {
						const aDate = Math.min(...a.tickets.map(t => new Date(t.created_at)), Infinity);
						const bDate = Math.min(...b.tickets.map(t => new Date(t.created_at)), Infinity);
						return aDate - bDate;
					});
				case 'location':
					return unitsWithTickets.sort((a, b) => a.name.localeCompare(b.name));
				default:
					return unitsWithTickets;
			}
		},

		/**
		 * Apply filters and re-render
		 */
		applyFilters() {
			this.renderMap();
			this.renderList();
		},

		/**
		 * Reset all filters
		 */
		resetFilters() {
			this.filters = {
				urgency: '',
				status: '',
				type: 'all',
				search: '',
			};
			this.sortBy = 'urgency-desc';

			document.getElementById('urgency-filter').value = '';
			document.getElementById('status-filter').value = '';
			document.getElementById('type-filter').value = 'all';
			document.getElementById('sort-select').value = 'urgency-desc';

			this.applyFilters();
		},

		/**
		 * Show unit details in modal
		 */
		showUnitDetails(unitId) {
			const unit = this.units.find(u => u.id === unitId);
			const unitTickets = this.tickets.filter(t => t.unit_id === unitId);

			if (!unit) return;

			const modal = document.getElementById('detail-modal');
			const modalBody = document.getElementById('modal-body');

			let html = `
				<h2>${this.escapeHtml(unit.name)}</h2>
				<div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #e0e0e0;">
					<p><strong>Type:</strong> ${this.escapeHtml(unit.type || 'N/A')}</p>
					<p><strong>Location:</strong> ${this.escapeHtml(unit.location || 'N/A')}</p>
					<p><strong>Active Tickets:</strong> ${unitTickets.length}</p>
				</div>
			`;

			if (unitTickets.length > 0) {
				html += '<h3>Service Tickets</h3>';
				html += '<div style="max-height: 400px; overflow-y: auto;">';

				unitTickets.forEach(ticket => {
					const urgencyColor = this.getUrgencyColor(ticket.urgency);
					html += `
						<div style="margin-bottom: 12px; padding: 12px; background: #f9f9f9; border-radius: 4px;">
							<div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
								<strong>${this.escapeHtml(ticket.title)}</strong>
								<span style="background: ${urgencyColor}; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8rem;">
									${ticket.urgency.toUpperCase()}
								</span>
							</div>
							<p style="margin: 0 0 4px 0; color: #666; font-size: 0.9rem;">${this.escapeHtml(ticket.description || 'No description')}</p>
							<p style="margin: 0; color: #999; font-size: 0.85rem;">Status: <strong>${this.escapeHtml(ticket.status)}</strong></p>
						</div>
					`;
				});

				html += '</div>';
			} else {
				html += '<p style="color: #999; font-style: italic;">No active service tickets</p>';
			}

			modalBody.innerHTML = html;
			modal.style.display = 'flex';
		},

		/**
		 * Get highest urgency from array of tickets
		 */
		getHighestUrgency(tickets) {
			if (!tickets || tickets.length === 0) return null;
			const urgencyOrder = { critical: 0, high: 1, medium: 2, low: 3 };
			return tickets.reduce((highest, ticket) => {
				const order = urgencyOrder[ticket.urgency] ?? 4;
				const highestOrder = urgencyOrder[highest] ?? 4;
				return order < highestOrder ? ticket.urgency : highest;
			}, null);
		},

		/**
		 * Get color for urgency level
		 */
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

		/**
		 * Escape HTML special characters
		 */
		escapeHtml(text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		},
	};

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => MapView.init());
	} else {
		MapView.init();
	}

	// Expose to global scope for inline onclick handlers
	window.MapView = MapView;
})();
