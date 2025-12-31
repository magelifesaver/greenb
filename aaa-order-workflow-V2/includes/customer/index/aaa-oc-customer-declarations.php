<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/index/aaa-oc-customer-declarations.php
 * Purpose: WFCP declarations for Customer (tables + order_index columns).
 * Version: 1.0.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Declare Customer tables and claim order_index extension */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['customer'] = array_unique( array_merge( $map['customer'] ?? [], [
		$wpdb->prefix . 'aaa_oc_customer',
		$wpdb->prefix . 'aaa_oc_customer_order',
		$wpdb->prefix . 'aaa_oc_order_index', // claim OI ownership for customer columns
	] ) );
	return $map;
});

/** Declare Customer columns on order_index */
add_filter( 'aaa_oc_expected_columns', function( $cols ) {
	global $wpdb;
	$cols = is_array( $cols ) ? $cols : [];
	$oi   = $wpdb->prefix . 'aaa_oc_order_index';

	$declared = [];
	if ( class_exists( 'AAA_OC_Customer_Table_Extender' ) && method_exists( 'AAA_OC_Customer_Table_Extender', 'declared_columns' ) ) {
		$declared = (array) AAA_OC_Customer_Table_Extender::declared_columns();
	} else {
		$declared = [
			'customer_banned','customer_ban_length','customer_warnings_text','customer_special_needs_text',
			'lkd_upload_med','lkd_upload_selfie','lkd_upload_id',
			'lkd_birthday','lkd_dl_exp','lkd_dln',
		];
	}

	if ( $declared ) {
		$cols[ $oi ] = array_values( array_unique( array_merge( $cols[ $oi ] ?? [], array_map( 'strval', $declared ) ) ) );
	}
	return $cols;
});
add_filter( 'aaa_oc_expected_columns_by_module', function( $map ) {
    global $wpdb;
    $oi  = $wpdb->prefix . 'aaa_oc_order_index';
    $map = is_array($map) ? $map : [];

    $cols = ( class_exists('AAA_OC_Customer_Table_Extender') && method_exists('AAA_OC_Customer_Table_Extender','declared_columns') )
        ? (array) AAA_OC_Customer_Table_Extender::declared_columns()
        : ['customer_banned','customer_ban_length','customer_warnings_text','customer_special_needs_text','lkd_upload_med','lkd_upload_selfie','lkd_upload_id','lkd_birthday','lkd_dl_exp','lkd_dln'];

    $map['customer'][$oi] = array_values(array_unique(array_map('strval',$cols)));
    return $map;
});
