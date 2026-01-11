<?php
/**
 * Plugin Name: CartLink Generator for WooCommerce
 * Description: Create dynamic cart links with pre-filled products, quantities, and custom prices, perfect for businesses interacting via chat to reduce purchase steps and drive faster conversions.
 * Version: 1.0.3
 * Author: Guaven Labs
 * Text Domain: cartlink-generator
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 9.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define constants
define( 'GUAVEN_CARTLINK_GENERATOR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GUAVEN_CARTLINK_GENERATOR_URL', plugin_dir_url( __FILE__ ) );
define( 'GUAVEN_CARTLINK_GENERATOR_VERSION', '1.0.3.0');



if(!defined('GUAVEN_CARTLINK_GENERATOR_DATA_DIR')){
    define( 'GUAVEN_CARTLINK_GENERATOR_DATA_DIR', wp_upload_dir()['basedir'].'/cartlink_autogenerate/' );
}

// Minimum WooCommerce version required
define( 'GUAVEN_CARTLINK_GENERATOR_MIN_WC_VERSION', '7.0' );
define( 'GUAVEN_CARTLINK_GENERATOR_WC_REQUIRED', 'woocommerce/woocommerce.php' );


add_action('before_woocommerce_init', function(){

    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

    }

});


// Include core files
require_once GUAVEN_CARTLINK_GENERATOR_PATH . 'includes/admin-page.php';
require_once GUAVEN_CARTLINK_GENERATOR_PATH . 'includes/ajax-functions.php';
require_once GUAVEN_CARTLINK_GENERATOR_PATH . 'includes/utilities.php';
require_once GUAVEN_CARTLINK_GENERATOR_PATH . 'includes/hooks.php';
