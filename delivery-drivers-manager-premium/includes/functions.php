<?php
use Automattic\WooCommerce\Utilities\OrderUtil;
	/**
	 * Create manager panel page.
	 *
	 * @return void
	 */
if ( ! function_exists( 'pwddm_create_manager_panel_page' ) ) {

	function pwddm_create_manager_panel_page() {
		// Create drivers panel page for the first activation.
		if ( ! get_option( 'pwddm_manager_page', false ) ) {
			$array   = array(
				'post_title'     => 'Delivery drivers manager',
				'post_type'      => 'page',
				'post_name'      => 'delivery-drivers-manager',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);
			$page_id = wp_insert_post( $array );
			update_option( 'pwddm_manager_page', $page_id );
		}
	}
}

	/**
	 * Determines whether HPOS is enabled.
	 *
	 * @return bool
	 */
if ( ! function_exists( 'pwddm_is_hpos_enabled' ) ) {
	function pwddm_is_hpos_enabled() : bool {
		if ( version_compare( get_option( 'woocommerce_version' ), '7.1.0' ) < 0 ) {
			return false;
		}

		if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return true;
		}

		return false;
	}
}

