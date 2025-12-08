<?php
/**
 * The model class for the Delivery Item objects
 *
 * @package         AtumPO\Deliveries
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.1
 */

namespace AtumPO\Deliveries\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\PurchaseOrders\PurchaseOrders;


class DeliveryItem extends AtumOrderItemModel {

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

	/**
	 * Read an ATUM Order item from the database
	 *
	 * @since 0.9.2
	 *
	 * @throws AtumException
	 */
	protected function read() {

		try {

			parent::read();

			// Read the Delivery item props from db.
			$this->atum_order_item->set_props( array(
				'product_id'    => $this->get_meta( '_product_id' ),
				'variation_id'  => $this->get_meta( '_variation_id' ),
				'quantity'      => $this->get_meta( '_qty' ),
				'po_item_id'    => $this->get_meta( '_po_item_id' ),
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

		$product_id = $this->atum_order_item->get_variation_id() ?: $this->atum_order_item->get_product_id();

		AtumCalculatedProps::defer_update_atum_sales_calc_props( $product_id, AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

	}

}
