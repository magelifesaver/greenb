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

namespace Kestrel\Account_Funds\Lifecycle;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Admin\User_Profiles;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database as WordPress_Database;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Database\Table;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use Throwable;

/**
 * Database handler.
 *
 * @since 4.0.0
 */
final class Database extends WordPress_Database {

	/** @var string */
	public const STORE_CREDIT_REWARDS_TABLE = 'wc_kestrel_store_credit_rewards';

	/** @var string */
	public const STORE_CREDIT_TRANSACTIONS_TABLE = 'wc_kestrel_store_credit_transactions';

	/** @var string */
	public const STORE_CREDIT_BALANCES_TABLE = 'wc_kestrel_store_credit_balances';

	/** @var string */
	private const LEGACY_USER_ACCOUNT_FUNDS_META_KEY = 'account_funds';

	/** @var bool */
	private static bool $migrating_legacy_account_funds = false;

	/**
	 * Checks if the necessary database tables exist, and creates them if they do not.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public static function check_tables() : void {

		$tables = [
			self::STORE_CREDIT_REWARDS_TABLE      => self::store_credit_rewards_table(),
			self::STORE_CREDIT_TRANSACTIONS_TABLE => self::store_credit_transactions_table(),
			self::STORE_CREDIT_BALANCES_TABLE     => self::store_credit_balances_table(),
		];

		/** @var Table $table */
		foreach ( $tables as $table_id => $table ) {
			if ( ! $table->exists() ) {

				$table_name = $table->get_name();

				Logger::info( sprintf( 'Creating database table "%s".', $table_name ) );

				try {
					$table->create( self::schema( $table_id ) );
					Logger::info( sprintf( 'Database table "%s" created successfully.', $table_name ) );
				} catch ( Throwable $exception ) {
					Logger::emergency( sprintf( 'Error creating database table "%s": %s', $table_name, $exception->getMessage() ) );
				}
			}
		}
	}

	/**
	 * Returns the store credit configurations table.
	 *
	 * @since 4.0.0
	 *
	 * @return Table
	 */
	public static function store_credit_rewards_table() : Table {

		return Table::name( self::STORE_CREDIT_REWARDS_TABLE );
	}

	/**
	 * Returns the store credit transactions table.
	 *
	 * @since 4.0.0
	 *
	 * @return Table
	 */
	public static function store_credit_transactions_table() : Table {

		return Table::name( self::STORE_CREDIT_TRANSACTIONS_TABLE );
	}

	/**
	 * Returns the store credit user balances table.
	 *
	 * @since 4.0.0
	 *
	 * @return Table
	 */
	public static function store_credit_balances_table() : Table {

		return Table::name( self::STORE_CREDIT_BALANCES_TABLE );
	}

	/**
	 * Returns the SQL schema for the given table name.
	 *
	 * @since 4.0.0
	 *
	 * @param string $table_name
	 * @return string
	 */
	private static function schema( string $table_name ) : string {

		switch ( $table_name ) {

			case self::STORE_CREDIT_REWARDS_TABLE:
				return 'id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				code VARCHAR(36) NOT NULL UNIQUE,
				label TINYTEXT NOT NULL,
				currency VARCHAR(3) NOT NULL,
				amount DECIMAL(26,8) NOT NULL DEFAULT 0,
				percentage TINYINT(1) NOT NULL DEFAULT 0,
				type VARCHAR(20) NOT NULL,
				`trigger` VARCHAR(20) NOT NULL,
				status VARCHAR(20) NOT NULL,
				`unique` TINYINT(1) NULL DEFAULT 0,
				award_total DECIMAL(26,8) NOT NULL DEFAULT 0,
				award_budget DECIMAL(26,8) NULL DEFAULT NULL,
				award_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
				award_limit BIGINT(20) UNSIGNED NULL DEFAULT NULL,
				expires_on TIMESTAMP NULL DEFAULT NULL,
				expires_by TIMESTAMP NULL DEFAULT NULL,
				rules JSON NOT NULL,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				deleted_at TIMESTAMP NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY idx_code (code),
				KEY idx_status (status)';

			case self::STORE_CREDIT_TRANSACTIONS_TABLE:
				return 'id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				customer_id BIGINT(20) UNSIGNED NULL,
				customer_email VARCHAR(100) NOT NULL,
				reward_id BIGINT(20) UNSIGNED NULL,
				currency VARCHAR(3) NOT NULL,
				amount DECIMAL(26,8) NOT NULL DEFAULT 0,
				balance DECIMAL(26,8) NOT NULL DEFAULT 0,
				status VARCHAR(20) NOT NULL,
				event VARCHAR(20) NOT NULL,
				event_id BIGINT(20) UNSIGNED NULL,
				note TEXT NULL,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				voided_at TIMESTAMP NULL DEFAULT NULL,
				expires_at TIMESTAMP NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY idx_reward_id (reward_id),
				KEY idx_customer_id (customer_id),
				KEY idx_customer_email (customer_email)';

			case self::STORE_CREDIT_BALANCES_TABLE:
				return 'email VARCHAR(100) NOT NULL,
				amount DECIMAL(26,8) NOT NULL DEFAULT 0,
				currency VARCHAR(3) NOT NULL,
				created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				modified_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY  (email),
				UNIQUE KEY idx_email_currency (email, currency)';

			default:
				return '';
		}
	}

	/**
	 * Checks if the legacy account funds migration is in progress.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	private static function is_migrating_legacy_account_funds() : bool {

		return self::$migrating_legacy_account_funds;
	}

	/**
	 * Migrates legacy account funds from user meta to the new user wallet system.
	 *
	 * It does so in a non-destructive way, should the merchant want to keep the legacy funds or downgrade the plugin.
	 *
	 * @NOTE This method will be eventually removed in a future version.
	 *
	 * @since 4.0.0
	 *
	 * @param int $user_id
	 * @param string $note
	 * @return void
	 */
	public static function migrate_legacy_user_account_funds( int $user_id, string $note = '' ) : void {

		$migrated = get_user_meta( $user_id, 'account_funds_migrated_on', true ); // migration flag

		if ( ! empty( $migrated ) ) {
			return; // already migrated
		}

		self::$migrating_legacy_account_funds = true;

		$account_funds = get_user_meta( $user_id, self::LEGACY_USER_ACCOUNT_FUNDS_META_KEY, true );
		$invalid_funds = ! is_numeric( $account_funds ) || floatval( $account_funds ) <= 0.0;

		if ( false === $account_funds || $invalid_funds ) {

			// legacy funds cannot be negative or zero, so we can just delete the user meta and be done with it
			if ( $invalid_funds && ! Wallet\Balance::allow_negative_balance() ) {
				delete_user_meta( $user_id, self::LEGACY_USER_ACCOUNT_FUNDS_META_KEY );
			}

			self::$migrating_legacy_account_funds = false;

			return;
		}

		$currency    = WooCommerce::currency()->code();
		$user_wallet = Wallet::get( $user_id, $currency );

		try {
			$user_wallet->credit( Transaction::seed( [
				'amount' => floatval( $account_funds ),
				'event'  => Transaction_Event::MIGRATION,
				'note'   => $note,
			] ) );
		} catch ( Throwable $exception ) {
			Logger::warning( sprintf( 'Could not migrate account funds to store credit for user %1$s: %2$s', $user_id, $exception->getMessage() ) );
		}

		update_user_meta( $user_id, 'account_funds_migrated_on', gmdate( 'Y-m-d H:i:s' ) );

		self::$migrating_legacy_account_funds = false;
	}

	/**
	 * Handles the legacy user account funds.
	 *
	 * - Returns the balance from the user table to the legacy account funds user meta.
	 * - Updates the funds balance instead of the user meta directly (similar to {@see User_Profiles::save_user_profile_store_credit()}).
	 *
	 * @since 4.0.0
	 *
	 * @see get_user_meta()
	 * @see get_metadata_raw()
	 * @see update_user_meta()
	 * @see update_metadata()
	 *
	 * @return void
	 */
	public static function filter_legacy_user_account_funds() : void {
		add_filter( 'update_user_metadata', function( $check, $user_id, $meta_key, $meta_value ) {
			if ( self::LEGACY_USER_ACCOUNT_FUNDS_META_KEY === $meta_key && ! self::is_migrating_legacy_account_funds() ) {
				wc_deprecated_argument( 'update_user_meta', '4.0.0', 'Do not use the "' . self::LEGACY_USER_ACCOUNT_FUNDS_META_KEY . '" user meta key any longer. Use the ' . Wallet::class . ' class methods instead to handle account funds balances.' );

				self::migrate_legacy_user_account_funds( $user_id, 'Third party code called update_user_meta() directly.' );

				$wallet         = Wallet::get( $user_id );
				$previous_funds = $wallet->balance();
				$new_funds      = (float) wc_format_decimal( $meta_value, wc_get_price_decimals() );

				if ( $new_funds === $previous_funds || ! $wallet->email() ) {
					return false;
				}

				try {
					$current_user_id = get_current_user_id();

					if ( $new_funds > $previous_funds ) {
						$reason = sprintf(
							/* translators: Placeholder: %s - May output the shop manager ID in parenthesis or empty string when undefined */
							__( 'Store credit manually increased by a shop manager %s using update_user_meta() (deprecated).', 'woocommerce-account-funds' ),
							$current_user_id ? sprintf( '(#%d)', $current_user_id ) : ''
						);

						$wallet->credit( Transaction::seed( [
							'amount'   => floatval( $new_funds - $previous_funds ),
							'event'    => Transaction_Event::USER_ACTION,
							'event_id' => $current_user_id,
							'note'     => $reason,
						] ) );
					} elseif ( $previous_funds > $new_funds ) {
						$reason = sprintf(
							/* translators: Placeholder: %s - May output the shop manager ID in parenthesis or empty string when undefined */
							__( 'Store credit manually decreased by a shop manager %s using update_user_meta() (deprecated).', 'woocommerce-account-funds' ),
							$current_user_id ? sprintf( '(#%d)', $current_user_id ) : ''
						);

						$wallet->debit( Transaction::seed( [
							'amount'   => $previous_funds - $new_funds,
							'event'    => Transaction_Event::USER_ACTION,
							'event_id' => $current_user_id,
							'note'     => $reason,
						] ) );
					}
				} catch ( Exception $exception ) {
					Logger::warning( sprintf( 'Could not manually update store credit for user #%1$s using update_user_meta() directly: %2$s', $user_id, $exception->getMessage() ) );
				}

				return false;
			}

			return $check;
		}, 10, 4 );

		add_filter( 'get_user_metadata', function( $value, $user_id, $meta_key, $single ) {
			if ( is_numeric( $user_id ) && self::LEGACY_USER_ACCOUNT_FUNDS_META_KEY === $meta_key && ! self::is_migrating_legacy_account_funds() ) {
				$balance = Wallet::get( $user_id, WooCommerce::currency()->code() )->balance();

				return $single ? $balance : [ $balance ];
			}

			return $value;
		}, 10, 4 );
	}

}
