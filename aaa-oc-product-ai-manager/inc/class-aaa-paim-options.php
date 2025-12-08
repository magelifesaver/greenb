<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-options.php
 * Purpose: Get/Set global PAIM options stored as a single option row.
 * Version: 0.4.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_OPTS' ) ) { define( 'AAA_PAIM_DEBUG_OPTS', true ); }

class AAA_Paim_Options {
	const OPT_KEY = 'aaa_paim_options';

	public static function all() : array {
		$opts = get_option( self::OPT_KEY, [] );
		return is_array( $opts ) ? $opts : [];
	}

	public static function get( string $key, $default = '' ) {
		$opts = self::all();
		return array_key_exists( $key, $opts ) ? $opts[ $key ] : $default;
	}

	public static function set( string $key, $value ) : void {
		$opts = self::all();
		$opts[ $key ] = $value;
		update_option( self::OPT_KEY, $opts, false );
	}

	public static function save_from_post() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'aaa-paim' ) );
		}
		check_admin_referer( 'aaa_paim_save_global', 'aaa_paim_nonce' );

		$enabled = ! empty( $_POST['paim_enabled'] );
		$debug   = ! empty( $_POST['paim_debug'] );
		$key_raw = isset( $_POST['openai_api_key'] ) ? wp_unslash( $_POST['openai_api_key'] ) : '';
		$key     = trim( (string) $key_raw );

		// --- NEW: web search settings ---
		$search_on  = ! empty( $_POST['paim_web_search'] );
		$provider   = isset( $_POST['paim_web_provider'] ) ? sanitize_text_field( wp_unslash( $_POST['paim_web_provider'] ) ) : 'serpapi';
		$search_key = isset( $_POST['paim_web_api_key'] ) ? trim( (string) wp_unslash( $_POST['paim_web_api_key'] ) ) : '';
		$allow_str  = isset( $_POST['paim_web_allow'] ) ? (string) wp_unslash( $_POST['paim_web_allow'] ) : '';

		self::set( 'enabled', $enabled ? 1 : 0 );
		self::set( 'debug',   $debug   ? 1 : 0 );
		// Only update key if provided (lets you keep secret hidden)
		if ( $key !== '' ) {
			self::set( 'openai_api_key', $key );
		}

		// Persist web search settings
		self::set( 'web_search_enabled', $search_on ? 1 : 0 );
		self::set( 'web_search_provider', in_array( $provider, [ 'serpapi', 'bing' ], true ) ? $provider : 'serpapi' );
		if ( $search_key !== '' ) {
			self::set( 'web_search_api_key', $search_key );
		}
		self::set( 'web_search_allow', $allow_str );

		aaa_paim_log( 'Global options saved', 'OPTS' );
	}
}
