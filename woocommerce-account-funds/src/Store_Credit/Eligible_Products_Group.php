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

namespace Kestrel\Account_Funds\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Named_Constructors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;

/**
 * Enumerator for eligible products group for store credit.
 *
 * @see Cashback for purchase-based store credit types
 * @see Milestone for milestone-based store credit types
 *
 * @since 4.0.0
 *
 * @method static self all_products()
 * @method static self some_products()
 * @method static self some_product_categories()
 */
final class Eligible_Products_Group {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string */
	public const ALL_PRODUCTS = 'all_products';

	/** @var string */
	public const SOME_PRODUCTS = 'some_products';

	/** @var string */
	public const SOME_PRODUCT_CATEGORIES = 'some_product_categories';

	/** @var string default value */
	protected static string $default = self::ALL_PRODUCTS;

	/**
	 * Returns the label for the eligible product type.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label() : string {

		switch ( $this->value() ) {
			case self::SOME_PRODUCTS:
				return __( 'Some products', 'woocommerce-account-funds' );
			case self::SOME_PRODUCT_CATEGORIES:
				return __( 'Some product categories', 'woocommerce-account-funds' );
			case self::ALL_PRODUCTS:
			default:
				return __( 'All products', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Returns a list of eligible product type options.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, string>
	 */
	public static function list() : array {

		$types   = self::values();
		$options = [];

		foreach ( $types as $type ) {
			$option = self::make( $type );

			$options[ $option->value() ] = $option->label();
		}

		return $options;
	}

}
