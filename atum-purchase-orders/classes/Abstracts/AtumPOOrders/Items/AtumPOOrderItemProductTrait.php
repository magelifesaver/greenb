<?php
/**
 * Shared trait Atum PO Order Item Products
 *
 * @package         AtumPO\Abstracts\AtumPOOrders
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 */

namespace AtumPO\Abstracts\AtumPOOrders\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumException;
use Atum\PurchaseOrders\Items\POItemProduct;

trait AtumPOOrderItemProductTrait {

	/**
	 * Get the expected quantity from the associated PO item
	 *
	 * @since 0.9.2
	 *
	 * @return int|float
	 *
	 * @throw AtumException
	 */
	public function get_expected_qty() {

		// If the PO item no longer exists will throw an error when loading it.
		try {

			$po_item = new POItemProduct( $this->get_po_item_id() );
			return $po_item->get_quantity();

		} catch ( AtumException $e ) {
			return 0;
		}

	}

	use AtumPOOrderItemTrait;

}
