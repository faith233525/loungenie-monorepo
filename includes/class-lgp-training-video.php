<?php

/**
 * Training Video (Help Guide) compatibility shim.
 *
 * Some templates reference LGP_Training_Video; the primary implementation
 * lives in LGP_Help_Guide. This class proxies static calls to maintain
 * backward compatibility and prevent fatals.
 *
 * @package LounGenie Portal
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only define if missing to avoid collisions in future updates.
if ( ! class_exists( 'LGP_Training_Video' ) && class_exists( 'LGP_Help_Guide' ) ) {
	class LGP_Training_Video extends LGP_Help_Guide {

		// Intentionally empty: inherits all static helpers from LGP_Help_Guide.
	}
}
