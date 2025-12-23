<?php
/**
 * Plugin Name: Address Autocomplete Premium
 * Plugin URI: https://wpsunshine.com/plugins/address-autocomplete
 * Description: Add address autocomplete to any form including many popular e-commerce, form, LMS or any plugins.
 * Author:      WP Sunshine
 * Author URI:  https://www.wpsunshine.com
 * Version:     1.7.1.1
 * Text Domain: address-autocomplete-anything
 */

define( 'WPS_AA_PREMIUM_STORE_URL', 'https://wpsunshine.com' );
define( 'WPS_AA_PREMIUM_PRODUCT_NAME', 'Address Autocomplete' );
define( 'WPS_AA_PREMIUM_PRODUCT_ID', 531 );
define( 'WPS_AA_PREMIUM_VERSION', '1.7.1.1' );
define( 'WPS_AA_PREMIUM_PLUGIN_FILE', __FILE__ );
define( 'WPS_AA_PREMIUM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS_AA_PREMIUM_PATH', dirname( __FILE__ ) );

// Include the core plugin
if ( ! class_exists( 'WPSunshine_Address_Autocomplete', false ) ) {
	include_once WPS_AA_PREMIUM_PATH . '/core/address-autocomplete.php';
}

require_once WPS_AA_PREMIUM_PATH . '/includes/class-aa-premium.php';

/**
 * Returns the main instance of WPSunshine_Address_Autocomplete_Premium.
 *
 * @return WPSunshine_Address_Autocomplete_Premium
 */
function WPS_AA_Premium() {
	return WPSunshine_Address_Autocomplete_Premium::instance();
}

/**
 * Initialize the plugin on plugins_loaded to ensure all dependencies are available.
 */
function wps_aa_premium_load_me() {
	$GLOBALS['wps_aa_premium'] = WPS_AA_Premium();
}

// Change priority to run after core plugin
add_action( 'plugins_loaded', 'wps_aa_premium_load_me', 20 );

function wps_aa_is_premium() {
	$license_data = get_option( 'wps_aa_license_data' );
	if ( ! empty( $license_data ) && $license_data->license == 'valid' ) {
		return true;
	}
	return false;
}
