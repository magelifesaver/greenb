<?php
/**
 * File: /wp-content/plugins/aaa-product-ai-manager/inc/class-aaa-paim-product.php
 * Purpose: Read/write product values for a PAIM set, create products, create missing terms.
 * Version: 0.3.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! defined( 'AAA_PAIM_DEBUG_PROD' ) ) { define( 'AAA_PAIM_DEBUG_PROD', true ); }

class AAA_Paim_Product {

	public static function get_set_items( int $set_id ) : array {
		global $wpdb;
		$t = $wpdb->prefix . 'aaa_paim_set_attributes';
		$sql = $wpdb->prepare(
			"SELECT object_type, object_key, label, ui_order FROM {$t} WHERE set_id=%d ORDER BY ui_order ASC, id ASC", $set_id
		);
		return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
	}

	public static function get_product_values( int $product_id, array $items ) : array {
		$out = [];
		foreach ( $items as $it ) {
			$key = $it['object_key'];
			if ( 'taxonomy' === $it['object_type'] ) {
				$terms = wp_get_object_terms( $product_id, $key, [ 'fields' => 'ids' ] );
				$out[ $key ] = is_wp_error( $terms ) ? [] : array_map( 'absint', (array) $terms );
			} else {
				$out[ $key ] = get_post_meta( $product_id, $key, true );
			}
		}
		return $out;
	}

	public static function get_ai_flags( int $product_id, int $set_id ) : array {
		global $wpdb;
		$t = $wpdb->prefix . 'aaa_paim_product_ai_flags';
		$sql = $wpdb->prepare(
			"SELECT object_type, object_key, ai_requested FROM {$t} WHERE product_id=%d AND set_id=%d",
			$product_id, $set_id
		);
		$rows = $wpdb->get_results( $sql, ARRAY_A ) ?: [];
		$out = [];
		foreach ( $rows as $r ) { $out[ $r['object_type'] . ':' . $r['object_key'] ] = (int) $r['ai_requested']; }
		return $out;
	}

	public static function set_ai_flag( int $product_id, int $set_id, string $type, string $key, bool $on ) : void {
		global $wpdb;
		$t = $wpdb->prefix . 'aaa_paim_product_ai_flags';
		$wpdb->replace( $t, [
			'product_id'   => $product_id,
			'set_id'       => $set_id,
			'object_type'  => sanitize_key( $type ),
			'object_key'   => sanitize_key( $key ),
			'ai_requested' => $on ? 1 : 0,
			'updated_at'   => current_time( 'mysql' ),
		] );
	}

	private static function validate_submission_all_filled( array $items, array $tax, array $meta, array $ai ) {
		$errors = [];
		foreach ( $items as $it ) {
			$type = $it['object_type']; $key = $it['object_key']; $ai_key = "{$type}:{$key}";
			$ai_on = ! empty( $ai[ $ai_key ] );
			if ( 'taxonomy' === $type ) {
				$vals = array_filter( array_map( 'absint', (array) ( $tax[ $key ] ?? [] ) ) );
				if ( empty( $vals ) && ! $ai_on ) { $errors[] = "Missing terms for taxonomy {$key} (and AI not requested)."; }
			} else {
				$val = isset( $meta[ $key ] ) ? ( is_scalar( $meta[ $key ] ) ? trim( (string) $meta[ $key ] ) : '' ) : '';
				if ( $val === '' && ! $ai_on ) { $errors[] = "Missing value for meta {$key} (and AI not requested)."; }
			}
		}
		return empty( $errors ) ? true : new WP_Error( 'paim_missing', implode( ' ', $errors ) );
	}

	private static function attach_set_to_product( int $product_id, int $set_id ) : void {
		update_post_meta( $product_id, '_paim_attribute_set_id', $set_id );
	}

	/** Create non-empty terms and return their IDs. Accepts array of names. */
	public static function ensure_terms( string $taxonomy, array $names ) : array {
		$ids = [];
		if ( ! taxonomy_exists( $taxonomy ) ) { return $ids; }
		foreach ( $names as $raw ) {
			$name = trim( wp_strip_all_tags( (string) $raw ) );
			if ( $name === '' ) { continue; }
			$term = term_exists( $name, $taxonomy );
			if ( 0 === $term || null === $term ) {
				$insert = wp_insert_term( $name, $taxonomy );
				if ( ! is_wp_error( $insert ) ) { $term = $insert; }
			}
			if ( is_array( $term ) && isset( $term['term_id'] ) ) { $ids[] = (int) $term['term_id']; }
		}
		return array_values( array_unique( $ids ) );
	}

	/** Create a simple product and return its ID (or WP_Error). */
	public static function create_simple_product( array $fields ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return new WP_Error( 'woocommerce_missing', 'WooCommerce is required.' );
		}
		$title      = sanitize_text_field( $fields['name'] ?? '' );
		if ( $title === '' ) { return new WP_Error( 'missing_name', 'Product name is required.' ); }

		$sku        = sanitize_text_field( $fields['sku'] ?? '' );
		$price      = (float) ( $fields['price'] ?? 0 );
		$sale_price = ( $fields['sale_price'] !== '' ) ? (float) $fields['sale_price'] : '';
		$cogs       = sanitize_text_field( $fields['cogs'] ?? '' ); // stored as text; your COGS plugin may parse/format
		$manage     = ! empty( $fields['manage_stock'] );
		$stock_qty  = isset( $fields['stock_qty'] ) ? (int) $fields['stock_qty'] : 0;

		$post_id = wp_insert_post( [
			'post_type'   => 'product',
			'post_status' => 'draft',
			'post_title'  => $title,
		], true );
		if ( is_wp_error( $post_id ) ) { return $post_id; }

		// Core Woo meta
		update_post_meta( $post_id, '_sku', $sku );
		update_post_meta( $post_id, '_regular_price', $price );
		if ( $sale_price !== '' ) { update_post_meta( $post_id, '_sale_price', $sale_price ); }
		update_post_meta( $post_id, '_price', ($sale_price !== '' ? $sale_price : $price) );

		// COGS (per your setup memory)
		update_post_meta( $post_id, '_cogs_total_value', $cogs );

		// Stock
		update_post_meta( $post_id, '_manage_stock', $manage ? 'yes' : 'no' );
		if ( $manage ) { update_post_meta( $post_id, '_stock', max(0,$stock_qty) ); }

		// Product type simple
		wp_set_object_terms( $post_id, 'simple', 'product_type' );

		aaa_paim_log( [ 'created_product' => $post_id ], 'PROD' );
		return $post_id;
	}

	public static function save_submission( int $product_id, int $set_id, array $payload, bool $enforce_all = true ) {
		$items = self::get_set_items( $set_id );
		$tax   = (array) ( $payload['tax']        ?? [] );
		$meta  = (array) ( $payload['meta']       ?? [] );
		$ai    = (array) ( $payload['ai']         ?? [] );
		$newt  = (array) ( $payload['new_terms']  ?? [] ); // taxonomy => "term1, term2"

		// Create any requested new terms and merge with chosen term IDs.
		foreach ( $newt as $taxonomy => $list ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$names = array_filter( array_map( 'trim', explode( ',', (string) $list ) ) );
			if ( $names ) {
				$created_ids = self::ensure_terms( $taxonomy, $names );
				$existing    = array_filter( array_map( 'absint', (array) ( $tax[ $taxonomy ] ?? [] ) ) );
				$tax[ $taxonomy ] = array_values( array_unique( array_merge( $existing, $created_ids ) ) );
			}
		}

		// Enforce: every attribute has a value OR AI requested (manual saves only).
		if ( $enforce_all ) {
			$check = self::validate_submission_all_filled( $items, $tax, $meta, $ai );
			if ( is_wp_error( $check ) ) {
				aaa_paim_log( [ 'save_blocked' => $check->get_error_message(), 'product' => $product_id, 'set' => $set_id ], 'PROD' );
				return $check;
			}
		}

		// 1) Apply taxonomy terms to the object.
		foreach ( $tax as $taxonomy => $ids ) {
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$ids = array_filter( array_map( 'absint', (array) $ids ) );
			wp_set_object_terms( $product_id, $ids, $taxonomy, false );
		}

		// 2) Reflect in Woo Product Attributes (taxonomy, visible, not variation).
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$current = $product->get_attributes(); $updated = [];
				foreach ( $current as $k => $attr_obj ) {
					$tax_name = $attr_obj->get_name();
					if ( ! isset( $tax[ $tax_name ] ) ) { $updated[ $k ] = $attr_obj; }
				}
				foreach ( $tax as $taxonomy => $ids ) {
					if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
					$ids = array_filter( array_map( 'absint', (array) $ids ) );
				$flags = AAA_Paim_AttrMeta::flags( $taxonomy ); // ['visible'=>bool,'variation'=>bool]
				$attr  = new WC_Product_Attribute();
				$attr->set_name( $taxonomy );
				$attr->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
				$attr->set_options( $ids );
				$attr->set_visible( isset($flags['visible']) ? (bool)$flags['visible'] : true );
				$attr->set_variation( isset($flags['variation']) ? (bool)$flags['variation'] : false );
				$updated[ $taxonomy ] = $attr;
				}
				$product->set_attributes( $updated );
				$product->save();
			}
		}

		// 3) Apply meta.
		foreach ( $meta as $k => $v ) {
			$k = sanitize_key( $k );
			update_post_meta( $product_id, $k, is_array( $v ) ? wp_json_encode( $v ) : sanitize_text_field( $v ) );
		}

		// 4) Store AI flags.
		foreach ( $ai as $combo => $on ) {
			$parts = explode( ':', (string) $combo, 2 );
			if ( 2 === count( $parts ) ) { self::set_ai_flag( $product_id, $set_id, $parts[0], $parts[1], (bool) $on ); }
		}

		// 5) Link set to product.
		self::attach_set_to_product( $product_id, $set_id );

		aaa_paim_log( [ 'saved_product' => $product_id, 'set' => $set_id ], 'PROD' );
		return true;
	}
}
