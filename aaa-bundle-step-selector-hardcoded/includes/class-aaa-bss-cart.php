<?php
/**
 * File: /wp-content/plugins/aaa-bundle-step-selector/includes/class-aaa-bss-cart.php
 *
 * Makes Step 2 items free by setting their cart price to 0.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AAA_BSS_Cart {

    public function __construct() {
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'set_free_prices' ], 20, 1 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'cart_item_label' ], 20, 2 );
    }

    public function set_free_prices( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) { return; }
        if ( ! $cart || ! is_a( $cart, 'WC_Cart' ) ) { return; }
        if ( ! AAA_BSS_Config::step2_is_free() ) { return; }

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( empty( $cart_item['aaa_bss_free'] ) ) { continue; }
            if ( empty( $cart_item['data'] ) || ! is_a( $cart_item['data'], 'WC_Product' ) ) { continue; }
            $cart_item['data']->set_price( 0 );
        }
    }

    public function cart_item_label( $item_data, $cart_item ) {
        if ( empty( $cart_item['aaa_bss_free'] ) ) { return $item_data; }

        $item_data[] = [
            'key'   => __( 'Promotion', 'aaa-bss' ),
            'value' => __( 'Free item', 'aaa-bss' ),
        ];
        return $item_data;
    }
}
