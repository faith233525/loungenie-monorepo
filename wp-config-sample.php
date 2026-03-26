<?php

/**
 * Sample wp-config for repository (no secrets). Copy to wp-config.php
 * and fill real values, or let CI render it from repository secrets.
 */
define('DB_NAME', 'database_name_here');
define('DB_USER', 'database_user_here');
define('DB_PASSWORD', 'database_password_here');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

$table_prefix = 'wp_';

define('WP_DEBUG', false);

/* That's all, stop editing! Happy publishing. */

if (! defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}
require_once ABSPATH . 'wp-settings.php';
