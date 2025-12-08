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

namespace Kestrel\Account_Funds\Store_Credit\Rewards\Traits;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Store_Credit\Eligible_Orders_Group;
use Kestrel\Account_Funds\Store_Credit\Eligible_Products_Group;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use WC_Product;

/**
 * Provides methods to manage product-related rules for store credit reward configurations.
 *
 * @since 4.0.0
 *
 * @method array<string, mixed>|null get_rules()
 * @method $this set_rules( array $rules )
 */
trait Has_Eligible_Products {

	/** @var array<int, bool> */
	private static array $applies_to_product = [];

	/**
	 * Returns store credit reward configurations applicable to the given product.
	 *
	 * @since 4.0.0
	 *
	 * @param int|WC_Product $product
	 * @param array<string, mixed> $args
	 * @return Collection<int, self>
	 */
	public static function find_for_product( $product, array $args = [] ) : Collection {

		$matches = [];

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( (int) $product );
		}

		if ( $product instanceof WC_Product ) {

			$rewards = self::find_many( $args );

			foreach ( $rewards as $reward ) {
				if ( $reward->applies_to_product( $product ) ) {
					$matches[ $reward->get_id() ] = $reward;
				}
			}
		}

		return Collection::create( $matches );
	}

	/**
	 * Determines if the store credit reward applies to the given product.
	 *
	 * @since 4.0.0
	 *
	 * @param int|WC_Product $product
	 * @return bool
	 */
	protected function applies_to_product( $product ) : bool {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( absint( $product ) );
		}

		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$product_id = $product->get_id();

		if ( isset( static::$applies_to_product[ $product_id ] ) ) {
			return static::$applies_to_product[ $product_id ];
		}

		$applies_to_product = $product->is_purchasable();

		if ( $applies_to_product ) {

			$eligible_products = $this->get_eligible_products();

			if ( ! $eligible_products && $this instanceof Cashback ) {
				// order-based cashback
				switch ( $this->get_eligible_orders() ) {
					case Eligible_Orders_Group::ALL_ORDERS:
						$applies_to_product = true;
						break;
					case Eligible_Orders_Group::INCLUDING_PRODUCTS:
						$applies_to_product = ! empty( $this->get_products_ids() ) && in_array( $product->get_id(), $this->get_products_ids(), true );
						break;
					case Eligible_Orders_Group::EXCLUDING_PRODUCTS:
						$applies_to_product = empty( $this->get_products_ids() ) || ! in_array( $product->get_id(), $this->get_products_ids(), true );
						break;
					case Eligible_Orders_Group::INCLUDING_PRODUCT_CATEGORIES:
						$applies_to_product = ! empty( $this->get_product_category_ids() ) && has_term( $this->get_product_category_ids(), 'product_cat', $product->get_id() );
						break;
					case Eligible_Orders_Group::EXCLUDING_PRODUCT_CATEGORIES:
						$applies_to_product = empty( $this->get_product_category_ids() ) || ! has_term( $this->get_product_category_ids(), 'product_cat', $product->get_id() );
						break;
					case Eligible_Orders_Group::INCLUDING_PRODUCT_TYPES:
						$applies_to_product = ! empty( $this->get_product_types() ) && in_array( $product->get_type(), $this->get_product_types(), true );
						break;
					case Eligible_Orders_Group::EXCLUDING_PRODUCT_TYPES:
						$applies_to_product = empty( $this->get_product_types() ) || ! in_array( $product->get_type(), $this->get_product_types(), true );
						break;
					default:
						$applies_to_product = false;
						break;
				}
			} else {

				// product-based cashback
				switch ( $eligible_products ) {
					case Eligible_Products_Group::ALL_PRODUCTS:
						$applies_to_product = true;
						break;
					case Eligible_Products_Group::SOME_PRODUCT_CATEGORIES:
						$applies_to_product = ! empty( $this->get_product_category_ids() ) && has_term( $this->get_product_category_ids(), 'product_cat', $product->get_id() );
						break;
					case Eligible_Products_Group::SOME_PRODUCTS:
						$applies_to_product = in_array( $product->get_id(), $this->get_products_ids(), true );
						break;
					default:
						$applies_to_product = false;
						break;
				}
			}
		}

		self::$applies_to_product[ $product_id ] = $applies_to_product;

		return $applies_to_product;
	}

	/**
	 * Returns the eligible products setting for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return 'all_products'|'some_product_categories'|'some_products'|null
	 */
	public function get_eligible_products() : ?string {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['products'] ) ) {
			return null;
		}

		return in_array( $rules['products'], array_keys( Eligible_Products_Group::list() ), true ) ? $rules['products'] : null;
	}

	/**
	 * Sets the eligible products setting for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param 'all_products'|'some_product_categories'|'some_products'|null $which_products
	 * @return self
	 */
	public function set_eligible_products( ?string $which_products ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( in_array( $which_products, array_keys( Eligible_Products_Group::list() ), true ) ) {
			$rules['products'] = $which_products;
		} else {
			unset( $rules['products'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Returns the eligible products IDs for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return int[]
	 */
	public function get_products_ids() : array {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['product_ids'] ) || ! is_array( $rules['product_ids'] ) ) {
			return [];
		}

		return array_map( 'absint', $rules['product_ids'] );
	}

	/**
	 * Sets the eligible products IDs for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param int[]|null $product_ids
	 * @return self
	 */
	public function set_products_ids( ?array $product_ids = [] ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		// @phpstan-ignore-next-line
		if ( ! empty( $product_ids ) && is_array( $product_ids ) ) {
			$rules['product_ids'] = array_unique( array_map( 'absint', $product_ids ) );
		} else {
			unset( $rules['product_ids'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Returns the eligible product categories IDs for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return int[]
	 */
	public function get_product_category_ids() : array {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['product_cat_ids'] ) || ! is_array( $rules['product_cat_ids'] ) ) {
			return [];
		}

		return array_map( 'absint', $rules['product_cat_ids'] );
	}

	/**
	 * Sets the eligible product categories IDs for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param int[]|null $product_category_ids
	 * @return self
	 */
	public function set_product_category_ids( ?array $product_category_ids = [] ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		// @phpstan-ignore-next-line
		if ( ! empty( $product_category_ids ) && is_array( $product_category_ids ) ) {
			$rules['product_cat_ids'] = array_unique( array_map( 'absint', $product_category_ids ) );
		} else {
			unset( $rules['product_cat_ids'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Returns the eligible product types for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return string[]
	 */
	public function get_product_types() : array {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['product_types'] ) || ! is_array( $rules['product_types'] ) ) {
			return [];
		}

		return array_map( 'strval', $rules['product_types'] );
	}

	/**
	 * Sets the eligible product types for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @param string[]|null $product_types
	 * @return self
	 */
	public function set_product_types( ?array $product_types = [] ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		// @phpstan-ignore-next-line
		if ( ! empty( $product_types ) && is_array( $product_types ) ) {
			$rules['product_types'] = array_unique( array_map( 'strval', $product_types ) );
		} else {
			unset( $rules['product_types'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Returns the product quantity behavior for the store credit configuration.
	 *
	 * @since 4.0.0
	 *
	 * @return 'ignore'|'multiply'
	 */
	public function get_product_quantity_behavior() : string {

		$rules = $this->get_rules();

		$default_behavior = 'multiply';

		if ( ! is_array( $rules ) || ! isset( $rules['product_qty'] ) ) {
			return $default_behavior;
		}

		return in_array( $rules['product_qty'], [ 'multiply', 'ignore' ], true ) ? $rules['product_qty'] : $default_behavior;
	}

	/**
	 * Sets the product quantity behavior for the store credit source.
	 *
	 * @since 4.0.0
	 *
	 * @param 'ignore'|'multiply'|null $behavior
	 * @return self
	 */
	public function set_product_quantity_behavior( ?string $behavior ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( in_array( $behavior, [ 'multiply', 'ignore' ], true ) ) {
			$rules['product_qty'] = $behavior;
		} else {
			unset( $rules['product_qty'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Determines if the store credit award is limited to once per product.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function is_limited_to_once_per_product() : bool {

		return true === $this->get_limited_to_once_per_product();
	}

	/**
	 * Determines if the store credit award is limited to once per product.
	 *
	 * @since 4.0.0
	 *
	 * @return bool|null
	 */
	public function get_limited_to_once_per_product() : ?bool {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['limit_once_per_product'] ) ) {
			return null;
		}

		return wc_string_to_bool( $rules['limit_once_per_product'] );
	}

	/**
	 * Sets whether the store credit award is limited to once per product.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $limited_to_once_per_product
	 * @return self
	 */
	public function set_limited_to_once_per_product( bool $limited_to_once_per_product ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( $limited_to_once_per_product ) {
			$rules['limit_once_per_product'] = 'yes';
		} else {
			$rules['limit_once_per_product'] = 'no';
		}

		return $this->set_rules( $rules );
	}

}
