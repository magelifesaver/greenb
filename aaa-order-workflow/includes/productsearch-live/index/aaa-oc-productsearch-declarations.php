<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/index/aaa-oc-productsearch-declarations.php
 * Purpose: WFCP declarations for ProductSearch module.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['productsearch'] = array_unique( array_merge( $map['productsearch'] ?? [], [
		$wpdb->prefix . 'aaa_oc_productsearch_index',
		$wpdb->prefix . 'aaa_oc_productsearch_synonyms',
	] ) );
	return $map;
});
