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

namespace Kestrel\Account_Funds\Integrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Plugins;

/**
 * WooCommerce Subscriptions integration class.
 *
 * @since 3.1.0
 */
final class Subscriptions implements Integration {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	protected function __construct() {

		self::add_filter( 'wc_account_funds_should_skip_calculating_cart_totals', [ $this, 'maybe_skip_cart_totals_calculations' ] );
		self::add_filter( 'wc_account_funds_can_use_funds', [ $this, 'maybe_current_user_cannot_use_store_credit' ] );
	}

	/**
	 * Determines whether the integration should be initialized.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
	}

	/**
	 * Maybe skip cart totals calculations when cart contains a subscription and the user is changing payment method.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @param bool $should_skip_calculate_cart_totals
	 * @return bool
	 */
	protected function maybe_skip_cart_totals_calculations( bool $should_skip_calculate_cart_totals ) : bool {

		// flag may be present when changing the payment method of a subscription
		return $should_skip_calculate_cart_totals || isset( $_REQUEST['change_payment_method'] ); // phpcs:ignore
	}

	/**
	 * Maybe prevent the user from using store credit if the cart contains a subscription and is switching payment method.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @param bool $can_use_store_credit
	 * @return bool
	 */
	protected function maybe_current_user_cannot_use_store_credit( bool $can_use_store_credit ) : bool {

		if ( ! $can_use_store_credit || ! isset( $_REQUEST['change_payment_method'] ) || ! function_exists( 'wcs_get_subscription' ) ) { // phpcs:ignore
			return $can_use_store_credit;
		}

		$wallet       = Wallet::get( get_current_user_id() );
		$subscription = wcs_get_subscription( (int) $_REQUEST['change_payment_method'] ); // phpcs:ignore

		if ( ! $subscription ) {
			return true;
		}

		return $subscription->get_total( 'edit' ) <= $wallet->available_balance( $subscription->get_parent_id() );
	}

}
