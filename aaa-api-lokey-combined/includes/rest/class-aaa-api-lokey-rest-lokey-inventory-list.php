<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_LokeyInventory_List {
	public static function inventory_list( $request ) {
		$ok = AAA_API_Lokey_REST_LokeyInventory_Common::require_woo();
		if ( is_wp_error( $ok ) ) { return $ok; }

		$page = max( 1, (int) $request->get_param( 'page' ) );
		$pp   = max( 1, min( 200, (int) $request->get_param( 'per_page' ) ) );
		$category = $request->get_param( 'category' );
		$brand    = $request->get_param( 'brand' );
		$supplier = $request->get_param( 'supplier' );
		$stock    = sanitize_key( (string) $request->get_param( 'stock_status' ) );

		$args = array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => $pp,
			'paged'          => $page,
			'fields'         => 'ids',
		);
		$tax = array();
		if ( $category ) { $tax[] = array( 'taxonomy' => 'product_cat', 'field' => is_numeric( $category ) ? 'term_id' : 'slug', 'terms' => is_numeric( $category ) ? (int) $category : sanitize_title( $category ) ); }
		$brand_tax = AAA_API_Lokey_REST_LokeyInventory_Common::brand_taxonomy();
		if ( $brand && $brand_tax ) { $tax[] = array( 'taxonomy' => $brand_tax, 'field' => is_numeric( $brand ) ? 'term_id' : 'slug', 'terms' => is_numeric( $brand ) ? (int) $brand : sanitize_title( $brand ) ); }
		if ( $tax ) { $args['tax_query'] = $tax; }
		if ( $stock ) { $args['meta_query'] = array( array( 'key' => '_stock_status', 'value' => $stock, 'compare' => '=' ) ); }

		if ( is_numeric( $supplier ) && AAA_API_Lokey_Atum_Bridge::is_active() ) {
			global $wpdb;
			$table = $wpdb->prefix . 'atum_product_data';
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT product_id FROM {$table} WHERE supplier_id = %d", (int) $supplier ) );
			$args['post__in'] = $ids ? array_map( 'intval', $ids ) : array( 0 );
		}

		$q = new WP_Query( $args );
		$ids = is_array( $q->posts ) ? $q->posts : array();
		$fallback = ! AAA_API_Lokey_Atum_Bridge::is_active();
		$atum_map = self::load_atum_rows( $ids, $fallback );

		$items = array();
		foreach ( $ids as $id ) {
			$p = wc_get_product( (int) $id );
			if ( ! $p ) { continue; }
			$row = isset( $atum_map[ (int) $id ] ) ? $atum_map[ (int) $id ] : array();
			$pp  = isset( $row['purchase_price'] ) ? (float) $row['purchase_price'] : 0.0;
			$sid = isset( $row['supplier_id'] ) ? (int) $row['supplier_id'] : 0;
			$qty = $p->get_stock_quantity();
			$qty_i = is_null( $qty ) ? 0 : (int) $qty;
			$items[] = array(
				'id'             => (int) $id,
				'name'           => $p->get_name(),
				'sku'            => (string) $p->get_sku(),
				'stock_status'   => (string) $p->get_stock_status(),
				'stock_quantity' => is_null( $qty ) ? null : $qty_i,
				'supplier'       => AAA_API_Lokey_REST_LokeyInventory_Common::supplier_label( $sid ),
				'purchase_price' => (float) $pp,
				'sale_price'     => (float) $p->get_sale_price(),
				'regular_price'  => (float) $p->get_regular_price(),
				'total_value'    => (float) ( $qty_i * $pp ),
			);
		}

		return rest_ensure_response( array(
			'version'   => AAA_API_Lokey_REST_Extended_Helpers::api_version(),
			'count'     => (int) $q->found_posts,
			'page'      => $page,
			'per_page'  => $pp,
			'fallback'  => $fallback,
			'timestamp' => current_time( 'mysql' ),
			'data'      => $items,
		) );
	}

	private static function load_atum_rows( $ids, $fallback ) {
		if ( $fallback || ! $ids ) { return array(); }
		global $wpdb;
		$table = $wpdb->prefix . 'atum_product_data';
		$in = implode( ',', array_map( 'intval', (array) $ids ) );
		$rows = $wpdb->get_results( "SELECT product_id, purchase_price, supplier_id FROM {$table} WHERE product_id IN ({$in})", ARRAY_A );
		$map = array();
		foreach ( (array) $rows as $r ) { $map[ (int) $r['product_id'] ] = $r; }
		return $map;
	}
}
