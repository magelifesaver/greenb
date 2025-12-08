<?php
/**
 * The model class for the Delivery Item Inventory objects
 * Multi-Inventory compatibility only
 *
 * @package         AtumPO\Deliveries
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.3
 */

namespace AtumPO\Deliveries\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Inc\Helpers as MIHelpers;


class DeliveryItemInventory extends AtumOrderItemModel {

	/**
	 * DeliveryItem constructor
	 *
	 * @param \WC_Order_Item $delivery_item The factory object for initialization.
	 *
	 * @throws AtumException
	 */
	public function __construct( \WC_Order_Item $delivery_item ) {
		$this->atum_order_item = $delivery_item;
		parent::__construct( $delivery_item->get_id() );
	}

	/* @noinspection PhpDocMissingThrowsInspection PhpDocRedundantThrowsInspection */
	/**
	 * Read an ATUM Order item from the database
	 *
	 * @since 0.9.3
	 *
	 * @throws AtumException
	 */
	protected function read() {

		try {

			parent::read();

			// Read the Delivery item inventory props from db.
			$this->atum_order_item->set_props( array(
				'inventory_id'  => $this->get_meta( '_inventory_id' ),
				'po_item_id'    => $this->get_meta( '_po_item_id' ),
				'quantity'      => $this->get_meta( '_qty' ),
				'stock_changed' => $this->get_meta( '_stock_changed' ),
				'returned_qty'  => $this->get_meta( '_returned_qty' ),
			) );

			$this->atum_order_item->set_object_read( TRUE );

		} catch ( AtumException $e ) {

			if ( ATUM_DEBUG ) {
				error_log( __METHOD__ . '::' . $e->getMessage() );
			}

		}

	}

	/**
	 * Delete and calculate inbound stock after deletion.
	 *
	 * @since 1.0.5
	 */
	public function delete() {

		parent::delete();

		$inventory_id = $this->atum_order_item->get_inventory_id();
		$inventory    = MIHelpers::get_inventory( $inventory_id );

		MIHelpers::update_order_item_inventories_sales_calc_props( $inventory, AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

	}

}
