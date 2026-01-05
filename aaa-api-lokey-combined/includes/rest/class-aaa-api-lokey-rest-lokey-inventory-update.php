<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_LokeyInventory_Update {
	public static function inventory_update( $request ) {
		$ok = AAA_API_Lokey_REST_LokeyInventory_Common::require_woo();
		if ( is_wp_error( $ok ) ) { return $ok; }

		$id = (int) $request->get_param( 'id' );
		$p  = $id ? wc_get_product( $id ) : null;
		if ( ! $p ) { return new WP_Error( 'aaa_api_lokey_invalid_product', 'Invalid product ID.', array( 'status' => 400 ) ); }
		$payload = (array) $request->get_json_params();

		$has_pp  = array_key_exists( 'purchase_price', $payload ) && null !== $payload['purchase_price'];
		$has_qty = array_key_exists( 'stock_quantity', $payload ) && null !== $payload['stock_quantity'];
		if ( ! $has_pp && ! $has_qty ) {
			return new WP_Error( 'aaa_api_lokey_no_fields', 'No valid fields provided.', array( 'status' => 400 ) );
		}

		$data = array();
		if ( $has_pp ) {
			if ( ! AAA_API_Lokey_Atum_Bridge::is_active() ) {
				return new WP_Error( 'aaa_api_lokey_atum_missing', 'ATUM Inventory is required for purchase_price updates.', array( 'status' => 424 ) );
			}
			$pp = is_numeric( $payload['purchase_price'] ) ? (float) $payload['purchase_price'] : null;
			if ( null === $pp ) { return new WP_Error( 'aaa_api_lokey_bad_purchase_price', 'purchase_price must be a number.', array( 'status' => 400 ) ); }
			$r = AAA_API_Lokey_Atum_Bridge::update_product_data( $id, array( 'purchase_price' => $pp ) );
			if ( is_wp_error( $r ) ) { return $r; }
			$data['purchase_price'] = (float) $pp;
		}
		if ( $has_qty ) {
			$qty = is_numeric( $payload['stock_quantity'] ) ? (int) $payload['stock_quantity'] : null;
			if ( null === $qty ) { return new WP_Error( 'aaa_api_lokey_bad_stock', 'stock_quantity must be an integer.', array( 'status' => 400 ) ); }
			$p->set_manage_stock( true );
			$p->set_stock_quantity( $qty );
			$p->save();
			$data['stock_quantity'] = (int) $qty;
		}

		return rest_ensure_response( AAA_API_Lokey_REST_Extended_Helpers::inv_ok( $id, $data, 'ATUM product data updated successfully.' ) );
	}
}
