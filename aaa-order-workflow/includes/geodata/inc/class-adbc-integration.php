<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/geodata/inc/class-adbc-integration.php
 * Purpose: Load ADBC scripts on WooCommerce Blocks checkout (official integration).
 * Version: 1.0.12
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class ADBC_Integration implements IntegrationInterface {

    const DEBUG_THIS_FILE = false;

    public function get_name() {
        return 'aaa-delivery-blocks-coords';
    }

    public function initialize() {
        $base = trailingslashit( ADBC_PLUGIN_URL ) . 'assets/js/';
        $ver  = '1.0.12'; // bump to bust caches

        // Core → helpers → entry (dependency chain)
        wp_register_script( 'adbc-core',     $base . 'adbc-core.js',  [], $ver, true );
        wp_register_script( 'adbc-apply',    $base . 'adbc-apply.js', [ 'adbc-core' ], $ver, true );
        wp_register_script( 'adbc-checkout', $base . 'adbc-checkout.js', [ 'wc-blocks-checkout', 'adbc-core', 'adbc-apply' ], $ver, true );

        // Prefer AAA_OC_Options (custom table); fallback to legacy option if not available
        if ( class_exists( 'AAA_OC_Options' ) ) {
            $opts = AAA_OC_Options::get( 'delivery_adbc_options', 'adbc', [] );
        } else {
            $opts = get_option( 'delivery_global', [] );
        }

        $api_browser = $opts['google_browser_api_key'] ?? ( defined( 'ADBC_GOOGLE_BROWSER_API_KEY' ) ? ADBC_GOOGLE_BROWSER_API_KEY : '' );

        wp_localize_script( 'adbc-checkout', 'adbcSettings', [
            'apiKey'  => $api_browser,
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'adbc_ajax' ),
            'fields'  => [
                'lat'  => defined('ADBC_FIELD_LAT')  ? ADBC_FIELD_LAT  : 'aaa-delivery-blocks/latitude',
                'lng'  => defined('ADBC_FIELD_LNG')  ? ADBC_FIELD_LNG  : 'aaa-delivery-blocks/longitude',
                'flag' => defined('ADBC_FIELD_FLAG') ? ADBC_FIELD_FLAG : 'aaa-delivery-blocks/coords-verified',
            ],
            'debug'   => (bool) ( defined('ADBC_DEBUG') ? ADBC_DEBUG : true ),
        ] );

        if ( self::DEBUG_THIS_FILE && defined('WP_DEBUG') && WP_DEBUG ) {
            error_log('[ADBC_Integration] initialized (Blocks)');
        }
    }

    public function get_script_handles() {
        // Return only the entry handle; dependencies will enqueue automatically.
        return [ 'adbc-checkout' ];
    }

    public function get_editor_script_handles() { return []; }
    public function get_script_data() { return []; }
}
