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

namespace Kestrel\Account_Funds\Store_Credit\Wallet;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Scoped\Carbon\CarbonImmutable;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\User;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Data_Stores\Wallet_Transactions;
use Kestrel\Account_Funds\Store_Credit\Reward;

/**
 * Object representation of a store credit transaction.
 *
 * When customers earn or spend store credit, a transaction is created to record the event and the amount in a ledger.
 *
 * @see Database::store_credit_transactions_table() for the associated schema and table properties
 *
 * @since 4.0.0
 *
 * @method int get_id()
 * @method int|null get_customer_id()
 * @method $this set_customer_id(?int $customer_id)
 * @method string get_customer_email()
 * @method $this set_customer_email(string $customer_email)
 * @method int|null get_reward_id()
 * @method $this set_reward_id(?int $reward_id)
 * @method string get_currency()
 * @method $this set_currency(string $currency)
 * @method float get_amount()
 * @method $this set_amount(float $amount)
 * @method float get_balance()
 * @method $this set_balance(float $balance)
 * @method string get_event()
 * @method $this set_event(string $event)
 * @method int|null get_event_id()
 * @method $this set_event_id(?int $event_id)
 * @method string|null get_note()
 * @method $this set_note(?string $note)
 */
final class Transaction extends Model {

	/** @var ?int */
	protected ?int $customer_id = null;

	/** @var string */
	protected string $customer_email = '';

	/** @var int|null */
	protected ?int $reward_id = null;

	/** @var string */
	protected string $currency = '';

	/** @var float */
	protected float $amount = 0.0;

	/** @var float */
	protected float $balance = 0.0;

	/** @var string */
	protected string $status = '';

	/** @var string */
	protected string $event = '';

	/** @var int|null */
	protected ?int $event_id = null;

	/** @var string|null */
	protected ?string $note = null;

	/** @var string|null */
	protected ?string $created_at = null;

	/** @var string|null */
	protected ?string $modified_at = null;

	/** @var string|null */
	protected ?string $voided_at = null;

	/** @var string|null */
	protected ?string $expires_at = null;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|int|static|string|null $source
	 */
	protected function __construct( $source = null ) {

		$this->defaults = [
			'customer_id'    => null,
			'customer_email' => '',
			'reward_id'      => null,
			'currency'       => WooCommerce::currency()->code(),
			'amount'         => 0.0,
			'balance'        => 0.0,
			'status'         => Transaction_Status::default_value(),
			'event'          => Transaction_Event::default_value(),
			'event_id'       => null,
			'note'           => null,
			'created_at'     => null,
			'modified_at'    => CarbonImmutable::now()->toDateTimeString(),
			'voided_at'      => null,
			'expires_at'     => null,
		];

		parent::__construct( $source );
	}

	/**
	 * Returns the data store for this model.
	 *
	 * @since 4.0.0
	 *
	 * @return Wallet_Transactions
	 */
	protected static function get_data_store() : Data_Store {

		return Wallet_Transactions::instance();
	}

	/**
	 * Finds a credit transaction instance by its identifier.
	 *
	 * @since 4.0.0
	 *
	 * @param int $identifier
	 * @return Transaction|null
	 */
	public static function find( $identifier ) : ?Model {

		// @phpstan-ignore-next-line type safety check
		if ( ! is_numeric( $identifier ) ) {
			return null;
		}

		return self::get_data_store()->query( [ 'id' => $identifier ] )->first();
	}

	/**
	 * Retrieves all credit transactions for a specific user.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed|string|User $user user object, ID or email address
	 * @param array<string, mixed> $args
	 * @return Collection
	 */
	public static function find_for_user( $user, array $args = [] ) : Collection {

		if ( $user instanceof User ) {
			$args['customer_id'] = $user->get_id();
			unset( $args['customer_email'] );
		} elseif ( is_numeric( $user ) || ( is_string( $user ) && is_email( $user ) ) ) {
			if ( $user_object = User::find( $user ) ) {
				$args['customer_id'] = $user_object->get_id();
				unset( $args['customer_email'] );
			} elseif ( is_numeric( $user ) ) {
				$args['customer_id'] = intval( $user );
				unset( $args['customer_email'] );
			} else {
				$args['customer_email'] = $user;
				unset( $args['customer_id'] );
			}
		} else {
			return Collection::create( [] );
		}

		return self::find_many( $args );
	}

	/**
	 * Finds multiple credit transaction instances based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 *
	 * @phpstan-param array{
	 *     id?: int|int[],
	 *     customer_id?: int|int[],
	 *     customer_email?: string|string[],
	 *     credit_id?: int|int[],
	 *     status?: string[]|'commited'|'voided'|'expired',
	 *     limit?: int,
	 * } $args
	 *
	 * @return Collection<int, Transaction>
	 */
	public static function find_many( array $args = [] ) : Collection {

		return self::get_data_store()->query( $args );
	}

	/**
	 * Synchronizes customer ID and email address.
	 *
	 * This function may be used to ensure that the customer ID and email address are in sync with the data store.
	 * For example, a guest user may sign up for an account, or a registered user may change their email address.
	 *
	 * @since 4.0.0
	 *
	 * @param int $customer_id
	 * @param string $customer_email
	 * @param string|null $old_customer_email optional, when an email is updated
	 * @return bool
	 */
	public static function sync_customer_data( int $customer_id, string $customer_email, ?string $old_customer_email = null ) : bool {

		return self::get_data_store()->sync_customer_data( $customer_id, $customer_email, $old_customer_email );
	}

	/**
	 * Calculates the balance for a customer based on their identifier and currency.
	 *
	 * @since 4.0.0
	 *
	 * @param int|string $id_or_email
	 * @param string $currency
	 * @return float
	 */
	public function calculate_balance( $id_or_email, string $currency ) : float {

		return self::seed()->get_data_store()->calculate_balance( $id_or_email, $currency );
	}

	/**
	 * Returns the time when the transaction was created.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_created_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->created_at ?: 'now' );
	}

	/**
	 * Sets the time when the transaction was created.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string $created_at
	 * @return $this
	 */
	public function set_created_at( $created_at ) : self {

		if ( ! $created_at instanceof CarbonImmutable ) {
			$created_at = CarbonImmutable::parse( $created_at ?: 'now' );
		}

		$this->created_at = $created_at->toDateTimeString();

		return $this;
	}

	/**
	 * Returns the time when the transaction was last updated.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_modified_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->modified_at ?: 'now' );
	}

	/**
	 * Sets the time when the transaction was created.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string $modified_at
	 * @return $this
	 */
	public function set_modified_at( $modified_at ) : self {

		if ( ! $modified_at instanceof CarbonImmutable ) {
			$modified_at = CarbonImmutable::parse( $modified_at ?: 'now' );
		}

		$this->modified_at = $modified_at->toDateTimeString();

		return $this;
	}

	/**
	 * Returns the time when the transaction was set to expire.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable|null
	 */
	public function get_expires_at() : ?CarbonImmutable {

		return $this->expires_at ? CarbonImmutable::parse( $this->expires_at ) : null;
	}

	/**
	 * Sets the time when the transaction was set to expire.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string|null $expires_at
	 * @return $this
	 */
	public function set_expires_at( $expires_at ) : self {

		if ( $expires_at instanceof CarbonImmutable ) {
			$this->expires_at = $expires_at->toDateTimeString();
		} elseif ( is_string( $expires_at ) ) {
			$this->expires_at = CarbonImmutable::parse( $expires_at )->toDateTimeString();
		} else {
			$this->expires_at = null;
		}

		return $this;
	}

	/**
	 * Returns the time when the transaction was voided.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable|null
	 */
	public function get_voided_at() : ?CarbonImmutable {

		return $this->voided_at ? CarbonImmutable::parse( $this->voided_at ) : null;
	}

	/**
	 * Sets the time when the transaction was voided.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string|null $voided_at
	 * @return $this
	 */
	public function set_voided_at( $voided_at ) : self {

		if ( $voided_at instanceof CarbonImmutable ) {
			$this->expires_at = $voided_at->toDateTimeString();
		} elseif ( is_string( $voided_at ) ) {
			$this->expires_at = CarbonImmutable::parse( $voided_at )->toDateTimeString();
		} else {
			$this->expires_at = null;
		}

		return $this;
	}

	/**
	 * Determines if the event that originated this transaction equals the given event.
	 *
	 * @see Transaction_Event
	 *
	 * @since 4.0.0
	 *
	 * @param string|Transaction_Event $event
	 * @return bool
	 */
	public function is_event( $event ) : bool {

		$event = $event instanceof Transaction_Event ? $event->value() : $event;

		return $this->get_event() === $event;
	}

	/**
	 * Sets the transaction status.
	 *
	 * @see Transaction_Status
	 *
	 * @since 4.0.0
	 *
	 * @param string $status
	 * @return $this
	 */
	public function set_status( string $status ) : self {

		$status = Transaction_Status::tryFrom( $status );

		if ( ! $status ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid transaction status "%s".', $status ) ), '' );

			$status = Transaction_Status::default_value();
		}

		$this->status = $status;

		return $this;
	}

	/**
	 * Gets the transaction status.
	 *
	 * @see Transaction_Status
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_status() : string {

		if ( ! $this->status ) {
			$this->set_status( Transaction_Status::default_value() );
		}

		if ( ! in_array( $this->status, Transaction_Status::values(), true ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid transaction status "%s".', $this->status ) ), '' );

			$this->status = Transaction_Status::default_value();
		}

		return $this->status;
	}

	/**
	 * Determines if the transaction has the given status.
	 *
	 * @since 4.0.0
	 *
	 * @param string|Transaction_Status $status
	 * @return bool
	 */
	public function is_status( $status ) : bool {

		$status = $status instanceof Transaction_Status ? $status->value() : $status;

		return $this->get_status() === $status;
	}

	/**
	 * Returns the credit source for this transaction.
	 *
	 * This is the store credit that this transaction is associated with, if applicable.
	 *
	 * @since 4.0.0
	 *
	 * @return Reward|null
	 */
	public function get_reward_object() : ?Reward {

		$reward_id = $this->get_reward_id();

		if ( ! $reward_id ) {
			return null;
		}

		return Reward::find( $reward_id );
	}

	/**
	 * Returns the source of the transaction.
	 *
	 * This is the originator of the transaction, such as an order, product, refund, user, etc., if applicable.
	 *
	 * @since 4.0.0
	 *
	 * @return object|null
	 */
	public function get_event_object() : ?object {

		return Transaction_Event::make( $this->get_event() )->object( $this->get_event_id() ?: 0 );
	}

	/**
	 * Determines if the transaction is a credit (positive amount).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_credit() : bool {

		return $this->get_amount() > 0.0;
	}

	/**
	 * Determines if the transaction is a debit (negative amount).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_debit() : bool {

		return $this->get_amount() < 0.0;
	}

	/**
	 * Determines if the transaction is committed.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_committed() : bool {

		return $this->is_status( Transaction_Status::COMMITTED );
	}

	/**
	 * Determines if the transaction is voided.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_voided() : bool {

		return $this->is_status( Transaction_Status::VOIDED );
	}

	/**
	 * Saves the transaction, updating voided and expiration timestamps as necessary.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $save_meta_data
	 * @return Transaction
	 */
	public function save( bool $save_meta_data = true ) : Model {

		$status = $this->get_status();

		switch ( $status ) {
			case Transaction_Status::VOIDED:
				if ( ! $this->get_voided_at() ) {
					$this->set_voided_at( CarbonImmutable::now() );
				}

				break;
			case Transaction_Status::EXPIRED:
				if ( ! $this->get_expires_at() ) {
					$this->set_expires_at( CarbonImmutable::now() );
				}

				break;
			case Transaction_Status::COMMITTED:
			default:
				$this->set_status( Transaction_Status::COMMITTED );
				$this->set_voided_at( null );
				$this->set_expires_at( null );
				break;
		}

		$is_new = $this->is_new();

		if ( $is_new ) {
			$this->set_created_at( CarbonImmutable::now() );
			$this->set_modified_at( CarbonImmutable::now() );
		} else {
			$this->set_modified_at( CarbonImmutable::now() );
		}

		$transaction = parent::save();

		// recalculate the balance for the customer for any transaction that has been saved
		Balance::seed( [
			'email'    => $this->get_customer_email(),
			'amount'   => self::get_data_store()->calculate_balance( $this->get_customer_email(), $this->get_currency() ),
			'currency' => $this->get_currency(),
		] )->save();

		// update the credit source award count if this is a new transaction for a credit event
		if ( $parent_credit_object = $is_new && $transaction->is_status( Transaction_Status::COMMITTED ) && $this->is_credit() ? $this->get_reward_object() : null ) {
			$parent_credit_object->increase_award_tally( $this->get_amount() );
		}

		return $transaction;
	}

	/**
	 * Commits and saves the transaction.
	 *
	 * @return void
	 */
	public function commit() : void {

		$this->set_status( Transaction_Status::COMMITTED );
		$this->save();
	}

	/**
	 * Voids the transaction.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function void() : void {

		$this->set_status( Transaction_Status::VOIDED );
		$this->save();
	}

	/**
	 * Converts the transaction data to array.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, mixed>
	 */
	public function to_array() : array {

		$array = parent::to_array();

		// ensures dates are casted as strings
		$array['created_at']  = $this->get_created_at()->toDateTimeString();
		$array['modified_at'] = $this->get_modified_at()->toDateTimeString();
		$array['voided_at']   = $this->get_voided_at() ? $this->get_voided_at()->toDateTimeString() : null;
		$array['expires_at']  = $this->get_expires_at() ? $this->get_expires_at()->toDateTimeString() : null;

		return $array;
	}

}
