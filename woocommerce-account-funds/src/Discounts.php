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
use Kestrel\Account_Funds\Settings\Store_Credit_Label;

/**
 * Discounts handler.
 *
 * @since 3.1.0
 * @deprecated 3.1.0
 */
final class Discounts {
	use Is_Handler;

	/** @var string fixed discount type */
	public const TYPE_FIXED = 'fixed';

	/** @var string percentage discount type */
	public const TYPE_PERCENTAGE = 'percentage';

	/** @var string deprecated feature flag */
	public const DEPRECATED_FEATURE_FLAG = 'account_funds_discounts_deprecated';

	/**
	 * Gets the discount settings for using account funds.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, mixed> discount options
	 */
	public static function get_discount_options() : array {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return [];
	}

	/**
	 * Gets the discount type for using account funds.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function get_discount_type() : string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return '';
	}

	/**
	 * Determines if the configured account funds discount is of a given type.
	 *
	 * @since 3.1.0
	 *
	 * @param string $type discount type
	 * @return bool
	 */
	public static function is_discount_type( string $type ) : bool {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return $type === self::get_discount_type();
	}

	/**
	 * Gets the discount amount for using account funds.
	 *
	 * @since 3.1.0
	 *
	 * @return float
	 */
	public static function get_discount_amount() : float {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return 0.0;
	}

	/**
	 * Gets the discount amount for using account funds, formatted.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return string
	 */
	public static function get_discount_amount_formatted() : string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return '';
	}

	/**
	 * Gets the discount label for using account funds.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	public static function get_discount_label() : string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return Store_Credit_Label::plural()->to_string();
	}

	/**
	 * Generates a unique discount code when using account funds, tied to the current user ID and timestamp.
	 *
	 * The discount code thus generated is then stored in the current session.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return string|null current user ID + the current time in YYYY_MM_DD_H_M format
	 */
	public static function generate_discount_code() : ?string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return null;
	}

	/**
	 * Gets the unique discount code generated for the applied account funds, if set in the current session.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return string|null the discount code or null if not set
	 */
	public static function get_applied_discount_code() : ?string {

		wc_deprecated_function( __METHOD__, '4.0.0' );

		return null;
	}

	/**
	 * Removes the applied account funds discount code from the current session.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 *
	 * @return void
	 */
	public static function remove_applied_discount_code() : void {

		wc_deprecated_function( __METHOD__, '4.0.0' );
	}

}

class_alias(
	__NAMESPACE__ . '\Discounts',
	'\Kestrel\WooCommerce\Account_Funds\Discounts'
);
