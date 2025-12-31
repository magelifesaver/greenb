<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/aaa-oc-core-fulfillment-loader.php
 * Purpose: Fulfillment module — logs/index extenders/helpers/admin hooks + settings tab registrar + installers.
 * Version: 1.4.1
 *
 * Notes:
 * - Debug constant now derives from options (matches Payment pattern).
 * - Class boot moved to plugins_loaded (priority 5) for symmetry.
 * - Extender also installs on aaa_oc_core_tables_ready (safety net).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent */
if ( defined( 'AAA_OC_FULFILLMENT_LOADER_READY' ) ) return;
define( 'AAA_OC_FULFILLMENT_LOADER_READY', false );

/* STEP 1A: Debug constant driven by options (fallback to constant override if pre-defined) */
if ( ! defined( 'AAA_OC_FULFILLMENT_DEBUG' ) ) {
	$__opt_debug = false;
	if ( class_exists( 'AAA_OC_Options' ) && method_exists( 'AAA_OC_Options', 'get' ) ) {
		$__opt_debug = (bool)(
			AAA_OC_Options::get( 'debug_fulfillment' )
			?: AAA_OC_Options::get( 'debug_modules_fulfillment' )
			?: AAA_OC_Options::get( 'debug_modules_all' )
			?: AAA_OC_Options::get( 'debug_all' )
		);
	}
	define( 'AAA_OC_FULFILLMENT_DEBUG', $__opt_debug );
}

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: fulfillment) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-product-fulfillment-index.php', false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-fulfillment-table-installer.php', false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-fulfillment-table-extender.php',  false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-fulfillment-extender-indexer.php', false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-fulfillment-declarations.php',          false, 'fulfillment' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-map-fulfillment-status.php',    false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-fulfillment-analytics.php',     false, 'fulfillment' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-admin-bulk-fulfill.php',          false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-admin-fulfillment-column.php',    false, 'fulfillment' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-fulfillment-products-table-hook.php', false, 'fulfillment' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-fulfillment-top-pills.php', false, 'fulfillment' );
	
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-fulfillment-indexer.php',          false, 'fulfillment' );
} else {
	foreach ([
		'/index/class-aaa-oc-product-fulfillment-index.php',
		'/index/class-aaa-oc-fulfillment-table-installer.php',
		'/index/class-aaa-oc-fulfillment-table-extender.php',
		'/index/class-aaa-oc-fulfillment-extender-indexer.php',
		'/index/aaa-oc-fulfillment-declarations.php',
		'/helpers/class-aaa-oc-map-fulfillment-status.php',
		'/helpers/class-aaa-oc-fulfillment-analytics.php',
		'/admin/class-aaa-oc-admin-bulk-fulfill.php',
		'/admin/class-aaa-oc-admin-fulfillment-column.php',
		'/hooks/class-aaa-oc-fulfillment-products-table-hook.php',
		'/hooks/class-aaa-oc-fulfillment-top-pills.php',
		'/index/class-aaa-oc-fulfillment-indexer.php',
	] as $rel ) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Settings tab self-registrar */
add_filter( 'aaa_oc_core_settings_tabs', function( array $tabs ){
	$file = __DIR__ . '/admin/tabs/aaa-oc-fulfillment.php';
	if ( file_exists( $file ) ) {
		$tabs['aaa-oc-fulfillment'] = [ 'label' => 'Fulfillment', 'file' => $file ];
	}
	return $tabs;
}, 20 );

/* STEP 6: Boot classes on plugins_loaded (priority 5) — symmetry with Payment */
add_action( 'plugins_loaded', function () {
	foreach ([
		'AAA_OC_Product_Fulfillment_Index',
		'AAA_OC_Fulfillment_Extender_Indexer',
		'AAA_OC_Fulfillment_Analytics',
		'AAA_OC_Map_Fulfillment_Status',
		'AAA_OC_Admin_Bulk_Fulfill',
		'AAA_OC_Admin_Fulfillment_Column',
	] as $cls ) {
		if ( class_exists( $cls ) && method_exists( $cls, 'init' ) ) { $cls::init(); }
	}
	if ( function_exists('aaa_oc_log') ) aaa_oc_log('[FULFILLMENT] plugins_loaded boot complete');
}, 5 );

/* STEP 7: Installers/extenders (admin, module install, and core-tables-ready safety net) */
add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_Fulfillment_Table_Installer' ) && method_exists( 'AAA_OC_Fulfillment_Table_Installer', 'maybe_install' ) ) {
		AAA_OC_Fulfillment_Table_Installer::maybe_install();
	}
	if ( class_exists( 'AAA_OC_Fulfillment_Table_Extender' ) && method_exists( 'AAA_OC_Fulfillment_Table_Extender', 'install' ) ) {
		AAA_OC_Fulfillment_Table_Extender::install();
	}
	if ( function_exists('aaa_oc_log') && AAA_OC_FULFILLMENT_DEBUG ) aaa_oc_log('[FULFILLMENT] admin_init ensured table+extender');
}, 10 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_Fulfillment_Table_Installer' ) && method_exists( 'AAA_OC_Fulfillment_Table_Installer', 'install' ) ) {
		AAA_OC_Fulfillment_Table_Installer::install();
	}
	if ( class_exists( 'AAA_OC_Fulfillment_Table_Extender' ) && method_exists( 'AAA_OC_Fulfillment_Table_Extender', 'install' ) ) {
		AAA_OC_Fulfillment_Table_Extender::install();
	}
	if ( function_exists('aaa_oc_log') ) aaa_oc_log('[FULFILLMENT] module_install ran installers');
}, 12 );

/* NEW: also react to core-tables-ready like Payment does */
add_action( 'aaa_oc_core_tables_ready', function () {
	if ( class_exists( 'AAA_OC_Fulfillment_Table_Extender' ) && method_exists( 'AAA_OC_Fulfillment_Table_Extender', 'install' ) ) {
		AAA_OC_Fulfillment_Table_Extender::install();
		if ( function_exists('aaa_oc_log') ) aaa_oc_log('[FULFILLMENT] core_tables_ready extender install');
	}
}, 10 );

/* STEP 8: Loader ready log */
if ( function_exists('aaa_oc_log') ) aaa_oc_log('[FULFILLMENT] loader ready');
