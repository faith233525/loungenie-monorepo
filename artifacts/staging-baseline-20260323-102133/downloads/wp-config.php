<?php
define( 'WP_CACHE', true );



/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */
// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'pools425_wp872' );
/** Database username */
define( 'DB_USER', 'pools425_wp872' );
/** Database password */
define( 'DB_PASSWORD', 'p7SFK)8X@3' );
/** Database hostname */
define( 'DB_HOST', 'localhost' );
/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );
/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );
/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'wvtozebt0whuekxy6fpk98m9s6fqqg9uexmhfaz7q79tltz8bzog349hktmfo7fv' );
define( 'SECURE_AUTH_KEY',  'wgtxxbyv7ucqx4gojrcddztxdaqdizru3sudek8snm3ahndlvaesuktzdsitsm1a' );
define( 'LOGGED_IN_KEY',    'ecnodl1iwpcec9ldfa9a1oe1fxji5nrsqscohrpluinugit332gtudihfht58ddr' );
define( 'NONCE_KEY',        'ir252pfgvexo4yzgjhxnwkngqnjkpocosa5puhvnwobruxnsuyrwr2m49bqvhdit' );
define( 'AUTH_SALT',        '5djrigp2tcyeyojwpvnqr5zzltog5tq1v4w6dyzvo0rluyx2gecpwk9uuiijshvo' );
define( 'SECURE_AUTH_SALT', 'z4pjybugjxeke3wxpg6qb73zvxnsxcj3lhvm7z5ojvfqosi2xl905npsvttwxnm7' );
define( 'LOGGED_IN_SALT',   '9vbpkllwydcqfskpoeswowjnzfapfuqrouku8zjele04grteird04olg4ogttvhw' );
define( 'NONCE_SALT',       'je5ocbojj7kjkjjkqsn1qpp8ydrhdmaoazrf0nkkdv5r2aghuzvwwi9ih9pezoos' );
/**#@-*/
/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp7p_';
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
/* Add any custom values between this line and the "stop editing" line. */
/* That's all, stop editing! Happy publishing. */
define('WP_MEMORY_LIMIT', '2048M');
define('WP_MAX_MEMORY_LIMIT', '2048M');
@ini_set('upload_max_filesize', '2048M');
@ini_set('post_max_size', '2048M');
@ini_set('max_execution_time', '300');
@ini_set('max_input_time', '300');
@ini_set('max_input_vars', '5000');
set_time_limit(300);
/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';