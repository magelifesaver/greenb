<?php
/*
Plugin Name: Premium Addons PRO
Description: Premium Addons PRO Plugin Includes 36+ premium widgets & addons for Elementor Page Builder.
Plugin URI: https://premiumaddons.com
Version: 2.9.55
Author: Leap13
Elementor tested up to: 3.34
Elementor Pro tested up to: 3.34
Author URI: https://leap13.com/
Text Domain: premium-addons-pro
Domain Path: /languages
*/

/**
 * Checking if WordPress is installed
 */
if ( ! function_exists( 'add_action' ) ) {
	die( 'WordPress not Installed' ); // if WordPress not installed kill the page.
}

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No access of directly access.
}

define( 'PREMIUM_PRO_ADDONS_VERSION', '2.9.55' );
define( 'PREMIUM_PRO_ADDONS_STABLE_VERSION', '2.9.54' );
define( 'PREMIUM_PRO_ADDONS_URL', plugins_url( '/', __FILE__ ) );
define( 'PREMIUM_PRO_ADDONS_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'PREMIUM_PRO_ADDONS_FILE', __FILE__ );
define( 'PREMIUM_PRO_ADDONS_BASENAME', plugin_basename( PREMIUM_PRO_ADDONS_FILE ) );
define( 'PAPRO_ITEM_NAME', 'Premium Addons PRO' );
define( 'PAPRO_STORE_URL', 'https://my.leap13.com' );
define( 'PAPRO_ITEM_ID', 361 );

/*
 * Load plugin core file.
 */
require_once PREMIUM_PRO_ADDONS_PATH . 'includes/class-papro-core.php';
