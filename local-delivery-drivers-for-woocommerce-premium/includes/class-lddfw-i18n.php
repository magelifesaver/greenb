<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link  http://www.powerfulwp.com
 * @since 1.0.0
 *
 * @package    LDDFW
 * @subpackage LDDFW/includes
 */

if ( ! class_exists( 'LDDFW_I18n' ) ) {
	/**
	 * Define the internationalization functionality.
	 *
	 * Loads and defines the internationalization files for this plugin
	 * so that it is ready for translation.
	 *
	 * @since      1.0.0
	 * @package    LDDFW
	 * @subpackage LDDFW/includes
	 * @author     powerfulwp <apowerfulwp@gmail.com>
	 */
	class LDDFW_I18n {



		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since 1.0.0
		 */
		public function load_plugin_textdomain() {

			load_plugin_textdomain(
				'lddfw',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);

		}



	}
}
