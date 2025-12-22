/**
 * Company Profile Partner View Polish
 * Handles collapsible sections, read-only badges, and view-specific enhancements
 *
 * @package LounGenie_Portal
 * @since 1.4.0
 */

( function() {
	'use strict';

	const STORAGE_KEY = 'lgp-profile-sections';

	/**
	 * Initialize collapsible section handlers
	 */
	function initializeCollapsibleSections() {
		const headers = document.querySelectorAll( '.lgp-card-header.collapsible' );

		headers.forEach( ( header ) => {
			// Load saved state from localStorage
			const sectionId = header.getAttribute( 'data-section' );
			const savedState = localStorage.getItem( `${STORAGE_KEY}-${sectionId}` );
			const card = header.closest( '.lgp-card' );
			const body = card.querySelector( '.lgp-card-body' );

			// Set initial state
			if ( savedState === 'collapsed' ) {
				card.classList.add( 'collapsed' );
				body.style.display = 'none';
			}

			// Add collapse/expand button if not exists
			if ( ! header.querySelector( '.collapse-toggle' ) ) {
				const toggle = document.createElement( 'button' );
				toggle.className = 'collapse-toggle';
				toggle.setAttribute( 'aria-label', 'Toggle section' );
				toggle.setAttribute( 'type', 'button' );
				toggle.innerHTML = '<span class="collapse-icon">▼</span>';
				header.appendChild( toggle );
			}

			// Click handler
			header.addEventListener( 'click', function( e ) {
				// Don't toggle on button/input clicks within the header
				if ( e.target.tagName === 'BUTTON' || e.target.tagName === 'INPUT' ) {
					return;
				}

				e.preventDefault();
				toggleSection( card, body, sectionId );
			} );

			// Also allow clicking the toggle button
			const toggle = header.querySelector( '.collapse-toggle' );
			if ( toggle ) {
				toggle.addEventListener( 'click', ( e ) => {
					e.stopPropagation();
					toggleSection( card, body, sectionId );
				} );
			}
		} );
	}

	/**
	 * Toggle section collapsed state
	 */
	function toggleSection( card, body, sectionId ) {
		const isCollapsed = card.classList.toggle( 'collapsed' );

		// Animate body
		if ( isCollapsed ) {
			body.style.maxHeight = body.scrollHeight + 'px';
			// Trigger reflow for animation
			body.offsetHeight;
			body.style.maxHeight = '0';
			body.style.overflow = 'hidden';
		} else {
			body.style.maxHeight = body.scrollHeight + 'px';
		}

		// Save state
		const state = isCollapsed ? 'collapsed' : 'expanded';
		localStorage.setItem( `${STORAGE_KEY}-${sectionId}`, state );

		// Update icon
		const icon = card.querySelector( '.collapse-icon' );
		if ( icon ) {
			icon.textContent = isCollapsed ? '▶' : '▼';
		}
	}

	/**
	 * Add read-only badges for partner users
	 */
	function addReadOnlyBadges() {
		if ( ! window.lgpIsSupport ) {
			const supportOnlySections = document.querySelectorAll( '[data-support-only="true"]' );

			supportOnlySections.forEach( ( section ) => {
				const header = section.querySelector( '.lgp-card-header' );
				if ( header && ! header.querySelector( '.badge-read-only' ) ) {
					const badge = document.createElement( 'span' );
					badge.className = 'badge-read-only';
					badge.textContent = 'Support Only';
					badge.setAttribute( 'title', 'This section is only visible to support users' );
					header.appendChild( badge );
				}
			} );

			// Add visual indicator to cards for partner view
			const cards = document.querySelectorAll( '.lgp-card' );
			cards.forEach( ( card ) => {
				if ( card.classList.contains( 'partner-read-only' ) ) {
					card.classList.add( 'is-read-only' );
				}
			} );
		}
	}

	/**
	 * Initialize tooltips for read-only fields
	 */
	function initializeReadOnlyTooltips() {
		if ( ! window.lgpIsSupport ) {
			// Add disabled state to edit buttons for partners
			const editButtons = document.querySelectorAll( '[data-action="edit"]' );
			editButtons.forEach( ( button ) => {
				if ( button.getAttribute( 'data-partner-restricted' ) === 'true' ) {
					button.disabled = true;
					button.classList.add( 'disabled' );
					button.setAttribute( 'title', 'Edit is not available for partners' );
				}
			} );

			const deleteButtons = document.querySelectorAll( '[data-action="delete"]' );
			deleteButtons.forEach( ( button ) => {
				if ( button.getAttribute( 'data-partner-restricted' ) === 'true' ) {
					button.disabled = true;
					button.classList.add( 'disabled' );
					button.setAttribute( 'title', 'Delete is not available for partners' );
				}
			} );
		}
	}

	/**
	 * Add expand all / collapse all controls
	 */
	function addBulkToggleControls() {
		const firstCard = document.querySelector( '.lgp-card' );
		if ( ! firstCard || document.querySelector( '.bulk-toggle-controls' ) ) {
			return;
		}

		const controls = document.createElement( 'div' );
		controls.className = 'bulk-toggle-controls';
		controls.style.marginBottom = '20px';

		const expandBtn = document.createElement( 'button' );
		expandBtn.className = 'lgp-btn lgp-btn-secondary';
		expandBtn.type = 'button';
		expandBtn.textContent = 'Expand All';
		expandBtn.addEventListener( 'click', () => expandAllSections() );

		const collapseBtn = document.createElement( 'button' );
		collapseBtn.className = 'lgp-btn lgp-btn-secondary';
		collapseBtn.type = 'button';
		collapseBtn.textContent = 'Collapse All';
		collapseBtn.style.marginLeft = '10px';
		collapseBtn.addEventListener( 'click', () => collapseAllSections() );

		controls.appendChild( expandBtn );
		controls.appendChild( collapseBtn );

		// Insert before first card
		firstCard.parentNode.insertBefore( controls, firstCard );
	}

	/**
	 * Expand all collapsible sections
	 */
	function expandAllSections() {
		const headers = document.querySelectorAll( '.lgp-card-header.collapsible' );
		headers.forEach( ( header ) => {
			const card = header.closest( '.lgp-card' );
			const body = card.querySelector( '.lgp-card-body' );
			const sectionId = header.getAttribute( 'data-section' );

			if ( card.classList.contains( 'collapsed' ) ) {
				card.classList.remove( 'collapsed' );
				body.style.maxHeight = body.scrollHeight + 'px';

				const icon = card.querySelector( '.collapse-icon' );
				if ( icon ) {
					icon.textContent = '▼';
				}

				localStorage.setItem( `${STORAGE_KEY}-${sectionId}`, 'expanded' );
			}
		} );
	}

	/**
	 * Collapse all collapsible sections
	 */
	function collapseAllSections() {
		const headers = document.querySelectorAll( '.lgp-card-header.collapsible' );
		headers.forEach( ( header ) => {
			const card = header.closest( '.lgp-card' );
			const body = card.querySelector( '.lgp-card-body' );
			const sectionId = header.getAttribute( 'data-section' );

			if ( ! card.classList.contains( 'collapsed' ) ) {
				card.classList.add( 'collapsed' );
				body.style.maxHeight = '0';
				body.style.overflow = 'hidden';

				const icon = card.querySelector( '.collapse-icon' );
				if ( icon ) {
					icon.textContent = '▶';
				}

				localStorage.setItem( `${STORAGE_KEY}-${sectionId}`, 'collapsed' );
			}
		} );
	}

	/**
	 * Remove support-only sections if partner view
	 */
	function filterSupportOnlySections() {
		if ( ! window.lgpIsSupport ) {
			// Hide audit log and service notes sections for partners
			const sections = document.querySelectorAll( '[data-support-only="true"]' );
			sections.forEach( ( section ) => {
				section.style.display = 'none';
			} );
		}
	}

	/**
	 * Add section-specific styling for partner view
	 */
	function addPartnerViewStyling() {
		if ( ! window.lgpIsSupport ) {
			const companyInfoCard = document.querySelector( '[data-section="company-info"]' );
			if ( companyInfoCard ) {
				companyInfoCard.classList.add( 'partner-view-primary' );
			}

			const unitsCard = document.querySelector( '[data-section="units"]' );
			if ( unitsCard ) {
				unitsCard.classList.add( 'partner-view-primary' );
			}
		}
	}

	/**
	 * Handle window resize for responsive collapse animations
	 */
	function handleResponsiveResize() {
		let resizeTimeout;

		window.addEventListener( 'resize', () => {
			clearTimeout( resizeTimeout );
			resizeTimeout = setTimeout( () => {
				// Update maxHeight for expanded sections on resize
				const cards = document.querySelectorAll( '.lgp-card:not(.collapsed)' );
				cards.forEach( ( card ) => {
					const body = card.querySelector( '.lgp-card-body' );
					if ( body && body.style.maxHeight ) {
						body.style.maxHeight = body.scrollHeight + 'px';
					}
				} );
			}, 100 );
		} );
	}

	/**
	 * Initialize all partner view enhancements
	 */
	function init() {
		// Filter support-only sections first
		filterSupportOnlySections();

		// Initialize collapsible sections
		initializeCollapsibleSections();

		// Add read-only badges and styling
		addReadOnlyBadges();
		addPartnerViewStyling();
		initializeReadOnlyTooltips();

		// Add bulk toggle controls if there are collapsible sections
		const collapsibleHeaders = document.querySelectorAll( '.lgp-card-header.collapsible' );
		if ( collapsibleHeaders.length > 1 ) {
			addBulkToggleControls();
		}

		// Handle responsive behavior
		handleResponsiveResize();
	}

	// Initialize when DOM is ready
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	// Expose functions for testing/debugging
	window.LGPPartnerPolish = {
		toggleSection,
		expandAllSections,
		collapseAllSections,
		init,
	};
} )();
