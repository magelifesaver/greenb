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

namespace Kestrel\Account_Funds\Lifecycle\Migrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Lifecycle\Migrations\Background\Migrate_Account_Funds_To_Credit_Transactions;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Jobs\Background_Job;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Background_Migration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Migration to upgrade the plugin to version 4.0.0.
 *
 * @since 4.0.0
 */
final class Upgrade_To_Version_4_0_0 implements Background_Migration {
	use Has_Plugin_Instance;

	/** @var Background_Job */
	private Background_Job $job;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @param WordPress_Plugin $plugin
	 */
	public function __construct( WordPress_Plugin $plugin ) {

		self::$plugin = $plugin;

		$this->job = Migrate_Account_Funds_To_Credit_Transactions::initialize( $plugin );
	}

	/**
	 * Creates and dispatches the background migration job.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		$this->migrate_account_funds_to_store_credit_transactions();
		$this->migrate_account_funds_settings();
		$this->migrate_account_funds_on_register();
	}

	/**
	 * Migrates the plugin settings to the new structure.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function migrate_account_funds_settings() : void {

		$default_label  = __( 'Store credit', 'woocommerce-account-funds' );
		$label_singular = get_option( 'account_funds_name', $default_label );
		$label_singular = is_string( $label_singular ) && '' !== trim( $label_singular ) ? $label_singular : $default_label;

		// store credit label settings
		update_option( 'kestrel_account_funds_store_credit_label', [
			'singular' => $label_singular,
			'plural'   => $label_singular, // did not exist before, this will default to legacy until updated by the merchant
		], false );

		$enable_top_up = 'yes' === get_option( 'account_funds_enable_topup', 'no' );

		if ( ! $enable_top_up ) {
			$top_up_min_in_cents = $top_up_max_in_cents = '';
		} else {
			$top_up_min        = get_option( 'account_funds_min_topup' ); // e.g. 10.12 or 10,12 etc.
			$top_up_max        = get_option( 'account_funds_max_topup' ); // same possible formats as above
			$decimal_separator = get_option( 'woocommerce_price_decimal_sep', '.' );
			$decimal_places    = get_option( 'woocommerce_price_num_decimals', 2 );

			$top_up_min = floatval( str_replace( '.', $decimal_separator, (string) $top_up_min ) );
			$top_up_max = floatval( str_replace( '.', $decimal_separator, (string) $top_up_max ) );

			// convert to cents
			$top_up_min_in_cents = $top_up_min > 0.0 ? intval( round( $top_up_min * pow( 10, $decimal_places ) ) ) : '';
			$top_up_max_in_cents = $top_up_max > 0.0 ? intval( round( $top_up_max * pow( 10, $decimal_places ) ) ) : '';
		}

		$using_custom_top_up_image_type = 'custom' === get_option( 'account_funds_topup_image_type' );
		$account_funds_topup_image_id   = intval( get_option( 'account_funds_topup_image' ) );

		// my account top-up settings (migrated to single option)
		update_option( 'kestrel_account_funds_my_account_top_up_settings', [
			'top_up_enabled'    => $enable_top_up ? 'yes' : 'no',
			'minimum_top_up'    => $top_up_min_in_cents,
			'maximum_top_up'    => $top_up_max_in_cents,
			'top_up_image_type' => $using_custom_top_up_image_type && $account_funds_topup_image_id > 0 ? 'custom' : '',
			'top_up_image_id'   => $account_funds_topup_image_id ?: '',
		], false );

		// gateway settings (with partial payment migrated to the gateway settings)
		$allow_partial_payment = 'yes' === get_option( 'account_funds_partial_payment', 'yes' );
		$gateway_settings      = (array) get_option( 'woocommerce_accountfunds_settings', [] );

		/* translators: Placeholder: %s - Store credit balance placeholder */
		$gateway_settings['description']     = empty( $gateway_settings['description'] ) ? sprintf( __( 'Available balance: %s', 'woocommerce-account-funds' ), '{store_credit_balance}' ) : strval( $gateway_settings['description'] );
		$gateway_settings['enabled']         = empty( $gateway_settings['enabled'] ) ? 'yes' : wc_bool_to_string( $gateway_settings['enabled'] );
		$gateway_settings['partial_payment'] = $allow_partial_payment ? 'yes' : 'no';

		// gateway settings
		update_option( 'woocommerce_accountfunds_settings', $gateway_settings );

		Logger::notice( 'Migrated account funds settings to the new setting options.' );

		// delete legacy options
		delete_option( 'account_funds_name' );
		delete_option( 'account_funds_enable_topup' );
		delete_option( 'account_funds_min_topup' );
		delete_option( 'account_funds_max_topup' );
		delete_option( 'account_funds_topup_image_type' );
		delete_option( 'account_funds_topup_image' );
		delete_option( 'account_funds_partial_payment' );
		delete_option( 'wcaf_settings' ); // ancient legacy option, if still lingering
	}

	/**
	 * Migrates the account funds on register settings to a milestone award object.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function migrate_account_funds_on_register() : void {

		$funds_on_register_enabled = 'yes' === get_option( 'account_funds_enable_funds_on_register' );
		$funds_on_register_amount  = get_option( 'account_funds_add_on_register' );

		if ( ! $funds_on_register_enabled || ! is_numeric( $funds_on_register_amount ) || floatval( $funds_on_register_amount ) <= 0 ) {
			return;
		}

		$sign_up_milestone = Milestone::seed()
			->set_label( __( 'Account funds on registration', 'woocommerce-account-funds' ) )
			->set_trigger( Transaction_Event::ACCOUNT_SIGNUP )
			->set_status( Reward_Status::ACTIVE )
			->set_currency( WooCommerce::currency()->code() )
			->set_amount( floatval( $funds_on_register_amount ) )
			->set_unique( true )
			->save();

		if ( ! $sign_up_milestone->get_id() ) {
			Logger::error( 'Failed to migrate account funds on registration settings to a sign-up milestone object.' );
		} else {
			Logger::notice( sprintf( 'Migrated account funds on registration settings to a milestone award object with ID %d.', $sign_up_milestone->get_id() ) );
		}

		// delete legacy options
		delete_option( 'account_funds_enable_funds_on_register' );
		delete_option( 'account_funds_add_on_register' );
	}

	/**
	 * Queues users for migrating account funds from user meta to credit transactions records.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function migrate_account_funds_to_store_credit_transactions() : void {

		$user_ids = get_users( [
			'fields'     => 'ID',
			'number'     => -1,
			'meta_query' => [
				[
					'key'     => 'account_funds',
					'compare' => 'EXISTS',
				],
			],
		] );

		if ( empty( $user_ids ) ) {
			Logger::notice( 'No users found with account funds to migrate.' );

			return;
		}

		foreach ( $user_ids as $user_id ) {
			$this->job->push_to_queue( $user_id );
		}

		$this->job->save()->dispatch();

		Logger::notice( sprintf( 'Queueing %d users for migrating account funds from user meta to credit transactions records.', count( $user_ids ) ) );
	}

	/**
	 * Determines whether the background migration process has completed.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_done() : bool {

		return ! $this->job->is_processing() && ! $this->job->is_queued();
	}

}
