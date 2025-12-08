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
use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Lifecycle\Milestones\First_Store_Credit_Awarded;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WP_User;

/**
 * User credit handler.
 *
 * @since 3.1.0
 *
 * @method static Plugin plugin()
 */
final class Users {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		// display information about the store credit awarded upon account signup in the WooCommerce signup form
		self::add_action( 'woocommerce_register_form', [ $this, 'display_signup_milestone_reward_information' ] );
		// award store credit on customer account signup if milestone awards are available
		self::add_action( 'user_register', [ $this, 'award_store_credit_upon_account_sign_up' ], PHP_INT_MAX, 2 );
		// if a user updates their email address, we need to update the wallet email as well
		self::add_action( 'profile_update', [ $this, 'update_wallet_on_user_email_update' ], PHP_INT_MAX, 3 );
	}

	/**
	 * Displays account signup reward information in the WooCommerce signup form.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function display_signup_milestone_reward_information() : void {

		/**
		 * Filters whether to display the account signup milestone reward information.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $display_signup_milestone_reward_information
		 */
		if ( false === apply_filters( 'wc_account_funds_should_display_signup_milestone_reward_information', true ) ) {
			return;
		}

		$rewards = $this->applicable_signup_rewards();
		$amount  = array_reduce( $rewards, static function( float $carry, Transaction $reward ) {
			return $carry + $reward->get_amount();
		}, 0.0 );

		if ( $amount > 0.0 ) {
			echo '<p class="store-credit-reward store-credit-milestone store-credit-signup-reward">';
			/* translators: Placeholders: %1$s is the amount of store credit, %2$s is the pluralized store credit label */
			printf( __( 'You will receive up to %1$s in %2$s when you create your account!', 'woocommerce-account-funds' ), '<strong>' . wc_price( $amount ) . '</strong>', wc_account_funds_store_credit_label( 'plural' ) ); // phpcs:ignore
			echo '</p>';
		}
	}

	/**
	 * Returns a list of applicable signup rewards.
	 *
	 * @since 3.1.0
	 *
	 * @param Wallet|null $wallet
	 * @return array<int, Transaction>
	 */
	protected function applicable_signup_rewards( ?Wallet $wallet = null ) : array {

		$applicable = [];
		$milestones = Milestone::find_many( [
			'status'  => Reward_Status::ACTIVE,
			'deleted' => false,
		] );

		foreach ( $milestones as $milestone ) {

			if ( $milestone->is_depleted() || $milestone->get_trigger() !== Transaction_Event::ACCOUNT_SIGNUP || ( $wallet && $wallet->has_credit_from( $milestone ) ) ) {
				continue;
			}

			$applicable[] = Transaction::seed()
				->set_amount( $milestone->get_amount() )
				->set_event( Transaction_Event::ACCOUNT_SIGNUP )
				->set_reward_id( $milestone->get_id() );
		}

		return $applicable;
	}

	/**
	 * Awards store credit on customer account signup.
	 *
	 * @since 3.1.0
	 *
	 * @param int|mixed|numeric-string $user_id
	 * @param array<string, mixed>|mixed $user_data
	 */
	protected function award_store_credit_upon_account_sign_up( $user_id, $user_data ) : void {

		if ( is_array( $user_data ) && isset( $user_data['user_email'] ) ) {
			$user_email = $user_data['user_email'];
		} elseif ( is_object( $user_data ) && isset( $user_data->user_email ) ) {
			$user_email = $user_data->user_email;
		}

		if ( ! is_numeric( $user_id ) || empty( $user_email ) || ! is_email( $user_email ) ) {
			return; // shouldn't happen
		}

		$wallet = Wallet::get( $user_email );

		if ( $wallet->email() && $wallet->transactions( ['limit' => 1 ] )->count() >= 1 ) {
			// this will link up a guest user sharing the same email address to the newly registered user so they have access to their store credit
			Transaction::sync_customer_data( (int) $user_id, $user_email );
		}

		$awards = 0;

		foreach ( $this->applicable_signup_rewards( $wallet ) as $reward ) {
			try {
				$reward = $reward->set_event_id( (int) $user_id );

				$wallet->credit( $reward );

				$awards++;
			} catch ( Exception $exception ) {
				Logger::warning( sprintf( 'Could not award store credit #%s upon user #%s account signup: %s', $reward->get_id(), $user_id, $exception->getMessage() ) );
				continue;
			}
		}

		if ( $awards > 0 ) {
			First_Store_Credit_Awarded::trigger();
		}
	}

	/**
	 * Updates the user's wallet email when their user email is updated.
	 *
	 * @since 4.0.0
	 *
	 * @param int|mixed|numeric-string $user_id
	 * @param mixed|WP_User $old_user_data
	 * @param array<string, mixed>|mixed $current_user_data
	 * @return void
	 */
	protected function update_wallet_on_user_email_update( $user_id, $old_user_data, $current_user_data ) : void {

		if ( ! is_numeric( $user_id ) || ! is_object( $old_user_data ) || ! is_array( $current_user_data ) || empty( $current_user_data ) ) {
			return;
		}

		$user = get_user_by( 'id', (int) $user_id );

		$new_email = $user ? $user->user_email : ( $current_user_data['user_email'] ?? '' );
		$old_email = $old_user_data->user_email ?: '';

		if ( $new_email === $old_email || ! is_email( $new_email ) || ! is_email( $old_email ) ) {
			return;
		}

		$wallet = Wallet::get( (int) $user_id );

		if ( ! $wallet->id() || $wallet->transactions( [ 'limit' => 1 ] )->count() === 0 ) {
			return;
		}

		Transaction::sync_customer_data( $wallet->id(), $new_email, $old_email );
	}

	/**
	 * Gets a user's store credit balance.
	 *
	 * Use {@see Wallet::balance()} instead. The {@see Wallet::available_balance()} will take into account held funds from orders in progress as well.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @param int $user_id
	 * @param bool $deprecated
	 * @return float
	 */
	public static function get_user_available_funds( int $user_id, bool $deprecated = true ) : float {

		if ( $deprecated ) {
			wc_deprecated_function( __METHOD__, '4.0.0', Wallet::class . '::balance()' );
		}

		Database::migrate_legacy_user_account_funds( $user_id, 'Direct call from ' . __METHOD__ );

		return Wallet::get( $user_id )->balance();
	}

	/**
	 * Increases a user's store credit balance by a given amount.
	 *
	 * Third parties should not use this method, and rather use {@see Wallet::credit()} instead.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @param int $user_id
	 * @param float $increase_amount
	 * @param string $event
	 * @param int|null $event_id
	 * @return bool success
	 */
	public static function increase_user_funds( int $user_id, float $increase_amount, string $event = Transaction_Event::UNDEFINED, ?int $event_id = null ) : bool {

		$user_wallet = Wallet::get( $user_id );

		if ( has_filter( 'woocommerce_account_funds_add_funds' ) ) {
			wc_deprecated_hook( 'woocommerce_account_funds_add_funds', '4.0.0' );
		}

		try {
			$user_wallet->credit( Transaction::seed( [
				'amount'   => abs( $increase_amount ),
				'event'    => Transaction_Event::make( $event )->value(),
				'event_id' => $event_id,
			] ) );
		} catch ( Exception $exception ) {
			Logger::warning( sprintf( 'Could not increase account funds for user %1$s: %2$s', $user_id, $exception->getMessage() ) );

			return false;
		}

		return true;
	}

	/**
	 * Decreases the user's store credit balance by a given amount.
	 *
	 * Third parties should not use this method, and rather use {@see Wallet::debit()} instead.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @param int $user_id
	 * @param float $decrease_amount
	 * @param string $event
	 * @param int|null $event_id
	 * @return bool success
	 */
	public static function decrease_user_funds( int $user_id, float $decrease_amount, string $event = Transaction_Event::UNDEFINED, ?int $event_id = null ) : bool {

		if ( ! $decrease_amount ) {
			return false;
		}

		$user_wallet = Wallet::get( $user_id );

		if ( has_filter( 'woocommerce_account_funds_remove_funds' ) ) {
			wc_deprecated_hook( 'woocommerce_account_funds_add_funds', '4.0.0' );
		}

		try {
			$user_wallet->debit( Transaction::seed( [
				'amount'   => $decrease_amount,
				'event'    => Transaction_Event::make( $event )->value(),
				'event_id' => $event_id,
			] ) );
		} catch ( Exception $exception ) {
			Logger::warning( sprintf( 'Could not decrease account funds for user %1$s: %2$s', $user_id, $exception->getMessage() ) );

			return false;
		}

		return true;
	}

	/**
	 * Determines if a user has store credit.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @param int $user_id
	 * @return bool
	 */
	public static function user_has_available_funds( int $user_id ) : bool {

		wc_deprecated_function( __METHOD__, '4.0.0', Wallet::class . '::balance()' );

		return self::get_user_available_funds( $user_id, false ) > 0;
	}

	/**
	 * Gets the amount of store credit granted to a user upon registration.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return float
	 */
	public static function get_funds_granted_upon_user_registration() : float {

		// this is placed here opportunistically to ensure that the legacy user account funds are migrated
		if ( $user_id = get_current_user_id() ) {
			Database::migrate_legacy_user_account_funds( $user_id, 'Direct call from ' . __METHOD__ );
		}

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return 0.0;
	}

}

class_alias(
	__NAMESPACE__ . '\Users',
	'\Kestrel\WooCommerce\Account_Funds\Users'
);
