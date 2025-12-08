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

/**
 * Enumerator for eligible orders group for store credit.
 *
 * @see Cashback awarded when customers pay for an order
 *
 * @since 4.0.0
 *
 * @method static self all_orders()
 * @method static self including_products()
 * @method static self excluding_products()
 * @method static self including_product_categories()
 * @method static self excluding_product_categories()
 * @method static self including_product_types()
 * @method static self excluding_product_types()
 */
final class Eligible_Orders_Group {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string */
	public const ALL_ORDERS = 'all_orders';

	/** @var string */
	public const INCLUDING_PRODUCTS = 'including_products';

	/** @var string */
	public const EXCLUDING_PRODUCTS = 'excluding_products';

	/** @var string */
	public const INCLUDING_PRODUCT_CATEGORIES = 'including_product_categories';

	/** @var string */
	public const EXCLUDING_PRODUCT_CATEGORIES = 'excluding_product_categories';

	/** @var string */
	public const INCLUDING_PRODUCT_TYPES = 'including_product_types';

	/** @var string */
	public const EXCLUDING_PRODUCT_TYPES = 'excluding_product_types';

	/** @var string default value */
	protected static string $default = self::ALL_ORDERS;

	/**
	 * Returns the label for the eligible order type.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label() : string {

		switch ( $this->value() ) {
			case self::INCLUDING_PRODUCTS:
				return __( 'Order contains specific products', 'woocommerce-account-funds' );
			case self::EXCLUDING_PRODUCTS:
				return __( 'Order does not contain specific products', 'woocommerce-account-funds' );
			case self::INCLUDING_PRODUCT_CATEGORIES:
				return __( 'Order contains product categories', 'woocommerce-account-funds' );
			case self::EXCLUDING_PRODUCT_CATEGORIES:
				return __( 'Order does not contain product categories', 'woocommerce-account-funds' );
			case self::INCLUDING_PRODUCT_TYPES:
				return __( 'Order contains product types', 'woocommerce-account-funds' );
			case self::EXCLUDING_PRODUCT_TYPES:
				return __( 'Order does not contain product types', 'woocommerce-account-funds' );
			case self::ALL_ORDERS:
			default:
				return __( 'Any order', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Returns a list of eligible order type options.
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
