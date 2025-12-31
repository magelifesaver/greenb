<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/aaa-oc-payconfirm-loader.php
 * Purpose: PayConfirm — CPT/register, REST, parser, matcher, hooks, inbox table, extenders.
 * Version: 1.7.3
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_PAYCONFIRM_LOADER_READY' ) ) return;
define( 'AAA_OC_PAYCONFIRM_LOADER_READY', true );
if ( ! defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) ) define( 'AAA_OC_PAYCONFIRM_DEBUG', false );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: payconfirm) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payconfirm-register.php',         false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payconfirm-rest.php',             false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-payconfirm-declarations.php',           false, 'payconfirm' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/parser/class-aaa-oc-payconfirm-parser.php',      false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/matcher/class-aaa-oc-payconfirm-matcher.php',    false, 'payconfirm' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/class-aaa-oc-payconfirm-hook.php',               false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/hooks/class-aaa-oc-payconfirm-triggers.php',     false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/hooks/class-aaa-oc-payconfirm-order-alias.php',  false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/inc/hooks/class-aaa-oc-payconfirm-admin-actions.php',false, 'payconfirm' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payconfirm-table-installer.php',  false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payconfirm-table-extender.php',   false, 'payconfirm' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-payconfirm-extender-indexer.php', false, 'payconfirm' );

	if ( is_admin() ) {
		AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-payconfirm-bulk.php',      false, 'payconfirm' );
		AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-payconfirm-metabox.php',   false, 'payconfirm' );
		AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-payconfirm-columns.php',   false, 'payconfirm' );
		AAA_OC_Loader_Util::require_or_log( $BASE . '/admin/class-aaa-oc-payconfirm-adminpost.php', false, 'payconfirm' );
	}
} else {
	foreach ([
		'/index/class-aaa-oc-payconfirm-register.php',
		'/index/class-aaa-oc-payconfirm-rest.php',
		'/index/aaa-oc-payconfirm-declarations.php',
		'/inc/parser/class-aaa-oc-payconfirm-parser.php',
		'/inc/matcher/class-aaa-oc-payconfirm-matcher.php',
		'/inc/class-aaa-oc-payconfirm-hook.php',
		'/inc/hooks/class-aaa-oc-payconfirm-triggers.php',
		'/inc/hooks/class-aaa-oc-payconfirm-order-alias.php',
		'/inc/hooks/class-aaa-oc-payconfirm-admin-actions.php',
		'/index/class-aaa-oc-payconfirm-table-installer.php',
		'/index/class-aaa-oc-payconfirm-table-extender.php',
		'/index/class-aaa-oc-payconfirm-extender-indexer.php',
		'/admin/class-aaa-oc-payconfirm-bulk.php',
		'/admin/class-aaa-oc-payconfirm-metabox.php',
		'/admin/class-aaa-oc-payconfirm-columns.php',
		'/admin/class-aaa-oc-payconfirm-adminpost.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: (removed) — no $R() shorthand; admin files already required above */

/* STEP 6: Boot classes */
foreach ( [
	'AAA_OC_PayConfirm_Register',
	'AAA_OC_PayConfirm_REST',
	'AAA_OC_PayConfirm_Hook',
	'AAA_OC_PayConfirm_Triggers',
	'AAA_OC_PayConfirm_Order_Alias',
	'AAA_OC_PayConfirm_Admin_Actions',
] as $cls ) {
	if ( class_exists( $cls ) && method_exists( $cls, 'init' ) ) { $cls::init(); }
}

/* STEP 7: Installers/extenders */
add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_PayConfirm_Table_Installer' ) && method_exists( 'AAA_OC_PayConfirm_Table_Installer', 'maybe_install' ) ) {
		AAA_OC_PayConfirm_Table_Installer::maybe_install();
	}
}, 10 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_PayConfirm_Table_Installer' ) && method_exists( 'AAA_OC_PayConfirm_Table_Installer', 'install' ) ) {
		AAA_OC_PayConfirm_Table_Installer::install();
	}
}, 10 );

add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_PayConfirm_Table_Extender' ) && method_exists( 'AAA_OC_PayConfirm_Table_Extender', 'install' ) ) {
		AAA_OC_PayConfirm_Table_Extender::install();
	}
}, 12 );

add_action( 'aaa_oc_module_install', function () {
	if ( class_exists( 'AAA_OC_PayConfirm_Table_Extender' ) && method_exists( 'AAA_OC_PayConfirm_Table_Extender', 'install' ) ) {
		AAA_OC_PayConfirm_Table_Extender::install();
	}
}, 12 );
