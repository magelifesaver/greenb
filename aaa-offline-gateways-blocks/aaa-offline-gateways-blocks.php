<?php
/**
 * Plugin Name: A Offline Gateways (Blocks Compatible)(live)
 * Description: Offline gateways (Zelle, Venmo, ApplePay, Credit Card, CashApp, COD) with Checkout Blocks + tipping.
 * Version: 1.4.2
 * Author: Webmaster Delivery
 * Text Domain: aaa-offline-gateways-blocks
 *
 * File: /wp-content/plugins/aaa-offline-gateways-blocks/aaa-offline-gateways-blocks.php
 * File Version: 1.4.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'AAA_OGB_VER', '1.4.2' );
define( 'AAA_OGB_DIR', plugin_dir_path( __FILE__ ) );
define( 'AAA_OGB_URL', plugin_dir_url( __FILE__ ) );

require_once AAA_OGB_DIR . 'inc/class-aaa-pm-logger.php';
require_once AAA_OGB_DIR . 'inc/class-aaa-pm-tips.php';
require_once AAA_OGB_DIR . 'inc/class-aaa-pm-ajax.php';
require_once AAA_OGB_DIR . 'inc/class-aaa-ogb-extension-cart.php'; // Store API tip updater
require_once AAA_OGB_DIR . 'inc/class-aaa-ogb-fees.php';
require_once AAA_OGB_DIR . 'inc/class-aaa-pm-offline-reference.php';
//require_once AAA_OGB_DIR . 'inc/class-aaa-ogb-forward-tip.php';

aaa_pm_log('Loader start: aaa-offline-gateways-blocks');

/** Network-safe: load WC gateway classes only after WooCommerce is available */
add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		if ( is_admin() ) {
			$hook = is_network_admin() ? 'network_admin_notices' : 'admin_notices';
			add_action( $hook, function() {
				if ( current_user_can( 'activate_plugins' ) ) {
					echo '<div class="notice notice-warning"><p><strong>AAA Offline Gateways:</strong> WooCommerce must be active on this site to load payment gateways.</p></div>';
				}
			} );
		}
		return;
	}
	// Safe to load WC-dependent classes now.
	require_once AAA_OGB_DIR . 'gateways/base/class-aaa-pm-base-gateway.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-zelle.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-venmo.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-applepay.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-creditcard.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-cashapp.php';
	require_once AAA_OGB_DIR . 'gateways/class-aaa-pm-cod.php';

	add_filter( 'woocommerce_payment_gateways', function( $m ) {
		$m[] = 'AAA_PM_Gateway_Zelle';
		$m[] = 'AAA_PM_Gateway_Venmo';
		$m[] = 'AAA_PM_Gateway_ApplePay';
		$m[] = 'AAA_PM_Gateway_CreditCard';
		$m[] = 'AAA_PM_Gateway_CashApp';
		$m[] = 'AAA_PM_Gateway_COD';
		return $m;
	} );
}, 11 );

/** Defer Blocks loader until WooCommerce Blocks is available */
add_action( 'woocommerce_blocks_loaded', function() {
	require_once AAA_OGB_DIR . 'inc/blocks/class-aaa-pm-blocks-loader.php';
} );

/** Classic checkout helper JS & CSS */
add_action( 'wp_enqueue_scripts', function() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		wp_enqueue_script(
			'aaa-pm-classic-tip',
			AAA_OGB_URL . 'assets/js/classic/aaa-pm-classic-tip.js',
			array( 'jquery' ),
			AAA_OGB_VER,
			true
		);
		wp_enqueue_style(
			'aaa-pm-checkout',
			AAA_OGB_URL . 'assets/css/classic/aaa-pm-classic-tip.css',
			array(),
			AAA_OGB_VER
		);
	}
} );

/** Store API: save custom text fields for Blocks (tip handled elsewhere) */
add_action( 'woocommerce_store_api_checkout_update_order_meta', function( $order, $request = null ) {
	if ( $request && method_exists( $request, 'get_json_params' ) ) {
		$d   = (array) $request->get_json_params();
		$pm  = $d['payment_method'] ?? '';
		$all = is_array( $d['payment_data'] ?? null ) ? $d['payment_data'] : array();
		$p   = $all[ $pm ] ?? array();

		foreach ( array(
			'payment_text_field_1' => '_payment_text_field_1',
			'payment_text_field_2' => '_payment_text_field_2',
			'payment_text_area'    => '_payment_text_area',
		) as $k => $meta ) {
			if ( isset( $p[ $k ] ) ) {
				$val = ( $k === 'payment_text_area' ) ? sanitize_textarea_field( $p[ $k ] ) : sanitize_text_field( $p[ $k ] );
				update_post_meta( $order->get_id(), $meta, $val );
			}
		}
	} else {
		aaa_pm_log( 'Store API meta hook without request (compat).' );
	}
}, 5, 2 );
