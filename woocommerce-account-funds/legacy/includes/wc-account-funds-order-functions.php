<?php
/**
 * Order functions.
 *
 * @since 2.6.3
 * @deprecated 4.0.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Formats the price with the order's currency symbol.
 *
 * @internal
 *
 * @since 2.6.3
 * @deprecated 4.0.0
 *
 * @param WC_Order $order order object
 * @param float|string $price raw price
 * @param array<string, mixed> $args optional arguments for formatting the price
 * @return string
 */
function wc_account_funds_format_order_price( WC_Order $order, $price, array $args = [] ) : string {

	wc_deprecated_function( __FUNCTION__, '4.0.0', 'wc_price() with the order\'s currency' );

	$args = wp_parse_args(
		$args,
		[
			'currency' => $order->get_currency(),
		]
	);

	return wc_price( $price, $args );
}

/**
 * Determines if the order contains deposit or top-up products.
 *
 * @internal
 *
 * @since 2.9.0
 * @deprecated 4.0.0
 *
 * @param WC_Order $order Order object.
 * @return bool
 */
function wc_account_funds_order_contains_deposit( WC_Order $order ) : bool {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	/** @var WC_Order_Item_Product[] $items */
	$items = $order->get_items();

	foreach ( $items as $item ) {
		$product = $item->get_product();

		if ( $product && $product->is_type( [ 'deposit', 'topup' ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Gets if the order contains only deposit products.
 *
 * @internal
 *
 * @since 3.2.0
 * @deprecated 4.0.0
 *
 * @param WC_Order $order
 * @return bool
 */
function wc_account_funds_order_contains_deposit_only( WC_Order $order ) : bool {

	wc_deprecated_function( __FUNCTION__, '4.0.0' );

	/** @var WC_Order_Item_Product[] $items */
	$items = $order->get_items();

	foreach ( $items as $item ) {
		$product = $item->get_product();

		if ( $product && ! $product->is_type( [ 'deposit', 'topup' ] ) ) {
			return false;
		}
	}

	return ! empty( $items );
}

/**
 * Pays the order with the customer's funds.
 *
 * @internal
 *
 * @since 3.0.0
 * @deprecated 4.0.0
 *
 * @param WC_Order $order
 * @param float|int|null $order_total
 * @return true|WP_Error
 */
function wc_account_funds_pay_order_with_funds( WC_Order $order, $order_total = null ) {

	wc_deprecated_function( __FUNCTION__, '4.0.0', \Kestrel\Account_Funds\Orders\Order::class . '::pay_with_store_credit()' );

	$customer_id = $order->get_customer_id();

	if ( ! $customer_id ) {
		return new WP_Error( 'customer_not_found', __( 'Customer not found.', 'woocommerce-account-funds' ) );
	}

	$funds = WC_Account_Funds::get_account_funds( (int) $customer_id, false, $order->get_id() );

	if ( is_null( $order_total ) ) {
		$order_total = $order->get_total();
	}

	if ( $funds < $order_total ) {
		return new WP_Error(
			'insufficient_funds',
			sprintf(
				/* translators: Placeholder: %s - label used to describe account funds, e.g. insufficient account funds amount */
				__( 'Insufficient %s amount.', 'woocommerce-account-funds' ),
				\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->to_string()
			)
		);
	}

	\Kestrel\Account_Funds\Users::decrease_user_funds(
		$customer_id,
		floatval( $order_total ),
		\Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event::ORDER_PAID,
		$order->get_id()
	);

	$order->update_meta_data( '_funds_used', $order_total );
	$order->update_meta_data( '_funds_removed', 1 );
	$order->update_meta_data( '_funds_version', \WC_ACCOUNT_FUNDS_VERSION );
	$order->save_meta_data();

	$order->add_order_note(
		sprintf(
			/* translators: Placeholders: %1$s payment gateway title, %2$s account funds used (applied at checkout) */
			__( '%1$s payment applied: %2$s', 'woocommerce-account-funds' ),
			$order->get_payment_method_title(),
			wc_price( $order_total, [ 'currency' => $order->get_currency() ] )
		)
	);

	return true;
}
