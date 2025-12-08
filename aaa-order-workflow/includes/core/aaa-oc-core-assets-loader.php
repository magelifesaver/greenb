<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/core/aaa-oc-core-assets-loader.php
 * Purpose: Fallback loader for core board assets (toolbar, sidebar, prefs, actions, filters, hide-completed).
 *          It auto-skips when the canonical board assets loader has already run.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_OC_CORE_ASSETS_DEBUG' ) ) { define( 'AAA_OC_CORE_ASSETS_DEBUG', true ); }

class AAA_OC_Core_Assets {

	public static function init() {
		if ( is_admin() ) {
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ], 10 );
		}
	}

	/**
	 * Load only on the Workflow Board page. Skip if canonical board assets already ran.
	 */
	public static function enqueue( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		if ( $page !== 'aaa-oc-workflow-board' ) {
			return;
		}

		// If the module's canonical loader already ran, skip to avoid duplication.
		if ( defined( 'AAA_OC_BOARD_ASSETS_LOADED' ) && AAA_OC_BOARD_ASSETS_LOADED ) {
			if ( AAA_OC_CORE_ASSETS_DEBUG ) {
				error_log( '[Core][Assets] Skipping (canonical board assets already enqueued).' );
			}
			return;
		}

		// Base URLs to /includes/core/assets/
		$base_js  = plugins_url( 'assets/js/',  __FILE__ );
		$base_css = plugins_url( 'assets/css/', __FILE__ );

		// New split files (sidebar + prefs now enabled)
	//	$js_sidebar      = $base_js . 'board-sidebar.js';
	//	$js_sidebar_pref = $base_js . 'board-sidebar-pref.js';
		$js_toolbar      = $base_js . 'board-toolbar.js';

		// Helpers (must run after toolbar shell)
		$js_actions      = $base_js . 'board-actions.js';
		$js_filters      = $base_js . 'board-filters.js';
		$js_hide_done    = $base_js . 'board-hide-completed.js';

		// Enqueue
		wp_enqueue_script( 'aaa-oc-board-toolbar',        $js_toolbar,      [ 'jquery' ], '1.4.2', true );
	//	wp_enqueue_script( 'aaa-oc-board-sidebar',        $js_sidebar,      [ 'jquery', 'aaa-oc-board-toolbar' ], '1.0.1', true );
	//	wp_enqueue_script( 'aaa-oc-board-sidebar-pref',   $js_sidebar_pref, [ 'aaa-oc-board-sidebar' ], '1.0.0', true );

		wp_enqueue_script( 'aaa-oc-board-actions',        $js_actions,      [ 'jquery', 'aaa-oc-board-toolbar' ], '1.0.0', true );
		wp_enqueue_script( 'aaa-oc-board-filters',        $js_filters,      [ 'jquery', 'aaa-oc-board-toolbar' ], '1.0.0', true );
		wp_enqueue_script( 'aaa-oc-board-hide-completed', $js_hide_done,    [ 'jquery', 'aaa-oc-board-toolbar' ], '1.0.0', true );

		// Optional CSS hook (reserved)
		// $css_board = $base_css . 'board-core.css';
		// wp_enqueue_style( 'aaa-oc-board-core', $css_board, [], '1.0.0' );

		wp_localize_script( 'aaa-oc-board-toolbar', 'AAA_OC_Core', [ 'boardPage' => $page ] );

		if ( AAA_OC_CORE_ASSETS_DEBUG ) {
			error_log( '[Core][Assets] Fallback enqueued from /includes/core/assets (toolbar + sidebar + helpers).' );
		}
	}
}

AAA_OC_Core_Assets::init();
