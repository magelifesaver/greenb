<?php
/**
 * AJAX: apply tip (per-payment method)
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/inc/class-aaa-pm-ajax.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function aaa_pm_apply_tip_ajax() {
	check_ajax_referer( 'aaa_pm_tip_nonce', 'nonce' );

	$tip = isset( $_POST['tip'] ) ? floatval( wp_unslash( $_POST['tip'] ) ) : 0;
	$pm  = isset( $_POST['pm'] )  ? sanitize_key( wp_unslash( $_POST['pm'] ) ) : '';

	// Enforce $0.50 increments
	$tip = max( 0, round( $tip * 2 ) / 2 );

	if ( ! WC()->session ) {
		wp_send_json_error( array( 'msg' => 'no-session' ), 400 );
	}

	WC()->session->set( 'aaa_pm_tip', $tip );

	$map = WC()->session->get( 'aaa_pm_tip_map', array() );
	if ( $pm ) {
		$map[ $pm ] = $tip;
		WC()->session->set( 'aaa_pm_tip_map', $map );
		WC()->session->set( 'aaa_pm_tip_last', $pm );
	}

	WC()->session->set( 'aaa_pm_tip_force', true );

	if ( WC()->cart ) {
		WC()->cart->calculate_totals();
	}

	aaa_pm_log( '[TIP][ajax] pm=' . $pm . ' tip=' . $tip );
	wp_send_json_success( array( 'pm' => $pm, 'tip' => $tip ) );
}
add_action( 'wp_ajax_aaa_pm_apply_tip', 'aaa_pm_apply_tip_ajax' );
add_action( 'wp_ajax_nopriv_aaa_pm_apply_tip', 'aaa_pm_apply_tip_ajax' );
