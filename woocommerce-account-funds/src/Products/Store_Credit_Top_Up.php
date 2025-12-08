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

namespace Kestrel\Account_Funds\Products;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use WC_Product;

/**
 * Store credit top-up products is a type of product created on the fly when a customer wishes to top-up their store credit balance.
 *
 * @since 2.0.0
 */
class Store_Credit_Top_Up extends WC_Product {

	/** @var string */
	protected $product_type = 'topup';

	/**
	 * Gets the product type.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_type() {

		return $this->product_type;
	}

	/**
	 * Top-up products always exist.
	 *
	 * @since 2.0.0
	 *
	 * @return true
	 */
	public function exists() {

		return true;
	}

	/**
	 * Top-up products are always purchasable.
	 *
	 * @since 2.0.0
	 *
	 * @return true
	 */
	public function is_purchasable() {

		return true;
	}

	/**
	 * Returns the image HTML for the store credit top-up do be displayed in cart.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed|string $size
	 * @param array<string, scalar>|mixed $attr
	 * @param bool|mixed $placeholder
	 * @return string
	 */
	public function get_image( $size = 'woocommerce_thumbnail', $attr = [], $placeholder = true ) {

		return Store_Credit_Account_Top_Up::image_html();
	}

	/**
	 * Returns the top-up product title.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function get_title() {

		return sprintf(
			/* translators: Placeholders: %s - Account funds label, e.g. "Account funds" */
			__( '%s top-up', 'woocommerce-account-funds' ),
			Store_Credit_Label::plural()->to_string()
		);
	}

	/**
	 * Returns the tax status.
	 *
	 * By default, top-up products, just like {@see Store_Credit_Deposit} products shouldn't be taxable.
	 *
	 * @since 2.0.0
	 *
	 * @param string $context
	 * @return string
	 */
	public function get_tax_status( $context = 'view' ) {

		/**
		 * Filters the tax status of a Top-up product.
		 *
		 * @since 2.1.2
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @param string $status the tax status
		 */
		return apply_filters( 'woocommerce_account_funds_topup_get_tax_status', 'none', $context );
	}

	/**
	 * Top-up products are not available in catalog.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_visible() {

		return false;
	}

	/**
	 * Top-up products are always virtual and do not require shipping.
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_virtual() {

		return true;
	}

	/**
	 * Makes sure topup is sold individually (no quantities).
	 *
	 * @since 2.0.0
	 *
	 * @return bool
	 */
	public function is_sold_individually() {

		return true;
	}

}
