<?php

defined( 'ABSPATH' ) or exit;

/**
 * Legacy integration for WooCommerce AvaTax.
 *
 * @since 4.1.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Integration_Avatax implements WC_Store_Credit_Integration {

	/**
	 * Init.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function init() : void {

		wc_deprecated_function( __METHOD__, '5.0.0' );
	}

	/**
	 * Gets the plugin basename.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @return string
	 */
	public static function get_plugin_basename() : string {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return 'woocommerce-avatax/woocommerce-avatax.php';
	}

	/**
	 * Whether to calculate the shipping discounts for the specified cart.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @param bool|mixed $calculate_discounts
	 * @return bool|mixed
	 */
	public static function calculate_shipping_discounts_for_cart( $calculate_discounts ) {

		return $calculate_discounts;
	}

	/**
	 * Gets the data for an AvaTax rate.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @param array|mixed $tax_rate
	 * @param int|mixed $rate_id
	 * @param WC_Order|mixed $order
	 * @return array|mixed
	 */
	public static function order_tax_rate( $tax_rate, $rate_id, $order ) {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return $tax_rate;
	}

	/**
	 * Filters the AvaTax transaction request data.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @param array|mixed $data
	 * @return array|mixed
	 */
	public static function avatax_request_data( $data ) {

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return $data;
	}

}
