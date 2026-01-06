<?php
/**
 * Plugin Name: AAA API Lokey Combined (Updated)
 * Description: Custom REST API endpoints for WooCommerce + ATUM product create/update/query.
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AAA_API_LOKEY_VERSION', '2.0.0' );
define( 'AAA_API_LOKEY_FILE', __FILE__ );
define( 'AAA_API_LOKEY_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_API_LOKEY_URL', plugin_dir_url( __FILE__ ) );
define( 'AAA_API_LOKEY_BASENAME', plugin_basename( __FILE__ ) );
define( 'AAA_API_LOKEY_OPTION', 'aaa_api_lokey_settings' );

require_once AAA_API_LOKEY_DIR . 'includes/aaa-api-lokey-loader.php';

register_activation_hook( __FILE__, array( 'AAA_API_Lokey_Loader', 'activate' ) );
add_action( 'plugins_loaded', array( 'AAA_API_Lokey_Loader', 'init' ), 9 );

add_filter(
	'plugin_action_links_' . AAA_API_LOKEY_BASENAME,
	function ( $links ) {
		$url = admin_url( 'options-general.php?page=aaa-api-lokey' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
		return $links;
	}
);
