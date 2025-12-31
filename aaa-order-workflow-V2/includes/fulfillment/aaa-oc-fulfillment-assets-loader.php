<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/fulfillment/aaa-oc-fulfillment-assets-loader.php
 * Purpose: Enqueue Fulfillment module JS + CSS on the Workflow Board page only.
 * Loads:
 *   /includes/fulfillment/assets/css/board-fulfillment.css
 *   /includes/fulfillment/assets/js/board-listener.js
 *   /includes/fulfillment/assets/js/board-data-indexer.js
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Per-file debug toggle */
if ( ! defined( 'AAA_OC_FULFILLMENT_ASSETS_DEBUG' ) ) {
	define( 'AAA_OC_FULFILLMENT_ASSETS_DEBUG', true );
}

final class AAA_OC_Fulfillment_Assets {

	public static function init() : void {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
		}
	}

	/**
	 * Only load on Workflow Board (admin.php?page=aaa-oc-workflow-board)
	 */
	public static function enqueue() : void {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page !== 'aaa-oc-workflow-board' ) return;

		// === Base paths ===
		$base_js_dir  = trailingslashit( AAA_OC_PLUGIN_DIR . 'includes/fulfillment/assets/js' );
		$base_js_url  = trailingslashit( AAA_OC_PLUGIN_URL . 'includes/fulfillment/assets/js' );
		$base_css_dir = trailingslashit( AAA_OC_PLUGIN_DIR . 'includes/fulfillment/assets/css' );
		$base_css_url = trailingslashit( AAA_OC_PLUGIN_URL . 'includes/fulfillment/assets/css' );

		// Version helper (filemtime during dev)
		$ver = function( $dir, $rel ) {
			$path = $dir . ltrim( $rel, '/' );
			return file_exists( $path )
				? (string) filemtime( $path )
				: ( defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0.0' );
		};

		// === CSS: Fulfillment board styling ===
		wp_enqueue_style(
			'aaa-oc-fulfillment-css',
			$base_css_url . 'board-fulfillment.css',
			[],
			$ver( $base_css_dir, 'board-fulfillment.css' )
		);

		// === JS: Listener (scanner / modal / picking workflow orchestrator) ===
		wp_enqueue_script(
			'aaa-oc-fulfillment-listener',
			$base_js_url . 'board-listener.js',
			[ 'jquery' ],
			$ver( $base_js_dir, 'board-listener.js' ),
			true
		);

		// === JS: Data indexer (counts, optional record-completion helper) ===
		wp_enqueue_script(
			'aaa-oc-fulfillment-indexer',
			$base_js_url . 'board-data-indexer.js',
			[ 'jquery' ],
			$ver( $base_js_dir, 'board-data-indexer.js' ),
			true
		);

		if ( AAA_OC_FULFILLMENT_ASSETS_DEBUG && function_exists( 'aaa_oc_log' ) ) {
			aaa_oc_log( '[FULFILLMENT][Assets] enqueued board-fulfillment.css, listener.js, and indexer.js' );
		}
	}
}

AAA_OC_Fulfillment_Assets::init();
