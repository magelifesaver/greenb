<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/index/aaa-oc-fulfillment-declarations.php
 * Purpose: WFCP declarations for Fulfillment (claims logs table + order_index columns).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tell WFCP which tables this module owns/extends.
 * - Owns:  aaa_oc_fulfillment_logs
 * - Extends: aaa_oc_order_index (adds fulfillment columns)
 */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];

	$map['fulfillment'] = array_unique( array_merge(
		$map['fulfillment'] ?? [],
		[
			$wpdb->prefix . 'aaa_oc_fulfillment_logs',
			$wpdb->prefix . 'aaa_oc_order_index',
		]
	) );

	return $map;
});

/**
 * Publish the fulfillment columns that live on order_index so WFCP can display them.
 * If a future extender exposes declared_columns(), we’ll use it; otherwise fallback to the known set.
 */
add_filter( 'aaa_oc_expected_columns', function( $cols ) {
	global $wpdb;
	$cols = is_array( $cols ) ? $cols : [];
	$oi   = $wpdb->prefix . 'aaa_oc_order_index';

	$declared = [];
	if (
		class_exists( 'AAA_OC_Fulfillment_Table_Extender' )
		&& method_exists( 'AAA_OC_Fulfillment_Table_Extender', 'declared_columns' )
	) {
		$declared = (array) AAA_OC_Fulfillment_Table_Extender::declared_columns();
	} else {
		// Fallback to the columns added in the extender.
		$declared = [
			'fulfillment_status',
			'picked_items',
			'usbs_order_fulfillment_data',
		];
	}

	if ( $declared ) {
		$cols[ $oi ] = array_values( array_unique( array_merge(
			$cols[ $oi ] ?? [],
			array_map( 'strval', $declared )
		) ) );
	}

	return $cols;
});
add_filter( 'aaa_oc_expected_columns_by_module', function( $map ) {
    global $wpdb;
    $oi  = $wpdb->prefix . 'aaa_oc_order_index';
    $map = is_array($map) ? $map : [];

    $cols = ( class_exists('AAA_OC_Fulfillment_Table_Extender') && method_exists('AAA_OC_Fulfillment_Table_Extender','declared_columns') )
        ? (array) AAA_OC_Fulfillment_Table_Extender::declared_columns()
        : ['fulfillment_status','picked_items','usbs_order_fulfillment_data'];

    $map['fulfillment'][$oi] = array_values(array_unique(array_map('strval',$cols)));
    return $map;
});
