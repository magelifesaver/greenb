<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/compat/aaa-oc-compat-loader.php
 * Purpose: Compatibility layer (schema extenders only) + funds bridge.
 * Version: 1.0.4
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Base path */
$base_dir = __DIR__;

/* STEP 2: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 3: Tracked includes (tag: compat) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $base_dir . '/index/class-aaa-oc-compat-table-extender.php', false, 'compat' );
	AAA_OC_Loader_Util::require_or_log( $base_dir . '/index/aaa-oc-compat-declarations.php',        false, 'compat' );
	AAA_OC_Loader_Util::require_or_log( $base_dir . '/index/class-aaa-oc-compat-funds-bridge.php',  false, 'compat' );
	AAA_OC_Loader_Util::require_or_log( $base_dir . '/index/class-aaa-oc-compat-indexer.php',       false, 'compat' );
} else {
	foreach ([
		'/index/class-aaa-oc-compat-table-extender.php',
		'/index/aaa-oc-compat-declarations.php',
		'/index/class-aaa-oc-compat-funds-bridge.php',
		'/index/class-aaa-oc-compat-indexer.php',
	] as $rel) { $f = $base_dir . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 4: Boot classes (unchanged) */
add_action( 'plugins_loaded', function () {
	if ( class_exists( 'AAA_OC_Compat_Indexer' ) && method_exists( 'AAA_OC_Compat_Indexer', 'init' ) ) {
		AAA_OC_Compat_Indexer::init();
	}
	if ( class_exists( 'AAA_OC_Compat_Funds_Bridge' ) && method_exists( 'AAA_OC_Compat_Funds_Bridge', 'init' ) ) {
		AAA_OC_Compat_Funds_Bridge::init();
	}
}, 5 );
/* STEP 5: Installers (unchanged) */
add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_Compat_Table_Extender' ) ) {
		AAA_OC_Compat_Table_Extender::maybe_install();
	}
}, 5 );
add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_Compat_Table_Extender' ) ) {
		AAA_OC_Compat_Table_Extender::maybe_install();
	}
}, 10 );
