<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/customer/aaa-oc-customer-assets-loader.php
 * Purpose: Enqueue Customer module assets (CSS for board borders/pills) + JS (board-customer-inline.js).
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_enqueue_scripts', function () {
	// CSS
	$css = __DIR__ . '/assets/css/board-customer.css';
	// Keep the same base/url approach you’re using
	$css_url = plugins_url( 'customer/assets/css/board-customer.css', dirname( __DIR__, 1 ) . '/aaa-order-workflow.php' );

	if ( file_exists( $css ) ) {
		wp_enqueue_style(
			'aaa-oc-board-customer',
			$css_url,
			array(),
			filemtime( $css )
		);
	}

	// JS (board-customer-inline.js) — Step A modal open/close, no saving yet
	$js = __DIR__ . '/assets/js/board-customer-inline.js';
	$js_url = plugins_url( 'customer/assets/js/board-customer-inline.js', dirname( __DIR__, 1 ) . '/aaa-order-workflow.php' );

	if ( file_exists( $js ) ) {
		wp_enqueue_script(
			'aaa-oc-board-customer-inline',
			$js_url,
			array( 'jquery' ),
			filemtime( $js ),
			true
		);
	}
});
