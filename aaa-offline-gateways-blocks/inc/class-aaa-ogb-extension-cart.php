<?php
// Registers a Store API "cart/extensions" update for tipping.
// Path: inc/store/class-aaa-ogb-extension-cart.php
// File Version: 1.4.2
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'aaa_ogb_register_extension_cart_update' ) ) {

	add_action( 'woocommerce_blocks_loaded', 'aaa_ogb_register_extension_cart_update' );
	function aaa_ogb_register_extension_cart_update() {
		if ( ! function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
			return;
		}
		woocommerce_store_api_register_update_callback( array(
			'namespace' => 'aaa-ogb/tip',
			'callback'  => 'aaa_ogb_handle_tip_update',
		) );
	}

	/**
	 * Handle tip update coming from Blocks extensionCartUpdate().
	 *
	 * @param array $data { pm: string gateway_id, tip_cents: int }
	 */
	function aaa_ogb_handle_tip_update( $data ) {
		if ( ! did_action( 'woocommerce_init' ) ) {
			wc_load_cart();
		}
		$pm    = isset( $data['pm'] ) ? sanitize_text_field( (string) $data['pm'] ) : '';
		$cents = isset( $data['tip_cents'] ) ? absint( $data['tip_cents'] ) : 0;

		// Enforce $0.50 increments (50 cents) at API boundary.
		$cents = (int) max( 0, round( $cents / 50 ) * 50 );

		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( 'aaa_ogb_tip', array(
			'pm'    => $pm,
			'cents' => $cents,
		) );

		if ( WC()->cart ) {
			WC()->cart->calculate_totals();
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wc_get_logger()->info(
				sprintf( '[AAA-OGB] tip update via StoreAPI pm=%s cents=%d', $pm, $cents ),
				array( 'source' => 'aaa-ogb' )
			);
		}
	}
}
