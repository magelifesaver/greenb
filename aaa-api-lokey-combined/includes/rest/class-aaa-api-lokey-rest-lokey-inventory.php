<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_LokeyInventory {
	public static function register( $ns ) {
		register_rest_route( $ns, '/diagnostics', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( 'AAA_API_Lokey_REST_LokeyInventory_Diagnostics', 'diagnostics' ),
			'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
		) );
		register_rest_route( $ns, '/inventory', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( 'AAA_API_Lokey_REST_LokeyInventory_List', 'inventory_list' ),
			'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
		) );
		register_rest_route( $ns, '/inventory/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( 'AAA_API_Lokey_REST_LokeyInventory_Update', 'inventory_update' ),
			'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );
	}
}
