<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Orders\Order;
use Kestrel\Account_Funds\Products\Store_Credit_Top_Up;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WC_Data_Exception;
use WC_Data_Store;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Order_Refund;
use WC_Product;
use WC_Subscription;

/**
 * Orders handler.
 *
 * @since 1.0.0
 */
final class Orders {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		// when using store credit for partial payments, hold the store credit applied to order metadata until payment is completed...
		if ( Blocks::is_checkout_block_in_use() ) {
			self::add_action( 'woocommerce_store_api_checkout_update_order_meta', [ $this, 'hold_store_credit_upon_order_created' ] );
		} else {
			self::add_action( 'woocommerce_checkout_create_order', [ $this, 'hold_store_credit_upon_order_created' ] );
		}

		// ...on payment complete, reduce the customer credit by the amount applied to the order
		self::add_action( 'woocommerce_payment_complete', [ $this, 'debit_store_credit_upon_payment_complete' ] );

		// award, refund or debit store credit on order status change or order refund event
		self::add_action( 'woocommerce_order_status_changed', [ $this, 'handle_store_credit_upon_order_status_change' ], 20, 4 );
		self::add_action( 'woocommerce_order_refunded', [ $this, 'void_store_credit_upon_order_refunded' ], 10, 2 );

		// apply store credit to order totals and adjust displayed order totals to include information on store credit applied
		self::add_action( 'woocommerce_order_after_calculate_totals', [ $this, 'apply_store_credit_to_calculated_order_totals' ], 10, 2 );
		self::add_filter( 'woocommerce_order_get_total', [ $this, 'adjust_order_total_to_include_store_credit' ], 10, 2 );
		self::add_filter( 'woocommerce_get_order_item_totals', [ $this, 'add_store_credit_row_to_order_totals' ], 10, 2 );

		// handle orders containing store credit top-up and deposits
		self::add_filter( 'woocommerce_order_item_needs_processing', [ $this, 'mark_order_containing_store_credit_top_up_or_deposit_as_needs_processing' ], 10, 2 );
		self::add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'add_store_credit_top_up_order_item_meta' ], 10, 3 );
		self::add_filter( 'woocommerce_order_item_product', [ $this, 'set_top_up_product_instance_for_order_item' ], 10, 2 );

		// handle special order queries for meta queries that include store credit usage in orders
		self::add_filter( 'woocommerce_order_query_args', [ __CLASS__, 'filter_order_query_args' ] );
		self::add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', [ __CLASS__, 'filter_orders_query' ], 10, 2 );
		self::add_filter( 'wcs_subscription_meta_query', [ $this, 'copy_subscriptions_order_meta_query' ], 10, 2 );
	}

	/**
	 * Processes a change in the Order status.
	 *
	 * @since 2.3.10
	 *
	 * @param int|mixed $order_id order ID
	 * @param mixed|string $from the old order status
	 * @param mixed|string $to the new order status
	 * @param mixed|WC_Order $order the order object
	 * @return void
	 */
	protected function handle_store_credit_upon_order_status_change( $order_id, $from, $to, $order = null ) : void {

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( in_array( $to, [ 'processing', 'completed' ], true ) ) {
			// process any deposits or top-ups purchased in the order
			$this->grant_store_credit_upon_order_paid( $order );
			// debit any held store credit
			$this->debit_store_credit_upon_payment_complete( $order );
			// pay the order with store credit if applicable
			$this->pay_order_with_store_credit( $order );
			// award any cashback after order is paid
			$this->award_store_credit_upon_order_paid( $order );
		} elseif ( 'on-hold' === $to ) {
			// debit any held store credit
			$this->debit_store_credit_upon_payment_complete( $order );
		} elseif ( 'cancelled' === $to ) {
			// refund any used store credit to pay for the cancelled order
			$this->handle_store_credit_upon_order_cancelled( $order );
		}
	}

	/**
	 * Pays the order with store credit if possible.
	 *
	 * @since 3.0.0
	 *
	 * @param WC_Order $order order ID
	 * @return void
	 */
	private function pay_order_with_store_credit( WC_Order $order ) : void {

		// not paying with funds
		if ( ! $order->get_customer_id() || Gateway::ID !== $order->get_payment_method() ) {
			return;
		}

		// already processed
		if ( wc_string_to_bool( $order->get_meta( '_funds_removed' ) ) ) {
			return;
		}

		/**
		 * Bail if this is a subscription renewal payment, as it's already handled by the gateway, {@see Gateway::process_subscription_payment()}.
		 */
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
			return;
		}

		$funds_used = $order->get_meta( '_funds_used' );

		if ( $funds_used && floatval( $funds_used > 0 ) ) {
			return;
		}

		try {
			Order::get( $order )->pay_with_store_credit();
		} catch ( Exception $exception ) {
			/* translators: Placeholder: %s - Error message */
			$order->add_order_note( sprintf( __( 'Payment error: %s', 'woocommerce-account-funds' ), $exception->getMessage() ) );
			$order->update_status( 'failed' );
		}
	}

	/**
	 * Grant store credit upon paid order from any deposit or top-up products purchased in the order.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	private function grant_store_credit_upon_order_paid( WC_Order $order ) : void {

		$customer_id = $order->get_customer_id();

		if ( ! $customer_id || wc_string_to_bool( $order->get_meta( '_funds_deposited' ) ) ) {
			return;
		}

		$items = $order->get_items();

		/** @var WC_Order_Item_Product $item */
		foreach ( $items as $item ) {

			$product = $item->get_product();

			if ( ! $product || ! $product->is_type( [ 'deposit', 'topup' ] ) ) {
				continue;
			}

			$amount = $product->is_type( 'topup' ) ? (float) $item['line_subtotal'] : (float) $product->get_regular_price() * (float) $item->get_quantity();

			if ( $amount <= 0.0 ) {
				continue;
			}

			try {
				if ( $product->is_type( 'topup' ) ) {
					$note = __( 'Customer bought store credit top-up.', 'woocommerce-account-funds' );
				} else {
					$note = __( 'Customer bought store credit deposit.', 'woocommerce-account-funds' );
				}

				Wallet::get( $order )->credit( Transaction::seed( [
					'amount'   => $amount,
					'event'    => Transaction_Event::ORDER_PAID,
					'event_id' => $order->get_id(),
					'note'     => $note,
				] ) );

				$order->update_meta_data( '_funds_deposited', 1 ); // @phpstan-ignore-line wrong type in WC function
				$order->save_meta_data();
				$order->add_order_note( sprintf(
					/* translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credit, e.g. "Store credit" */
					__( 'Added %1$s in %2$s to the customer\'s balance.', 'woocommerce-account-funds' ),
					wc_price( $amount, [ 'currency' => $order->get_currency() ] ),
					Store_Credit_Label::plural()->lowercase()->to_string()
				) );
			} catch ( Exception $exception ) {
				Logger::warning( sprintf( 'Could not grant store credit to user #%1$s for order #%2$s: %3$s', $customer_id, $order->get_id(), $exception->getMessage() ) );
			}
		}
	}

	/**
	 * Awards store credit (cashback) on order paid.
	 *
	 * @since 4.0.0
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	private function award_store_credit_upon_order_paid( WC_Order $order ) : void {

		Order::get( $order )->award_cashback();
	}

	/**
	 * Holds the store credit used in order meta until payment is completed, when paying with store credit partially.
	 *
	 * Basically, until the main gateway processes the payment, we hold the store credit used in the order meta.
	 * This temporarily lowers the available balance of the customer, until the store credit is debted or refunded.
	 *
	 * @see Wallet::available_balance()
	 *
	 * @since 2.4.0
	 *
	 * @param int|mixed|WC_Order $order order object or ID
	 * @return void
	 */
	protected function hold_store_credit_upon_order_created( $order ) : void {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order || ! Cart::is_using_account_funds_partially() ) {
			return;
		}

		$used_funds = Cart::get_applied_account_funds_amount();

		$order->update_meta_data( '_funds_used', $used_funds ); // @phpstan-ignore-line
		$order->update_meta_data( '_funds_removed', 0 ); // @phpstan-ignore-line
		$order->update_meta_data( '_funds_version', self::plugin()->version() ); // @phpstan-ignore-line
	}

	/**
	 * Debits store credit to customer when paying for an order partially using store credit.
	 *
	 * This callback triggers when the main gateway has paid for an order, and we debit the store credit held.
	 *
	 * @see Orders::hold_store_credit_upon_order_created()
	 *
	 * @since 2.0.0
	 *
	 * @param int|mixed|WC_Order $order
	 * @return void
	 */
	protected function debit_store_credit_upon_payment_complete( $order ) : void {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/**
		 * Bail early if funds have already been removed to prevent race conditions between `woocommerce_payment_complete` and `woocommerce_order_status_changed` hooks.
		 *
		 * @see Orders::handle_store_credit_upon_order_status_change() which could trigger multiple times for the same order
		 */
		if ( wc_string_to_bool( $order->get_meta( '_funds_removed' ) ) ) {
			return;
		}

		/**
		 * Bail if this is a subscription renewal payment, as it's already handled by the gateway, {@see Gateway::process_subscription_payment()}.
		 */
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
			return;
		}

		$customer_id = $order->get_customer_id();

		if ( ! $customer_id ) {
			return;
		}

		if ( null !== WC()->session ) {
			Cart::remove_account_funds_from_cart();
		}

		if ( $funds_used = (float) $order->get_meta( '_funds_used' ) ) {
			try {
				Wallet::get( $order )->debit( Transaction::seed( [
					'amount'   => $funds_used,
					'event'    => Transaction_Event::ORDER_PAID,
					'event_id' => $order->get_id(),
				] ) );
			} catch ( Exception $exception ) {
				Logger::warning( sprintf( 'Could not debit store credit funds to user #%1$s for paying order #%2$s: %3$s', $customer_id, $order->get_id(), $exception->getMessage() ) );
				return;
			}

			$order->update_meta_data( '_funds_removed', 1 ); // @phpstan-ignore-line wrong type in WC function
			$order->save_meta_data();
			$order->add_order_note( sprintf(
				/* translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credit, e.g. "Store credit" */
				__( 'Removed %1$s in %2$s from the customer\'s balance used to pay for the order.', 'woocommerce-account-funds' ),
				wc_price( $funds_used, [ 'currency' => $order->get_currency() ] ),
				Store_Credit_Label::plural()->lowercase()->to_string()
			) );
		}
	}

	/**
	 * Refunds any store credit applied to the order to the customer's wallet when an order paid with store credit is cancelled.
	 *
	 * Voids any awarded cashback for a cancelled order that granted store credit to the customer.
	 *
	 * @since 2.0.0
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	protected function handle_store_credit_upon_order_cancelled( WC_Order $order ) : void {

		$store_credit_used = (float) $order->get_meta( '_funds_used' );

		if ( ! $store_credit_used ) {
			return;
		}

		// unlock any store credit locked by the order pending payment
		if ( ! wc_string_to_bool( $order->get_meta( '_funds_removed' ) ) ) {
			$order->update_meta_data( '_funds_removed', 1 ); // @phpstan-ignore-line wrong type in WC function
			$order->update_meta_data( '_funds_refunded', $store_credit_used ); // @phpstan-ignore-line wrong type in WC function
			$order->save_meta_data();
		} else {
			$customer_id  = $order->get_customer_id();
			$store_credit = min(
				(float) $store_credit_used - (float) $order->get_meta( '_funds_refunded' ), // the remaining store credit
				(float) $order->get_total() - (float) $order->get_total_refunded() // the remaining order total
			);

			if ( $customer_id && $store_credit > 0 ) {
				try {
					Wallet::get( $order )->credit( Transaction::seed( [
						'amount'   => $store_credit,
						'event'    => Transaction_Event::ORDER_CANCELLED,
						'event_id' => $order->get_id(),
					] ) );

					$order->update_meta_data( '_funds_refunded', $store_credit_used ); // @phpstan-ignore-line wrong type in WC function
					$order->save_meta_data();

					$order->add_order_note( sprintf(
						/* translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credit, e.g. "Store credit" */
						__( 'Restored %1$s in %2$s from the customer\'s balance after the order was cancelled.', 'woocommerce-account-funds' ),
						wc_price( $store_credit, [ 'currency' => $order->get_currency() ] ),
						Store_Credit_Label::plural()->lowercase()->to_string()
					) );
				} catch ( Exception $exception ) {
					Logger::warning( sprintf( 'Could not restore store credit for user #%1$s after cancelling order #%2$s: %3$s', $customer_id, $order->get_id(), $exception->getMessage() ) );
				}
			}
		}

		Order::get( $order )->void_awarded_cashback();
	}

	/**
	 * Voids store credit when an order is refunded.
	 *
	 * This happens for cashback that was awarded by the refunded order, or for any top-up and deposit products that were bought in the refunded order.
	 *
	 * @since 2.1.0
	 *
	 * @param int|mixed $order_id order ID
	 * @param int|mixed $refund_id refund ID
	 * @return void
	 * @throws Exception
	 */
	protected function void_store_credit_upon_order_refunded( $order_id, $refund_id ) : void {

		$order       = wc_get_order( $order_id );
		$customer_id = $order ? $order->get_customer_id() : null;

		if ( ! $customer_id || ! $refund_id ) {
			return;
		}

		$refund               = new WC_Order_Refund( $refund_id );
		$refund_items         = $refund->get_items();
		$refunded_product_ids = [];

		$total_store_credit_to_refund = 0;

		/** @var WC_Order_Item_Product $item */
		foreach ( $refund_items as $item ) {

			// gather the total store credit to refund by looking for top up products in refunded line items (negative amounts)
			$refund_item_id         = $item->get_meta( '_refunded_item_id' );
			$refunded_product_ids[] = $item->get_product_id();

			if ( 'yes' === wc_get_order_item_meta( $refund_item_id, '_top_up_product', true ) ) {
				$total_store_credit_to_refund += abs( (float) $item->get_total() ); // refunded are negative, abs() used as need positive
			}
		}

		if ( ! empty( $refunded_product_ids ) ) {
			Order::get( $order )->void_awarded_cashback( $refunded_product_ids );
		}

		if ( 0 >= $total_store_credit_to_refund ) {
			return;
		}

		try {
			Wallet::get( $order )->credit( Transaction::seed( [
				'amount'   => $total_store_credit_to_refund,
				'event'    => Transaction_Event::ORDER_REFUNDED,
				'event_id' => (int) $refund_id,
			] ) );

			$order->add_order_note( sprintf(
				/* translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credit, e.g. "Store credit" */
				__( 'Granted %1$s in %2$s to the customer after refunding order items.', 'woocommerce-account-funds' ),
				wc_price( $total_store_credit_to_refund, [ 'currency' => $order->get_currency() ] ),
				Store_Credit_Label::plural()->lowercase()->to_string()
			) );

		} catch ( Exception $exception ) {
			Logger::warning( sprintf( 'Could not restore store credit for user #%1$s after refunding order #%2$s: %3$s', $customer_id, $order_id, $exception->getMessage() ) );

			return;
		}
	}

	/**
	 * Processes the order after calculate totals.
	 *
	 * @since 2.4.0
	 *
	 * @param bool|mixed $and_taxes whether taxes are included
	 * @param int|WC_Order $order order object
	 * @return void
	 * @throws WC_Data_Exception
	 */
	protected function apply_store_credit_to_calculated_order_totals( $and_taxes, $order ) : void {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$funds_used = (float) $order->get_meta( '_funds_used' );

		if ( 0 >= $funds_used ) {
			return;
		}

		// deduct store credit from the order total on partial payments
		if ( Gateway::ID !== $order->get_payment_method() ) {

			$total = $order->get_total( 'edit' ) - $funds_used;

			$order->set_total( (string) $total );
		}

		// update the store credit version
		$order->update_meta_data( '_funds_version', self::plugin()->version() );
	}

	/**
	 * Filters if the item needs to be processed before completing the order.
	 *
	 * @since 2.1.17
	 *
	 * @param bool|mixed $needs_processing needs processing?
	 * @param mixed|WC_Product $product product object
	 * @return bool|mixed
	 */
	protected function mark_order_containing_store_credit_top_up_or_deposit_as_needs_processing( $needs_processing, $product ) {

		if ( ! $product instanceof WC_Product ) {
			return $needs_processing;
		}

		if ( $product->is_type( 'deposit' ) || $product->is_type( 'topup' ) ) {
			$needs_processing = false;
		}

		return $needs_processing;
	}

	/**
	 * Moves used store credit row above the order total row in order details.
	 *
	 * @version 2.3.0
	 *
	 * @param array<string, mixed>|mixed $rows set of items for order details
	 * @param int|WC_Order $order order
	 * @return array<string, mixed>|mixed
	 */
	protected function add_store_credit_row_to_order_totals( $rows, $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		$parent_id = $order instanceof WC_Order ? $order->get_parent_id() : null;

		// this check exists to account for refunds and subscriptions
		if ( $parent_id ) {
			$order = wc_get_order( $parent_id );
		}

		if ( ! $order instanceof WC_Order || Gateway::ID === $order->get_payment_method() ) {
			return $rows;
		}

		$used_store_credit = (float) $order->get_meta( '_funds_used' );

		if ( $used_store_credit ) {
			$index = array_search( 'payment_method', array_keys( $rows ) ); // phpcs:ignore
			$rows  = array_merge( array_slice( $rows, 0, $index ), [
				'funds_used' => [
					'label' => Store_Credit_Label::plural()->uppercase_first()->to_string() . ':',
					'value' => '-' . wc_price( $used_store_credit, [ 'currency' => $order->get_currency() ] ),
				],
			], array_slice( $rows, $index ) );
		}

		return $rows;
	}

	/**
	 * Adjust total to include amount paid with store  credit.
	 *
	 * @since 2.0.0
	 *
	 * @param float|mixed $total order total
	 * @param int|mixed|WC_Order $order order object
	 * @return float|mixed
	 */
	protected function adjust_order_total_to_include_store_credit( $total, $order ) {
		global $wp;

		// don't interfere with total while paying for order
		if ( is_checkout() || ! empty( $wp->query_vars['order-pay'] ) || ( ! empty( $_POST ) && ! empty( $_POST['payment_status'] ) ) ) {
			return $total;
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order ) {
			return $total;
		}

		$funds_used = (float) $order->get_meta( '_funds_used' );

		if ( $funds_used > 0 && Gateway::ID === $order->get_payment_method() && ! $order->get_meta( '_funds_version' ) ) {
			$total = floatval( $order->get_total( 'edit' ) ) + $funds_used;
		}

		return $total;
	}

	/**
	 * Store top-up info.
	 *
	 * @since 2.1.3
	 *
	 * @param mixed|WC_Order_Item_Product $item order item object
	 * @param mixed|string $cart_item_key cart item key
	 * @param array<string, mixed>|mixed $values cart item values
	 */
	protected function add_store_credit_top_up_order_item_meta( $item, $cart_item_key, $values ) {

		if ( is_array( $values ) && $item instanceof WC_Order_Item && ! empty( $values['top_up_amount'] ) ) {

			$item->add_meta_data( '_top_up_amount', $values['top_up_amount'], true );
			$item->add_meta_data( '_top_up_product', 'yes', true );
		}
	}

	/**
	 * Update order item product with instance of {@see Store_Credit_Top_Up}.
	 *
	 * @since 2.1.3
	 *
	 * @param bool|WC_Product $product
	 * @param mixed|WC_Order_Item_Product $item
	 * @return bool|WC_Product
	 * @throws Exception
	 */
	protected function set_top_up_product_instance_for_order_item( $product, $item ) {

		if ( $item instanceof WC_Order_Item && 'yes' === $item->get_meta( '_top_up_product' ) ) {

			$product = new Store_Credit_Top_Up( 0 ); // @phpstan-ignore-line

			WC_Data_Store::load( 'product-topup' )->read( $product ); // @phpstan-ignore-line
		}

		return $product;
	}

	/**
	 * Filters the order posts query vars.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $args
	 * @return array<string, mixed>|mixed
	 */
	protected static function filter_order_query_args( $args ) {

		if ( ! is_array( $args ) ) {
			return $args;
		}

		if ( isset( $args['funds_query'] ) && WooCommerce::are_custom_order_tables_enabled() ) {
			if ( isset( $args['meta_query'] ) ) {
				array_push( $args['meta_query'], ...$args['funds_query'] );
			} else {
				$args['meta_query'] = $args['funds_query'];
			}

			unset( $args['funds_query'] );
		}

		return $args;
	}

	/**
	 * Handles order queries with custom metadata.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $query query for WC_Order_Query
	 * @param array<string, mixed>|mixed $query_vars query vars from a WC_Order_Query
	 * @return array<string, mixed>|mixed
	 */
	protected static function filter_orders_query( $query, $query_vars ) {

		if ( ! is_array( $query ) || ! is_array( $query_vars ) ) {
			return $query;
		}

		if ( isset( $query_vars['funds_query'] ) && ! WooCommerce::are_custom_order_tables_enabled() ) {
			array_push( $query['meta_query'], ...$query_vars['funds_query'] );
		}

		return $query;
	}

	/**
	 * Filters the meta query used for copying the metadata from a subscription to an order and vice-versa.
	 *
	 * @since 2.3.5
	 *
	 * @param mixed|string $meta_query the meta query string
	 * @param WC_Order|WC_Subscription $to_order the order to copy the metadata
	 * @return mixed|string
	 */
	protected function copy_subscriptions_order_meta_query( $meta_query, $to_order ) {

		// copying the metadata from an order to a subscription
		if ( is_string( $meta_query ) && $to_order instanceof WC_Subscription ) {
			$meta_query .= " AND `meta_key` NOT LIKE '_funds_%%'";
		}

		return $meta_query;
	}

}

class_alias(
	__NAMESPACE__ . '\Orders',
	'\WC_Account_Funds_Order_Manager'
);
