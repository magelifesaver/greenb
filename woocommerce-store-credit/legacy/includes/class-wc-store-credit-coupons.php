<?php
/**
 * Class to handle the store credit coupons.
 *
 * @package WC_Store_Credit/Classes
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Store_Credit_Coupons class.
 */
class WC_Store_Credit_Coupons {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_coupon_discount_types', [ $this, 'add_discount_type' ] );
		add_filter( 'woocommerce_product_coupon_types', [ $this, 'product_coupon_types' ] );
		add_filter( 'woocommerce_coupon_is_valid_for_cart', [ $this, 'is_valid_for_cart' ], 10, 2 );
		add_filter( 'woocommerce_coupon_is_valid', [ $this, 'is_valid' ], 10, 3 );
		add_filter( 'woocommerce_coupon_error', [ $this, 'error_message' ], 10, 3 );
		add_filter( 'woocommerce_coupon_get_individual_use', [ $this, 'filter_individual_use' ], 10, 2 );
		add_filter( 'woocommerce_coupon_get_maximum_amount', [ $this, 'filter_maximum_spend' ], 10, 2 );

		add_action( 'woocommerce_new_coupon', [ $this, 'flush_coupon_cache' ] );
		add_action( 'woocommerce_update_coupon', [ $this, 'flush_coupon_cache' ] );
		add_action( 'wc_store_credit_before_trash_coupon', [ $this, 'flush_coupon_cache' ] );
		add_action( 'wc_store_credit_before_delete_coupon', [ $this, 'flush_coupon_cache' ] );
	}

	/**
	 * Registers the 'store_credit' discount type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $discount_types The coupon types.
	 * @return array
	 */
	public function add_discount_type( $discount_types ) {
		$discount_types['store_credit'] = __( 'Store credit', 'woocommerce-store-credit' );

		return $discount_types;
	}

	/**
	 * Registers the 'store_credit' coupon as a product discount type.
	 *
	 * @since 3.0.0
	 *
	 * @param array $types The cart coupon types.
	 * @return array
	 */
	public function product_coupon_types( $types ) {
		$types[] = 'store_credit';

		return $types;
	}

	/**
	 * Checks if the coupon is valid for cart.
	 *
	 * @since 3.0.0
	 *
	 * @param bool      $valid  True if the coupon is valid. False otherwise.
	 * @param WC_Coupon $coupon The coupon object.
	 * @return bool
	 */
	public function is_valid_for_cart( $valid, $coupon ) {
		if ( wc_is_store_credit_coupon( $coupon ) ) {
			// Not valid for the cart if the coupon has product restrictions.
			$valid = ! (
				count( $coupon->get_product_ids() ) || count( $coupon->get_product_categories() ) ||
				count( $coupon->get_excluded_product_ids() ) || count( $coupon->get_excluded_product_categories() ) ||
				$coupon->get_exclude_sale_items()
			);
		}

		return $valid;
	}

	/**
	 * Validates a store credit coupon.
	 *
	 * @since 3.0.0
	 *
	 * @param bool         $valid     True if the coupon is valid. False otherwise.
	 * @param WC_Coupon    $coupon    The coupon object.
	 * @param WC_Discounts $discounts WC_Discounts instance.
	 * @return bool
	 */
	public function is_valid( $valid, $coupon, $discounts ) {
		if ( ! $valid || ! wc_is_store_credit_coupon( $coupon ) ) {
			return $valid;
		}

		$is_cart = ( $discounts->get_object() instanceof WC_Cart );

		// The cart contains Store Credit products.
		if ( $is_cart ) {
			foreach ( $discounts->get_items() as $item ) {
				if ( $item->product->is_type( 'store_credit' ) ) {
					return false;
				}
			}
		}

		$credit = $coupon->get_amount();

		if ( $is_cart ) {
			// Include the credit used in the pending payment order.
			$order_id = WC()->session->get( 'order_awaiting_payment' );

			if ( $order_id ) {
				$code        = $coupon->get_code();
				$credit_used = wc_get_store_credit_used_for_order( $order_id, 'per_coupon' );

				if ( ! empty( $credit_used[ $code ] ) ) {
					$credit += $credit_used[ $code ];
				}
			}
		}

		// Credit exhausted.
		if ( $credit <= 0 ) {
			return false;
		}

		return $valid;
	}

	/**
	 * Filters the coupon error message.
	 *
	 * @since 3.0.0
	 *
	 * @param string    $message Error message.
	 * @param int       $code    Error code.
	 * @param WC_Coupon $coupon  Coupon object.
	 * @return mixed
	 */
	public function error_message( $message, $code, $coupon ) {
		if ( 100 !== $code || ! wc_is_store_credit_coupon( $coupon ) ) {
			return $message;
		}

		if ( $coupon->get_amount() <= 0 ) {
			return __( 'There is no credit remaining on this coupon.', 'woocommerce-store-credit' );
		} else {
			return __( 'This coupon cannot be used on this cart.', 'woocommerce-store-credit' );
		}
	}

	/**
	 * Filters the 'individual use' property of a coupon to allow store credit to be used with them.
	 *
	 * @param bool      $is_individual_use Whether the coupon is for individual use.
	 * @param WC_Coupon $coupon            Coupon object.
	 * @return bool
	 */
	public function filter_individual_use( $is_individual_use, $coupon ) {
		// If it's not individual use enabled, do nothing.
		if ( ! $is_individual_use ) {
			return false;
		}

		// A store credit coupon should never restrict other coupons.
		if ( wc_is_store_credit_coupon( $coupon ) ) {
			return false;
		}

		// An individual use coupon is being checked, let's see what else is in the cart.
		if ( ! isset( WC()->cart ) ) {
			return $is_individual_use;
		}

		$applied_coupons = WC()->cart->get_applied_coupons();
		$other_coupons   = array_diff( $applied_coupons, [ $coupon->get_code() ] );

		if ( empty( $other_coupons ) ) {
			return $is_individual_use;
		}

		// Check if all other applied coupons are store credit coupons.
		$all_others_are_store_credit = true;
		foreach ( $other_coupons as $coupon_code ) {
			if ( ! wc_is_store_credit_coupon( $coupon_code ) ) {
				$all_others_are_store_credit = false;
				break;
			}
		}

		// If all other coupons are store credit, allow this "individual use" coupon.
		if ( $all_others_are_store_credit ) {
			return false;
		}

		return $is_individual_use;
	}

	/**
	 * Filters the coupon 'maximum_amount' property to bypass the validation for store credit coupons.
	 *
	 * @since 5.0.0
	 * @internal
	 *
	 * @param string|mixed $maximum_amount the maximum amount
	 * @param WC_Coupon|mixed $coupon the coupon object
	 * @return string|mixed
	 */
	public function filter_maximum_spend( $maximum_amount, $coupon ) {

		if ( wc_is_store_credit_coupon( $coupon ) ) {
			return ''; // store credit coupons should not have a maximum spend amount
		}

		return $maximum_amount;
	}

	/**
	 * Flushes the cache related to the specified coupon.
	 *
	 * @since 3.1.2
	 *
	 * @param mixed $the_coupon Coupon object, ID or code.
	 */
	public function flush_coupon_cache( $the_coupon ) {
		$coupon = wc_store_credit_get_coupon( $the_coupon );

		if ( ! wc_is_store_credit_coupon( $coupon ) ) {
			return;
		}

		$allowed_emails = $coupon->get_email_restrictions();

		foreach ( $allowed_emails as $email ) {
			$key = sanitize_key( $email );

			wp_cache_delete( "wc_store_credit_customer_all_coupons_{$key}", 'store_credit' );
			wp_cache_delete( "wc_store_credit_customer_active_coupons_{$key}", 'store_credit' );
			wp_cache_delete( "wc_store_credit_customer_exhausted_coupons_{$key}", 'store_credit' );
		}
	}
}

return new WC_Store_Credit_Coupons();
