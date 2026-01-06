<?php
/**
 * File: /wp-content/plugins/aaa_oc_product_forecast_index/includes/productforecast/index/aaa-oc-productforecast-declarations.php
 * Purpose: WFCP declarations for ProductForecast module (expected tables).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'aaa_oc_expected_tables', function( $map ) {
    global $wpdb;
    $map = is_array( $map ) ? $map : [];
    $map['productforecast'] = array_unique( array_merge( $map['productforecast'] ?? [], [
        $wpdb->prefix . 'aaa_oc_productforecast_index',
        $wpdb->prefix . 'aaa_oc_productforecast_log',
    ] ) );
    return $map;
} );
