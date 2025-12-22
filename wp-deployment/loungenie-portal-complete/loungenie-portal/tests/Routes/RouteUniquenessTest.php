<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class RouteUniquenessTest extends TestCase {
    public function test_routes_are_unique_by_namespace_route_method() {
        global $test_registered_routes;
        $test_registered_routes = [];

        // Include API classes and invoke their route registrations
        require_once LGP_PLUGIN_DIR . 'api/dashboard.php';
        require_once LGP_PLUGIN_DIR . 'api/map.php';
        require_once LGP_PLUGIN_DIR . 'api/companies.php';
        require_once LGP_PLUGIN_DIR . 'api/units.php';
        require_once LGP_PLUGIN_DIR . 'api/tickets.php';
        require_once LGP_PLUGIN_DIR . 'api/gateways.php';
        require_once LGP_PLUGIN_DIR . 'api/help-guides.php';
        require_once LGP_PLUGIN_DIR . 'api/attachments.php';
        require_once LGP_PLUGIN_DIR . 'api/service-notes.php';

        // Explicitly register routes by calling static register methods
        if (class_exists('LGP_Dashboard_API')) { LGP_Dashboard_API::register_routes(); }
        if (class_exists('LGP_Map_API')) { LGP_Map_API::register_routes(); }
        if (class_exists('LGP_Companies_API')) { LGP_Companies_API::register_routes(); }
        if (class_exists('LGP_Units_API')) { LGP_Units_API::register_routes(); }
        if (class_exists('LGP_Tickets_API')) { LGP_Tickets_API::register_routes(); }
        if (class_exists('LGP_Gateways_API')) { LGP_Gateways_API::register_routes(); }
        if (class_exists('LGP_Help_Guides_API')) { LGP_Help_Guides_API::register_routes(); }
        if (class_exists('LGP_Attachments_API')) { LGP_Attachments_API::register_routes(); }
        if (class_exists('LGP_Service_Notes_API')) { LGP_Service_Notes_API::register_routes(); }
        if (class_exists('LGP_Email_Integration')) { LGP_Email_Integration::register_routes(); }

        $routes = is_array($test_registered_routes) ? $test_registered_routes : [];
        $unique = [];
        $dupes = [];
        foreach ($routes as $key) {
            if (isset($unique[$key])) {
                $dupes[$key] = ($dupes[$key] ?? 1) + 1; // count occurrences beyond first
            }
            $unique[$key] = true;
        }

        $total       = count($routes);
        $uniqueCount = count($unique);

        if ($uniqueCount !== $total && !empty($dupes)) {
            ksort($dupes);
            $lines = [];
            foreach ($dupes as $key => $count) {
                $lines[] = sprintf('%s (count=%d)', $key, $count + 1);
            }
            $message = "Duplicate REST routes detected:\n" . implode("\n", $lines);
            $this->fail($message);
        }

        $this->assertSame($uniqueCount, $total, 'REST route registrations must be unique by (namespace, route, method)');
    }
}
