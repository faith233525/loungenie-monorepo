<?php
// Usage: wp eval-file wp-cli/lgp-backfill-geocode.php

if (! defined('WP_CLI') ) {
    return;
}

class LGP_Backfill_Geocode
{
    // Throttle to respect Nominatim usage policy
    const THROTTLE_SEC = 1;

    public static function run()
    {
        global $wpdb;

        $companies = $wpdb->get_results("SELECT id, name, address, state FROM {$wpdb->prefix}lgp_companies");

        if (empty($companies) ) {
            WP_CLI::log('No companies found.');
            return;
        }

        foreach ( $companies as $company ) {
            $option_key = 'lgp_geocode_' . (int) $company->id;
            $cached     = get_option($option_key);

            if (! empty($cached) && isset($cached['lat'], $cached['lng']) ) {
                WP_CLI::log("{$company->name} already cached: {$cached['lat']}, {$cached['lng']}");
                continue;
            }

            $query = trim(implode(', ', array_filter(array( $company->address, $company->state ))));

            if (empty($query) ) {
                WP_CLI::warning("Skipping {$company->name}: missing address/state");
                continue;
            }

            $url = add_query_arg(
                array(
                'format' => 'json',
                'q'      => $query,
                'limit'  => 1,
                ), 'https://nominatim.openstreetmap.org/search' 
            );

            WP_CLI::log("Geocoding: {$company->name}");

            $response = wp_remote_get(
                $url, array(
                'timeout' => 10,
                'headers' => array( 'User-Agent' => 'LounGeniePortal/1.0 (backfill)' ),
                ) 
            );

            if (is_wp_error($response) ) {
                WP_CLI::warning("Failed request for {$company->name}: {$response->get_error_message()}");
                sleep(self::THROTTLE_SEC);
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);

            if (empty($data[0]['lat']) || empty($data[0]['lon']) ) {
                WP_CLI::warning("No coordinates found for {$company->name}");
                sleep(self::THROTTLE_SEC);
                continue;
            }

            $coords = array(
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon'],
            );

            update_option($option_key, $coords);

            WP_CLI::success("{$company->name} => Lat: {$coords['lat']}, Lng: {$coords['lng']}");

            sleep(self::THROTTLE_SEC);
        }

        WP_CLI::success('Backfill completed.');
    }
}

LGP_Backfill_Geocode::run();
