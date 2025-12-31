<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/index/aaa-oc-delivery-declarations.php
 * Purpose: WFCP declarations for Delivery (tables + order_index columns).
 * Version: 1.1.0
 *
 * Changes in 1.1.0:
 * - Declare new Delivery columns on aaa_oc_order_index:
 *   delivery_latitude, delivery_longitude, delivery_address_line,
 *   travel_time_seconds, travel_distance_meters,
 *   was_rescheduled, reschedule_count, last_rescheduled_at, original_delivery_ts,
 *   reschedule_reason, rescheduled_by, is_asap_zone, asap_zone_id,
 *   asap_eta_minutes, asap_eta_computed_at, asap_fee
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Declare Delivery tables and claim order_index extension */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['delivery'] = array_unique( array_merge( $map['delivery'] ?? [], [
		$wpdb->prefix . 'aaa_oc_delivery',
		$wpdb->prefix . 'aaa_oc_order_index', // claim OI ownership for delivery columns
	] ) );
	return $map;
} );

/** Return the full set of Delivery-declared columns (kept in one place for both filters) */
function aaa_oc_delivery_declared_columns() : array {
	return [
		// Existing delivery slice
		'delivery_date_ts','delivery_date_formatted','delivery_date_locale',
		'delivery_time','delivery_time_range','driver_id',
		'is_scheduled','is_same_day','is_asap',
		// New: coords + address + travel
		'delivery_latitude','delivery_longitude','delivery_address_line',
		'travel_time_seconds','travel_distance_meters',
		// New: reschedule + ASAP metadata
		'was_rescheduled','reschedule_count','last_rescheduled_at',
		'original_delivery_ts','reschedule_reason','rescheduled_by',
		'is_asap_zone','asap_zone_id','asap_eta_minutes','asap_eta_computed_at','asap_fee',
	];
}

/** Declare Delivery columns on order_index */
add_filter( 'aaa_oc_expected_columns', function( $cols ) {
	global $wpdb;
	$cols = is_array( $cols ) ? $cols : [];
	$oi   = $wpdb->prefix . 'aaa_oc_order_index';

	$declared = aaa_oc_delivery_declared_columns();
	if ( $declared ) {
		$cols[ $oi ] = array_values( array_unique( array_merge( $cols[ $oi ] ?? [], array_map( 'strval', $declared ) ) ) );
	}
	return $cols;
} );

/** Attribute the same columns to this module for WFCP “by module” view */
add_filter( 'aaa_oc_expected_columns_by_module', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$oi  = $wpdb->prefix . 'aaa_oc_order_index';

	$map['delivery'][ $oi ] = array_values( array_unique( array_map( 'strval', aaa_oc_delivery_declared_columns() ) ) );
	return $map;
} );
