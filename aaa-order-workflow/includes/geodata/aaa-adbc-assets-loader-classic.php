<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/geodata/aaa-adbc-assets-loader-classic.php
 * Purpose: Load ADBC scripts for classic templates & My Account → Edit Address.
 * Version: 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Local constants
if ( ! defined( 'ADBC_DEBUG' ) )      define( 'ADBC_DEBUG', true );
if ( ! defined( 'ADBC_FIELD_LAT' ) )  define( 'ADBC_FIELD_LAT',  'aaa-delivery-blocks/latitude' );
if ( ! defined( 'ADBC_FIELD_LNG' ) )  define( 'ADBC_FIELD_LNG',  'aaa-delivery-blocks/longitude' );
if ( ! defined( 'ADBC_FIELD_FLAG' ) ) define( 'ADBC_FIELD_FLAG', 'aaa-delivery-blocks/coords-verified' );

/**
 * Enqueue for My Account → Edit Address (classic contexts).
 * (Blocks checkout is handled by the IntegrationInterface class.)
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) return;
    if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'edit-address' ) ) return;

    if ( class_exists( 'AAA_OC_Options' ) ) {
        $opts = AAA_OC_Options::get( 'delivery_adbc_options', 'adbc', [] );
    } else {
        $opts = get_option( 'delivery_global', [] );
    }

    $api_browser = $opts['google_browser_api_key'] ?? '';
    $api_geocode = $opts['google_geocode_api_key'] ?? '';

    wp_enqueue_script(
        'adbc-account',
        plugins_url( 'assets/js/adbc-account.js', __FILE__ ),
        [],
        '1.1.0',
        true
    );

    wp_localize_script( 'adbc-account', 'adbcSettings', [
        'apiKey'     => $api_browser,
        'geocodeKey' => $api_geocode,
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'adbc_ajax' ),
        'fields'     => [ 'lat' => ADBC_FIELD_LAT, 'lng' => ADBC_FIELD_LNG, 'flag' => ADBC_FIELD_FLAG ],
        'debug'      => (bool) ADBC_DEBUG,
    ] );
}, 20 );
