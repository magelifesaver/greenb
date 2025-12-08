<?php

defined( 'ABSPATH' ) or exit;

/**
 * Legacy integration for WooCommerce PDF Invoices & Packing Slips.
 *
 * @since 5.0.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Integration_Invoices_Packing_Slips implements WC_Store_Credit_Integration {

	/**
	 * Init.
	 *
	 * @since 5.0.0
	 * @deprecated 5.0.0
	 */
	public static function init() {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Gets the plugin basename.
	 *
	 * @since 5.0.0
	 * @deprecated 5.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_basename() : string {

		return 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php';
	}

	/**
	 * Filters the order 'discount_total' value.
	 *
	 * This filter is only called for 'view' context.
	 *
	 * @since 5.0.0
	 * @deprecated 5.0.0
	 *
	 * @param float|mixed $discount_total
	 * @param WC_Order|mixed $order
	 * @return float|mixed
	 */
	public static function get_order_discount_total( $discount_total, $order ) {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return $discount_total;
	}

}
