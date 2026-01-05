<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AAA_API_Lokey_Auth {

	public static function can_access( $request ) {
		if ( is_user_logged_in() && current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$key = self::extract_key( $request );
		if ( ! $key ) {
			return new WP_Error( 'aaa_api_lokey_unauthorized', 'Missing API key.', array( 'status' => 401 ) );
		}

		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		$stored   = isset( $settings['api_key'] ) ? (string) $settings['api_key'] : '';
		if ( $stored && hash_equals( $stored, (string) $key ) ) {
			return true;
		}

		return new WP_Error( 'aaa_api_lokey_forbidden', 'Invalid API key.', array( 'status' => 403 ) );
	}

	private static function extract_key( $request ) {
		if ( ! $request instanceof WP_REST_Request ) {
			return '';
		}

		$auth = $request->get_header( 'authorization' );
		if ( $auth && stripos( $auth, 'bearer ' ) === 0 ) {
			return trim( substr( $auth, 7 ) );
		}

		$h1 = $request->get_header( 'x-aaa-api-key' );
		$h2 = $request->get_header( 'x-lokey-key' );
		if ( $h1 ) {
			return trim( (string) $h1 );
		}
		if ( $h2 ) {
			return trim( (string) $h2 );
		}

		$q = $request->get_param( 'api_key' );
		return $q ? trim( (string) $q ) : '';
	}
}
