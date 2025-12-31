<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/index/aaa-oc-payment-declarations.php
 * Purpose: WFCP declarations for Payment module.
 * Notes: Declares tables created by Payment + columns it adds to the order_index table.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['payment'] = array_unique( array_merge( $map['payment'] ?? [], [
		$wpdb->prefix . 'aaa_oc_payment_index',
		$wpdb->prefix . 'aaa_oc_payment_log',
		// Also show that Payment extends the board table (optional but helpful in WFCP)
		$wpdb->prefix . 'aaa_oc_order_index',
	] ) );
	return $map;
});

add_filter( 'aaa_oc_expected_columns', function( $cols ) {
	global $wpdb;
	$oi = $wpdb->prefix . 'aaa_oc_order_index';
	$cols = is_array( $cols ) ? $cols : [];
	$declared = [
		'aaa_oc_payment_status',
		'aaa_oc_epayment_total',
		'aaa_oc_payrec_total',
		'aaa_oc_order_balance',
		'aaa_oc_order_total',
		'aaa_oc_tip_total',
		'epayment_tip',
		'total_order_tip',
		'real_payment_method',
		'epayment_detail',
		'envelope_outstanding',
		'cleared',
		'driver_id',
		'envelope_id',
		'route_id',
		'last_payment_at',
	];
	$cols[ $oi ] = array_values( array_unique( array_merge( $cols[ $oi ] ?? [], $declared ) ) );
	return $cols;
});
