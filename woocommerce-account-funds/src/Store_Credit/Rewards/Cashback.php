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

namespace Kestrel\Account_Funds\Store_Credit\Rewards;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Collection;
use Kestrel\Account_Funds\Store_Credit\Eligible_Orders_Group;
use Kestrel\Account_Funds\Store_Credit\Reward;
use Kestrel\Account_Funds\Store_Credit\Reward_Type;
use Kestrel\Account_Funds\Store_Credit\Rewards\Traits\Has_Eligible_Products;

/**
 * Store credit as cashback.
 *
 * Cashback is a type of store credit that is issued to customers based on their purchases, allowing them to receive a percentage of their spending back as credit for future use.
 *
 * @since 4.0.0
 *
 * @method static Cashback find( int $identifier )
 * @method static Collection<int, Cashback> find_many( array $args = [] )
 * @method array<string, mixed>|null get_rules()
 * @method $this set_rules( array $rules )
 */
final class Cashback extends Reward {
	use Has_Eligible_Products;

	protected const TYPE = Reward_Type::CASHBACK;

	/**
	 * Gets the eligible orders for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @return string|null
	 */
	public function get_eligible_orders() : ?string {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['orders'] ) ) {
			return null;
		}

		return in_array( $rules['orders'], array_keys( Eligible_Orders_Group::list() ), true ) ? $rules['orders'] : null;
	}

	/**
	 * Sets the eligible orders for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param string|null $eligible_orders
	 * @return $this
	 */
	public function set_eligible_orders( ?string $eligible_orders ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( in_array( $eligible_orders, array_keys( Eligible_Orders_Group::list() ), true ) ) {
			$rules['orders'] = $eligible_orders;
		} else {
			unset( $rules['orders'] );
		}

		$this->set_rules( $rules );

		return $this;
	}

	/**
	 * Gets the minimum order amount for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @return float|null
	 */
	public function get_minimum_order_amount() : ?float {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['minimum_order_amount'] ) ) {
			return null;
		}

		return is_numeric( $rules['minimum_order_amount'] ) ? (float) $rules['minimum_order_amount'] : null;
	}

	/**
	 * Sets the minimum order amount for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param float|null $amount
	 * @return $this
	 */
	public function set_minimum_order_amount( ?float $amount ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( is_numeric( $amount ) && $amount >= 0 ) {
			$rules['minimum_order_amount'] = (float) $amount;
		} else {
			unset( $rules['minimum_order_amount'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Gets the maximum order amount for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @return float|null
	 */
	public function get_maximum_order_amount() : ?float {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['maximum_order_amount'] ) ) {
			return null;
		}

		return is_numeric( $rules['maximum_order_amount'] ) ? (float) $rules['maximum_order_amount'] : null;
	}

	/**
	 * Sets the maximum order amount for this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param float|null $amount
	 * @return $this
	 */
	public function set_maximum_order_amount( ?float $amount ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		if ( is_numeric( $amount ) && $amount >= 0 ) {
			$rules['maximum_order_amount'] = (float) $amount;
		} else {
			unset( $rules['maximum_order_amount'] );
		}

		return $this->set_rules( $rules );
	}

	/**
	 * Determines whether to exclude free items from this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function excludes_free_items() : bool {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['exclude_free_items'] ) ) {
			return false;
		}

		return wc_string_to_bool( $rules['exclude_free_items'] );
	}

	/**
	 * Sets whether to exclude free items from this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $exclude
	 * @return $this
	 */
	public function set_exclude_free_items( bool $exclude = false ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		$rules['exclude_free_items'] = $exclude ? 'yes' : 'no';

		return $this->set_rules( $rules );
	}

	/**
	 * Determines whether to exclude items on sale from this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function excludes_items_on_sale() : bool {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['exclude_items_on_sale'] ) ) {
			return false;
		}

		return wc_string_to_bool( $rules['exclude_items_on_sale'] );
	}

	/**
	 * Set whether to exclude items on sale from this cashback.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $exclude
	 * @return $this
	 */
	public function set_exclude_items_on_sale( bool $exclude = false ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		$rules['exclude_items_on_sale'] = $exclude ? 'yes' : 'no';

		return $this->set_rules( $rules );
	}

	/**
	 * Determines whether to exclude awarding cashback if there are applied coupons.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function excludes_coupon_discounts() : bool {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) || ! isset( $rules['exclude_coupons'] ) ) {
			return false;
		}

		return wc_string_to_bool( $rules['exclude_coupons'] );
	}

	/**
	 * Sets whether to exclude awarding cashback if there are applied coupons.
	 *
	 * @since 4.0.0
	 *
	 * @param bool $exclude
	 * @return $this
	 */
	public function set_exclude_coupon_discounts( bool $exclude = false ) : self {

		$rules = $this->get_rules();

		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		$rules['exclude_coupons'] = $exclude ? 'yes' : 'no';

		return $this->set_rules( $rules );
	}

}
