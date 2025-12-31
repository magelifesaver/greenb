<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/announcements/index/aaa-oc-payment-declarations.php
 * Purpose: WFCP declarations for announcements module.
 * Notes: Declares tables created by announcements.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['annc'] = array_unique( array_merge( $map['annc'] ?? [], [
		$wpdb->prefix . 'aaa_oc_announcements',
		$wpdb->prefix . 'aaa_oc_announcement_user',
	] ) );
	return $map;
});

