<?php
/**
 * The model class for the Delivery Item Product Inventory objects
 * Multi-Inventory compatibility only
 *
 * @package         AtumPO\Deliveries
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.3
 */

namespace AtumPO\Deliveries\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Inc\Globals as AtumGlobals;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumMultiInventory\Inc\Helpers as MIHelpers;
use AtumMultiInventory\Models\Inventory;
use AtumPO\Abstracts\AtumPOOrders\Items\AtumPOOrderItemProductTrait;
use AtumPO\Deliveries\Models\DeliveryItemInventory;


class DeliveryItemProductInventory extends AtumOrderItemProduct {

	/**
	 * The Delivery item invenotory data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'inventory_id'  => 0,
		'po_item_id'    => 0, // The PO item to which is linked this delivery inventory item.
		'quantity'      => 1,
		'stock_changed' => 'no',
		'returned_qty'  => 0,
	);

	/**
	 * Meta keys reserved for internal use
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array( '_inventory_id', '_po_item_id', '_qty', '_stock_changed', '_returned_qty' );

	/**
	 * DeliveryItemProductInventory constructor
	 *
	 * @param int $item
	 */
	public function __construct( $item = 0 ) {
		
		parent::__construct( $item );
		
	}

	/**
	 * Load the Delivery item inventory
	 *
	 * @since 0.9.3
	 */
	protected function load() {

		/* @noinspection PhpParamsInspection PhpUnhandledExceptionInspection */
		$this->atum_order_item_model = new DeliveryItemInventory( $this );

		if ( ! $this->atum_order_id ) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

	/**
	 * Saves an item's metadata to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 0.9.3
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/purchase_orders_pro/delivery_item_product_inventory/save_data', array(
			'_inventory_id'  => $this->get_inventory_id( 'edit' ),
			'_po_item_id'    => $this->get_po_item_id( 'edit' ),
			'_qty'           => $this->get_quantity( 'edit' ),
			'_stock_changed' => $this->get_stock_changed( 'edit' ),
			'_returned_qty'  => $this->get_returned_qty( 'edit' ),
		), $this );

		$this->atum_order_item_model->save_meta( $save_values );

	}

	/**
	 * Save and calculate inventory inbound stock after saving.
	 *
	 * @since 1.0.5
	 *
	 * @return int
	 */
	public function save() {

		$atum_order_item_id = parent::save();

		if ( $atum_order_item_id ) {
			$inventory = MIHelpers::get_inventory( $this->get_inventory_id() );
			MIHelpers::update_order_item_inventories_sales_calc_props( $inventory, AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
		}

		return $atum_order_item_id;

	}

	/**
	 * Get delivery item inventory type.
	 *
	 * @since 0.9.3
	 *
	 * @return string
	 */
	public function get_type() {
		return 'delivery_item_inventory';
	}

	/**
	 * Get the associated inventory ID.
	 *
	 * @since 0.9.3
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 *
	 * @return int
	 */
	public function get_inventory_id( $context = 'view' ) {
		return absint( $this->get_prop( 'inventory_id', $context ) );
	}

	/**
	 * Get the expected quantity from the associated PO order item inventory
	 *
	 * @since 0.9.3
	 *
	 * @return float|int
	 */
	public function get_expected_qty() {

		$po_item_inventories = Inventory::get_order_item_inventories( $this->get_po_item_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
		$po_item_inventories = current( wp_list_filter( $po_item_inventories, [ 'inventory_id' => $this->get_inventory_id() ] ) );

		return (float) $po_item_inventories->qty ?? 0;

	}

	/**
	 * Get the delivery item associated to this delivery item inventory
	 *
	 * @since 0.9.5
	 *
	 * @return DeliveryItemProduct|bool
	 */
	public function get_associated_delivery_item() {

		global $wpdb;

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "
			SELECT aoi.order_item_id FROM $atum_order_items_table aoi
			LEFT JOIN $atum_order_items_meta_table aoim ON (aoi.order_item_id = aoim.order_item_id AND meta_key = '_po_item_id')
			WHERE order_item_type = 'delivery_item' AND order_id = %d AND aoim.meta_value = %d
		", $this->get_atum_order_id(), $this->get_po_item_id() );
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$delivery_item_id = $wpdb->get_var( $sql );

		if ( $delivery_item_id ) {
			return new DeliveryItemProduct( $delivery_item_id );
		}

		return FALSE;

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

	/**
	 * Setter for the inventory_id prop
	 *
	 * @since 0.9.3
	 *
	 * @param int $value
	 */
	public function set_inventory_id( $value ) {
		$this->set_prop( 'inventory_id', absint( $value ) );
	}

	use AtumPOOrderItemProductTrait;

}
