<?php

/**
 * Plugin Name: Minimum Purchase Amount For WooCommerce
 * Description: Want to increase your WooCommerce average order value?  This plugin allows you to set minimum order value for your entire store, specific user roles, and for the free shipping. Start optimizing your sales today!
 * Short Description: This woocommecre extension will allow you to specify the minimum purchase value for WooCommerce.
 * Version: 2.3.25
 * Author: CT Talks
 * Author URI: https://cttalks.com/
 * Plugin URI: https://wordpress.org/plugins/minimum-purchase-amount-for-woo-cart/
 * Developer: Team CT Talks
 * Text Domain: ct-minimum-purchase-amount-for-woo-cart
 * Domain Path: /languages
 * 
 * Tested up to: 6.9
 * WC requires at least: 3.5
 * WC tested up to: 10.4.3
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Defining the plugin constants
 * * CT_MPAC_DIR_PATH - Directory path for the plugin.
 * * CT_MPAC_DIR_URL  - Directory URL of the plugin.
 * * CT_MPAC_VERSION  - Current plugins version.
 */
if (!defined('CT_MPAC_DIR_PATH')) {
	define('CT_MPAC_DIR_PATH', plugin_dir_path(__FILE__));
}

if (!defined('CT_MPAC_DIR_URL')) {
	define('CT_MPAC_DIR_URL', plugin_dir_url(__FILE__));
}

if (!defined('CT_MPAC_VERSION')) {
	define('CT_MPAC_VERSION', '2.3.25');
}

add_action('plugins_loaded', 'ct_mpac_admin_settings_menu');
add_action('init', 'ct_mpac_load_textdomain');
register_activation_hook(__FILE__, 'ct_plugin_activation_steps');

// Declare WooCommerce High-performance order storage support.
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__);
		}
	}
);

function ct_mpac_load_textdomain() {
	load_plugin_textdomain('ct-minimum-purchase-amount-for-woo-cart', false, plugin_basename(dirname(__FILE__)) . '/languages');
}

if (!function_exists('ct_mpac_admin_settings_menu')) {
	function ct_mpac_admin_settings_menu() {
		if (defined('WC_VERSION')) {
			include_once CT_MPAC_DIR_PATH . '/includes/admin/ct-class-settings-page.php';
			include_once CT_MPAC_DIR_PATH . 'includes/public/ct-class-min-cart-amount-application.php';
			if (is_admin()) {
				new CtMPAC_Settings_Page();
			} else {
				new CtMPAC_Application();
			}
		}
	}
}



function ct_plugin_activation_steps() {
	// Check if it's the first activation
	$activatedVersion = get_option('ct_mpac_active_version', false);
	if (!$activatedVersion) {
		// Set the option to false to indicate that the plugin has been activated before
		update_option('ct_mpac_active_version', CT_MPAC_VERSION);
		update_option('ct_mpac_show_welcome_modal', true);
	}
}
