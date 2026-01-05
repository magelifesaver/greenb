<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_Atum_Bridge {
	private static $allowed = array(
		'purchase_price','supplier_id','supplier_sku','atum_controlled','out_stock_date','out_stock_threshold','inheritable','inbound_stock','stock_on_hold','reserved_stock','sold_today','sales_last_days','customer_returns','warehouse_damage','lost_in_post','other_logs','out_stock_days','lost_sales','has_location','atum_stock_status','restock_status','barcode','calc_backorders','multi_inventory','inventory_iteration','inventory_sorting_mode','expirable_inventories','price_per_inventory','show_write_off_inventories','show_out_of_stock_inventories','selectable_inventories','selectable_inventories_mode','low_stock_threshold_by_inventory','minimum_threshold','available_to_purchase','selling_priority','calculated_stock','is_bom',
	);

	public static function is_active() {
		return class_exists( '\\Atum\\Inc\\Helpers' ) && method_exists( '\\Atum\\Inc\\Helpers', 'get_atum_product' );
	}

	public static function update_product_data( $product_id, $fields ) {
		if ( ! self::is_active() ) {
			return new WP_Error( 'aaa_api_lokey_atum_missing', 'ATUM Inventory is not active.', array( 'status' => 424 ) );
		}

		$product_id   = (int) $product_id;
		$fields       = is_array( $fields ) ? $fields : array();
		$atum_product = \Atum\Inc\Helpers::get_atum_product( $product_id );
		if ( ! $atum_product ) {
			return new WP_Error( 'aaa_api_lokey_invalid_product', 'Invalid product for ATUM.', array( 'status' => 404 ) );
		}

		// Ensure the row exists in the ATUM table.
		$atum_product->save();

		$updated = array();
		$ignored = array();
		$direct  = array();

		foreach ( $fields as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( ! in_array( $key, self::$allowed, true ) ) {
				$ignored[ $key ] = 'not_allowed';
				continue;
			}

			$setter = 'set_' . $key;
			$casted = self::cast_value( $key, $value );
			$updated[ $key ] = $casted;

			if ( method_exists( $atum_product, $setter ) ) {
				$atum_product->{$setter}( $casted );
			} else {
				$direct[ $key ] = $casted;
			}
		}

		if ( ! empty( $updated ) ) {
			$atum_product->save();
		}
		if ( ! empty( $direct ) ) {
			self::direct_update( $product_id, $direct );
		}

		return array( 'updated' => $updated, 'ignored' => $ignored, 'row' => self::read_row( $product_id ) );
	}

	public static function read_row( $product_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'atum_product_data';
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE product_id = %d", (int) $product_id ), ARRAY_A );
		return is_array( $row ) ? $row : array();
	}

	private static function direct_update( $product_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'atum_product_data';
		$wpdb->update( $table, $data, array( 'product_id' => (int) $product_id ) );
	}

	private static function cast_value( $key, $value ) {
		$ints   = array('supplier_id','out_stock_threshold','inbound_stock','stock_on_hold','reserved_stock','sold_today','sales_last_days','customer_returns','warehouse_damage','lost_in_post','other_logs','out_stock_days','lost_sales','inventory_iteration','minimum_threshold','calculated_stock');
		$bools  = array('atum_controlled','inheritable','has_location','multi_inventory','expirable_inventories','price_per_inventory','show_write_off_inventories','show_out_of_stock_inventories','selectable_inventories','low_stock_threshold_by_inventory','available_to_purchase','is_bom');
		$floats = array('purchase_price');

		if ( in_array( $key, $ints, true ) ) {
			return is_numeric( $value ) ? (int) $value : 0;
		}
		if ( in_array( $key, $bools, true ) ) {
			return empty( $value ) ? 0 : 1;
		}
		if ( in_array( $key, $floats, true ) ) {
			return is_numeric( $value ) ? (float) $value : 0.0;
		}

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}
