<?php
/**
 * Handle the Stock Takes integration for Barcodes PRO.
 *
 * @package     AtumBarcodes
 * @subpackage  Integrations
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       1.0.0
 */

namespace AtumBarcodes\Integrations;

use Atum\Inc\Globals as AtumGlobals;
use AtumBarcodes\Inc\Globals;

defined( 'ABSPATH' ) || die;

final class StockTakes {

	/**
	 * StockTakes singleton constructor
	 *
	 * @since 1.0.0
	 */
	public static function search_st_by_product_barcode( $barcode, $order_type_id ) {

		global $wpdb;

		$st_table                = $wpdb->prefix . \AtumST\StockTakes\StockTakes::STOCK_TAKES_TABLE;
		$atum_product_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;
		$barcode_like_term       = "%$barcode%";

		// Return all the order ids that includes a product with the barcode or their ID matches the barcode (if has no barcode saved) or have that specific barcode.
		// phpcs:disable WordPress.DB.PreparedSQL
		return apply_filters( 'atum/barcodes_pro/stock_takes/matching_orders', $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT st.st_id 
			FROM $st_table st
			LEFT JOIN $wpdb->postmeta pm ON (st.st_id = pm.post_id AND pm.meta_key = %s)
			WHERE ( st.item_id IN (
				SELECT product_id FROM $atum_product_data_table WHERE barcode LIKE %s
			)
			OR st.st_id LIKE %s OR pm.meta_value LIKE %s ) AND (st.is_inventory = 0 OR st.is_inventory IS NULL)",
			Globals::ATUM_BARCODE_META_KEY, $barcode_like_term, $barcode_like_term, $barcode_like_term ) ),
			$barcode, $order_type_id
		);
		// phpcs:enable

	}

}
