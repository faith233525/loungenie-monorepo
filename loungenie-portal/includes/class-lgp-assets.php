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
		// Enqueue portal CSS
		wp_enqueue_style(
			'lgp-portal',
			LGP_ASSETS_URL . 'css/portal.css',
			array(),
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

		// Enqueue portal JS
		wp_enqueue_script(
			'lgp-portal',
			LGP_ASSETS_URL . 'js/portal.js',
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

		// Localize script with AJAX data
		wp_localize_script(
			'lgp-portal',
			'lgpData',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'restUrl'   => rest_url( 'lgp/v1/' ),
				'nonce'     => wp_create_nonce( 'lgp_portal_nonce' ),
				'isSupport' => LGP_Auth::is_support(),
				'isPartner' => LGP_Auth::is_partner(),
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
