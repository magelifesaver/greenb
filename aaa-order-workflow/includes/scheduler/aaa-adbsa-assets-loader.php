<?php
/**
 * File: /wp-content/plugins/aaa-order-workflow/includes/scheduler/aaa-adbsa-assets-loader.php
 * Purpose: Enqueue scripts and styles for Delivery Blocks Scheduler Advanced (multi-mode).
 * Version: 1.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', function() {

	// Only enqueue on checkout
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'aaa_oc_options';
	$sameday_val   = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key='adbsa_options_sameday' LIMIT 1" );
	$scheduled_val = $wpdb->get_var( "SELECT option_value FROM {$table} WHERE option_key='adbsa_options_scheduled' LIMIT 1" );
	$sameday_opts   = maybe_unserialize( $sameday_val );
	$scheduled_opts = maybe_unserialize( $scheduled_val );

	$sameday_method   = $sameday_opts['method_instance_id']   ?? '';
	$scheduled_method = $scheduled_opts['method_instance_id'] ?? '';

	// === Core checkout assets ===
	wp_enqueue_script(
		'adbsa-method-listener',
		plugin_dir_url( __FILE__ ) . 'assets/js/adbsa-method-listener.js',
		[ 'jquery' ],
		'2.3.0',
		true
	);

	wp_enqueue_script(
		'adbsa-sameday',
		plugin_dir_url( __FILE__ ) . 'assets/js/adbsa-sameday.js',
		[ 'adbsa-method-listener' ],
		'1.0.0',
		true
	);

	wp_enqueue_script(
		'adbsa-scheduled',
		plugin_dir_url( __FILE__ ) . 'assets/js/adbsa-scheduled.js',
		[ 'adbsa-method-listener' ],
		'1.0.0',
		true
	);

	wp_enqueue_script(
		'adbsa-summary',
		plugin_dir_url( __FILE__ ) . 'assets/js/adbsa-summary.js',
		[ 'wc-blocks-checkout' ],
		'1.4.1',
		true
	);
	wp_enqueue_script(
		'adbsa-slot-refresh',
		plugin_dir_url( __FILE__ ) . 'assets/js/adbsa-slot-refresh.js',
		[ 'adbsa-method-listener' ],
		'1.0.0',
		true
	);


	// Pass PHP values to JS
	wp_localize_script(
		'adbsa-method-listener',
		'adbsaSettings',
		[
			'sameDayMethod'   => $sameday_method,
			'scheduledMethod' => $scheduled_method,
		]
	);

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log('[ADBSA][Enqueue] Method listener + sameday/scheduled scripts loaded. SD=' . $sameday_method . ' SC=' . $scheduled_method);
	}
});
