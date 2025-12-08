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

namespace Kestrel\Account_Funds\Products\Data_Stores;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Products\Store_Credit_Top_Up;
use WC_Product;
use WC_Product_Data_Store_CPT;

/**
 * WooCommerce data store for {@see Store_Credit_Top_Up} products.
 *
 * @since 2.0.0
 */
class Store_Credit_Top_Up_Post_Type extends WC_Product_Data_Store_CPT {

	/**
	 * Reads the top-up product.
	 *
	 * For top-up there's no real product stored in DB, so nothing to do in this method.
	 *
	 * @since 2.1.3
	 *
	 * @param Store_Credit_Top_Up|WC_Product $product
	 */
	public function read( &$product ) {

		$product->set_defaults();
		$product->set_id( wc_get_page_id( 'myaccount' ) );
		$product->set_props( [
			'name'              => $product->get_title(),
			'virtual'           => $product->is_virtual(),
			'tax_status'        => $product->get_tax_status(),
			'sold_individually' => $product->is_sold_individually(),
		] );

		$product->set_object_read( true );
	}

}
