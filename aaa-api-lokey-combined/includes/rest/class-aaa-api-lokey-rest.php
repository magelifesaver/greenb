<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AAA_API_Lokey_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$settings   = get_option( AAA_API_LOKEY_OPTION, array() );
		$namespaces = isset( $settings['namespaces'] ) && is_array( $settings['namespaces'] ) ? $settings['namespaces'] : array( 'aaa/v1' );
		$namespaces = array_values( array_filter( array_map( 'trim', $namespaces ) ) );
		$namespaces = $namespaces ? $namespaces : array( 'aaa/v1' );

		foreach ( $namespaces as $ns ) {
			register_rest_route(
				$ns,
				'/ping',
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'ping' ),
					'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
				)
			);
			AAA_API_Lokey_REST_Products::register( $ns );
			AAA_API_Lokey_REST_Atum::register( $ns );
			AAA_API_Lokey_REST_Reports::register( $ns );
		}
	}

	public static function ping( $request ) {
		return rest_ensure_response(
			array(
				'ok'     => true,
				'time'   => current_time( 'mysql' ),
				'atum'   => AAA_API_Lokey_Atum_Bridge::is_active(),
				'woo'    => class_exists( 'WooCommerce' ),
				'plugin' => AAA_API_LOKEY_VERSION,
			)
		);
	}
}
