<?php

defined( 'ABSPATH' ) or exit;

/**
 *
 * Deposits handler.
 *
 * @since 2.0.0
 * @deprecated 4.0.0
 */
class WC_Account_Funds_Deposits_Manager {

	/**
	 * Show add to cart button.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public function add_to_cart() : void {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		woocommerce_simple_add_to_cart();
	}

	/**
	 * Ensure this is yes.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function enable_signup_and_login_from_checkout( $value ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $value;
	}

	/**
	 * Ensure this is no.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function enable_guest_checkout( $value ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $value;
	}

	/**
	 * Product type for topup.
	 *
	 * @since 2.1.3
	 * @deprecated 4.0.0
	 *
	 * @param mixed $override
	 * @param mixed $product_id
	 * @return mixed
	 */
	public function woocommerce_product_type_for_topup( $override, $product_id ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $override;
	}

	/**
	 * Top up product ID = my account page ID, until WC has a filter to adjust the product object.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @param mixed $classname
	 * @param mixed $product_type
	 * @param mixed $post_type
	 * @param mixed $product_id
	 * @return mixed
	 */
	public function woocommerce_product_class_for_topup( $classname, $product_type, $post_type, $product_id ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $classname;
	}

	/**
	 * Adjust the price.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @param mixed $cart_item
	 * @return array cart item
	 */
	public function add_cart_item( $cart_item ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $cart_item;
	}

	/**
	 * Get data from the session and add to the cart item's meta.
	 *
	 * @since 2.0.0
	 * @deprecated 4.0.0
	 *
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return mixed cart item
	 */
	public function get_cart_item_from_session( $cart_item, $values, $cart_item_key ) {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $cart_item;
	}

}

new WC_Account_Funds_Deposits_Manager();
