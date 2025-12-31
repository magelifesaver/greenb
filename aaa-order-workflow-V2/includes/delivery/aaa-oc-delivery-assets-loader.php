<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/delivery/aaa-oc-delivery-assets-loader.php
 * Purpose: Enqueue Delivery module admin assets (board save driver/delivery JS) on the Workflow Board.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Per-file debug toggle */
if ( ! defined( 'AAA_OC_DELIVERY_ASSETS_DEBUG' ) ) {
	define( 'AAA_OC_DELIVERY_ASSETS_DEBUG', false );
}

add_action( 'admin_enqueue_scripts', function () {
	// Load only on the Workflow Board page
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $page !== 'aaa-oc-workflow-board' ) return;

	$base_url = plugin_dir_url( __FILE__ );
	$ver      = defined( 'AAA_OC_VERSION' ) ? AAA_OC_VERSION : '1.0.0';

	// JS: Save driver / save delivery buttons inside the expanded card right pane
	wp_enqueue_script(
		'aaa-oc-delivery-board',
		$base_url . 'assets/js/board-save-driver.js',
		[ 'jquery' ],
		$ver,
		true
	);

	// Primary localization used by the delivery JS
	wp_localize_script( 'aaa-oc-delivery-board', 'AAA_OC_Delivery', [
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'aaa_oc_ajax_nonce' ),
	]);

	// Back-compat shim: the bundled JS referenced AAA_OC_Payment.* in earlier builds.
	// Keep it harmless by aliasing to AAA_OC_Delivery if Payment isn’t present.
	$shim = <<<JS
window.AAA_OC_Payment = window.AAA_OC_Payment || window.AAA_OC_Delivery || {
  ajaxUrl: (window.ajaxurl || (location.origin + '/wp-admin/admin-ajax.php')),
  nonce: ''
};
JS;
	wp_add_inline_script( 'aaa-oc-delivery-board', $shim, 'before' );

	if ( AAA_OC_DELIVERY_ASSETS_DEBUG ) {
		error_log( '[DELIVERY][ASSETS] Enqueued board-save-driver.js on Workflow Board.' );
	}
});
