<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_Extended_Helpers {
	public static function api_version() {
		return defined( 'AAA_API_LOKEY_API_VERSION' ) ? (string) AAA_API_LOKEY_API_VERSION : (string) AAA_API_LOKEY_VERSION;
	}
	public static function now_mysql() { return current_time( 'mysql' ); }

	public static function ok( $action, $id, $data, $message = null ) {
		$out = array(
			'version'   => self::api_version(),
			'status'    => 'success',
			'action'    => (string) $action,
			'id'        => (int) $id,
			'data'      => $data,
			'timestamp' => self::now_mysql(),
		);
		if ( null !== $message ) { $out['message'] = (string) $message; }
		return $out;
	}

	public static function inv_ok( $id, $data, $message ) {
		return array(
			'version'   => self::api_version(),
			'status'    => 'success',
			'id'        => (int) $id,
			'data'      => $data,
			'message'   => (string) $message,
			'timestamp' => self::now_mysql(),
		);
	}

	public static function normalize_term_items( $items ) {
		$out = array();
		foreach ( (array) $items as $it ) {
			if ( is_array( $it ) ) {
				if ( isset( $it['id'] ) && is_numeric( $it['id'] ) ) { $out[] = (int) $it['id']; continue; }
				if ( ! empty( $it['slug'] ) ) { $out[] = sanitize_title( (string) $it['slug'] ); continue; }
				if ( ! empty( $it['name'] ) ) { $out[] = sanitize_text_field( (string) $it['name'] ); continue; }
				continue;
			}
			if ( is_numeric( $it ) ) { $out[] = (int) $it; continue; }
			$it = trim( (string) $it );
			if ( '' !== $it ) { $out[] = $it; }
		}
		return array_values( array_filter( $out, function ( $v ) { return '' !== (string) $v; } ) );
	}

	public static function taxonomy_from_attribute( $item ) {
		if ( empty( $item['taxonomy'] ) ) { return ''; }
		$id   = isset( $item['id'] ) && is_numeric( $item['id'] ) ? (int) $item['id'] : 0;
		$name = isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '';
		if ( $id && function_exists( 'wc_attribute_taxonomy_name_by_id' ) ) {
			$tax = wc_attribute_taxonomy_name_by_id( $id );
			return is_string( $tax ) ? sanitize_key( $tax ) : '';
		}
		if ( $name && function_exists( 'wc_attribute_taxonomy_name' ) ) { return sanitize_key( wc_attribute_taxonomy_name( $name ) ); }
		return $name ? sanitize_key( 'pa_' . sanitize_title( $name ) ) : '';
	}

	public static function term_ids( $taxonomy, $items, $create_terms ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) { return array(); }
		return AAA_API_Lokey_REST_Products_Helpers::term_ids( $taxonomy, self::normalize_term_items( $items ), (bool) $create_terms );
	}

	public static function merge_set_terms( $product_id, $taxonomy, $items, $create_terms, $merge ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) { return; }
		$new = self::term_ids( $taxonomy, $items, $create_terms );
		if ( $merge ) {
			$cur = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'ids' ) );
			$new = array_values( array_unique( array_merge( (array) $cur, (array) $new ) ) );
		}
		wp_set_object_terms( (int) $product_id, $new, $taxonomy, false );
	}

	public static function normalize_attributes( $items ) {
		$out = array();
		foreach ( (array) $items as $it ) {
			if ( ! is_array( $it ) ) { continue; }
			$opt = isset( $it['options'] ) && is_array( $it['options'] ) ? $it['options'] : array();
			$opt = array_values( array_filter( array_map( 'sanitize_text_field', $opt ) ) );
			$vis = array_key_exists( 'visible', $it ) ? (bool) $it['visible'] : null;
			$var = array_key_exists( 'variation', $it ) ? (bool) $it['variation'] : null;
			$tax = self::taxonomy_from_attribute( $it );
			if ( $tax && taxonomy_exists( $tax ) ) { $out[] = array( 'taxonomy' => $tax, 'options' => $opt, 'visible' => $vis, 'variation' => $var ); continue; }
			$name = isset( $it['name'] ) ? sanitize_text_field( (string) $it['name'] ) : '';
			if ( $name ) { $out[] = array( 'name' => $name, 'options' => $opt, 'visible' => $vis, 'variation' => $var ); }
		}
		return $out;
	}

	public static function merge_apply_attributes( $product, $items, $create_terms ) {
		$items = self::normalize_attributes( $items );
		if ( ! $items ) { return; }
		$existing = $product->get_attributes();
		$max_pos  = 0;
		foreach ( (array) $existing as $ea ) { $max_pos = max( $max_pos, (int) $ea->get_position() ); }
		foreach ( $items as $it ) {
			$key  = ! empty( $it['taxonomy'] ) ? $it['taxonomy'] : $it['name'];
			$attr = isset( $existing[ $key ] ) ? $existing[ $key ] : new WC_Product_Attribute();
			if ( empty( $existing[ $key ] ) ) { $attr->set_position( ++$max_pos ); }
			if ( ! empty( $it['taxonomy'] ) ) {
				$tax = $it['taxonomy'];
				$attr->set_name( $tax );
				$attr->set_id( function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? (int) wc_attribute_taxonomy_id_by_name( substr( $tax, 3 ) ) : 0 );
				$new = AAA_API_Lokey_REST_Products_Helpers::term_ids( $tax, (array) $it['options'], (bool) $create_terms );
				$cur = (array) $attr->get_options();
				$attr->set_options( array_values( array_unique( array_merge( $cur, $new ) ) ) );
			} else {
				$attr->set_name( $it['name'] );
				$cur = (array) $attr->get_options();
				$new = array_values( array_filter( array_map( 'sanitize_text_field', (array) $it['options'] ) ) );
				$attr->set_options( array_values( array_unique( array_merge( $cur, $new ) ) ) );
			}
			if ( null !== $it['visible'] ) { $attr->set_visible( (bool) $it['visible'] ); }
			if ( null !== $it['variation'] ) { $attr->set_variation( (bool) $it['variation'] ); }
			$existing[ $key ] = $attr;
		}
		$product->set_attributes( $existing );
	}

	public static function sale_from_discount( $regular_price, $discount_percent ) {
		if ( ! is_numeric( $regular_price ) || ! is_numeric( $discount_percent ) ) { return ''; }
		$d = max( 0.0, min( 100.0, (float) $discount_percent ) );
		return wc_format_decimal( (float) $regular_price * ( 1.0 - ( $d / 100.0 ) ) );
	}
}
