<?php
/**
 * Handle the Multi-Inventory customizations for Purchase Orders.
 *
 * @package     AtumPO\Integrations
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.7.4
 */

namespace AtumPO\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Components\AtumStockDecimals;
use Atum\Inc\Globals as AtumGlobals;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumLevels\Models\BOMModel;
use AtumLevels\Models\BOMOrderItemsModel;
use AtumMultiInventory\Inc\MultiPrice as MIMultiPrice;
use AtumMultiInventory\Models\Inventory;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Inc\Helpers;
use AtumPO\Inc\ReturningPOs;
use AtumPO\Models\POExtended;
use Atum\Inc\Helpers as AtumHelpers;
use AtumMultiInventory\Inc\Helpers as MIHelpers;
use AtumLevels\Inc\Helpers as PLHelpers;


class MultiInventory {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiInventory
	 */
	private static $instance;

	/**
	 * Store the inventories to exclude from searches for the current order
	 *
	 * @var array
	 */
	private $inventory_exclusions = [];

	/**
	 * Take control over the restored delivery item inventories
	 *
	 * @var array
	 */
	private $returned_delivery_item_inventory_qtys = [];

	/**
	 * MultiInventory singleton constructor
	 *
	 * @since 0.7.4
	 */
	private function __construct() {

		if ( is_admin() ) {

			$this->alter_mi_views();

			// Bypass the order item subtotal calculation to avoid wrong calculations for order items with MI.
			// TODO: IS THIS STILL NECESSARY SINCE WE ARE CHECKING INSIDE THIS FUNCTION IF IT'S A MI PRODUCT WITH MULTI-PRICE?
			add_action( 'atum/purchase_orders_pro/before_load_items', array( MIMultiPrice::get_instance(), 'bypass_get_order_item_subtotal' ) );

			// Add the order item inventories' extra data for the discount and taxes.
			add_filter( 'atum/multi_inventory/order_item_inventory/extra_data', array( $this, 'add_mi_line_extra_data' ), 10, 3 );

			// Add the inventories to the product searches when adding items to PO.
			add_filter( 'atum/purchase_orders_pro/ajax/search_product_data', array( $this, 'add_inventories_to_product_search' ), 10, 3 );

			// Handle the exclusions for the AddItemModal's search.
			add_filter( 'atum/purchase_orders_pro/add_item_search_excluded', array( $this, 'handle_product_search_exclusions' ), 10, 2 );
			add_filter( 'atum/purchase_orders_pro/add_item_search_excluded_data', array( $this, 'handle_product_search_inventory_exclusions' ), 10, 3 );

			// Add order item inventories from the AddItemModal component.
			add_filter( 'atum/purchase_orders_pro/after_adding_order_items', array( $this, 'add_order_item_inventories' ), 10, 3 );

			// Clone MI order items after cloning a PO.
			add_action( 'atum/purchase_orders_pro/po_duplicate_after_save', array( $this, 'maybe_clone_order_item_inventories' ), 10, 2 );

			// Add MI icon to delivery items with MI.
			add_action( 'atum/purchase_orders_pro/after_delivery_item_icons', array( $this, 'add_mi_icon_to_delivery_items' ), 10, 3 );

			// Add a CSS class to the delivery items' rows.
			add_filter( 'atum/purchase_orders_pro/delivery_item_css_class', array( $this, 'delivery_item_css_class' ), 10, 3 );

			// Add the inventories to delivery items.
			add_action( 'atum/purchase_orders_pro/after_add_delivery_modal_item', array( $this, 'add_inventories_to_delivery_items' ), 10, 2 );
			add_action( 'atum/purchase_orders_pro/after_edit_delivery_modal_item', array( $this, 'add_inventories_to_delivery_items' ), 10, 3 );
			add_action( 'atum/purchase_orders_pro/after_delivery_item', array( $this, 'add_inventories_to_delivery_items' ), 10, 3 );

			// Add inventory order items from the "Add Delivery Modal".
			add_action( 'atum/purchase_orders_pro/ajax/before_add_delivery_item', array( $this, 'maybe_add_delivery_item_inventory' ), 10, 3 );

			// Edit inventory order items from the "Edit Delivery Modal".
			add_action( 'atum/purchase_orders_pro/ajax/before_edit_delivery_item', array( $this, 'maybe_edit_delivery_item_inventory' ), 10, 3 );

			// Do not show items with MI enabled but without MIs in delivery modals.
			add_filter( 'atum/purchase_orders_pro/delivery_modal/show_item', array( $this, 'maybe_show_mi_delivery_item' ), 10, 2 );

			// Adjust the MI product's quantity after removing a delivery item inventory.
			add_filter( 'atum/purchase_orders_pro/ajax/before_remove_delivery_item', array( $this, 'before_remove_delivery_item' ), 10, 2 );

			// Restore the inventories after deleting a delivery.
			add_action( 'atum/purchase_orders_pro/delivery/after_restock_delivery_item', array( $this, 'restore_delivery_item_inventories' ), 10, 3 );

			// Add delivery inventory items to stock.
			add_action( 'atum/purchase_orders_pro/ajax/before_add_delivery_item_to_stock', array( $this, 'add_delivery_item_inventory_to_stock' ), 10, 2 );

			// Prevent products with MI enabled from being discounted directly.
			add_filter( 'atum/purchase_orders_pro/delivery/allow_change_stock', array( $this, 'skip_mi_products_from_add_to_stock' ), 10, 2 );

			// Register MI items as supplier items when creating a new PO from AddToPO modal and the "multiple POs" option is enabled.
			add_filter( 'atum/purchase_orders_pro/add_items_to_po/supplier_items', array( $this, 'add_to_po_mi_supplier_items' ), 10, 2 );

			// Add items in bulk to PO from AddToPO modals.
			add_filter( 'atum/purchase_orders_pro/bulk_add_to_po', array( $this, 'bulk_add_mi_to_po' ), 10, 3 );

			// Get MI items from AddToPO modals.
			add_filter( 'atum/purchase_orders_pro/get_add_to_po_items', array( $this, 'get_add_to_po_mi_items' ), 10, 2 );

			// Print the MI items to the AddToPO modal.
			add_action( 'atum/purchase_orders_pro/add-to-po-modal/print_item', array( $this, 'print_add_to_po_mi_items' ), 10, 2 );

			// Add the right CSS classes to the AddToPO modal items.
			add_filter( 'atum/purchase_orders_pro/add_to_po/item_row_classes', array( $this, 'add_to_po_items_classes' ), 10, 2 );

			// Change the MI icon on PO items.
			add_filter( 'atum/multi_inventory/meta_boxes/order_items/mi_icon', array( $this, 'po_item_mi_icon' ) );

			// Add the MI items when creating POs from orders from the WC Orders bulk actions.
			add_action( 'atum/purchase_orders_pro/create_pos_from_orders/before_create_po', array( $this, 'add_mi_items_to_create_pos_from_orders' ), 10, 4 );

			// Allow getting the right stock quantity when browsing a PO with MI items.
			add_filter( 'atum/multi_inventory/bypass_mi_get_stock_quantity', array( $this, 'maybe_bypass_mi_get_stock_quantity' ) );

			// Remove the auto-assign inventories to POs from ATUM settings (not supported).
			add_filter( 'atum/settings/defaults', array( $this, 'change_mi_settings_defaults' ), 12 );

			// Add the create inventory modal's JS template to POs.
			add_action( 'atum/purchase_orders_pro/after_items_meta_box', array( $this, 'add_mi_js_templates' ) );

			// Add the batch tracking box to the POs List Table.
			if ( 'yes' === AtumHelpers::get_option( 'mi_batch_tracking', 'no' ) ) {
				add_filter( 'atum/purchase_orders_pro/extra_filters', array( $this, 'add_batch_tracking_filter' ) );
				add_filter( 'atum/purchase_orders_pro/extra_filters/no_auto_filter', array( $this, 'batch_tracking_no_auto_filter' ) );
				add_action( 'atum/purchase_orders_pro/list_table/after_nav_filters', array( $this, 'add_auto_filters' ) );
			}

			// Merge inventories on merge order items.
			add_action( 'atum/purchase_orders_pro/merge_order_items', array( $this, 'merge_order_items' ), 10, 2 );

			// Handle the MI Batch tracking extra filter.
			add_filter( 'atum/purchase_orders_pro/list_table/extra_filter', array( $this, 'pos_list_batch_tracking' ), 10, 2 );

			// Add inventories on merge order items.
			add_action( 'atum/purchase_orders_pro/merge_po/add_delivery_item_inventory', array( $this, 'duplicate_delivery_item_inventory' ), 10, 2 );

			// Register the extra MI's posted data keys to be able to save them when saving an Extended PO.
			add_filter( 'atum/order/posted_data_keys', array( $this, 'add_mi_posted_data_keys' ), 10, 2 );

			// Check if a MI item was fully added to stock.
			add_filter( 'atum/purchase_orders_pro/po_items_not_added_to_stock/stock_changed', array( $this, 'get_po_inventory_items_not_added_to_stock' ), 10, 3 );

			// Maybe remove delivery item after removing a delivery item inventory.
			add_action( 'atum/purchase_orders_pro/delivery/after_remove_item', array( $this, 'after_remove_delivery_item' ), 10, 5 );

			// Recalculate the inventory Gross Profit value in SC within the PO Settings.
			add_filter( 'atum/multi_inventory/list_tables/column_calc_gross_profit', array( $this, 'calculate_inventory_gross_profit' ), 10, 2 );

			// Search out stock/low stock/restock status inventories from a supplier for adding to PO.
			add_filter( 'atum/purchase_orders_pro/ajax/add_supplier_items', array( $this, 'add_supplier_items_to_po' ), 10, 4 );

			// Modify the inventory's inbound stock SQL query.
			add_filter( 'atum/inventory_inbound_stock/sql_select', array( $this, 'inventory_inbound_stock_select' ), 10, 2 );
			add_filter( 'atum/inventory_inbound_stock/sql_joins', array( $this, 'inventory_inbound_stock_joins' ), 10, 2 );

			// Prepare inventories to add to PO order items.
			add_filter( 'atum/purchase_orders/ajax/prepare_items', array( $this, 'prepare_inventories' ), 10, 2 );

		}

		// As we aren't sending the subtotal fields for MI lines, we need to calculate the right value.
		add_filter( 'atum/multi_inventory/calculate_update_mi_order_lines/line_subtotal', array( $this, 'get_mi_line_subtotal' ), 10, 4 );

		// Add support for delivery item inventories.
		add_filter( 'atum/purchase_orders_pro/delivery/item_class_name', array( $this, 'get_delivery_item_inventory_class' ), 10, 3 );
		add_filter( 'atum/purchase_orders_pro/delivery/get_items_key', array( $this, 'get_delivery_item_inventory_key' ), 10, 2 );
		add_filter( 'atum/purchase_orders_pro/delivery/get_items_class', array( $this, 'get_delivery_item_inventories_class' ), 10, 2 );
		add_filter( 'atum/purchase_orders_pro/delivery/item_type_to_group', array( $this, 'add_delivery_item_inventory_group' ) );
		add_filter( 'atum/purchase_orders_pro/delivery/item_group_to_type', array( $this, 'add_delivery_item_inventory_group_to_type' ) );

		// Disable the automatic MI stock changes when switching the PO status.
		add_filter( 'atum/multi_inventory/bypass_' . PurchaseOrders::get_post_type() . '_stock_change', '__return_true', 10, 3 );

		add_filter( 'atum/purchase_orders/maybe_insert_bom_order_item', array( $this, 'maybe_insert_delivery_bom_order_item' ), 10, 7 );

		// Get rid of the manage stock warning icons when for MI-enabled item products.
		add_filter( 'atum/purchase_orders_pro/item_unmanaged_stock_warning', array( $this, 'maybe_show_item_unamanaged_stock_warning' ), 10, 2 );
		add_filter( 'atum/purchase_orders_pro/delivery_item_unmanaged_stock_warning', array( $this, 'maybe_show_item_unamanaged_stock_warning' ), 10, 2 );

		// Update the purchase price for all the inventories at once.
		add_action( 'atum/purchase_orders_pro/after_update_purchase_prices', array( $this, 'change_inventories_purchase_prices' ), 10, 2 );

		// Show the right available stock on PO items for products with MI enabled.
		add_filter( 'atum/purchase_orders_pro/po_item/available_stock', array( $this, 'show_mi_product_available_stock' ), 10, 2 );

		// Add the order item inventories to the returned PO items.
		add_filter( 'atum/purchase_orders_pro/returning_pos/returned_po_items', array( $this, 'add_returned_inventory_items' ), 10, 3 );

		// Add the order item inventories to the delivered PO items.
		add_filter( 'atum/purchase_orders_pro/delivered_po_items', array( $this, 'add_delivered_inventory_items' ), 10, 3 );

		// Restore the inventories when processing a returned PO.
		add_action( 'atum/purchase_orders_pro/po_status_changed/maybe_process_delivery_item', array( $this, 'maybe_return_order_item_inventories' ), 10, 4 );

	}

	/**
	 * Alter the MI views to fit the new PO PRO UI
	 *
	 * @since 0.5.0
	 */
	private function alter_mi_views() {

		// Override the PO's item inventory view.
		add_filter( 'atum/load_view/' . ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/mi-panel', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			return ATUM_PO_PATH . 'views/meta-boxes/po-items/multi-inventory/mi-panel';

		} );

		// Override the PO's item inventory view args.
		add_filter( 'atum/load_view_args/' . ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/mi-panel', array( $this, 'orders_mi_panel_args' ) );
		add_filter( 'atum/load_view_args/' . ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/inventory', array( $this, 'orders_mi_panel_args' ) );

		// Override the "new inventory" template for order item inventories.
		add_filter( 'atum/load_view/' . ATUM_MULTINV_PATH . 'views/meta-boxes/order-items/inventory', function ( $view ) {

			if ( ! Helpers::is_po_post() ) {
				return $view;
			}

			return ATUM_PO_PATH . 'views/meta-boxes/po-items/multi-inventory/inventory';

		} );

	}

	/**
	 * Pass extra args to the Orders' MI panel view
	 *
	 * @since 0.7.5
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function orders_mi_panel_args( $args ) {

		if ( ! Helpers::is_po_post() ) {
			return $args;
		}

		$atum_order                = PurchaseOrders::get_instance()->get_current_atum_order( Helpers::get_po_id(), TRUE );
		$args['currency']          = $atum_order->currency;
		$args['currency_template'] = sprintf( $atum_order->get_price_format(), get_woocommerce_currency_symbol( $atum_order->currency ), '%value%' );
		$args['decimal_sep']       = wc_get_price_decimal_separator();
		$args['step']              = AtumStockDecimals::get_input_step();

		return $args;

	}

	/**
	 * Return the right subtotal for an MI order item when processing a save/delete.
	 *
	 * @since 0.7.6
	 *
	 * @param float $subtotal
	 * @param array $items
	 * @param int   $item_id
	 * @param int   $inventory_id
	 *
	 * @return float
	 */
	public function get_mi_line_subtotal( $subtotal, $items, $item_id, $inventory_id ) {

		// subtotal = total + ( discount * quantity ).
		if ( isset( $items['oi_inventory_total'][ $item_id ][ $inventory_id ], $items['oi_inventory_discount'][ $item_id ][ $inventory_id ], $items['oi_inventory_qty'][ $item_id ][ $inventory_id ] ) ) {
			$subtotal = (float) $items['oi_inventory_total'][ $item_id ][ $inventory_id ] + ( (float) $items['oi_inventory_discount'][ $item_id ][ $inventory_id ] * (float) $items['oi_inventory_qty'][ $item_id ][ $inventory_id ] );
		}

		return $subtotal;

	}

	/**
	 * Add config data to every order item inventory for the discount and taxes fields (if any)
	 *
	 * @since 0.7.7
	 *
	 * @param array $extra_data
	 * @param int   $item_id
	 * @param int   $inventory_id
	 *
	 * @return array
	 */
	public function add_mi_line_extra_data( $extra_data, $item_id, $inventory_id ) {

		// Save the inventory item's discount config (if any).
		if ( ! empty( $_POST['oi_inventory_discount_config'] ) && ! empty( $_POST['oi_inventory_discount_config'][ $item_id ] ) ) {
			$extra_data['discount_config'] = array_map( 'sanitize_text_field', (array) json_decode( stripslashes( $_POST['oi_inventory_discount_config'][ $item_id ] ), TRUE ) );
		}

		return $extra_data;

	}

	/**
	 * Add the inventories to the products searches when adding items to PO.
	 *
	 * @since 0.8.1
	 *
	 * @param array           $item_data
	 * @param \WC_Product     $product
	 * @param POExtended|NULL $po
	 *
	 * @return array
	 */
	public function add_inventories_to_product_search( $item_data, $product, $po ) {

		if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) {

			$inventories                   = MIHelpers::get_product_inventories_sorted( $product->get_id() );
			$inventories_data              = array();
			$item_data['multi_price']      = MIHelpers::has_multi_price( $product );
			$po_supplier                   = $po ? $po->get_supplier( 'id' ) : NULL;
			$supplier_products_restriction = AtumHelpers::get_option( 'po_supplier_products_restriction', 'yes' );

			foreach ( $inventories as $inventory ) {

				if ( ! empty( $_GET['exclude_inventories'] ) && in_array( $inventory->id, $_GET['exclude_inventories'] ) ) {
					continue;
				}

				// Exclude all the inventories that are linked to a supplier distinct to the PO supplier (if the restriction is enabled).
				if ( 'yes' === $supplier_products_restriction && $po_supplier && $inventory->supplier_id && $inventory->supplier_id !== $po_supplier ) {
					continue;
				}

				// Exclude expired inventories.
				if ( $inventory->is_expired() ) {
					continue;
				}

				$inventory_data = $this->get_inventory_data( $inventory, $item_data['multi_price'] );

				$inventories_data[] = apply_filters( 'atum/purchase_orders_pro/multi_inventory_search_data', $inventory_data, $inventory, $product, $po );

			}

			// If all the inventories were already added, return an empty array to prevent the parent product from being returned.
			if ( empty( $inventories_data ) ) {
				return [];
			}

			$item_data['inventories'] = $inventories_data;

		}

		return $item_data;

	}

	/**
	 * Get array with inventory data.
	 *
	 * @since 1.0.4
	 *
	 * @param Inventory $inventory
	 * @param boolean   $multiprice
	 *
	 * @return array
	 */
	private function get_inventory_data( $inventory, $multiprice ) {

		$supplier_id   = $inventory->supplier_id;
		$supplier_name = '&ndash;';

		if ( $supplier_id ) {
			$supplier      = new Supplier( $supplier_id );
			$supplier_name = $supplier->name;
		}

		$inventory_data = array(
			'id'          => absint( $inventory->id ),
			'product_id'  => absint( $inventory->product_id ),
			'name'        => $inventory->name,
			'supplier'    => $supplier_name,
			'supplier_id' => $supplier_id,
			'stock'       => $inventory->stock_quantity ?: 0,
			'meta'        => array(
				array(
					'label' => __( 'SKU', ATUM_PO_TEXT_DOMAIN ),
					'value' => $inventory->sku,
				),
				array(
					'label' => __( 'Sup. SKU', ATUM_PO_TEXT_DOMAIN ),
					'value' => $inventory->supplier_sku,
				),
				array(
					'label' => __( 'Stock', ATUM_PO_TEXT_DOMAIN ),
					'value' => $inventory->stock_quantity ?: 0,
				),
			),
		);

		if ( $multiprice ) {
			$inventory_data['cost'] = $inventory->purchase_price;
		}

		return $inventory_data;
	}

	/**
	 * Handle product exclusions on the AddItemModal search box
	 *
	 * @since 0.8.2
	 *
	 * @param int[]           $excluded_products
	 * @param POItemProduct[] $order_items
	 *
	 * @return int[]
	 */
	public function handle_product_search_exclusions( $excluded_products, $order_items ) {

		if ( ! empty( $excluded_products ) && ! empty( $order_items ) ) {

			$po_order_type_id = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );

			foreach ( $order_items as $order_item ) {

				$product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();

				$product = $order_item->get_product();

				// If the product has MI enabled, just get rid of it from the exclusions list.
				if ( $product && $product->exists() && MIHelpers::has_multi_inventory( $product_id ) ) {

					$excluded_products = array_diff( $excluded_products, [ $product_id ] );

					// Get the order item inventories for the current item.
					$order_item_inventories = Inventory::get_order_item_inventories( $order_item->get_id(), $po_order_type_id );

					if ( ! empty( $order_item_inventories ) ) {

						foreach ( $order_item_inventories as $order_item_inventory ) {
							$this->inventory_exclusions[] = $order_item_inventory->inventory_id;
						}

					}

				}

			}

		}

		return $excluded_products;

	}

	/**
	 * Handle inventory exclusions on the AddItemModal search box
	 *
	 * @since 0.8.2
	 *
	 * @param string          $data_str
	 * @param int[]           $excluded
	 * @param POItemProduct[] $order_items
	 *
	 * @return string
	 */
	public function handle_product_search_inventory_exclusions( $data_str, $excluded, $order_items ) {

		if ( ! empty( $this->inventory_exclusions ) ) {
			$data_str .= ' data-exclude-inventories="' . implode( ',', $this->inventory_exclusions ) . '"';
		}

		return $data_str;
	}

	/**
	 * Add order item inventories from the AddItemModal component.
	 *
	 * @since 0.8.2
	 *
	 * @param array      $html
	 * @param array      $items_data
	 * @param POExtended $atum_order
	 *
	 * @return array
	 */
	public function add_order_item_inventories( $html, $items_data, $atum_order ) {

		if ( ! empty( $items_data['oi_inventory_qty'] ) ) {

			$order_type_id = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );

			$view_args = array(
				'currency'          => $atum_order->supplier_currency,
				'currency_template' => sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol( $atum_order->supplier_currency ), '%value%' ),
				'decimal_sep'       => wc_get_price_decimal_separator(),
				'step'              => AtumStockDecimals::get_input_step(),
				'field_name_prefix' => 'atum_order_item_',
			);

			foreach ( $items_data['oi_inventory_qty'] as $inventory_id => $qty ) {

				$cost = $items_data['oi_inventory_cost'][ $inventory_id ] ?? 0;
				$data = array(
					'qty'      => $qty,
					'subtotal' => $cost * $qty,
					'total'    => $atum_order->apply_supplier_discount( $cost ) * $qty,
				);

				$inventory  = MIHelpers::get_inventory( $inventory_id );
				$product_id = $inventory->product_id;
				$product    = NULL;
				$item       = NULL;

				// Find the item ID for this inventory and create it if doesn't exist.
				$order_items = $atum_order->get_items();
				$found_item  = FALSE;
				$item_id     = 0;

				foreach ( $order_items as $order_item ) {

					$order_item_product_id = $order_item->get_variation_id() ?: $order_item->get_product_id();

					if ( $order_item_product_id === $product_id ) {
						$item_id  = $order_item->get_id();
						$item_qty = $order_item->get_quantity() + $qty;
						$order_item->set_quantity( $item_qty );
						$order_item->save();
						$inventory->save_order_item_inventory( $item_id, $product_id, $data, $order_type_id );
						$found_item = TRUE;

						do_action( 'atum/purchase_orders_pro/added_order_item_inventory', $atum_order, $order_item, $inventory, $qty );
						break;
					}

				}

				// If the related product was still not added to the PO, add it now.
				if ( ! $found_item ) {

					$product = AtumHelpers::get_atum_product( $product_id );

					if ( MIHelpers::has_multi_price( $product ) ) {
						$item = $atum_order->add_product( $product, $qty, $data );
					}
					else {
						$item = $atum_order->add_product( $product, $qty, [], $items_data['cost'][ $product_id ] ?? $product->get_purchase_price() );
					}

					$item_id = $item->get_id();

					$display_fields = AtumHelpers::get_option( 'po_display_extra_fields', [] );
					$display_fields = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];

					// Load the item template.
					$item_view_args = array_merge( $view_args, [
						'atum_order'     => $atum_order,
						'item'           => $item,
						'item_id'        => $item_id,
						'class'          => 'new_row',
						'supplier'       => $atum_order->get_supplier(),
						'display_fields' => $display_fields,
					] );

					$html[ $item_id ]['products'][] = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/po-items/item', $item_view_args );

					$inventory->save_order_item_inventory( $item_id, $product_id, $data, $order_type_id );

					do_action( 'atum/purchase_orders_pro/added_order_item_inventory', $atum_order, $item, $inventory, $qty );
				}

				$oi_view_args = array_merge( $view_args, array(
					'order'                => $atum_order,
					'order_item_inventory' => $inventory->get_order_item_inventory( $item_id ),
					'item_id'              => $item_id,
					'item'                 => $item ?? new POItemProduct( $item_id ),
					'product'              => $product ?? AtumHelpers::get_atum_product( $product_id ),
					'order_type_id'        => $order_type_id,
				) );

				$html[ $item_id ]['inventories'][] = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/po-items/multi-inventory/inventory', $oi_view_args );

			}

		}

		return $html;

	}

	/**
	 * Clone any order item inventory available on the original PO
	 *
	 * @since 0.8.7
	 *
	 * @param POExtended $cloned_po
	 * @param POExtended $original_po
	 */
	public function maybe_clone_order_item_inventories( $cloned_po, $original_po ) {

		// Remove cache before read PO to getting the added items.
		$cache_key  = AtumCache::get_cache_key( 'get_atum_order_model', [ $cloned_po->get_id(), TRUE, PurchaseOrders::POST_TYPE ] );
		AtumCache::delete_cache( $cache_key );

		// Re-read the returning PO from the db to make sure we have the correct data.
		$cloned_po = AtumHelpers::get_atum_order_model( $cloned_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		$order_items                   = $original_po->get_items();
		$order_type_id                 = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
		$cloned_order_item_inventories = [];

		foreach ( $order_items as $order_item ) {

			$order_item_id          = $order_item->get_id();
			$order_item_inventories = Inventory::get_order_item_inventories( $order_item_id, $order_type_id );

			if ( ! empty( $order_item_inventories ) ) {

				foreach ( $order_item_inventories as $order_item_inventory ) {
					$cloned_order_item_inventories[ $order_item_inventory->product_id ][ $order_item_inventory->inventory_id ] = $order_item_inventory;
				}

			}

		}

		// Clone all the order item inventories to the cloned PO.
		if ( ! empty( $cloned_order_item_inventories ) ) {

			$cloned_order_items = $cloned_po->get_items();

			$returned_po_items  = $cloned_po->is_returning() ? ReturningPOs::get_returned_po_items( $original_po->get_id() ) : [];
			$delivered_po_items = $cloned_po->is_returning() ? Helpers::get_delivered_po_items( $original_po->get_id() ) : [];

			foreach ( $cloned_order_items as $cloned_order_item ) {

				$product_id           = $cloned_order_item->get_variation_id() ?: $cloned_order_item->get_product_id();
				$cloned_order_item_id = $cloned_order_item->get_id();

				if ( isset( $cloned_order_item_inventories[ $product_id ] ) ) {

					foreach ( $cloned_order_item_inventories[ $product_id ] as $inventory_id => $cloned_order_item_inventory ) {

						// TODO: CONTROL WHAT HAPPENS WHEN THE INVENTORY NO LONGER EXISTS.
						$inventory = MIHelpers::get_inventory( $inventory_id, $product_id );

						if ( $cloned_po->is_returning() ) {
							$returned_qty  = floatval( $returned_po_items[ "$inventory->product_id:$inventory_id" ] ) ?? 0;
							$delivered_qty = $delivered_po_items[ "$inventory->product_id:$inventory_id" ] ?? 0;
							$qty           = max( $delivered_qty - $returned_qty, 0 );
							$total         = $subtotal = ( $cloned_order_item_inventory->subtotal / $cloned_order_item_inventory->qty ) * $qty;
						}
						else {
							$qty      = $cloned_order_item_inventory->qty;
							$subtotal = $cloned_order_item_inventory->subtotal;
							$total    = $cloned_order_item_inventory->total;
						}

						$data = array(
							'qty'      => $qty,
							'subtotal' => $subtotal,
							'total'    => $total,
						);
						$inventory->save_order_item_inventory( $cloned_order_item_id, $product_id, $data, $order_type_id );

					}

				}

			}

		}

	}

	/**
	 * Add the MI icon to distinguish the order items that have MI enabled
	 *
	 * @since 0.9.3
	 *
	 * @param int           $item_id
	 * @param POItemProduct $item
	 * @param \WC_Product   $product
	 */
	public function add_mi_icon_to_delivery_items( $item_id, $item, $product ) {

		if ( $item instanceof POItemProduct ) :

			if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) : ?>
				<span class="atmi-multi-inventory color-success tips" data-tip="<?php esc_attr_e( 'This item has Multi-Inventory enabled.', ATUM_PO_TEXT_DOMAIN ) ?>"></span>
			<?php endif;

		endif;

	}

	/**
	 * Add a CSS class to delivery items with MI (within modals).
	 *
	 * @since 0.9.3
	 *
	 * @param string        $classes
	 * @param POItemProduct $po_item
	 * @param \WC_Product   $product
	 *
	 * @return string
	 */
	public function delivery_item_css_class( $classes, $po_item, $product ) {

		if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) {
			$classes .= ' with-mi';
		}

		return $classes;

	}

	/**
	 * Add inventories to delivery modals' items
	 *
	 * @since 0.9.3
	 *
	 * @param POItemProduct $po_item
	 * @param \WC_Product   $product
	 * @param Delivery      $delivery Optional. Only needed when editing a delivery.
	 */
	public function add_inventories_to_delivery_items( $po_item, $product, $delivery = NULL ) {

		if ( $po_item instanceof POItemProduct && 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) {

			$po_inventory_order_items = Inventory::get_order_item_inventories( $po_item->get_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
			$delivery_inventory_items = self::get_po_delivery_inventory_items( $po_item->get_atum_order_id() );

			// Add Delivery modal.
			if ( doing_action( 'atum/purchase_orders_pro/after_add_delivery_modal_item' ) ) {
				AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/deliveries/multi-inventory/add-delivery-modal-inventory-items', compact( 'po_item', 'product', 'po_inventory_order_items', 'delivery_inventory_items' ) );
			}
			// Edit Delivery modal.
			elseif ( doing_action( 'atum/purchase_orders_pro/after_edit_delivery_modal_item' ) ) {
				$delivery_inventory_item_objs = $delivery->get_items( 'delivery_item_inventory' );
				AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/deliveries/multi-inventory/edit-delivery-modal-inventory-items', compact( 'po_item', 'product', 'po_inventory_order_items', 'delivery_inventory_items', 'delivery_inventory_item_objs', 'delivery' ) );
			}
			// Delivery items.
			else {

				$po                           = PurchaseOrders::get_instance()->get_current_atum_order( $po_item->get_atum_order_id(), TRUE );
				$delivery_inventory_item_objs = $delivery->get_items( 'delivery_item_inventory' );
				AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/deliveries/multi-inventory/delivery-inventory-items', compact( 'po', 'po_item', 'product', 'delivery_inventory_items', 'delivery_inventory_item_objs', 'delivery', 'po_inventory_order_items' ) );
			}

		}

	}

	/**
	 * Get the delivery item inventory's class name.
	 *
	 * @since 0.9.4
	 *
	 * @param string $class_name
	 * @param int    $item_id
	 * @param string $item_type
	 *
	 * @return string
	 */
	public function get_delivery_item_inventory_class( $class_name, $item_id, $item_type ) {

		if ( 'delivery_item_inventory' === $item_type ) {
			$class_name = '\\AtumPO\\Deliveries\\Items\\DeliveryItemProductInventory';
		}

		return $class_name;
	}

	/**
	 * Get the key used for the delivery item inventories
	 *
	 * @since 0.9.4
	 *
	 * @param string                                           $key
	 * @param DeliveryItemProduct|DeliveryItemProductInventory $item
	 *
	 * @return string
	 */
	public function get_delivery_item_inventory_key( $key, $item ) {

		if ( $item instanceof DeliveryItemProductInventory ) {
			$key = 'delivery_item_inventories';
		}

		return $key;

	}

	/**
	 * Get the delivery item inventories' class name.
	 *
	 * @since 0.9.4
	 *
	 * @param string $class_name
	 * @param string $items_key
	 *
	 * @return string
	 */
	public function get_delivery_item_inventories_class( $class_name, $items_key ) {

		if ( 'delivery_item_inventories' === $items_key ) {
			$class_name = '\\AtumPO\\Deliveries\\Items\\DeliveryItemProductInventory';
		}

		return $class_name;
	}

	/**
	 * Add the delivery item inventory group
	 *
	 * @since 0.9.4
	 *
	 * @param array $delivery_groups
	 *
	 * @return array
	 */
	public function add_delivery_item_inventory_group( $delivery_groups ) {

		$delivery_groups['delivery_item_inventory'] = 'delivery_item_inventories';

		return $delivery_groups;

	}

	/**
	 * Add the order item inventories group to the delivery
	 *
	 * @since 0.9.4
	 *
	 * @param array $groups
	 *
	 * @return array
	 */
	public function add_delivery_item_inventory_group_to_type( $groups ) {

		$groups['delivery_item_inventories'] = 'delivery_item_inventory';

		return $groups;
	}

	/**
	 * Get the delivery inventory items that were already registered on the specified PO
	 *
	 * @since 0.9.4
	 *
	 * @param int $po_id
	 *
	 * @return array
	 */
	public static function get_po_delivery_inventory_items( $po_id ) {

		global $wpdb;

		$cache_key                = AtumCache::get_cache_key( 'get_po_delivery_inventory_items', $po_id );
		$delivery_inventory_items = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $delivery_inventory_items;
		}

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "
			SELECT aoi.order_item_id, order_item_name, aoim.meta_value AS po_item_id, aoim3.meta_value AS inventory_id, aoim2.meta_value AS qty, aoi.order_id 
			FROM $atum_order_items_table aoi
			LEFT JOIN $atum_order_items_meta_table aoim ON ( aoi.order_item_id = aoim.order_item_id AND aoim.meta_key = '_po_item_id' )
			LEFT JOIN $atum_order_items_meta_table aoim2 ON ( aoi.order_item_id = aoim2.order_item_id AND aoim2.meta_key = '_qty' )
			LEFT JOIN $atum_order_items_meta_table aoim3 ON ( aoi.order_item_id = aoim3.order_item_id AND aoim3.meta_key = '_inventory_id' )
			WHERE aoi.order_item_type = 'delivery_item_inventory' AND aoim.meta_value IN (
			    SELECT order_item_id FROM $atum_order_items_table WHERE order_item_type = 'line_item' AND order_id = %d
			)
		", $po_id );
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql );

		AtumCache::set_cache( $cache_key, $results, ATUM_PO_TEXT_DOMAIN );

		return $results;

	}

	/**
	 * Get all inventories qtys included in a PO
	 *
	 * @since 1.0.3
	 *
	 * @param int $po_id
	 *
	 * @return array|null
	 */
	public static function get_po_delivery_inventory_items_qty_sum( $po_id ) {

		global $wpdb;

		$cache_key                        = AtumCache::get_cache_key( 'get_po_delivery_inventory_items_qty_sum', $po_id );
		$delivery_inventory_items_qty_sum = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $delivery_inventory_items_qty_sum;
		}

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "
			SELECT DISTINCT aoim3.meta_value AS inventory_id, SUM( aoim2.meta_value) AS qty, aoi.order_id 
			FROM $atum_order_items_table aoi
			LEFT JOIN $atum_order_items_meta_table aoim ON ( aoi.order_item_id = aoim.order_item_id AND aoim.meta_key = '_po_item_id' )
			LEFT JOIN $atum_order_items_meta_table aoim2 ON ( aoi.order_item_id = aoim2.order_item_id AND aoim2.meta_key = '_qty' )
			LEFT JOIN $atum_order_items_meta_table aoim3 ON ( aoi.order_item_id = aoim3.order_item_id AND aoim3.meta_key = '_inventory_id' )
			WHERE aoi.order_item_type = 'delivery_item_inventory' AND aoim.meta_value IN (
			    SELECT order_item_id FROM $atum_order_items_table WHERE order_item_type = 'line_item' AND order_id = %d
			)
			GROUP BY aoim3.meta_value;
		", $po_id );
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql );

		if ( $results ) {
			$response = [];

			foreach ( $results as $result ) {
				$response[ $result->inventory_id ] = $result->qty;
			}

			$results = $response;
		}

		AtumCache::set_cache( $cache_key, $results, ATUM_PO_TEXT_DOMAIN );

		return $results;

	}

	/**
	 * Get the delivery item inventories related to any PO item + already in total units accross deliveries.
	 *
	 * @since 0.9.23
	 *
	 * @param int  $po_item_id          The PO item's ID that we want to retrieve the count.
	 * @param int  $inventory_id        The specific inventory ID to check.
	 * @param bool $check_stock_changed Whether to count only items that were already added to stock.
	 *
	 * @return array
	 */
	public static function get_po_item_mi_already_in_total( $po_item_id, $inventory_id, $check_stock_changed = TRUE ) {

		$cache_key  = AtumCache::get_cache_key( 'get_po_item_mi_already_in_total', [ $po_item_id, $inventory_id, $check_stock_changed ] );
		$already_in = AtumCache::get_cache( $cache_key, ATUM_PO_TEXT_DOMAIN, FALSE, $has_cache );

		if ( $has_cache ) {
			return $already_in;
		}

		global $wpdb;

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		$joins = array(
			"LEFT JOIN $atum_order_items_meta_table itm2 ON (itm1.order_item_id = itm2.order_item_id AND itm2.meta_key = '_po_item_id')",
			"LEFT JOIN $atum_order_items_meta_table itm3 ON (itm1.order_item_id = itm3.order_item_id AND itm3.meta_key = '_inventory_id')",
			"LEFT JOIN $atum_order_items_table it ON (it.order_item_id = itm1.order_item_id)",
		);

		$where = array(
			"itm1.meta_key = '_qty'",
			$wpdb->prepare( 'itm2.meta_value = %d', $po_item_id ),
			$wpdb->prepare( 'itm3.meta_value = %d', $inventory_id ),
			"it.order_item_type = 'delivery_item_inventory'",
		);

		if ( $check_stock_changed ) {
			$joins[] = "LEFT JOIN $atum_order_items_meta_table itm4 ON (itm1.order_item_id = itm4.order_item_id AND itm4.meta_key = '_stock_changed')";
			$where[] = "itm4.meta_value = 'yes'";
		}

		// phpcs:disable WordPress.DB.PreparedSQL
		$already_in = $wpdb->get_var( "
			SELECT SUM(itm1.meta_value)
			FROM $atum_order_items_meta_table itm1 \n" .
			implode( "\n", $joins ) . "\n" .
			'WHERE ' . implode( ' AND ', $where )
		);
		// phpcs:enable

		AtumCache::set_cache( $cache_key, $already_in ?? 0, ATUM_PO_TEXT_DOMAIN );

		return $already_in ?? 0;

	}

	/**
	 * Add a new item inventory to a delivery
	 *
	 * @since 0.9.4
	 *
	 * @param array      $item      The posted item data.
	 * @param POExtended $po        The PO being updated.
	 * @param Delivery   $delivery  The delivery being added.
	 */
	public function maybe_add_delivery_item_inventory( $item, $po, $delivery ) {

		if ( 'inventory' === $item['type'] ) {

			$delivered = (float) $item['delivered'];

			// Do not add items that have not been marked as delivered.
			if ( $delivered <= 0 ) {
				return;
			}

			$inventory = MIHelpers::get_inventory( absint( $item['inventoryId'] ) );

			if ( ! $inventory->exists() ) {
				return;
			}

			// Add the inventory to the Delivery.
			$this->add_delivery_item_inventory( $delivery, $inventory, $delivered, [ 'po_item_id' => absint( $item['id'] ) ] );

		}

	}

	/**
	 * Update a delivery item inventory
	 *
	 * @since 0.9.4
	 *
	 * @param array      $item      The posted item data.
	 * @param POExtended $po        The PO being updated.
	 * @param Delivery   $delivery  The delivery being added.
	 */
	public function maybe_edit_delivery_item_inventory( $item, $po, $delivery ) {

		if ( 'inventory' === $item['type'] ) {

			$delivered = (float) $item['delivered'];

			$inventory_id = absint( $item['inventoryId'] );
			$inventory    = MIHelpers::get_inventory( $inventory_id );

			if ( ! $inventory->exists() ) {
				return;
			}

			$delivery_item_inventories = $delivery->get_items( 'delivery_item_inventory' );
			$current_delivery_item     = NULL;

			foreach ( $delivery_item_inventories as $delivery_item_inventory ) {
				/**
				 * Variable definition
				 *
				 * @var DeliveryItemProductInventory $delivery_item_inventory
				 */
				if ( $delivery_item_inventory->get_inventory_id() === $inventory_id ) {
					$current_delivery_item = $delivery_item_inventory;
					break;
				}
			}

			// Found: edit the existing item inventory.
			if ( $current_delivery_item ) {

				// Remove the item inventory from the delivery.
				if ( $delivered <= 0 ) {

					// Restore the stock (if needed).
					if ( 'yes' === $current_delivery_item->get_stock_changed() ) {

						$old_stock = $inventory->stock_quantity;
						$new_stock = MIHelpers::update_inventory_stock( $inventory->product_id, $inventory, $current_delivery_item->get_quantity(), 'decrease' );

						$product = wc_get_product( $inventory->product_id );

						/* translators: 1.the formatted product name, 2. Old stock, 3. New Stock, 4. Used inventory */
						$po->add_order_note( sprintf( __( ' Stock levels changed: [%1$s], %2$s &rarr; %3$s using inventory &quot;%4$s&quot;', ATUM_PO_TEXT_DOMAIN ), $product->get_formatted_name(), $old_stock, $new_stock, $inventory->name ) );

					}

					$delivery->remove_item( $current_delivery_item->get_id() );
					$delivery->save_items();

				}
				else {

					$old_quantity = $current_delivery_item->get_quantity();
					$current_delivery_item->set_quantity( $delivered );
					$current_delivery_item->save();

					// Change the product's stock automatically if it was already changed.
					if ( 'yes' === $current_delivery_item->get_stock_changed() ) {

						$new_quantity = $current_delivery_item->get_quantity();

						if ( $new_quantity !== $old_quantity ) {

							if ( $old_quantity > $new_quantity ) {
								$change_quantity = $old_quantity - $new_quantity;
								$action          = 'decrease';
							}
							else {
								$change_quantity = $new_quantity - $old_quantity;
								$action          = 'increase';
							}

							$old_stock = $inventory->stock_quantity;
							$new_stock = MIHelpers::update_inventory_stock( $inventory->product_id, $inventory, $change_quantity, $action );

							$product = wc_get_product( $inventory->product_id );

							/* translators: 1.the formatted product name, 2. Old stock, 3. New Stock, 4. Used inventory */
							$po->add_order_note( sprintf( __( ' Stock levels changed: [%1$s], %2$s &rarr; %3$s using inventory &quot;%4$s&quot;', ATUM_PO_TEXT_DOMAIN ), $product->get_formatted_name(), $old_stock, $new_stock, $inventory->name ) );


						}
					}

				}

			}
			// Not found: add a new item inventory.
			else {

				// Do not add items that has not been marked as delivered.
				if ( $delivered <= 0 ) {
					return;
				}

				$this->add_delivery_item_inventory( $delivery, $inventory, $delivered, [ 'po_item_id' => absint( $item['id'] ) ] );

			}

		}

	}

	/**
	 * Check whether tho hide any product with MI enabled that has no inventories asigned yet
	 *
	 * @since 0.9.23
	 *
	 * @param bool          $show
	 * @param POItemProduct $po_item
	 *
	 * @return bool
	 */
	public function maybe_show_mi_delivery_item( $show, $po_item ) {

		$product = $po_item->get_product();

		if ( $product && MIHelpers::has_multi_inventory( $product ) ) {
			$order_item_inventories = Inventory::get_order_item_inventories( $po_item->get_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
			$show                   = ! empty( $order_item_inventories );
		}

		return $show;

	}

	/**
	 * Add an inventory line item to the Delivery
	 *
	 * @since 0.9.4
	 *
	 * @param Delivery  $delivery
	 * @param Inventory $inventory
	 * @param int|float $qty
	 * @param array     $args
	 *
	 * @return DeliveryItemProductInventory The inventory item added to the delivery
	 */
	private function add_delivery_item_inventory( $delivery, $inventory, $qty = NULL, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'name'         => $inventory->name,
			'inventory_id' => $inventory->id,
			'quantity'     => $qty,
		) );

		$item = new DeliveryItemProductInventory();
		$item->set_props( $args );
		$item->set_atum_order_id( $delivery->get_id() );
		$item->save();
		$delivery->add_item( $item );

		return $item;

	}

	/**
	 * Adjust the MI product's quantity after removing a delivery item inventory.
	 *
	 * @since 0.9.5
	 *
	 * @param Delivery $delivery
	 * @param int      $delivery_item_id
	 *
	 * @return Delivery
	 */
	public function before_remove_delivery_item( $delivery, $delivery_item_id ) {

		$delivery_item_inventory = $delivery->get_item( $delivery_item_id, 'delivery_item_inventory' );

		if ( $delivery_item_inventory instanceof DeliveryItemProductInventory ) {

			$removed_qty   = $delivery_item_inventory->get_quantity();
			$delivery_item = $delivery_item_inventory->get_associated_delivery_item();

			if ( $delivery_item ) {
				$item_qty = $delivery_item->get_quantity();
				$delivery_item->set_quantity( $item_qty - $removed_qty );
				$delivery_item->save();
				$delivery->add_item( $delivery_item );
			}

		}
		// Check if a delivery item with MI enabled is being removed.
		else {

			$delivery_item = $delivery->get_item( $delivery_item_id );

			if ( $delivery_item instanceof DeliveryItemProduct ) {

				$product_id = $delivery_item->get_variation_id() ?: $delivery_item->get_product_id();

				if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product_id ) ) {

					// Find all the delivery item inventories associated to the removed delivery item and remove them.
					$delivery_item_inventories = $this->find_associated_delivery_inventory_items( $delivery->get_id(), $delivery_item->get_po_item_id() );

					if ( ! empty( $delivery_item_inventories ) ) {

						// TODO: RESTOCK THE ITEMS THAT WERE ALREADY ADDED BEFORE REMOVING THEM.

						foreach ( $delivery_item_inventories as $delivery_item_inventory_id ) {

							$delivery->remove_item( $delivery_item_inventory_id );

						}

						$delivery->save();

					}

				}

			}

		}

		return $delivery;

	}

	/**
	 * Restore all the inventories after deleting a delivery (when appropriate)
	 *
	 * @since 0.9.5
	 *
	 * @param Delivery                                         $delivery      The Delivery object that contains the inventory items to restore.
	 * @param DeliveryItemProduct|DeliveryItemProductInventory $delivery_item It will restore onlt the inventory id it's a DeliveryItemProductInventory or all the inventories if it's a DeliveryItemProduct.
	 *
	 * @throws \Exception
	 */
	public function restore_delivery_item_inventories( $delivery, $delivery_item ) {

		$po_item_id = $delivery_item->get_po_item_id();

		/**
		 * Variable definition
		 *
		 * @var DeliveryItemProductInventory[] $delivery_item_inventories
		 */
		if ( $delivery_item instanceof DeliveryItemProductInventory ) {
			$delivery_item_inventories = [ $delivery_item ];
		}
		else {
			$delivery_item_inventories = $delivery->get_items( 'delivery_item_inventory' );
		}

		foreach ( $delivery_item_inventories as $delivery_item_inventory ) {

			if ( $po_item_id !== $delivery_item_inventory->get_po_item_id() ) {
				continue;
			}

			if ( 'yes' === $delivery_item_inventory->get_stock_changed() ) {

				$inventory = MIHelpers::get_inventory( $delivery_item_inventory->get_inventory_id() );
				$old_stock = $inventory->stock_quantity;
				$new_stock = MIHelpers::update_inventory_stock( $inventory->product_id, $inventory, $delivery_item_inventory->get_quantity(), 'decrease' );

				$po      = $delivery->get_po_object();
				$product = wc_get_product( $inventory->product_id );

				/* translators: 1.the formatted product name, 2. Old stock, 3. New Stock, 4. Used inventory */
				$po->add_order_note( sprintf( __( ' Stock levels changed: [%1$s], %2$s &rarr; %3$s using inventory &quot;%4$s&quot;', ATUM_PO_TEXT_DOMAIN ), $product->get_formatted_name(), $old_stock, $new_stock, $inventory->name ) );

				$delivery_item_inventory->set_stock_changed( FALSE );
				$delivery_item_inventory->save();

				do_action( 'atum/purchase_orders_pro/after_restore_delivery_item_inventory_to_stock', $inventory, $delivery_item_inventory, $delivery );

			}

		}

	}

	/**
	 * Find all the delivery inventory items associated to a specific PO item within a delivery
	 *
	 * @since 0.9.5
	 *
	 * @param int $delivery_id
	 * @param int $po_item_id
	 *
	 * @return array
	 */
	private function find_associated_delivery_inventory_items( $delivery_id, $po_item_id ) {

		global $wpdb;

		$atum_order_items_table      = $wpdb->prefix . AtumOrderPostType::ORDER_ITEMS_TABLE;
		$atum_order_items_meta_table = $wpdb->prefix . AtumOrderPostType::ORDER_ITEM_META_TABLE;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "
			SELECT aoi.order_item_id FROM $atum_order_items_table aoi
			LEFT JOIN $atum_order_items_meta_table aoim ON( aoi.order_item_id = aoim.order_item_id AND meta_key = '_po_item_id' )
			WHERE order_id = %d AND order_item_type = 'delivery_item_inventory' AND meta_value = %d
		", $delivery_id, $po_item_id );
		// phpcs:enable

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_col( $sql );

	}

	/**
	 * Add the specified delivery item inventory to stock
	 *
	 * @since 0.9.5
	 *
	 * @param array    $item_data
	 * @param Delivery $delivery
	 */
	public function add_delivery_item_inventory_to_stock( $item_data, $delivery ) {

		// Only increase stocks for inventory items.
		if ( 'inventory' === $item_data['type'] ) {

			// TODO: ADD PRODUCT LEVELS COMPATIBILITY (FOR CALCULATED STOCKS).
			$delivery_item_inventory = $delivery->get_item( $item_data['id'], 'delivery_item_inventory' );
			$inventory               = MIHelpers::get_inventory( $item_data['inventoryId'] );

			if ( 'yes' !== $delivery_item_inventory->get_stock_changed() ) {

				$old_stock = $inventory->stock_quantity;
				$new_stock = MIHelpers::update_inventory_stock( $inventory->product_id, $inventory, $delivery_item_inventory->get_quantity(), 'increase' );
				$delivery_item_inventory->set_stock_changed( TRUE );
				$delivery_item_inventory->save();

				$po      = $delivery->get_po_object();
				$product = wc_get_product( $inventory->product_id );

				/* translators: 1.the formatted product name, 2. Old stock, 3. New Stock, 4. Used inventory */
				$po->add_order_note( sprintf( __( ' Stock levels changed: [%1$s], %2$s &rarr; %3$s using inventory &quot;%4$s&quot;', ATUM_PO_TEXT_DOMAIN ), $product->get_formatted_name(), $old_stock, $new_stock, $inventory->name ) );

				do_action( 'atum/purchase_orders_pro/after_add_delivery_item_inventory_to_stock', $item_data, $inventory, $delivery_item_inventory, $delivery );

			}

		}

	}

	/**
	 * Prevent products with MI enabled from being discounted directly
	 *
	 * @since 0.9.5
	 *
	 * @param bool        $allow
	 * @param \WC_Product $product
	 *
	 * @return bool
	 */
	public function skip_mi_products_from_add_to_stock( $allow, $product ) {
		return 'yes' !== MIHelpers::get_product_multi_inventory_status( $product );
	}

	/**
	 * Register MI items as supplier items when creating a new PO from AddToPO modal and the "multiple POs" option is enabled.
	 *
	 * @since 0.9.10
	 *
	 * @param array  $supplier_items
	 * @param string $id
	 *
	 * @return array
	 */
	public function add_to_po_mi_supplier_items( $supplier_items, $id ) {

		if ( ! is_numeric( $id ) && str_contains( $id, ':' ) ) {

			list( $product_id, $inventory_id ) = array_map( 'absint', explode( ':', $id ) );

			$inventory = MIHelpers::get_inventory( $inventory_id, $product_id );

			if ( $inventory->exists() ) {
				$supplier_items[ absint( $inventory->supplier_id ) ][] = $id;
			}

		}

		return $supplier_items;

	}

	/**
	 * Add MI items in bulk to a new PO from List Tables
	 *
	 * @since 0.9.8
	 *
	 * @param POExtended $po
	 * @param array      $items
	 * @param array      $item_qtys
	 *
	 * @return POExtended
	 */
	public function bulk_add_mi_to_po( $po, $items, $item_qtys ) {

		$po_items = $po->get_items();
		$qtys     = array();

		foreach ( $items as $id ) {

			if ( ! is_numeric( $id ) && str_contains( $id, ':' ) ) {

				list( $product_id, $inventory_id ) = array_map( 'absint', explode( ':', $id ) );

				$item      = NULL;
				$inventory = MIHelpers::get_inventory( $inventory_id, $product_id );
				$product   = AtumHelpers::get_atum_product( $product_id );

				if ( ! $inventory->exists() || ! $product instanceof \WC_Product ) {
					continue;
				}

				/**
				 * Variable definition
				 *
				 * @var POItemProduct $po_item
				 */
				foreach ( $po_items as $po_item ) {

					$item_product_id = $po_item->get_variation_id() ?: $po_item->get_product_id();

					if ( $item_product_id === $product_id ) {
						$item = $po_item;
						break;
					}

				}

				// Perhaps the parent product was not selected by the user?
				if ( ! $item ) {
					$item       = $po->add_product( $product, 1 );
					$po_items[] = $item;
					$po->save();
				}

				$has_multi_price = MIHelpers::has_multi_price( $product );
				$qty             = ! empty( $item_qtys[ $id ] ) ? $item_qtys[ $id ] : 1;

				// Add the inventory to the PO.
				$data = array(
					'qty'      => $qty,
					'subtotal' => ( (float) ( $has_multi_price ? $inventory->purchase_price : $product->get_purchase_price() ) ) * $qty,
					'total'    => $po->apply_supplier_discount( (float) ( $has_multi_price ? $inventory->purchase_price : $product->get_purchase_price() ) ) * $qty,
				);

				if ( array_key_exists( $product_id, $qtys ) ) {
					$qtys[ $product_id ]['qty']      += $data['qty'];
					$qtys[ $product_id ]['subtotal'] += $data['subtotal'];
					$qtys[ $product_id ]['total']    += $data['total'];
				}
				else {
					$qtys[ $product_id ]['qty']      = $data['qty'];
					$qtys[ $product_id ]['subtotal'] = $data['subtotal'];
					$qtys[ $product_id ]['total']    = $data['total'];
				}

				$inventory->save_order_item_inventory( $item->get_id(), $product_id, $data, AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

			}

		}

		// Adjust the related product props from the MI items' data.
		if ( ! empty( $qtys ) ) {

			foreach ( $po_items as $po_item ) {

				$item_product_id = $po_item->get_variation_id() ?: $po_item->get_product_id();

				if ( array_key_exists( $item_product_id, $qtys ) ) {

					$props = array(
						'quantity' => $qtys[ $item_product_id ]['qty'],
						'subtotal' => $qtys[ $item_product_id ]['subtotal'],
						'total'    => $qtys[ $item_product_id ]['total'],
					);

					// Handle taxes.
					if ( Helpers::may_use_po_taxes() ) {

						$qty = $qty ?? 1;

						/**
						 * Variable definition
						 *
						 * @var float $subtotal
						 * @var float $total
						 * @var float $subtotal_tax
						 * @var float $total_tax
						 * @var array $total_taxes
						 * @var array $tax_rates
						 */
						extract( $po->add_default_taxes( $qtys[ $item_product_id ]['subtotal'], $qtys[ $item_product_id ]['total'] ) );

						if ( ! empty( $total_taxes ) ) {
							$props['subtotal']  = $subtotal;
							$props['total']     = $total;
							$props['total_tax'] = wc_round_tax_total( $total_tax );
							$props['taxes']     = array(
								'subtotal' => [ $subtotal_tax ],
								'total'    => [ $total_tax ],
							);
						}

					}

					$po_item->set_props( $props );

				}

			}

			$po->calculate_totals();

		}

		return $po;

	}

	/**
	 * Add MI items in bulk to a new PO from List Tables
	 *
	 * @since 0.9.10
	 *
	 * @param \WC_Product[] $items
	 * @param string        $item_id
	 *
	 * @return \WC_Product[]|Inventory[]
	 */
	public function get_add_to_po_mi_items( $items, $item_id ) {

		if ( ! str_contains( $item_id, ':' ) ) {
			return $items;
		}

		list( $product_id, $inventory_id ) = array_map( 'absint', explode( ':', $item_id ) );

		$inventory = MIHelpers::get_inventory( $inventory_id );

		if ( ! $inventory->exists() ) {
			return $items;
		}

		$index = NULL;

		foreach ( $items as $i => $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Product|Inventory $item
			 */
			if ( $item instanceof \WC_Product ) {

				// We already found the parent product and there are no sibling inventories.
				if ( ! is_null( $index ) ) {
					break;
				}

				if ( $inventory->product_id === $item->get_id() ) {
					$index = $i;
				}

			}
			elseif ( $item instanceof Inventory ) {

				// We've reached a new inventory that doesn't belong to the same product.
				if ( ! is_null( $index ) ) {
					break;
				}

				// We understand that inventories are always under its parent product, so their indexes will be always higher.
				if ( $item->product_id === $inventory->product_id ) {
					$index = $i;
				}

			}

		}

		// Add the inventory to the array with the right index.
		array_splice( $items, $index + 1, 0, [ $inventory ] );

		return $items;

	}

	/**
	 * Print MI items to the AddtoPO modals
	 *
	 * @since 0.9.10
	 *
	 * @param \WC_Product|Inventory $item
	 * @param int[]                 $suppliers
	 */
	public function print_add_to_po_mi_items( $item, &$suppliers ) {

		if ( $item instanceof Inventory ) {
			$inventory = $item;
			AtumHelpers::load_view( ATUM_PO_PATH . 'views/add-to-po/multi-inventory/add-to-po-mi-item', compact( 'inventory', 'suppliers' ) );
		}

	}

	/**
	 * Add MI classes to the AddToPO result items
	 *
	 * @since 0.9.10
	 *
	 * @param string $classes
	 * @param int    $product_id
	 */
	public function add_to_po_items_classes( $classes, $product_id ) {

		if ( MIHelpers::has_multi_inventory( $product_id ) ) {
			$classes .= ' with-mi';

			if ( MIHelpers::has_multi_price( $product_id ) ) {
				$classes .= ' multi-price';
			}
		}

		return $classes;

	}

	/**
	 * Change the MI icon's HTML in PO items
	 *
	 * @since 0.9.9
	 *
	 * @return string
	 */
	public function po_item_mi_icon() {

		ob_start();
		?>
		<span class="atmi-multi-inventory atum-tooltip" title="<?php esc_attr_e( 'This item has Multi-Inventory enabled', ATUM_PO_TEXT_DOMAIN ) ?>"></span>
		<?php

		return ob_get_clean();

	}

	/**
	 * Add the MI items when creating POs from orders from the WC Orders bulk actions.
	 *
	 * @since 0.9.10
	 *
	 * @param \WC_Order $order
	 * @param int[]     $item_ids
	 * @param int[]     $product_ids
	 * @param int[]     $item_qtys
	 */
	public function add_mi_items_to_create_pos_from_orders( $order, $item_ids, &$product_ids, &$item_qtys ) {

		foreach ( $item_ids as $item_id ) {

			$order_item_inventories = Inventory::get_order_item_inventories( $item_id, AtumGlobals::get_order_type_id( $order->get_type() ) );

			if ( ! empty( $order_item_inventories ) ) {
				foreach ( $order_item_inventories as $order_item_inventory ) {
					$id               = "{$order_item_inventory->product_id}:{$order_item_inventory->inventory_id}";
					$product_ids[]    = $id;
					$item_qtys[ $id ] = wc_stock_amount( $order_item_inventory->qty );
				}
			}

		}

	}

	/**
	 * Allow the MI's get_stock_quantity method to work within POs
	 *
	 * @since 0.9.10
	 *
	 * @param bool $bypass
	 *
	 * @return bool
	 */
	public function maybe_bypass_mi_get_stock_quantity( $bypass ) {

		if ( ! function_exists( 'get_current_screen' ) ) {
			return $bypass;
		}

		$screen = get_current_screen();

		if (
			( $screen && PurchaseOrders::POST_TYPE === $screen->id && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) ||
			( AtumHelpers::is_atum_ajax() && ( strpos( $_REQUEST['action'], 'atum_order_' ) === 0 || strpos( $_REQUEST['action'], 'atum_po_' ) === 0 ) )
		) {
			$bypass = FALSE;
		}

		return $bypass;

	}

	/**
	 * Change the MI settings
	 *
	 * @since 0.9.11
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function change_mi_settings_defaults( $settings ) {
		unset( $settings['mi_po_auto_assigned_inventories'] ); // Remove the auto-assign inventories to PO items option.
		return $settings;
	}

	/**
	 * Add the MI JS templates to the POs
	 *
	 * @since 0.9.11
	 */
	public function add_mi_js_templates() {
		AtumHelpers::load_view( ATUM_MULTINV_PATH . 'views/js-templates/add-inventory-modal' );
	}

	/**
	 * Add the MI batch tracking extra filter to the POs list table
	 *
	 * @since 0.9.12
	 *
	 * @param array $extra_filters
	 *
	 * @return array
	 */
	public function add_batch_tracking_filter( $extra_filters ) {

		$extra_filters['mi_batch_tracking'] = __( 'Batch Tracking', ATUM_PO_TEXT_DOMAIN );

		return $extra_filters;

	}

	/**
	 * Do not enable auto-filtering on the Batch Tracking extra filter
	 *
	 * @since 0.9.14
	 *
	 * @param array $no_auto_filters
	 *
	 * @return array
	 */
	public function batch_tracking_no_auto_filter( $no_auto_filters ) {

		$no_auto_filters[] = 'mi_batch_tracking';

		return $no_auto_filters;

	}

	/**
	 * Add the batch tracking auto-filter to the POs list table
	 *
	 * @since 1.0.7
	 */
	public function add_auto_filters() {
		$barcode = ! empty( $_REQUEST['batch'] ) ? esc_attr( $_REQUEST['batch'] ) : '';
		echo '<input type="hidden" class="auto-filter" name="batch" value="' . $barcode . '">';
	}

	/**
	 * Handle the MI Batch tracking extra filter.
	 *
	 * @since 0.9.14
	 *
	 * @param array  $filtered_pos
	 * @param string $extra_filter
	 *
	 * @return array
	 */
	public function pos_list_batch_tracking( $filtered_pos, $extra_filter ) {

		if ( 'mi_batch_tracking' === $extra_filter && ! empty( $_REQUEST['batch'] ) ) {

			$batch_number = wc_clean( $_REQUEST['batch'] );

			if ( $batch_number ) {

				global $wpdb;

				$inventories_table      = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
				$inventory_orders_table = $wpdb->prefix . Inventory::INVENTORY_ORDERS_TABLE;
				$order_items_table      = AtumOrderPostType::ORDER_ITEMS_TABLE;
				$order_type_id          = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );

				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$filtered_pos = $wpdb->get_col( $wpdb->prepare( "
					SELECT aoi.order_id FROM {$wpdb->prefix}{$order_items_table} aoi 
					LEFT JOIN $inventory_orders_table aio ON (aoi.order_item_id = aio.order_item_id AND aio.order_type = %d)
					LEFT JOIN $inventories_table ai ON (aio.inventory_id = ai.id)
					WHERE ai.lot = %s
				", $order_type_id, $batch_number ) );
				// phpcs:enable

			}

		}

		return $filtered_pos;

	}

	/**
	 * Merge PO item inventories
	 *
	 * @since 0.9.11
	 *
	 * @param AtumOrderItemProduct $item
	 * @param AtumOrderItemProduct $merged_item
	 */
	public function merge_order_items( $item, $merged_item ) {

		$order         = $item->get_order();
		$order_type_id = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );

		if ( $order instanceof PurchaseOrder ) {

			$item_inventories   = Inventory::get_order_item_inventories( $item->get_id(), $order_type_id );
			$merged_inventories = Inventory::get_order_item_inventories( $merged_item->get_id(), $order_type_id );

			if ( ! empty( $merged_inventories ) ) {

				foreach ( $merged_inventories as $merged_inventory_item ) {

					$merged_inventory = MIHelpers::get_inventory( $merged_inventory_item->inventory_id );
					$found_inventory  = FALSE;

					foreach ( $item_inventories as $source_inventory_item ) {

						if ( $merged_inventory_item->inventory_id === $source_inventory_item->inventory_id ) {

							$data = [
								'qty'        => floatval( $source_inventory_item->qty ) + floatval( $merged_inventory_item->qty ),
								'subtotal'   => floatval( $source_inventory_item->subtotal ) + floatval( $merged_inventory_item->subtotal ),
								'total'      => floatval( $source_inventory_item->total ) + floatval( $merged_inventory_item->total ),
								'extra_data' => maybe_serialize( $merged_inventory->prepare_order_item_inventory_extra_data( $item->get_id() ) ),
							];
							$merged_inventory->save_order_item_inventory( $item->get_id(), $item->get_product_id(), $data, $order_type_id );

							$found_inventory = TRUE;

							break;

						}

					}

					if ( ! $found_inventory ) {

						$data = [
							'qty'        => floatval( $merged_inventory_item->qty ),
							'subtotal'   => floatval( $merged_inventory_item->subtotal ),
							'total'      => floatval( $merged_inventory_item->total ),
							'extra_data' => maybe_serialize( $merged_inventory->prepare_order_item_inventory_extra_data( $item->get_id() ) ),
						];
						$merged_inventory->save_order_item_inventory( $item->get_id(), $item->get_product_id(), $data, $order_type_id );

					}
				}

			}

		}

	}

	/**
	 * Add delivery inventories when merging POs.
	 *
	 * @since 0.9.13
	 *
	 * @param DeliveryItemProductInventory $delivery_item_inventory
	 * @param Delivery                     $delivery
	 */
	public function duplicate_delivery_item_inventory( $delivery_item_inventory, $delivery ) {

		$po                = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE );
		$order_type_id     = AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE );
		$item_inventory_id = FALSE;
		$po_item_id        = FALSE;

		// Find the order_item_id with this inventory_id.
		foreach ( $po->get_items() as $po_item ) {
			$po_inventories = Inventory::get_order_item_inventories( $po_item->get_id(), $order_type_id );

			foreach ( $po_inventories as $po_inventory ) {
				if ( $delivery_item_inventory->get_inventory_id() === absint( $po_inventory->inventory_id ) ) {
					$item_inventory_id = $po_inventory->inventory_id;
					$po_item_id        = $po_item->get_id();
					break;
				}
			}
		}

		if ( $item_inventory_id && $po_item_id ) {
			$inventory = MIHelpers::get_inventory( $item_inventory_id );
			$args      = array(
				'name'         => $inventory->name,
				'inventory_id' => $inventory->id,
				'quantity'     => $delivery_item_inventory->get_quantity(),
				'po_item_id'   => $po_item_id,
			);

			$this->add_delivery_item_inventory( $delivery, $inventory, $delivery_item_inventory->get_quantity(), $args );
		}

	}

	/**
	 * Register the MI's posted data keys to be able to save them when saving an ATUM Order.
	 *
	 * @since 0.9.20
	 *
	 * @param string[]       $posted_data_keys
	 * @param AtumOrderModel $atum_order
	 *
	 * @return string[]
	 */
	public function add_mi_posted_data_keys( $posted_data_keys, $atum_order ) {

		if ( ! $atum_order instanceof POExtended ) {
			return $posted_data_keys;
		}

		// Add the PO PRO's custom MI data keys.
		return array_merge( $posted_data_keys, [
			'oi_inventory_cost',
			'oi_inventory_discount',
			'oi_inventory_discount_config',
		] );

	}

	/**
	 * Replace order id when reading the transient to read the current delivery's transient.
	 *
	 * @since 0.9.26
	 *
	 * @param int  $order_id
	 * @param bool $force
	 *
	 * @return int
	 */
	public function override_bom_order_items_transient_order_id( $order_id, $force ) {

		if ( ProductLevels::$current_delivery_item instanceof DeliveryItemProduct || ProductLevels::$current_delivery_item instanceof DeliveryItemProductInventory ) {

			$order_id = ProductLevels::$current_delivery_item->get_atum_order_id();
		}

		return $order_id;
	}

	/**
	 * Create a fake transient with the Delivery transient if set. Must work with override_bom_order_items_transient_order_id
	 *
	 * @since 0.9.23
	 *
	 * @param array $current_items
	 * @param int   $order_id
	 * @param bool  $force
	 *
	 * @return array
	 */
	public function override_bom_order_items_transient( $current_items, $order_id, $force ) {

		if ( $current_items && ProductLevels::$current_po_item instanceof POItemProduct && (
				ProductLevels::$current_delivery_item instanceof DeliveryItemProduct || ProductLevels::$current_delivery_item instanceof DeliveryItemProductInventory
			) ) {

			// Substitute the delivery item id by the PO order item id.
			$delivery_item_id = ProductLevels::$current_delivery_item->get_id();
			if ( in_array( $delivery_item_id, $current_items ) ) {
				$current_items[ ProductLevels::$current_po_item->get_id() ] = $current_items[ $delivery_item_id ];
				unset( $current_items[ $delivery_item_id ] );
			}

		}

		return $current_items;
	}

	/**
	 * Override the order item id to get the BOM order items from the current delivery item
	 *
	 * @since 0.9.27
	 *
	 * @param int $order_item_id
	 * @param int $order_type_id
	 *
	 * @return int
	 */
	public function override_bom_order_item_id( $order_item_id, $order_type_id ) {

		if (
			AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) === $order_type_id &&
			ProductLevels::$current_po_item instanceof POItemProduct && ProductLevels::$current_po_item->get_id() === $order_item_id &&
			( ProductLevels::$current_delivery_item instanceof DeliveryItemProduct || ProductLevels::$current_delivery_item instanceof DeliveryItemProductInventory )
		) {
			$order_item_id = ProductLevels::$current_delivery_item->get_id();
		}

		return $order_item_id;

	}

	/**
	 * Check if a MI item was fully added to stock.
	 *
	 * @since 0.9.24
	 *
	 * @param string              $stock_changed
	 * @param DeliveryItemProduct $delivery_item
	 * @param Delivery            $delivery
	 *
	 * @return string
	 */
	public function get_po_inventory_items_not_added_to_stock( $stock_changed, $delivery_item, $delivery ) {

		$product_id = $delivery_item->get_product_id();

		if ( MIHelpers::has_multi_inventory( $product_id ) ) {

			$delivery_inventory_items = $delivery->get_items( 'delivery_item_inventory' );
			$total_qty                = 0;

			foreach ( $delivery_inventory_items as $delivery_inventory_item ) {

				if ( 'yes' === $delivery_inventory_item->get_stock_changed() ) {
					$total_qty += $delivery_inventory_item->get_quantity();
				}

			}

			$stock_changed = $delivery_item->get_quantity() <= $total_qty ? 'yes' : 'no';

		}

		return $stock_changed;

	}

	/**
	 * Remove the delivery item after removing its last delivery item inventory.
	 *
	 * @since 0.9.26
	 *
	 * @param Delivery $delivery
	 * @param int      $po_item_id
	 * @param string   $delivery_item_type
	 * @param int      $product_id
	 * @param int|NULL $inventory_id
	 */
	public function after_remove_delivery_item( $delivery, $po_item_id, $delivery_item_type, $product_id, $inventory_id ) {

		if ( 'delivery_item_inventory' !== $delivery_item_type ) {
			return;
		}

		$inventories_count = 0;

		// Check if exist other delivery_item_inventories with the same product_id.
		foreach ( $delivery->get_items( 'delivery_item_inventory' ) as $delivery_inventory_item ) {

			/**
			 * Variable definition
			 *
			 * @var DeliveryItemProductInventory $delivery_inventory_item
			 */
			if ( $po_item_id !== $delivery_inventory_item->get_po_item_id() ) {
				continue;
			}

			$inventories_count++;

		}

		if ( $inventories_count > 0 ) {
			return;
		}

		// Remove the delivery_order_item.
		foreach ( $delivery->get_items() as $delivery_item ) {

			/**
			 * Variable definition
			 *
			 * @var DeliveryItemProduct $delivery_item
			 */
			if ( $po_item_id !== $delivery_item->get_po_item_id() ) {
				continue;
			}

			$delivery->remove_item( $delivery_item->get_id() );

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
				ProductLevels::$current_po_item->get_id() !== $order_item_id ||
				ProductLevels::$current_delivery_item->get_po_item_id() !== $order_item_id ) {

				return $insert;
			}

			$order_id         = $order_item->get_atum_order_id();
			$delivery_item_id = ProductLevels::$current_delivery_item->get_id();

			// The current BOM product has MI enabled.
			if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $bom_product ) && MIHelpers::is_product_multi_inventory_compatible( $bom_product ) ) {

				$bom_id      = $bom_product->get_id();
				$inventories = MIHelpers::get_product_inventories_sorted( $bom_id );

				// Filter the usable inventories before using them.
				$only_main = BOMModel::has_linked_bom( $bom_id ) && PLHelpers::is_bom_stock_control_enabled();
				foreach ( $inventories as $index => $inventory ) {

					// If has BOMs, only can use the main inventory.
					if ( $only_main && ! $inventory->is_main() ) {
						unset( $inventories[ $index ] );
					}

				}

				if ( ! empty( $inventories ) ) {

					$insert = FALSE;

					// Returns the Delivery transient.
					$bom_order_item_inventories = PLHelpers::get_bom_order_items_transient( $order_id );

					if ( isset( $bom_order_item_inventories, $bom_order_item_inventories[ $delivery_item_id ], $bom_order_item_inventories[ $delivery_item_id ][ $bom_id ] ) ) {

						foreach ( $bom_order_item_inventories[ $delivery_item_id ][ $bom_id ] as $bom_inventory ) {

							if ( floatval( $bom_inventory['used'] ) <= 0 ) {
								continue;
							}

							// Find the inventory.
							$inventory = NULL;
							foreach ( $inventories as $index => $inv ) {

								if ( $inv->id === $bom_inventory['id'] ) {
									$inventory = $inv;
									unset( $inventories[ $index ] ); // Disable this inventory for the next iterations.
									break;
								}

							}

							if ( is_null( $inventory ) ) {
								continue; // Inventory deleted?
							}

							// Insert the BOM order item.
							BOMOrderItemsModel::insert_bom_order_item( $delivery_item_id, $order_type_id, $bom_id, $linked_bom->bom_type, $bom_inventory['used'], $inventory->id );

						}

					}
					else {

						// If order item product (the parent one) has the MI disabled, no order item inventories were added, so bypass this checking.
						$product = $order_item->get_product();
						if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) {

							// Check whether the current delivery item is a DeliveryItemProductInventory.
							if ( ! ProductLevels::$current_delivery_item instanceof DeliveryItemProductInventory ) {
								return $insert;
							}

							// Check if the delivery item inventory is main.
							$delivery_item_inventory_id = ProductLevels::$current_delivery_item->get_inventory_id();
							$delivery_item_inventory    = MIHelpers::get_inventory( $delivery_item_inventory_id );

							if ( ! $delivery_item_inventory->is_main() && PLHelpers::is_bom_stock_control_enabled() ) {
								return $insert;
							}

							// Check if the Main Inventory was added to this order item.
							// As the Main Inventory is the only one allowed to contain BOMs, if it's not used, no BOMs will be deducted here.
							$order_item_inventories = Inventory::get_order_item_inventories( $order_item_id, $order_type_id );

							foreach ( $order_item_inventories as $order_item_inventory ) {

								if ( (int) $order_item_inventory->inventory_id === $delivery_item_inventory_id ) {

									$qty              = ProductLevels::$current_delivery_item->get_quantity() * $accumulated_multiplier;
									$inventory_to_use = NULL;

									foreach ( $inventories as $inventory ) {

										if ( $inventory->is_main() ) {
											$inventory->set_stock_status();
										}

										if ( 'outofstock' === $inventory->stock_status ) {
											$inventory_to_use = $inventory;
											break;
										}
									}

									if ( is_null( $inventory_to_use ) ) {
										// Just get the first one. No need to check for the stock status as it's a manual order.
										$inventory_to_use = current( $inventories );
									}

									BOMOrderItemsModel::insert_bom_order_item( $delivery_item_id, $order_type_id, $bom_id, $linked_bom->bom_type, $qty, $inventory_to_use->id );

									break;
								}

							}
						}

					}

				}

			}
			else {

				// If order item product (the parent one) has the MI disabled, no order item inventories were added, so bypass this checking.
				$product = $order_item->get_product();
				if ( 'yes' === MIHelpers::get_product_multi_inventory_status( $product ) && MIHelpers::is_product_multi_inventory_compatible( $product ) ) {

					// Check whether the current delivery item is a DeliveryItemProductInventory.
					if ( ! ProductLevels::$current_delivery_item instanceof DeliveryItemProductInventory ) {
						return $insert;
					}

					// Check if the delivery item inventory is main.
					$delivery_item_inventory_id = ProductLevels::$current_delivery_item->get_inventory_id();
					$delivery_item_inventory    = MIHelpers::get_inventory( $delivery_item_inventory_id );

					if ( ! $delivery_item_inventory->is_main() && PLHelpers::is_bom_stock_control_enabled() ) {
						return $insert;
					}

					// TODO: Probably the below foreach is no needed because we know which inventory are going to use.
					// Check if the Main Inventory was added to this order item.
					// As the Main Inventory is the only one allowed to contain BOMs, if it's not used, no BOMs will be deducted here.
					$order_item_inventories = Inventory::get_order_item_inventories( $order_item_id, $order_type_id );

					foreach ( $order_item_inventories as $order_item_inventory ) {

						if ( (int) $order_item_inventory->inventory_id === $delivery_item_inventory_id ) {
							// only product's main inventory qty can be deducted.
							$qty = wc_stock_amount( ProductLevels::$current_delivery_item->get_quantity() * $accumulated_multiplier );
							BOMOrderItemsModel::insert_bom_order_item( $delivery_item_id, $order_type_id, $linked_bom->bom_id, $linked_bom->bom_type, $qty );
							$insert = FALSE;
							break;
						}
					}

				}
			}
		}

		return $insert;
	}

	/**
	 * Calculate inventory gross profit within PO Pro settings.
	 *
	 * @since 1.0.4
	 *
	 * @param string    $gross_profit
	 * @param Inventory $inventory
	 *
	 * @return string
	 */
	public function calculate_inventory_gross_profit( $gross_profit, $inventory ) {

		$purchase_price = (float) $inventory->purchase_price;
		$price          = (float) $inventory->price;
		$supplier       = new Supplier( $inventory->supplier_id );
		$product        = AtumHelpers::get_atum_product( $inventory->product_id );

		return Helpers::calculate_supplier_gross_profit( $gross_profit, $product, $price, $purchase_price, $supplier );

	}

	/**
	 * Find inventories from a suppliers list and add their products to the list for adding items to the PO.
	 *
	 * @since 1.0.4
	 *
	 * @param array     $ids        The products id previous list to add.
	 * @param string    $mode       The criteria to find inventories. Can be 'out_stock', 'low_stock' or 'restock'.
	 * @param array     $suppliers  The suppliers list.
	 * @param int|false $po_id      (Optional) The PO to add the items.
	 *
	 * @return array
	 */
	public function add_supplier_items_to_po( $ids, $mode, $suppliers, $po_id = FALSE ) {

		global $wpdb;

		$inventories_table    = $wpdb->prefix . Inventory::INVENTORIES_TABLE;
		$inventory_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
		$post_statuses        = AtumGlobals::get_queryable_product_statuses();

		// Add main inventories to results.
		$main_inventories = $ids ? $wpdb->get_results( "SELECT product_id, id FROM $inventories_table WHERE is_main=1 AND product_id IN (" . implode( ',', $ids ) . ')' ) : []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! empty( $main_inventories ) ) {
			foreach ( $main_inventories as $main_inventory ) {
				$ids[] = "$main_inventory->product_id:$main_inventory->id";
			}
		}

		switch ( $mode ) {
			case 'low_stock':
				$low_stock_threshold = get_option( 'woocommerce_notify_low_stock_amount' );

				$low_stock_join = apply_filters( 'atum/purchase_orders_pro/multi_inventory/add_supplier_items/low_stock_join', [
					"$inventory_meta_table im ON (i.id = im.inventory_id)",
					"$wpdb->posts p ON i.product_id = p.ID",
				]);

				$global_low_stock_clause = FALSE === $low_stock_threshold || '' === $low_stock_threshold ? '' : "OR ( ( lsa.meta_value IS NULL OR lsa.meta_value = '' ) AND s.meta_value + 0 <= $low_stock_threshold )";

				$low_stock_where = apply_filters( 'atum/purchase_orders_pro/multi_inventory/add_supplier_items/low_stock_where', [
					'im.supplier_id IN (' . implode( ',', $suppliers ) . ')',
					"p.post_type IN ('product', 'product_variation')",
					"p.post_status IN ('" . implode( "','", $post_statuses ) . "')",
					'im.stock_quantity IS NOT NULL',
					"( ( im.low_stock_threshold IS NOT NULL AND im.stock_quantity < im.low_stock_threshold )
						$global_low_stock_clause )",
				]);

				// Get all the products that have their stock under low stock amount value.
				$sql = "SELECT i.product_id, i.id FROM $inventories_table i INNER JOIN " .
					implode( ' INNER JOIN ', $low_stock_join ) .
					' WHERE ' . implode( ' AND ', $low_stock_where );
				break;
			case 'restock':
				// The inventories don't use restock_status.
				return $ids;
			case 'out_stock':
			default:
				$out_stock_join = apply_filters( 'atum/purchase_orders_pro/multi_inventory/add_supplier_items/out_stock_join', [
					"$inventory_meta_table im ON (i.id = im.inventory_id)",
					"$wpdb->posts p ON i.product_id = p.ID",
				] );

				$out_stock_where = apply_filters( 'atum/purchase_orders_pro/multi_inventory/add_supplier_items/out_stock_where', [
					'im.supplier_id IN (' . implode( ',', $suppliers ) . ')',
					"p.post_type IN ('product', 'product_variation')",
					"p.post_status IN ('" . implode( "','", $post_statuses ) . "')",
					"im.stock_status != 'instock'",
				] );

				// Get all the products that aren't in stock and aren't inheritables.
				$sql = "SELECT i.product_id, i.id FROM $inventories_table i INNER JOIN " .
						implode( ' INNER JOIN ', $out_stock_join ) .
						' WHERE ' . implode( ' AND ', $out_stock_where );
				break;
		}

		// If it's an actual PO, exclude its saved items.
		if ( ! empty( $po_id ) ) {

			$po             = AtumHelpers::get_atum_order_model( absint( $po_id ), TRUE, PurchaseOrders::POST_TYPE );
			$po_items       = $po->get_items();
			$po_product_ids = [];

			foreach ( $po_items as $po_item ) {
				$po_product_ids[] = $po_item->get_variation_id() ?: $po_item->get_product_id();
			}

			if ( ! empty( $po_product_ids ) ) {
				$sql .= ' AND i.product_id NOT IN (' . implode( ',', array_unique( $po_product_ids ) ) . ')';
			}
		}

		$found_products = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		foreach ( $found_products as $found_product ) {
			$ids[] = $found_product->product_id;
			$ids[] = "$found_product->product_id:$found_product->id";
		}

		return array_unique( $ids );
	}

	/**
	 * Add delivery stuff to the inventory's inbound stock query select.
	 *
	 * @since 1.0.5
	 *
	 * @param string    $select
	 * @param Inventory $inventory
	 *
	 * @return string
	 */
	public function inventory_inbound_stock_select( $select, $inventory ) {

		$select = 'SUM(io.`qty` - IFNULL(doi_inv.`d_qty`,0)) qty';

		return $select;
	}

	/**
	 * Add delivery stuff to the inventory's inbound stock query select.
	 *
	 * @since 1.0.5
	 *
	 * @param array     $joins
	 * @param Inventory $inventory
	 *
	 * @return array
	 */
	public function inventory_inbound_stock_joins( $joins, $inventory ) {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return array_merge( $joins, [
			$wpdb->prepare("
				LEFT JOIN (SELECT doim_p.`meta_value` po_item_id, SUM( doim_q.`meta_value`) d_qty FROM `$wpdb->atum_order_itemmeta` doim_p
					INNER JOIN `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS doi ON doi.`order_item_id` = doim_p.`order_item_id` AND doi.`order_item_type` = 'delivery_item_inventory'
					INNER JOIN `$wpdb->atum_order_itemmeta` AS doim_i ON doim_p.`order_item_id` = doim_i.`order_item_id` AND doim_i.`meta_key` = '_inventory_id' AND doim_i.`meta_value` = %d
					INNER JOIN `$wpdb->atum_order_itemmeta` AS doim_q ON doi.`order_item_id` = doim_q.`order_item_id` AND  doim_q.`meta_key` = '_qty'
		        WHERE doim_p.`meta_key` = '_po_item_id'
		        GROUP BY doim_p.`meta_value` ) doi_inv
		        ON oi.`order_item_id` = doi_inv.`po_item_id`
	        ", $inventory->id ),
		] );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Add inventories to items in the Add PO items modals.
	 *
	 * @since 1.0.4
	 *
	 * @param array $prepared_list
	 * @param array $ids
	 *
	 * @return array
	 */
	public function prepare_inventories( $prepared_list, $ids ) {

		foreach ( $prepared_list as $i => $product_data ) {
			if ( isset( $product_data['inventories'] ) ) {
				unset( $prepared_list[ $i ]['inventories'] );
			}
		}

		foreach ( $ids as $id ) {

			if ( ! str_contains( $id, ':' ) ) {
				continue;
			}

			list( $product_id, $inventory_id ) = array_map( 'absint', explode( ':', $id ) );

			// Orphan inventories.
			if ( FALSE === in_array( $product_id, array_keys( $prepared_list ) ) ) {
				continue;
			}

			$product = AtumHelpers::get_atum_product( $product_id );

			if ( ! $product || ! MIHelpers::has_multi_inventory( $product ) ) {
				continue;
			}

			$inventory = new Inventory( $inventory_id );

			$inventory_data = $this->get_inventory_data( $inventory, MIHelpers::has_multi_price( $product ) );

			$prepared_list[ $product_id ]['inventories'][] = $inventory_data;

			// Assign isInventory after added to product inventories.
			$inventory_data['isInventory'] = TRUE;

			/*$position     = array_search( $product_id, array_keys( $prepared_list ) ) + 1;
			$array_before = array_slice( $prepared_list, 0, $position, TRUE );
			$array_after  = array_slice( $prepared_list, $position, count( $prepared_list ) -$position , TRUE );

			$prepared_list = $array_before + array( $inventory_id => $inventory_data ) + $array_after;*/
			$prepared_list[ $inventory_id ] = $inventory_data;

		}

		return $prepared_list;

	}

	/**
	 * Disable the manage stock warning icon for MI products
	 *
	 * @since 1.0.5
	 *
	 * @param bool        $show
	 * @param \WC_Product $product
	 *
	 * @return bool
	 */
	public function maybe_show_item_unamanaged_stock_warning( $show, $product ) {

		if ( MIHelpers::has_multi_inventory( $product ) ) {
			return FALSE;
		}

		return $show;

	}

	/**
	 * Change the purchase price for all the inventories at once.
	 *
	 * @param string $action
	 * @param float  $decimal_tax
	 */
	public function change_inventories_purchase_prices( $action, $decimal_tax ) {

		global $wpdb;

		$inventory_meta_table = $wpdb->prefix . Inventory::INVENTORY_META_TABLE;
		if ( 'add' === $action ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "
			UPDATE $inventory_meta_table imt
			SET imt.purchase_price = ROUND( (imt.purchase_price * $decimal_tax), 2 )
			WHERE imt.purchase_price > 0 AND imt.purchase_price IS NOT NULL
		" );
			// phpcs:enable

		}
		elseif ( 'deduct' === $action ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "
			UPDATE $inventory_meta_table imt
			SET imt.purchase_price = ROUND( (imt.purchase_price / $decimal_tax), 2 )
			WHERE imt.purchase_price > 0 AND imt.purchase_price IS NOT NULL
		" );
			// phpcs:enable

		}

	}

	/**
	 * Show the right available stock on PO items for products with MI enabled
	 *
	 * @since 1.1.2
	 *
	 * @param int|float|string $stock
	 * @param \WC_Product      $product
	 *
	 * @return int|float|string
	 */
	public function show_mi_product_available_stock( $stock, $product ) {

		if ( $product instanceof \WC_Product && MIHelpers::has_multi_inventory( $product ) ) {
			$stock = wc_stock_amount( $product->get_stock_quantity() );
		}

		return $stock;

	}

	/**
	 * Add the order item inventories to the returned PO items
	 *
	 * @since 1.1.2
	 *
	 * @param array           $returned_items
	 * @param POItemProduct[] $items
	 * @param POExtended      $returning_po
	 *
	 * @return array
	 */
	public function add_returned_inventory_items( $returned_items, $items, $returning_po ) {

		foreach ( $items as $item ) {

			$inventory_order_items = Inventory::get_order_item_inventories( $item->get_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

			if ( ! empty( $inventory_order_items ) ) {
				foreach ( $inventory_order_items as $inventory_order_item ) {

					$id = "{$inventory_order_item->product_id}:{$inventory_order_item->inventory_id}";

					if ( array_key_exists( $id, $returned_items ) ) {
						$returned_items[ $id ] += $inventory_order_item->qty;
					}
					else {
						$returned_items[ $id ] = $inventory_order_item->qty;
					}

				}
			}

		}

		return $returned_items;

	}

	/**
	 * Add the order item inventories to the deivererd PO items
	 *
	 * @since 1.1.2
	 *
	 * @param array                 $delivered_items
	 * @param DeliveryItemProduct[] $delivery_items
	 * @param Delivery              $delivery
	 *
	 * @return array
	 */
	public function add_delivered_inventory_items( $delivered_items, $delivery_items, $delivery ) {

		foreach ( $delivery_items as $delivery_item ) {

			if ( ! $delivery_item instanceof DeliveryItemProductInventory ) {
				continue;
			}

			$inventory = MIHelpers::get_inventory( $delivery_item->get_inventory_id() );

			$id = "{$inventory->product_id}:{$inventory->id}";

			if ( array_key_exists( $id, $delivered_items ) ) {
				$delivered_items[ $id ] += $delivery_item->get_quantity();
			}
			else {
				$delivered_items[ $id ] = $delivery_item->get_quantity();
			}

		}

		return $delivered_items;

	}

	/**
	 * Restore the inventories when processing a returned PO
	 *
	 * @since 1.1.2
	 *
	 * @param DeliveryItemProductInventory $delivery_item_inventory
	 * @param Delivery                     $delivery
	 * @param POExtended                   $po
	 * @param string                       $action
	 */
	public function maybe_return_order_item_inventories( $delivery_item_inventory, $delivery, $po, $action = 'decrease' ) {

		// NOTE: we assume that the PO passed is a returnig or cancelled PO because it's being checked beforehand.
		if ( $delivery_item_inventory instanceof DeliveryItemProductInventory ) {

			// If this item's stock was increased previously, must undo the change.
			if ( 'yes' === $delivery_item_inventory->get_stock_changed() ) {

				$inventory_id               = $delivery_item_inventory->get_inventory_id();
				$delivery_item_inv_quantity = (float) $delivery_item_inventory->get_quantity();
				$inventory                  = MIHelpers::get_inventory( $inventory_id );

				// If it's a cancelled PO, all the items should be restored, for returning POs it can be a part of them.
				if ( $po->is_returning() ) {

					$po_items = $po->get_items();

					foreach ( $po_items as $po_item ) {

						$po_item_inventories = Inventory::get_order_item_inventories( $po_item->get_id(), AtumGlobals::get_order_type_id( PurchaseOrders::POST_TYPE ) );

						if ( ! empty( $po_item_inventories ) ) {
							$found_po_inventory_item = wp_list_filter( $po_item_inventories, [ 'inventory_id' => $inventory_id ] );

							if ( ! empty( $found_po_inventory_item ) ) {
								$delivery_item_inv_quantity = (float) ( current( $found_po_inventory_item ) )->qty;
								break;
							}
						}

					}

					// Order item inventory not found in the returning PO??
					if ( empty( $found_po_inventory_item ) ) {
						return;
					}

					// In case there are items previously returned.
					$returned_items = (float) $delivery_item_inventory->get_returned_qty();
					$quantity       = (float) $delivery_item_inventory->get_quantity() - $returned_items;

					if ( $quantity > $delivery_item_inv_quantity ) {
						$quantity = $delivery_item_inv_quantity;
					}

				}
				else {
					$quantity = $delivery_item_inv_quantity - (float) $delivery_item_inventory->get_returned_qty();
				}

				if ( $quantity > 0 ) {

					if ( $po->is_returning() ) {

						// NOTE: it can be a partial return and, later, there could be another one. So we have to control the units discounted on each.
						if ( isset( $this->returned_delivery_item_inventory_qtys[ $inventory_id ] ) ) {

							// If we already restored all the required items for the returning PO, just do nothing.
							if ( $this->returned_delivery_item_inventory_qtys[ $inventory_id ] >= $quantity ) {
								return;
							}

							$restore_qty = $quantity - $this->returned_delivery_item_inventory_qtys[ $inventory_id ];

							$this->returned_delivery_item_inventory_qtys[ $inventory_id ] += $quantity;

						}
						else {
							$this->returned_delivery_item_inventory_qtys[ $inventory_id ] = $quantity;

							$restore_qty = $quantity;
						}

						if ( ( (float) $delivery_item_inventory->get_quantity() - ( $returned_items ?? 0 ) ) !== $restore_qty ) {
							$delivery_item_inventory->set_stock_changed( TRUE );
						}
						else {
							$delivery_item_inventory->set_stock_changed( FALSE );
						}

						$delivery_item_inventory->set_returned_qty( $restore_qty );

					}
					// Cancelled PO.
					else {
						$restore_qty = $quantity;
						$delivery_item_inventory->set_stock_changed( FALSE );
					}

					$delivery_item_inventory->save();
					$old_stock = $inventory->stock_quantity;
					$new_stock = MIHelpers::update_inventory_stock( $inventory->product_id, $inventory, $restore_qty, $action );

					$po      = $delivery->get_po_object();
					$product = wc_get_product( $inventory->product_id );

					/* translators: 1.the formatted product name, 2. Old stock, 3. New Stock, 4. Used inventory */
					$po->add_order_note( sprintf( __( ' Stock levels changed: [%1$s], %2$s &rarr; %3$s using inventory &quot;%4$s&quot;', ATUM_PO_TEXT_DOMAIN ), $product->get_formatted_name(), $old_stock, $new_stock, $inventory->name ) );

					do_action( 'atum/purchase_orders_pro/after_restore_delivery_item_inventory_to_stock', $inventory, $delivery_item_inventory, $delivery );

				}
				else {
					$delivery_item_inventory->set_stock_changed( FALSE );
					$delivery_item_inventory->save();
				}

			}

		}

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
	 * @return MultiInventory instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
