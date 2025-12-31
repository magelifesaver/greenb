<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/aaa-oc-customer-loader.php
 * Purpose: Customer module — tables + OI extender + indexer + hooks + admin profile + ajax.
 * Version: 1.1.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_CUSTOMER_LOADER_READY' ) ) return;
define( 'AAA_OC_CUSTOMER_LOADER_READY', true );
if ( ! defined( 'AAA_OC_CUSTOMER_DEBUG' ) ) define( 'AAA_OC_CUSTOMER_DEBUG', false );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: customer) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-customer-table-installer.php', false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-customer-table-extender.php',  false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-customer-indexer.php',         false, 'customer' );
//	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-customer-border-filters.php',  false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-customer-declarations.php',          false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-customer-card-hooks.php',      false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/aaa-oc-customer-board-borders.php',      false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-customer-user-columns.php', false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/ajax/class-aaa-oc-customer-inline-save.php', false, 'customer' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-customer-profile-fields.php', false, 'customer' );
} else {
	foreach ([
		'/index/class-aaa-oc-customer-table-installer.php',
		'/index/class-aaa-oc-customer-table-extender.php',
		'/index/class-aaa-oc-customer-indexer.php',
//		'/hooks/class-aaa-oc-customer-border-filters.php',
		'/index/aaa-oc-customer-declarations.php',
		'/hooks/class-aaa-oc-customer-card-hooks.php',
		'/hooks/aaa-oc-customer-board-borders.php',
		'/admin/class-aaa-oc-customer-user-columns.php',
		'/ajax/class-aaa-oc-customer-inline-save.php',
		'/admin/class-aaa-oc-customer-profile-fields.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Boot + installers (unchanged) */
add_action( 'plugins_loaded', function () {
	if ( class_exists( 'AAA_OC_Customer_Indexer' ) && method_exists( 'AAA_OC_Customer_Indexer', 'init' ) ) {
		AAA_OC_Customer_Indexer::init();
	}
}, 5 );

add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_Customer_Table_Installer' ) && method_exists( 'AAA_OC_Customer_Table_Installer', 'maybe_install' ) ) {
		AAA_OC_Customer_Table_Installer::maybe_install();
	}
	if ( class_exists( 'AAA_OC_Customer_Table_Extender' ) && method_exists( 'AAA_OC_Customer_Table_Extender', 'maybe_install' ) ) {
		AAA_OC_Customer_Table_Extender::maybe_install();
	}
}, 6 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_Customer_Table_Installer' ) && method_exists( 'AAA_OC_Customer_Table_Installer', 'install' ) ) {
		AAA_OC_Customer_Table_Installer::install();
	}
	if ( class_exists( 'AAA_OC_Customer_Table_Extender' ) && method_exists( 'AAA_OC_Customer_Table_Extender', 'install' ) ) {
		AAA_OC_Customer_Table_Extender::install();
	}
}, 10 );
