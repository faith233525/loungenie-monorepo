<?php
/**
 * Assets Management Class
 * Handles CSS and JS enqueuing for the portal
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LGP_Assets {

	/**
	 * Initialize assets management
	 */
	public static function init() {
		// Assets are enqueued by router when needed
	}

	/**
	 * Enqueue portal assets (CSS and JS)
	 * Called by router when loading portal
	 */
	public static function enqueue_portal_assets() {
		// Safety check: don't enqueue in WordPress admin
		if ( is_admin() ) {
			return;
		}
		// Resource hints for faster connections to external CDNs used by the portal
		add_filter(
			'wp_resource_hints',
			function ( $hints, $relation_type ) {
				$domains = array(
					'fonts.googleapis.com',
					'fonts.gstatic.com',
					'cdnjs.cloudflare.com',
					'unpkg.com',
				);
				if ( 'preconnect' === $relation_type || 'dns-prefetch' === $relation_type ) {
					$hints = array_unique( array_merge( $hints, $domains ) );
				}
				return $hints;
			},
			10,
			2
		);
		// Enqueue Montserrat font (brand typography)
		wp_enqueue_style(
			'lgp-font-montserrat',
			'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap',
			array(),
			null,
			'all'
		);
		// Enqueue FontAwesome for consistent iconography
		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
			array( 'lgp-font-montserrat' ),
			'6.5.1',
			'all'
		);

		// Enqueue design tokens first (CSS variables)
		wp_enqueue_style(
			'lgp-design-tokens',
			LGP_ASSETS_URL . 'css/design-tokens.css',
			array( 'font-awesome' ),
			LGP_VERSION,
			'all'
		);

		// Enqueue portal components (modern UI matching design demo)
		wp_enqueue_style(
			'lgp-portal-components',
			LGP_ASSETS_URL . 'css/portal-components.css',
			array( 'lgp-design-tokens' ),
			LGP_VERSION,
			'all'
		);

		// Enqueue the refactored design system next (base styles, utilities, components)
		wp_enqueue_style(
			'lgp-design-system',
			LGP_ASSETS_URL . 'css/design-system-refactored.css',
			array( 'lgp-portal-components' ),
			LGP_VERSION,
			'all'
		);

		// Enqueue portal CSS (overrides and portal-specific layout)
		wp_enqueue_style(
			'lgp-portal',
			LGP_ASSETS_URL . 'css/portal.css',
			array( 'lgp-design-system' ),
			LGP_VERSION,
			'all'
		);

		// Admin role switcher widget styles
		wp_enqueue_style(
			'lgp-role-switcher',
			LGP_ASSETS_URL . 'css/role-switcher.css',
			array( 'lgp-portal' ),
			LGP_VERSION,
			'all'
		);

		// Leaflet assets for support-only map
		if ( LGP_Auth::is_support() ) {
			wp_enqueue_style(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				array(),
				'1.9.4'
			);
			wp_enqueue_script(
				'leaflet',
				'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				array(),
				'1.9.4',
				true
			);
		}

		// Utilities library (shared helpers used across portal scripts)
		wp_enqueue_script(
			'lgp-utils',
			LGP_ASSETS_URL . 'js/lgp-utils.js',
			array(),
			LGP_VERSION,
			true
		);

		// Enqueue portal JS
		wp_enqueue_script(
			'lgp-portal',
			LGP_ASSETS_URL . 'js/portal.js',
			array( 'lgp-utils' ),
			LGP_VERSION,
			true
		);

		// Ensure WordPress REST API settings (wpApiSettings) are available for nonces
		// This provides wpApiSettings.root and wpApiSettings.nonce used by our JS
		wp_enqueue_script( 'wp-api' );

		// Enqueue portal initialization (sidebar toggle, CSP-compliant)
		wp_enqueue_script(
			'lgp-portal-init',
			LGP_ASSETS_URL . 'js/portal-init.js',
			array(),
			LGP_VERSION,
			true
		);

		// Enqueue company profile enhancements (for inline modals, audit log, service notes)
		wp_enqueue_script(
			'lgp-company-profile-enhancements',
			LGP_ASSETS_URL . 'js/company-profile-enhancements.js',
			array(),
			LGP_VERSION,
			true
		);

		// Enqueue company profile partner polish (for collapsible sections, read-only badges)
		wp_enqueue_script(
			'lgp-company-profile-partner-polish',
			LGP_ASSETS_URL . 'js/company-profile-partner-polish.js',
			array(),
			LGP_VERSION,
			true
		);

		if ( LGP_Auth::is_support() ) {
			wp_enqueue_script(
				'lgp-company-map',
				LGP_ASSETS_URL . 'js/lgp-map.js',
				array( 'leaflet' ),
				LGP_VERSION,
				true
			);
		}

		// Responsive sidebar controller for mobile/off-canvas behavior
		wp_enqueue_script(
			'lgp-responsive-sidebar',
			LGP_ASSETS_URL . 'js/responsive-sidebar.js',
			array( 'lgp-portal' ),
			LGP_VERSION,
			true
		);

		// Demo portal enhancements: client-side filters for Units & Tickets
		wp_enqueue_script(
			'lgp-portal-demo',
			LGP_ASSETS_URL . 'js/portal-demo.js',
			array( 'lgp-portal' ),
			LGP_VERSION,
			true
		);

		// Prepare localized data for portal
		$company_name = method_exists( 'LGP_Auth', 'get_company_name' ) ? LGP_Auth::get_company_name() : '';
		$current_user = wp_get_current_user();
		$rest_nonce   = wp_create_nonce( 'wp_rest' );

		// Localize script with AJAX data
		wp_localize_script(
			'lgp-portal',
			'lgpData',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'restUrl'     => rest_url( 'lgp/v1/' ),
				'nonce'       => wp_create_nonce( 'lgp_portal_nonce' ),
				'restNonce'   => $rest_nonce,
				'isSupport'   => LGP_Auth::is_support(),
				'isPartner'   => LGP_Auth::is_partner(),
				'companyName' => $company_name,
				'userEmail'   => $current_user ? $current_user->user_email : '',
				'userName'    => $current_user ? $current_user->display_name : '',
			)
		);

		// Support-only map data
		if ( LGP_Auth::is_support() ) {
			$markers = class_exists( 'LGP_Geocode' ) ? LGP_Geocode::get_company_markers_for_map() : array();
			wp_localize_script(
				'lgp-company-map',
				'lgpCompanyMap',
				array(
					'markers'         => $markers,
					'tileUrl'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
					'tileAttribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
				)
			);
		}
	}
}
