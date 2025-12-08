<?php
/**
 * The class for the Delivery Item Product objects
 *
 * @package         AtumPO\Deliveries
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.1
 */

namespace AtumPO\Deliveries\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCalculatedProps;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Abstracts\AtumPOOrders\Items\AtumPOOrderItemProductTrait;
use AtumPO\Deliveries\Models\DeliveryItem;
use Atum\Inc\Globals as AtumGlobals;

class DeliveryItemProduct extends AtumOrderItemProduct {

	/**
	 * The Delivery item data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'product_id'    => 0,
		'variation_id'  => 0,
		'po_item_id'    => 0,
		'quantity'      => 1,
		'stock_changed' => 'no',
		'returned_qty'  => 0,
	);

	/**
	 * Meta keys reserved for internal use
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array( '_product_id', '_variation_id', '_po_item_id', '_qty', '_stock_changed', '_returned_qty' );

	/**
	 * Load the Delivery item
	 *
	 * @since 0.9.0
	 */
	protected function load() {

		/* @noinspection PhpParamsInspection PhpUnhandledExceptionInspection */
		$this->atum_order_item_model = new DeliveryItem( $this );

		if ( ! $this->atum_order_id ) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

	/**
	 * Save and calculate inventory inbound stock after saving.
	 *
	 * @since 1.0.5
	 *
	 * @return int|void
	 */
	public function save() {

		$atum_order_item_id = parent::save();

		if ( $atum_order_item_id ) {
			AtumCalculatedProps::defer_update_atum_sales_calc_props( $this->get_product_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
		}

	}

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 0.9.2
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/purchase_orders_pro/delivery_item_product/save_data', array(
			'_product_id'    => $this->get_product_id( 'edit' ),
			'_variation_id'  => $this->get_variation_id( 'edit' ),
			'_po_item_id'    => $this->get_po_item_id( 'edit' ),
			'_qty'           => $this->get_quantity( 'edit' ),
			'_stock_changed' => $this->get_stock_changed( 'edit' ),
			'_returned_qty'  => $this->get_returned_qty( 'edit' ),
		), $this );

		$this->atum_order_item_model->save_meta( $save_values );

	}

	/**
	 * Get delivery item type.
	 *
	 * @since 0.9.2
	 *
	 * @return string
	 */
	public function get_type() {
		return 'delivery_item';
	}

	/**
	 * Get the returned qty for this item (if any).
	 *
	 * @since 1.1.2
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_returned_qty( $context = 'view' ) {
		return $this->get_prop( 'returned_qty', $context );
	}

	/**
	 * Setter for the returned_qty prop
	 *
	 * @since 1.1.2
	 *
	 * @param int|float $value
	 */
	public function set_returned_qty( $value ) {
		$this->set_prop( 'returned_qty', wc_stock_amount( $value ) );
	}

	use AtumPOOrderItemProductTrait;

}
