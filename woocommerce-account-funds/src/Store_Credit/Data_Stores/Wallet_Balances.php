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
use Kestrel\Account_Funds\Store_Credit\Wallet\Balance;

/**
 * User balances data store.
 *
 * This data store is responsible for managing user balances in the store credit system.
 *
 * @since 4.0.0
 */
final class Wallet_Balances extends Custom_Table {

	/**
	 * Returns the table associated with this data store.
	 *
	 * @since 4.0.0
	 *
	 * @return Table
	 */
	protected function table() : Table {

		return Database::store_credit_balances_table();
	}

	/**
	 * Reads a user balance record from the database.
	 *
	 * @since 4.0.0
	 *
	 * @param Balance $model
	 * @return void
	 */
	public function read( Model &$model ) : void {

		$result = $this->table()->get_row(
			'WHERE email = %s AND currency = %s',
			[ $model->get_email(), $model->get_currency() ],
			$model
		);

		if ( ! $result ) {
			return;
		}

		$model->set_properties( (array) $result );
	}

	/**
	 * Creates a new user balance record in the database.
	 *
	 * @since 4.0.0
	 *
	 * @param Balance $model
	 * @return Balance
	 */
	public function create( Model &$model ) : Model {

		return $this->upsert( $model );
	}

	/**
	 * Updates a user balance record in the database.
	 *
	 * @since 4.0.0
	 *
	 * @param Balance $model
	 * @return Balance
	 */
	public function update( Model &$model ) : Model {

		return $this->upsert( $model );
	}

	/**
	 * Upserts a user balance record in the database.
	 *
	 * @since 4.0.0
	 *
	 * @param Balance $model
	 * @return Balance
	 */
	private function upsert( Balance $model ) : Balance {

		Database::connect()->replace(
			$this->table()->get_name(),
			[
				'email'    => $model->get_email(),
				'amount'   => $model->get_amount(),
				'currency' => $model->get_currency(),
			],
			[
				'%s',
				'%f',
				'%s',
			],
		);

		return $model;
	}

	/**
	 * Deletes a user balance record from the database.
	 *
	 * @since 4.0.0
	 *
	 * @param Balance $model
	 * @return bool
	 */
	public function delete( Model &$model ) : bool {

		return (bool) Database::connect()->delete(
			$this->table()->get_name(),
			[
				'email'    => $model->get_email(),
				'currency' => $model->get_currency(),
			],
			[
				'%s',
				'%s',
			]
		);
	}

	/**
	 * Prepares raw data from the database table into the expected format of the model.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, scalar> $args
	 * @return Collection<int, Balance>
	 */
	public function query( array $args = [] ) : Collection {

		$email    = $args['email'] ?: null;
		$currency = $args['currency'] ?: null;

		if ( empty( $email ) || ! is_email( $email ) ) {
			$balance = null;
		} elseif ( $currency ) {
			$balance = $this->table()->get_row( 'WHERE email = %s AND currency = %s', $email, $currency );
		} else {
			$balance = $this->table()->get_row( 'WHERE email = %s', $email );
		}

		return Collection::create( empty( $balance ) ? [] : [
			Balance::seed( [
				'email'       => (string) $balance->email,
				'amount'      => (float) $balance->amount,
				'currency'    => (string) $balance->currency,
				'created_at'  => (string) $balance->created_at,
				'modified_at' => (string) $balance->modified_at,
			] ),
		] );
	}

}
