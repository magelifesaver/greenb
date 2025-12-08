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

use Kestrel\Account_Funds\Scoped\Carbon\CarbonImmutable;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Contracts\Data_Store;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Data_Stores\Reward_Configurations;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Object representation of a store credit reward configuration.
 *
 * This model will hold information how the merchant intends to issue store credit to customers.
 *
 * More specific types described in {@see Reward_Type} will be used to represent specific types of store credit, such as cashback or promotions.
 *
 * @see Database::store_credit_rewards_table() for the associated schema and table properties
 *
 * @since 4.0.0
 *
 * @method int get_id()
 * @method string get_code()
 * @method $this set_code(string $code)
 * @method string get_label()
 * @method $this set_label(string $label)
 * @method string get_currency()
 * @method $this set_currency(string $currency)
 * @method float get_amount()
 * @method $this set_amount(float $amount)
 * @method bool get_percentage()
 * @method $this set_percentage(bool $percentage)
 * @method bool|null get_unique()
 * @method $this set_unique(bool|null $unique)
 * @method float get_award_total()
 * @method $this set_award_total(float $award_total)
 * @method float|null get_award_budget()
 * @method $this set_award_budget(?float $award_budget)
 * @method int get_award_count()
 * @method $this set_award_count(int $award_count)
 * @method int|null get_award_limit()
 * @method $this set_award_limit(?int $award_limit)
 * @method string|null get_expires_by()
 * @method $this set_expires_by(?string $expires_by)
 * @method array<string, mixed>|null get_rules()
 * @method $this set_rules(array $rules)
 */
class Reward extends Model {

	/** @var string default type */
	protected const TYPE = '';

	/** @var string */
	protected string $code = '';

	/** @var string */
	protected string $label = '';

	/** @var string */
	protected string $currency = '';

	/** @var float */
	protected float $amount = 0.0;

	/** @var bool */
	protected bool $percentage = false;

	/** @var string */
	protected string $type = '';

	/** @var string */
	protected string $trigger = Transaction_Event::UNDEFINED;

	/** @var string */
	protected string $status = Reward_Status::ACTIVE;

	/** @var bool */
	protected bool $unique = false;

	/** @var float */
	protected float $award_total = 0.0;

	/** @var float|null */
	protected ?float $award_budget = null;

	/** @var int */
	protected int $award_count = 0;

	/** @var int|null */
	protected ?int $award_limit = null;

	/** @var string|null */
	protected ?string $expires_on = null;

	/** @var string|null */
	protected ?string $expires_by = null;

	/** @var array<string, mixed> */
	protected array $rules = [];

	/** @var string|null */
	protected ?string $created_at = null;

	/** @var string|null */
	protected ?string $modified_at = null;

	/** @var string|null */
	protected ?string $deleted_at = null;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|int|Reward|null $source
	 */
	protected function __construct( $source = null ) {

		$this->defaults = [
			'code'         => '',
			'label'        => '',
			'currency'     => WooCommerce::currency()->code(),
			'amount'       => 0.0,
			'percentage'   => false,
			'type'         => static::TYPE ?: Reward_Type::default_value(),
			'trigger'      => Transaction_Event::UNDEFINED,
			'status'       => Reward_Status::default_value(),
			'unique'       => false,
			'award_total'  => 0.0,
			'award_budget' => null,
			'award_count'  => 0,
			'award_limit'  => null,
			'expires_on'   => null,
			'expires_by'   => null,
			'rules'        => [],
			'created_at'   => null,
			'modified_at'  => CarbonImmutable::now()->toDateTimeString(),
			'deleted_at'   => null,
		];

		if ( is_array( $source ) && isset( $source['rules'] ) && ! is_array( $source['rules'] ) ) {
			$source['rules'] = is_string( $source['rules'] ) ? (array) json_decode( $source['rules'], true ) : [];
		}

		parent::__construct( $source );
	}

	/**
	 * Returns the data store for the reward model.
	 *
	 * @since 4.0.0
	 *
	 * @return Reward_Configurations
	 */
	protected static function get_data_store() : Data_Store {

		return Reward_Configurations::instance();
	}

	/**
	 * Finds a reward instance by its identifier.
	 *
	 * @since 4.0.0
	 *
	 * @param int $identifier
	 * @return Reward|null
	 */
	public static function find( $identifier ) : ?Model {

		// @phpstan-ignore-next-line type safety check
		if ( ! is_numeric( $identifier ) ) {
			return null;
		}

		$args = [ 'id' => intval( $identifier ) ];

		if ( '' !== static::TYPE ) {
			$args['type'] = static::TYPE;
		}

		return self::get_data_store()->query( $args )->first();
	}

	/**
	 * Finds multiple reward configuration instances based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args optional arguments to filter the credits
	 *
	 * @phpstan-param array{
	 *     id?: int|int[],
	 *     code?: string|string[],
	 *     status?: string[]|'active'|'inactive'|'depleted',
	 *     type?: string[]|'cashback'|'milestone'|'reward',
	 *     deleted?: bool,
	 * } $args
	 *
	 * @return Collection<int, Reward>
	 */
	public static function find_many( array $args = [] ) : Collection {

		$args = wp_parse_args(
			$args,
			[
				'deleted' => false, // by default, do not return trashed items
			]
		);

		if ( '' !== static::TYPE ) {
			$args['type'] = static::TYPE;
		}

		return self::get_data_store()->query( $args );
	}

	/**
	 * Returns the last reward configuration based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 * @return Reward|null
	 */
	protected static function last( array $args = [] ) : ?Model {

		$args['limit'] = 1;

		if ( '' !== static::TYPE ) {
			$args['type'] = static::TYPE;
		}

		return self::get_data_store()->query( $args )->last();
	}

	/**
	 * Returns the time when the configuration was created.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_created_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->created_at ?: 'now' );
	}

	/**
	 * Sets the time when the configuration was created.
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
	 * Returns the time when the configuration was last updated.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable
	 */
	public function get_modified_at() : CarbonImmutable {

		return CarbonImmutable::parse( $this->modified_at ?: 'now' );
	}

	/**
	 * Sets the time when the configuration was created.
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
	 * Returns the time when the configuration was trashed.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable|null
	 */
	public function get_deleted_at() : ?CarbonImmutable {

		return $this->deleted_at ? CarbonImmutable::parse( $this->deleted_at ) : null;
	}

	/**
	 * Sets the time when the configuration was deleted.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string|null $deleted_at
	 * @return $this
	 */
	public function set_deleted_at( $deleted_at ) : self {

		if ( $deleted_at instanceof CarbonImmutable ) {
			$this->deleted_at = $deleted_at->toDateTimeString();
		} elseif ( is_string( $deleted_at ) ) {
			$this->deleted_at = CarbonImmutable::parse( $deleted_at )->toDateTimeString();
		} else {
			$this->deleted_at = null;
		}

		return $this;
	}

	/**
	 * Returns the time when the rewarded amount should expire on.
	 *
	 * @since 4.0.0
	 *
	 * @return CarbonImmutable|null
	 */
	public function get_expires_on() : ?CarbonImmutable {

		return $this->expires_on ? CarbonImmutable::parse( $this->expires_on ) : null;
	}

	/**
	 * Sets the time when the rewarded amount should expire on.
	 *
	 * @since 4.0.0
	 *
	 * @param CarbonImmutable|string|null $expires_on
	 * @return $this
	 */
	public function set_expires_on( $expires_on ) : self {

		if ( $expires_on instanceof CarbonImmutable ) {
			$this->expires_on = $expires_on->toDateTimeString();
		} elseif ( is_string( $expires_on ) ) {
			$this->expires_on = CarbonImmutable::parse( $expires_on )->toDateTimeString();
		} else {
			$this->expires_on = null;
		}

		return $this;
	}

	/**
	 * Determines if the reward can only be redeemed once per customer.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_unique() : bool {

		return wc_string_to_bool( $this->get_unique() );
	}

	/**
	 * Determines if the reward is percentage-based.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_percentage() : bool {

		return wc_string_to_bool( $this->get_percentage() );
	}

	/**
	 * Determines if the reward has a budget (a maximum amount that can be awarded).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function has_award_budget() : bool {

		$budget = $this->get_award_budget();

		return is_numeric( $budget ) && floatval( $budget ) > 0;
	}

	/**
	 * Determines if the reward has an award limit (how many times it can be awarded to customers).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function has_award_limit() : bool {

		$usage_limit = $this->get_award_limit();

		return is_numeric( $usage_limit ) && $usage_limit > 0;
	}

	/**
	 * Determines the number of times the rewarded can be awarded again, by how many times.
	 *
	 * @since 4.0.0
	 *
	 * @return int|null
	 */
	public function remaining_award_triggers() : ?int {

		if ( ! $this->has_award_limit() ) {
			return null;
		}

		$usage_limit = $this->get_award_limit();
		$usage_count = $this->get_award_count();

		return max( 0, (int) $usage_limit - $usage_count );
	}

	/**
	 * Determines if the reward is depleted (can no longer be awarded because the award limit has been hit).
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_depleted() : bool {

		return $this->is_status( Reward_Status::DEPLETED ) || ( $this->has_award_limit() && $this->remaining_award_triggers() === 0 );
	}

	/**
	 * Sets the reward type.
	 *
	 * @see Reward_Type
	 *
	 * @since 4.0.0
	 *
	 * @param string $type
	 * @return $this
	 */
	public function set_type( string $type ) : self {

		$type = Reward_Type::tryFrom( $type );

		if ( ! $type ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit type "%s".', $type ) ), '' );

			$type = Reward_Type::default_value();
		}

		$this->type = $type;

		return $this;
	}

	/**
	 * Returns the reward type.
	 *
	 * @see Reward_Type
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_type() : string {

		if ( ! $this->type ) {
			$this->set_type( static::TYPE ?: Reward_Type::default_value() );
		}

		if ( ! in_array( $this->type, Reward_Type::values(), true ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit type "%s".', $this->type ) ), '' );

			$this->type = Reward_Type::default_value();
		}

		return $this->type;
	}

	/**
	 * Determines if the reward is of a given type.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward_Type|string $type
	 * @return bool
	 */
	public function is_type( $type ) : bool {

		$type = $type instanceof Reward_Type ? $type->value() : $type;

		return $this->get_type() === $type;
	}

	/**
	 * Gets the reward trigger (how store credit will be awarded for this type of reward).
	 *
	 * @see Transaction_Event
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_trigger() : string {

		if ( ! $this->trigger ) {
			$this->set_trigger( Transaction_Event::UNDEFINED );
		}

		if ( ! in_array( $this->trigger, Transaction_Event::values(), true ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit source trigger "%s".', $this->trigger ) ), '' );

			$this->trigger = Transaction_Event::UNDEFINED;
		}

		return $this->trigger;
	}

	/**
	 * Sets the trigger that will be used to award store credit to a customer for this type of reward.
	 *
	 * @see Transaction_Event
	 *
	 * @since 4.0.0
	 *
	 * @param string $trigger
	 * @return $this
	 */
	public function set_trigger( string $trigger ) : self {

		$trigger = Transaction_Event::tryFrom( $trigger );

		if ( ! $trigger ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit source trigger "%s".', $trigger ) ), '' );

			$trigger = Transaction_Event::UNDEFINED;
		}

		$this->trigger = $trigger;

		return $this;
	}

	/**
	 * Determines if the reward configuration has a given trigger.
	 *
	 * @see Transaction_Event
	 *
	 * @since 4.0.0
	 *
	 * @param string $trigger
	 * @return bool
	 */
	public function is_trigger( string $trigger ) : bool {

		return Transaction_Event::tryFrom( $trigger ) === $this->get_trigger();
	}

	/**
	 * Sets the reward configuration status.
	 *
	 * @see Reward_Status
	 *
	 * @since 4.0.0
	 *
	 * @param string $status
	 * @return $this
	 */
	public function set_status( string $status ) : self {

		$status = Reward_Status::tryFrom( $status );

		if ( ! $status ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit source status "%s".', $status ) ), '' );

			$status = Reward_Status::default_value();
		}

		$this->status = $status;

		return $this;
	}

	/**
	 * Gets the reward configuration status.
	 *
	 * @see Reward_Status
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_status() : string {

		if ( ! $this->status ) {
			$this->set_status( Reward_Status::default_value() );
		}

		if ( ! in_array( $this->status, Reward_Status::values(), true ) ) {
			_doing_it_wrong( __METHOD__, esc_html( sprintf( 'Invalid credit source status "%s".', $this->status ) ), '' );

			$this->status = Reward_Status::default_value();
		}

		return $this->status;
	}

	/**
	 * Determines if the reward configuration has a given status.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward_Status|string $status
	 * @return bool
	 */
	public function is_status( $status ) : bool {

		$status = $status instanceof Reward_Status ? $status->value() : $status;

		return $this->get_status() === $status;
	}

	/**
	 * Determines if the reward configuration is in the trash.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_trashed() : bool {

		return $this->get_deleted_at() !== null;
	}

	/**
	 * Increases the tally for the total amount of awarded store credit for this configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param float $amount
	 * @param int $count
	 * @return $this
	 */
	public function increase_award_tally( float $amount = 0.0, int $count = 1 ) : self {

		$new_award_total = $this->get_award_total() + abs( $amount );
		$new_award_count = $this->get_award_count() + abs( $count );

		$this->set_award_total( $new_award_total );
		$this->set_award_count( $new_award_count );

		if ( ! $this->is_new() ) {
			$this->get_data_store()->update_award_tally( $this->get_id(), $new_award_total, $new_award_count );
			$this->save();
		}

		return $this;
	}

	/**
	 * Decreases the tally for the total amount of awarded store credit for this configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param float $amount
	 * @param int $count
	 * @return $this
	 */
	public function decrease_award_tally( float $amount = 0.0, int $count = 1 ) : self {

		$new_award_total = max( 0.0, $this->get_award_total() - abs( $amount ) );
		$new_award_count = max( 0, $this->get_award_count() - abs( $count ) );

		$this->set_award_total( $new_award_total );
		$this->set_award_count( $new_award_count );

		if ( ! $this->is_new() ) {
			$this->get_data_store()->update_award_tally( $this->get_id(), $new_award_total, $new_award_count );
			$this->save();
		}

		return $this;
	}

	/**
	 * Generates a unique code for the reward.
	 *
	 * @since 4.0.0
	 *
	 * @return string e.g., "1236-8633-B6E0-ACB4"
	 */
	private function generate_code() : string {

		$code = str_replace( '-', '', wp_generate_uuid4() );
		// trim down to 16 characters
		$code = substr( $code, 0, 16 );
		// add dashes every 4 characters
		$code = preg_replace( '/(.{4})/', '$1-', $code );

		// return the code in uppercase
		return strtoupper( rtrim( $code, '-' ) );
	}

	/**
	 * Saves the reward configuration to the database.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $save_meta_data
	 * @return Model&Reward
	 */
	public function save( bool $save_meta_data = true ) : Model {

		if ( ! $this->get_code() ) {
			$this->set_code( $this->generate_code() );
		}

		if ( ! $this->get_label() ) {
			$this->set_label( sprintf(
				'%s %s',
				Reward_Type::make( $this->get_type() )->label_singular(),
				CarbonImmutable::now()->format( 'Y-m-d' )
			) );
		}

		// deplete the credit source if the award limit has been hit
		if ( $this->get_award_limit() && $this->get_award_count() === $this->get_award_limit() ) {
			$this->set_status( Reward_Status::DEPLETED );
		}

		if ( $this->is_new() ) {
			$this->set_created_at( CarbonImmutable::now() );
			$this->set_modified_at( CarbonImmutable::now() );
		} else {
			$this->set_modified_at( CarbonImmutable::now() );
		}

		return parent::save( $save_meta_data );
	}

	/**
	 * Soft-deletes the reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return Model&Reward
	 */
	public function trash() : Model {

		if ( $this->get_deleted_at() !== null ) {
			return $this;
		}

		$deleted_at = CarbonImmutable::now();

		$this->set_deleted_at( $deleted_at );
		$this->save();

		return $this;
	}

	/**
	 * Restores the reward configuration from the trash.
	 *
	 * @since 4.0.0
	 *
	 * @return Model&Reward
	 */
	public function restore() : Model {

		$this->set_deleted_at( null );
		$this->save();

		return $this;
	}

	/**
	 * Counts the number of reward configurations based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 *
	 * @phpstan-param array{
	 *     status?: string,
	 *     type?: string,
	 *     deleted?: bool,
	 * } $args
	 *
	 * @return int
	 */
	public static function count( array $args = [] ) : int {

		$args = wp_parse_args( $args, [
			'deleted' => false, // by default, do not count trashed items
			'type'    => static::TYPE,
		] );

		return self::get_data_store()->count( $args );
	}

	/**
	 * Returns the URL to manage reward configurations.
	 *
	 * @since 4.0.0
	 *
	 * @param string|null $type the type of reward configuration to manage, defaults to this class current type
	 * @param array<string, mixed> $query_args optional query arguments to append to the URL
	 * @return string
	 */
	public static function manage_items_url( ?string $type = null, array $query_args = [] ) : string {

		$url = admin_url( 'admin.php?page=store-credit-' . ( $type ?: static::TYPE ) );

		if ( ! empty( $query_args ) ) {
			$url = add_query_arg( $query_args, $url );
		}

		return $url;
	}

	/**
	 * Returns the URL to add a new reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param string|null $type the type of reward configuration to manage, defaults to this class current type
	 * @param array<string, mixed> $query_args optional query arguments to append to the URL
	 * @return string
	 */
	public static function add_new_item_url( ?string $type = null, array $query_args = [] ) : string {

		return static::manage_items_url( $type ?: static::TYPE, array_merge( ['add_new' => $type ?: static::TYPE ], $query_args ) );
	}

	/**
	 * Returns the URL to the edit screen for this reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $query_args optional query arguments
	 * @return string
	 */
	public function edit_item_url( array $query_args = [] ) : string {

		return static::manage_items_url( $this->get_type(), array_merge( ['edit' => $this->get_id() ], $query_args ) );
	}

	/**
	 * Returns the URL to move this reward configuration to the trash.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $query_args optional query arguments
	 * @return string
	 */
	public function trash_url( array $query_args = [] ) : string {

		return wp_nonce_url( static::manage_items_url( $this->get_type(), array_merge( ['trash' => $this->get_id() ], $query_args ) ), 'trash' );
	}

	/**
	 * Returns the URL to restore this reward configuration from the trash.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $query_args optional query arguments
	 * @return string
	 */
	public function restore_item_url( array $query_args = [] ) : string {

		return wp_nonce_url( static::manage_items_url( $this->get_type(), array_merge( ['restore' => $this->get_id() ], $query_args ) ), 'restore' );
	}

	/**
	 * Returns the URL to permanently delete this reward configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $query_args optional query arguments
	 * @return string
	 */
	public function delete_item_url( array $query_args = [] ) : string {

		return wp_nonce_url( static::manage_items_url( $this->get_type(), array_merge( ['delete' => $this->get_id() ], $query_args ) ), 'delete' );
	}

	/**
	 * Converts the reward model data to an array suitable for storage.
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
		$array['deleted_at']  = $this->get_deleted_at() ? $this->get_deleted_at()->toDateTimeString() : null;
		$array['expires_on']  = $this->get_expires_on() ? $this->get_expires_on()->toDateTimeString() : null;

		// ensures rules are stored as a JSON string
		$rules          = ! empty( $array['rules'] ) ? wp_json_encode( (array) $array['rules'] ) : null;
		$array['rules'] = empty( $rules ) ? '{}' : $rules;

		return $array;
	}

}
