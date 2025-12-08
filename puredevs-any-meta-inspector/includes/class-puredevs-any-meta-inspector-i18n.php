<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Puredevs_Any_Meta_Inspector
 * @subpackage Puredevs_Any_Meta_Inspector/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Puredevs_Any_Meta_Inspector
 * @subpackage Puredevs_Any_Meta_Inspector/includes
 * @author     puredevs <#>
 */
class Puredevs_Any_Meta_Inspector_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function pdami_load_plugin_textdomain() {

		load_plugin_textdomain(
			'puredevs-any-meta-inspector',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
