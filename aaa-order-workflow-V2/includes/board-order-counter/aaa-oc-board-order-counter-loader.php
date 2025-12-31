<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/board-order-counter/aaa-oc-board-order-counter-loader.php
 * Purpose: Daily Order Counter — assigns per-day incrementing number on order create.
 * Version: 1.4.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_ODC_LOADER_READY' ) ) return;
define( 'AAA_OC_ODC_LOADER_READY', true );
if ( ! defined( 'AAA_OC_ODC_DEBUG' ) ) define( 'AAA_OC_ODC_DEBUG', false );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked include (tag: odc) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/board-order-counter-helpers.php', false, 'odc' );
} else {
	$f = $BASE . '/helpers/board-order-counter-helpers.php';
	if ( file_exists($f) ) require_once $f;
}

/* STEP 5: Hook into Woo new order (unchanged) */
add_action( 'woocommerce_new_order', function ( $order_id ) {
	if ( function_exists( 'aaa_oc_board_counter_set_daily_number' ) ) {
		aaa_oc_board_counter_set_daily_number( (int) $order_id );
	}
}, 10, 1 );
