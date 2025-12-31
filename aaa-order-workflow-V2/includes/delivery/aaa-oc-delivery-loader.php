<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/aaa-oc-core-delivery-loader.php
 * Purpose: Delivery module — tables/extenders/helpers/hooks.
 * Version: 1.5.2
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* STEP 1: Idempotent + debug */
if ( defined( 'AAA_OC_DELIVERY_LOADER_READY' ) ) return;
define( 'AAA_OC_DELIVERY_LOADER_READY', true );
if ( ! defined( 'AAA_OC_DELIVERY_DEBUG' ) ) define( 'AAA_OC_DELIVERY_DEBUG', false );

/* STEP 2: Base path */
$BASE = __DIR__;

/* STEP 3: Ensure central util */
$util = dirname( __DIR__, 2 ) . '/core/options/helpers/class-aaa-oc-loader-util.php';
if ( ! class_exists('AAA_OC_Loader_Util') && file_exists($util) ) require_once $util;

/* STEP 4: Tracked includes (tag: delivery) */
if ( class_exists('AAA_OC_Loader_Util') ) {
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-delivery-table-installer.php',  false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-delivery-table-extender.php',   false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-delivery-extender-indexer.php', false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/class-aaa-oc-delivery-bridge-indexer.php', false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/index/aaa-oc-delivery-declarations.php',           false, 'delivery' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-delivery-key-bridge.php',     false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-delivery-metabox.php',        false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-save-delivery.php',           false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-save-driver.php',             false, 'delivery' );

	AAA_OC_Loader_Util::require_or_log( $BASE . '/hooks/class-aaa-oc-delivery-date-time.php',       false, 'delivery' );
	AAA_OC_Loader_Util::require_or_log( $BASE . '/helpers/class-aaa-oc-delivery-scheduled.php',      false, 'delivery' );
} else {
	foreach ([
		'/index/class-aaa-oc-delivery-table-installer.php',
		'/index/class-aaa-oc-delivery-table-extender.php',
		'/index/class-aaa-oc-delivery-extender-indexer.php',
		'/index/class-aaa-oc-delivery-bridge-indexer.php',
		'/index/aaa-oc-delivery-declarations.php',
		'/helpers/class-aaa-oc-delivery-key-bridge.php',
		'/helpers/class-aaa-oc-delivery-metabox.php',
		'/helpers/class-aaa-oc-save-delivery.php',
		'/helpers/class-aaa-oc-save-driver.php',
		'/hooks/class-aaa-oc-delivery-date-time.php',
		'/helpers/class-aaa-oc-delivery-scheduled.php',
	] as $rel) { $f = $BASE . $rel; if ( file_exists($f) ) require_once $f; }
}

/* STEP 5: Installers (idempotent) */
$maybe_install = function() {
	if ( class_exists( 'AAA_OC_Delivery_Table_Installer' ) )  AAA_OC_Delivery_Table_Installer::maybe_install();
	if ( class_exists( 'AAA_OC_Delivery_Table_Extender' ) )   AAA_OC_Delivery_Table_Extender::maybe_install();
};
add_action( 'admin_init', $maybe_install, 5 );
add_action( 'aaa_oc_module_install', $maybe_install, 10 );
/* STEP 6: Debug log */
if ( function_exists('aaa_oc_log') && AAA_OC_DELIVERY_DEBUG ) aaa_oc_log('[DELIVERY] loader ready');
