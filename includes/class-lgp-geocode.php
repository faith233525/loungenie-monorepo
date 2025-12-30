<?php

/**
 * Lightweight geocoding helper using free Nominatim (OpenStreetMap)
 * Caches coordinates on the company record to avoid repeat lookups.
 */

if (! defined('ABSPATH')) {
	exit;
}

class LGP_Geocode
{

	private const ENDPOINT     = 'https://nominatim.openstreetmap.org/search';
	private const CACHE_PREFIX = 'lgp_geocode_';

	/**
	 * Initialize geocoding system with background processing
	 *
	 * @return void
	 */
	public static function init()
	{
		// Schedule background geocoding cron job
		if (! wp_next_scheduled('lgp_geocode_background_process')) {
			wp_schedule_event(time(), 'hourly', 'lgp_geocode_background_process');
		}

		add_action('lgp_geocode_background_process', array(__CLASS__, 'process_geocode_queue'));
	}

	/**
	 * Queue a company for background geocoding
	 *
	 * PERFORMANCE OPTIMIZATION: Avoid blocking requests with external API calls
	 *
	 * @param int $company_id Company ID to geocode
	 * @return void
	 */
	public static function queue_geocode($company_id)
	{
		$queue = get_option('lgp_geocode_queue', array());
		if (!in_array($company_id, $queue, true)) {
			$queue[] = $company_id;
			update_option('lgp_geocode_queue', $queue, false);
		}
	}

	/**
	 * Process queued geocoding requests (runs via WP Cron)
	 *
	 * Processes up to 10 companies per run to avoid timeout
	 *
	 * @return void
	 */
	public static function process_geocode_queue()
	{
		global $wpdb;

		$queue = get_option('lgp_geocode_queue', array());
		if (empty($queue)) {
			return;
		}

		$companies_table = $wpdb->prefix . 'lgp_companies';
		$processed       = array();
		$batch_size      = 10; // Process 10 per cron run to avoid timeouts

		foreach (array_slice($queue, 0, $batch_size) as $company_id) {
			$row = $wpdb->get_row($wpdb->prepare(
				"SELECT id, name, address, state, venue_type FROM {$companies_table} WHERE id = %d",
				$company_id
			));

			if ($row) {
				$loc = self::geocode_company_row($row);
				if ($loc) {
					error_log("LounGenie Portal: Geocoded company {$company_id} in background");
				}
			}

			$processed[] = $company_id;

			// Rate limiting: Sleep 1 second between requests (Nominatim requirement)
			sleep(1);
		}

		// Remove processed items from queue
		$queue = array_diff($queue, $processed);
		update_option('lgp_geocode_queue', array_values($queue), false);
	}

	/**
	 * Get markers for all companies (support-only callers should gate access).
	 *
	 * PERFORMANCE: Returns cached coordinates without blocking on external API
	 *
	 * @return array[]
	 */
	public static function get_company_markers()
	{
		global $wpdb;
		$companies_table = $wpdb->prefix . 'lgp_companies';

		// PERFORMANCE: Cache the full marker list for 30 minutes
		$cache_key = 'lgp_company_markers_all';
		$markers   = get_transient($cache_key);

		if (false !== $markers) {
			return $markers;
		}

		$rows    = $wpdb->get_results("SELECT id, name, address, state, venue_type FROM $companies_table");
		$markers = array();

		if (empty($rows)) {
			return $markers;
		}

		foreach ($rows as $row) {
			$loc = self::get_cached_location($row->id);

			// PERFORMANCE: Don't block on API calls - queue for background processing
			if (! $loc) {
				self::queue_geocode($row->id);
				continue; // Skip this marker for now
			}

			if ($loc) {
				$markers[] = array(
					'id'   => (int) $row->id,
					'name' => $row->name,
					'type' => $row->venue_type ?? '',
					'lat'  => $loc['lat'],
					'lng'  => $loc['lng'],
				);
			}
		}

		// Cache markers for 30 minutes
		set_transient($cache_key, $markers, 30 * MINUTE_IN_SECONDS);

		return $markers;
	}

	/**
	 * Support-only wrapper to fetch markers.
	 *
	 * @return array
	 */
	public static function get_company_markers_for_map()
	{
		// Allow when user can manage options (support) or LGP_Auth says support
		if (function_exists('current_user_can') && current_user_can('manage_options')) {
			return self::get_company_markers();
		}

		if (class_exists('LGP_Auth') && LGP_Auth::is_support()) {
			return self::get_company_markers();
		}

		return array();
	}

	/**
	 * Geocode a single company row; caches result back to DB when found.
	 *
	 * @param object $row
	 * @return array|null
	 */
	private static function geocode_company_row($row)
	{
		$parts = array_filter(
			array(
				$row->address ?? '',
				$row->state ?? '',
			)
		);
		$loc   = self::lookup(implode(', ', $parts));

		if ($loc) {
			self::set_cached_location($row->id, $loc);
		}
		return $loc;
	}

	/**
	 * Perform a Nominatim lookup (single result).
	 *
	 * @param string $query
	 * @return array|null
	 */
	private static function lookup($query)
	{
		if (empty($query)) {
			return null;
		}
		$url = add_query_arg(
			array(
				'q'              => $query,
				'format'         => 'json',
				'limit'          => 1,
				'addressdetails' => 0,
			),
			self::ENDPOINT
		);

		$resp = wp_remote_get(
			$url,
			array(
				'headers' => array('User-Agent' => 'LounGeniePortal/1.0 (support@poolsafeinc.com)'),
				'timeout' => 10,
			)
		);
		if (is_wp_error($resp)) {
			return null;
		}
		$body = json_decode(wp_remote_retrieve_body($resp), true);
		if (empty($body[0]['lat']) || empty($body[0]['lon'])) {
			return null;
		}
		return array(
			'lat' => (float) $body[0]['lat'],
			'lng' => (float) $body[0]['lon'],
		);
	}

	/**
	 * Retrieve cached coordinates from wp_options.
	 */
	private static function get_cached_location($company_id)
	{
		$data = get_option(self::CACHE_PREFIX . (int) $company_id);
		if (empty($data['lat']) || empty($data['lng'])) {
			return null;
		}
		return array(
			'lat' => (float) $data['lat'],
			'lng' => (float) $data['lng'],
		);
	}

	/**
	 * Cache coordinates in wp_options.
	 */
	private static function set_cached_location($company_id, $loc)
	{
		update_option(
			self::CACHE_PREFIX . (int) $company_id,
			array(
				'lat'       => (float) $loc['lat'],
				'lng'       => (float) $loc['lng'],
				'cached_at' => current_time('mysql', true),
			),
			false
		);
	}
}
