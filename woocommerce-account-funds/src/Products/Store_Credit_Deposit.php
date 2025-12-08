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

use WC_Product;
use WC_Product_Simple;

/**
 * Store credit deposit product that customers can buy to increase their balance.
 *
 * @since 2.7.0
 */
class Store_Credit_Deposit extends WC_Product_Simple {

	/** @var string */
	protected $product_type = 'deposit';

	/**
	 * Initializes the deposit product.
	 *
	 * @since 2.7.0
	 *
	 * @param int|WC_Product $product product instance or ID
	 */
	public function __construct( $product = 0 ) {

		$this->data = wp_parse_args( [
			'tax_status' => 'none',
			'virtual'    => true,
		], $this->data );

		parent::__construct( $product );
	}

	/**
	 * Gets the product type.
	 *
	 * @since 2.1.5
	 *
	 * @return string
	 */
	public function get_type() {

		return $this->product_type;
	}

	/**
	 * Returns the tax status.
	 *
	 * @since 2.7.0
	 *
	 * @param string $context
	 * @return string
	 */
	public function get_tax_status( $context = 'view' ) {

		// by default, deposit products are non-taxable
		return ! $this->exists() || 0 === $this->get_id() ? 'none' : parent::get_tax_status( $context );
	}

	/**
	 * Returns whether the product is virtual.
	 *
	 * @since 2.7.0
	 *
	 * @param string $context
	 * @return bool
	 */
	public function get_virtual( $context = 'view' ) {

		// by default, deposit products are virtual
		return ! $this->exists() || 0 === $this->get_id() || parent::get_virtual( $context );
	}

}
