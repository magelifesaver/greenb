<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_Products {
	public static function register( $ns ) {
		register_rest_route( $ns, '/products', array(
			array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'list_items'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
			array('methods'=>WP_REST_Server::CREATABLE,'callback'=>array(__CLASS__,'create'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
		) );
		register_rest_route( $ns, '/products/(?P<id>\d+)', array(
			array('methods'=>WP_REST_Server::READABLE,'callback'=>array(__CLASS__,'get_one'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
			array('methods'=>WP_REST_Server::EDITABLE,'callback'=>array(__CLASS__,'update'),'permission_callback'=>array('AAA_API_Lokey_Auth','can_access')),
		) );
	}

	private static function require_woo() {
		return class_exists( 'WooCommerce' ) ? true : new WP_Error( 'aaa_api_lokey_woo_missing', 'WooCommerce is required.', array( 'status' => 424 ) );
	}

	public static function create( $request ) {
		$r = self::require_woo();
		if ( is_wp_error( $r ) ) { return $r; }
		$name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		if ( ! $name ) { return new WP_Error('aaa_api_lokey_missing_name','Product name is required.',array('status'=>400)); }
		$type = sanitize_key( (string) $request->get_param( 'type' ) );
		$p = ( 'variable' === $type ) ? new WC_Product_Variable() : new WC_Product_Simple();
		$p->set_name( $name );
		if ( ($st = sanitize_key( (string) $request->get_param( 'status' ) )) && 'none' !== $st ) { $p->set_status( $st ); }
		if ( $sku = sanitize_text_field( (string) $request->get_param( 'sku' ) ) ) { $p->set_sku( $sku ); }
		if ( null !== $request->get_param( 'regular_price' ) ) { $p->set_regular_price( wc_format_decimal( $request->get_param( 'regular_price' ) ) ); }
		if ( null !== $request->get_param( 'sale_price' ) ) { $p->set_sale_price( wc_format_decimal( $request->get_param( 'sale_price' ) ) ); }
		if ( null !== $request->get_param( 'manage_stock' ) ) { $p->set_manage_stock( (bool) $request->get_param( 'manage_stock' ) ); }
		if ( null !== $request->get_param( 'stock_quantity' ) ) { $p->set_stock_quantity( (int) $request->get_param( 'stock_quantity' ) ); }
		if ( null !== $request->get_param( 'stock_status' ) ) { $p->set_stock_status( sanitize_key( (string) $request->get_param( 'stock_status' ) ) ); }
		$id = $p->save();
		if ( AAA_API_Lokey_Atum_Bridge::is_active() && $request->get_param( 'init_atum' ) !== false ) { AAA_API_Lokey_Atum_Bridge::update_product_data( $id, array() ); }
		$product = wc_get_product( $id );
		return rest_ensure_response( array('product_id'=>$id,'product'=>AAA_API_Lokey_REST_Products_Helpers::format_product( $product, $request->get_param('include_atum') )) );
	}

	public static function update( $request ) {
		$r = self::require_woo();
		if ( is_wp_error( $r ) ) { return $r; }
		$id = (int) $request['id'];
		$p  = wc_get_product( $id );
		if ( ! $p ) { return new WP_Error('aaa_api_lokey_not_found','Product not found.',array('status'=>404)); }
		AAA_API_Lokey_REST_Products_Helpers::apply_product_fields( $p, $request );
		AAA_API_Lokey_REST_Products_Helpers::apply_taxonomies( $p, $request );
		AAA_API_Lokey_REST_Products_Helpers::apply_attributes( $p, $request );
		$p->save();
		return rest_ensure_response( array('product_id'=>$id,'product'=>AAA_API_Lokey_REST_Products_Helpers::format_product( $p, $request->get_param('include_atum') )) );
	}

	public static function get_one( $request ) {
		$r = self::require_woo();
		if ( is_wp_error( $r ) ) { return $r; }
		$p = wc_get_product( (int) $request['id'] );
		if ( ! $p ) { return new WP_Error('aaa_api_lokey_not_found','Product not found.',array('status'=>404)); }
		return rest_ensure_response( AAA_API_Lokey_REST_Products_Helpers::format_product( $p, $request->get_param('include_atum') ) );
	}

	public static function list_items( $request ) {
		$r = self::require_woo();
		if ( is_wp_error( $r ) ) { return $r; }
		$pp = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );
		$pg = max( 1, (int) $request->get_param( 'page' ) );
		$wq = AAA_API_Lokey_REST_Products_Helpers::query_products( $request, $pp, $pg );
		if ( is_wp_error( $wq ) ) { return $wq; }
		$items = array();
		foreach ( $wq->posts as $post ) {
			$p = wc_get_product( $post->ID );
			if ( $p ) { $items[] = AAA_API_Lokey_REST_Products_Helpers::format_product( $p, $request->get_param('include_atum') ); }
		}
		return rest_ensure_response( array('items'=>$items,'pagination'=>array('page'=>$pg,'per_page'=>$pp,'total'=>(int)$wq->found_posts,'total_pages'=>(int)$wq->max_num_pages)) );
	}
}
