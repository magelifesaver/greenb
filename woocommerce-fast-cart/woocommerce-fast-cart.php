<?php
/**
 * The main plugin file for WooCommerce Fast Cart.
 *
 * This file is included during the WordPress bootstrap process if the plugin is active.
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:      WooCommerce Fast Cart
 * Plugin URI:       https://barn2.com/wordpress-plugins/woocommerce-fast-cart/
 * Update URI:       https://barn2.com/wordpress-plugins/woocommerce-fast-cart/
 * Description:      Same page order popup for the WooCommerce cart and checkout.
 * Requires Plugins: woocommerce
 * Version:          1.3.6
 * Author:           Barn2 Plugins
 * Author URI:       https://barn2.com
 * Text Domain:      wc-fast-cart
 * Domain Path:      /languages
 *
 * Requires at least:    6.1
 * Tested up to:         6.8
 * Requires PHP:         7.4
 * WC requires at least: 7.2
 * WC tested up to:      10.0.3
 *
 * Copyright:   Barn2 Media Ltd
 * License:     GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Barn2\Plugin\WC_Fast_Cart;

const PLUGIN_FILE    = __FILE__;
const PLUGIN_VERSION = '1.3.6';

// Include autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin The plugin instance.
 */
function wfc() {
	return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
}

wfc()->register();

