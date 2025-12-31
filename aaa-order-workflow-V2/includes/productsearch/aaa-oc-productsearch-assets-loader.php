<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/productsearch/aaa-oc-productsearch-assets-loader.php
 * Purpose: Assets for ProductSearch module (frontend + admin settings).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Debug toggle (reuses module-level flag when present).
 */
if ( ! defined( 'AAA_OC_PRODUCTSEARCH_DEBUG' ) ) {
	define( 'AAA_OC_PRODUCTSEARCH_DEBUG', true );
}

if ( ! class_exists( 'AAA_OC_ProductSearch_Assets_Loader' ) ) {

	class AAA_OC_ProductSearch_Assets_Loader {

		public static function init() {
			// Frontend (for search-related UX, kept very light).
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ) );

			// Admin settings page (for synonyms tab styling, etc.).
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
		}

		/**
		 * Frontend assets – very small; safe to load globally.
		 */
		public static function enqueue_frontend() {
			$base = plugin_dir_url( __FILE__ ) . 'assets/';

			wp_enqueue_style(
				'aaa-oc-productsearch',
				$base . 'css/aaa-oc-productsearch.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'aaa-oc-productsearch',
				$base . 'js/aaa-oc-productsearch.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			wp_localize_script(
				'aaa-oc-productsearch',
				'AAAOCPS',
				array(
					'siteUrl' => home_url( '/' ),
				)
			);

			if ( AAA_OC_PRODUCTSEARCH_DEBUG ) {
				if ( function_exists( 'aaa_oc_log' ) ) {
					aaa_oc_log( '[PRODUCTSEARCH] Frontend assets enqueued.' );
				} else {
					error_log( '[PRODUCTSEARCH] Frontend assets enqueued.' );
				}
			}
		}

		/**
		 * Admin assets – only on Workflow core settings page where the tab will live.
		 */
		public static function enqueue_admin( $hook ) {
			if ( empty( $_GET['page'] ) || $_GET['page'] !== 'aaa-oc-core-settings' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$base = plugin_dir_url( __FILE__ ) . 'assets/';

			wp_enqueue_style(
				'aaa-oc-productsearch-admin',
				$base . 'css/aaa-oc-productsearch.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'aaa-oc-productsearch-admin',
				$base . 'js/aaa-oc-productsearch.js',
				array( 'jquery' ),
				'1.0.0',
				true
			);

			if ( AAA_OC_PRODUCTSEARCH_DEBUG ) {
				if ( function_exists( 'aaa_oc_log' ) ) {
					aaa_oc_log( '[PRODUCTSEARCH] Admin assets enqueued on core settings page.' );
				} else {
					error_log( '[PRODUCTSEARCH] Admin assets enqueued on core settings page.' );
				}
			}
		}
	}

	AAA_OC_ProductSearch_Assets_Loader::init();
}
