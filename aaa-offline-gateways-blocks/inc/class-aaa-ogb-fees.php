<?php
// Adds the Tip (Gateway) fee based on session state set by the Store API callback.
// Path: includes/class-aaa-ogb-fees.php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'aaa_ogb_add_tip_fee' ) ) {
	add_action( 'woocommerce_cart_calculate_fees', 'aaa_ogb_add_tip_fee', 20, 1 );
	function aaa_ogb_add_tip_fee( WC_Cart $cart ) {
		// Frontend only; allow during Store API/AJAX.
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		$tip = WC()->session ? (array) WC()->session->get( 'aaa_ogb_tip', array() ) : array();
		$cents = isset( $tip['cents'] ) ? absint( $tip['cents'] ) : 0;
		if ( $cents <= 0 ) {
			return;
		}

		$pm = isset( $tip['pm'] ) ? sanitize_text_field( (string) $tip['pm'] ) : '';
		$title = aaa_ogb_resolve_gateway_title( $pm );
		$label = $title ? sprintf( __( 'Tip (%s)', 'aaa-ogb' ), $title ) : __( 'Tip', 'aaa-ogb' );

		$amount = $cents / 100;
		$cart->add_fee( $label, $amount, false ); // Tip is not taxable by default.
	}
}

if ( ! function_exists( 'aaa_ogb_resolve_gateway_title' ) ) {
	function aaa_ogb_resolve_gateway_title( $pm ) {
		if ( ! $pm ) { return ''; }
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : array();
		if ( isset( $gateways[ $pm ] ) && method_exists( $gateways[ $pm ], 'get_title' ) ) {
			return trim( wp_strip_all_tags( $gateways[ $pm ]->get_title() ) );
		}
		// Fallback: pretty print the id.
		$pretty = ucwords( trim( str_replace( array( 'pay_with_', '_' ), array( '', ' ' ), $pm ) ) );
		return $pretty ?: $pm;
	}
}
