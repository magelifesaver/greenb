<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/aaa-oc-productsearch-loader.php
 * Purpose: ProductSearch — installers, helpers, indexer, hooks, admin assets, declarations.
 * Version: 1.1.4
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_PRODUCTSEARCH_LOADER_READY' ) ) return;
define( 'AAA_OC_PRODUCTSEARCH_LOADER_READY', true );
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) )   define( 'AAA_OC_PRODUCTSEARCH_DEBUG', false );
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_VERSION' ) ) define( 'AAA_OC_PRODUCTSEARCH_VERSION', '1.1.4' );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: productsearch) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-productsearch-helpers.php',        false, 'productsearch' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-productsearch-table-installer.php',  false, 'productsearch' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-productsearch-table-indexer.php',    false, 'productsearch' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-productsearch-declarations.php',           false, 'productsearch' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-productsearch-search-hooks.php',     false, 'productsearch' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-productsearch-results-hooks.php',     false, 'productsearch' );

} else {
	foreach ([
		'/helpers/class-aaa-oc-productsearch-helpers.php',
		'/index/class-aaa-oc-productsearch-table-installer.php',
		'/index/class-aaa-oc-productsearch-table-indexer.php',
		'/index/aaa-oc-productsearch-declarations.php',
		'/hooks/class-aaa-oc-productsearch-search-hooks.php',
		'/hooks/class-aaa-oc-productsearch-results-hooks.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5+: existing hooks/installers/assets remain unchanged */
add_filter( 'aaa_oc_expected_tables', function( $map ) {
	global $wpdb;
	$map = is_array( $map ) ? $map : [];
	$map['productsearch'] = array_unique( array_merge( $map['productsearch'] ?? [], [
		$wpdb->prefix . 'aaa_oc_productsearch_index',
		$wpdb->prefix . 'aaa_oc_productsearch_synonyms',
	] ) );
	return $map;
});

/* STEP 6: Boot + installers + assets (unchanged) */
add_action( 'plugins_loaded', function () {
	if ( class_exists( 'AAA_OC_ProductSearch_Search_Hooks' ) && method_exists( 'AAA_OC_ProductSearch_Search_Hooks', 'init' ) ) {
		AAA_OC_ProductSearch_Search_Hooks::init();
	}
	if ( class_exists( 'AAA_OC_ProductSearch_Table_Indexer' ) ) {
		add_action( 'woocommerce_product_set_stock_status', [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_stock_status' ], 10, 3 );
		add_action( 'save_post_product',                     [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_product_save' ], 20, 2 );
		add_action( 'set_object_terms',                      [ 'AAA_OC_ProductSearch_Table_Indexer', 'on_terms_set' ],    20, 6 );
	}
}, 8 );

add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) && method_exists( 'AAA_OC_ProductSearch_Table_Installer', 'maybe_install' ) ) {
		AAA_OC_ProductSearch_Table_Installer::maybe_install();
	}
AAA_OC_ProductSearch_Results_Hooks::init();
}, 5 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) && method_exists( 'AAA_OC_ProductSearch_Table_Installer', 'install' ) ) {
		AAA_OC_ProductSearch_Table_Installer::install();
	}
}, 10 );

add_action( 'admin_enqueue_scripts', function () {
	$screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$on_board = isset( $_GET['page'] ) && $_GET['page'] === 'aaa-oc-workflow-board';
	if ( ! $screen || strpos( (string) $screen->id, 'aaa-oc' ) === false || ! $on_board ) return;
	if ( ! apply_filters( 'aaa_oc_load_module_assets', true, 'productsearch', [] ) ) return;

	$ver = defined('AAA_OC_VERSION') ? AAA_OC_VERSION : AAA_OC_PRODUCTSEARCH_VERSION;
	wp_enqueue_style(  'aaa-oc-productsearch', plugin_dir_url(__FILE__) . 'assets/css/aaa-oc-productsearch.css', [], $ver );
	wp_enqueue_script( 'aaa-oc-productsearch', plugin_dir_url(__FILE__) . 'assets/js/aaa-oc-productsearch.js',  [ 'jquery' ], $ver, true );
}, 10 );
