<?php
/**
 * Handle Product Levels customizations for Purchase Orders
 *
 * @since       0.9.13
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Integrations
 */

namespace AtumPO\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumLevels\Inc\Helpers as PLHelpers;
use AtumLevels\Inc\Orders as PLOrders;
use AtumLevels\Models\BOMModel;
use AtumLevels\Models\BOMOrderItemsModel;
use AtumLevels\Integrations\MultiInventory as PLMultiInventory;
use AtumLevels\Inc\Globals as PLGlobals;
use AtumMultiInventory\Inc\Helpers as MIHelpers;
use AtumMultiInventory\Models\Inventory;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Inc\Helpers;
use AtumPO\Models\POExtended;

class ProductLevels {

	/**
	 * The singleton instance holder
	 *
	 * @var ProductLevels
	 */
	private static $instance;

	/**
	 * Keep the current po item
	 *
	 * @since 0.9.23
	 *
	 * @var POItemProduct
	 */
	public static $current_po_item;

	/**
	 * Keep the current delivery item
	 *
	 * @since 0.9.23
	 *
	 * @var DeliveryItemProduct|DeliveryItemProductInventory
	 */
	public static $current_delivery_item;

	/**
	 * ProductLevels singleton constructor
	 *
	 * @since 0.9.13
	 */
	private function __construct() {

		// Disable automatic PO restocking from PL.
		add_filter( 'atum/product_levels/bypass_' . PurchaseOrders::POST_TYPE . '_stock_increase', '__return_true' );
		add_filter( 'atum/product_levels/bypass_' . PurchaseOrders::POST_TYPE . '_stock_decrease', '__return_true' );

		if ( is_admin() ) {

			// Update the search product data results for BOM-related products.
			add_filter( 'atum/purchase_orders_pro/ajax/search_product_data', array( $this, 'update_json_search_product_data' ), 10, 3 );

			// Update the search inventory data results for BOM-related inventories.
			add_filter( 'atum/purchase_orders_pro/multi_inventory_search_data', array( $this, 'update_json_search_inventory_data' ), 10, 4 );

			// Update the BOMs' stock before adding to stock.
			add_action( 'atum/purchase_orders_pro/delivery/before_stock_change', array( $this, 'before_stock_change' ), 10, 4 );

			// Queue the product to recalculate the BOM tree stock after changing the stock.
			add_action( 'atum/purchase_orders_pro/delivery/after_stock_change', array( $this, 'maybe_recalculate_bom_tree_stock' ), 10, 4 );

			// Check whether to insert BOM order item inventories after adding any inventory to stock.
			add_action( 'atum/purchase_orders_pro/after_add_delivery_item_inventory_to_stock', array( $this, 'maybe_add_bom_order_item_inventories' ), 10, 4 );

			// Check whether to remove BOM order item inventories after removing any inventory from stock.
			add_action( 'atum/purchase_orders_pro/after_restore_delivery_item_inventory_to_stock', array( $this, 'maybe_remove_bom_order_item_inventories' ), 10, 3 );

			// Add PL icon to delivery items with BOMs.
			add_action( 'atum/purchase_orders_pro/after_delivery_item_icons', array( $this, 'add_pl_icon_to_delivery_items' ), 9, 3 );

			// Add BOM trees to delivery items.
			add_action( 'atum/purchase_orders_pro/after_delivery_item', array( $this, 'add_bom_tree_to_delivery_items' ), 10, 4 );

			// Change the PL icon's tooltip text.
			add_filter( 'atum/product_levels/order_items/pl_icon', array( $this, 'pl_icon_tooltip_text' ) );

			/* MI + PL compatibility */
			if ( Addons::is_addon_active( 'multi_inventory' ) ) {

				if ( Helpers::is_po_post() ) {

					// Do not display the BOM tree on PO items as these will show on delivery items in PO PRO.
					remove_action( 'atum/multi_inventory/after_order_item_inventory_info', array( PLMultiInventory::get_instance(), 'display_order_item_inventory_bom_tree' ) );

					// Only show up the BOM tree for PO items with MI to which no inventories were assigned yet.
					remove_action( 'atum/atum_order/after_item_product_html', array( PLMultiInventory::get_instance(), 'call_display_order_item_bom_tree' ) );
					add_action( 'atum/atum_order/after_item_product_html', array( $this, 'maybe_call_display_order_item_bom_tree' ), 10, 2 );

					// Add BOM trees to delivery inventory items.
					add_action( 'atum/purchase_orders_pro/after_delivery_inventory_item', array( $this, 'add_bom_tree_to_delivery_inventory_items' ), 10, 5 );

					// Add a custom view for the inventory BOM tree in delivery items.
					add_filter( 'atum/load_view/' . ATUM_LEVELS_PATH . 'views/meta-boxes/order-items/multi-inventory/bom-mi-tree', array( $this, 'inventory_bom_tree_view' ), 10, 2 );

					// Add the expand/collapse icon to delivery inventory items with BOM trees.
					add_action( 'atum/purchase_orders_pro/delivery_inventory_icons', array( $this, 'add_inventory_bom_tree_toggler_icon' ), 10, 2 );

					// Add the delivery name to the BOM MI Management modal.
					add_action( 'atum/product_levels/bom_mi_management_item/before_product_name', array( $this, 'add_delivery_name_to_bom_mi_modal' ) );
				}

				// Get the BOM Order item inventories tree for Ajax calls.
				add_filter( 'atum/product_levels/ajax/bom_order_item_inventories', array( $this, 'get_ajax_order_item_inventory_bom_tree' ), 9, 4 ); // Before PL.

			}

		}

	}

	/**
	 * Update the search product data results for BOM-related products
	 *
	 * @since 0.9.20
	 *
	 * @param array       $results
	 * @param \WC_Product $product
	 * @param POExtended  $po
	 *
	 * @return array
	 */
	public function update_json_search_product_data( $results, $product, $po ) {

		if ( PLHelpers::is_bom_stock_control_enabled() && BOMModel::has_linked_bom( $product->get_id() ) ) {

			// Change the "Stock" label to "Calculated Stock".
			$stock_meta_key = key( wp_list_filter( $results['meta'] ?? [], [ 'label' => __( 'Stock', ATUM_PO_TEXT_DOMAIN ) ] ) );

			if ( ! is_null( $stock_meta_key ) ) {
				$results['meta'][ $stock_meta_key ]['label'] = __( 'Calculated Stock', ATUM_PO_TEXT_DOMAIN );
			}

		}

		return $results;

	}

	/**
	 * Update the search inventory data results for BOM-related inventories
	 *
	 * @since 0.9.20
	 *
	 * @param array       $inventory_data
	 * @param Inventory   $inventory
	 * @param \WC_Product $product
	 * @param POExtended  $po
	 *
	 * @return array
	 */
	public function update_json_search_inventory_data( $inventory_data, $inventory, $product, $po ) {

		// The main inventories are who have the calculated stock in top and middle levels.
		if ( $inventory->is_main() && PLHelpers::is_bom_stock_control_enabled() && BOMModel::has_linked_bom( $inventory->product_id ) ) {

			// Change the "Stock" label to "Calculated Stock".
			$stock_meta_key = key( wp_list_filter( $inventory_data['meta'] ?? [], [ 'label' => __( 'Stock', ATUM_PO_TEXT_DOMAIN ) ] ) );

			if ( ! is_null( $stock_meta_key ) ) {
				$inventory_data['meta'][ $stock_meta_key ]['label'] = __( 'Calculated Stock', ATUM_PO_TEXT_DOMAIN );
			}

		}

		return $inventory_data;

	}

	/**
	 * Change the BOMs' stock before adding PO items to stock
	 *
	 * @since 0.9.21
	 *
	 * @param DeliveryItemProduct $delivery_item
	 * @param int|float           $quantity
	 * @param string              $action
	 * @param Delivery            $delivery
	 */
	public function before_stock_change( $delivery_item, $quantity, $action, $delivery ) {

		// If the PO item no longer exists, getting the POItemProduct will throw an error.
		try {

			$po_item_id                  = $delivery_item->get_po_item_id();
			self::$current_po_item       = new POItemProduct( $po_item_id );
			self::$current_delivery_item = $delivery_item;

			// Only for products that have BOMs.
			if ( ! BOMModel::has_linked_bom( self::$current_po_item->get_product_id() ) ) {
				return;
			}

			$pl_orders     = PLOrders::get_instance();
			$order_type_id = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
			$product       = self::$current_po_item->get_product();

			// Make sure the product still exists.
			if ( ! $product instanceof \WC_Product ) {
				return;
			}

			// Allow adding/deducting a part of the item.
			if ( $quantity < self::$current_po_item->get_quantity() ) {
				// NOTE: The item shouldn't be saved after this change or would affect the original item.
				self::$current_po_item->set_quantity( $quantity );
			}

			if ( 'increase' === $action ) {

				$this->replace_bom_order_items_filters_when_increasing_stock();

				// Prevent increasing stock twice.
				$item_stock_changed = self::$current_po_item->get_stock_changed();

				if ( 'yes' !== $item_stock_changed ) {
					$pl_orders->increase_bom_stock_order_items( [ self::$current_po_item ], $order_type_id );
				}

				$this->restore_bom_order_items_filters_when_increasing_stock();

			}
			elseif ( 'decrease' === $action ) {

				$this->replace_bom_order_items_filters_when_decreasing_stock();
				self::$current_po_item->set_id( $delivery_item->get_id() );
				$this->reduce_bom_stock_order_items( self::$current_po_item );
				$this->restore_bom_order_items_filters_when_decreasing_stock();

			}

			PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );

			// Set the right stock changed meta.
			$delivery_item->set_stock_changed( 'increase' === $action );
			$delivery_item->save();

		} catch ( AtumException $e ) {

			if ( ATUM_DEBUG ) {
				error_log( __METHOD__ . '::' . __( 'The PO item does not exist', ATUM_PO_TEXT_DOMAIN ) );
			}

		}

		self::$current_po_item       = NULL;
		self::$current_delivery_item = NULL;

	}

	/**
	 * Check whether to add BOM order item inventories and adjust the tree stock after adding
	 *
	 * @param array                        $item_data
	 * @param Inventory                    $inventory
	 * @param DeliveryItemProductInventory $delivery_item_inventory
	 * @param Delivery                     $delivery
	 */
	public function maybe_add_bom_order_item_inventories( $item_data, $inventory, $delivery_item_inventory, $delivery ) {

		$product = AtumHelpers::get_atum_product( $inventory->product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// If the current product has linked BOM and the inventory added is the main inventory, add the BOM order item inventories.
		if ( $inventory->is_main() && BOMModel::has_linked_bom( $inventory->product_id ) ) {

			$this->replace_bom_order_items_filters_when_increasing_stock();

			$po_item_id = $delivery_item_inventory->get_po_item_id();

			// If the PO item no longer exists, getting the POItemProduct will throw an error.
			try {

				self::$current_po_item       = new POItemProduct( $po_item_id );
				self::$current_delivery_item = $delivery_item_inventory;
				$pl_orders                   = PLOrders::get_instance();
				$order_type_id               = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
				$quantity                    = $delivery_item_inventory->get_quantity();

				if ( $quantity < self::$current_po_item->get_quantity() ) {
					self::$current_po_item->set_quantity( $quantity );
				}

				// The hook is only executed when the delivery item inventory changes the stock.
				$pl_orders->increase_bom_stock_order_items( [ self::$current_po_item ], $order_type_id );
				PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );

			} catch ( AtumException $e ) {

				if ( ATUM_DEBUG ) {
					error_log( __METHOD__ . '::' . __( 'The PO item does not exist', ATUM_PO_TEXT_DOMAIN ) );
				}

			}

			self::$current_po_item       = NULL;
			self::$current_delivery_item = NULL;

			// TODO: Is reactivating these filters really necessary?
			$this->restore_bom_order_items_filters_when_increasing_stock();

		}

		if ( PLGlobals::is_bom_product( $product ) ) {
			PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );
		}

	}

	/**
	 * Check whether to remove BOM order item inventories after removing any inventory from stock.
	 *
	 * @since 0.9.23
	 *
	 * @param Inventory                    $inventory
	 * @param DeliveryItemProductInventory $delivery_item_inventory
	 * @param Delivery                     $delivery
	 */
	public function maybe_remove_bom_order_item_inventories( $inventory, $delivery_item_inventory, $delivery ) {

		$product = AtumHelpers::get_atum_product( $inventory->product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		// If the current product has linked BOM and the inventory removed is the main inventory, remove the BOM order item inventories.
		if ( $inventory->is_main() && BOMModel::has_linked_bom( $inventory->product_id ) ) {

			$this->replace_bom_order_items_filters_when_decreasing_stock();

			$po_item_id = $delivery_item_inventory->get_po_item_id();

			// If the PO item no longer exists, getting the POItemProduct will throw an error.
			try {

				self::$current_po_item       = new POItemProduct( $po_item_id );
				self::$current_delivery_item = $delivery_item_inventory;
				$pl_orders                   = PLOrders::get_instance();
				$order_type_id               = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
				$quantity                    = $delivery_item_inventory->get_quantity();

				if ( $quantity < self::$current_po_item->get_quantity() ) {
					self::$current_po_item->set_quantity( $quantity );
				}

				$this->reduce_bom_stock_order_items( self::$current_po_item );
				PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );

			} catch ( AtumException $e ) {

				if ( ATUM_DEBUG ) {
					error_log( __METHOD__ . '::' . __( 'The PO item does not exist', ATUM_PO_TEXT_DOMAIN ) );
				}

			}

			self::$current_po_item       = NULL;
			self::$current_delivery_item = NULL;

			// TODO: Is reactivating these filters really necessary?
			$this->restore_bom_order_items_filters_when_decreasing_stock();
		}

		if ( PLGlobals::is_bom_product( $product ) ) {
			PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );
		}

	}

	/**
	 * Override the BOM order items insert to insert the items attached to the current delivery
	 *
	 * @since 0.9.27
	 *
	 * @param bool                 $insert
	 * @param AtumOrderItemProduct $order_item
	 * @param int                  $order_type_id
	 * @param object               $linked_bom
	 * @param float                $qty
	 * @param \WC_Product          $bom_product
	 * @param int                  $accumulated_multiplier
	 *
	 * @return bool
	 */
	public function maybe_insert_delivery_bom_order_item( $insert, $order_item, $order_type_id, $linked_bom, $qty, $bom_product, $accumulated_multiplier ) {

		if ( $insert ) {

			$order_item_id = $order_item->get_id();

			// Ensure this is the correct behaviour.
			if ( AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) !== $order_type_id ||
				self::$current_po_item->get_id() !== $order_item_id ||
				self::$current_delivery_item->get_po_item_id() !== $order_item_id ) {

				return $insert;
			}

			$insert = apply_filters( 'atum/purchase_orders/maybe_insert_bom_order_item', $insert, $order_item, $order_type_id, $linked_bom, $qty, $bom_product, $accumulated_multiplier );

			if ( $insert ) {

				BOMOrderItemsModel::insert_bom_order_item( self::$current_delivery_item->get_id(), $order_type_id, $bom_product->get_id(), $bom_product->get_type(), $qty );
				$insert = FALSE;
			}

		}

		return $insert;

	}

	/**
	 * Reduce the stock of the BOM products linked to an order item product from an order.
	 * This is a simplified version of PLOrders->reduce_bom_stock_order_items to deal with delivery items
	 *
	 * @since 0.9.27
	 *
	 * @param POItemProduct $po_order_item
	 */
	public function reduce_bom_stock_order_items( $po_order_item ) {

		if ( 'yes' !== self::$current_delivery_item->get_stock_changed() ) {
			return;
		}

		$delivery_item_id   = self::$current_delivery_item->get_id();
		$order_item_product = AtumHelpers::get_atum_product( $po_order_item->get_variation_id() ?: $po_order_item->get_product_id() );
		$order_type_id      = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );

		if ( ! $order_item_product instanceof \WC_Product ) {
			return;
		}

		// PO Order: get saved BOMs to increase stock levels.
		$bom_order_items = BOMOrderItemsModel::get_bom_order_items( $delivery_item_id, $order_type_id );

		if ( ! empty( $bom_order_items ) ) {

			foreach ( $bom_order_items as $bom_order_item ) {

				$qty = $bom_order_item->qty;

				if ( $qty ) {

					$bom_product = AtumHelpers::get_atum_product( $bom_order_item->bom_id );

					if ( PLGlobals::is_bom_product( $bom_product ) && apply_filters( 'atum/product_levels/maybe_decrease_bom_stock_order_items', TRUE, $po_order_item, $bom_order_item->bom_id, $bom_order_item->qty, NULL, $order_type_id ) ) {

						// Allow changing the qty to update externally.
						$update_qty = apply_filters( 'atum/product_levels/update_stock_quantity', $qty, $bom_product, $order_item_product );

						wc_update_product_stock( $bom_product, $update_qty, 'decrease' );

					}
				}

			}

			BOMOrderItemsModel::clean_bom_order_items( $delivery_item_id, $order_type_id );

			PLHelpers::defer_recalculate_bom_tree_stock( $order_item_product->get_id(), [
				'order_id'  => $delivery_item_id,
				'action'    => 'decrease',
				'old_stock' => $order_item_product->get_stock_quantity(),
				'new_stock' => $order_item_product->get_stock_quantity() - self::$current_delivery_item->get_quantity(),
			] );
		}

		do_action( 'atum/product_levels/after_reduce_bom_stock_order_items', [ $po_order_item ], $order_type_id );

	}

	/**
	 * If the delivery has changed the product's stock and it's a BOM, recalculate al BOM tree stock
	 *
	 * @since 0.9.27
	 *
	 * @param DeliveryItemProduct $delivery_item  The delivery item .
	 * @param int|float           $quantity       The quantity amount changed.
	 * @param string              $action         Possible values: 'increase', 'decrease', 'set'.
	 * @param Delivery            $delivery       The delivery object.
	 */
	public function maybe_recalculate_bom_tree_stock( $delivery_item, $quantity, $action, $delivery ) {

		if ( ! PLHelpers::is_bom_stock_control_enabled() ) {
			return;
		}

		$product = $delivery_item->get_product();

		if ( PLGlobals::is_bom_product( $product ) ) {
			PLHelpers::defer_recalculate_bom_tree_stock( $product->get_id() );
		}
	}

	/**
	 * Remove PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function replace_bom_order_items_filters_when_increasing_stock() {

		add_filter( 'atum/product_levels/maybe_insert_bom_order_item', array( $this, 'maybe_insert_delivery_bom_order_item' ), 9, 7 );

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			add_filter( 'atum/product_levels/bom_order_items_transient_order_id', array( MultiInventory::get_instance(), 'override_bom_order_items_transient_order_id' ), 10, 2 );
			add_filter( 'atum/product_levels/bom_order_items_transient', array( MultiInventory::get_instance(), 'override_bom_order_items_transient' ), 10, 3 );
			add_filter( 'atum/product_levels/get_bom_order_items/order_item_id', array( MultiInventory::get_instance(), 'override_bom_order_item_id' ), 10, 2 );
		}
		$this->replace_common_bom_order_filters();

	}

	/**
	 * Restore PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function restore_bom_order_items_filters_when_increasing_stock() {

		remove_filter( 'atum/product_levels/maybe_insert_bom_order_item', array( $this, 'maybe_insert_delivery_bom_order_item' ), 9 );

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			remove_filter( 'atum/product_levels/bom_order_items_transient_order_id', array( MultiInventory::get_instance(), 'override_bom_order_items_transient_order_id' ), 10 );
			remove_filter( 'atum/product_levels/bom_order_items_transient', array( MultiInventory::get_instance(), 'override_bom_order_items_transient' ), 10 );
			remove_filter( 'atum/product_levels/get_bom_order_items/order_item_id', array( MultiInventory::get_instance(), 'override_bom_order_item_id' ), 10 );
		}
		$this->restore_common_bom_order_items_filters();

	}

	/**
	 * Remove PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function replace_bom_order_items_filters_when_decreasing_stock() {

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			add_filter( 'atum/product_levels/bom_order_items_transient_order_id', array( MultiInventory::get_instance(), 'override_bom_order_items_transient_order_id' ), 10, 2 );
			add_filter( 'atum/product_levels/bom_order_items_transient', array( MultiInventory::get_instance(), 'override_bom_order_items_transient' ), 10, 3 );
			add_filter( 'atum/product_levels/get_bom_order_items/order_item_id', array( MultiInventory::get_instance(), 'override_bom_order_item_id' ), 10, 2 );
		}
		$this->replace_common_bom_order_filters();

	}

	/**
	 * Restore PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function restore_bom_order_items_filters_when_decreasing_stock() {

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {
			remove_filter( 'atum/product_levels/bom_order_items_transient_order_id', array( MultiInventory::get_instance(), 'override_bom_order_items_transient_order_id' ), 10 );
			remove_filter( 'atum/product_levels/bom_order_items_transient', array( MultiInventory::get_instance(), 'override_bom_order_items_transient' ), 10 );
			remove_filter( 'atum/product_levels/get_bom_order_items/order_item_id', array( MultiInventory::get_instance(), 'override_bom_order_item_id' ), 10 );
		}
		$this->restore_common_bom_order_items_filters();

	}

	/**
	 * Replace common (when increasing and decreasing stock) PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function replace_common_bom_order_filters() {

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {

			remove_filter( 'atum/product_levels/process_and_get_bom_order_items/order_item_qty', array( PLMultiInventory::get_instance(), 'adjust_bom_order_item_qty' ) );
			remove_filter( 'atum/product_levels/process_and_get_bom_order_items/order_item_reduced_qty', array( PLMultiInventory::get_instance(), 'adjust_bom_order_item_reduced_qty' ) );
			//remove_filter( 'atum/product_levels/maybe_insert_bom_order_item', array( PLMultiInventory::get_instance(), 'maybe_insert_bom_order_item' ) );

			remove_action( 'atum/product_levels/before_clean_bom_order_items', array( PLMultiInventory::get_instance(), 'move_bom_order_items_before_clean' ) );
			remove_action( 'atum/product_levels/after_get_bom_order_items', array( PLMultiInventory::get_instance(), 'maybe_refresh_bom_order_transient' ) );

		}
	}

	/**
	 * Restore common (when increasing and decreasing stock) PL BOM order items filters that interfere with PO PRO.
	 *
	 * @since 0.9.23
	 */
	private function restore_common_bom_order_items_filters() {

		if ( Addons::is_addon_active( 'multi_inventory' ) ) {

			add_filter( 'atum/product_levels/process_and_get_bom_order_items/order_item_qty', array( PLMultiInventory::get_instance(), 'adjust_bom_order_item_qty' ), 10, 4 );
			add_filter( 'atum/product_levels/process_and_get_bom_order_items/order_item_reduced_qty', array( PLMultiInventory::get_instance(), 'adjust_bom_order_item_reduced_qty' ), 10, 4 );
			//add_filter( 'atum/product_levels/maybe_insert_bom_order_item', array( PLMultiInventory::get_instance(), 'maybe_insert_bom_order_item' ), 10, 7 );

			add_action( 'atum/product_levels/before_clean_bom_order_items', array( PLMultiInventory::get_instance(), 'move_bom_order_items_before_clean' ), 10, 2 );
			add_action( 'atum/product_levels/after_get_bom_order_items', array( PLMultiInventory::get_instance(), 'maybe_refresh_bom_order_transient' ), 10, 2 );

		}

	}

	/**
	 * Add the PL icon to distinguish the order items with BOMs
	 *
	 * @since 0.9.24
	 *
	 * @param int           $item_id
	 * @param POItemProduct $item
	 * @param \WC_Product   $product
	 */
	public function add_pl_icon_to_delivery_items( $item_id, $item, $product ) {

		if ( $product instanceof \WC_Product ) :

			$product_id = $product->get_id();

			if ( $item instanceof POItemProduct ) :

				if ( BOMModel::has_linked_bom( $product_id ) ) : ?>
					<i class="atmi-tree atum-tooltip" data-tip="<?php esc_attr_e( 'This item has linked BOM', ATUM_PO_TEXT_DOMAIN ) ?>"></i>
					<?php
					if ( ! Addons::is_addon_active( 'multi_inventory' ) || ! MIHelpers::has_multi_inventory( $product_id ) ) : ?>
						<i class="collapse-tree atum-icon collapsed atum-tooltip" title="<?php esc_attr_e( 'Toggle BOM tree', ATUM_PO_TEXT_DOMAIN ); ?>"></i>
					<?php endif;

				endif;

			endif;

		endif;

	}

	/**
	 * Change the text shown on the PL icon's tooltip
	 *
	 * @since 0.9.24
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function pl_icon_tooltip_text( $text ) {
		return __( 'This item has linked BOM', ATUM_PO_TEXT_DOMAIN );
	}

	/**
	 * Add the expand/collapse icon to delivery inventory items with BOM trees.
	 *
	 * @since 0.9.24
	 *
	 * @param POItemProduct $po_item
	 * @param Inventory     $inventory
	 */
	public function add_inventory_bom_tree_toggler_icon( $po_item, $inventory ) {

		$product = $po_item->get_product();

		if ( BOMModel::has_linked_bom( $product->get_id() ) && $inventory->is_main() ) : ?>
			<i class="collapse-tree atum-icon collapsed atum-tooltip" title="<?php esc_attr_e( 'Toggle BOM tree', ATUM_PO_TEXT_DOMAIN ); ?>"></i>
		<?php endif;

	}

	/**
	 * Call PL's display_order_item_bom_tree from do_action call without item_id
	 *
	 * @since 0.9.24
	 *
	 * @param POItemProduct  $item
	 * @param AtumOrderModel $atum_order
	 *
	 * @throws \Exception
	 */
	public function maybe_call_display_order_item_bom_tree( $item, $atum_order ) {

		// Only for PO PRO.
		if ( $atum_order instanceof POExtended && $item instanceof POItemProduct ) {

			$product = $item->get_product();

			if ( $product && $product->exists() && Addons::is_addon_active( 'multi_inventory' ) && MIHelpers::has_multi_inventory( $product ) ) {

				$order_item_inventories = Inventory::get_order_item_inventories( $item->get_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

				// Only show up the BOM tree for order items with MI enabled but not inventories assigned yet.
				if ( empty( $order_item_inventories ) ) {
					PLMultiInventory::get_instance()->display_order_item_bom_tree( $item->get_id(), $item, $atum_order, $item->get_quantity() );
				}
			}

		}

	}

	/**
	 * Add BOM trees to delivery items
	 *
	 * @since 0.9.24
	 *
	 * @param POItemProduct       $po_item
	 * @param \WC_Product         $product
	 * @param Delivery            $delivery
	 * @param DeliveryItemProduct $delivery_item
	 */
	public function add_bom_tree_to_delivery_items( $po_item, $product, $delivery, $delivery_item ) {

		// Add a custom view for the BOM trees in delivery items.
		$bom_tree_view_hook = 'atum/load_view/' . ATUM_LEVELS_PATH . 'views/meta-boxes/order-items/bom-tree';
		if ( ! has_filter( $bom_tree_view_hook, array( $this, 'bom_tree_view' ) ) ) {
			add_filter( $bom_tree_view_hook, array( $this, 'bom_tree_view' ), 10, 2 );
		}

		// The MI products will have the BOM trees on the main inventory (if any).
		if (
			$product instanceof \WC_Product && $po_item instanceof POItemProduct &&
			Addons::is_addon_active( 'multi_inventory' ) && ! MIHelpers::has_multi_inventory( $product )
		) {
			$po = AtumHelpers::get_atum_order_model( $po_item->get_atum_order_id(), TRUE, PurchaseOrders::POST_TYPE );
			PLMultiInventory::get_instance()->display_order_item_bom_tree( $po_item->get_id(), $delivery_item, $po, $delivery_item->get_quantity() );
		}

	}

	/**
	 * Add the BOM trees to the main inventories with BOMs in delivery inventory items
	 *
	 * @since 0.9.24
	 *
	 * @param DeliveryItemProductInventory $delivery_inventory_item_obj
	 * @param \WC_Product                  $product
	 * @param Delivery                     $delivery
	 * @param object                       $delivery_inventory_item
	 * @param Inventory                    $inventory
	 */
	public function add_bom_tree_to_delivery_inventory_items( $delivery_inventory_item_obj, $product, $delivery, $delivery_inventory_item, $inventory ) {

		if ( $inventory->is_main() && BOMModel::has_linked_bom( $product->get_id() ) ) {
			PLMultiInventory::get_instance()->display_order_item_inventory_bom_tree( $inventory, $delivery_inventory_item, $delivery_inventory_item_obj, AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ), $delivery_inventory_item->qty, $delivery );
		}

	}

	/**
	 * Add a custom template for the BOM tree view in delivery items
	 *
	 * @since 0.9.24
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 */
	public function bom_tree_view( $view, $args ) {

		// Only for PO PRO.
		if ( ! Helpers::is_po_post() ) {
			return $view;
		}

		return ATUM_PO_PATH . 'views/meta-boxes/deliveries/product-levels/bom-tree';
	}

	/**
	 * Add a custom template for the inventory BOM tree view
	 *
	 * @since 0.9.24
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 */
	public function inventory_bom_tree_view( $view, $args ) {

		// Only for PO PRO.
		if ( ! Helpers::is_po_post() ) {
			return $view;
		}

		return ATUM_PO_PATH . 'views/meta-boxes/deliveries/multi-inventory/bom-mi-tree';
	}

	/**
	 * Add the delivery name to the BOM MI Management modal
	 *
	 * @since 0.9.24
	 *
	 * @param DeliveryItemProduct|DeliveryItemProductInventory $order_item
	 */
	public function add_delivery_name_to_bom_mi_modal( $order_item ) {

		if ( $order_item instanceof DeliveryItemProduct || $order_item instanceof DeliveryItemProductInventory ) : ?>
			<?php $delivery = new Delivery( $order_item->get_atum_order_id() ); ?>
			<div class="delivery-name"><?php echo esc_html( $delivery->name ) ?></div>
		<?php endif;

	}

	/**
	 * Return an Inventories BOM tree for Ajax calls after updating the BOM item transient for Deliveries.
	 *
	 * @since 0.9.28
	 *
	 * @param string $content
	 * @param int    $order_item_id
	 * @param int    $order_id
	 * @param array  $tree_bom_order_items
	 *
	 * @return string
	 */
	public function get_ajax_order_item_inventory_bom_tree( $content, $order_item_id, $order_id, $tree_bom_order_items ) {

		$order_type = get_post_type( $order_id );

		if ( Deliveries::POST_TYPE === $order_type ) {

			// Prevent PL MI from executing.
			remove_filter( 'atum/product_levels/ajax/bom_order_item_inventories', array( PLMultiInventory::get_instance(), 'get_ajax_order_item_inventory_bom_tree' ) );

			$order_type_id = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
			$order         = AtumHelpers::get_atum_order_model( $order_id, TRUE );
			$order_item    = $order->get_atum_order_item( $order_item_id );

			if ( $order_item instanceof DeliveryItemProductInventory ) {

				$inventory_id = $order_item->get_inventory_id();
				$inventory    = new Inventory( $inventory_id );

				if ( $inventory->is_main ) {

					$item_product            = AtumHelpers::get_atum_product( $inventory->product_id );
					$bom_order_items         = [];
					$order_item_qty          = $order_item->get_quantity();
					$unsaved_bom_order_items = $tree_bom_order_items;
					$order_item_inventory    = FALSE; // Only can edit the tree via Ajax if nothing is delivered -> no order item inventories can exist.
					$is_completed            = 'yes' === $order_item->get_meta( '_stock_changed' );

					$content = AtumHelpers::load_view_to_string( ATUM_LEVELS_PATH . 'views/meta-boxes/order-items/multi-inventory/bom-mi-tree', compact( 'inventory', 'order_item_inventory', 'order_item', 'order_item_qty', 'order_type_id', 'bom_order_items', 'unsaved_bom_order_items', 'item_product', 'order', 'is_completed' ) );

				}

			}

		}
		return $content;

	}


	/********************
	 * Instance methods
	 ********************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_PO_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return ProductLevels instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
