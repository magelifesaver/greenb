<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/aaa-oc-productsearch-loader.php
 * Purpose: ProductSearch module bootstrap — installers, helpers, indexer hooks, search hooks (V1).
 * Version: 1.1.2
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Idempotent guard – prevent double-loading.
 */
if ( defined( 'AAA_OC_PRODUCTSEARCH_LOADER_READY' ) ) {
	return;
}
define( 'AAA_OC_PRODUCTSEARCH_LOADER_READY', true );

/** Debug/Version */
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) ) {
	define( 'AAA_OC_PRODUCTSEARCH_DEBUG', true );
}
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_VERSION' ) ) {
	define( 'AAA_OC_PRODUCTSEARCH_VERSION', '1.1.2' );
}

/**
 * Includes: helpers, installers, indexers, search hooks.
 * Simple V1-style requires (no Loader_Util dependency).
 */
require_once __DIR__ . '/helpers/class-aaa-oc-productsearch-helpers.php';
require_once __DIR__ . '/index/class-aaa-oc-productsearch-table-installer.php';
require_once __DIR__ . '/index/class-aaa-oc-productsearch-table-indexer.php';
require_once __DIR__ . '/hooks/class-aaa-oc-productsearch-search-hooks.php';

/**
 * Small loader-level log helper (uses aaa_oc_log when available).
 */
if ( ! function_exists( 'aaa_oc_productsearch_log' ) ) {
	function aaa_oc_productsearch_log( $msg ) {
		if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) || ! AAA_OC_PRODUCTSEARCH_DEBUG ) {
			return;
		}
		if ( function_exists( 'aaa_oc_log' ) ) {
			aaa_oc_log( '[PRODUCTSEARCH] ' . $msg );
		} else {
			error_log( '[PRODUCTSEARCH] ' . $msg );
		}
	}
}

aaa_oc_productsearch_log( 'Loader included (V1)' );

/**
 * Wire indexer + search hooks after WooCommerce is available.
 */
add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		aaa_oc_productsearch_log( 'WooCommerce not active; aborting hook wiring.' );
		return;
	}

	// Index maintenance triggers.
	if ( class_exists( 'AAA_OC_ProductSearch_Table_Indexer' ) ) {
		add_action(
			'woocommerce_product_set_stock_status',
			array( 'AAA_OC_ProductSearch_Table_Indexer', 'on_stock_status' ),
			10,
			3
		);
		add_action(
			'save_post_product',
			array( 'AAA_OC_ProductSearch_Table_Indexer', 'on_product_save' ),
			20,
			2
		);
		add_action(
			'set_object_terms',
			array( 'AAA_OC_ProductSearch_Table_Indexer', 'on_terms_set' ),
			20,
			6
		);

		aaa_oc_productsearch_log( 'Indexer hooks wired on plugins_loaded.' );
	} else {
		aaa_oc_productsearch_log( 'AAA_OC_ProductSearch_Table_Indexer missing; hooks not wired.' );
	}

	// Search integration – intercept Woo search and use our index.
	if ( class_exists( 'AAA_OC_ProductSearch_Search_Hooks' ) && method_exists( 'AAA_OC_ProductSearch_Search_Hooks', 'init' ) ) {
		AAA_OC_ProductSearch_Search_Hooks::init();
		aaa_oc_productsearch_log( 'Search hooks init() called.' );
	} else {
		aaa_oc_productsearch_log( 'Search hooks class missing; search not overridden.' );
	}
}, 20 );

/**
 * Ensure tables exist on admin requests (dbDelta-safe).
 * Mirrors how other V1 index/table installers run.
 */
add_action( 'admin_init', function () {
	if ( ! class_exists( 'AAA_OC_ProductSearch_Table_Installer' ) ) {
		aaa_oc_productsearch_log( 'Installer class missing on admin_init.' );
		return;
	}

	AAA_OC_ProductSearch_Table_Installer::maybe_install();
	aaa_oc_productsearch_log( 'maybe_install executed on admin_init.' );
}, 5 );
