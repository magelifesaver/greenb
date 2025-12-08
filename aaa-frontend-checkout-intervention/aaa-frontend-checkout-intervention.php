<?php
/**
 * Plugin Name: AAA Frontend Checkout Intervention
 * Description: Logs and monitors WooCommerce checkout sessions, login events, and frontend user actions.
 * Version: 1.4.0
 * Author: Webmaster Workflow
 * Text Domain: aaa-fci
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Base defines (guarded)
if ( ! defined( 'AAA_FCI_PATH' ) )    define( 'AAA_FCI_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'AAA_FCI_URL' ) )     define( 'AAA_FCI_URL',  plugin_dir_url( __FILE__ ) );
if ( ! defined( 'AAA_FCI_VERSION' ) ) define( 'AAA_FCI_VERSION', '1.4.0' );

// Toggleable debug (constant + filter)
if ( ! defined( 'AAA_FCI_DEBUG' ) ) {
	define( 'AAA_FCI_DEBUG', false ); // Set to true to enable extra logging.
}

if ( ! function_exists( 'aaa_fci_debug_enabled' ) ) {
	/**
	 * Whether debug mode is enabled.
	 */
	function aaa_fci_debug_enabled() {
		$enabled = defined( 'AAA_FCI_DEBUG' ) && AAA_FCI_DEBUG;
		// Allow override via filter.
		return (bool) apply_filters( 'aaa_fci_debug_enabled', $enabled );
	}
}

if ( ! function_exists( 'aaa_fci_debug_log' ) ) {
	/**
	 * Lightweight debug logger (error_log) — no-op if debug disabled.
	 *
	 * @param mixed $message
	 * @param array|string $context
	 */
	function aaa_fci_debug_log( $message, $context = [] ) {
		if ( ! aaa_fci_debug_enabled() ) return;
		if ( ! is_scalar( $message ) ) $message = print_r( $message, true ); // phpcs:ignore
		if ( ! empty( $context ) ) {
			if ( ! is_string( $context ) ) $context = wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
			$message .= ' | ' . $context;
		}
		@error_log( '[AFCI:DEBUG] ' . $message );
	}
}

// Load subsystems
require_once AAA_FCI_PATH . 'aaa-afci-loader.php';
require_once AAA_FCI_PATH . 'aaa-afci-assets-loader.php';

register_activation_hook( __FILE__, function() {
	@error_log( sprintf(
		'[AFCI] Plugin activated v%s (debug=%s) at %s',
		AAA_FCI_VERSION,
		aaa_fci_debug_enabled() ? 'on' : 'off',
		current_time( 'mysql' )
	) );
} );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links){
    $settings = '<a href="' . esc_url(admin_url('admin.php?page=aaa-afci-settings')) . '">Settings</a>';
    array_unshift($links, $settings);
    return $links;
});

