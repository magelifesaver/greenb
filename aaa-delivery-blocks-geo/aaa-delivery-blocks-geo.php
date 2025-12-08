<?php
/**
 * Plugin Name:  A Delivery Blocks Geo (live)
 * Description:  GEO travel/ETA data for Woo Blocks (Step 3).
 * Version:      0.3.0
 * Author:       Webmaster Workflow
 * Text Domain:  aaa-delivery-blocks-geo
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** ─────────────────────────  Constants (scoped to GEO)  ───────────────────────── */
if ( ! defined( 'ADBG_DEBUG' ) )                     define( 'ADBG_DEBUG', true );
if ( ! defined( 'ADBG_PLUGIN_DIR' ) )                define( 'ADBG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'ADBG_PLUGIN_URL' ) )                define( 'ADBG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Additional Checkout Field IDs
if ( ! defined( 'ADBG_FIELD_ETA' ) )                 define( 'ADBG_FIELD_ETA',        'aaa-delivery-blocks/eta-seconds' );
if ( ! defined( 'ADBG_FIELD_ETA_RANGE' ) )           define( 'ADBG_FIELD_ETA_RANGE',  'aaa-delivery-blocks/eta-range' );
if ( ! defined( 'ADBG_FIELD_ORIGIN' ) )              define( 'ADBG_FIELD_ORIGIN',     'aaa-delivery-blocks/eta-origin' );
if ( ! defined( 'ADBG_FIELD_DISTANCE' ) )            define( 'ADBG_FIELD_DISTANCE',   'aaa-delivery-blocks/distance-meters' );
if ( ! defined( 'ADBG_FIELD_TRAVEL' ) )              define( 'ADBG_FIELD_TRAVEL',     'aaa-delivery-blocks/travel-seconds' );
if ( ! defined( 'ADBG_FIELD_REFRESHED' ) )           define( 'ADBG_FIELD_REFRESHED',  'aaa-delivery-blocks/travel-refreshed' );

/** ─────────────────────────  Includes  ───────────────────────── */
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-logger.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-geo-settings.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-fields.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-travel.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-ajax.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-login.php';
require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-logger.php';

/** ─────────────────────────  Bootstrap  ───────────────────────── */
add_action( 'plugins_loaded', function () {
    // Register fields
    ADBG_Fields::register();

    // AJAX + Login hook
    ADBG_Ajax::init();
    ADBG_Login_Refresh::init();

    // Blocks Integration (no keys exposed)
    $has_interface = interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' );
    $has_registry  = class_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry' );

    if ( $has_interface && $has_registry ) {
        require_once ADBG_PLUGIN_DIR . 'includes/class-adbg-integration.php';
        add_action( 'woocommerce_blocks_checkout_block_registration', function( $registry ) {
            $registry->register( new ADBG_Integration() );
        } );
    } else {
        add_action( 'wp_enqueue_scripts', function () {
            if ( function_exists( 'is_checkout' ) && is_checkout() ) {
                wp_register_script( 'adbg-checkout', ADBG_PLUGIN_URL . 'assets/js/adbg-checkout.js', [], '0.3.0', true );
                wp_localize_script( 'adbg-checkout', 'adbgGeoSettings', [
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                    'nonce'   => wp_create_nonce( 'adbg_ajax' ),
                    'debug'   => (bool) ADBG_DEBUG,
                ] );
                wp_enqueue_script( 'adbg-checkout' );
            }
        } );
    }
} );

/** Settings link on Plugins list */
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links ) {
    $url = admin_url( 'admin.php?page=delivery-geo' );
    $links[] = '<a href="'.esc_url($url).'">'.esc_html__('Settings','aaa-delivery-blocks-geo').'</a>';
    return $links;
});
