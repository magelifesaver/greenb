<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/announcements/aaa-oc-annc-loader.php
 * Purpose: Announcements — tables, capability seed, settings page, AJAX, board assets, tab.
 * Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_ANNC_LOADER_READY' ) ) return;
define( 'AAA_OC_ANNC_LOADER_READY', true );
if ( ! defined( 'AAA_OC_ANNC_DEBUG' ) )   define( 'AAA_OC_ANNC_DEBUG', false );
if ( ! defined( 'AAA_OC_ANNC_VERSION' ) ) define( 'AAA_OC_ANNC_VERSION', '1.4.2' );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: annc) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-annc-table-installer.php', false, 'annc' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-annc-declarations.php',    false, 'annc' );

	$ui   = $BASE . '/admin/class-aaa-oc-annc-settings-page.php';
	$ajax = $BASE . '/ajax/class-aaa-oc-annc-ajax.php';
	AAA_OC_Loader_Util::require_or_log( $ui,   false, 'annc' );
	AAA_OC_Loader_Util::require_or_log( $ajax, false, 'annc' );
} else {
	foreach ([
		'/index/aaa-oc-annc-table-installer.php',
		'/index/aaa-oc-annc-declarations.php',
		'/admin/class-aaa-oc-annc-settings-page.php',
		'/ajax/class-aaa-oc-annc-ajax.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Admin UI (optional) */
$ui = $BASE . '/admin/class-aaa-oc-annc-settings-page.php';
if ( class_exists('AAA_OC_Loader_Util') ) AAA_OC_Loader_Util::require_or_log( $ui, 'annc' );
elseif ( file_exists( $ui ) ) require_once $ui;

if ( class_exists( 'AAA_OC_Announcements_Settings_Page' ) ) new AAA_OC_Announcements_Settings_Page();

/* STEP 6: AJAX (optional) */
$ajax = $BASE . '/ajax/class-aaa-oc-annc-ajax.php';
if ( class_exists('AAA_OC_Loader_Util') ) AAA_OC_Loader_Util::require_or_log( $ajax, 'annc' );
elseif ( file_exists( $ajax ) ) require_once $ajax;

if ( class_exists( 'AAA_OC_Announcements_Ajax' ) && method_exists( 'AAA_OC_Announcements_Ajax', 'init' ) ) {
	AAA_OC_Announcements_Ajax::init();
}

/* STEP 7: Installer (idempotent) */
add_action( 'admin_init', function () {
	if ( class_exists( 'AAA_OC_Announcements_Table_Installer' ) ) {
		AAA_OC_Announcements_Table_Installer::maybe_install();
	}
	if ( function_exists( 'aaa_oc_log' ) && AAA_OC_ANNC_DEBUG ) aaa_oc_log('[ANN] admin_init maybe_install');
}, 5 );

/* STEP 8: Capability seed (unchanged logic) */
add_action( 'init', function () {
	$cap = 'aaa_oc_view_announcements';
	if ( function_exists( 'aaa_oc_get_option' ) && aaa_oc_get_option( 'aaa_oc_annc_cap_seeded', 'announcements' ) ) return;

	if ( function_exists( 'wp_roles' ) ) {
		$roles = wp_roles();
		if ( $roles && ! empty( $roles->roles ) ) {
			foreach ( array_keys( $roles->roles ) as $slug ) {
				$role = get_role( $slug );
				if ( $role && isset( $role->capabilities[ $cap ] ) ) {
					if ( function_exists( 'aaa_oc_set_option' ) ) aaa_oc_set_option( 'aaa_oc_annc_cap_seeded', 1, 'announcements' );
					return;
				}
			}
		}
	}
	$shop_mgr = get_role( 'shop_manager' );
	if ( $shop_mgr ) {
		$shop_mgr->add_cap( $cap );
		if ( function_exists( 'aaa_oc_set_option' ) ) aaa_oc_set_option( 'aaa_oc_annc_cap_seeded', 1, 'announcements' );
	}
}, 5 );

/* STEP 9: Board-only assets (unchanged) */
add_action( 'admin_enqueue_scripts', function () {
	if ( ! is_admin() ) return;
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	$is_board = isset($_GET['page']) && $_GET['page'] === 'aaa-oc-workflow-board';
	if ( ! $screen || strpos( (string) $screen->id, 'aaa-oc' ) === false || ! $is_board ) return;

	$ver = defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : AAA_OC_ANNC_VERSION;
	wp_enqueue_style( 'aaa-oc-annc-css',  AAA_OC_PLUGIN_URL . 'includes/announcements/assets/css/announcements.css', [], $ver );
	wp_enqueue_script('aaa-oc-annc-js',   AAA_OC_PLUGIN_URL . 'includes/announcements/assets/js/announcements.js', [ 'jquery' ], $ver, true );
	wp_localize_script( 'aaa-oc-annc-js', 'AAA_OC_ANN', [
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'aaa_oc_annc' ),
		'i18n'  => [ 'ack' => __( 'I have read this and I am up to date with the new changes.', 'aaa-oc' ), 'button' => __( 'Acknowledge & Close', 'aaa-oc' ), 'next' => __( 'Next', 'aaa-oc' ) ],
	] );
}, 10 );

/* STEP 10: Settings tab pointer (unchanged) */
add_filter( 'aaa_oc_core_settings_tabs', function ( array $tabs ) use ( $BASE ) {
	$file = $BASE . '/admin/tabs/aaa-oc-annc.php';
	if ( file_exists( $file ) ) $tabs['aaa-oc-annc'] = [ 'label' => 'Announcements', 'file' => $file ];
	return $tabs;
}, 20 );
