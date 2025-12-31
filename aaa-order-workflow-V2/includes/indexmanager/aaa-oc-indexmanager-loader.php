<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/indexmanager/aaa-oc-indexmanager-loader.php
 * Purpose: IndexManager — helpers, declarations, installers, indexers, hooks, assets, settings tabs.
 * Version: 1.1.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_IM_LOADER_READY' ) ) return;
define( 'AAA_OC_IM_LOADER_READY', true );
if ( ! defined( 'AAA_OC_IM_DEBUG' ) ) define( 'AAA_OC_IM_DEBUG', false );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: im) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-indexmanager-helpers.php',           false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-indexmanager-table-installer.php',     false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-indexmanager-declarations.php',              false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-indexmanager-table-indexer.php',       false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-indexmanager-hooks-users.php',         false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-indexmanager-hooks-products.php',      false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-indexmanager-hooks-orders.php',        false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-indexmanager-rest.php',              false, 'im' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/aaa-oc-indexmanager-assets-loader.php',                   false, 'im' );
} else {
	foreach ([
		'/helpers/class-aaa-oc-indexmanager-helpers.php',
		'/index/class-aaa-oc-indexmanager-table-installer.php',
		'/index/aaa-oc-indexmanager-declarations.php',
		'/index/class-aaa-oc-indexmanager-table-indexer.php',
		'/hooks/class-aaa-oc-indexmanager-hooks-users.php',
		'/hooks/class-aaa-oc-indexmanager-hooks-products.php',
		'/hooks/class-aaa-oc-indexmanager-hooks-orders.php',
		'/helpers/class-aaa-oc-indexmanager-rest.php',
		'/aaa-oc-indexmanager-assets-loader.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Normalize settings (unchanged) */
add_action( 'plugins_loaded', function(){
	if ( class_exists( 'AAA_OC_IndexManager_Helpers' ) ) {
		$u = AAA_OC_IndexManager_Helpers::get_opt('users');
		if ( is_array($u) && ! empty($u['session_only']) && empty($u['login_index']) ) {
			$u['login_index'] = 1;
			AAA_OC_IndexManager_Helpers::set_opt('users', $u);
		}
	}
}, 5 );

/* STEP 6: Ensure tables (unchanged) */
add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_IndexManager_Table_Installer' ) ) {
		AAA_OC_IndexManager_Table_Installer::ensure_all();
	}
}, 5 );

/* STEP 7: Boot entity hooks (unchanged) */
add_action( 'plugins_loaded', function () {
	class_exists('AAA_OC_IndexManager_Hooks_Users')    && AAA_OC_IndexManager_Hooks_Users::boot();
	class_exists('AAA_OC_IndexManager_Hooks_Products') && AAA_OC_IndexManager_Hooks_Products::boot();
	class_exists('AAA_OC_IndexManager_Hooks_Orders')   && AAA_OC_IndexManager_Hooks_Orders::boot();
}, 12 );

/* STEP 8: Settings tabs (unchanged) */
add_filter( 'aaa_oc_core_settings_tabs', function( array $tabs ) use ( $BASE ) {
	$map = [
		'aaa-oc-indexmanager-users'    => [ 'label' => 'Index Manager — Users',    'file' => $BASE . '/admin/tabs/aaa-oc-indexmanager-users.php' ],
		'aaa-oc-indexmanager-products' => [ 'label' => 'Index Manager — Products', 'file' => $BASE . '/admin/tabs/aaa-oc-indexmanager-products.php' ],
		'aaa-oc-indexmanager-orders'   => [ 'label' => 'Index Manager — Orders',   'file' => $BASE . '/admin/tabs/aaa-oc-indexmanager-orders.php' ],
	];
	foreach ( $map as $id => $spec ) if ( file_exists( $spec['file'] ) ) $tabs[ $id ] = $spec;
	return $tabs;
}, 20 );

/* STEP 9: Session guard */
add_action( 'init', function(){ $GLOBALS['aaa_oc_im_last_uid'] = get_current_user_id(); }, 1 );
