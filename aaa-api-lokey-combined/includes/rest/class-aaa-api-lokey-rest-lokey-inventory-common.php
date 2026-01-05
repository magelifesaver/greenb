<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class AAA_API_Lokey_REST_LokeyInventory_Common {
	public static function require_woo() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_Error( 'aaa_api_lokey_woo_missing', 'WooCommerce is required.', array( 'status' => 424 ) );
		}
		return true;
	}

	public static function brand_taxonomy() {
		$settings = get_option( AAA_API_LOKEY_OPTION, array() );
		$tax = isset( $settings['brand_taxonomy'] ) ? sanitize_key( (string) $settings['brand_taxonomy'] ) : '';
		return ( $tax && taxonomy_exists( $tax ) ) ? $tax : '';
	}

	public static function supplier_label( $supplier_id ) {
		$supplier_id = (int) $supplier_id;
		if ( ! $supplier_id ) { return ''; }
		$p = get_post( $supplier_id );
		return ( $p && ! is_wp_error( $p ) && ! empty( $p->post_title ) ) ? (string) $p->post_title : (string) $supplier_id;
	}
}
