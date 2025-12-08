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

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce;
use Kestrel\Account_Funds\Store_Credit\Wallet;

/**
 * Shortcodes handler.
 *
 * @since 4.0.0
 */
final class Shortcodes {
	use Is_Handler;

	/**
	 * Initializes the shortcodes handler.
	 *
	 * @since 4.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		$this->register_shortcodes();
	}

	/**
	 * Registers the plugin shortcodes.
	 *
	 * Supported shortcodes:
	 *
	 * - `[store_credit_balance]`: Returns the store credit balance for the current user or the specified user.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	private function register_shortcodes() : void {

		$get_customer_account_funds = fn( $attributes ) => $this->process_store_credit_balance_shortcode( $attributes );

		add_shortcode( 'get-account-funds', $get_customer_account_funds ); // backwards compatibility with a legacy version of the shortcode below
		add_shortcode( 'store_credit_balance', $get_customer_account_funds );
	}

	/**
	 * Returns the account funds balance for the given user (defaults to current user).
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $attributes
	 *
	 * @phpstan-param array{
	 *     customer?: int|string,
	 *     currency?: string,
	 *     formatted?: bool
	 * }|mixed $attributes
	 *
	 * @return string
	 */
	private function process_store_credit_balance_shortcode( $attributes ) : string {

		$attributes = wp_parse_args(
			is_array( $attributes ) ? $attributes : (array) $attributes,
			[
				'customer'  => get_current_user_id(),
				'currency'  => WooCommerce::currency()->code(),
				'formatted' => true,
			]
		);

		$customer_id = $attributes['customer'] ?: get_current_user_id();
		$formatted   = (bool) $attributes['formatted'];
		$currency    = (string) $attributes['currency'];
		$balance     = Wallet::get( $customer_id, $currency )->available_balance();

		return ! $formatted ? (string) $balance : wc_price( $balance, [
			'currency' => $currency,
		] );
	}

}

class_alias(
	__NAMESPACE__ . '\Shortcodes',
	'\WC_Account_Funds_Shortcodes'
);
