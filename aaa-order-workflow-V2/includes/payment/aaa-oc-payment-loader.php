<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payment/aaa-oc-payment-loader.php
 * Purpose: Payment module — tables, OI extender, helpers, AJAX, board hooks.
 * Version: 1.2.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent */
if ( defined( 'AAA_OC_PAYMENT_LOADER_READY' ) ) return;
define( 'AAA_OC_PAYMENT_LOADER_READY', false );

/**
 * DEBUG RULE:
 * - Admin setting is the source of truth (if available).
 * - Per-file constant remains as a hard override (define true/false in wp-config or MU if needed).
 */
if ( ! defined( 'AAA_OC_PAYMENT_DEBUG' ) ) {
	$__opt_debug = false;
	if ( class_exists( 'AAA_OC_Options' ) && method_exists( 'AAA_OC_Options', 'get' ) ) {
		// Try specific, then broader module/global keys you’ve used elsewhere.
		$__opt_debug = (bool) (
			AAA_OC_Options::get( 'debug_payment' )           // most specific
			?: AAA_OC_Options::get( 'debug_modules_payment' )
			?: AAA_OC_Options::get( 'debug_modules_all' )
			?: AAA_OC_Options::get( 'debug_all' )            // global catch-all
		);
	}
	define( 'AAA_OC_PAYMENT_DEBUG', $__opt_debug );
}
/* STEP 2: Base path */
$BASE = __DIR__;


/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists( 'AAA_OC_Loader_Util' ) && file_exists( $util ) ) require_once $util;

/* STEP 4: Tracked includes (tag: payment) */

if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payment-table-installer.php', false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payment-table-extender.php',  false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-payment-declarations.php',          false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-payment-card-hooks.php',      false, 'payment' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/class-aaa-oc-payment-fields.php',            false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/class-aaa-oc-payment-meta.php',              false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-disable-auto-paid.php',     false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-map-payment-method.php',    false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-payment-status-label.php',  false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-payment-calc.php',          false, 'payment' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payment-indexer.php',         false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/ajax/class-aaa-oc-ajax-payment-update.php',      false, 'payment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/ajax/class-aaa-oc-payment-feed.php',             false, 'payment' );
} else {
	foreach ([
		'/index/class-aaa-oc-payment-table-installer.php',
		'/index/class-aaa-oc-payment-table-extender.php',
		'/index/aaa-oc-payment-declarations.php',
		'/hooks/class-aaa-oc-payment-card-hooks.php',
		'/inc/class-aaa-oc-payment-fields.php',
		'/inc/class-aaa-oc-payment-meta.php',
		'/helpers/class-aaa-oc-disable-auto-paid.php',
		'/helpers/class-aaa-oc-map-payment-method.php',
		'/helpers/class-aaa-oc-payment-status-label.php',
		'/helpers/class-aaa-oc-payment-calc.php',
		'/index/class-aaa-oc-payment-indexer.php',
		'/ajax/class-aaa-oc-ajax-payment-update.php',
		'/ajax/class-aaa-oc-payment-feed.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Installers (unchanged) */
if ( ! function_exists( 'aaa_oc_payment__base_oi_exists' ) ) {
	function aaa_oc_payment__base_oi_exists(): bool {
		global $wpdb; $tbl = $wpdb->prefix . 'aaa_oc_order_index'; $like = $wpdb->esc_like( $tbl );
		return ( $wpdb->get_var( "SHOW TABLES LIKE '{$like}'" ) === $tbl );
	}
}

add_action( 'plugins_loaded', function () {
	foreach ( [ 'AAA_OC_Payment_Meta', 'AAA_OC_Disable_Auto_Paid', 'AAA_OC_Payment_Indexer' ] as $cls ) {
		if ( class_exists( $cls ) && method_exists( $cls, 'init' ) ) $cls::init();
	}
}, 5 );

add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_Payment_Setup' ) ) {
		if ( method_exists( 'AAA_OC_Payment_Setup', 'maybe_install' ) ) AAA_OC_Payment_Setup::maybe_install();
		elseif ( method_exists( 'AAA_OC_Payment_Setup', 'install' ) )   AAA_OC_Payment_Setup::install();
	}
	if ( class_exists( 'AAA_OC_Payment_Table_Extender' ) ) {
		if ( aaa_oc_payment__base_oi_exists() ) {
			if ( method_exists( 'AAA_OC_Payment_Table_Extender', 'maybe_install' ) ) AAA_OC_Payment_Table_Extender::maybe_install();
			elseif ( method_exists( 'AAA_OC_Payment_Table_Extender', 'install' ) )   AAA_OC_Payment_Table_Extender::install();
		}
	}
}, 15 );

add_action( 'aaa_oc_core_tables_ready', function () {
	if ( class_exists( 'AAA_OC_Payment_Table_Extender' ) && aaa_oc_payment__base_oi_exists() && method_exists( 'AAA_OC_Payment_Table_Extender', 'install' ) ) {
		AAA_OC_Payment_Table_Extender::install();
	}
}, 10 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_Payment_Setup' ) && method_exists( 'AAA_OC_Payment_Setup', 'install' ) ) {
		AAA_OC_Payment_Setup::install();
	}
	if ( class_exists( 'AAA_OC_Payment_Table_Extender' ) && method_exists( 'AAA_OC_Payment_Table_Extender', 'install' ) ) {
		if ( aaa_oc_payment__base_oi_exists() ) {
			AAA_OC_Payment_Table_Extender::install();
		}
	}
}, 10 );
