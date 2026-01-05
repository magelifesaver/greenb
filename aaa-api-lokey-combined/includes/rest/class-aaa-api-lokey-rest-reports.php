<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_Reports {
	public static function register( $ns ) {
		register_rest_route( $ns, '/reports/top-products', array(
			'methods'=>WP_REST_Server::READABLE,
			'callback'=>array(__CLASS__,'top_products'),
			'permission_callback'=>array('AAA_API_Lokey_Auth','can_access'),
		) );
		register_rest_route( $ns, '/reports/low-stock', array(
			'methods'=>WP_REST_Server::READABLE,
			'callback'=>array(__CLASS__,'low_stock'),
			'permission_callback'=>array('AAA_API_Lokey_Auth','can_access'),
		) );
	}

	public static function top_products( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'aaa_api_lokey_woo_missing', 'WooCommerce is required.', array( 'status' => 424 ) );
		}
		global $wpdb;
		$limit = max( 1, min( 200, (int) $request->get_param( 'limit' ) ) );
		$status = sanitize_key( (string) $request->get_param( 'status' ) );
		$where  = "p.post_type='product'";
		if ( $status && 'any' !== $status ) { $where .= $wpdb->prepare( " AND p.post_status=%s", $status ); }
		$table_pml = $wpdb->prefix . 'wc_product_meta_lookup';
		$sql = "SELECT p.ID AS product_id, p.post_title AS name, IFNULL(pml.total_sales,0) AS total_sales, pml.stock_quantity, pml.stock_status
			FROM {$wpdb->posts} p
			LEFT JOIN {$table_pml} pml ON p.ID = pml.product_id
			WHERE {$where}
			ORDER BY total_sales DESC
			LIMIT %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $limit ), ARRAY_A );
		return rest_ensure_response( array('items'=>is_array($rows)?$rows:array()) );
	}

	public static function low_stock( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'aaa_api_lokey_woo_missing', 'WooCommerce is required.', array( 'status' => 424 ) );
		}
		global $wpdb;
		$limit = max( 1, min( 200, (int) $request->get_param( 'limit' ) ) );
		$thr   = (int) $request->get_param( 'threshold' );
		$thr   = $thr > 0 ? $thr : 5;
		$table_pml = $wpdb->prefix . 'wc_product_meta_lookup';
		$join_atum  = '';
		$sel_atum   = '';
		if ( AAA_API_Lokey_Atum_Bridge::is_active() ) {
			$table_apd = $wpdb->prefix . 'atum_product_data';
			$join_atum = "LEFT JOIN {$table_apd} apd ON p.ID = apd.product_id";
			$sel_atum  = ", apd.inbound_stock, apd.supplier_id, apd.barcode, apd.purchase_price";
		}
		$sql = "SELECT p.ID AS product_id, p.post_title AS name, pml.stock_quantity, pml.stock_status{$sel_atum}
			FROM {$wpdb->posts} p
			LEFT JOIN {$table_pml} pml ON p.ID = pml.product_id
			{$join_atum}
			WHERE p.post_type IN ('product','product_variation')
			AND p.post_status IN ('publish','private','draft')
			AND pml.manage_stock = 1
			AND pml.stock_quantity IS NOT NULL
			AND pml.stock_quantity <= %d
			ORDER BY pml.stock_quantity ASC
			LIMIT %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $thr, $limit ), ARRAY_A );
		return rest_ensure_response( array('threshold'=>$thr,'items'=>is_array($rows)?$rows:array()) );
	}
}
