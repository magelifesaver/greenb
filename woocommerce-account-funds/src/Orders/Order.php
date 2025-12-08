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

namespace Kestrel\Account_Funds\Orders;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Lifecycle\Milestones\Customer_Paid_With_Store_Credit;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Awarded;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Helpers\Arrays;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Eligible_Orders_Group;
use Kestrel\Account_Funds\Store_Credit\Eligible_Products_Group;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Exceptions\Transaction_Exception;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WC_Order;
use WC_Order_Item_Product;
use WP_User;

/**
 * WooCommerce order handler for awarding cashback or for paying an order using store credit.
 *
 * @since 4.0.0
 */
final class Order {
	use Has_Plugin_Instance;

	/** @var WC_Order|null */
	private ?WC_Order $order;

	/** @var float */
	private float $order_total;

	/** @var WC_Order_Item_Product[] */
	private array $order_items;

	/** @var bool whether the order was paid with store credit (internal flag) */
	private bool $just_paid_with_store_credit = false;

	/** @var string */
	private static string $awarded_cashback_meta_key = '_awarded_cashback';

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param WC_Order|null $order
	 */
	private function __construct( ?WC_Order $order = null ) {

		$this->order       = ! is_a( $order, '\WC_Subscription' ) ? $order : null; // @phpstan-ignore-line unrecognized class is expected
		$this->order_total = $order ? (float) $order->get_total() : 0.0;
		$this->order_items = $order ? $order->get_items() : []; // @phpstan-ignore-line contains only WC_Order_Item_Product objects
	}

	/**
	 * Returns an order instance based on the identifier provided.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed|Order|WC_Order $identifier
	 * @return Order
	 */
	public static function get( $identifier ) : Order {

		if ( $identifier instanceof self ) {
			return $identifier;
		}

		if ( is_numeric( $identifier ) ) {
			$order = wc_get_order( $identifier );
		} else {
			$order = $identifier;
		}

		return new self( $order instanceof WC_Order ? $order : null );
	}

	/**
	 * Pays the order with the store credit using the wallet belonging to the customer associated with the order.
	 *
	 * @since 4.0.0
	 *
	 * @param float|int|null $amount_to_pay optional amount to pay with store credit: if null, the full order total will be used
	 * @return void
	 * @throws Transaction_Exception
	 */
	public function pay_with_store_credit( $amount_to_pay = null ) : void {

		if ( ! $this->order ) {
			/* translators: Placeholder: %s - label used to describe store credit to customers, e.g. "store credit" */
			throw new Transaction_Exception( esc_html( sprintf( __( 'Unable to determine the order to pay with %s.', 'woocommerce-account-funds' ), Store_Credit_Label::plural()->lowercase()->to_string() ) ) );
		}

		// already processed
		if ( $this->just_paid_with_store_credit || wc_string_to_bool( $this->order->get_meta( '_funds_removed' ) ) ) {
			return;
		}

		$wallet = Wallet::get( $this->order );

		if ( ! $wallet->email() ) {
			/* translators: Placeholder: %s - label used to describe store credit to customers, e.g. "store credit" */
			throw new Transaction_Exception( esc_html( sprintf( __( 'A registered customer with a valid email is required to pay for the order using %s.', 'woocommerce-account-funds' ), Store_Credit_Label::plural()->lowercase()->to_string() ) ) );
		}

		$balance       = $wallet->available_balance( $this->order );
		$amount_to_pay = floatval( null !== $amount_to_pay ? $amount_to_pay : $this->order_total );

		if ( $balance < $amount_to_pay ) {
			/* translators: Placeholder: %s - label used to describe store credit, e.g. "Insufficient store credit amount". */
			throw new Transaction_Exception( esc_html( sprintf( __( 'Insufficient %s amount to pay for the order.', 'woocommerce-account-funds' ), Store_Credit_Label::plural()->lowercase()->to_string() ) ) );
		}

		$wallet->debit( Transaction::seed( [
			'amount'   => $amount_to_pay,
			'event'    => Transaction_Event::ORDER_PAID,
			'event_id' => $this->order->get_id(),
		] ) );

		$this->order->update_meta_data( '_funds_used', $amount_to_pay ); // @phpstan-ignore-line WooCommerce documented the wrong type
		$this->order->update_meta_data( '_funds_removed', 1 ); // @phpstan-ignore-line WooCommerce documented the wrong type
		$this->order->update_meta_data( '_funds_version', self::plugin()->version() ); // @phpstan-ignore-line WooCommerce documented the wrong type
		$this->order->save_meta_data();

		$this->order->add_order_note( sprintf(
			/* translators: Placeholders: %1$s - Store credit label, %2$s - Amount paid using store credit */
			__( 'Customer paid with %1$s: %2$s.', 'woocommerce-account-funds' ),
			Store_Credit_Label::plural()->lowercase()->to_string(),
			wc_price( -abs( $amount_to_pay ), [ 'currency' => $this->order->get_currency() ] )
		) );

		$this->just_paid_with_store_credit = true;

		Customer_Paid_With_Store_Credit::trigger( [ 'order_id' => $this->order->get_id() ] );
	}

	/**
	 * Returns applicable cashback rewards for the customer based on the current order.
	 *
	 * @since 4.0.0
	 *
	 * @param Wallet $wallet
	 * @return Transaction[]
	 */
	public function applicable_cashback_rewards( Wallet $wallet ) : array {

		if ( ! $this->order ) {
			return [];
		}

		$applicable       = [];
		$cashback_sources = Cashback::find_many( [
			'status'  => Reward_Status::ACTIVE,
			'deleted' => false,
		] );

		/** @var Cashback $cashback */
		foreach ( $cashback_sources as $cashback ) {

			if ( $cashback->is_depleted() || ( $cashback->is_unique() && $wallet->has_credit_from( $cashback ) ) ) {
				continue;
			}

			if ( $cashback->get_trigger() === Transaction_Event::PRODUCT_PURCHASE ) {
				$applicable = array_merge( $applicable, $this->get_cashback_for_product_purchase( $cashback, $wallet ) );
			} elseif ( $cashback->get_trigger() === Transaction_Event::ORDER_PAID ) {
				if ( $reward = $this->get_cashback_for_order_paid( $cashback, $wallet ) ) {
					$applicable[] = $reward;
				}
			}
		}

		return $applicable;
	}

	/**
	 * Awards cashback to the customer based on the order.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function award_cashback() : void {

		if ( ! $this->order ) {
			return;
		}

		$customer_id    = $this->get_customer_id();
		$customer_email = $this->get_customer_email();

		if ( ! $customer_email || $this->has_awarded_cashback() ) {
			return;
		}

		$user_wallet = Wallet::get( $customer_id ?: $customer_email );
		$rewards     = $this->applicable_cashback_rewards( $user_wallet );

		if ( empty( $rewards ) ) {
			return;
		}

		$awarded_total    = 0.0;
		$cashback_sources = [];

		foreach ( $rewards as $reward ) {

			try {
				$user_wallet->credit( $reward );
			} catch ( Exception $exception ) {
				Logger::warning( sprintf( 'Failed to credit cashback to user with email "%s" for order #%d : %s', $this->order->get_id(), $user_wallet->email(), $exception->getMessage() ) );
				continue;
			}

			$reward = $reward->get_reward_object();

			if ( $reward instanceof Cashback ) {
				$cashback_sources[] = $reward;
			}

			$awarded_total += $reward->get_amount();
		}

		if ( $awarded_total > 0.0 && ! empty( $cashback_sources ) ) {
			$this->record_awarded_cashback_on_order( $awarded_total, $cashback_sources, $rewards );
		}
	}

	/**
	 * Checks if the order has already been awarded store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private function has_awarded_cashback() : bool {

		return $this->order && ! empty( $this->order->get_meta( self::$awarded_cashback_meta_key ) );
	}

	/**
	 * Adds an order note for the awarded cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param float $awarded_total total amount of cashback awarded
	 * @param Cashback[] $cashback_sources list of cashback sources that were used to award the cashback
	 * @param Transaction[] $transactions list of transactions that were created for the cashback
	 */
	private function record_awarded_cashback_on_order( float $awarded_total, array $cashback_sources, array $transactions ) : void {

		$cashback_ids = $cashback_links = [];

		foreach ( $cashback_sources as $cashback ) {
			$cashback_links[] = sprintf( '<a href="%1$s">"%2$s"</a>', esc_url( $cashback->edit_item_url() ), esc_html( $cashback->get_label() ) );
		}

		foreach ( $transactions as $transaction ) {
			$cashback_ids[] = $transaction->get_reward_id();
		}

		$note = sprintf(
			/* translators: Placeholder: %1$s - Total cashback amount awarded, %2$s - Comma-separated list of admin cashback sources (HTML links) */
			__( 'Awarded %1$s as %2$s cashback to customer via %3$s.', 'account-funds-for-woocommerce' ),
			wc_price( $awarded_total, [ 'currency' => $this->order->get_currency() ] ),
			Store_Credit_Label::plural()->lowercase()->to_string(),
			Arrays::array( $cashback_links )->to_human_readable_list()
		);

		$this->order->update_meta_data( self::$awarded_cashback_meta_key, $cashback_ids );
		$this->order->save_meta_data();
		$this->order->add_order_note( $note );

		First_Store_Credit_Awarded::trigger();
	}

	/**
	 * If the order was cancelled or refunded, voids the awarded cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param int[]|null $product_ids if null, voids all awarded cashback for the order, otherwise only voids the matched product purchases
	 * @return void
	 */
	public function void_awarded_cashback( ?array $product_ids = null ) : void {

		if ( ! $this->order ) {
			return;
		}

		$transaction_ids = $this->order->get_meta( self::$awarded_cashback_meta_key );

		if ( empty( $transaction_ids ) || ! is_array( $transaction_ids ) ) {
			return;
		}

		$transaction_ids = Transaction::find_many( [ 'id' => $transaction_ids ] );
		$voided_amount   = 0.0;

		foreach ( $transaction_ids as $transaction ) {

			if ( $transaction->is_voided() || $transaction->is_debit() ) {
				continue;
			}

			if ( null === $product_ids || ( $transaction->get_event() === Transaction_Event::PRODUCT_PURCHASE && in_array( $transaction->get_event_id(), $product_ids, true ) ) ) {

				$transaction->void();

				$voided_amount += $transaction->get_amount();
			}
		}

		if ( $voided_amount > 0.0 ) {

			$note = sprintf(
				/* translators: Placeholders: %1$s - Voided amount, %2$s - Store credit label */
				__( 'Voided %1$s from the awarded %2$s cashback for this order.', 'account-funds-for-woocommerce' ),
				wc_price( $voided_amount, [ 'currency' => $this->order->get_currency() ] ),
				Store_Credit_Label::plural()->lowercase()->to_string()
			);

			$this->order->add_order_note( $note );
		}
	}

	/**
	 * Returns any cashback transactions for eligible product purchases in order.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback $cashback
	 * @param Wallet $wallet
	 * @return Transaction[] transactions for the products purchased
	 */
	private function get_cashback_for_product_purchase( Cashback $cashback, Wallet $wallet ) : array {

		$transactions = [];

		if ( empty( $this->order_items ) || ! $wallet->email() ) {
			return $transactions;
		}

		if ( $cashback->get_eligible_products() === Eligible_Products_Group::ALL_PRODUCTS ) {
			$all_products_eligible = true;
			$eligible_product_ids  = [];
		} else {
			$all_products_eligible = false;
			$eligible_product_ids  = $cashback->get_products_ids();

			if ( $cashback->get_eligible_products() === Eligible_Products_Group::SOME_PRODUCT_CATEGORIES ) {
				foreach ( $cashback->get_product_category_ids() as $product_category_id ) {
					$eligible_product_ids = array_merge( $eligible_product_ids, $this->get_products_for_category( $product_category_id ) );
				}
			}
		}

		foreach ( $this->order_items as $item ) {

			$product_id = $item->get_product_id();
			$quantity   = $item->get_quantity();
			$item_total = $item->get_total() ?: 0.0;

			if ( ! $product_id || ! $quantity || ! $item_total ) {
				continue;
			}

			if ( ! $all_products_eligible && ! in_array( $product_id, $eligible_product_ids, true ) ) {
				continue;
			}

			if ( $cashback->is_limited_to_once_per_product() && $wallet->has_credit_from( $cashback, [ 'event' => Transaction_Event::PRODUCT_PURCHASE, 'event_id' => $product_id ] ) ) {
				continue;
			}

			$amount = $this->calculate_cashback_amount( (float) $item_total, $quantity, $cashback );

			if ( $amount === 0.0 ) {
				continue;
			}

			$transactions[] = Transaction::seed()
				->set_reward_id( $cashback->get_id() )
				->set_customer_id( $wallet->id() )
				->set_customer_email( $wallet->email() )
				->set_event( Transaction_Event::PRODUCT_PURCHASE )
				->set_event_id( $product_id )
				->set_currency( $this->order->get_currency() )
				->set_amount( $amount );
		}

		return $transactions;
	}

	/**
	 * Returns a cashback transaction for the order paid.
	 *
	 * @since 4.0.0
	 *
	 * @param Cashback $cashback
	 * @param Wallet $wallet
	 * @return Transaction|null
	 */
	private function get_cashback_for_order_paid( Cashback $cashback, Wallet $wallet ) : ?Transaction {

		if ( ! $wallet->email() ) {
			return null;
		}

		// check for min/max order amounts requirements
		if ( floatval( $cashback->get_minimum_order_amount() ) > $this->order_total ) {
			return null;
		} elseif ( $cashback->get_maximum_order_amount() && $cashback->get_maximum_order_amount() < $this->order_total ) {
			return null;
		}

		// check for exclusion flags
		if ( $cashback->excludes_free_items() && $this->order->has_free_item() ) {
			return null;
		} elseif ( $cashback->excludes_coupon_discounts() && ! empty( $this->order->get_coupon_codes() ) ) {
			return null;
		} elseif ( $cashback->excludes_items_on_sale() && $this->contains_products_on_sale() ) {
			return null;
		}

		// exclude top-up products from cashback if disallowed in settings
		if ( Store_Credit_Account_Top_Up::exclude_top_up_from_rewards() && $this->contains_product_types( [ 'topup' ] ) ) {
			return null;
		}

		$is_awardable = false;

		// check for product eligibility in order
		switch ( $cashback->get_eligible_orders() ) {

			case Eligible_Orders_Group::EXCLUDING_PRODUCTS:
				if ( ! $this->contains_products( $cashback->get_products_ids() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::INCLUDING_PRODUCTS:
				if ( $this->contains_products( $cashback->get_products_ids() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::EXCLUDING_PRODUCT_CATEGORIES:
				if ( ! $this->contains_product_categories( $cashback->get_product_category_ids() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::INCLUDING_PRODUCT_CATEGORIES:
				if ( $this->contains_product_categories( $cashback->get_product_category_ids() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::EXCLUDING_PRODUCT_TYPES:
				if ( ! $this->contains_product_types( $cashback->get_product_types() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::INCLUDING_PRODUCT_TYPES:
				if ( $this->contains_product_types( $cashback->get_product_types() ) ) {
					$is_awardable = true;
				}

				break;

			case Eligible_Orders_Group::ALL_ORDERS:
			default:
				$is_awardable = true;
				break;
		}

		if ( ! $is_awardable ) {
			return null;
		}

		$amount = $this->calculate_cashback_amount( $this->order_total, 1, $cashback );

		if ( $amount === 0.0 ) {
			return null;
		}

		return Transaction::seed()
			->set_reward_id( $cashback->get_id() )
			->set_customer_id( $wallet->id() )
			->set_customer_email( $wallet->email() )
			->set_event( Transaction_Event::ORDER_PAID )
			->set_event_id( $this->order->get_id() )
			->set_currency( $this->order->get_currency() )
			->set_amount( $amount );
	}

	/**
	 * Calculates the cashback amount to be awarded.
	 *
	 * @since 4.0.0
	 *
	 * @param float $amount base amount to calculate cashback on (e.g. order total or paid item price)
	 * @param float|int $quantity quantity, if a multiplier applies (products only)
	 * @param Cashback $cashback cashback object containing the rules for the cashback
	 * @return float calculated cashback amount
	 */
	private function calculate_cashback_amount( float $amount, $quantity, Cashback $cashback ) : float {

		if ( $cashback->is_percentage() ) {
			$amount = ( $amount * $cashback->get_amount() ) / 100;
		} else {
			$amount = $cashback->get_amount();
		}

		if ( $cashback->get_trigger() === Transaction_Event::PRODUCT_PURCHASE && $cashback->get_product_quantity_behavior() === 'multiply' ) {
			$amount *= floatval( $quantity );
		}

		return max( 0.0, $amount );
	}

	/**
	 * Returns the customer ID from the order.
	 *
	 * @since 4.0.0
	 *
	 * @return int|null
	 */
	private function get_customer_id() : ?int {

		$customer_id = $this->order->get_customer_id();

		// @phpstan-ignore-next-line type safety check
		if ( is_numeric( $customer_id ) && $customer_id > 0 ) {
			return (int) $customer_id;
		}

		return null;
	}

	/**
	 * Returns the customer email from the order.
	 *
	 * @since 4.0.0
	 *
	 * @return string|null
	 */
	private function get_customer_email() : ?string {

		/** @var int|string $customer_id */
		$customer_id    = $this->order->get_customer_id(); // @phpstan-ignore-line type safety check
		$customer_email = null;

		if ( is_numeric( $customer_id ) && $customer_id > 0 ) {
			$customer = get_user_by( 'id', $customer_id );

			if ( $customer instanceof WP_User ) {
				$customer_email = $customer->user_email;
			}
		} elseif ( is_string( $customer_id ) && is_email( $customer_id ) ) {
			$customer_email = $customer_id;
		} elseif ( $billing_email = $this->order->get_billing_email() ) {
			$customer_email = $billing_email;
		}

		return $customer_email;
	}

	/**
	 * Returns a list of product IDs for the given product categories.
	 *
	 * @since 4.0.0
	 *
	 * @param int $category_id
	 * @return int[] product IDs
	 */
	private function get_products_for_category( int $category_id ) : array {

		$products = [];

		foreach ( $this->order_items as $item ) {
			$product = $item->get_product();

			if ( $product && has_term( $category_id, 'product_cat', $product->get_id() ) ) {
				$products[] = $product->get_id();
			}
		}

		return $products;
	}

	/**
	 * Determines if the order contains any items on sale.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private function contains_products_on_sale() : bool {

		foreach ( $this->order_items as $item ) {
			$product = $item->get_product();

			if ( $product && $product->is_on_sale() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if the order contains any of the given products.
	 *
	 * @since 4.0.0
	 *
	 * @param array $product_ids
	 * @return bool
	 */
	private function contains_products( array $product_ids ) : bool {

		if ( empty( $product_ids ) ) {
			return false;
		}

		foreach ( $this->order_items as $item ) {
			if ( in_array( $item->get_product_id(), $product_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if the order contains any of the given product categories.
	 *
	 * @since 4.0.0
	 *
	 * @param int[] $product_category_ids
	 * @return bool
	 */
	private function contains_product_categories( array $product_category_ids ) : bool {

		if ( empty( $product_category_ids ) ) {
			return false;
		}

		foreach ( $this->order_items as $item ) {
			$product = $item->get_product();

			if ( $product && has_term( $product_category_ids, 'product_cat', $product->get_id() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determines if the order contains any of the given product types.
	 *
	 * @since 4.0.0
	 *
	 * @param string[] $product_types
	 * @return bool
	 */
	private function contains_product_types( array $product_types ) : bool {

		if ( empty( $product_types ) ) {
			return false;
		}

		foreach ( $this->order_items as $item ) {
			$product      = $item->get_product();
			$product_type = $product ? $product->get_type() : null;

			if ( ! $product_type ) {
				continue;
			}

			if ( $product && in_array( $product_type, $product_types, true ) ) {
				return true;
			}
		}

		return false;
	}

}
