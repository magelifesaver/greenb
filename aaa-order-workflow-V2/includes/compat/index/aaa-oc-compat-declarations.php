<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/compat/index/aaa-oc-compat-declarations.php
 * Purpose: WFCP declarations for Compat (columns only; claims order_index extension).
 * Version: 1.0.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Register module key + claim that this module extends order_index */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['compat'] = array_unique( array_merge( $map['compat'] ?? [], [
		$wpdb->prefix . 'aaa_oc_order_index', // claim OI ownership for this module's columns
	] ) );
	return $map;
});

/** Declare columns added by the compat extender */
add_filter( 'aaa_oc_expected_columns', function( $cols ) {
	global $wpdb;
	$cols = is_array( $cols ) ? $cols : [];
	$oi   = $wpdb->prefix . 'aaa_oc_order_index';

	$declared = [];
	if ( class_exists( 'AAA_OC_Compat_Table_Extender' ) && method_exists( 'AAA_OC_Compat_Table_Extender', 'declared_columns' ) ) {
		$declared = (array) AAA_OC_Compat_Table_Extender::declared_columns();
	} else {
		$declared = [
			'af_funds_used','af_funds_balance',
			'sc_credit_used','sc_credit_balance',
			'crm_contact_id','crm_reg_source','crm_lists','crm_tags',
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

    $cols = ( class_exists('AAA_OC_Compat_Table_Extender') && method_exists('AAA_OC_Compat_Table_Extender','declared_columns') )
        ? (array) AAA_OC_Compat_Table_Extender::declared_columns()
        : ['af_funds_used','af_funds_balance','sc_credit_used','sc_credit_balance','crm_contact_id','crm_reg_source','crm_lists','crm_tags'];

    $map['compat'][$oi] = array_values(array_unique(array_map('strval',$cols)));
    return $map;
});
