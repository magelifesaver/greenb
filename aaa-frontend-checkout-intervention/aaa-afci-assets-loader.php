<?php
/**
 * File: /aaa-frontend-checkout-intervention/aaa-afci-assets-loader.php
 * Purpose: Registers/enqueues admin + frontend assets for AFCI.
 * Version: 1.4.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin CSS/JS (only on AFCI pages)
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
	// Our main page hook contains the slug "aaa-afci-settings"
	if ( strpos( $hook, 'aaa-afci-settings' ) === false && strpos( $hook, 'aaa-afci' ) === false ) {
		return;
	}

	if ( function_exists( 'aaa_fci_debug_log' ) ) {
		aaa_fci_debug_log( 'Enqueue admin assets', [ 'hook' => $hook ] );
	}

	// CSS
	wp_enqueue_style(
		'aaa-fci-admin',
		AAA_FCI_URL . 'assets/css/aaa-afci-admin.css',
		[],
		AAA_FCI_VERSION
	);

	// JS
	wp_enqueue_script(
		'aaa-afci-admin',
		AAA_FCI_URL . 'assets/js/aaa-afci-admin.js',
		[ 'jquery' ],
		AAA_FCI_VERSION,
		true
	);

	wp_localize_script( 'aaa-afci-admin', 'afciAdmin', [
		'ajax'   => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'afci_admin' ),
		'debug'  => function_exists( 'aaa_fci_debug_enabled' ) && aaa_fci_debug_enabled() ? 1 : 0,
	]);
});

/**
 * (Optional) Frontend JS registration here if you want to override or extend
 * the logger—kept minimal because we inject the logger in logger-core.php.
 */
