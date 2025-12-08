<?php
/**
 * File: /includes/core/modules/board-order-counter/board-order-counter-loader.php
 * Purpose: Initialize the Daily Order Counter module within AAA Order Workflow (V1).
 * Version: 1.3.1
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Local debug flag for this file (guarded to prevent redeclaration)
if ( ! defined( 'DEBUG_THIS_FILE' ) ) {
	define( 'DEBUG_THIS_FILE', true );
}

/**
 * Hook into WooCommerce order creation.
 */
add_action( 'woocommerce_new_order', function ( $order_id ) {
	require_once __DIR__ . '/helpers/board-order-counter-helpers.php';
	aaa_oc_board_counter_set_daily_number( $order_id );
}, 10, 1 );

/**
 * Register module in Workflow registry (V1).
 */
add_filter( 'aaa_oc_module_registry', function ( $mods ) {
	$mods['board-order-counter'] = [
		'title'       => 'Board: Daily Order Counter',
		'description' => 'Assigns a daily incrementing order number; resets each day.',
		'version'     => '1.3.1',
		'scope'       => 'odc',
	];
	return $mods;
});
