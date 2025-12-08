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
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Data_Stores\Wallet_Balances;

/**
 * Object representation of a user store credit balance.
 *
 * @see Database::store_credit_balances_table() for the associated schema and table properties
 *
 * @since 4.0.0
 *
 * @method string get_email()
 * @method $this set_email(string $email)
 * @method float get_amount()
 * @method $this set_amount(float $amount)
 * @method string get_currency()
 * @method $this set_currency(string $currency)
 */
final class Balance extends Model {

	/** @var string */
	protected string $email = '';

	/** @var float */
	protected float $amount = 0.0;

	/** @var string|null */
	protected ?string $currency = null;

	/** @var string|null */
	protected ?string $created_at = null;

	/** @var string|null */
	protected ?string $modified_at = null;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|int|static|string|null $source
	 */
	protected function __construct( $source = null ) {

		$this->defaults = [
			'email'       => '',
			'amount'      => 0.0,
			'currency'    => WooCommerce::currency()->code(),
			'created_at'  => null,
			'modified_at' => CarbonImmutable::now()->toDateTimeString(),
		];

		parent::__construct( $source );
	}

	/**
	 * Returns the data store associated with this model.
	 *
	 * @since 4.0.0
	 *
	 * @return Data_Store
	 */
	protected static function get_data_store() : Data_Store {

		return Wallet_Balances::instance();
	}

	/**
	 * Returns the balance for a use.
	 *
	 * @since 4.0.0
	 *
	 * @param array<int|string, string>|mixed $identifier email => currency, e.g. ["customer@example.org" => "USD"]
	 * @return Model|null
	 */
	public static function find( $identifier ) : ?Model {

		if ( ! is_array( $identifier ) ) {
			return null;
		}

		$email    = key( $identifier );
		$currency = current( $identifier );

		if ( ! is_string( $email ) || ! is_email( $email ) || ! is_string( $currency ) ) {
			return null;
		}

		$balance = self::get_data_store()->query( [ 'email' => $email, 'currency' => $currency ] )->first();

		return $balance instanceof Balance ? $balance : null;
	}

	/**
	 * Finds many user balances based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 * @return Collection<int, Balance>
	 */
	public static function find_many( array $args = [] ) : Collection {

		return self::get_data_store()->query( $args );
	}

	/**
	 * By default, user balances cannot be negative.
	 *
	 * This is here to provide some customization for specific use cases that require balances to go negative.
	 * It is not recommended nor officially supported to change this behavior as it may have unintended consequences with some features.
	 *
	 * @since 4.0.2
	 *
	 * @return bool
	 */
	public static function allow_negative_balance() : bool {

		/**
		 * Filter whether user balances can go negative.
		 *
		 * @since 4.0.2
		 *
		 * @param bool $allow_negative_balance default false
		 */
		return (bool) apply_filters( 'wc_account_funds_allow_negative_store_credit_balance', false );
	}

	/**
	 * Returns the time when the balance record was created.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_created_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->created_at ?: 'now' );
	}

	/**
	 * Sets the time when the balance record was created.
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
	 * Returns the time when the balance was last updated.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_modified_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->modified_at ?: 'now' );
	}

	/**
	 * Sets the time when the balance was created.
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
	 * Determines if the user balance is new.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_new() : bool {

		return ! $this->created_at || empty( $this->get_email() );
	}

	/**
	 * Saves the user balance.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $save_meta_data
	 * @return Model
	 */
	public function save( bool $save_meta_data = true ) : Model {

		if ( ! self::allow_negative_balance() && ( $this->get_amount() < 0 ) ) {
			$this->set_amount( 0.0 );
		}

		if ( $this->is_new() ) {
			$this->set_created_at( CarbonImmutable::now() );
		}

		$this->set_modified_at( CarbonImmutable::now() );

		return parent::save( $save_meta_data );
	}

	/**
	 * Returns the user balance as an array.
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

		// balances have no ID (which is otherwise inherited from the base model)
		unset( $array['id'] );

		return $array;
	}

}
