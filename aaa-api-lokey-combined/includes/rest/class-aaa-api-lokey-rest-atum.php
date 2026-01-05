<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_Atum {
	public static function register( $ns ) {
		register_rest_route( $ns, '/products/(?P<id>\d+)/atum', array(
			array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array(__CLASS__,'update'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
			array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'get_row'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
		) );
	}

	public static function update( $request ) {
		$id = (int) $request['id'];
		$body = $request->get_json_params();
		$fields = array();
		if ( is_array( $body ) && isset( $body['atum'] ) && is_array( $body['atum'] ) ) {
			$fields = $body['atum'];
		} elseif ( is_array( $body ) ) {
			$fields = $body;
		} else {
			$fields = (array) $request->get_params();
			unset( $fields['id'] );
		}
		$res = AAA_API_Lokey_Atum_Bridge::update_product_data( $id, $fields );
		if ( is_wp_error( $res ) ) { return $res; }
		return rest_ensure_response( array('product_id'=>$id) + $res );
	}

	public static function get_row( $request ) {
		$id = (int) $request['id'];
		if ( ! AAA_API_Lokey_Atum_Bridge::is_active() ) {
			return new WP_Error( 'aaa_api_lokey_atum_missing', 'ATUM Inventory is not active.', array( 'status' => 424 ) );
		}
		return rest_ensure_response( array('product_id'=>$id,'row'=>AAA_API_Lokey_Atum_Bridge::read_row( $id )) );
	}
}
