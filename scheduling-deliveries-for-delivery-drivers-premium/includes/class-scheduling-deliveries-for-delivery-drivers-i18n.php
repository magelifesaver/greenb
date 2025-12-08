<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://https://powerfulwp.com
 * @since      1.0.0
 *
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Scheduling_Deliveries_For_Delivery_Drivers
 * @subpackage Scheduling_Deliveries_For_Delivery_Drivers/includes
 * @author     powerfulwp <apowerfulwp@gmail.com>
 */
class Scheduling_Deliveries_For_Delivery_Drivers_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'scheduling-deliveries-for-delivery-drivers',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
