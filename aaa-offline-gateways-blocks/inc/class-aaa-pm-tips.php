<?php
/**
 * Tip handling (cart/order fees; per payment method)
 * Path: /wp-content/plugins/aaa-offline-gateways-blocks/inc/class-aaa-pm-tips.php
 * File Version: 1.4.4-ensure-meta
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function aaa_pm_gateway_ids() {
	return array( 'pay_with_zelle','pay_with_venmo','pay_with_applepay','pay_with_creditcard','pay_with_cashapp','pay_with_cod' );
}

/** Utility: clear ALL tip session keys */
function aaa_pm_clear_tip_sessions_all() {
	if ( function_exists( 'WC' ) && WC()->session ) {
		WC()->session->__unset( 'aaa_pm_tip' );
		WC()->session->__unset( 'aaa_pm_tip_map' );
		WC()->session->__unset( 'aaa_pm_tip_last' );
		WC()->session->__unset( 'aaa_ogb_tip' ); // StoreAPI state
	}
}

/** Add fee during cart calc (classic path) */
function aaa_pm_cart_add_tip_fee( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
	if ( ! WC()->session ) return;

	// Capture from classic checkout request
	if ( isset( $_POST['post_data'] ) ) {
		parse_str( wp_unslash( $_POST['post_data'] ), $pd );
		if ( isset( $pd['aaa_tip_amount'] ) ) {
			$raw  = floatval( $pd['aaa_tip_amount'] );
			$tipv = max( 0, round( $raw * 2 ) / 2 ); // $0.50 increments
			WC()->session->set( 'aaa_pm_tip', $tipv );
			$map    = (array) WC()->session->get( 'aaa_pm_tip_map', array() );
			$chosen = (string) WC()->session->get( 'chosen_payment_method', '' );
			if ( $chosen ) { $map[ $chosen ] = $tipv; }
			WC()->session->set( 'aaa_pm_tip_map', $map );
			WC()->session->set( 'aaa_pm_tip_last', $chosen );
		}
	}

	$map    = (array) WC()->session->get( 'aaa_pm_tip_map', array() );
	$chosen = (string) WC()->session->get( 'chosen_payment_method', '' );
	$force  = (bool) WC()->session->get( 'aaa_pm_tip_force', false );
	$last   = (string) WC()->session->get( 'aaa_pm_tip_last', '' );

	$pm_for_fee = $force ? $last : $chosen;
	if ( $force ) { WC()->session->set( 'aaa_pm_tip_force', false ); }

	$tip = 0.0;
	if ( $pm_for_fee && isset( $map[ $pm_for_fee ] ) ) {
		$settings    = get_option( 'woocommerce_' . $pm_for_fee . '_settings', array() );
		$tip_enabled = isset( $settings['enable_tipping'] ) && $settings['enable_tipping'] === 'yes';
		if ( $tip_enabled ) {
			$tip = max( 0, round( floatval( $map[ $pm_for_fee ] ) * 2 ) / 2 );
		} else {
			WC()->session->set( 'aaa_pm_tip', 0 );
			$map[ $pm_for_fee ] = 0;
			WC()->session->set( 'aaa_pm_tip_map', $map );
		}
	}

	if ( $tip > 0 ) {
		$cart->add_fee( __( 'Tip', 'aaa-offline-gateways-blocks' ), $tip, false );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'aaa_pm_cart_add_tip_fee', 20 );

/** Store API payload parsing (Blocks) */
function aaa_pm_extract_tip_from_request( $request ) {
	$out = array( 'pm'=>'', 'tip'=>0.0 );
	if ( $request && method_exists( $request, 'get_json_params' ) ) {
		$d = (array) $request->get_json_params();
		$out['pm'] = $d['payment_method'] ?? '';
		$p = is_array( $d['payment_data'] ?? null ) ? ( $d['payment_data'][ $out['pm'] ] ?? array() ) : array();
		if ( isset( $p['tip_amount'] ) ) {
			$out['tip'] = floatval( $p['tip_amount'] );
		}
	}
	$out['tip'] = max( 0, round( $out['tip'] * 2 ) / 2 ); // normalize to $0.50
	return $out;
}

function aaa_pm_order_remove_tip_fees( $order ) {
	foreach ( $order->get_items( 'fee' ) as $item_id => $fee ) {
		$name = strtolower( (string) $fee->get_name() );
		if ( $name === 'tip' || strpos( $name, 'tip' ) !== false ) { $order->remove_item( $item_id ); }
	}
}

function aaa_pm_add_tip_fee_to_order( $order, $tip, $label='Tip' ) {
	$tip = floatval( $tip ); if ( $tip <= 0 ) return;
	aaa_pm_order_remove_tip_fees( $order );
	$fee = new WC_Order_Item_Fee();
	$fee->set_name( $label ); $fee->set_amount( $tip ); $fee->set_total( $tip ); $fee->set_tax_status( 'none' );
	$order->add_item( $fee ); $order->calculate_totals( true ); $order->save();
}

/** Blocks: capture & apply tip (kept as-is) */
function aaa_pm_blocks_capture_and_apply_tip( $order, $request=null ) {
	$p = aaa_pm_extract_tip_from_request( $request );
	if ( ! in_array( $p['pm'], aaa_pm_gateway_ids(), true ) ) return;

	$settings    = get_option( 'woocommerce_' . $p['pm'] . '_settings', array() );
	$tip_enabled = isset( $settings['enable_tipping'] ) && $settings['enable_tipping'] === 'yes';
	if ( ! $tip_enabled ) { return; }

	$map = (array) WC()->session->get( 'aaa_pm_tip_map', array() );
	if ( $p['pm'] ) { $map[ $p['pm'] ] = max( 0, $p['tip'] ); WC()->session->set( 'aaa_pm_tip_map', $map ); }
	if ( $p['tip'] > 0 ) {
		update_post_meta( $order->get_id(), '_wpslash_tip', $p['tip'] );
		aaa_pm_add_tip_fee_to_order( $order, $p['tip'] );
	}
	aaa_pm_clear_tip_sessions_all();
}
add_action( 'woocommerce_store_api_checkout_update_order_meta', 'aaa_pm_blocks_capture_and_apply_tip', 15, 2 );

/** Store API processed: keep original behaviour */
add_action( 'woocommerce_store_api_checkout_order_processed', function( $order ) {
	$tip = floatval( get_post_meta( $order->get_id(), '_wpslash_tip', true ) );
	if ( $tip > 0 ) { aaa_pm_add_tip_fee_to_order( $order, $tip ); }
	aaa_pm_clear_tip_sessions_all();
	wc_clear_notices();
}, 10, 1 );

/** Classic processed: keep original behaviour */
add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
	wc_clear_notices();
	aaa_pm_clear_tip_sessions_all();
}, 99, 1 );

/* ---------------------------------------------------------------------------
 * SIMPLE GUARANTEE: always write _wpslash_tip from the order's Tip fee
 * (Works for both Classic and Blocks. Minimal, non-invasive.)
 * -------------------------------------------------------------------------*/
if ( ! function_exists( 'aaa_pm_ensure_tip_meta_from_order' ) ) {
	function aaa_pm_ensure_tip_meta_from_order( $order_or_id ) {
		$order = is_numeric( $order_or_id ) ? wc_get_order( $order_or_id ) : $order_or_id;
		if ( ! $order instanceof WC_Order ) { return; }

		$existing = get_post_meta( $order->get_id(), '_wpslash_tip', true );
		if ( is_numeric( $existing ) && floatval( $existing ) > 0 ) { return; }

		$tip_total = 0.0;
		foreach ( $order->get_items( 'fee' ) as $fee ) {
			$name = strtolower( (string) $fee->get_name() );
			if ( $name === 'tip' || strpos( $name, 'tip' ) !== false ) {
				$tip_total += floatval( $fee->get_total() );
			}
		}
		$tip_total = max( 0, round( $tip_total * 2 ) / 2 );
		if ( $tip_total > 0 ) {
			update_post_meta( $order->get_id(), '_wpslash_tip', $tip_total );
		}
	}
}

/** Run the guarantee on both flows */
add_action( 'woocommerce_checkout_order_processed', function( $order_id ) {
	aaa_pm_ensure_tip_meta_from_order( $order_id );
}, 12 );

add_action( 'woocommerce_store_api_checkout_order_processed', function( $order ) {
	aaa_pm_ensure_tip_meta_from_order( $order );
}, 12 );
