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
use Kestrel\Account_Funds\Store_Credit\Wallet\Balance;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Status;
use ReflectionClass;
use ReflectionProperty;

/**
 * Credit transactions data store.
 *
 * @since 4.0.0
 */
final class Wallet_Transactions extends Custom_Table {
	use Has_Query_Helpers;

	/** @var string internal storage name */
	protected const NAME = Database::STORE_CREDIT_TRANSACTIONS_TABLE;

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

		return Database::store_credit_transactions_table();
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
			self::$model_properties = ( new ReflectionClass( Transaction::class ) )->getProperties();
		}

		$model_data = [];

		foreach ( self::$model_properties as $property ) {
			$property = $property->getName();

			switch ( $property ) {

				// float
				case 'amount':
				case 'balance':
					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_numeric( $row_data[ $property ] ) ? floatval( $row_data[ $property ] ) : 0.0;
					break;

				// strings
				case 'currency':
				case 'note':
				case 'customer_email':
				case 'created_at':
				case 'modified_at':
					$default_value = 'customer_email' === $property ? '' : null;

					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_string( $row_data[ $property ] ) ? trim( $row_data[ $property ] ) : $default_value;
					break;

				// integers
				case 'id':
				case 'reward_id':
				case 'customer_id':
				case 'event_id':
					if ( in_array( $property, [ 'customer_id', 'reward_id', 'source_id', 'voided_at', 'expires_at' ], true ) ) {
						$default_value = null;
					} else {
						$default_value = 0;
					}

					$model_data[ $property ] = isset( $row_data[ $property ] ) && is_numeric( $row_data[ $property ] ) && intval( $row_data[ $property ] ) > 0 ? intval( $row_data[ $property ] ) : $default_value;
					break;

				// enum values
				case 'status':
				case 'event':
					$value = isset( $row_data[ $property ] ) && is_string( $row_data[ $property ] ) ? $row_data[ $property ] : '';

					if ( 'status' === $property ) {
						$model_data[ $property ] = Transaction_Status::make( $value )->value();
					} else {
						$model_data[ $property ] = Transaction_Event::make( $value )->value();
					}

					break;
			}
		}

		return $model_data;
	}

	/**
	 * Creates a new transaction record in the database table.
	 *
	 * @since 4.0.0
	 *
	 * @param Transaction $model
	 * @return Transaction
	 */
	public function create( Model &$model ) : Model {

		// always set the balance to the current balance + amount of the transaction being persisted in storage
		$model->set_balance( $this->calculate_balance( $model->get_customer_email(), $model->get_currency() ) + $model->get_amount() );

		return parent::create( $model ); // @phpstan-ignore-line
	}

	/**
	 * Returns the sum of all transactions for a user ID or email.
	 *
	 * @since 4.0.0
	 *
	 * @param int|string $id_or_email
	 * @param string $currency
	 * @return float
	 */
	public function calculate_balance( $id_or_email, string $currency ) : float {

		if ( is_numeric( $id_or_email ) ) {
			$column = 'customer_id';
			$value  = intval( $id_or_email );
		} else {
			$column = 'customer_email';
			$value  = is_email( $id_or_email ) ? $id_or_email : '';
		}

		$status = Transaction_Status::COMMITTED;
		$query  = '
			SELECT SUM(amount) AS total
			FROM ' . esc_sql( $this->table()->get_name() ) . '
			WHERE ' . esc_sql( $column ) . ' = \'' . esc_sql( $value ) . '\'
			AND currency = \'' . esc_sql( $currency ) . '\'
			AND status = \'' . esc_sql( $status ) . '\';
		';

		$result = current( Database::query( $query ) );

		return Balance::allow_negative_balance()
			? floatval( $result->total ?? 0.0 )
			: max( 0.0, floatval( $result->total ?? 0.0 ) );
	}

	/**
	 * Synchronizes a customer ID and email address in the store credit transactions table.
	 *
	 * @since 4.0.0
	 *
	 * @param int $customer_id
	 * @param string $customer_email
	 * @param string|null $old_customer_email
	 * @return bool
	 */
	public function sync_customer_data( int $customer_id, string $customer_email, ?string $old_customer_email = null ) : bool {

		if ( ! $old_customer_email ) {
			$success = (bool) Database::connect()->replace(
				$this->table()->get_name(),
				[
					'customer_id'    => $customer_id,
					'customer_email' => $customer_email,
				],
				[
					'%d',
					'%s',
				]
			);
		} else {
			$success = (bool) Database::connect()->update(
				$this->table()->get_name(),
				[
					'customer_id'    => $customer_id,
					'customer_email' => $customer_email,
				],
				[
					'customer_email' => $old_customer_email,
				],
				[
					'%d',
					'%s',
				],
				[
					'%s',
				]
			);
		}

		// if no old email is specified, this could be a case where a guest user is now a registered user and no email change occurred
		if ( ! $success || ! $old_customer_email ) {
			return $success;
		}

		// also update any existing balances for the old email to the new email
		$balances = Balance::find_many( [ 'email' => $old_customer_email ] );

		foreach ( $balances as $balance ) {
			if ( $balance->get_email() === $old_customer_email ) {

				$old_properties          = $balance->to_array();
				$old_properties['email'] = $customer_email;

				unset( $old_properties['modified_at'] );

				Balance::seed( $old_properties )->save();

				$balance->delete();
			}
		}

		return true;
	}

	/**
	 * Reads a store credit record from the database table.
	 *
	 * @since 4.0.0
	 *
	 * @param Transaction $model
	 * @return void
	 */
	public function read( Model &$model ) : void {

		$id = $model->get_id();

		if ( ! $id ) {
			return;
		}

		$result = $this->table()->get_row( 'WHERE id = %d', $id );

		if ( ! $result ) {
			return;
		}

		$model->set_properties( $this->row_to_model_data( (array) $result ) );
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
	 *     customer_id?: int|int[],
	 *     customer_email?: string|string[],
	 *     credit_id?: int|int[],
	 *     status?: string[]|'commited'|'voided'|'expired',
	 *     limit?: int,
	 * } $args
	 *
	 * @return Collection<int, Transaction>
	 */
	public function query( array $args = [] ) : Collection {

		$query = $values = [];

		foreach ( $args as $column_name => $value ) {
			switch ( $column_name ) {
				case 'id':
				case 'customer_id':
				case 'reward_id':
					$query[]  = $this->process_integer_query( $column_name, $value );
					$values[] = $this->process_integer_value( $value );
					break;
				case 'customer_email':
				case 'status':
					$query[]  = $this->process_string_query( $column_name, $value );
					$values[] = $this->process_string_value( $value );
					break;
			}
		}

		if ( ! empty( $query ) ) {
			$query = 'WHERE ' . implode( ' AND ', $query );
		} else {
			$query = '';
		}

		if ( isset( $args['limit'] ) && intval( $args['limit'] ) > 0 ) {
			$query .= ' LIMIT ' . intval( $args['limit'] );
		}

		$results = array_map( function( $row_data ) : Transaction {
			return Transaction::seed( $this->row_to_model_data( (array) $row_data ) );
		}, $this->table()->get_rows( $query, ...$values ) );

		return Collection::create( $results );
	}

}
