<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/geodata/aaa-adbc-assets-loader-blocks.php
 * Purpose: Legacy/fallback Blocks loader. NO-OP when official ADBC_Integration is present.
 * Version: 1.0.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Local constants (guards)
if ( ! defined( 'ADBC_DEBUG' ) )      define( 'ADBC_DEBUG', true );
if ( ! defined( 'ADBC_FIELD_LAT' ) )  define( 'ADBC_FIELD_LAT',  'aaa-delivery-blocks/latitude' );
if ( ! defined( 'ADBC_FIELD_LNG' ) )  define( 'ADBC_FIELD_LNG',  'aaa-delivery-blocks/longitude' );
if ( ! defined( 'ADBC_FIELD_FLAG' ) ) define( 'ADBC_FIELD_FLAG', 'aaa-delivery-blocks/coords-verified' );

/**
 * If the official Blocks integration exists (inc/class-adbc-integration.php),
 * do nothing here to avoid duplicate registration.
 */
if ( class_exists( 'ADBC_Integration' ) ) {
    return;
}

/**
 * Fallback (very old Blocks): if checkout is a Blocks page and the official
 * integration is not loaded, enqueue the scripts directly on the frontend.
 * NOTE: This does NOT register with IntegrationRegistry to avoid double registration errors.
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return;

    // If has_block() exists, only run when the Blocks checkout is present
    if ( function_exists( 'has_block' ) && ! has_block( 'woocommerce/checkout' ) ) {
        return;
    }

    // Read options from the same store as the settings tab
    if ( class_exists( 'AAA_OC_Options' ) ) {
        $opts = AAA_OC_Options::get( 'delivery_adbc_options', 'adbc', [] );
    } else {
        // Legacy fallback (kept only for safety on old installs)
        $opts = get_option( 'delivery_global', [] );
    }

    $api_browser = $opts['google_browser_api_key'] ?? '';
    $base        = trailingslashit( ADBC_PLUGIN_URL ) . 'assets/js/';
    $ver         = '1.0.3';

    // Core → helpers → entry
    wp_register_script( 'adbc-core',     $base . 'adbc-core.js',  [], $ver, true );
    wp_register_script( 'adbc-apply',    $base . 'adbc-apply.js', [ 'adbc-core' ], $ver, true );
    wp_register_script( 'adbc-checkout', $base . 'adbc-checkout.js', [ 'adbc-core', 'adbc-apply' ], $ver, true );

    wp_localize_script( 'adbc-checkout', 'adbcSettings', [
        'apiKey'  => $api_browser,
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'adbc_ajax' ),
        'fields'  => [
            'lat'  => ADBC_FIELD_LAT,
            'lng'  => ADBC_FIELD_LNG,
            'flag' => ADBC_FIELD_FLAG,
        ],
        'debug'   => (bool) ADBC_DEBUG,
    ] );

    wp_enqueue_script( 'adbc-checkout' );
}, 20);
