<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/index/aaa-oc-payconfirm-declarations.php
 * Purpose: WFCP declarations for PayConfirm — inbox table + per-table column declarations + expected indexes.
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Owned tables */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['payconfirm'] = array_unique( array_merge(
		$map['payconfirm'] ?? [],
		[ $wpdb->prefix . 'aaa_oc_payconfirm_inbox' ]
	) );
	return $map;
});

/** Canonical column lists added by this module */
function aaa_oc_payconfirm_order_index_cols(): array {
	return [
		'pc_post_id','pc_matched_order_id','pc_txn','pc_amount','pc_match_status',
		/** NEW: alias snapshot */
		'pc_aliases','pc_alias_snapshot_ts',
	];
}
function aaa_oc_payconfirm_payment_index_cols(): array {
	return ['pc_matched_order_id']; // soft pointer
}

/** A) By-table declaration */
add_filter( 'aaa_oc_expected_columns', function( $tables ) {
	global $wpdb;
	$tables = is_array( $tables ) ? $tables : [];
	$oi = $wpdb->prefix . 'aaa_oc_order_index';
	$pi = $wpdb->prefix . 'aaa_oc_payment_index';

	$tables[$oi] = array_values( array_unique( array_merge( $tables[$oi] ?? [], aaa_oc_payconfirm_order_index_cols() ) ) );
	$tables[$pi] = array_values( array_unique( array_merge( $tables[$pi] ?? [], aaa_oc_payconfirm_payment_index_cols() ) ) );
	return $tables;
});

/** B) By-module + per-table */
add_filter( 'aaa_oc_expected_columns_by_module', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$oi  = $wpdb->prefix . 'aaa_oc_order_index';
	$pi  = $wpdb->prefix . 'aaa_oc_payment_index';

	$map['payconfirm'][$oi] = aaa_oc_payconfirm_order_index_cols();
	$map['payconfirm'][$pi] = aaa_oc_payconfirm_payment_index_cols();
	return $map;
});

/** Expected indexes (names only) */
add_filter( 'aaa_oc_expected_indexes_by_module', function( $map ) {
	$map = is_array( $map ) ? $map : [];
	$map['payconfirm'] = array_unique( array_merge(
		$map['payconfirm'] ?? [],
		[
			'idx_pc_match_status',
			'idx_pc_txn',
			'idx_pc_post_id',
			/** NEW */
			'idx_pc_alias_snapshot_ts',
			// payment_index (soft)
			'idx_pi_pc_matched_order_id',
		]
	));
	return $map;
});

/** User meta used by this module (for audits) */
add_filter( 'aaa_oc_expected_usermeta_keys', function( $keys ) {
	$keys = is_array( $keys ) ? $keys : [];
	$keys[] = 'aaa_oc_pay_accounts';
	return array_values( array_unique( $keys ) );
});
