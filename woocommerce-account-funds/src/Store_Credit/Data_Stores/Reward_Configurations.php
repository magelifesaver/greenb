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

namespace Kestrel\Account_Funds\Store_Credit\Data_Stores;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Model\Data_Stores\Custom_Table;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database\Table;
use Kestrel\Account_Funds\Store_Credit\Data_Stores\Traits\Has_Query_Helpers;
use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use ReflectionClass;
use ReflectionProperty;

/**
 * Store credit reward configurations data store.
 *
 * @since 4.0.0
 */
final class Reward_Configurations extends Custom_Table {
	use Has_Query_Helpers;

	/** @var string internal storage name */
	protected const NAME = Database::STORE_CREDIT_REWARDS_TABLE;

	/** @var ReflectionProperty[]|null */
	private static ?array $model_properties = null;

	/**
	 * Returns the table associated with this data store.
	 *
	 * @since 4.0.0
	 *
	 * @return Table
	 */
	protected function table() : Table {

		return Database::store_credit_rewards_table();
	}

	/**
	 * Prepares raw data from the database table into the expected format of the model.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, scalar> $row_data
	 * @return array<string, mixed>
	 */
	protected function row_to_model_data( array $row_data ) : array {

		if ( null === self::$model_properties ) {
			self::$model_properties = ( new ReflectionClass( Reward::class ) )->getProperties();
		}

		$model_data = [];

		foreach ( self::$model_properties as $property ) {
			$property = $property->getName();

			switch ( $property ) {

				// strings
				case 'code':
				case 'currency':
				case 'label':
				case 'trigger':
				case 'created_at':
				case 'modified_at':
				case 'deleted_at':
					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_string( $row_data[ $property ] ) ? $row_data[ $property ] : '';

					if ( 'deleted_at' === $property && empty( $model_data[ $property ] ) ) {
						$model_data[ $property ] = null;
					}

					break;

				// floats
				case 'amount':
				case 'award_total':
				case 'award_budget':
					if ( 'award_budget' === $property ) {
						$default_value = null;
					} else {
						$default_value = 0.0;
					}

					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_numeric( $row_data[ $property ] ) ? floatval( $row_data[ $property ] ) : $default_value;
					break;

				// booleans
				case 'percentage':
				case 'unique':
					$model_data[ $property ] = wc_string_to_bool( $row_data[ $property ] ?? false );
					break;

				// integers
				case 'id':
				case 'award_count':
				case 'award_limit':
				case 'expires_by':
					if ( in_array( $property, [ 'expires_by', 'award_limit' ], true ) ) {
						$default_value = null;
					} else {
						$default_value = 0;
					}

					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_numeric( $row_data[ $property ] ) && intval( $row_data[ $property ] ) > 0 ? intval( $row_data[ $property ] ) : $default_value;
					break;

				// associative array from JSON data
				case 'rules':
					$rules = $row_data[ $property ] ?: [];

					if ( is_string( $rules ) ) {
						$rules = json_decode( $rules, true );
					}

					$model_data[ $property ] = is_array( $rules ) ? $rules : [];
					break;

				// enum values
				case 'type':
				case 'status':
					$value = isset( $row_data[ $property ] ) && is_string( $row_data[ $property ] ) ? $row_data[ $property ] : '';

					if ( 'type' === $property ) {
						$model_data[ $property ] = Reward_Type::tryFrom( $value ) ?: Reward_Type::default_value();
					} else {
						$model_data[ $property ] = Reward_Status::tryFrom( $value ) ?: Reward_Status::default_value();
					}

					break;
			}
		}

		return $model_data;
	}

	/**
	 * Creates a store credit record in the database table.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $model
	 * @return Reward
	 */
	public function create( Model &$model ) : Model {

		/** @var Reward $model */
		$model = parent::create( $model ); // @phpstan-ignore-line
		$model = Reward_Type::make( $model->get_type() )->seed( $model->to_array() ); // use the right instance type

		return $model;
	}

	/**
	 * Reads a store credit record from the database table.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $model
	 * @return void
	 */
	public function read( Model &$model ) : void {

		$id = $model->get_id();

		if ( ! $id ) {
			return;
		}

		$result = $this->table()->get_row( 'WHERE id = %d LIMIT 1', $id );

		if ( ! $result ) {
			return;
		}

		$model->set_properties( $this->row_to_model_data( (array) $result ) );
	}

	/**
	 * Updates a store credit record in the database table.
	 *
	 * @since 4.0.0
	 *
	 * @param Reward $model
	 * @return Reward
	 */
	public function update( Model &$model ) : Model {

		/** @var Reward $model */
		$model = parent::update( $model ); // @phpstan-ignore-line
		$model = $model->get_id() ? Reward_Type::make( $model->get_type() )->seed( $model->to_array() ) : $model; // use the right instance type

		return $model;
	}

	/**
	 * Queries store credit records based on the provided arguments.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 *
	 * @phpstan-param array{
	 *     id?: int|int[],
	 *     code?: string|string[],
	 *     status?: string[]|'active'|'inactive'|'depleted',
	 *     type?: string[]|'cashback'|'milestone'|'reward',
	 *     deleted?: bool,
	 *     limit?: int
	 * } $args
	 *
	 * @return Collection<int, Reward>
	 */
	public function query( array $args = [] ) : Collection {

		$query = $values = [];

		foreach ( $args as $column_name => $value ) {
			switch ( $column_name ) {
				case 'id':
					$query[]  = $this->process_integer_query( $column_name, $value );
					$values[] = $this->process_integer_value( $value );
					break;
				case 'code':
				case 'status':
				case 'type':
					// @phpstan-ignore-next-line
					if ( 'status' === $column_name && ( empty( $value ) || in_array( $value, [ 'all', 'any', '*' ], true ) ) ) {
						break;
					}

					$query[]  = $this->process_string_query( $column_name, $value );
					$values[] = $this->process_string_value( $value );
					break;

				case 'deleted':
					$query[] = $column_name . '_at IS ' . ( wc_string_to_bool( $value ) ? 'NOT NULL' : 'NULL' );
					break;
			}
		}

		if ( ! empty( $query ) ) {
			$query = ' WHERE ' . implode( ' AND ', $query );
		} else {
			$query = '';
		}

		// will always return the latest records first
		$query .= ' ORDER BY id DESC';

		if ( isset( $args['limit'] ) && intval( $args['limit'] ) > 0 ) {
			$query .= ' LIMIT ' . intval( $args['limit'] );
		}

		// ensure each instance in the results is set to the correct credit source type/class
		$results = array_map( function( $row_data ) : Reward {
			return Reward_Type::make( $row_data->type ?: null )->seed( $this->row_to_model_data( (array) $row_data ) );
		}, $this->table()->get_rows( $query, ...$values ) );

		return Collection::create( $results );
	}

	/**
	 * Updates the usage count of a store credit record.
	 *
	 * @since 4.0.0
	 *
	 * @param int $reward_id
	 * @param float $new_total_awarded
	 * @param int $new_usage_count
	 * @return void
	 */
	public function update_award_tally( int $reward_id, float $new_total_awarded, int $new_usage_count ) : void {

		Database::connect()->replace(
			$this->table()->get_name(),
			[
				'id'          => abs( $reward_id ),
				'award_total' => max( 0.0, $new_total_awarded ),
				'award_count' => max( 0, $new_usage_count ),
			],
			[
				'%d',
				'%f',
				'%d',
			]
		);
	}

	/**
	 * Counts the number of store credit records based on the provided arguments.
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
	public function count( array $args = [] ) : int {

		$where_clauses = [];

		if ( ! empty( $args['status'] ) ) {
			if ( 'deleted' === $args['status'] ) {
				$args['deleted'] = true;
			} else {
				$where_clauses[] = 'status=\'' . esc_sql( $args['status'] ) . '\'';
			}
		}

		if ( ! empty( $args['type'] ) ) {
			$where_clauses[] = 'type=\'' . esc_sql( $args['type'] ) . '\'';
		}

		if ( isset( $args['deleted'] ) ) {
			$where_clauses[] = ! $args['deleted'] ? 'deleted_at IS NULL' : 'deleted_at IS NOT NULL';
		}

		$query = ' SELECT COUNT(id) AS total FROM ' . esc_sql( $this->table()->get_name() );

		if ( ! empty( $where_clauses ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$result = Database::query( $query );

		return isset( $result[0]->total ) ? intval( $result[0]->total ) : 0;
	}

}
