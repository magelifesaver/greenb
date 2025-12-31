<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/index/aaa-oc-indexmanager-declarations.php
 * Purpose: WFCP declarations for IndexManager module.
 * Notes: Adjust table names if your helper returns different ones.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$prefix = $wpdb->prefix;
	$map = is_array( $map ) ? $map : [];
	$map['indexmanager'] = array_unique( array_merge( $map['indexmanager'] ?? [], [
		$prefix . 'aaa_oc_im_users',
		$prefix . 'aaa_oc_im_products',
		$prefix . 'aaa_oc_im_orders',
	] ) );
	return $map;
});
