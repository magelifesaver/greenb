<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/payconfirm/aaa-oc-payconfirm-loader.php
 * Purpose: Load PayConfirm core + admin tools (metabox, bulk, admin-post).
 * Version: 1.4.8
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Guard so an older/duplicate loader can’t re-run */
if ( defined( 'AAA_OC_PAYCONFIRM_LOADER_READY' ) ) { return; }
define( 'AAA_OC_PAYCONFIRM_LOADER_READY', true );

if ( ! defined( 'AAA_OC_PAYCONFIRM_DEBUG' ) )   define( 'AAA_OC_PAYCONFIRM_DEBUG', true );
if ( ! defined( 'AAA_OC_PAYCONFIRM_VERSION' ) ) define( 'AAA_OC_PAYCONFIRM_VERSION', '1.4.8' );

/** --------------------------
 * Core & REST
 * -------------------------- */
require_once __DIR__ . '/index/class-aaa-oc-payconfirm-register.php';
require_once __DIR__ . '/index/class-aaa-oc-payconfirm-rest.php';

/** --------------------------
 * Parser + matcher
 * -------------------------- */
require_once __DIR__ . '/inc/parser/class-aaa-oc-payconfirm-parser.php';
require_once __DIR__ . '/inc/matcher/class-aaa-oc-payconfirm-matcher.php';

/** --------------------------
 * Hooks
 * -------------------------- */
require_once __DIR__ . '/inc/class-aaa-oc-payconfirm-hook.php';
require_once __DIR__ . '/inc/hooks/class-aaa-oc-payconfirm-triggers.php';
require_once __DIR__ . '/inc/hooks/class-aaa-oc-payconfirm-admin-actions.php';

/** --------------------------
 * Admin UI (loaded only in admin)
 * -------------------------- */
if ( is_admin() ) {
	require_once __DIR__ . '/admin/class-aaa-oc-payconfirm-bulk.php';
	require_once __DIR__ . '/admin/class-aaa-oc-payconfirm-metabox.php';
	require_once __DIR__ . '/admin/class-aaa-oc-payconfirm-columns.php';
	require_once __DIR__ . '/admin/class-aaa-oc-payconfirm-adminpost.php';
}

/** --------------------------
 * Init
 * -------------------------- */
add_action( 'plugins_loaded', function () {
	if ( AAA_OC_PAYCONFIRM_DEBUG ) {
		error_log( '[PayConfirm] loader boot v' . AAA_OC_PAYCONFIRM_VERSION );
	}

	// Core always inits (front + admin)
	AAA_OC_PayConfirm_Register::init();
	AAA_OC_PayConfirm_REST::init();
	AAA_OC_PayConfirm_Hook::init();
	AAA_OC_PayConfirm_Triggers::init();
	AAA_OC_PayConfirm_Admin_Actions::init();
} );

/**
 * Admin-only init on admin_init to guarantee availability during editor/metabox lifecycle.
 * (Avoids edge cases where is_admin() on plugins_loaded is true, but some admin
 * screens initialize later and miss early hooks.)
 */
if ( is_admin() ) {
	add_action( 'admin_init', function () {
		AAA_OC_PayConfirm_Bulk::init();
		AAA_OC_PayConfirm_Metabox::init();
		AAA_OC_PayConfirm_Columns::init();
		AAA_OC_PayConfirm_AdminPost::init();

		if ( AAA_OC_PAYCONFIRM_DEBUG ) {
			error_log( '[PayConfirm] admin init wired (bulk, metabox, columns, adminpost)' );
		}
	} );
}
