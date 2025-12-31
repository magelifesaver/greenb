<?php
/**
 * File: /plugins/aaa-order-workflow/includes/payment/aaa-oc-payment-assets-loader.php
 * Purpose: Enqueue payment-related JS assets (calculation, modal, feed) and CSS for board payment UI.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Optional assets (board-only) */
add_action( 'admin_enqueue_scripts', function () {
	$screen   = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$on_board = isset($_GET['page']) && $_GET['page'] === 'aaa-oc-workflow-board'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $screen || strpos( (string) $screen->id, 'aaa-oc' ) === false || ! $on_board ) { return; }
	if ( ! apply_filters( 'aaa_oc_load_module_assets', true, 'payment', [] ) ) { return; }

	// Use core version if present; fall back to module version (kept as-is for compatibility)
	$ver = defined('AAA_OC_VERSION') ? AAA_OC_VERSION : AAA_OC_PAYMENT_VERSION;

	// CSS (new)
	wp_enqueue_style(
		'aaa-oc-payment-css',
		plugin_dir_url(__FILE__) . 'assets/css/board-payment.css',
		[],
		$ver
	);

	// JS (existing)
	wp_enqueue_script(
		'aaa-oc-payment-modal',
		plugin_dir_url(__FILE__) . 'assets/js/board-payment-modal.js',
		[ 'jquery' ],
		$ver,
		true
	);
	wp_enqueue_script(
		'aaa-oc-payment-save',
		plugin_dir_url(__FILE__) . 'assets/js/board-payment-save.js',
		[ 'jquery' ],
		$ver,
		true
	);
	wp_enqueue_script(
		'aaa-oc-payment-calc',
		plugin_dir_url(__FILE__) . 'assets/js/board-payment-calc.js',
		[ 'jquery' ],
		$ver,
		true
	);
}, 10);
