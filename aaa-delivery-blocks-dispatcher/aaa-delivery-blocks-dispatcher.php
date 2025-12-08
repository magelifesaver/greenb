<?php
/**
 * File: /wp-content/plugins/aaa-delivery-blocks-dispatcher/aaa-delivery-blocks-dispatcher.php
 * Plugin Name: A Delivery Blocks Dispatcher (live)dev
 * Description: Dispatcher map and unassigned/driver tree for WooCommerce processing orders within a radius.
 * Version: 0.1.0
 * Author: Workflow Delivery
 * Text Domain: adbd
 * Purpose: Plugin bootstrap. Registers constants, loads classes, hooks initialization,
 *          and adds a direct “Settings” link from the Plugins list.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
// TEMP: Debug mode to remove restrictions and show everything on the page
if ( ! defined( 'ADBD_DEBUG_ALL' ) ) {
	define( 'ADBD_DEBUG_ALL', true ); // set to false (or remove) later
}

define( 'ADBD_VERSION', '0.1.0' );
define( 'ADBD_PLUGIN_FILE', __FILE__ );
define( 'ADBD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADBD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ADBD_PLUGIN_DIR . 'includes/class-adbd-admin.php';
require_once ADBD_PLUGIN_DIR . 'includes/class-adbd-rest.php';
// Note: class-adbd-orders.php intentionally not loaded in M1 (no geocoding needed)

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>AAA Delivery Blocks Dispatcher</strong> requires WooCommerce to be active.</p></div>';
		} );
		return;
	}

	ADBD_Admin::init();
	ADBD_REST::init();
} );

// “Settings” link goes to the WooCommerce submenu page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
	$url = admin_url( 'admin.php?page=adbd-dispatcher' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">Settings</a>' );
	return $links;
} );
