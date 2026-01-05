<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_ExtendedProducts {
	public static function register( $ns ) {
		register_rest_route( $ns, '/products/extended', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'create' ),
			'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
		) );
		register_rest_route( $ns, '/products/extended/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::EDITABLE,
			'callback'            => array( __CLASS__, 'update' ),
			'permission_callback' => array( 'AAA_API_Lokey_Auth', 'can_access' ),
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		) );
	}

	private static function require_woo() {
		return AAA_API_Lokey_REST_LokeyInventory_Common::require_woo();
	}

	public static function create( $request ) {
		$ok = self::require_woo();
		if ( is_wp_error( $ok ) ) { return $ok; }
		$payload = (array) $request->get_json_params();
		$name = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
		if ( '' === $name ) { return new WP_Error( 'aaa_api_lokey_name_required', 'name is required.', array( 'status' => 400 ) ); }

		$type = isset( $payload['type'] ) ? sanitize_key( (string) $payload['type'] ) : 'simple';
		$product = ( 'variable' === $type ) ? new WC_Product_Variable() : new WC_Product_Simple();
		$product->set_name( $name );
		self::apply_base_fields( $product, $payload, true );
		$id = $product->save();
		if ( ! $id ) { return new WP_Error( 'aaa_api_lokey_create_failed', 'Could not create product.', array( 'status' => 500 ) ); }
		self::apply_extended_fields( $product, $payload, false );
		$product->save();
		$data = AAA_API_Lokey_REST_Products_Helpers::format_product( $product, true );
		return rest_ensure_response( AAA_API_Lokey_REST_Extended_Helpers::ok( 'created', $id, $data ) );
	}

	public static function update( $request ) {
		$ok = self::require_woo();
		if ( is_wp_error( $ok ) ) { return $ok; }
		$id = (int) $request->get_param( 'id' );
		$product = $id ? wc_get_product( $id ) : null;
		if ( ! $product ) { return new WP_Error( 'aaa_api_lokey_invalid_product', 'Invalid product ID.', array( 'status' => 400 ) ); }
		$payload = (array) $request->get_json_params();
		self::apply_base_fields( $product, $payload, false );
		self::apply_extended_fields( $product, $payload, true );
		$product->save();
		$data = AAA_API_Lokey_REST_Products_Helpers::format_product( $product, true );
		return rest_ensure_response( AAA_API_Lokey_REST_Extended_Helpers::ok( 'updated', $id, $data ) );
	}

	private static function apply_base_fields( $product, $payload, $is_create ) {
		if ( isset( $payload['sku'] ) ) { $product->set_sku( sanitize_text_field( (string) $payload['sku'] ) ); }
		if ( isset( $payload['status'] ) ) {
			$s = preg_replace( '/^wc-/', '', sanitize_key( (string) $payload['status'] ) );
			if ( $s && 'none' !== $s ) { $product->set_status( $s ); }
		}
		$product->set_manage_stock( true );
		if ( array_key_exists( 'stock_quantity', $payload ) && null !== $payload['stock_quantity'] ) {
			if ( is_numeric( $payload['stock_quantity'] ) ) { $product->set_stock_quantity( (int) $payload['stock_quantity'] ); }
		} elseif ( $is_create ) {
			$product->set_stock_quantity( 0 );
		}
		if ( isset( $payload['regular_price'] ) && is_numeric( $payload['regular_price'] ) ) { $product->set_regular_price( wc_format_decimal( $payload['regular_price'] ) ); }
		if ( isset( $payload['sale_price'] ) && is_numeric( $payload['sale_price'] ) ) { $product->set_sale_price( wc_format_decimal( $payload['sale_price'] ) ); }
		if ( isset( $payload['discount_percent'] ) && ! isset( $payload['sale_price'] ) ) {
			$rp = isset( $payload['regular_price'] ) ? $payload['regular_price'] : $product->get_regular_price();
			$sp = AAA_API_Lokey_REST_Extended_Helpers::sale_from_discount( $rp, $payload['discount_percent'] );
			if ( '' !== $sp ) { $product->set_sale_price( $sp ); }
		}
		if ( isset( $payload['description'] ) ) { $product->set_description( wp_kses_post( (string) $payload['description'] ) ); }
		if ( isset( $payload['short_description'] ) ) { $product->set_short_description( wp_kses_post( (string) $payload['short_description'] ) ); }
	}

	private static function apply_extended_fields( $product, $payload, $merge_terms ) {
		$id = (int) $product->get_id();
		$create_terms = ! empty( $payload['create_terms'] );
		if ( $id && array_key_exists( 'categories', $payload ) ) {
			AAA_API_Lokey_REST_Extended_Helpers::merge_set_terms( $id, 'product_cat', $payload['categories'], $create_terms, $merge_terms );
		}
		$brand_tax = AAA_API_Lokey_REST_LokeyInventory_Common::brand_taxonomy();
		if ( $id && $brand_tax && array_key_exists( 'brands', $payload ) ) {
			AAA_API_Lokey_REST_Extended_Helpers::merge_set_terms( $id, $brand_tax, $payload['brands'], $create_terms, $merge_terms );
		}
		if ( array_key_exists( 'attributes', $payload ) ) {
			AAA_API_Lokey_REST_Extended_Helpers::merge_apply_attributes( $product, $payload['attributes'], $create_terms );
		}
		if ( $id && array_key_exists( 'supplier_id', $payload ) && is_numeric( $payload['supplier_id'] ) && (int) $payload['supplier_id'] > 0 ) {
			$sid = (int) $payload['supplier_id'];
			if ( AAA_API_Lokey_Atum_Bridge::is_active() ) {
				AAA_API_Lokey_Atum_Bridge::update_product_data( $id, array( 'supplier_id' => $sid ) );
			} else {
				update_post_meta( $id, '_supplier_id', $sid );
			}
		}
	}
}
