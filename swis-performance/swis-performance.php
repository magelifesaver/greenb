<?php
/**
 * Loader for SWIS Performance.
 *
 * @link https://ewww.io/swis/
 * @package SWIS_Performance
 */

/*
Plugin Name: SWIS Performance
Plugin URI: https://ewww.io/swis/
Description: Make your site faster!
Author: Exactly WWW
Version: 2.3.0
Requires at least: 6.4
Requires PHP: 7.4
License: GPLv3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SWIS_Performance' ) && false === strpos( add_query_arg( '', '' ), 'swis_disable' ) ) {

	// The full path of the plugin file (this file).
	if ( ! defined( 'SWIS_PLUGIN_FILE' ) ) {
		define( 'SWIS_PLUGIN_FILE', __FILE__ );
	}
	// Plugin version.
	define( 'SWIS_PLUGIN_VERSION', 230 );
	require 'includes/class-swis-performance.php';
	require __DIR__ . '/vendor/autoload.php';

	/**
	 * The main function that returns SWIS_Performance
	 *
	 * The main function responsible for returning the one true SWIS_Performance
	 * instance to functions everywhere.
	 *
	 * @since 0.1
	 * @return object|SWIS_Performance The one true SWIS_Performance instance.
	 */
	function swis() {
		return SWIS_Performance::instance();
	}

	// Fire it up!
	swis();

	register_activation_hook( __FILE__, 'swis_activation' );
	/**
	 * Run any actions needed on activation.
	 */
	function swis_activation() {
		update_option( 'swis_activation', true, false );
	}

	register_deactivation_hook( __FILE__, 'swis_deactivation' );
	/**
	 * Run cleanup when plugin is deactivated.
	 *
	 * @param boolean $network_wide If the plugin is network activated.
	 */
	function swis_deactivation( $network_wide ) {
		remove_all_actions( 'swis_site_cache_cleared' );
		remove_all_actions( 'swis_cache_by_url_cleared' );
		$cache = new SWIS\Cache();
		$cache->on_deactivation( $network_wide );
	}
}
