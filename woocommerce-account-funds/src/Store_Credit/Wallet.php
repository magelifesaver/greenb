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

namespace Kestrel\Account_Funds\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\User;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Customer;
use Kestrel\Account_Funds\Store_Credit\Wallet\Balance;
use Kestrel\Account_Funds\Store_Credit\Wallet\Exceptions\Transaction_Exception;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use WC_Customer;
use WC_Order;
use WP_User;

/**
 * Object representation of a customer wallet holding store credit.
 *
 * @see Balance for the current balance of the wallet
 * @see Transaction for each individual transaction in the wallet
 *
 * @since 4.0.0
 */
final class Wallet {

	/** @var Customer|null */
	private ?Customer $customer;

	/** @var int|null */
	private ?int $customer_id;

	/** @var string|null */
	private ?string $customer_email;

	/** @var string */
	private string $currency;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param int|null $customer_id user ID
	 * @param string|null $customer_email user email
	 * @param Customer|null $customer
	 * @param string|null $currency
	 */
	private function __construct( ?int $customer_id = null, ?string $customer_email = null, ?Customer $customer = null, ?string $currency = null ) {

		$this->customer       = $customer;
		$this->customer_id    = $customer_id;
		$this->customer_email = $customer_email;
		$this->currency       = $currency ?: WooCommerce::currency()->code();
	}

	/**
	 * Returns the customer account ID.
	 *
	 * @since 4.0.0
	 *
	 * @return int|null
	 */
	public function id() : ?int {

		return $this->customer_id;
	}

	/**
	 * Returns the customer account email.
	 *
	 * @since 4.0.0
	 *
	 * @return string|null
	 */
	public function email() : ?string {

		return $this->customer_email;
	}

	/**
	 * Returns the associated customer instance.
	 *
	 * @since 4.0.0
	 *
	 * @return Customer|null
	 */
	public function customer() : ?Customer {

		return $this->customer;
	}

	/**
	 * Returns the currency code for the wallet.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function currency() : string {

		return $this->currency;
	}

	/**
	 * Returns an instance of the user wallet based on the identifier provided.
	 *
	 * @since 4.0.0
	 *
	 * @param int|string|User|WC_Customer|WC_Order|WP_User $customer_identifier
	 * @param string|null $currency
	 * @return Wallet
	 */
	public static function get( $customer_identifier, ?string $currency = null ) : Wallet {

		$customer_id = $customer_email = $customer = null;

		if ( $customer_identifier instanceof WC_Order ) {
			$customer       = Customer::find( $customer_identifier->get_user() ?: $customer_identifier->get_billing_email() );
			$customer_id    = $customer ? $customer->get_id() : null;
			$customer_email = $customer ? $customer->get_email() : $customer_identifier->get_billing_email();
			$currency       = ! $currency ? $customer_identifier->get_currency() : $currency;
		} elseif ( is_numeric( $customer_identifier ) || is_object( $customer_identifier ) ) {
			$customer       = Customer::find( $customer_identifier );
			$customer_id    = $customer ? $customer->get_id() : null;
			$customer_email = $customer ? $customer->get_email() : null;
		} elseif ( is_string( $customer_identifier ) && is_email( $customer_identifier ) ) { // @phpstan-ignore-line
			$customer       = Customer::find_by_email( $customer_identifier );
			$customer_id    = $customer ? $customer->get_id() : null;
			$customer_email = $customer_identifier;
		}

		if ( ! $customer_email || ! is_email( $customer_email ) ) {
			return new self( null, null, null, $currency );
		}

		/** @var Customer|null $customer */
		return new self( $customer_id, $customer_email, $customer, $currency );
	}

	/**
	 * Determines if the wallet is for a registered customer account.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function belongs_to_registered_user() : bool {

		return $this->customer && $this->customer->is_registered();
	}

	/**
	 * Determines if the wallet is for a guest user account.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function belongs_to_guest_user() : bool {

		return ! $this->belongs_to_registered_user();
	}

	/**
	 * Returns the total balance of the wallet.
	 *
	 * @see available_balance() for the actual amount available for transactions
	 *
	 * @since 4.0.0
	 *
	 * @return float
	 */
	public function balance() : float {

		if ( ! $this->customer_email ) {
			return 0.0;
		}

		$balance = Balance::find( [ $this->customer_email => $this->currency ] );

		if ( $balance instanceof Balance ) {
			return Balance::allow_negative_balance() ? $balance->get_amount() : max( 0.0, $balance->get_amount() );
		}

		return 0.0;
	}

	/**
	 * Returns the available amount of store credit in the wallet, excluding any amounts used in pending orders.
	 *
	 * This function should be used when presenting the user with the amount currently available for transactions.
	 * Likewise, it should be used by the {@see Gateway} to determine how much store credit balance is available for the customer to pay with.
	 *
	 * @NOTE for now this does not take into account currencies, so when we introduce multi-currency support, this will need to be updated to handle that.
	 *
	 * @since 4.0.0
	 *
	 * @param int|int[]|WC_Order|WC_Order[]|null $exclude_orders
	 * @return float
	 */
	public function available_balance( $exclude_orders = null ) : float {

		/**
		 * @NOTE In the future we could remove this.
		 * Typically the available balance function is called at checkout, when we need to pull store credit from the customer's balance.
		 * So, it's important to ensure that we have migrated any legacy user account funds to store credit.
		 */
		if ( $user_id = $this->id() ) {
			Database::migrate_legacy_user_account_funds( $user_id, 'Migrated from user meta while checking for available balance' );
		}

		$balance = $this->balance();

		if ( ! $balance || ! $this->belongs_to_registered_user() ) {
			return $balance;
		}

		$exclude_order_ids = [];

		foreach ( (array) $exclude_orders as $order ) {
			if ( is_numeric( $order ) ) {
				$exclude_order_ids[] = (int) $order;
			} elseif ( $order instanceof WC_Order ) {
				$exclude_order_ids[] = $order->get_id();
			}
		}

		/** @var int[] $orders_ids */
		$orders_ids = wc_get_orders( [
			'type'        => 'shop_order',
			'limit'       => -1,
			'return'      => 'ids',
			'customer_id' => $this->id(),
			'funds_query' => [
				[
					'key'   => '_funds_removed',
					'value' => '0',
				],
				[
					'key'     => '_funds_used',
					'value'   => '0',
					'compare' => '>',
				],
			],
		] );

		foreach ( $orders_ids as $order_id ) {
			if ( in_array( $order_id, $exclude_order_ids, true ) ) {
				continue;
			}

			// @phpstan-ignore-next-line sanity checks
			if ( WC()->session && ! empty( WC()->session->order_awaiting_payment ) && intval( $order_id ) === intval( WC()->session->order_awaiting_payment ) ) {
				continue;
			}

			$order = wc_get_order( $order_id );

			$balance -= $order ? floatval( $order->get_meta( '_funds_used' ) ) : 0.0;
		}

		return Balance::allow_negative_balance() ? $balance : max( 0.0, $balance );
	}

	/**
	 * Determines if the user account has received a specific store credit from the given source.
	 *
	 * This can be used when checking against {@see Reward::is_unique()} to prevent awarding the same credit multiple times.
	 *
	 * @since 4.0.0
	 *
	 * @param int|Reward $source
	 * @param array<string, mixed> $args optional arguments to filter transactions
	 * @return bool
	 */
	public function has_credit_from( $source, array $args = [] ) : bool {

		if ( $source instanceof Reward ) {
			$source = $source->get_id();
		}

		// @phpstan-ignore-next-line type safety check
		if ( ! is_numeric( $source ) ) {
			return false;
		}

		$transactions = $this->transactions( array_merge( [
			'reward_id' => $source,
			'limit'     => 1,
		], $args ) );

		return ! $transactions->is_empty();
	}

	/**
	 * Returns all the transactions for the user account.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args optional arguments to filter transactions
	 * @return Collection<int, Transaction>
	 */
	public function transactions( array $args = [] ) : Collection {

		if ( $user_id = $this->id() ) {
			$args['customer_id'] = $user_id;
		} elseif ( $user_email = $this->email() ) {
			$args['customer_email'] = $user_email;
		} else {
			return Collection::create( [] );
		}

		return Transaction::find_many( $args );
	}

	/**
	 * Awards credit to the user account.
	 *
	 * @since 4.0.0
	 *
	 * @param Transaction $credit the credit transaction to record
	 * @return Transaction
	 * @throws Transaction_Exception if the user account does not have at least an email address
	 */
	public function credit( Transaction $credit ) : Transaction {

		if ( $this->belongs_to_registered_user() ) {
			$credit->set_customer_id( $this->id() );
			$credit->set_customer_email( $this->email() );
		} elseif ( $email = $this->email() ) {
			$credit->set_customer_email( $email );
		} else {
			throw new Transaction_Exception( esc_html__( 'Cannot award store credit to a user account without an email.', 'woocommerce-account-funds' ) );
		}

		if ( ! $credit->get_currency() ) {
			$credit->set_currency( $this->currency() );
		}

		if ( ! $credit->is_credit() ) {
			throw new Transaction_Exception( esc_html__( 'Credit transactions should have a positive amount.', 'woocommerce-account-funds' ) );
		}

		$credit->save();

		return $credit;
	}

	/**
	 * Records a debit to the user account.
	 *
	 * @since 4.0.0
	 *
	 * @param Transaction $debit the debit transaction to record
	 * @return Transaction
	 * @throws Transaction_Exception
	 */
	public function debit( Transaction $debit ) : Transaction {

		if ( $this->belongs_to_registered_user() ) {
			$debit->set_customer_id( $this->id() );
			$debit->set_customer_email( $this->email() );
		} elseif ( $email = $this->email() ) {
			$debit->set_customer_email( $email );
		} else {
			throw new Transaction_Exception( esc_html__( 'Cannot debit a user account without an email.', 'woocommerce-account-funds' ) );
		}

		if ( ! $debit->get_currency() ) {
			$debit->set_currency( $this->currency() );
		}

		// ensures the debit is a negative amount
		if ( ! $debit->is_debit() ) {
			$debit->set_amount( -abs( $debit->get_amount() ) );
		}

		if ( ! Balance::allow_negative_balance() && ( ( $this->balance() + $debit->get_amount() ) < 0.0 ) ) {
			throw new Transaction_Exception( esc_html__( 'Cannot debit an amount larger than the available store credit balance.', 'woocommerce-account-funds' ) );
		}

		$debit->save();

		return $debit;
	}

	/**
	 * Returns the formatted balance.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $avaialble_only
	 * @param int|int[]|WC_Order|WC_Order[]|null $exclude_orders $exclude_orders
	 * @return string
	 */
	public function to_string( bool $avaialble_only = false, $exclude_orders = null ) : string {

		return wc_price( $avaialble_only ? $this->available_balance( $exclude_orders ) : $this->balance(), [ 'currency' => $this->currency() ] );
	}

	/**
	 * Returns an array representation of the wallet object.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array() : array {

		$customer = $this->customer();

		return [
			'id'                => $this->id(),
			'email'             => $this->email(),
			'customer'          => $customer ? $customer->to_array() : null,
			'balance'           => $this->balance(),
			'available_balance' => $this->available_balance(),
			'currency'          => $this->currency(),
		];
	}

}
