<?php
/**
 * Shared trait Atum PO Order Items
 *
 * @package         AtumPO\Abstracts\AtumPOOrders
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 */

namespace AtumPO\Abstracts\AtumPOOrders\Items;

defined( 'ABSPATH' ) || die;

trait AtumPOOrderItemTrait {

	/**
	 * Get the associated PO item ID.
	 *
	 * @since 0.9.17
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_po_item_id( $context = 'view' ) {
		return $this->get_prop( 'po_item_id', $context );
	}

	/**
	 * Setter for the po_item_id prop
	 *
	 * @since 0.9.17
	 *
	 * @param int $value
	 */
	public function set_po_item_id( $value ) {
		$this->set_prop( 'po_item_id', absint( $value ) );
	}

}
