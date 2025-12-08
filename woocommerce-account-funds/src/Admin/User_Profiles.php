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

namespace Kestrel\Account_Funds\Admin;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Lifecycle\Database;
use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Admin\Notices\Notice;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WP_User;

/**
 * Admin user profiles handler.
 *
 * @TODO This allows for direct editing of the store credit balance for users, which eventually we will move onto a dedicated screen.
 *
 * @since 4.0.0
 */
final class User_Profiles {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		// list screen
		self::add_filter( 'manage_users_columns', [ $this, 'add_store_credit_column' ] );
		self::add_action( 'manage_users_custom_column', [ $this, 'output_store_credit_column' ], 10, 3 );

		// edit user screen
		self::add_action( 'show_user_profile', [ $this, 'output_user_profile_store_credit'] );
		self::add_action( 'edit_user_profile', [ $this, 'output_user_profile_store_credit'] );
		self::add_action( 'personal_options_update', [ $this, 'save_user_profile_store_credit' ] );
		self::add_action( 'edit_user_profile_update', [ $this, 'save_user_profile_store_credit' ] );
	}

	/**
	 * Adds custom columns to the users' table.
	 *
	 * @since 2.7.0
	 *
	 * @param array<string, string>|mixed $columns
	 * @return array<string, string>|mixed
	 */
	protected function add_store_credit_column( $columns ) {

		if ( is_array( $columns ) && current_user_can( 'manage_woocommerce' ) ) {
			$columns['store_credit'] = Store_Credit_Label::plural()->uppercase_first()->to_string();
		}

		return $columns;
	}

	/**
	 * Gets the content for the custom column of the users' table.
	 *
	 * @since 2.7.0
	 *
	 * @param mixed|string $content
	 * @param mixed|string $column
	 * @param int|mixed $user_id
	 * @return mixed|string
	 */
	protected function output_store_credit_column( $content, $column, $user_id ) {

		if ( 'store_credit' !== $column || ! is_numeric( $user_id ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return $content;
		}

		Database::migrate_legacy_user_account_funds( $user_id, 'Migrated from user meta (plugin version ' . self::plugin()->version() . ')' );

		return Wallet::get( $user_id )->to_string();
	}

	/**
	 * Shows custom fields on the edit user pages.
	 *
	 * @since 2.7.0
	 *
	 * @param mixed|WP_User $user
	 * @return void
	 */
	protected function output_user_profile_store_credit( $user ) : void {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		Database::migrate_legacy_user_account_funds( $user->ID ?: 0, 'Migrated from user meta (plugin version ' . self::plugin()->version() . ')' );

		$balance = Wallet::get( $user )->balance();

		?>
		<h2><?php echo esc_html( Store_Credit_Label::plural()->uppercase_first()->to_string() ); ?></h2>
		<table class="form-table" id="fieldset-store-credit">
			<tr>
				<th><label for="store_credit"><?php esc_html_e( 'Amount', 'woocommerce-account-funds' ); ?></label></th>
				<td>
					<input type="text" name="store_credit" id="store_credit" value="<?php echo esc_attr( wc_format_localized_price( (string) $balance ) ); ?>" class="wc_input_price" style="position:relative;" />
					<p class="description">
						<?php

						echo esc_html( sprintf(
							/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" by default */
							__( 'The amount of %s this user can use to purchase items.', 'woocommerce-account-funds' ),
							Store_Credit_Label::plural()->lowercase()->to_string()
						) );

						?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Shows the store credit field on the edit user pages.
	 *
	 * @since 2.7.0
	 *
	 * @param int|mixed $user_id User ID
	 * @return void
	 */
	protected function save_user_profile_store_credit( $user_id ) : void {

		// phpcs:ignore
		if ( ! is_numeric( $user_id ) || ! isset( $_POST['store_credit'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$user_id   = (int) $user_id;
		$wallet    = Wallet::get( $user_id );
		$old_email = get_userdata( $user_id )->user_email;
		$new_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : $old_email; // phpcs:ignore

		// if the email was updated in the same context, sync the customer data first and refresh the wallet
		if ( $old_email !== $new_email ) {

			Transaction::sync_customer_data( $wallet->id(), $new_email, $old_email );

			$wallet = Wallet::get( $user_id );
		}

		$previous_funds  = $wallet->balance();
		$new_funds       = (float) wc_format_decimal( wc_clean( wp_unslash( $_POST['store_credit'] ) ), wc_get_price_decimals() ); // phpcs:ignore
		$increased_funds = false;

		if ( $new_funds === $previous_funds ) {
			return;
		}

		try {
			if ( $new_funds > $previous_funds ) {
				$reason = __( 'Store credit manually increased by a shop manager.', 'woocommerce-account-funds' );

				$wallet->credit( Transaction::seed( [
					'amount'   => floatval( $new_funds - $previous_funds ),
					'event'    => Transaction_Event::USER_ACTION,
					'event_id' => get_current_user_id(),
					'note'     => $reason,
				] ) );
				$increased_funds = true;
			} else { // $previous_funds > $new_funds
				$reason = __( 'Store credit manually decreased by a shop manager.', 'woocommerce-account-funds' );

				$wallet->debit( Transaction::seed( [
					'amount'   => $previous_funds - $new_funds,
					'event'    => Transaction_Event::USER_ACTION,
					'event_id' => get_current_user_id(),
					'note'     => $reason,
				] ) );
			}
		} catch ( Exception $exception ) {
			/* translators: Placeholders: %1$s - user ID, %2$s - error message */
			Logger::warning( sprintf( 'Could not manually update store credit for user #%1$s: %2$s', $user_id, $exception->getMessage() ) );
			Notice::warning( $exception->getMessage() )->dispatch();
		}

		if ( $increased_funds ) {

			/**
			 * Fires the action for notifying the customer about the store credit increase.
			 *
			 * @since 2.8.0
			 *
			 * @param int $user_id user ID
			 * @param float $previous_funds the previous funds amount
			 * @param float $new_funds the new funds amount
			 */
			do_action( 'wc_account_funds_customer_funds_increased', $user_id, $previous_funds, $new_funds );
		}
	}

}

class_alias(
	__NAMESPACE__ . '\User_Profiles',
	'\WC_Account_Funds_Admin_Users'
);
