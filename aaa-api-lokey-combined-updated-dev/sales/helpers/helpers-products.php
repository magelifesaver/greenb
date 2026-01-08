<?php
/**
 * ============================================================================
 * File Path: /wp-content/mu-plugins/lokey-sales-reports/helpers/helpers-products.php
 * Version: 1.3.0
 * Updated: 2025-12-01
 * Author: Lokey Delivery DevOps
 * ============================================================================
 *
 * Description:
 *   Product aggregation utilities for LokeyReports endpoints:
 *     - Groups by product, category, or brand
 *     - Calculates sales totals, discounts, and quantities sold
 *     - Supports BeRocket brand taxonomy via filter
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * Return the brand taxonomy used for aggregation.
 * Default: berocket_brand (filterable).
 * --------------------------------------------------------------------------
 *
 * @return string
 */
if ( ! function_exists( 'lokey_reports_get_brand_taxonomy' ) ) {
	function lokey_reports_get_brand_taxonomy() {
		$taxonomy = 'berocket_brand';
		return apply_filters( 'lokey_reports_brand_taxonomy', $taxonomy );
	}
}

/**
 * --------------------------------------------------------------------------
 * Sanitize product grouping type (product|category|brand).
 * --------------------------------------------------------------------------
 *
 * @param string $value Raw group_by param.
 * @return string
 */
if ( ! function_exists( 'lokey_reports_sanitize_product_group' ) ) {
	function lokey_reports_sanitize_product_group( $value ) {
		$value   = is_string( $value ) ? strtolower( trim( $value ) ) : '';
		$allowed = [ 'product', 'category', 'brand' ];
		return in_array( $value, $allowed, true ) ? $value : 'product';
	}
}

/**
 * --------------------------------------------------------------------------
 * Aggregate order line items into product/category/brand metrics.
 * --------------------------------------------------------------------------
 *
 * @param WC_Order[] $orders   Orders within the requested range.
 * @param string     $group_by product|category|brand
 * @return array[] Aggregated rows with metrics.
 */
if ( ! function_exists( 'lokey_reports_aggregate_products' ) ) {
	function lokey_reports_aggregate_products( array $orders, $group_by ) {

		$group_by   = lokey_reports_sanitize_product_group( $group_by );
		$rows       = [];
		$orders_map = []; // [group_key][order_id] => true
		$term_cache = []; // [product_id|taxonomy] => terms[]

		// -- Helper: initialize product row
		$init_product = function ( $product_id, $product_name, $sku ) {
			return [
				'type'           => 'product',
				'product_id'     => $product_id,
				'product_name'   => $product_name,
				'sku'            => $sku,
				'qty_sold'       => 0,
				'orders_count'   => 0,
				'gross_sales'    => 0.0,
				'net_sales'      => 0.0,
				'discount_total' => 0.0,
			];
		};

		// -- Helper: initialize term row (category/brand)
		$init_term = function ( $term, $type ) {
			return [
				'type'           => $type, // category|brand
				'term_id'        => (int) $term->term_id,
				'name'           => (string) $term->name,
				'slug'           => (string) $term->slug,
				'taxonomy'       => (string) $term->taxonomy,
				'qty_sold'       => 0,
				'orders_count'   => 0,
				'gross_sales'    => 0.0,
				'net_sales'      => 0.0,
				'discount_total' => 0.0,
			];
		};

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order ) continue;

			$order_id = $order->get_id();

			foreach ( $order->get_items( 'line_item' ) as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) continue;

				$product_id = (int) $item->get_product_id();
				if ( ! $product_id ) continue;

				$qty = (int) $item->get_quantity();
				if ( $qty <= 0 ) continue;

				// Calculate totals for this line item.
				$subtotal      = (float) $item->get_subtotal() + (float) $item->get_subtotal_tax();
				$total         = (float) $item->get_total() + (float) $item->get_total_tax();
				$line_discount = max( 0.0, $subtotal - $total );

				// --------------------------------------------------------------
				// 🔹 Grouping: PRODUCT
				// --------------------------------------------------------------
				if ( 'product' === $group_by ) {
					$key = 'p_' . $product_id;

					if ( ! isset( $rows[ $key ] ) ) {
						$product      = $item->get_product();
						$product_name = $product ? $product->get_name() : $item->get_name();
						$sku          = $product ? $product->get_sku() : '';
						$rows[ $key ] = $init_product( $product_id, $product_name, $sku );
					}

					$rows[ $key ]['qty_sold']       += $qty;
					$rows[ $key ]['gross_sales']    += $subtotal;
					$rows[ $key ]['net_sales']      += $total;
					$rows[ $key ]['discount_total'] += $line_discount;

					if ( empty( $orders_map[ $key ][ $order_id ] ) ) {
						$rows[ $key ]['orders_count']++;
						$orders_map[ $key ][ $order_id ] = true;
					}

					continue; // done with this item
				}

				// --------------------------------------------------------------
				// 🔹 Grouping: CATEGORY or BRAND
				// --------------------------------------------------------------
				$taxonomy = ( 'category' === $group_by ) ? 'product_cat' : lokey_reports_get_brand_taxonomy();
				$cache_k  = $product_id . '|' . $taxonomy;

				if ( ! isset( $term_cache[ $cache_k ] ) ) {
					$terms = get_the_terms( $product_id, $taxonomy );
					$term_cache[ $cache_k ] = is_array( $terms ) ? $terms : [];
				}

				foreach ( $term_cache[ $cache_k ] as $term ) {
					if ( ! $term || ! isset( $term->term_id ) ) continue;

					$key = 't_' . $taxonomy . '_' . (int) $term->term_id;

					if ( ! isset( $rows[ $key ] ) ) {
						$type         = ( 'category' === $group_by ) ? 'category' : 'brand';
						$rows[ $key ] = $init_term( $term, $type );
					}

					$rows[ $key ]['qty_sold']       += $qty;
					$rows[ $key ]['gross_sales']    += $subtotal;
					$rows[ $key ]['net_sales']      += $total;
					$rows[ $key ]['discount_total'] += $line_discount;

					if ( empty( $orders_map[ $key ][ $order_id ] ) ) {
						$rows[ $key ]['orders_count']++;
						$orders_map[ $key ][ $order_id ] = true;
					}
				}
			}
		}

		return array_values( $rows );
	}
}

