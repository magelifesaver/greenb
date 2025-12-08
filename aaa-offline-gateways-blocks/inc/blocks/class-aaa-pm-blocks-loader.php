<?php
/**
 * Blocks bootstrap & registration (network-safe)
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/inc/blocks/class-aaa-pm-blocks-loader.php
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Register our Blocks JS (safe on init) */
add_action( 'init', function() {
	wp_register_script(
		'aaa-pm-blocks',
		AAA_OGB_URL . 'assets/js/blocks/aaa-pm-blocks.js',
		array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-data', 'wc-blocks-checkout' ),
		AAA_OGB_VER,
		true
	);
	wp_localize_script( 'aaa-pm-blocks', 'AAA_OGB_AJAX', array(
		'url'   => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'aaa_pm_tip_nonce' ),
	) );
	aaa_pm_log('[Blocks] script registered');
} );

/**
 * Register each payment method integration with Woo Blocks.
 * Guard against missing Blocks classes to avoid fatals in Network Admin.
 */
add_action( 'woocommerce_blocks_payment_method_type_registration', function( $registry ) {
	if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		aaa_pm_log('[Blocks] AbstractPaymentMethodType missing; skip registration');
		return;
	}
	// Load integration class only when the Blocks base class exists.
	require_once AAA_OGB_DIR . 'inc/blocks/integration/class-aaa-pm-blocks-generic.php';

	$ids = array( 'pay_with_zelle','pay_with_venmo','pay_with_applepay','pay_with_creditcard','pay_with_cashapp','pay_with_cod' );
	foreach ( $ids as $id ) {
		try {
			$registry->register( new AAA_PM_Blocks_Generic( $id ) );
			aaa_pm_log('[Blocks] registered integration: ' . $id);
		} catch ( \Throwable $e ) {
			aaa_pm_log('[Blocks] ERROR registering ' . $id . ': ' . $e->getMessage() );
		}
	}
} );
