<?php
/**
 * File: /plugins/aaa-order-workflow/includes/core/modules/board/aaa-oc-core-board-assets-loader.php
 * Purpose: Enqueue canonical assets for the Workflow Board (no legacy sidebar bootstrap).
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', function () {
	$screen = function_exists('get_current_screen') ? get_current_screen() : null;
	if ( empty( $screen->id ) || strpos( $screen->id, 'aaa-oc' ) === false ) return;

	$page = isset($_GET['page']) ? sanitize_key($_GET['page']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $page !== 'aaa-oc-workflow-board' ) return;

	$base_dir  = dirname( dirname( __FILE__ ) ); // strip off '/hooks'
	$mod_url = trailingslashit( plugin_dir_url( __FILE__ ) . 'assets' );
	$mod_fs  = trailingslashit( dirname( __FILE__ ) . '/assets' );

	$ver = function( $rel ) use ( $mod_fs ) {
		$file = $mod_fs . ltrim( $rel, '/' );
		return file_exists( $file )
			? (string) filemtime( $file )
			: ( defined('AAA_OC_VERSION') ? AAA_OC_VERSION : '1.0.0' );
	};

	// CSS
	wp_enqueue_style( 'aaa-oc-board-css',         $mod_url . 'css/board.css',           [], $ver('css/board.css') );
	wp_enqueue_style( 'aaa-oc-board-header-fix',  $mod_url . 'css/board-header-fix.css',[], $ver('css/board-header-fix.css') );

	// Canonical toolbar & helpers (no legacy sidebar bootstrap)
	wp_enqueue_script( 'aaa-oc-board-toolbar',        $mod_url . 'js/board-toolbar.js',        [ 'jquery' ], $ver('js/board-toolbar.js'), true );
	wp_enqueue_script( 'aaa-oc-board-actions',        $mod_url . 'js/board-actions.js',        [ 'jquery', 'aaa-oc-board-toolbar' ], $ver('js/board-actions.js'), true );
	wp_enqueue_script( 'aaa-oc-board-filters',        $mod_url . 'js/board-filters.js',        [ 'jquery', 'aaa-oc-board-toolbar' ], $ver('js/board-filters.js'), true );
	wp_enqueue_script( 'aaa-oc-board-hide-completed', $mod_url . 'js/board-hide-completed.js', [ 'jquery', 'aaa-oc-board-toolbar' ], $ver('js/board-hide-completed.js'), true );

	// NOTE: intentionally NOT enqueuing board-sidebar.js or board-sidebar-pref.js anymore
	// (they caused “No existing sidebar root; aborting.” and are superseded by the toolbar shell). 

	// Board runtime
	wp_enqueue_script( 'aaa-oc-board',              $mod_url . 'js/board.js',              [ 'jquery' ], $ver('js/board.js'), true );
	wp_enqueue_script( 'aaa-oc-board-print',        $mod_url . 'js/board-print.js',        [ 'jquery' ], $ver('js/board-print.js'), true );
	wp_enqueue_script( 'aaa-oc-board-header-fix',   $mod_url . 'js/board-header-fix.js',   [ 'jquery', 'aaa-oc-board-toolbar', 'aaa-oc-board-filters', 'aaa-oc-board-actions' ], $ver('js/board-header-fix.js'), true );
	wp_enqueue_script( 'aaa-oc-board-admin-notes',  $mod_url . 'js/board-admin-notes.js',  [ 'jquery' ], $ver('js/board-admin-notes.js'), true );

	// Localize for AJAX + admin edit link base (used by the feed)
	wp_localize_script( 'aaa-oc-board', 'AAA_OC_Vars', [
		'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
		'nonce'          => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
		'showCountdown'  => (int) get_option( 'aaa_oc_show_countdown_bar', 0 ),
		'pollInterval'   => 60,
		'adminEditBase'  => admin_url( 'post.php?action=edit&post=' ),
	] );

	if ( ! defined( 'AAA_OC_BOARD_ASSETS_LOADED' ) ) {
		define( 'AAA_OC_BOARD_ASSETS_LOADED', true );
	}
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[AAA-OC][BoardAssets] Enqueued canonical board assets (no legacy sidebar).' );
	}
}, 5);
