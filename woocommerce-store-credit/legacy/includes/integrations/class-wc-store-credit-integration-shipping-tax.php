<?php

defined( 'ABSPATH' ) or exit;

/**
 * Legacy integration for WooCommerce Shipping & Tax.
 *
 * @since 4.1.0
 * @deprecated 5.0.0
 */
class WC_Store_Credit_Integration_Shipping_Tax implements WC_Store_Credit_Integration {

	/**
	 * Init.
	 *
	 * @since 4.1.0
	 * @deprecated 5.0.0
	 *
	 * @return void
	 */
	public static function init() {

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

		return 'woocommerce-services/woocommerce-services.php';
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

		wc_deprecated_function( __METHOD__, '5.0.0' );

		return $calculate_discounts;
	}

}
