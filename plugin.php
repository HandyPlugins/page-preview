<?php
/**
 * Plugin Name:       Page Preview
 * Plugin URI:        https://handyplugins.co/
 * Description:       Adds screenshots to WordPress post listings, allowing you to quickly visualize and manage your pages directly from the admin panel.
 * Version:           1.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            HandyPlugins
 * Author URI:        https://handyplugins.co/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       page-preview
 * Domain Path:       /languages
 *
 * @package           PagePreview
 */

namespace PagePreview;

// Useful global constants.
define( 'PAGE_PREVIEW_VERSION', '1.0' );
define( 'PAGE_PREVIEW_DB_VERSION', '1.0' );
define( 'PAGE_PREVIEW_PLUGIN_FILE', __FILE__ );
define( 'PAGE_PREVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'PAGE_PREVIEW_PATH', plugin_dir_path( __FILE__ ) );
define( 'PAGE_PREVIEW_INC', PAGE_PREVIEW_PATH . 'includes/' );


// Require Composer autoloader if it exists.
if ( file_exists( PAGE_PREVIEW_PATH . '/vendor/autoload.php' ) ) {
	require_once PAGE_PREVIEW_PATH . 'vendor/autoload.php';
}

// load deps
require_once PAGE_PREVIEW_INC . 'package/deliciousbrains/wp-background-processing/classes/wp-async-request.php';
require_once PAGE_PREVIEW_INC . 'package/deliciousbrains/wp-background-processing/classes/wp-background-process.php';

/**
 * PSR-4-ish autoload
 *
 * @since 1.0
 */
spl_autoload_register(
	function ( $class ) {
		// project-specific namespace prefix.
		$prefix = 'PagePreview\\';

		// base directory for the namespace prefix.
		$base_dir = __DIR__ . '/includes/classes/';

		// does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );

		$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		// if the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);


// Include files.
require_once PAGE_PREVIEW_INC . 'constants.php';
require_once PAGE_PREVIEW_INC . 'utils.php';
require_once PAGE_PREVIEW_INC . 'settings.php';


$network_activated = Utils\is_network_wide( PAGE_PREVIEW_PLUGIN_FILE );
if ( ! defined( 'PAGE_PREVIEW_IS_NETWORK' ) ) {
	define( 'PAGE_PREVIEW_IS_NETWORK', $network_activated );
}

/**
 * WP CLI Commands
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	\WP_CLI::add_command( 'page-preview', '\PagePreview\Command' );
}


/**
 * Setup routine
 *
 * @return void
 * @since 1.0 bootstrapping with plugins_loaded hook
 */
function setup() {
	// Bootstrap.
	Settings\setup();
	Install::setup();
	PagePreviewer::factory();
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\setup' );
