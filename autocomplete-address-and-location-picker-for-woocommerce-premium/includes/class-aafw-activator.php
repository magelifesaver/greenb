<?php

/**
 * Fired during plugin activation
 *
 * @link       https://powerfulwp.com/
 * @since      1.0.0
 *
 * @package    Aafw
 * @subpackage Aafw/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Aafw
 * @subpackage Aafw/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Aafw_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( aafw_fs()->is__premium_only() ) {
			if ( aafw_fs()->can_use_premium_code() ) {
				if ( is_plugin_active( 'autocomplete-address-and-location-picker-for-woocommerce/autocomplete-address-and-location-picker-for-woocommerce.php' ) ) {
					add_action( 'update_option_active_plugins', 'aafw_deactivate_lite_version__premium_only' );
				}
			}
		}

		// Set default settings options.
		add_option( 'aafw_center_map_latitude', '40.730610' );
		add_option( 'aafw_center_map_longitude', '-73.935242' );
		add_option( 'aafw_map_zoom', '11' );
		add_option( 'aafw_activation_date', date_i18n( 'Y-m-d H:i:s' ) );

	}

}
