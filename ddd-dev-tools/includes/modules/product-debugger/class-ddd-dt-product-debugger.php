<?php
// @version 2.1.0
defined( 'ABSPATH' ) || exit;

class DDD_DT_Product_Debugger {
    public static function settings(): array {
        $d = [ 'enabled' => 0, 'debug_enabled' => 0 ];
        $s = DDD_DT_Options::get( 'ddd_product_debugger_settings', [], 'global' );
        return is_array( $s ) ? array_merge( $d, $s ) : $d;
    }

    public static function init() {
        if ( ! is_admin() ) {
            return;
        }
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function is_enabled(): bool {
        return ! empty( self::settings()['enabled'] );
    }

    public static function enqueue_assets( $hook ) {
        if ( 'tools_page_ddd-dev-tools' !== $hook ) {
            return;
        }
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
        if ( 'product_debugger' !== $tab ) {
            return;
        }
        if ( empty( self::settings()['enabled'] ) ) {
            return;
        }
        wp_enqueue_script( 'ddd-dt-product-debugger', DDD_DT_URL . 'assets/js/product-debugger.js', [ 'jquery' ], DDD_DT_VERSION, true );
    }

    public static function debug_product( int $product_id ): array {
        global $wpdb;

        if ( $product_id <= 0 ) {
            return [ 'ok' => false, 'error' => 'Invalid product ID.' ];
        }
        if ( ! function_exists( 'wc_get_product' ) ) {
            return [ 'ok' => false, 'error' => 'WooCommerce not available.' ];
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return [ 'ok' => false, 'error' => 'Product not found.' ];
        }

        $meta = get_post_meta( $product_id );

        $atum_data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}atum_product_data WHERE product_id = %d", $product_id ), ARRAY_A );
        $atum_locs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}atum_product_locations WHERE product_id = %d", $product_id ), ARRAY_A );

        return [
            'ok' => true,
            'product' => $product,
            'meta' => $meta,
            'atum_product_data' => $atum_data,
            'atum_product_locations' => $atum_locs,
        ];
    }
}
