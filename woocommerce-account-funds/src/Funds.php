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

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;

defined( 'ABSPATH' ) or exit;

/**
 * Account funds manager.
 *
 * @since 3.1.0
 * @deprecated 4.0.0
 *
 * @method static Plugin plugin()
 */
final class Funds {
	use Is_Handler;

	/** @var bool|null memoized setting value */
	private static ?bool $give_discount = null;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;
	}

	/**
	 * Gets the account funds label.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function get_account_funds_label() : string {

		wc_deprecated_function( __METHOD__, '4.0.0', Store_Credit_Label::class . '::string()' );

		return Store_Credit_Label::plural()->to_string();
	}

	/**
	 * Determines if customers can partially pay for an order using account funds.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function should_allow_partial_payments_using_account_funds() : bool {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return self::plugin()->gateway()->allows_partial_payment();
	}

	/**
	 * Determines if customers should be granted account funds upon user registration.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return bool
	 */
	public static function should_grant_account_funds_upon_user_registration() : bool {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return false;
	}

	/**
	 * Determines if customers should be granted a discount when using account funds.
	 *
	 * @since 3.1.0
	 * @deprecated the {@see Discounts} feature is deprecated and will be removed in a future major release of the plugin
	 *
	 * @NOTE This feature won't be available when using the Cart and Checkout blocks.
	 *
	 * @return bool
	 */
	public static function applied_account_funds_should_grant_discount() : bool {

		if ( null === self::$give_discount ) {
			// need to check also if the discount amount is greater than 0, as older versions allowed to set a 0 amount
			self::$give_discount = wc_string_to_bool( get_option( 'account_funds_give_discount', 'no' ) ) && Discounts::get_discount_amount() > 0;
		}

		if ( has_filter( 'wc_account_funds_applied_funds_should_grant_discount' ) ) {
			wc_deprecated_hook( 'wc_account_funds_applied_funds_should_grant_discount', '3.1.0' );
		}

		/**
		 * Filters whether customers should be granted a discount when using account funds.
		 *
		 * @since 3.1.0
		 * @deprecated this filter will be removed in a future major release of the plugin
		 *
		 * @param bool $give_discount
		 */
		$grants_discount = (bool) apply_filters( 'wc_account_funds_applied_funds_should_grant_discount', self::$give_discount ) && ( ! Blocks::is_cart_block_in_use() && ! Blocks::is_checkout_block_in_use() );

		if ( $grants_discount && has_filter( 'wc_account_funds_applied_funds_should_grant_discount' ) ) {
			wc_deprecated_function( __METHOD__, '3.1.0' );
		}

		return $grants_discount;
	}

	/**
	 * Gets the customer-facing label for applying account funds in cart or checkout.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return string
	 */
	public static function get_apply_account_funds_label() : string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return '';
	}

	/**
	 * Gets the customer-facing label for removing account funds in cart or checkout.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return string
	 */
	public static function get_remove_account_funds_label() : string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return '';
	}

}

class_alias(
	__NAMESPACE__ . '\Funds',
	'\Kestrel\WooCommerce\Account_Funds\Funds'
);
