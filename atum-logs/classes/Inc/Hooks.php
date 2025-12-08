<?php
/**
 * Atum Action Logs hooks
 *
 * @package         AtumLogs
 * @subpackage      Inc
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.0.1
 */

namespace AtumLogs\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\InventoryLogs\Models\Log;
use Atum\InventoryLogs\Models\LogItem;
use Atum\Modules\ModuleManager;
use Atum\PurchaseOrders\Models\POItem;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use Atum\Suppliers\Suppliers;
use AtumLevels\Inc\Globals as AtumPlGlobals;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumMultiInventory\Inc\Helpers as AtumMIHelpers;
use AtumLevels\Inc\Helpers as AtumPlHelpers;
use AtumMultiInventory\Models\Inventory;
use AtumLevels\Models\BOMModel;
use Automattic\WooCommerce\Utilities\OrderUtil;

class Hooks {

	/**
	 * The singleton instance holder
	 *
	 * @var Hooks $instance
	 */
	private static $instance;

	/**
	 * Hooks singleton constructor
	 */
	private function __construct() {

		if ( is_admin() || AtumHelpers::is_rest_request() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register the admin-side hooks
	 *
	 * @since 0.0.1
	 */
	public function register_admin_hooks() {

		if ( ModuleManager::is_module_active( 'stock_central' ) ) {

			// Save from stock central.
			add_filter( 'atum/ajax/before_update_product_meta', array( $this, 'sc_before_update_product_data' ), 1 );
			add_action( 'atum/product_data/after_save_data', array( $this, 'sc_log_update_product_data' ), 10, 2 );

			// Save locations at stock central.
			add_action( 'atum/ajax/stock_central_list/before_set_locations', array( $this, 'sc_before_locations' ) );
			add_action( 'atum/ajax/stock_central_list/after_set_locations', array( $this, 'sc_after_locations' ) );

			// Stock Central bulk actions.
			add_action( 'atum/ajax/list_table/bulk_action_applied', array( $this, 'sc_apply_bulk_action' ), 100 );
		}

		if ( ModuleManager::is_module_active( 'data_export' ) ) {

			// Export Data from Stock Central / Manufacturing Central.
			add_action( 'atum/data_export/before_export_data', array( $this, 'sc_export_data' ) );

		}

		if ( ModuleManager::is_module_active( 'purchase_orders' ) ) {

			// Create/Save Purchase Order.
			add_action( 'save_post_atum_purchase_order', array( $this, 'po_save_order' ), 5 );

			// Set the purchase price for the product X from the Purchase Order.
			add_action( 'atum/ajax/atum_order/before_set_purchase_price', array( $this, 'po_change_order_item_purchase_price' ), 10, 3 );

			// Printed the Purchase Order X to PDF.
			add_action( 'atum/purchase_orders/po_export/generate', array( $this, 'po_generate_pdf' ) );

			// The stock levels of product X changed after changing the PO status.
			add_action( 'atum/purchase_orders/po/after_increase_stock_levels', array( $this, 'po_changed_stock_levels' ), 1 );
			add_action( 'atum/purchase_orders/po/after_decrease_stock_levels', array( $this, 'po_changed_stock_levels' ), 1 );

			// Log changed stock levels from Purchase Order items.
			//add_action( 'atum/purchase_orders/after_save', array( $this, 'po_changed_stock_levels' ) );
		}

		// Moved to trash/Deleted Permanently the Purchase Order/Inventory Log/Supplier/Product.
		add_action( 'deleted_post', array( $this, 'log_trash_delete' ), 1 );
		add_action( 'wp_trash_post', array( $this, 'log_trash_delete' ), 1 );
		// Restored the Purchase Order from trash.
		add_action( 'untrashed_post', array( $this, 'log_trash_delete' ), 1 );

		if ( ModuleManager::is_module_active( 'purchase_orders' ) || ModuleManager::is_module_active( 'inventory_logs' ) ) {
			// PO before save status.
			add_action( 'pre_post_update', array( $this, 'po_before_save' ), 10, 2 );
			// Atum Orders status changes.
			add_action( 'atum/atum_order_model/update_status', array( $this, 'po_mark_atum_order' ), 10, 2 );
			// Added a new product to the Purchase Order/Inventory Log.
			add_action( 'atum/atum_order/add_order_item_meta', array( $this, 'po_add_order_item' ), 10, 3 );
			// Added a new fee to the Purchase Order.
			add_action( 'atum/ajax/atum_order/fee_added', array( $this, 'po_add_order_fee_shipping' ), 10, 2 );
			// Added a new fee shipping cost to the Purchase Order.
			add_action( 'atum/ajax/atum_order/shipping_cost_added', array( $this, 'po_add_order_fee_shipping' ), 10, 2 );
			// Added a new tax to the Purchase Order.
			add_action( 'atum/ajax/atum_order/tax_added', array( $this, 'po_add_order_tax' ), 10, 2 );
			// Changed the (quantity, price…) for the order item X on the Purchase Order Y.
			add_action( 'atum/ajax/atum_order/before_save_order_items', array( $this, 'po_save_order_items' ), 10, 2 );
			// Deleted the order item X from the Purchase Order Y.
			add_action( 'atum/ajax/atum_order/before_remove_order_items', array( $this, 'po_remove_order_item' ), 10, 2 );
			// Added meta to the Purchase Order.
			add_action( 'wp_ajax_add-meta', array( $this, 'po_add_meta' ), 1 );
			// Added a note to the Purchase Order/Inventory Log.
			add_action( 'atum/ajax/atum_order/note_added', array( $this, 'atum_order_add_note' ), 10, 2 );
			// Removed a note from the Purchase Order/Inventory Log.
			add_action( 'atum/ajax/atum_order/before_remove_note', array( $this, 'atum_order_remove_note' ) );
			// The stock levels of product X changed after changing the PO status.
			add_action( 'atum/orders/status_atum_received', array( $this, 'po_register_stock_levels_received' ), 1, 2 );
			add_action( 'atum/orders/status_changed', array( $this, 'po_register_stock_levels_changed' ), 1, 4 );
		}

		// Create/Save Supplier.
		add_action( 'save_post_atum_supplier', array( $this, 'sup_save_supplier' ), 5 );

		if ( ModuleManager::is_module_active( 'inventory_logs' ) ) {
			// Create/Save Inventory Logs.
			add_action( 'save_post_atum_inventory_log', array( $this, 'il_save_inventory_log' ), 5 );
			add_action( 'atum/ajax/increase_atum_order_stock', array( $this, 'il_increase_stock' ), PHP_INT_MAX );
			add_action( 'atum/ajax/decrease_atum_order_stock', array( $this, 'il_decrease_stock' ), PHP_INT_MAX );
			add_action( 'woocommerce_restore_order_stock', array( $this, 'order_change_stock' ), PHP_INT_MAX );
		}

		// Changed the option X from ATUM Settings / Enable/disable modules.
		add_action( 'updated_option', array( $this, 'settings_updated_option' ), 10, 3 );
		// Tools execution.
		foreach ( [ 'ajax', 'cli' ] as $source ) {
			add_action( "atum/$source/tool_change_manage_stock", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_change_control_stock", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_clear_out_stock_threshold", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_clear_out_atum_transients", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_mi_migrate_regions", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_mi_migrate_countries", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_mi_migrate_shipping_zones", array( $this, 'settings_execute_tools' ) );
			add_action( "atum/$source/tool_sync_real_stock", array( $this, 'settings_execute_tools' ), 1 );
		}

		// Addons.
		add_action( 'atum/addons/activate_license', array( $this, 'addons_license' ) );
		add_action( 'atum/addons/deactivate_license', array( $this, 'addons_license' ) );

		// Atum locations create.
		add_action( 'create_term', array( $this, 'location_create' ), 10, 3 );
		// Atum locations delete.
		add_action( 'delete_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY, array( $this, 'location_delete' ), 10, 4 );
		// Update atum locations.
		add_action( 'edit_terms', array( $this, 'location_before_update' ), 10, 2 );
		add_action( 'edited_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY, array( $this, 'location_after_update' ), 10, 2 );

		// Create WC Product / Product data / Locations assigned.
		add_action( 'woocommerce_product_bulk_and_quick_edit', array( $this, 'before_product_save' ), 9, 2 );
		add_action( 'woocommerce_product_quick_edit_save', array( $this, 'after_product_quick_save' ) );
		add_action( 'pre_post_update', array( $this, 'before_product_save' ), 10, 2 );
		add_action( 'save_post', array( $this, 'after_product_save' ), 10, 3 );

		// Add categories/tags.
		add_action( 'create_term', array( $this, 'wc_term_create' ), 10, 3 );
		// Add attributes.
		add_action( 'woocommerce_attribute_added', array( $this, 'wc_attribute_create' ), 1, 2 );
		// Delete categories/tags.
		add_action( 'delete_product_cat', array( $this, 'wc_term_delete' ), 10, 4 );
		add_action( 'delete_product_tag', array( $this, 'wc_term_delete' ), 10, 4 );
		// Delete attributes.
		add_action( 'woocommerce_attribute_deleted', array( $this, 'wc_attribute_delete' ), 1, 3 );
		// Update categories/tags.
		add_action( 'edit_terms', array( $this, 'wc_term_before_update' ), 1, 2 );
		add_action( 'edited_product_cat', array( $this, 'wc_term_after_update' ), 1, 2 );
		add_action( 'edited_product_tag', array( $this, 'wc_term_after_update' ), 1, 2 );
		// Updated attribute.
		add_action( 'woocommerce_attribute_updated', array( $this, 'wc_attribute_updated' ), 1, 3 );
		// Save attributes from product.
		add_action( 'wp_ajax_woocommerce_save_attributes', array( $this, 'wc_save_product_attributes' ), 1 );
		// Add attribute to product.
		add_action( 'wp_ajax_woocommerce_add_new_attribute', array( $this, 'wc_add_new_attribute' ), 1 );
		// Save variations from product.
		add_action( 'wp_ajax_woocommerce_save_variations', array( $this, 'wc_save_variations' ), 2 );
		// Add variation to product.
		add_action( 'product_variation_linked', array( $this, 'wc_link_variation' ) );
		add_action( 'woocommerce_before_product_object_save', array( $this, 'wc_before_new_variation' ), 10, 2 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'wc_after_new_variation' ), 10, 2 );
		// Delete variations.
		add_action( 'wp_ajax_woocommerce_bulk_edit_variations', array( $this, 'wc_before_delete_variations' ), 1 );
		add_action( 'wp_ajax_woocommerce_remove_variations', array( $this, 'wc_before_delete_variations' ), 1 );
		add_action( 'wp_ajax_woocommerce_load_variations', array( $this, 'wc_after_delete_variations' ), 1 );

		// Create/Save WC Order.
		add_action( 'woocommerce_new_order', array( $this, 'wc_create_order' ), PHP_INT_MAX, 2 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'before_order_save' ), 10, 2 );
		add_action( 'woocommerce_before_shop_order_object_save', array( $this, 'before_order_save' ), 10, 2 );
		add_action( 'woocommerce_update_order', array( $this, 'after_order_save' ), PHP_INT_MAX, 2 );
		add_action( 'woocommerce_new_order_item', array( $this, 'wc_after_add_order_item' ), 10, 3 );
		add_action( 'woocommerce_after_order_object_save', array( $this, 'wc_saved_order_items' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'wc_saved_order_items' ) );
		add_action( 'woocommerce_before_save_order_items', array( $this, 'before_order_items_save' ), 1 );

		// Bulk actions order status.
		add_filter( 'woocommerce_bulk_action_ids', array( $this, 'wc_before_bulk_order_status' ), 10, 3 );
		// Add order item.
		add_action( 'wp_ajax_woocommerce_add_order_item', array( $this, 'before_add_order_item' ), 10, 3 );
		add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'wc_add_order_item' ), PHP_INT_MAX, 3 );
		add_action( 'wp_ajax_woocommerce_add_order_fee', array( $this, 'wc_add_order_fee_shipping' ), 1 );
		add_action( 'wp_ajax_woocommerce_add_order_shipping', array( $this, 'wc_add_order_fee_shipping' ), 1 );
		add_action( 'wp_ajax_woocommerce_add_order_tax', array( $this, 'wc_add_order_fee_shipping' ), 1 );
		// Delete order item.
		add_action( 'wp_ajax_woocommerce_remove_order_item', array( $this, 'wc_delete_order_item' ), 1 );
		// Save order items.
		add_action( 'wp_ajax_woocommerce_save_order_items', array( $this, 'wc_save_order_items' ), 1 );
		// Add order note.
		add_action( 'wp_ajax_woocommerce_add_order_note', array( $this, 'wc_add_order_note' ), 1 );
		// Add coupon discount.
		add_action( 'wp_ajax_woocommerce_add_coupon_discount', array( $this, 'wc_add_coupon_discount' ), 1 );
		// Refund.
		add_action( 'woocommerce_order_refunded', array( $this, 'wc_order_refund' ), 10, 2 );
		// Email notifications.
		add_action( 'woocommerce_after_resend_order_email', array( $this, 'wc_email_notifications' ), 10, 2 );

		// Create/save Coupons.
		add_action( 'pre_post_update', array( $this, 'before_coupon_save' ), 10, 2 );
		add_action( 'save_post', array( $this, 'after_coupon_save' ), 10, 3 );

		// WC Settings.
		add_action( 'woocommerce_admin_settings_sanitize_option', array( $this, 'wc_save_settings' ), 10, 3 );

	}

	/**
	 * Register the global hooks
	 *
	 * @since 0.0.1
	 */
	public function register_global_hooks() {

		// Product stock status.
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'wc_product_updated' ), 10, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'wc_product_updated' ), 10, 3 );

		// WC Orders.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'wc_new_customer_order' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'store_api_wc_new_customer_order' ) );

		// Product reviews.
		add_action( 'set_comment_cookies', array( $this, 'wc_set_comment' ), 10, 2 );

		// WC API hooks.
		$objects_list = [ 'product', 'product_variation', 'shop_order', InventoryLogs::POST_TYPE, PurchaseOrders::POST_TYPE ];
		foreach ( $objects_list as $obj ) {
			add_filter( "woocommerce_rest_pre_insert_{$obj}", array( $this, 'wc_v1_api_before' ), 1, 3 );
			add_filter( "woocommerce_rest_pre_insert_{$obj}_object", array( $this, 'wc_api_before' ), 1, 3 );
			add_filter( "woocommerce_rest_insert_{$obj}", array( $this, 'wc_api_after' ), PHP_INT_MAX, 3 );
			add_filter( "woocommerce_rest_insert_{$obj}_object", array( $this, 'wc_api_after' ), PHP_INT_MAX, 3 );
			add_filter( "woocommerce_rest_{$obj}_object_trashable", array( $this, 'wc_api_before_delete_object' ), 1, 2 );
			add_filter( "woocommerce_rest_delete_{$obj}_object", array( $this, 'wc_api_delete_object' ), PHP_INT_MAX, 3 );
		}
		add_filter( 'rest_request_before_callbacks', array( $this, 'wc_api_order_before' ), 10, 3 );
		add_action( 'atum/api/rest_set_atum_order_item', array( $this, 'api_before_save_atum_order_items' ), 10, 3 );
		add_action( 'atum/api/rest_after_set_atum_order_item', array( $this, 'api_after_save_atum_order_items' ), 10, 3 );
		add_action( 'atum/api/rest_insert_order_note', array( $this, 'atum_order_add_note_api' ), 10, 2 );
		add_action( 'atum/api/rest_delete_order_note', array( $this, 'atum_order_remove_note_api' ), 10, 3 );
		add_action( 'atum/api/before_save_atum_order', array( $this, 'wc_api_before' ), 1, 3 );
		add_action( 'atum/api/rest_after_insert_' . Suppliers::POST_TYPE, array( $this, 'api_after_save_atum_supplier' ), 10, 3 );
		add_action( 'atum/api/rest_before_insert_' . Suppliers::POST_TYPE, array( $this, 'api_before_save_atum_supplier' ), 1, 2 );
		add_action( 'atum/api/rest_insert_' . Suppliers::POST_TYPE, array( $this, 'api_after_save_atum_supplier' ), 10, 3 );
		add_action( 'atum/api/rest_run_tool', array( $this, 'api_run_tool' ), 10, 2 );
		add_action( 'woocommerce_rest_set_order_item', array( $this, 'add_new_order_items' ), 10, 2 );
		add_action( 'woocommerce_reduce_order_stock', array( $this, 'wc_order_stock' ) );
		add_action( 'woocommerce_restore_order_stock', array( $this, 'wc_order_stock' ) );

	}

	/** ======================================
	 * STOCK CENTRAL LOGS METHODS
	 * ====================================== */

	/**
	 * Checks for previous values before saving stock central
	 *
	 * @param array $data
	 *
	 * @return mixed
	 * @since 0.3.1
	 */
	public function sc_before_update_product_data( $data ) {

		$previous_value = '';
		$pre_data       = array();

		foreach ( $data as $key => $product_data ) {
			if ( str_contains( $key, ':' ) && Addons::is_addon_active( 'multi_inventory' ) ) {

				list( $product_id, $inventory_id ) = explode( ':', $key );

				$main_inventory  = Inventory::get_product_main_inventory( $product_id );
				$inventory       = absint( $inventory_id ) == $main_inventory->id ? $main_inventory : AtumMIHelpers::get_inventory( $inventory_id ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
				$pre_data        = $inventory->get_all_data();
				$pre_data['inv'] = $inventory_id;

			}
			else {

				$product_id      = $key;
				$product         = AtumHelpers::get_atum_product( $product_id );
				$pre_data['inv'] = FALSE;

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				foreach ( $product_data as $meta_key => $meta_value ) {
					$avoid = FALSE;

					if ( '_custom' === substr( $meta_key, - 7 ) || '_currency' === substr( $meta_key, - 9 ) ) {
						$avoid = TRUE;
					}

					switch ( $meta_key ) {
						case 'undefined':
							$avoid = TRUE;
							break;
						case 'stock':
							$previous_value = $product->get_stock_quantity();
							break;
						case 'regular_price':
							$previous_value = $product->get_regular_price();
							break;
						case 'sale_price':
							$previous_value = $product->get_sale_price();
							break;
						case '_sale_price_dates_to':
							$previous_value = $product->get_date_on_sale_to();
							break;
						case '_sale_price_dates_from':
							$previous_value = $product->get_date_on_sale_from();
							break;
						case substr( AtumGlobals::PURCHASE_PRICE_KEY, 1 ):
							$previous_value = $product->get_purchase_price();
							break;
						case 'sku':
							$previous_value = $product->get_sku();
							break;
						case 'supplier_sku':
							$previous_value = $product->get_supplier_sku();
							break;
						default:
							if ( is_callable( array( $product, 'get_' . $meta_key ) ) ) {
								$previous_value = $product->{'get_' . $meta_key}();
							}
							else {
								$previous_value = __( 'Not found', ATUM_LOGS_TEXT_DOMAIN );
							}
							break;

					}
					if ( TRUE !== $avoid ) {
						$pre_data[ $meta_key ] = $previous_value;
					}
				}
			}

			$transient_key = AtumCache::get_transient_key( 'log_list_table_' . str_replace( ':', '_', $key ) );
			AtumCache::set_transient( $transient_key, $pre_data, MINUTE_IN_SECONDS, TRUE );
		}

		return $data;
	}

	/**
	 * Logs the 'save product' action
	 *
	 * @since 0.2.1
	 *
	 * @param array       $product_data
	 * @param \WC_Product $product
	 *
	 * @throws \Exception
	 */
	public function sc_log_update_product_data( $product_data, $product ) {

		$transient_key = AtumCache::get_transient_key( 'log_list_table_' . $product->get_id() );
		$old_values    = AtumCache::get_transient( $transient_key, TRUE );
		$product_metas = Helpers::get_product_metas();

		if ( empty( $old_values ) ) {
			return;
		}

		$inventory_exist = isset( $old_values['inv'] ) && $old_values['inv'] && Addons::is_addon_active( 'multi_inventory' );
		$is_variation    = 'variation' === $product->get_type();

		$entity = $inventory_exist ? __( 'Inventory', ATUM_LOGS_TEXT_DOMAIN ) : __( 'Product', ATUM_LOGS_TEXT_DOMAIN );
		$source = $inventory_exist ? LogModel::SRC_MI : LogModel::SRC_ATUM;
		$module = $inventory_exist ? LogModel::MOD_MI_STOCK_CENTRAL : LogModel::MOD_STOCK_CENTRAL;

		if ( Addons::is_addon_active( 'product_levels' ) ) {

			$is_bom = in_array( $product->get_type(), AtumPlGlobals::get_all_product_levels(), TRUE );

			if ( $is_bom ) {
				$source = $inventory_exist ? LogModel::SRC_MI : LogModel::SRC_PL;
				$module = $inventory_exist ? LogModel::MOD_MI_MAN_CENTRAL : LogModel::MOD_MAN_CENTRAL;
				$entity = $inventory_exist ? __( 'BOM Inventory', ATUM_LOGS_TEXT_DOMAIN ) : __( 'BOM Product', ATUM_LOGS_TEXT_DOMAIN );
			}
		}

		foreach ( $product_data as $key => $data ) {

			if ( str_contains( $key, ':' ) ) {
				$product_data = $data;
			}

		}

		foreach ( $product_data as $meta_key => $meta_value ) {
			$field_name = '';
			$avoid      = FALSE;

			if ( isset( $product_metas[ $meta_key ] ) ) {

				if ( 'Product Levels' === $product_metas[ $meta_key ] ) {
					$source = LogModel::SRC_PL;
					if ( in_array( $module, [ LogModel::MOD_STOCK_CENTRAL, LogModel::MOD_MI_STOCK_CENTRAL ] ) ) {
						$module = LogModel::MOD_PL_STOCK_CENTRAL;
					}
					elseif ( LogModel::MOD_MI_MAN_CENTRAL === $module ) {
						$module = LogModel::MOD_MAN_CENTRAL;
					}
				}
				elseif ( 'Multi-Inventory' === $product_metas[ $meta_key ] ) {
					$source = LogModel::SRC_MI;
					if ( in_array( $module, [ LogModel::MOD_STOCK_CENTRAL, LogModel::MOD_PL_STOCK_CENTRAL ] ) ) {
						$module = LogModel::MOD_MI_STOCK_CENTRAL;
					}
					elseif ( LogModel::MOD_MAN_CENTRAL === $module ) {
						$module = LogModel::MOD_MI_MAN_CENTRAL;
					}
				}
			}

			$log_data = [
				'source' => $source,
				'module' => $module,
				'entry'  => LogEntry::ACTION_SC_EDIT,
			];

			$meta_key = esc_attr( $meta_key );

			if ( '_custom' === substr( $meta_key, - 7 ) || '_currency' === substr( $meta_key, - 9 ) ) {
				$avoid = TRUE;
			}

			switch ( $meta_key ) {
				case 'undefined':
					$avoid = TRUE;
					break;
				case 'stock':
					$field_name = __( 'stock amount', ATUM_LOGS_TEXT_DOMAIN );
					if ( $old_values['inv'] ) {
						$meta_key = 'stock_quantity';
					}
					break;
				case 'regular_price':
					$field_name = __( 'regular price', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case 'sale_price':
					$field_name = __( 'sale price', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case '_sale_price_dates_to':
					$field_name = __( 'ending date', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case '_sale_price_dates_from':
					$field_name = __( 'starting date', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case substr( AtumGlobals::PURCHASE_PRICE_KEY, 1 ):
					$field_name = __( 'purchase price', ATUM_LOGS_TEXT_DOMAIN );
					break;
				default:
					$field_name = $meta_key;
					break;
			}
			$equals = ( is_numeric( $meta_value ) && isset( $old_values[ $meta_key ] ) && floatval( $meta_value ) === floatval( $old_values[ $meta_key ] ) );

			// Avoid save log without changes.
			if ( ! isset( $old_values[ $meta_key ] ) || FALSE === $old_values[ $meta_key ] || $old_values[ $meta_key ] === $meta_value || $equals ) {
				$avoid = TRUE;
			}

			if ( TRUE !== $avoid ) {

				$log_data['data'] = [
					'product_id'   => $product->get_id(),
					'product_name' => $product->get_name(),
					'field_name'   => $field_name,
					'entity'       => $entity,
					'field'        => $meta_key,
					'old_value'    => $old_values[ $meta_key ],
					'new_value'    => $meta_value,
				];
				if ( $inventory_exist ) {
					$log_data['data']['inventory'] = [
						'id'   => $old_values['inv'],
						'name' => $old_values['name'],
					];
				}
				if ( $is_variation ) {
					$log_data['data']['product_parent'] = $product->get_parent_id();
				}
				LogModel::maybe_save_log( $log_data );
			}
			AtumCache::delete_transients( $transient_key );

		}

	}

	/**
	 * Checks for previous product locations
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id
	 */
	public function sc_before_locations( $product_id ) {

		$locations = wc_get_product_terms( $product_id, AtumGlobals::PRODUCT_LOCATION_TAXONOMY );

		if ( empty( $locations ) ) {
			return;
		}

		$data = wp_list_pluck( $locations, 'term_id' );

		$transient_key = AtumCache::get_transient_key( 'log_sc_product_locations_' . $product_id );
		AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Logs products locations changes in Stock Central
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id
	 *
	 * @throws \Exception
	 */
	public function sc_after_locations( $product_id ) {

		$product       = AtumHelpers::get_atum_product( $product_id );
		$is_variation  = 'variation' === $product->get_type();
		$locations     = wc_get_product_terms( $product_id, AtumGlobals::PRODUCT_LOCATION_TAXONOMY );
		$new_locations = wp_list_pluck( $locations, 'term_id' );
		$transient_key = AtumCache::get_transient_key( 'log_sc_product_locations_' . $product_id );
		$old_locations = AtumCache::get_transient( $transient_key, TRUE );
		$added         = [];
		$removed       = [];

		if ( ! empty( $new_locations ) ) {
			foreach ( $new_locations as $new_location ) {
				if ( ! empty( $old_locations ) && FALSE !== in_array( $new_location, $old_locations ) ) {
					continue;
				}

				$loc_term = get_term( $new_location );
				$added[]  = [
					'id'   => $new_location,
					'name' => $loc_term->name,
				];
			}
		}

		if ( ! empty( $old_locations ) ) {
			foreach ( $old_locations as $old_location ) {
				if ( FALSE === in_array( $old_location, $new_locations ) ) {
					$loc_term  = get_term( $old_location );
					$removed[] = [
						'id'   => $old_location,
						'name' => $loc_term->name,
					];
				}
			}
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_STOCK_CENTRAL,
		];

		$entity = __( 'Product', ATUM_LOGS_TEXT_DOMAIN );

		if ( Addons::is_addon_active( 'product_levels' ) && ! empty( BOMModel::get_associated_products( $product->get_id() ) ) ) {
			$entity             = __( 'BOM Product', ATUM_LOGS_TEXT_DOMAIN );
			$log_data['source'] = LogModel::SRC_PL;
			$log_data['module'] = LogModel::MOD_MAN_CENTRAL;
		}

		if ( ! empty( $added ) ) {

			$log_data['data'] = [
				'product_id'   => $product_id,
				'product_name' => $product->get_name(),
				'entity'       => $entity,
				'field'        => 'locations',
				'added'        => $added,
			];
			if ( $is_variation ) {
				$log_data['data']['product_parent'] = $product->get_parent_id();
			}
			$log_data['entry'] = LogEntry::ACTION_SC_SET_LOC;
			LogModel::maybe_save_log( $log_data );
		}

		if ( ! empty( $removed ) ) {
			$log_data['data'] = [
				'product_id'   => $product_id,
				'product_name' => $product->get_name(),
				'entity'       => $entity,
				'field'        => 'locations',
				'removed'      => $removed,
			];
			if ( $is_variation ) {
				$log_data['data']['product_parent'] = $product->get_parent_id();
			}
			$log_data['entry'] = LogEntry::ACTION_SC_DEL_LOC;
			LogModel::maybe_save_log( $log_data );
		}

		AtumCache::delete_transients( $transient_key );

	}

	/**
	 * Logs the bulk actions in Stock Central
	 *
	 * @since 0.3.1
	 *
	 * @param array $args
	 *
	 * @throws \Exception
	 */
	public function sc_apply_bulk_action( $args ) {

		/**
		 * Variable definition
		 *
		 * @var string $bulk_action
		 * @var int[]  $ids
		 * @var bool   $executed
		 * @var array  $extra_data
		 */
		extract( $args );

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_STOCK_CENTRAL,
		];

		$product_list = [];

		foreach ( $ids as $id ) {

			$product = wc_get_product( $id );

			$product_array = [
				'id'   => $id,
				'name' => $product && $product->exists() ? $product->get_name() : '#' . $id,
			];

			if ( $product && $product->exists() && 'variation' === $product->get_type() ) {
				$product_array['parent'] = $product->get_parent_id();
			}

			$product_list[] = $product_array;

		}

		$log_data['data'] = [
			'field'    => $_POST['bulk_action'],
			'products' => $product_list,
		];

		$entity = __( 'Products', ATUM_LOGS_TEXT_DOMAIN );

		foreach ( $ids as $id ) {

			if ( Addons::is_addon_active( 'product_levels' ) && ! empty( BOMModel::get_associated_products( $id ) ) ) {
				$entity             = __( 'BOM Products', ATUM_LOGS_TEXT_DOMAIN );
				$log_data['source'] = LogModel::SRC_PL;
				$log_data['module'] = LogModel::MOD_MAN_CENTRAL;
				break;
			}

		}

		$log_data['data']['entity'] = $entity;

		switch ( $bulk_action ) {

			case 'uncontrol_stock':
				$log_data['entry'] = LogEntry::ACTION_SC_UNCONTROL_STOCK;
				break;

			case 'control_stock':
				$log_data['entry'] = LogEntry::ACTION_SC_CONTROL_STOCK;
				break;

			case 'unmanage_stock':
				$log_data['entry'] = LogEntry::ACTION_SC_UNMANAGE_STOCK;
				break;

			case 'manage_stock':
				$log_data['entry'] = LogEntry::ACTION_SC_MANAGE_STOCK;
				break;

			default:
				return;
		}

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs the data exportation in Stock Central
	 *
	 * @since 0.3.1
	 *
	 * @param array $data
	 *
	 * @throws \Exception
	 */
	public function sc_export_data( $data ) {

		if ( 'atum-logs' === $data['page'] ) {
			return;
		}

		$log_data = [
			'source' => 'atum-manufacturing-central' === $data['page'] ? LogModel::SRC_PL : LogModel::SRC_ATUM,
			'module' => 'atum-manufacturing-central' === $data['page'] ? LogModel::MOD_MAN_CENTRAL : LogModel::MOD_STOCK_CENTRAL,
			'data'   => $data,
			'entry'  => 'atum-manufacturing-central' === $data['page'] ? LogEntry::ACTION_MC_EXPORT : LogEntry::ACTION_SC_EXPORT,
		];

		LogModel::maybe_save_log( $log_data );
	}


	/** ======================================
	 * PURCHASE ORDERS LOGS METHODS
	 * ====================================== */

	/**
	 * Before saving PO data.
	 *
	 * @since 1.2.0
	 *
	 * @param int   $post_ID
	 * @param mixed $data
	 */
	public function po_before_save( $post_ID, $data ) {

		$post = get_post( $post_ID );

		if ( PurchaseOrders::POST_TYPE !== $post->post_type ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$atum_order    = AtumHelpers::get_atum_order_model( $post_ID, FALSE );
		$order_status  = $atum_order->get_status();
		$transient_key = AtumCache::get_transient_key( 'log_' . PurchaseOrders::POST_TYPE . '_status_' . $post_ID );
		AtumCache::set_transient( $transient_key, $order_status, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Logs Purchase Orders creation
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function po_save_order() {

		$id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : NULL;

		if ( is_null( $id ) || PurchaseOrders::POST_TYPE !== $_POST['post_type'] ) {
			return;
		}

		// Add new PO.
		if ( isset( $_POST['auto_draft'], $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'] ) {

			$data = [
				'order_id'      => $id,
				'order_name'    => 'PO#' . $id,
			];

			foreach ( apply_filters( 'atum/logs/purchase_orders_fields', Helpers::get_purchase_order_metas() ) as $meta ) {
				if ( isset ( $_POST[ $meta ] ) && $_POST[ $meta ] ) {

					$value = wc_clean( $_POST[ $meta ] );

					if ( 'supplier' === $meta ) {
						$supplier_data = new Supplier( absint( $value ) );
						if ( ! empty( $supplier_data ) ) {
							$value = [
								'id'   => $supplier_data->id,
								'name' => $supplier_data->name,
							];
						}
					}
					$data[ $meta ] = $value;
				}
			}

			$log_data = [
				'source' => LogModel::SRC_ATUM,
				'module' => LogModel::MOD_PURCHASE_ORDERS,
				'data'   => $data,
				'entry'  => LogEntry::ACTION_PO_CREATE,
			];
			LogModel::maybe_save_log( $log_data );
		}
		// Edit PO.
		else {

			$post  = get_post( $id );
			$metas = apply_filters( 'atum/logs/purchase_orders_fields', Helpers::get_purchase_order_metas() );

			foreach ( $metas as $meta ) {

				switch ( $meta ) {
					case 'date':
						$old_value = $post->post_date;

						if ( ! isset( $_POST[ $meta ] ) ) {
							continue 2;
						}

						$new_value = $_POST[ $meta ];

						if ( isset( $_POST['date_hour'] ) ) {
							$new_value .= ' ' . $_POST['date_hour'] . ':' . $_POST['date_minute'];
						}

						if ( count( explode( ':', $new_value ) ) !== count( explode( ':', $old_value ) ) ) {
							$new_value .= ':00';
						}

						break;
					case 'date_expected':
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );

						if ( ! isset( $_POST[ $meta ] ) ) {
							continue 2;
						}

						$new_value = $_POST[ $meta ];

						if ( ! is_null( $new_value ) && isset( $_POST['date_expected_hour'] ) ) {
							$new_value .= ' ' . $_POST['date_expected_hour'] . ':' . $_POST['date_expected_minute'];
						}

						if ( count( explode( ':', $new_value ) ) !== count( explode( ':', $old_value ) ) ) {
							$new_value .= ':00';
						}

						break;
					case 'delivery_date':
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );

						if ( ! isset( $_POST[ $meta ] ) ) {
							continue 2;
						}

						$new_value = ! empty( $_POST[ $meta ] ) ? date_i18n( 'Y-m-d H:i:s', strtotime( $_POST[ $meta ] ) ) : NULL;
						break;
					case 'status':
						$transient_key = AtumCache::get_transient_key( 'log_' . PurchaseOrders::POST_TYPE . '_status_' . $id );
						$old_value = AtumCache::get_transient( $transient_key, TRUE );
						$new_value = $post->post_status;
						AtumCache::delete_transients( $transient_key );
						break;
					case 'currency':
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );
						$meta_name = apply_filters( 'atum/logs/purchase_orders_field_name', $meta );
						$new_value = $_POST[ $meta_name ] ?? NULL;
						break;
					case 'supplier':
					case 'requisitioner':
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );
						$new_value = $_POST[ $meta ] ?? NULL;
						break;
					case 'description':
						$old_value = $post->post_content;
						$new_value = $_POST[ $meta ] ?? NULL;
						break;
					case 'pdf_template':
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );
						$new_value = $_POST[ $meta ] ?? NULL;
						if ( ! $old_value ) {
							$old_value = apply_filters( 'atum/logs/purchase_orders_field_default_value', 'default', $meta );
						}
						break;
					default:
						$old_value = get_post_meta( $id, '_' . $meta, TRUE );
						$new_value = $_POST[ $meta ] ?? NULL;
						//$old_value = '';
						//$new_value = '';
						break;
				}

				Helpers::maybe_log_purchase_order_detail( $id, $meta, $old_value, $new_value );

			}

		}
	}

	/**
	 * Log Atum Order status as received
	 *
	 * @since 0.5.1
	 *
	 * @param AtumOrderModel $atum_order
	 * @param string         $status
	 *
	 * @throws \Exception
	 */
	public function po_mark_atum_order( $atum_order, $status ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {
			$module = LogModel::MOD_INVENTORY_LOGS;
			$name   = 'Log#' . $atum_order->get_id();
		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {
			$module = LogModel::MOD_PURCHASE_ORDERS;
			$name   = 'PO#' . $atum_order->get_id();
		}
		else {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => $module,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => $name,
				'field'      => 'status',
				'old_value'  => $atum_order->get_status(),
				'new_value'  => $status,
			],
			'entry'  => InventoryLogs::POST_TYPE === $atum_order->get_post_type() ? LogEntry::ACTION_IL_EDIT_STATUS : LogEntry::ACTION_PO_EDIT_STATUS,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs adding a product to an Atum order
	 *
	 * @since 0.3.1
	 *
	 * @param int                  $item_id
	 * @param AtumOrderItemProduct $item
	 * @param AtumOrderModel       $atum_order
	 *
	 * @throws \Exception
	 */
	public function po_add_order_item( $item_id, $item, $atum_order ) {

		$order_id = $atum_order->get_id();

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {

			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $order_id;
			$entry = LogEntry::ACTION_IL_ADD_ITEM;

		}
		else {

			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $order_id;
			$entry = LogEntry::ACTION_PO_ADD_ITEM;

		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => $mod,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => $name,
				'item_id'    => $item_id,
			],
			'entry'  => $entry,
		];

		$product = $item->get_product();

		if ( $product instanceof \WC_Product && $product->exists() ) {

			$product_data = [
				'id'   => $product->get_id(),
				'name' => $product->exists() ? $product->get_name() : '#' . $product->get_id(),
			];
			if ( 'variation' === $product->get_type() ) {
				$product_data['parent'] = $product->get_parent_id();
			}

			$log_data['data']['product'] = $product_data;
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs adding a shipping cost or fee to an Atum order
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel       $atum_order
	 * @param AtumOrderItemProduct $item
	 *
	 * @throws \Exception
	 */
	public function po_add_order_fee_shipping( $atum_order, $item ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_IL_ADD_FEE_SHIP;
		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_PO_ADD_FEE_SHIP;
		}
		else {
			return;
		}

		$field = doing_action( 'atum/ajax/atum_order/fee_added' ) ? __( 'fee', ATUM_LOGS_TEXT_DOMAIN ) : __( 'shipping cost', ATUM_LOGS_TEXT_DOMAIN );

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => $mod,
			'entry'  => $entry,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => $name,
				'field'      => $field,
				'item_id'    => $item->get_id(),
				'item_name'  => $item->get_name(),
			],
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs adding a tax to an Atum order
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel $atum_order
	 * @param int            $rate_id
	 *
	 * @throws \Exception
	 */
	public function po_add_order_tax( $atum_order, $rate_id ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_PO_ADD_TAX;
		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_IL_ADD_TAX;
		}
		else {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => $mod,
			'entry'  => $entry,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => $name,
				'tax_id'     => $rate_id,
				'tax_name'   => \WC_Tax::get_rate_label( $rate_id ) ?: '#' . $rate_id,
			],
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs details changes in order items
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel $atum_order
	 * @param array          $items
	 *
	 * @throws \Exception
	 */
	public function po_save_order_items( $atum_order, $items ) {

		$order_items = $atum_order->get_items( [ 'line_item', 'tax', 'shipping', 'fee' ] );
		$log_data    = [];
		$item_data   = [];

		foreach ( $order_items as $order_item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $order_item
			 */
			$item_data[ $order_item->get_id() ] = $order_item->get_data();
		}

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {

			$mod  = LogModel::MOD_INVENTORY_LOGS;
			$name = 'Log#' . $atum_order->get_id();

		}
		else {

			$mod  = LogModel::MOD_PURCHASE_ORDERS;
			$name = 'PO#' . $atum_order->get_id();

		}

		foreach ( $items as $k => $idata ) {
			if ( empty( $idata ) ) {
				continue;
			}

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $obj
			 */
			switch ( $k ) {
				case 'meta_key':
					foreach ( $idata as $i => $imetas ) {
						$obj = $order_items[ $i ];
						foreach ( $imetas as $imeta => $ifield ) {
							if ( '' === $items['meta_value'][ $i ][ $imeta ] && ! strstr( $imeta, 'new-' ) ) {
								$old_metas       = $obj->get_formatted_meta_data();
								$old_meta        = $old_metas[ $imeta ];
								$log_delete_meta = [
									'source' => LogModel::SRC_ATUM,
									'module' => $mod,
									'data'   => [
										'order_id'   => $atum_order->get_id(),
										'order_name' => $name,
										'item_id'    => $i,
										'item_name'  => $obj->get_name(),
										'meta_field' => $old_meta->key,
										'meta_value' => $old_meta->value,
									],
									'entry'  => ( LogModel::MOD_PURCHASE_ORDERS === $mod ) ? LogEntry::ACTION_PO_DEL_ITEM_META : LogEntry::ACTION_IL_DEL_ITEM_META,
								];
								LogModel::maybe_save_log( $log_delete_meta );
								continue;
							}

							if ( $items['meta_value'][ $i ][ $imeta ] === $obj->get_meta( $ifield ) ) {
								continue;
							}

							$log_data[] = [
								'order_id'      => $atum_order->get_id(),
								'order_name'    => $name,
								'item_id'       => $obj->get_id(),
								'item_name'     => $obj->get_name(),
								'field'         => 'meta',
								'field_changed' => $ifield,
								'old_value'     => $obj->get_meta( $ifield ),
								'new_value'     => $items['meta_value'][ $i ][ $imeta ],
							];
						}
					}
					break;
				case 'shipping_method':
					foreach ( $idata as $i => $value ) {
						$obj  = $order_items[ $i ];
						$data = $obj->get_data();
						if ( $data['method_id'] === $value ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'Shipping Method', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $data['method_id'],
							'new_value'     => $value,
						];
					}
					break;
				case 'shipping_method_title':
					foreach ( $idata as $i => $value ) {
						$obj  = $order_items[ $i ];
						$data = $obj->get_data();
						if ( $data['method_title'] === $value ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'Shipping Method Title', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $data['method_title'],
							'new_value'     => $value,
						];
					}
					break;
				case 'shipping_cost':
					foreach ( $idata as $i => $value ) {
						$obj  = $order_items[ $i ];
						$data = $obj->get_data();
						if ( str_contains( $value, ',' ) ) {
							$value = str_replace( ',', '.', $value );
						}
						if ( floatval( $data['total'] ) === floatval( $value ) ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'Shipping Cost', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $data['total'],
							'new_value'     => $value,
						];
					}
					break;
				case 'item_tax_class':
				case 'atum_order_item_tax_class':
					foreach ( $idata as $i => $value ) {
						$obj = $order_items[ $i ];
						if ( $value === $obj->get_tax_class() ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'Order Tax Class', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $obj->get_tax_class(),
							'new_value'     => $value,
						];
					}
					break;
				case 'atum_order_item_qty':
					foreach ( $idata as $item_id => $value ) {
						if ( str_contains( $value, ',' ) ) {
							$value = floatval( str_replace( ',', '.', $value ) );
						}
						$obj    = $order_items[ $item_id ];
						$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $obj->get_quantity() ) );
						if ( floatval( $value ) === $obj->get_quantity() || $equals ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'quantity', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $obj->get_quantity(),
							'new_value'     => $value,
						];
					}
					break;
				case 'line_subtotal':
					foreach ( $idata as $item_id => $value ) {
						if ( str_contains( $value, ',' ) ) {
							$value = floatval( str_replace( ',', '.', $value ) );
						}
						$obj    = $order_items[ $item_id ];
						$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $item_data[ $item_id ]['subtotal'] ) );
						if ( $value === $item_data[ $item_id ]['subtotal'] || $equals ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $item_id,
							'item_name'     => $obj->get_name(),
							'field'         => __( 'price', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $item_data[ $item_id ]['subtotal'],
							'new_value'     => $value,
						];
					}
					break;
				case 'line_tax':
				case 'shipping_taxes':
					foreach ( $idata as $item_id => $taxes ) {
						$obj        = $order_items[ $item_id ];
						$taxes_data = [];
						foreach ( $taxes as $t => $value ) {
							if ( str_contains( $value, ',' ) ) {
								$value = floatval( str_replace( ',', '.', $value ) );
							}
							$old_tax = $item_data[ $item_id ]['taxes']['total'][ $t ];
							if ( '' === $value && is_null( $old_tax ) ) {
								continue;
							}
							$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $old_tax ) );
							if ( $value === $old_tax || $equals ) {
								continue;
							}
							$taxes_data[] = [
								'id'        => $t,
								'tax'       => \WC_Tax::get_rate_label( $t ),
								'old_value' => $old_tax,
								'new_value' => $value,
							];
						}
						if ( ! empty( $taxes_data ) ) {
							$log_data[] = [
								'order_id'      => $atum_order->get_id(),
								'order_name'    => $name,
								'item_id'       => $obj->get_id(),
								'item_name'     => $obj->get_name(),
								'field'         => __( 'tax', ATUM_LOGS_TEXT_DOMAIN ),
								'field_changed' => $k,
								'values'        => $taxes_data,
							];
						}
					}
					break;
				case 'line_total':
					foreach ( $idata as $item_id => $value ) {
						if ( str_contains( $value, ',' ) ) {
							$value = floatval( str_replace( ',', '.', $value ) );
						}
						$obj    = $order_items[ $item_id ];
						$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $item_data[ $item_id ]['total'] ) );
						if ( $value === $item_data[ $item_id ]['total'] || $equals ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'total', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $item_data[ $item_id ]['total'],
							'new_value'     => $value,
						];
					}
					break;
				case 'atum_order_item_name':
					foreach ( $idata as $i => $value ) {
						$obj = $order_items[ $i ];
						if ( $value === $obj->get_name() ) {
							continue;
						}
						$log_data[] = [
							'order_id'      => $atum_order->get_id(),
							'order_name'    => $name,
							'item_id'       => $obj->get_id(),
							'item_name'     => $obj->get_name(),
							'field'         => __( 'Item Name', ATUM_LOGS_TEXT_DOMAIN ),
							'field_changed' => $k,
							'old_value'     => $obj->get_name(),
							'new_value'     => $value,
						];
					}
					break;
			}
		}

		if ( ! empty( $log_data ) ) {

			foreach ( $log_data as $log ) {

				if ( 'meta' === $log['field'] ) {
					$entry = ( LogModel::MOD_PURCHASE_ORDERS === $mod ) ? LogEntry::ACTION_PO_ITEM_META : LogEntry::ACTION_IL_ITEM_META;
				}
				else {
					$entry = ( LogModel::MOD_PURCHASE_ORDERS === $mod ) ? LogEntry::ACTION_PO_ITEM_CHANGED : LogEntry::ACTION_IL_ITEM_CHANGED;
				}
				$log_processed_data = [
					'module' => $mod,
					'source' => LogModel::SRC_ATUM,
					'data'   => $log,
					'entry'  => $entry,
				];
				LogModel::maybe_save_log( $log_processed_data );
			}

		}

		// Log for totals.
		$total = [
			'total'  => 0,
			'ship'   => 0,
			'tax'    => 0,
			'refund' => 0,
		];
		if ( ! empty( $items['line_total'] ) ) {
			foreach ( $items['line_total'] as $val ) {
				$total['total'] += floatval( str_replace( ',', '.', $val ) );
			}
		}
		if ( ! empty( $items['line_tax'] ) ) {
			foreach ( $items['line_tax'] as $taxes ) {
				foreach ( $taxes as $tax ) {
					$total['tax']   += floatval( str_replace( ',', '.', $tax ) );
					$total['total'] += floatval( str_replace( ',', '.', $tax ) );
				}
			}
		}
		if ( ! empty( $items['shipping_taxes'] ) ) {
			foreach ( $items['shipping_taxes'] as $taxes ) {
				foreach ( $taxes as $tax ) {
					$total['tax']   += floatval( str_replace( ',', '.', $tax ) );
					$total['total'] += floatval( str_replace( ',', '.', $tax ) );
				}
			}
		}
		if ( ! empty( $items['shipping_cost'] ) ) {
			foreach ( $items['shipping_cost'] as $ship ) {
				$total['ship']  += floatval( str_replace( ',', '.', $ship ) );
				$total['total'] += floatval( str_replace( ',', '.', $ship ) );
			}
		}
		if ( ! empty( $items['refund_line_tax'] ) ) {
			foreach ( $items['refund_line_tax'] as $refunds ) {
				foreach ( $refunds as $refund ) {
					$total['refund'] += floatval( str_replace( ',', '.', $refund ) );
					$total['total']  -= floatval( str_replace( ',', '.', $refund ) );
				}
			}
		}

		$change = [];

		foreach ( $total as $index => $value ) {
			switch ( $index ) {
				case 'tax':
					$meta = '_total_tax';
					break;
				case 'ship':
					$meta = '_shipping_total';
					break;
				case 'refund':
					$meta = '_discount_total';
					break;
				default:
					$meta = '_total';
					break;
			}
			$old_total = get_post_meta( $atum_order->get_id(), $meta, TRUE );

			if ( wc_price( $value ) !== wc_price( $old_total ) ) {
				$change['old_values'][ $meta ] = $old_total;
				$change['new_values'][ $meta ] = $value;
			}

		}

		if ( ! empty( $change ) ) {
			$log_data = [
				'module' => $mod,
				'source' => LogModel::SRC_ATUM,
				'entry'  => ( LogModel::MOD_PURCHASE_ORDERS === $mod ) ? LogEntry::ACTION_PO_EDIT_TOTALS : LogEntry::ACTION_IL_EDIT_TOTALS,
				'data'   => [
					'order_id'   => $atum_order->get_id(),
					'order_name' => $name,
					'totals'     => $change,
				],
			];
			LogModel::maybe_save_log( $log_data );
		}

	}

	/**
	 * Logs delete order items / taxes
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel $atum_order
	 * @param array          $item_ids
	 *
	 * @throws \Exception
	 */
	public function po_remove_order_item( $atum_order, $item_ids ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_IL_DEL_ORDER_ITEM;
		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {
			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_PO_DEL_ORDER_ITEM;
		}
		else {
			return;
		}

		if ( ! is_array( $item_ids ) && is_numeric( $item_ids ) ) {
			$item_ids = array( $item_ids );
		}

		foreach ( $item_ids as $item_id ) {

			$order_items = $atum_order->get_items( [ 'line_item', 'tax', 'shipping', 'fee' ] );

			$item = $order_items[ $item_id ];

			if ( ! empty( $item ) ) {
				$log_data = [
					'module' => $mod,
					'source' => LogModel::SRC_ATUM,
					'entry'  => $entry,
					'data'   => [
						'order_id'   => $atum_order->get_id(),
						'order_name' => $name,
						'item_id'    => $item_id,
						'item_name'  => $item->get_name(),
						'field'      => 'removed_item',
						'item_data'  => $item->get_data(),
					],
				];
				LogModel::maybe_save_log( $log_data );
			}
		}
	}

	/**
	 * Logs adding meta to Purchase Orders
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function po_add_meta() {

		check_ajax_referer( 'add-meta', '_ajax_nonce-add-meta' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$post = get_post( (int) $_POST['post_id'] );

		if ( ! $post ) {
			return;
		}

		if ( PurchaseOrders::POST_TYPE === $post->post_type ) {
			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'PO';
			$entry = LogEntry::ACTION_PO_ADD_META;
		}
		elseif ( InventoryLogs::POST_TYPE === $post->post_type ) {
			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'Log';
			$entry = LogEntry::ACTION_IL_ADD_META;
		}
		else {
			return;
		}

		$data = [
			'order_id'   => $post->ID,
			'order_name' => $name . '#' . $post->ID,
			'meta_field' => $_POST['metakeyselect'],
			'meta_value' => $_POST['metavalue'],
		];

		$log_data = [
			'module' => $mod,
			'source' => LogModel::SRC_ATUM,
			'entry'  => $entry,
			'data'   => $data,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs purchase price changes in order items
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel       $atum_order
	 * @param AtumOrderItemProduct $atum_order_item
	 * @param float                $purchase_price
	 *
	 * @throws \Exception
	 */
	public function po_change_order_item_purchase_price( $atum_order, $atum_order_item, $purchase_price ) {

		$product_id = $atum_order_item->get_variation_id() ?: $atum_order_item->get_product_id();
		$product    = AtumHelpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( $product->get_purchase_price() === $purchase_price ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_PURCHASE_ORDERS,
			'entry'  => LogEntry::ACTION_PO_PURCHASE_PRICE,
			'data'   => [
				'order_id'      => $atum_order->get_id(),
				'order_name'    => 'PO#' . $atum_order->get_id(),
				'order_item_id' => $atum_order_item->get_id(),
				'product_id'    => $atum_order_item->get_product_id(),
				'product_name'  => $product->get_name(),
				'field'         => AtumGlobals::PURCHASE_PRICE_KEY,
				'old_value'     => $product->get_purchase_price(),
				'new_value'     => $purchase_price,
			],
		];
		if ( 'variation' === $product->get_type() ) {
			$log_data['data']['product_parent'] = $product->get_parent_id();
		}
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs add note to purchase order
	 *
	 * @since 0.3.1
	 *
	 * @param AtumOrderModel $atum_order
	 * @param int            $comment_id
	 *
	 * @throws \Exception
	 */
	public function atum_order_add_note( $atum_order, $comment_id ) {

		if ( InventoryLogs::POST_TYPE === $atum_order->get_post_type() ) {

			$mod   = LogModel::MOD_INVENTORY_LOGS;
			$name  = 'Log#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_IL_ADD_NOTE;

		}
		elseif ( PurchaseOrders::POST_TYPE === $atum_order->get_post_type() ) {

			$mod   = LogModel::MOD_PURCHASE_ORDERS;
			$name  = 'PO#' . $atum_order->get_id();
			$entry = LogEntry::ACTION_PO_ADD_NOTE;

		} else {

			return;

		}

		$comment = get_comment( $comment_id );

		$log_data = [
			'module' => $mod,
			'source' => LogModel::SRC_ATUM,
			'entry'  => $entry,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => $name,
				'note'       => [
					'comment_ID'      => $comment_id,
					'comment_content' => $comment->comment_content,
					'comment_author'  => $comment->comment_author,
				],
			],
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Log for add note to atum order
	 *
	 * @since 1.0.8
	 *
	 * @param \WP_Comment      $note    New order note object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @throws \Exception
	 */
	public function atum_order_add_note_api( $note, $request ) {

		$atum_order = AtumHelpers::get_atum_order_model( (int) $request['order_id'], FALSE );

		$this->atum_order_add_note( $atum_order, $note->comment_ID );
	}

	/**
	 * Logs remove note from purchase order / inventory log
	 *
	 * @since 1.0.0
	 *
	 * @param int $note_id
	 */
	public function atum_order_remove_note( $note_id ) {

		$comment = get_comment( $note_id );

		$atum_order = AtumHelpers::get_atum_order_model( $comment->comment_post_ID, FALSE );

		if ( ! is_wp_error( $atum_order ) ) {

			Helpers::remove_atum_order_note( $atum_order, $comment );
		}
	}

	/**
	 * Log for order note is deleted or trashed via the REST API
	 *
	 * @since 1.0.8
	 *
	 * @param \WP_Comment       $note     The deleted or trashed order note.
	 * @param \WP_REST_Response $response The response data.
	 * @param \WP_REST_Request  $request  The request sent to the API.
	 */
	public function atum_order_remove_note_api( $note, $response, $request ) {

		$atum_order = AtumHelpers::get_atum_order_model( (int) $request['order_id'], FALSE );

		Helpers::remove_atum_order_note( $atum_order, $note );
	}

	/**
	 * Logs PDF generation of a purchase order
	 *
	 * @since 0.3.1
	 *
	 * @param int $atum_order_id
	 *
	 * @throws \Exception
	 */
	public function po_generate_pdf( $atum_order_id ) {

		$log_data = [
			'module' => LogModel::MOD_PURCHASE_ORDERS,
			'source' => LogModel::SRC_ATUM,
			'entry'  => LogEntry::ACTION_PO_GENERATE_PDF,
			'data'   => [
				'order_id'   => $atum_order_id,
				'order_name' => 'PO#' . $atum_order_id,
				'action'     => 'print_pdf',
			],
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Checks for previous value before saving stock levels
	 *
	 * @param int       $order_id
	 * @param \WC_Order $order
	 *
	 * @since 0.3.1
	 */
	public function po_register_stock_levels_received( $order_id, $order ) {

		Helpers::atum_orders_register_stock_levels( $order );
	}

	/**
	 * Checks for previous value before saving stock levels
	 *
	 * @since 0.3.1
	 *
	 * @param int       $order_id
	 * @param string    $old_status
	 * @param string    $new_status
	 * @param \WC_Order $order
	 *
	 * @throws \Exception
	 */
	public function po_register_stock_levels_changed( $order_id, $old_status, $new_status, $order ) {

		if ( $old_status !== $new_status ) {

			switch ( $order->get_post_type() ) {
				case PurchaseOrders::POST_TYPE:
					$entry  = LogEntry::ACTION_PO_EDIT_STATUS;
					$module = LogModel::MOD_PURCHASE_ORDERS;
					$name   = 'PO#' . $order->get_id();
					break;
				case InventoryLogs::POST_TYPE:
					$entry  = LogEntry::ACTION_IL_EDIT_STATUS;
					$module = LogModel::MOD_INVENTORY_LOGS;
					$name   = 'Log#' . $order->get_id();
					break;
				default:
					return;
			}

			$log_data = [
				'source' => LogModel::SRC_ATUM,
				'module' => $module,
				'data'   => [
					'order_id'   => $order->get_id(),
					'order_name' => $name,
					'field'      => 'status',
					'old_value'  => $old_status,
					'new_value'  => $new_status,
				],
				'entry'  => $entry,
			];
			LogModel::maybe_save_log( $log_data );
		}

		if ( apply_filters( 'atum/action_logs/check_purchase_order_stock_levels', PurchaseOrders::FINISHED === $new_status, $order ) || $order_id !== $order->get_id() ) {
			return;
		}

		// Any status !== finished is like pending, so reduce stock.
		if ( $order && PurchaseOrders::FINISHED === $old_status && $old_status !== $new_status ) {
			Helpers::atum_orders_register_stock_levels( $order );
		}

	}

	/**
	 * Logs stock levels
	 *
	 * @since 0.3.1
	 *
	 * @param PurchaseOrder $order
	 *
	 * @throws \Exception
	 */
	public function po_changed_stock_levels( $order ) {

		$atum_order_items = $order->get_items();

		if ( ! empty( $atum_order_items ) ) {
			foreach ( $atum_order_items as $item_id => $atum_order_item ) {

				$product = $atum_order_item->get_product();

				/**
				 * Variable definition
				 *
				 * @var \WC_Product $product
				 */

				if ( $product instanceof \WC_Product && $product->exists() && $product->managing_stock() ) {

					$new_stock = $product->get_stock_quantity();

					$transient_key = AtumCache::get_transient_key( 'log_stock_level_' . $product->get_id() );
					$old_stock     = AtumCache::get_transient( $transient_key, TRUE );

					if ( $old_stock !== $new_stock ) {

						$log_data = [
							'source' => LogModel::SRC_ATUM,
							'module' => LogModel::MOD_PURCHASE_ORDERS,
							'entry'  => LogEntry::ACTION_PO_STOCK_LEVELS,
							'data'   => [
								'order_id'     => $order->get_id(),
								'order_name'   => 'PO#' . $order->get_id(),
								'item_id'      => $item_id,
								'product_id'   => $product->get_id(),
								'product_name' => $product->get_name(),
								'old_stock'    => $old_stock,
								'new_stock'    => $new_stock,
							],
						];
						if ( 'variation' === $product->get_type() ) {
							$log_data['data']['product_parent'] = $product->get_parent_id();
						}
						LogModel::maybe_save_log( $log_data );
					}

					AtumCache::delete_transients( $transient_key );
				}
			}
		}

	}

	/**
	 * Logs when moving to trash, recovering or deleting a post
	 * Applied to PO, IL, Suppliers and Products
	 *
	 * @since 0.3.1
	 *
	 * @param int $post_id
	 *
	 * @throws \Exception
	 */
	public function log_trash_delete( $post_id ) {

		if ( AtumHelpers::is_using_hpos_tables() && OrderUtil::is_order( $post_id ) ) {
			$type = OrderUtil::get_order_type( $post_id );
		}
		else {
			$post = get_post( $post_id );
			$type = $post->post_type;
		}

		switch ( $type ) {
			case Suppliers::POST_TYPE:
				$entity_data = array(
					'entity' => 'SUPPLIER',
					'name'   => $post->post_title,
					'module' => LogModel::MOD_SUPPLIERS,
					'source' => LogModel::SRC_ATUM,
				);
				break;
			case InventoryLogs::POST_TYPE:
				$entity_data = array(
					'entity' => 'IL',
					'name'   => 'Log#' . $post_id,
					'module' => LogModel::MOD_INVENTORY_LOGS,
					'source' => LogModel::SRC_ATUM,
				);
				break;
			case PurchaseOrders::POST_TYPE:
				$entity_data = array(
					'entity' => 'PO',
					'name'   => 'PO#' . $post_id,
					'module' => LogModel::MOD_PURCHASE_ORDERS,
					'source' => LogModel::SRC_ATUM,
				);
				break;
			case 'product':
				$entity_data = array(
					'entity' => 'WC_PRODUCT',
					'name'   => $post->post_title,
					'module' => LogModel::MOD_WC_PRODUCT_DATA,
					'source' => LogModel::SRC_WC,
				);
				break;
			case 'shop_order':
				$entity_data = array(
					'entity' => 'WC_ORDER',
					'name'   => '#' . $post_id,
					'module' => LogModel::MOD_WC_ORDERS,
					'source' => LogModel::SRC_WC,
				);
				break;
			case 'shop_coupon':
				$entity_data = array(
					'entity' => 'WC_COUPON',
					'name'   => $post->post_title,
					'module' => LogModel::MOD_COUPONS,
					'source' => LogModel::SRC_WC,
				);
				break;
			default:
				$entity_data = array();
				break;
		}

		$entity_data = apply_filters( 'atum/action_logs/entity_data', $entity_data, $post_id, $type );

		if ( empty( $entity_data ) ) {
			return;
		}

		if ( doing_action( 'wp_trash_post' ) ) {
			$action = 'ACTION_' . $entity_data['entity'] . '_TRASH';
		}
		elseif ( doing_action( 'untrashed_post' ) ) {
			$action = 'ACTION_' . $entity_data['entity'] . '_UNTRASH';
		}
		else {
			$action = 'ACTION_' . $entity_data['entity'] . '_DEL';
		}

		$log_data = [
			'source' => $entity_data['source'],
			'module' => $entity_data['module'],
			'data'   => [
				'id'   => $post_id,
				'name' => $entity_data['name'],
			],
			'entry'  => LogEntry::get( $action ),
		];

		if ( 'product' === $type ) {

			$product = AtumHelpers::get_atum_product( $post_id );

			if ( $product && $product->exists() && 'variation' === $product->get_type() ) {
				$log_data['data']['parent'] = $product->get_parent_id();
			}

		}

		LogModel::maybe_save_log( $log_data );

	}

	/** ======================================
	 * SUPPLIERS LOGS METHODS
	 * ====================================== */

	/**
	 * Logs Supplier creation
	 *
	 * @since 0.3.1
	 */
	public function sup_save_supplier() {

		$id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : NULL;

		if ( is_null( $id ) || Suppliers::POST_TYPE !== $_POST['post_type'] ) {
			return;
		}

		// New Supplier.
		if ( isset( $_POST['auto_draft'] ) && 'auto-draft' === $_POST['original_post_status'] ) {
			$data     = [
				'id'             => $id,
				'name'           => $_POST['post_title'],
			];
			foreach ( Helpers::get_atum_supplier_metas() as $meta ) {
				foreach ( array( 'supplier_details', 'billing_information', 'default_settings' ) as $group ) {
					if ( isset( $_POST[ $group ][ $meta ] ) )
						$data[ $meta ] = $_POST[ $group ][ $meta ];
				}
			}
			$log_data = [
				'source' => LogModel::SRC_ATUM,
				'module' => LogModel::MOD_SUPPLIERS,
				'data'   => $data,
				'entry'  => LogEntry::ACTION_SUPPLIER_NEW,
			];
			LogModel::maybe_save_log( $log_data );
		}
		// Edit Supplier.
		else {

			$post      = get_post( $id );
			$meta_data = get_metadata( 'post', $id, '', TRUE );

			foreach ( array_merge( [ 'name', 'post_status' ], Helpers::get_atum_supplier_metas() ) as $dt ) {

				$old_value = $meta_data[ '_' . $dt ][0] ?? FALSE;
				$new_value = '';

				switch ( $dt ) {
					case 'name':
						$old_value = $post->post_title;
						$new_value = $_POST['post_title'];
						break;
					case 'post_status':
						$old_value = $post->post_status;
						$new_value = $_POST[ $dt ];
						break;
					case 'code':
					case 'tax_number':
					case 'phone':
					case 'fax':
					case 'website':
					case 'ordering_url':
					case 'general_email':
					case 'ordering_email':
					case 'description':
						$new_value = $_POST['supplier_details'][ $dt ];
						break;
					case 'country':
					case 'currency':
					case 'address':
					case 'city':
					case 'state':
					case 'zip_code':
						$new_value = $_POST['billing_information'][ $dt ];
						break;
					case 'assigned_to':
					case 'location':
					case 'discount':
					case 'tax_rate':
					case 'lead_time':
					case 'delivery_terms':
					case 'days_to_cancel':
					case 'cancelation_policy':
						$new_value = $_POST['default_settings'][ $dt ];
						break;
				}
				// Avoid save log without changes.
				if ( ( FALSE === $old_value || is_null( $old_value ) ) && '' === $new_value ) {
					$old_value = '';
				}
				if ( $old_value !== $new_value ) {
					if ( 'post_status' === $dt ) {
						$entry = LogEntry::ACTION_SUPPLIER_STATUS;
					}
					else {
						$entry = LogEntry::ACTION_SUPPLIER_DETAILS;
					}

					$log_data = [
						'source' => LogModel::SRC_ATUM,
						'module' => LogModel::MOD_SUPPLIERS,
						'data'   => [
							'id'        => $id,
							'name'      => $post->post_title,
							'field'     => $dt,
							'old_value' => $old_value,
							'new_value' => $new_value,
						],
						'entry'  => $entry,
					];

					LogModel::maybe_save_log( $log_data );
				}
			}

		}

	}

	/** ======================================
	 * INVENTORY LOGS LOGING METHODS
	 * ====================================== */

	/**
	 * Logs Inventory Log creation
	 *
	 * @since 0.3.1
	 */
	public function il_save_inventory_log() {

		$id = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : NULL;

		if ( is_null( $id ) || InventoryLogs::POST_TYPE !== $_POST['post_type'] ) {
			return;
		}

		// New Inventory Log.
		if ( isset( $_POST['auto_draft'] ) && 'auto-draft' === $_POST['original_post_status'] ) {
			$data     = [
				'order_id'         => $id,
				'order_name'       => 'Log#' . $id,
				'atum_order_type'  => $_POST['atum_order_type'],
				'post_status'      => $_POST['post_status'],
				'atum_order_note'  => $_POST['atum_order_note'],
				'reservation_date' => $_POST['reservation_date'] . ' ' . $_POST['reservation_date_hour'] . ':' . $_POST['reservation_date_minute'],
				'return_date'      => $_POST['return_date'] . ' ' . $_POST['return_date_hour'] . ':' . $_POST['return_date_minute'],
				'damage_date'      => $_POST['damage_date'] . ' ' . $_POST['damage_date_hour'] . ':' . $_POST['damage_date_minute'],
				'shipping_company' => $_POST['shipping_company'],
				'custom_name'      => $_POST['custom_name'],
			];
			$log_data = [
				'source' => LogModel::SRC_ATUM,
				'module' => LogModel::MOD_INVENTORY_LOGS,
				'data'   => $data,
				'entry'  => LogEntry::ACTION_IL_CREATE,
			];
			LogModel::maybe_save_log( $log_data );
		}
		// Edit Inventory Log.
		else {

			$post = get_post( $id );
			$data = Helpers::get_atum_inventory_logs_metas();

			$meta_data = get_metadata( 'post', $id, '', TRUE );

			foreach ( $data as $dt ) {

				$old_value  = isset( $meta_data[ '_' . $dt ] ) ? $meta_data[ '_' . $dt ][0] : FALSE;

				// Old and new values.
				switch ( $dt ) {
					case 'status':
						$old_value = $post->post_status;
						$new_value = $_POST[ $dt ];
						break;
					case 'description':
						$old_value = $post->post_content;
						$new_value = $_POST[ $dt ];
						break;
					case 'date_created':
						$new_value = $_POST['date'] . ' ' . $_POST['date_hour'] . ':' . $_POST['date_minute'];
						break;
					case 'reservation_date':
						$new_value = $_POST['reservation_date'] . ' ' . $_POST['reservation_date_hour'] . ':' . $_POST['reservation_date_minute'];
						break;
					case 'return_date':
						$new_value = $_POST['return_date'] . ' ' . $_POST['return_date_hour'] . ':' . $_POST['return_date_minute'];
						break;
					case 'damage_date':
						$new_value = $_POST['damage_date'] . ' ' . $_POST['damage_date_hour'] . ':' . $_POST['damage_date_minute'];
						break;
					case 'order':
						$new_value = $_POST['wc_order'] ?? '';
						break;
					case 'atum_order_type':
						$old_value  = $meta_data['_type'][0];
						// No break.
					default:
						$new_value = $_POST[ $dt ];
						break;
				}

				$avoid = FALSE;

				// Dates checking changes.
				switch ( $dt ) {
					case 'reservation_date':
					case 'return_date':
					case 'damage_date':
					case 'date_created':
						if ( str_contains( $old_value, $new_value ) ) {
							$avoid = TRUE;
						}
						break;
				}

				// Avoid save log without changes.
				if ( FALSE === $old_value || is_null( $old_value ) ) {
					$avoid = TRUE;
				}
				if ( ' :' === $new_value || '' === $new_value ) {
					$avoid = TRUE;
				}

				if ( TRUE !== $avoid )
					Helpers::maybe_log_inventory_log_detail( $id, $dt, $old_value, $new_value );

			}

		}

	}

	/**
	 * Logs item stock increase from an ATUM Order
	 *
	 * @param AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function il_increase_stock( $order ) {

		Helpers::il_change_stock( $order, 'increase' );
	}

	/**
	 * Logs item stock decrease from an ATUM Order
	 *
	 * @param AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function il_decrease_stock( $order ) {

		Helpers::il_change_stock( $order, 'decrease' );
	}

	/**
	 * Logs item stock change from an ATUM Order
	 *
	 * @param AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function order_change_stock( $order ) {

		if ( ! isset( $_POST['operation'] ) ) {
			return;
		}

		Helpers::il_change_stock( $order, $_POST['operation'] );
	}



	/** ======================================
	 * SETTINGS LOGS METHODS
	 * ====================================== */

	/**
	 * Logs settings changes
	 *
	 * @since 0.3.1
	 *
	 * @param string $option_name
	 * @param mixed  $old_value
	 * @param mixed  $value
	 *
	 * @throws \Exception
	 */
	public function settings_updated_option( $option_name, $old_value, $value ) {

		if ( 'atum_settings' !== $option_name ) {
			return;
		}

		$defaults = (array) \Atum\Settings\Settings::get_instance()->get_default_settings();

		foreach ( $value as $i => $new_value ) {

			if ( ! empty( $old_value ) && isset( $old_value[ $i ] ) && $new_value === $old_value[ $i ] ) {
				continue;
			}

			if ( empty( $new_value ) && ! isset( $old_value[ $i ] ) ) {
				continue;
			}

			if ( empty( $old_value ) && $new_value === $defaults[ $i ]['default'] ) {
				continue;
			}

			if ( is_numeric( $new_value ) && isset( $old_value[ $i ] ) && floatval( $new_value ) === floatval( $old_value[ $i ] ) ) {
				continue;
			}

			if ( '_module' === substr( $i, - 7 ) ) {
				$entry = 'yes' === $new_value ? LogEntry::ACTION_SET_ENABLE_MOD : LogEntry::ACTION_SET_DISABLE_MOD;
			}
			else {
				$entry = LogEntry::ACTION_SET_CHANGE_OPT;
			}

			$source = LogModel::SRC_ATUM;
			$module = LogModel::MOD_ATUM_SETTINGS;

			if ( 0 === strpos( $i, 'mi_' ) ) {
				$source = LogModel::SRC_MI;
				$module = LogModel::MOD_MI_SETTINGS;
			}
			elseif ( 0 === strpos( $i, 'pl_' ) ) {
				$source = LogModel::SRC_PL;
				$module = LogModel::MOD_PL_SETTINGS;
			}
			elseif ( 0 === strpos( $i, 'al_' ) ) {
				$source = LogModel::SRC_AL;
				$module = LogModel::MOD_AL_SETTINGS;
			}
			elseif ( 0 === strpos( $i, 'po_' ) ) {
				$source = LogModel::SRC_PO;
				$module = LogModel::MOD_PO_SETTINGS;
			}
			elseif ( 0 === strpos( $i, 'st_' ) ) {
				$source = LogModel::SRC_ST;
				$module = LogModel::MOD_ST_SETTINGS;
			}

			$log_data = [
				'source' => $source,
				'module' => $module,
				'data'   => [
					'field'     => $i,
					'old_value' => ! empty( $old_value ) && isset( $old_value[ $i ] ) ? $old_value[ $i ] : FALSE,
					'new_value' => $new_value,
				],
				'entry'  => $entry,
			];

			LogModel::maybe_save_log( $log_data );

		}
	}

	/**
	 * Logs the tools execution
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function settings_execute_tools() {

		$source = LogModel::SRC_ATUM;
		$module = LogModel::MOD_ATUM_SETTINGS;
		$entry  = LogEntry::ACTION_SET_RUN_TOOL;

		if ( doing_action( 'atum/ajax/tool_change_manage_stock' ) ) {
			$tool = __( 'Update WC\'s Manage Stock', ATUM_LOGS_TEXT_DOMAIN );
		}
		elseif ( doing_action( 'atum/ajax/tool_change_control_stock' ) ) {
			$tool = __( 'Update ATUM\'s stock control', ATUM_LOGS_TEXT_DOMAIN );
		}
		elseif ( doing_action( 'atum/ajax/tool_clear_out_stock_threshold' ) ) {
			$tool = __( 'Clear Out Stock Threshold', ATUM_LOGS_TEXT_DOMAIN );
		}
		elseif ( doing_action( 'atum/ajax/tool_clear_out_atum_transients' ) ) {
			$tool = __( 'Clear Out ATUM Temporary Data', ATUM_LOGS_TEXT_DOMAIN );
		}
		elseif ( doing_action( 'atum/ajax/tool_mi_migrate_regions' ) ) {
			$tool   = __( 'Switch from Zones to Zones', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
		}
		elseif ( doing_action( 'atum/ajax/tool_mi_migrate_countries' ) ) {
			$tool   = __( 'Migrate from Countries to Zones', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
		}
		elseif ( doing_action( 'atum/ajax/tool_mi_migrate_shipping_zones' ) ) {
			$tool   = __( 'Migrate from Zones to Countries', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
		}
		elseif ( doing_action( 'atum/cli/tool_change_manage_stock' ) ) {
			$tool  = __( 'Update WC\'s Manage Stock', ATUM_LOGS_TEXT_DOMAIN );
			$entry = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_change_control_stock' ) ) {
			$tool  = __( 'Update ATUM\'s stock control', ATUM_LOGS_TEXT_DOMAIN );
			$entry = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_clear_out_stock_threshold' ) ) {
			$tool  = __( 'Clear Out Stock Threshold', ATUM_LOGS_TEXT_DOMAIN );
			$entry = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_clear_out_atum_transients' ) ) {
			$tool  = __( 'Clear Out ATUM Temporary Data', ATUM_LOGS_TEXT_DOMAIN );
			$entry = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_mi_migrate_regions' ) ) {
			$tool   = __( 'Switch from Zones to Zones', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
			$entry  = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_mi_migrate_countries' ) ) {
			$tool   = __( 'Migrate from Countries to Zones', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
			$entry  = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_mi_migrate_shipping_zones' ) ) {
			$tool   = __( 'Migrate from Zones to Countries', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_MI;
			$module = LogModel::MOD_MI_SETTINGS;
			$entry  = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		elseif ( doing_action( 'atum/cli/tool_sync_real_stock' ) ) {
			$tool   = __( 'Sync WooCommerce stock with calculated stock', ATUM_LOGS_TEXT_DOMAIN );
			$source = LogModel::SRC_PL;
			$module = LogModel::MOD_PL_SETTINGS;
			$entry  = LogEntry::ACTION_SET_RUN_TOOL_CLI;
		}
		else {
			$tool = __( 'Unknown', ATUM_LOGS_TEXT_DOMAIN );
		}

		$data = [];
		if ( isset( $_POST, $_POST['option'] ) && ! empty( $_POST['option'] ) ) {
			$data['options'] = json_decode( stripslashes( $_POST['option'] ), TRUE );
		}
		$data['tool'] = $tool;

		$log_data = [
			'source' => $source,
			'module' => $module,
			'data'   => $data,
			'entry'  => $entry,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/** ======================================
	 * ATUM ADDONS LICENSE LOGS METHODS
	 * ====================================== */

	/**
	 * Logs addons license activation/deactivation
	 *
	 * @since 0.3.1
	 *
	 * @param array|\WP_Error $response
	 *
	 * @throws \Exception
	 */
	public function addons_license( $response ) {

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		$addon        = esc_attr( $_POST['addon'] );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return;
		}

		if ( doing_action( 'atum/addons/activate_license' ) ) {
			$entry = LogEntry::ACTION_ADDON_ACTIVATE;
			$error = FALSE === $license_data->success;
		}
		elseif ( doing_action( 'atum/addons/deactivate_license' ) ) {
			$entry = LogEntry::ACTION_ADDON_DEACTIVATE;
			$error = 'deactivated' !== $license_data->license;
		}
		else {
			return;
		}

		if ( ! $error ) {
			$log_data = [
				'source' => LogModel::SRC_ATUM,
				'module' => LogModel::MOD_ADDONS,
				'data'   => [
					'name' => $addon,
					'data' => $license_data,
				],
				'entry'  => $entry,
			];
			LogModel::maybe_save_log( $log_data );
		}
	}


	/** ======================================
	 * ATUM LOCATIONS LOGS METHODS
	 * ====================================== */

	/**
	 * Logs a location creation
	 *
	 * @since 0.3.1
	 *
	 * @param int    $term_id
	 * @param int    $tt_id
	 * @param string $taxonomy
	 *
	 * @throws \Exception
	 */
	public function location_create( $term_id, $tt_id, $taxonomy ) {

		if ( AtumGlobals::PRODUCT_LOCATION_TAXONOMY !== $taxonomy ) {
			return;
		}

		$term = get_term( $term_id );

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_LOCATIONS,
			'data'   => Helpers::o2a( $term ),
			'entry'  => LogEntry::ACTION_LOC_CREATE,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs a location deletion
	 *
	 * @since 0.3.1
	 *
	 * @param object $term
	 * @param int    $tt_id
	 * @param object $deleted_term
	 * @param array  $object_ids
	 *
	 * @throws \Exception
	 */
	public function location_delete( $term, $tt_id, $deleted_term, $object_ids ) {

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_LOCATIONS,
			'data'   => Helpers::o2a( $deleted_term ),
			'entry'  => LogEntry::ACTION_LOC_DEL,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Reads previous value of an ATUM location before change it
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 *
	 * @since 0.3.1
	 */
	public function location_before_update( $term_id, $taxonomy ) {

		if ( AtumGlobals::PRODUCT_LOCATION_TAXONOMY !== $taxonomy ) {
			return;
		}

		$term = (array) get_term( $term_id );

		$transient_key = AtumCache::get_transient_key( 'log_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY . '_' . $term_id );
		AtumCache::set_transient( $transient_key, $term, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Saves log on editing an ATUM location
	 *
	 * @since 0.3.1
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 *
	 * @throws \Exception
	 */
	public function location_after_update( $term_id, $tt_id ) {

		$term = get_term( $term_id );
		$term = (array) $term;

		$transient_key = AtumCache::get_transient_key( 'log_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY . '_' . $term_id );
		$predata       = AtumCache::get_transient( $transient_key, TRUE );
		$diff_data     = [];

		foreach ( $predata as $idx => $data ) {
			if ( $data === $term[ $idx ] ) {
				continue;
			}

			$diff_data[] = array(
				'field'     => $idx,
				'old_value' => $predata[ $idx ],
				'new_value' => $term[ $idx ],
			);

		}

		if ( empty( $diff_data ) ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_LOCATIONS,
			'data'   => [
				'term_id' => $term_id,
				'name'    => $term['name'],
				'changes' => $diff_data,
			],
			'entry'  => LogEntry::ACTION_LOC_CHANGE,
		];

		LogModel::maybe_save_log( $log_data );

		AtumCache::delete_transients( $transient_key );
	}


	/** ======================================
	 * PRODUCT DATA LOGS METHODS
	 * ====================================== */

	/**
	 * Checks ATUM Locations/Product Data previous values for a product
	 *
	 * @param int   $post_ID
	 * @param mixed $data
	 *
	 * @since 0.3.1
	 */
	public function before_product_save( $post_ID, $data ) {

		if ( doing_action( 'woocommerce_product_bulk_and_quick_edit' ) ) {
			if ( empty( $_REQUEST ) || ! isset( $_REQUEST['woocommerce_quick_edit'] ) ) {
				return;
			}

		}

		$dump_data = [];
		$post      = get_post( $post_ID );

		if ( FALSE === in_array( $post->post_type, [ 'product', 'product_variation' ] ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			$transient_key = AtumCache::get_transient_key( 'log_new_product' );
			AtumCache::set_transient( $transient_key, $post_ID, MINUTE_IN_SECONDS, TRUE );

			return;
		}

		$product = AtumHelpers::get_atum_product( $post_ID );

		/**
		 * Variable defainition
		 *
		 * @var \WC_Product $product
		 */
		if ( $product instanceof \WC_Product && $product->exists() ) {

			$product_id = 'variation' === $product->get_type() ? $product->get_parent_id() : $product->get_id();

			// Remember for ATUM Locations.
			$locations     = wc_get_product_terms( $product_id, AtumGlobals::PRODUCT_LOCATION_TAXONOMY );
			$transient_key = AtumCache::get_transient_key( 'log_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY . '_product_' . $product_id );
			AtumCache::set_transient( $transient_key, $locations, MINUTE_IN_SECONDS, TRUE );

			// Remember for product data.
			$dump_data['menu_order']     = $post->menu_order;
			$dump_data['comment_status'] = $post->comment_status;

			foreach ( Helpers::get_product_metas() as $meta => $plugin ) {
				if ( ( 'ATUM' === $plugin || 'Product Levels' === $plugin ) && method_exists( $product, 'get_' . $meta ) ) {
					$dump_data[ $meta ] = $product->{"get_$meta"}();
				}
				elseif ( 'Multi-Inventory' === $plugin && method_exists( $product, 'get_product' . $meta ) ) {
					$dump_data[ $meta ] = $product->{"get_product$meta"}();
				}
				elseif ( 'WP' === $plugin ) {
					$dump_data[ $meta ] = $post->$meta;
				}
				elseif ( '_menu_order' === $meta ) {
					$dump_data[ $meta ] = $product->get_menu_order();
				}
				elseif ( '_reviews_allowed' === $meta ) {
					$dump_data[ $meta ] = $product->get_reviews_allowed( 'edit' );
				}
				elseif ( 'product_type' === $meta ) {
					$dump_data[ $meta ] = $product->get_type();
				}
				else {
					$dump_data[ $meta ] = get_post_meta( $post_ID, $meta, TRUE );
				}
			}
			$transient_key_metadata = AtumCache::get_transient_key( 'log_metadata_product_' . $product->get_id() );
			AtumCache::set_transient( $transient_key_metadata, $dump_data, MINUTE_IN_SECONDS, TRUE );

			// Remember for product categories/tags.
			$cats                   = $product->get_category_ids();
			$tags                   = $product->get_tag_ids();
			$transient_key_metadata = AtumCache::get_transient_key( 'log_categories_product_' . $product->get_id() );
			AtumCache::set_transient( $transient_key_metadata, [
				'product_id' => $product->get_id(),
				'cats'       => $cats,
				'tags'       => $tags,
			], MINUTE_IN_SECONDS, TRUE );
		}

	}

	/**
	 * Log WC Product quick save.
	 *
	 * @param \WC_Product $product
	 *
	 * @since 1.0.6
	 */
	public function after_product_quick_save( $product ) {

		$this->after_product_save( $product->get_id(), NULL, TRUE );

	}

	/**
	 * Logs ATUM Locations/Product Data updates on a product
	 *
	 * @since 0.3.1
	 *
	 * @param int    $post_ID
	 * @param object $post
	 * @param mixed  $update
	 *
	 * @throws \Exception
	 */
	public function after_product_save( $post_ID, $post, $update ) {

		$post = get_post( $post_ID );

		if ( FALSE === in_array( $post->post_type, [ 'product', 'product_variation' ] ) ) {
			return;
		}

		if ( $update === FALSE )
			return;

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( doing_action( 'save_post' ) )
			remove_action( 'woocommerce_rest_insert_product_object', array( $this, 'wc_api_after' ), PHP_INT_MAX );
		else
			remove_action( 'save_post', array( $this, 'after_product_save' ), 10 );

		// Log new product.
		$transient_key = AtumCache::get_transient_key( 'log_new_product' );
		if ( AtumCache::get_transient( $transient_key, TRUE ) === $post_ID ) {

			$data = [
				'id'   => $post->ID,
				'name' => $post->post_title,
			];

			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_WC_PRODUCT_DATA,
				'data'   => $data,
				'entry'  => LogEntry::ACTION_WC_PRODUCT_CREATE,
			];
			LogModel::maybe_save_log( $log_data );
			AtumCache::delete_transients( $transient_key );

			return;
		}

		$product = AtumHelpers::get_atum_product( $post_ID );

		/**
		 * Variable definition
		 *
		 * @var \WC_Product $product
		 */

		if ( $product && $product->exists() ) {

			$is_variation = 'variation' === $product->get_type() ? TRUE : FALSE;

			$product_id = $is_variation ? $product->get_parent_id() : $product->get_id();

			// ATUM Locations block.
			$new_locations = wc_get_product_terms( $product_id, AtumGlobals::PRODUCT_LOCATION_TAXONOMY );
			$transient_key = AtumCache::get_transient_key( 'log_' . AtumGlobals::PRODUCT_LOCATION_TAXONOMY . '_product_' . $product_id );
			$old_locations = AtumCache::get_transient( $transient_key, TRUE );

			if ( ! empty( $new_locations ) ) {
				foreach ( $new_locations as $new_location ) {
					if ( empty( $old_locations ) || ( ! empty( $old_locations ) && FALSE === in_array( $new_location, $old_locations ) ) ) {
						$log_data = [
							'source' => LogModel::SRC_ATUM,
							'module' => LogModel::MOD_LOCATIONS,
							'data'   => [
								'product_id'   => $product->get_id(),
								'product_name' => $product->get_name(),
								'term_id'      => $new_location->term_id,
								'name'         => $new_location->name,
							],
							'entry'  => LogEntry::ACTION_LOC_ASSIGN,
						];
						if ( $is_variation ) {
							$log_data['data']['product_parent'] = $product->get_parent_id();
						}

						LogModel::maybe_save_log( $log_data );
					}
				}
			}

			if ( ! empty( $old_locations ) ) {
				foreach ( $old_locations as $old_location ) {
					if ( ! empty( $new_locations ) && FALSE === in_array( $old_location, $new_locations ) ) {
						$log_data = [
							'source' => LogModel::SRC_ATUM,
							'module' => LogModel::MOD_LOCATIONS,
							'entry'  => LogEntry::ACTION_LOC_UNASSIGN,
							'data'   => [
								'product_id'   => $product->get_id(),
								'product_name' => $product->get_name(),
								'term_id'      => $old_location->term_id,
								'name'         => $old_location->name,
							],
						];
						if ( $is_variation ) {
							$log_data['data']['product_parent'] = $product->get_parent_id();
						}

						LogModel::maybe_save_log( $log_data );
					}
				}
			}
			AtumCache::delete_transients( $transient_key );

			// Product data block.
			$transient_key_metadata = AtumCache::get_transient_key( 'log_metadata_product_' . $product->get_id() );
			$old_metas              = AtumCache::get_transient( $transient_key_metadata, TRUE );

			if ( ! empty( $old_metas ) && ( ! isset( $_POST['original_post_status'] ) || 'auto-draft' !== $_POST['original_post_status'] ) ) {

				foreach ( Helpers::get_product_metas() as $meta => $plugin ) {

					// Logged at wc_product_updated method.
					if ( 'atum_stock_status' === $meta || '_stock_status' === $meta ) {
						continue;
					}

					if ( ( 'ATUM' === $plugin || 'Product Levels' === $plugin ) && method_exists( $product, 'get_' . $meta ) ) {
						$new_value = $product->{"get_$meta"}();
					}
					elseif ( 'Multi-Inventory' === $plugin && method_exists( $product, 'get_product' . $meta ) ) {
						$data[ $meta ] = $product->{"get_product$meta"}();
					}
					elseif ( 'WP' === $plugin ) {
						$new_value = $post->$meta;
					}
					elseif ( 'Multi-Inventory' === $plugin ) { // Multi-Inventory is logged in its own Integration class.
						continue;
					}
					elseif ( 'product_type' === $meta ) {
						$new_value = $product->get_type();
					}
					elseif ( '_menu_order' === $meta ) {
						$new_value = $product->get_menu_order();
					}
					elseif ( '_reviews_allowed' === $meta ) {
						$new_value = $product->get_reviews_allowed( 'edit' );
					}
					else {
						$new_value = get_post_meta( $product->get_id(), $meta, TRUE );
					}

					if ( 'out_stock_date' === $meta && ! empty( $old_metas[ $meta ] ) ) {
						/**
						 * Variable definition
						 *
						 * @var \WC_DateTime $new_value
						 * @var \WC_DateTime $old_value
						 */
						$old_value = $old_metas[ $meta ];

						if ( $old_value instanceof \WC_DateTime && $new_value instanceof \WC_DateTime && $old_value->getTimestamp() === $new_value->getTimestamp() ) {
							continue;
						}
					}

					switch ( $plugin ) {
						case 'ATUM':
							$source = LogModel::SRC_ATUM;
							$module = LogModel::MOD_ATUM_PRODUCT_DATA;
							break;
						case 'Product Levels':
							$source = LogModel::SRC_PL;
							$module = LogModel::MOD_PL_PRODUCT_DATA;
							break;
						default:
							$source = LogModel::SRC_WC;
							$module = LogModel::MOD_WC_PRODUCT_DATA;
							break;
					}

					/**
					 * Variable definition
					 *
					 * @var mixed $old_value
					 */
					$old_value = $old_metas[ $meta ];

					if ( $old_value !== $new_value || ( is_null( $old_value ) && ! empty( $new_value ) ) ) {

						// Entries.
						switch ( $meta ) {
							case '_atum_manage_stock':
							case 'atum_controlled':
								$entry = LogEntry::ACTION_PD_MANAGE_STOCK;
								break;
							case '_sync_purchase_price':
								$entry = 'yes' === $new_value ? LogEntry::ACTION_PD_ENABLE_SYNC : LogEntry::ACTION_PD_DISABLE_SYNC;
								break;
							case 'minimum_threshold':
							case 'available_to_purchase':
								$entry = LogEntry::ACTION_PD_EDIT;
								break;
							default:
								$entry = LogEntry::ACTION_PD_EDIT_2;
								break;
						}

						// Titles and values.
						switch ( $meta ) {
							case 'supplier_id':
								$meta    = __( 'Supplier', ATUM_LOGS_TEXT_DOMAIN );
								$old_sup = new Supplier( $old_value );
								$new_sup = new Supplier( $new_value );

								$old_value = [
									'id'   => $old_value,
									'name' => $old_sup->name,
								];
								$new_value = [
									'id'   => $new_value,
									'name' => $new_sup->name,
								];
								break;
							case '_tax_class':
								if ( '' === $old_value ) {
									$old_value = 'standard';
								}
								if ( '' === $new_value ) {
									$new_value = 'standard';
								}
								break;
							case '_stock':
								$meta = __( 'Stock Quantity', ATUM_LOGS_TEXT_DOMAIN );
								break;
							case 'post_content':
								$meta = __( 'Description', ATUM_LOGS_TEXT_DOMAIN );
								break;
							case 'post_excerpt':
								$meta = __( 'Short Description', ATUM_LOGS_TEXT_DOMAIN );
								break;
						}

						$log_data = [
							'source' => $source,
							'module' => $module,
							'entry'  => $entry,
							'data'   => [
								'id'        => $product->get_id(),
								'name'      => $product->get_name(),
								'field'     => $meta,
								'old_value' => $old_value,
								'new_value' => $new_value,
							],
						];
						if ( $is_variation ) {
							$log_data['data']['parent'] = $product->get_parent_id();
						}

						LogModel::maybe_save_log( $log_data );
					}
				}
			}
			AtumCache::delete_transients( $transient_key_metadata );

			// Product categories block.
			$transient_key_categories = AtumCache::get_transient_key( 'log_categories_product_' . $product->get_id() );
			$old_data                 = AtumCache::get_transient( $transient_key_categories, TRUE );

			if ( isset( $old_data['product_id'] ) && $product->get_id() === $old_data['product_id'] && ( ! isset( $_POST['original_post_status'] ) || 'auto-draft' !== $_POST['original_post_status'] ) ) {

				$new_cats = $product->get_category_ids();
				$new_tags = $product->get_tag_ids();
				$old_cats = isset( $old_data['cats'] ) ? $old_data['cats'] : [];
				$old_tags = isset( $old_data['tags'] ) ? $old_data['tags'] : [];
				$changes  = [];

				foreach ( $new_cats as $id ) {
					if ( FALSE === in_array( $id, $old_cats ) ) {
						$changes[] = [
							'id'     => $id,
							'entity' => 'cat',
							'action' => 'add',
						];
					}
				}

				foreach ( $old_cats as $id ) {
					if ( FALSE === in_array( $id, $new_cats ) ) {
						$changes[] = [
							'id'     => $id,
							'entity' => 'cat',
							'action' => 'remove',
						];
					}
				}
				foreach ( $new_tags as $id ) {
					if ( FALSE === in_array( $id, $old_tags ) ) {
						$changes[] = [
							'id'     => $id,
							'entity' => 'tag',
							'action' => 'add',
						];
					}
				}
				foreach ( $old_tags as $id ) {
					if ( FALSE === in_array( $id, $new_tags ) ) {
						$changes[] = [
							'id'     => $id,
							'entity' => 'tag',
							'action' => 'remove',
						];
					}
				}

				foreach ( $changes as $change ) {
					if ( 'tag' === $change['entity'] ) {
						$entry = 'add' === $change['action'] ? LogEntry::ACTION_WC_TAG_ADD : LogEntry::ACTION_WC_TAG_DEL;
					}
					else {
						$entry = 'add' === $change['action'] ? LogEntry::ACTION_WC_CATEGORY_ADD : LogEntry::ACTION_WC_CATEGORY_DEL;
					}
					$cat      = get_term( $change['id'] );
					$log_data = [
						'source' => LogModel::SRC_WC,
						'module' => LogModel::MOD_CATEGORIES,
						'entry'  => $entry,
						'data'   => [
							'product_id'                                              => $product->get_id(),
							'product_name'                                            => $product->get_name(),
							'term_id'                                                 => $cat->term_id,
							'name'                                                    => $cat->name,
							'add' === $change['action'] ? 'new_term' : 'removed_term' => $cat,
						],
					];
					if ( $is_variation ) {
						$log_data['data']['product_parent'] = $product->get_parent_id();
					}

					LogModel::maybe_save_log( $log_data );
				}
			}
			AtumCache::delete_transients( $transient_key_categories );

		}

	}

	/**
	 * Logs product stock_status changes
	 *
	 * @since 0.3.1
	 *
	 * @param int         $product_id
	 * @param string      $status
	 * @param \WC_Product $product
	 *
	 * @throws \Exception
	 */
	public function wc_product_updated( $product_id, $status, $product ) {

		$new_value    = $status;
		$data         = $product->get_data();
		$old_value    = $data['stock_status'];
		$is_variation = 'variation' === $product->get_type();

		$out_stock_threshold = $data['out_stock_threshold'] ?? FALSE;
		$stock_quantity      = $data['stock_quantity'] ?? FALSE;

		if ( 'auto-draft' === get_post( $product->get_id() )->post_status ) {
			return;
		}

		if ( $old_value === $new_value ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_PRODUCT_DATA,
			'data'   => [
				'id'        => $product_id,
				'name'      => $product->get_name(),
				'old_value' => $old_value,
				'new_value' => $new_value,
			],
			'entry'  => LogEntry::ACTION_WC_PRODUCT_STATUS,
		];

		if ( $is_variation ) {
			$log_data['data']['parent'] = $product->get_parent_id();
		}

		LogModel::maybe_save_log( $log_data );

		if ( 'outofstock' === $new_value && FALSE !== $out_stock_threshold && $out_stock_threshold >= $stock_quantity ) {
			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_WC_PRODUCT_DATA,
				'entry'  => LogEntry::ACTION_ATUM_MIN_THRESHOLD,
				'data'   => [
					'id'                  => $product_id,
					'name'                => $product->get_name(),
					'out_stock_threshold' => $out_stock_threshold,
					'stock_quantity'      => $stock_quantity,
				],
			];

			if ( $is_variation ) {
				$log_data['data']['parent'] = $product->get_parent_id();
			}

			LogModel::maybe_save_log( $log_data );
		}

	}

	/**
	 * Logs a category/tag creation
	 *
	 * @since 0.3.1
	 *
	 * @param int    $term_id
	 * @param int    $tt_id
	 * @param string $taxonomy
	 *
	 * @throws \Exception
	 */
	public function wc_term_create( $term_id, $tt_id, $taxonomy ) {

		if ( 'product_cat' === $taxonomy ) {
			$entry = LogEntry::ACTION_WC_CATEGORY_CREATE;
		}
		elseif ( 'product_tag' === $taxonomy ) {
			$entry = LogEntry::ACTION_WC_TAG_CREATE;
		}
		else {
			return;
		}

		$term = get_term( $term_id );

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'data'   => Helpers::o2a( $term ),
			'entry'  => $entry,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs an attribute creation
	 *
	 * @since 0.3.1
	 *
	 * @param int   $id
	 * @param mixed $data
	 *
	 * @throws \Exception
	 */
	public function wc_attribute_create( $id, $data ) {

		$data['id'] = $id;
		$log_data   = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_WC_ATTR_CREATE,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs a category/tag deletion
	 *
	 * @since 0.3.1
	 *
	 * @param \WP_Term $term
	 * @param int      $tt_id
	 * @param \WP_Term $deleted_term
	 * @param array    $object_ids
	 *
	 * @throws \Exception
	 */
	public function wc_term_delete( $term, $tt_id, $deleted_term, $object_ids ) {

		if ( 'product_cat' === $deleted_term->taxonomy ) {
			$entry = LogEntry::ACTION_WC_CATEGORY_REMOVE;
		}
		elseif ( 'product_tag' === $deleted_term->taxonomy ) {
			$entry = LogEntry::ACTION_WC_TAG_REMOVE;
		}
		else {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'data'   => Helpers::o2a( $deleted_term ),
			'entry'  => $entry,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs attribute deletion
	 *
	 * @since 0.3.1
	 *
	 * @param int    $id
	 * @param string $name
	 * @param string $taxonomy
	 *
	 * @throws \Exception
	 */
	public function wc_attribute_delete( $id, $name, $taxonomy ) {

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'data'   => [
				'id'       => $id,
				'name'     => $name,
				'taxonomy' => $taxonomy,
			],
			'entry'  => LogEntry::ACTION_WC_ATTR_DELETE,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Reads previous value of an ATUM location before change it
	 *
	 * @since 0.3.1
	 *
	 * @param int    $term_id
	 * @param string $taxonomy
	 */
	public function wc_term_before_update( $term_id, $taxonomy ) {

		if ( 'product_cat' !== $taxonomy && 'product_tag' !== $taxonomy ) {
			return;
		}

		$term = (array) get_term( $term_id );

		$transient_key = AtumCache::get_transient_key( 'log_' . $taxonomy . '_' . $term_id );
		AtumCache::set_transient( $transient_key, $term, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Saves log on editing an WC Term
	 *
	 * @since 0.3.1
	 *
	 * @param int $term_id
	 * @param int $tt_id
	 *
	 * @throws \Exception
	 */
	public function wc_term_after_update( $term_id, $tt_id ) {

		$term = get_term( $term_id );
		$term = (array) $term;

		$transient_key = AtumCache::get_transient_key( 'log_' . $term['taxonomy'] . '_' . $term_id );
		$predata       = AtumCache::get_transient( $transient_key, TRUE );
		$diff_data     = [];

		$entry = 'product_cat' === $term['taxonomy'] ? LogEntry::ACTION_WC_CATEGORY_EDIT : LogEntry::ACTION_WC_TAG_EDIT;

		foreach ( $predata as $idx => $data ) {

			if ( $data === $term[ $idx ] ) {
				continue;
			}

			$diff_data[] = array(
				'field'     => $idx,
				'old_value' => $predata[ $idx ],
				'new_value' => $term[ $idx ],
			);

		}

		if ( empty( $diff_data ) ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'entry'  => $entry,
			'data'   => [
				'term_id' => $term_id,
				'name'    => $term['name'],
				'changes' => $diff_data,
			],
		];

		LogModel::maybe_save_log( $log_data );

		AtumCache::delete_transients( $transient_key );
	}

	/**
	 * Logs attribute updated
	 *
	 * @since 0.3.1
	 *
	 * @param int    $id
	 * @param mixed  $data
	 * @param string $old_slug
	 *
	 * @throws \Exception
	 */
	public function wc_attribute_updated( $id, $data, $old_slug ) {

		$data['id']       = $id;
		$data['old_slug'] = $old_slug;
		$log_data         = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_WC_ATTR_UPDATE,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs attribute saving
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_save_product_attributes() {

		check_ajax_referer( 'save-attributes', 'security' );

		if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['data'], $_POST['post_id'] ) ) {
			return;
		}

		parse_str( wp_unslash( $_POST['data'] ), $data ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$labels       = wc_get_attribute_taxonomy_labels();
		$product_id   = absint( wp_unslash( $_POST['post_id'] ) );
		$product_type = ! empty( $_POST['product_type'] ) ? wc_clean( wp_unslash( $_POST['product_type'] ) ) : 'simple';
		$classname    = \WC_Product_Factory::get_product_classname( $product_id, $product_type );
		/**
		 * Variable definition
		 *
		 * @var \WC_Product $product
		 */
		$product    = new $classname( $product_id );
		$attributes = $product->get_attributes();
		if ( ! empty( $data['attribute_names'] ) ) {
			foreach ( $data['attribute_names'] as $i => $attribute_name ) {

				// If attribute has not values, it wont be assigned to product, so there's nothing to log.
				if ( ! isset( $data['attribute_values'], $data['attribute_values'][ $i ] ) ) {
					continue;
				}
				if ( empty( $data['attribute_values'][ $i ] ) ) {
					continue;
				}

				$label = isset( $labels[ $attribute_name ] ) ? $labels[ $attribute_name ] : $attribute_name;

				if ( ! isset( $attributes[ sanitize_title( $attribute_name ) ] ) ) {

					$log_data = [
						'source' => LogModel::SRC_WC,
						'module' => LogModel::MOD_CATEGORIES,
						'entry'  => LogEntry::ACTION_WC_ATTR_ASSIGN,
						'data'   => [
							'product_id'      => $product_id,
							'product_name'    => $product->get_name(),
							'attribute_label' => $label,
							'attribute_name'  => $attribute_name,
						],
					];
					if ( 'variation' === $product->get_type() ) {
						$log_data['data']['product_parent'] = $product->get_parent_id();
					}
					LogModel::maybe_save_log( $log_data );
				}
				else {
					/**
					 * Variable definition
					 *
					 * @since 1.0.0
					 *
					 * @var \WC_Product_Attribute $attribute
					 */
					$attribute = $attributes[ sanitize_title( $attribute_name ) ];
					$values    = is_array( $data['attribute_values'][ $i ] ) ? $data['attribute_values'][ $i ] : [ $data['attribute_values'][ $i ] ];
					foreach ( $values as $att ) {
						if ( FALSE === in_array( $att, $attribute->get_options() ) ) {
							$term = get_term( $att );

							if ( $term instanceof \WP_Term ) {
								$log_data = [
									'source' => LogModel::SRC_WC,
									'module' => LogModel::MOD_CATEGORIES,
									'entry'  => LogEntry::ACTION_WC_ATTR_ASSIGN_VALUE,
									'data'   => [
										'value'           => $term->name,
										'product_id'      => $product_id,
										'product_name'    => $product->get_name(),
										'attribute_label' => $label,
										'attribute_name'  => $attribute_name,
									],
								];
								if ( 'variation' === $product->get_type() ) {
									$log_data['data']['product_parent'] = $product->get_parent_id();
								}
								LogModel::maybe_save_log( $log_data );
							}
						}
					}
				}
			}
		}
		if ( ! empty( $attributes ) ) {
			$length = count( $data['attribute_names'] );
			for ( $i = 0; $i < $length; $i ++ ) {
				$data['attribute_names'][ $i ] = sanitize_title( $data['attribute_names'][ $i ] );
			}
			/**
			 * Variable definition
			 *
			 * @since 1.0.0
			 *
			 * @var \WC_Product_Attribute $at_data
			 */
			foreach ( $attributes as $attribute => $at_data ) {
				if ( in_array( $attribute, $data['attribute_names'] ) ) {
					foreach ( $at_data->get_options() as $option ) {
						$i = 0;
						foreach ( $data['attribute_names'] as $i => $attribute_name ) {
							if ( $attribute === $attribute_name ) {
								break;
							}
						}
						if ( isset( $data['attribute_values'][ $i ] ) && is_array( $data['attribute_values'][ $i ] ) &&
						    FALSE === in_array( $option, $data['attribute_values'][ $i ] ) ) {

							$term     = get_term( $option );
							$log_data = [
								'source' => LogModel::SRC_WC,
								'module' => LogModel::MOD_CATEGORIES,
								'entry'  => LogEntry::ACTION_WC_ATTR_UNASSIGN_VALUE,
								'data'   => [
									'value'           => $term->name,
									'product_id'      => $product_id,
									'product_name'    => $product->get_name(),
									'attribute_label' => isset( $labels[ $attribute ] ) ? $labels[ $attribute ] : $attribute,
									'attribute_name'  => $attribute,
								],
							];
							if ( 'variation' === $product->get_type() ) {
								$log_data['data']['product_parent'] = $product->get_parent_id();
							}
							LogModel::maybe_save_log( $log_data );

						}
					}
					continue;
				}

				$log_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_CATEGORIES,
					'entry'  => LogEntry::ACTION_WC_ATTR_UNASSIGN,
					'data'   => [
						'product_id'      => $product_id,
						'product_name'    => $product->get_name(),
						'attribute_label' => $labels[ $attribute ] ?: $attribute,
						'attribute_name'  => $attribute,
					],
				];
				if ( 'variation' === $product->get_type() ) {
					$log_data['data']['product_parent'] = $product->get_parent_id();
				}
				LogModel::maybe_save_log( $log_data );
			}
		}

	}

	/**
	 * Logs attribute saving
	 *
	 * @since 1.0.0
	 *
	 * @throws \Exception
	 */
	public function wc_add_new_attribute() {

		check_ajax_referer( 'add-attribute', 'security' );

		if ( ! current_user_can( 'manage_product_terms' ) || ! isset( $_POST['taxonomy'], $_POST['term'] ) ) {
			return;
		}

		$taxonomy = esc_attr( wp_unslash( $_POST['taxonomy'] ) ); // phpcs:ignore
		$term     = wc_clean( wp_unslash( $_POST['term'] ) );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$taxonomy_data = get_taxonomy( $taxonomy );

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_CATEGORIES,
			'entry'  => LogEntry::ACTION_WC_ATTR_ADD,
			'data'   => [
				'attribute_name'  => $taxonomy_data->name,
				'attribute_label' => $taxonomy_data->label,
				'new_value'       => $term,
			],
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Variations saving
	 *
	 * @since 0.5.1
	 *
	 * @throws \Exception
	 */
	public function wc_save_variations() {

		check_ajax_referer( 'save-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) || empty( $_POST ) || empty( $_POST['product_id'] ) ) {
			return;
		}

		// Get the parent product.
		$product_id = absint( $_POST['product_id'] );
		$product    = AtumHelpers::get_atum_product( $product_id );

		if ( ! method_exists( $product, 'get_variation_attributes' ) ) {
			return;
		}

		$attributes = $product->get_variation_attributes();

		foreach ( $_POST['variable_post_id'] as $index => $variation_id ) {

			$product_name = $product->get_name();
			$att_name     = '';

			foreach ( $attributes as $at_index => $attribute ) {
				if ( isset( $_POST[ 'attribute_' . $at_index ], $_POST[ 'attribute_' . $at_index ][ $index ] ) ) {
					if ( strlen( $att_name ) > 0 ) {
						$att_name .= ', ';
					}
					$att_name .= $_POST[ 'attribute_' . $at_index ][ $index ];
				}
			}

			$product_name .= strlen( $att_name ) ? ' (' . $att_name . ')' : '';

			$variation = AtumHelpers::get_atum_product( $variation_id );

			if ( ! $variation instanceof \WC_Product ) {
				continue;
			}

			foreach ( Helpers::get_var_product_metas() as $meta => $plugin ) {

				$avoid = FALSE;

				switch ( $plugin ) {
					case 'ATUM':
						$post_data = 'variation_' . $meta;
						$source    = LogModel::SRC_ATUM;
						$module    = LogModel::MOD_ATUM_PRODUCT_DATA;
						break;
					default:
						$post_data = 'variable' . $meta;
						$source    = LogModel::SRC_WC;
						$module    = LogModel::MOD_WC_PRODUCT_DATA;
						break;
				}

				if ( ! isset( $_POST[ $post_data ], $_POST[ $post_data ][ $index ] ) ) {
					continue;
				}

				$new_value = $_POST[ $post_data ][ $index ];

				switch ( $meta ) {
					case '_description':
						$old_value = get_post_meta( $variation_id, '_variation_description', TRUE );
						break;
					case '_stock':
						$old_value = wc_stock_amount( $variation->get_stock_quantity( 'edit' ) );
						$new_value = wc_stock_amount( wp_unslash( $_POST['variable_stock'][ $index ] ) );
						break;
					case '_regular_price':
						$old_value = $variation->get_regular_price();
						break;
					case '_sale_price':
						$old_value = $variation->get_sale_price();
						break;
					case '_backorders':
						$old_value = $variation->get_backorders();
						break;
					case '_sale_price_dates_to':
						$old_value = $variation->get_date_on_sale_to();
						break;
					case '_sale_price_dates_from':
						$old_value = $variation->get_date_on_sale_from();
						break;
					case substr( AtumGlobals::PURCHASE_PRICE_KEY, 1 ):
						$old_value = $variation->get_purchase_price();
						break;
					case 'sku':
						$old_value = $variation->get_sku();
						break;
					case 'supplier_sku':
						$old_value = $variation->get_supplier_sku();
						break;
					case 'supplier':
						$old_value = $variation->get_supplier_id();
						break;
					case '_tax_class':
						$old_value = get_post_meta( $variation_id, '_tax_class', TRUE );
						break;
					case '_shipping_class':
						if ( '-1' === $new_value ) {
							$new_value = '';
						}
						$old_value = $variation->get_shipping_class();
						break;
					case '_enabled':
						$old_value = 'publish' === $variation->get_status() ? 'on' : 'off';
						break;
					default:
						if ( is_callable( array( $variation, 'get_' . $meta ) ) ) {
							$old_value = $variation->{'get_' . $meta}();
						}
						elseif ( is_callable( array( $variation, 'get' . $meta ) ) ) {
							$old_value = $variation->{'get' . $meta}();
						}
						elseif ( FALSE !== get_post_meta( $variation_id, $meta, TRUE ) ) {
							$old_value = get_post_meta( $variation_id, $meta, TRUE );
						}
						else {
							$avoid     = TRUE;
							$old_value = NULL;
						}
						break;
				}

				if ( is_numeric( $old_value ) && str_contains( $new_value, ',' ) ) {
					$new_value = floatval( str_replace( ',', '.', $new_value ) );
				}
				$equals = ( is_numeric( $new_value ) && floatval( $new_value ) === floatval( $old_value ) ) ? TRUE : FALSE;

				if ( is_null( $old_value ) ) {
					$old_value = '';
				}

				if ( TRUE === $avoid || $old_value === $new_value || $equals ) {
					continue;
				}

				$log_data = [
					'source' => $source,
					'module' => $module,
					'entry'  => LogEntry::ACTION_PD_EDIT_2,
					'data'   => [
						'id'           => $product_id,
						'name'         => $product_name,
						'variation_id' => $variation_id,
						'field'        => $meta,
						'old_value'    => $old_value,
						'new_value'    => $new_value,
					],
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

	}

	/**
	 * Log add variation to product
	 *
	 * @since 1.0.0
	 *
	 * @param int $variation_id
	 *
	 * @throws \Exception
	 */
	public function wc_link_variation( $variation_id ) {

		$product = AtumHelpers::get_atum_product( $variation_id );

		if ( ! $product ) {
			return;
		}

		// Avoid log twice.
		remove_action( 'woocommerce_before_product_object_save', array( $this, 'wc_before_new_variation' ), 10 );
		remove_action( 'woocommerce_after_product_object_save', array( $this, 'wc_after_new_variation' ), 10 );

		$parent_id = $product->get_parent_id();
		$parent    = AtumHelpers::get_atum_product( $parent_id );

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_PRODUCT_DATA,
			'entry'  => LogEntry::ACTION_WC_VARIATION_LINK,
			'data'   => [
				'variation_id'   => $variation_id,
				'variation_name' => $product->get_name(),
				'product_id'     => $parent_id,
				'product_name'   => $parent->get_name(),
			],
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Reads and stores a new variation
	 *
	 * @param \WC_Product    $product
	 * @param \WC_Data_Store $data_store
	 *
	 * @since 1.0.1
	 */
	public function wc_before_new_variation( $product, $data_store ) {

		if ( 'variation' !== $product->get_type() || ! $product->get_parent_id() || $product->get_id() ) {
			return;
		}

		$data = [
			'product_id'     => $product->get_parent_id(),
			'variation_name' => $product->get_name(),
		];

		$transient_key = AtumCache::get_transient_key( 'log_new_variation_product_' . $product->get_parent_id() );
		AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );
	}

	/**
	 * Logs creating a new variation
	 *
	 * @since 1.0.1
	 *
	 * @param \WC_Product    $product
	 * @param \WC_Data_Store $data_store
	 *
	 * @throws \Exception
	 */
	public function wc_after_new_variation( $product, $data_store ) {

		if ( ! $product->get_parent_id() || ! $product->get_id() ) {
			return;
		}

		$parent = wc_get_product( $product->get_parent_id() );

		$transient_key = AtumCache::get_transient_key( 'log_new_variation_product_' . $product->get_parent_id() );
		$data          = AtumCache::get_transient( $transient_key, TRUE );

		if ( empty( $data ) ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_PRODUCT_DATA,
			'entry'  => LogEntry::ACTION_WC_VARIATION_LINK,
			'data'   => [
				'product_id'     => $parent->get_id(),
				'product_name'   => $parent->get_name(),
				'variation_id'   => $product->get_id(),
				'variation_name' => $product->get_name(),
			],
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Reads and stores variations from a product
	 *
	 * @since 1.0.0
	 */
	public function wc_before_delete_variations() {

		$product_id = 0;

		if ( doing_action( 'wp_ajax_woocommerce_bulk_edit_variations' ) ) {

			check_ajax_referer( 'bulk-edit-variations', 'security' );

			// Check permissions again and make sure we have what we need.
			if ( ! current_user_can( 'edit_products' ) || empty( $_POST['product_id'] ) || empty( $_POST['bulk_action'] ) ) {
				return;
			}

			if ( 'delete_all' !== wc_clean( wp_unslash( $_POST['bulk_action'] ) ) ) {
				return;
			}

			$product_id = absint( $_POST['product_id'] );

		}
		elseif ( doing_action( 'wp_ajax_woocommerce_remove_variations' ) ) {

			check_ajax_referer( 'delete-variations', 'security' );

			if ( ! current_user_can( 'edit_products' ) || ! isset( $_POST['variation_ids'] ) ) {

				return;
			}

			$variation_ids = array_map( 'absint', (array) wp_unslash( $_POST['variation_ids'] ) );

			foreach ( $variation_ids as $variation_id ) {
				if ( 'product_variation' === get_post_type( $variation_id ) ) {
					$variation = wc_get_product( $variation_id );

					if ( wc_get_product( $variation->get_parent_id() ) ) {
						$product_id = $variation->get_parent_id();
					}
				}
			}

		}
		else {
			return;
		}

		if ( $product_id ) {

			$var_list   = [];
			$variations = get_posts(
				array(
					'post_parent'    => $product_id,
					'posts_per_page' => - 1,
					'post_type'      => 'product_variation',
					'fields'         => 'ids',
					'post_status'    => AtumGlobals::get_queryable_product_statuses(),
				)
			);

			foreach ( $variations as $variation ) {
				$product    = wc_get_product( $variation );
				$var_list[] = [
					'id'   => $variation,
					'name' => $product->get_name(),
				];
			}

			$transient_key = AtumCache::get_transient_key( 'log_product_variations_' . $product_id );
			AtumCache::set_transient( $transient_key, $var_list, MINUTE_IN_SECONDS, TRUE );

		}

	}

	/**
	 * Logs removing variations from a product
	 *
	 * @since 1.0.0
	 *
	 * @throws \Exception
	 */
	public function wc_after_delete_variations() {

		check_ajax_referer( 'load-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) || empty( $_POST['product_id'] ) ) {
			return;
		}

		$product_id = absint( $_POST['product_id'] );
		$product    = wc_get_product( $product_id );

		$transient_key = AtumCache::get_transient_key( 'log_product_variations_' . $product_id );
		$old_data      = AtumCache::get_transient( $transient_key, TRUE );
		$new_data      = [];

		if ( empty( $old_data ) ) {
			return;
		}

		$variations = get_posts(
			array(
				'post_parent'    => $product_id,
				'posts_per_page' => - 1,
				'post_type'      => 'product_variation',
				'fields'         => 'ids',
				'post_status'    => AtumGlobals::get_queryable_product_statuses(),
			)
		);

		foreach ( $variations as $variation ) {
			$new_data[] = $variation;
		}

		foreach ( $old_data as $old_var ) {

			if ( FALSE === in_array( $old_var['id'], $new_data ) ) {

				$log_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_PRODUCT_DATA,
					'entry'  => LogEntry::ACTION_WC_VARIATION_DELETE,
					'data'   => [
						'variation_id'   => $old_var['id'],
						'variation_name' => $old_var['name'],
						'product_id'     => $product_id,
						'product_name'   => $product->get_name(),
					],
				];
				LogModel::maybe_save_log( $log_data );
			}
		}
		AtumCache::delete_transients( $transient_key );
	}

	/**
	 * Logs adding new comment to product
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Comment $comment
	 * @param \WP_User    $user
	 *
	 * @throws \Exception
	 */
	public function wc_set_comment( $comment, $user ) {

		if ( 'review' !== $comment->comment_type ) {
			return;
		}

		$product_id = $comment->comment_post_ID;
		$product    = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product || ! $product->exists() ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_PRODUCT_DATA,
			'entry'  => LogEntry::ACTION_WC_PRODUCT_REVIEW,
			'data'   => [
				'comment' => [
					'id'       => $comment->comment_ID,
					'content'  => $comment->comment_content,
					'approved' => $comment->comment_approved,
				],
				'user'    => [
					'id'   => $user->ID,
					'name' => $user->display_name,
				],
				'rating'  => isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0,
				'id'      => $product_id,
				'name'    => $product->get_name(),
			],
		];
		if ( 'variation' === $product->get_type() ) {
			$log_data['data']['parent'] = $product->get_parent_id();
		}
		LogModel::maybe_save_log( $log_data );
	}


	/** ======================================
	 * WC ORDERS LOGS METHODS
	 * ====================================== */

	/**
	 * Logs created orders by customer
	 *
	 * @since 0.3.1
	 *
	 * @param int       $order_id
	 * @param mixed     $posted_data
	 * @param \WC_Order $order
	 *
	 * @throws \Exception
	 */
	public function wc_new_customer_order( $order_id, $posted_data, $order ) {

		$transient_key_metadata = AtumCache::get_transient_key( 'created_order' . $order_id );
		if ( AtumCache::get_transient( $transient_key_metadata, TRUE ) ) {
			return;
		}
		AtumCache::set_transient( $transient_key_metadata, $order_id, MINUTE_IN_SECONDS, TRUE );

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'entry'  => LogEntry::ACTION_WC_ORDER_CREATE,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => '#' . $order_id,
			],
		];
		LogModel::maybe_save_log( $log_data );

		foreach ( $order->get_items() as $item_id => $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product      = $item->get_product();
			$is_variation = 'variation' === $product->get_type();

			if ( ! $product instanceof \WC_Product ) {
				continue;
			}

			if ( $product->managing_stock() && ! $item->get_meta( '_reduced_stock', TRUE ) ) {
				$data = [
					'order_id'     => $order_id,
					'order_name'   => '#' . $order_id,
					'product_id'   => $product->get_id(),
					'product_name' => $product->get_formatted_name(),
					'old_stock'    => $product->get_stock_quantity(),
					'order_source' => 'customer_order',
					'new_stock'    => $product->get_stock_quantity() - $item->get_quantity(),
					'new_status'   => $product->get_stock_status(),
				];

				if ( $is_variation ) {
					$data['product_parent'] = $product->get_parent_id();
				}

				$log_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_ORDERS,
					'entry'  => LogEntry::ACTION_WC_ORDER_STOCK_LVL,
					'data'   => $data,
				];
				LogModel::maybe_save_log( $log_data );

			}

			if ( Addons::is_addon_active( 'product_levels' ) ) {

				$linked_boms = BOMModel::get_linked_bom( $product->get_id() );

				if ( ! empty( $linked_boms ) ) {

					$data = [];

					$data['order_id']      = $order_id;
					$data['order_name']    = '#' . $order_id;
					$data['order_item_id'] = $item->get_id();

					foreach ( $linked_boms as $bom ) {
						$bom_product      = AtumHelpers::get_atum_product( $bom->bom_id );
						$is_bom_variation = 'variation' === $bom_product->get_type();

						// Check product BOMs.
						if ( AtumPlHelpers::check_bom_minimum_threshold( $bom->bom_id, $product ) ) {

							$data = [
								'product_id'   => $product->get_id(),
								'product_name' => $product->get_name(),
								'bom_id'       => $bom->bom_id,
								'bom_name'     => $bom_product->get_name(),
								'bom_data'     => (array) $bom,
							];

							if ( $is_variation ) {
								$data['product_parent'] = $product->get_parent_id();
							}
							if ( $is_bom_variation ) {
								$data['bom_parent'] = $bom_product->get_parent_id();
							}

							$log_data = [
								'source' => LogModel::SRC_PL,
								'module' => LogModel::MOD_PL_PRODUCT_DATA,
								'data'   => $data,
								'entry'  => LogEntry::ACTION_PL_BOM_MIN_THRESHOLD,
							];
							LogModel::maybe_save_log( $log_data );
						}

						$bom_list_array = array(
							'id'       => $bom->bom_id,
							'name'     => $bom_product->get_name(),
							'quantity' => $bom->qty * $item->get_quantity(),
							'stock'    => $bom_product->get_stock_quantity(),
						);
						if ( $is_bom_variation ) {
							$bom_list_array['parent'] = $bom_product->get_parent_id();
						}

						$data['bom_list'][] = apply_filters( 'atum/action_logs/order_item_extra_data', $bom_list_array, $bom->bom_id );
					}

					if ( Addons::is_addon_active( 'multi_inventory' ) && AtumMIHelpers::is_product_multi_inventory_compatible( $product ) && ! empty( Inventory::get_product_inventories( $product->get_id() ) ) ) {
						$entry = LogEntry::ACTION_PL_BOM_INV_USED;
					}
					else {
						$entry = LogEntry::ACTION_PL_BOM_USED;
					}

					$log_data = [
						'source' => LogModel::SRC_PL,
						'module' => LogModel::MOD_PL_ORDERS,
						'data'   => $data,
						'entry'  => $entry,
					];
					LogModel::maybe_save_log( $log_data );

				}
			}
		}
	}

	/**
	 * Logs created orders by customer (through the Store API)
	 *
	 * @since 1.4.3
	 *
	 * @param \WC_Order $order
	 *
	 * @throws \Exception
	 */
	public function store_api_wc_new_customer_order( $order ) {
		$this->wc_new_customer_order( $order->get_id(), NULL, $order );
	}

	/**
	 * Checks data previous values for an Order before adjusting items quantities
	 *
	 * @since 1.4.0
	 *
	 * @param integer $order_id
	 */
	public function before_order_items_save( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order instanceof \WC_Order ) {
			$this->before_order_save( $order );
		}
	}

	/**
	 * Checks data previous values for an Order
	 *
	 * @param \WC_Order                $order
	 * @param \WC_Order_Data_Store_CPT $data_store
	 *
	 * @since 0.3.1
	 */
	public function before_order_save( $order, $data_store = NULL ) {

		// Check order.
		if ( ! $order || ! $order->get_id() ) {
			return;
		}

		// Read products stock levels.
		$this->register_log_order_product_stocklevels( $order->get_id() );

		// The next logs are only for updated orders.
		if ( 'auto-draft' === $order->get_status() ) {
			return;
		}

		$metas = array();

		// Remember for order data.
		foreach ( Helpers::get_woocommerce_order_metas() as $meta ) {

			if ( 'order_status' === $meta ) {
				$metas[ $meta ] = $order->get_status();
			}
			elseif ( '_customer_provided_note' === $meta ) {
				$metas[ $meta ] = $order->get_customer_note();
			}
			elseif ( method_exists( $order, 'get_' . $meta ) ) {
				$metas[ $meta ] = $order->{"get_$meta"}();
			}
			elseif ( method_exists( $order, 'get' . $meta ) ) {
				$metas[ $meta ] = $order->{"get$meta"}();
			}
			else {
				$metas[ $meta ] = get_post_meta( $order->get_id(), $meta, TRUE );
			}

		}

		$transient_key_metadata = AtumCache::get_transient_key( 'log_metadata_order_' . $order->get_id() );
		AtumCache::set_transient( $transient_key_metadata, $metas, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Register order items to check if must be logged.
	 *
	 * @since 1.3.1
	 *
	 * @param $order_id
	 */
	private function register_log_order_product_stocklevels( $order_id ) {

		if ( apply_filters( 'atum/logs/saved_order_items_logs', FALSE ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$item_data = array();
		foreach ( $order->get_items() as $item_id => $item ) {

			/**
			 * Var definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			$item_data[ $item_id . $product_id ] = [
				'product_id' => $product_id,
				'quantity'   => $item->get_quantity(),
				'name'       => $item->get_name(),
				'tax_class'  => $item->get_tax_class(),
				'subtotal'   => $item->get_subtotal(),
				'total'      => $item->get_total(),
			];

			$product = wc_get_product( $product_id );

			if ( $product instanceof \WC_Product && $product->exists() ) {
				$item_data[ $item_id . $product_id ]['stock']  = $product->get_stock_quantity();
				$item_data[ $item_id . $product_id ]['status'] = $product->get_stock_status();
			}

			$item_data = apply_filters( 'atum/logs/product_stock_levels', $item_data, $item_id, $product );

		}

		if ( ! empty( $item_data ) ) {
			$transient_key_stocklevel = AtumCache::get_transient_key( 'log_product_stocklevels_order_' . $order->get_id() );
			$old_items                = AtumCache::get_transient( $transient_key_stocklevel, TRUE );
			if ( empty( $old_items ) ) {
				AtumCache::set_transient( $transient_key_stocklevel, $item_data, MINUTE_IN_SECONDS, TRUE );
			}
		}
	}

	/**
	 * Logs create WC order
	 *
	 * @since 0.3.1
	 *
	 * @param int       $order_id
	 * @param \WC_Order $order
	 *
	 * @throws \Exception
	 */
	public function wc_create_order( $order_id, $order = NULL ) {

		$order = $order ?: wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		remove_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'wc_api_after' ), PHP_INT_MAX );

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'entry'  => LogEntry::ACTION_WC_ORDER_CREATE_M,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => '#' . $order_id,
			],
		];
		LogModel::maybe_save_log( $log_data );

		$this->check_order_item_product_stocklevels( $order_id );

	}

	/**
	 * Log stock levels product changes
	 *
	 * @since 1.0.8
	 *
	 * @param int            $item_id
	 * @param \WC_Order_Item $item
	 * @param int            $order_id
	 */
	public function wc_after_add_order_item( $item_id, $item, $order_id ) {

		$order = wc_get_order( $order_id );

		if ( empty( $order ) || 'line_item' !== $item->get_type() )
			return;

		/**
		 * Variable definition
		 *
		 * @var \WC_Order_Item_Product $item
		 */
		$product = $item->get_product();

		if ( $product instanceof \WC_Product && $product->exists() ) {

			do_action( 'atum/logs/log_product_stock_levels', $product, $order_id );
			Helpers::log_product_stock_levels( $product, $order_id );
		}

	}

	/**
	 * Log stock levels product changes
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Order $order
	 */
	public function wc_order_stock( $order ) {

		$this->check_order_item_product_stocklevels( $order->get_id() );

	}

	/**
	 * Log stock levels product changes
	 *
	 * @since 1.0.8
	 *
	 * @param int $order_id
	 */
	private function check_order_item_product_stocklevels( $order_id ) {

		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			$product = $item->get_product();
			do_action( 'atum/logs/log_product_stock_levels', $product, $order_id );
			Helpers::log_product_stock_levels( $product, $order_id );
		}

	}

	/**
	 * Check previous product stock levels before API request
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Order_Item_Product $item
	 * @param array                 $posted
	 */
	public function add_new_order_items( $item, $posted ) {

		if ( ! $item->is_type( 'line_item' ) ) {
			return;
		}

		$product = $item->get_product();
		do_action( 'atum/logs/check_product_stock_levels', $product );
		Helpers::check_product_stock_levels( $product );

	}

	/**
	 * Logs edit WC_Order
	 *
	 * @since 0.3.1
	 *
	 * @param int       $order_id
	 * @param \WC_Order $order
	 *
	 * @throws \Exception
	 */
	public function after_order_save( $order_id, $order = NULL ) {

		$order = $order ?: wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Order data block.
		$transient_key_metadata = AtumCache::get_transient_key( 'log_metadata_order_' . $order_id );
		$old_metas              = AtumCache::get_transient( $transient_key_metadata, TRUE );

		if ( ! empty( $old_metas ) && FALSE === in_array( $order->get_status(), [ 'auto-draft', 'draft' ] ) ) {

			$updates = Helpers::check_order_details( $order, $old_metas );

			foreach ( $updates as $update ) {

				$log_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_ORDERS,
					'data'   => [
						'order_id'   => $order_id,
						'order_name' => '#' . $order_id,
						'field'      => $update['field'],
						'old_value'  => $update['old_value'],
						'new_value'  => $update['new_value'],
					],
					'entry'  => 'order_status' === $update['field'] ? LogEntry::ACTION_WC_ORDER_STATUS : LogEntry::ACTION_WC_ORDER_DATA,
				];
				LogModel::maybe_save_log( $log_data );

			}
		}
		AtumCache::delete_transients( $transient_key_metadata );

		// Do not log add-items before saving a new order.
		if ( FALSE !== in_array( $order->get_status(), [ 'auto-draft', 'draft' ] ) ) {
			return;
		}


		// Check for changes on products stock levels.
		$this->check_order_item_product_stocklevels( $order_id );

	}

	/**
	 * Log WC order items changes.
	 *
	 * @since 1.3.5
	 *
	 * @param \WC_Order $order
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function wc_saved_order_items( $order ) {

		if ( ! $order instanceof \WC_Order ) {
			$order = wc_get_order( $order );
		}
		$order_id = $order->get_id();

		$transient_key_stocklevel = AtumCache::get_transient_key( 'log_product_stocklevels_order_' . $order_id );
		$old_items                = AtumCache::get_transient( $transient_key_stocklevel, TRUE );
		$product_changes          = array();

		if ( ! empty( $old_items ) ) {

			foreach ( $old_items as $oid => $old_item ) {

				$product_id = $old_item['product_id'];
				$product    = wc_get_product( $product_id );

				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				$new_stock  = $product->get_stock_quantity();
				$new_status = $product->get_stock_status();

				if ( floatval( $old_item['stock'] ) !== floatval( $new_stock ) || $old_item['status'] !== $new_status ) {
					$product_changes[ $oid ] = [
						'product_id'   => $product_id,
						'product_name' => $product->get_name(),
						'old_stock'    => $old_item['stock'],
						'new_stock'    => $product->get_stock_quantity(),
						'old_status'   => $old_item['status'],
						'new_status'   => $product->get_stock_status(),
					];

					if ( 'variation' === $product->get_type() ) {
						$product_changes[ $oid ]['product_parent'] = $product->get_parent_id();
					}
				}
			}
		}

		foreach ( $order->get_items() as $item_id => $item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */

			if ( ! $item->is_type( 'line_item' ) ) {
				continue;
			}

			$product_id = $item->get_variation_id() ?: $item->get_product_id();

			if ( ! apply_filters( 'atum/logs/saved_order_items_logs', FALSE ) && ! isset( $old_items[ $item_id . $product_id ] ) ) {

				// Add new line_item log.
				$this->wc_add_order_item( $item_id, $item, $order );

			}
			//else {
			if ( isset( $old_items[ $item_id . $product_id ] ) ) {

				foreach ( [ 'quantity', 'name', 'tax_class', 'subtotal', 'total' ] as $field ) {

					$new_field = $item->{'get_'.$field}();

					$old_field = isset( $old_items[ $item_id . $product_id ][ $field ] ) ? $old_items[ $item_id . $product_id ][ $field ] : FALSE;

					if ( ( empty( $old_field ) && empty( $new_field ) ) || $old_field === $new_field )
						continue;

					$log_processed_data = [
						'source' => LogModel::SRC_WC,
						'module' => LogModel::MOD_WC_ORDERS,
						'data'   => [
							'order_id'   => $order_id,
							'order_name' => '#' . $order_id,
							'item_id'    => $item_id,
							'item_name'  => $item->get_name(),
							'field'      => $field,
							'old_value'  => $old_field,
							'new_field'  => $new_field,
						],
						'entry'  => LogEntry::ACTION_WC_ORDER_ITEM_EDIT,
					];
					LogModel::maybe_save_log( $log_processed_data );
				}

			}

			$product = $item->get_product();

			if ( ! $product instanceof \WC_Product || ! $product->managing_stock() ) {
				continue;
			}

			if ( FALSE === in_array( $item_id . $product_id, array_keys( $product_changes ) ) ) {

				$old_stock  = empty( $old_items ) || ! isset( $old_items[ $item_id . $product_id ] ) ? FALSE : $old_items[ $item_id . $product_id ]['stock'];
				$old_status = empty( $old_items ) || ! isset( $old_items[ $item_id . $product_id ] ) ? FALSE : $old_items[ $item_id . $product_id ]['status'];
				$new_stock  = $product->get_stock_quantity();

				if ( ! empty( $old_items ) && ( floatval( $new_stock ) !== floatval( $old_stock ) || $old_status !== $product->get_stock_status() ) ) {

					$product_changes[ $item_id . $product_id ] = [
						'product_id'   => $product_id,
						'product_name' => $product->get_formatted_name(),
						'old_stock'    => $old_stock,
						'new_stock'    => $new_stock,
						'old_status'   => $old_status,
						'new_status'   => $product->get_stock_status(),
					];

					if ( 'variation' === $product->get_type() ) {
						$product_changes[ $item_id . $product_id ]['product_parent'] = $product->get_parent_id();
					}

				}
			}
		}

		if ( ! empty( $product_changes ) && ! apply_filters( 'atum/logs/saved_order_items_logs', FALSE ) ) {
			foreach ( $product_changes as $product_change ) {

				$product_change['order_name']   = '#' . $order_id;
				$product_change['order_id']     = $order_id;
				$product_change['order_source'] = 'admin_save';
				$log_data                       = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_ORDERS,
					'entry'  => LogEntry::ACTION_WC_ORDER_STOCK_LVL,
					'data'   => $product_change,
				];
				LogModel::maybe_save_log( $log_data );
			}
			AtumCache::delete_transients( $transient_key_stocklevel );

			// Prevent to log order again.
			add_filter( 'atum/logs/saved_order_items_logs', '__return_true' );
		}

	}

	/**
	 * Checks for previous values in several status orders
	 *
	 * @since 0.3.1
	 *
	 * @param array  $ids
	 * @param string $action
	 * @param string $entity
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function wc_before_bulk_order_status( $ids, $action, $entity ) {

		if ( ! str_contains( $action, 'mark_' ) || 'order' !== $entity ) {
			return $ids;
		}

		remove_action( 'woocommerce_update_order', array( $this, 'after_order_save' ), PHP_INT_MAX );

		$data       = [];
		$new_status = substr( $action, 5 );

		foreach ( $ids as $id ) {
			$order       = wc_get_order( $id );
			$data[ $id ] = [
				'old_status' => $order->get_status(),
				'new_status' => $new_status,
			];
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'data'   => [
				'order_list'     => $ids,
				'status_changes' => $data,
			],
			'entry'  => LogEntry::ACTION_WC_ORDER_ST_BULK,
		];
		LogModel::maybe_save_log( $log_data );

		return $ids;

	}

	/**
	 * Register previous values for products stock levels
	 *
	 * @since 1.0.7
	 */
	public function before_add_order_item() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		if ( ! isset( $_POST['order_id'], $_POST['data'] ) ) {
			return;
		}

		$order_id = absint( wp_unslash( $_POST['order_id'] ) );

		$items_to_add = isset( $_POST['data'] ) ? array_filter( wp_unslash( (array) $_POST['data'] ) ) : array();

		foreach ( $items_to_add as $item ) {

			if ( ! isset( $item['id'], $item['qty'] ) || empty( $item['id'] ) ) {
				continue;
			}
			$product_id = absint( $item['id'] );
			$product    = wc_get_product( $product_id );
			//$product    = AtumHelpers::get_atum_product( $product_id );

			// Save previous stock value.
			$data                   = [
				'stock'  => $product->get_stock_quantity(),
				'status' => $product->get_stock_status(),
			];
			$transient_key_metadata = AtumCache::get_transient_key( 'log_stock_level_product_' . $product_id . '_order_' . $order_id );
			AtumCache::set_transient( $transient_key_metadata, $data, MINUTE_IN_SECONDS, TRUE );

		}

	}

	/**
	 * Logs adding a line item to a wc order
	 *
	 * @since 0.3.1
	 *
	 * @param int                    $item_id
	 * @param \WC_Order_Item_Product $item
	 * @param \WC_Order              $order
	 *
	 * @throws \Exception
	 */
	public function wc_add_order_item( $item_id, $item, $order ) {

		if ( $item_id && ! $item instanceof \WC_Order_Item_Product ) {
			$item = $order->get_item( $item_id );
		}

		if ( ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}

		$product = $item->get_product();

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$data = [
			'order_id'     => $order->get_id(),
			'order_name'   => '#' . $order->get_id(),
			'product_id'   => $item->get_product_id(),
			'product_name' => $product->get_name(),
			'qty'          => $item->get_quantity(),
		];

		if ( 'variation' === $product->get_type() ) {
			$data['product_parent'] = $product->get_parent_id();
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'entry'  => LogEntry::ACTION_WC_ORDER_ADD_PRODUCT,
			'data'   => $data,
		];
		LogModel::maybe_save_log( $log_data );

		if ( doing_action( 'woocommerce_ajax_add_order_item_meta' ) ) {
			// Register order_items after ajax to avoid logging same product several times.
			$this->register_log_order_product_stocklevels( $order->get_id() );
		}

		// Save previous stock value.
		$transient_key_metadata = AtumCache::get_transient_key( 'log_stock_level_product_' . $product->get_id() . '_order_' . $order->get_id() );
		$old_data               = AtumCache::get_transient( $transient_key_metadata, TRUE );

		if ( ! empty( $old_data ) && isset( $old_data['stock'] ) && ( floatval( $old_data['stock'] ) !== $product->get_stock_quantity() || $old_data['status'] != $product->get_stock_status() ) ) {

			$product_change = [
				'product_id'   => $product->get_id(),
				'product_name' => $product->get_name(),
				'old_stock'    => $old_data['stock'],
				'new_stock'    => $product->get_stock_quantity(),
				'old_status'   => $old_data['status'],
				'new_status'   => $product->get_stock_status(),
				'order_id'     => $order->get_id(),
				'order_name'   => '#' . $order->get_id(),
			];

			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_WC_ORDERS,
				'entry'  => LogEntry::ACTION_WC_ORDER_CH_STOCK_LVL,
				'data'   => $product_change,
			];
			LogModel::maybe_save_log( $log_data );

		}

		AtumCache::delete_transients( $transient_key_metadata );

	}

	/**
	 * Logs a fee/shipping/tax addition to an order
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_add_order_fee_shipping() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return;
		}

		$order_id = intval( $_POST['order_id'] );
		$data     = [
			'order_id'   => $order_id,
			'order_name' => '#' . $order_id,
		];

		if ( doing_action( 'wp_ajax_woocommerce_add_order_fee' ) ) {
			$entry          = LogEntry::ACTION_WC_ORDER_ADD_FEE;
			$data['amount'] = $_POST['amount'] ?: 0;
			$data['entity'] = __( 'Fee', ATUM_LOGS_TEXT_DOMAIN );
		}
		elseif ( doing_action( 'wp_ajax_woocommerce_add_order_shipping' ) ) {
			$entry = LogEntry::ACTION_WC_ORDER_ADD_SHIP;
		}
		elseif ( doing_action( 'wp_ajax_woocommerce_add_order_tax' ) ) {
			$entry           = LogEntry::ACTION_WC_ORDER_ADD_TAX;
			$data['rate_id'] = $_POST['rate_id'];
			$data['entity']  = __( 'Tax', ATUM_LOGS_TEXT_DOMAIN );
		}

		if ( isset( $entry ) ) {
			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_WC_ORDERS,
				'data'   => $data,
				'entry'  => $entry,
			];
			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Logs an order item deletion from an order
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_delete_order_item() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['order_item_ids'] ) ) {
			return;
		}

		$order_id       = intval( $_POST['order_id'] );
		$removed        = [];
		$list           = [];
		$order_item_ids = wp_unslash( $_POST['order_item_ids'] );

		if ( ! is_array( $order_item_ids ) && is_numeric( $order_item_ids ) ) {
			$order_item_ids = array( $order_item_ids );
		}

		$order       = wc_get_order( $order_id );
		$order_items = $order->get_items( [ 'line_item', 'tax', 'shipping', 'fee' ] );

		foreach ( $order_item_ids as $order_item_id ) {

			if ( isset( $order_items[ $order_item_id ] ) && $order_items[ $order_item_id ] instanceof \WC_Order_Item ) {
				$removed[ $order_item_id ] = [
					'item_id' => $order_item_id,
					'type'    => Helpers::get_order_item_type( $order_items[ $order_item_id ] ),
				];
				$list[]                    = $order_items[ $order_item_id ]->get_name();
			}
		}

		if ( ! empty( $list ) ) {
			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_WC_ORDERS,
				'data'   => [
					'order_id'   => $order_id,
					'order_name' => '#' . $order_id,
					'list'       => $list,
					'details'    => $removed,
				],
				'entry'  => LogEntry::ACTION_WC_ORDER_ITEM_DELETE,
			];
			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Logs order items changes
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_save_order_items() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['items'] ) ) {
			return;
		}

		$fields = [
			'order_item_tax_class'  => 'tax_class',
			'order_item_qty'        => 'quantity',
			'line_subtotal'         => 'subtotal',
			'line_total'            => 'total',
			'shipping_method'       => 'method_id',
			'shipping_method_title' => 'method_title',
			'shipping_cost'         => 'total',
			'order_item_name'       => 'name',
		];

		$order_id    = intval( $_POST['order_id'] );
		$order       = wc_get_order( $order_id );
		$order_items = $order->get_items( [ 'line_item', 'tax', 'shipping', 'fee' ] );
		$item_data   = [];
		foreach ( $order_items as $order_item ) {
			$item_data[ $order_item->get_id() ] = $order_item->get_data();
		}

		$data_to_save = array();
		wp_parse_str( wp_unslash( $_POST['items'] ), $data_to_save );

		foreach ( $data_to_save as $i => $idata ) {
			switch ( $i ) {
				case 'meta_key':
					foreach ( $idata as $item_id => $value ) {
						if ( ! isset( $order_items[ $item_id ] ) ) {
							continue;
						}
						$obj = $order_items[ $item_id ];
						foreach ( $value as $imeta => $meta_name ) {
							if ( '' === $data_to_save['meta_value'][ $item_id ][ $imeta ] && ! strstr( $imeta, 'new-' ) ) {
								$old_metas       = $obj->get_formatted_meta_data();
								$old_meta        = $old_metas[ $imeta ];
								$log_delete_meta = [
									'source' => LogModel::SRC_WC,
									'module' => LogModel::MOD_WC_ORDERS,
									'data'   => [
										'order_id'   => $order_id,
										'order_name' => '#' . $order_id,
										'item_id'    => $item_id,
										'item_name'  => $obj->get_name(),
										'meta_field' => $old_meta->key,
										'meta_value' => $old_meta->value,
									],
									'entry'  => LogEntry::ACTION_WC_ORDER_DEL_ITEM_META,
								];
								LogModel::maybe_save_log( $log_delete_meta );
								continue;
							}
							if ( $data_to_save['meta_value'][ $item_id ][ $imeta ] === $obj->get_meta( $meta_name ) ) {
								continue;
							}
							$log_data[] = [
								'field' => 'meta',
								'data'  => [
									'order_id'   => $order_id,
									'order_name' => '#' . $order_id,
									'item_id'    => $item_id,
									'item_name'  => $obj->get_name(),
									'field'      => $meta_name,
									'old_value'  => $obj->get_meta( $meta_name ),
									'new_value'  => $data_to_save['meta_value'][ $item_id ][ $imeta ],
								],
							];
						}
					}
					break;
				case 'line_total':
				case 'order_item_tax_class':
				case 'order_item_qty':
				case 'shipping_method':
				case 'shipping_method_title':
				case 'shipping_cost':
				case 'order_item_name':
					if ( ! isset( $fields[ $i ] ) ) {
						break;
					}
					$search_field = $fields[ $i ];
					foreach ( $idata as $item_id => $value ) {
						if ( ! isset( $order_items[ $item_id ] ) ) {
							continue;
						}
						$obj = $order_items[ $item_id ];
						if ( in_array( $i, [ 'line_total', 'shipping_cost' ] ) && str_contains( $value, ',' ) ) {
							$value = floatval( str_replace( ',', '.', $value ) );
						}
						if ( $value === $item_data[ $item_id ][ $search_field ] ) {
							continue;
						}
						if ( in_array( $i, [
								'order_item_qty',
								'shipping_cost',
							] ) && floatval( $value ) === floatval( $item_data[ $item_id ][ $search_field ] ) ) {
							continue;
						}
						$log_data[ $i . $item_id ] = [
							'field' => 'field',
							'data'  => [
								'order_id'      => $order_id,
								'order_name'    => '#' . $order_id,
								'item_id'       => $item_id,
								'item_name'     => $obj->get_name(),
								'field'         => $i,
								'field_changed' => $i,
								'old_value'     => $item_data[ $item_id ][ $search_field ],
								'new_value'     => $value,
							],
						];
						if ( 'line_total' === $i ) {
							$log_data[ $i . $item_id ]['data']['field'] = __( 'price', ATUM_LOGS_TEXT_DOMAIN );
						}
						elseif ( 'order_item_tax_class' === $i ) {
							$log_data[ $i . $item_id ]['data']['field'] = __( 'tax', ATUM_LOGS_TEXT_DOMAIN );
						}
						elseif ( 'order_item_qty' === $i ) {
							$log_data[ $i . $item_id ]['data']['field'] = __( 'quantity', ATUM_LOGS_TEXT_DOMAIN );
						}
					}
					break;
				case 'line_tax':
				case 'shipping_taxes':
					foreach ( $idata as $item_id => $taxes ) {
						if ( ! isset( $order_items[ $item_id ] ) ) {
							continue;
						}
						$obj        = $order_items[ $item_id ];
						$taxes_data = [];
						foreach ( $taxes as $t => $value ) {
							if ( str_contains( $value, ',' ) ) {
								$value = floatval( str_replace( ',', '.', $value ) );
							}
							$old_tax = ! empty( $item_data[ $item_id ]['taxes']['total'][ $t ] ) ? $item_data[ $item_id ]['taxes']['total'][ $t ] : NULL;
							if ( '' === $value && is_null( $old_tax ) ) {
								continue;
							}
							$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $old_tax ) );
							if ( $value === $old_tax || $equals ) {
								continue;
							}
							$taxes_data[] = [
								'id'        => $t,
								'tax'       => \WC_Tax::get_rate_label( $t ),
								'old_value' => $old_tax,
								'new_value' => $value,
							];
						}
						if ( ! empty( $taxes_data ) ) {
							$log_data[ $i . $item_id ] = [
								'field' => 'field',
								'data'  => [
									'order_id'      => $order_id,
									'order_name'    => '#' . $order_id,
									'item_id'       => $item_id,
									'item_name'     => $obj->get_name(),
									'field'         => __( 'tax', ATUM_LOGS_TEXT_DOMAIN ),
									'field_changed' => $i,
									'values'        => $taxes_data,
								],
							];
						}
					}
					break;
			}
		}

		if ( ! empty( $log_data ) ) {
			foreach ( $log_data as $log ) {
				$log_processed_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_ORDERS,
					'data'   => $log['data'],
					'entry'  => 'meta' === $log['field'] ? LogEntry::ACTION_WC_ORDER_ITEM_META : LogEntry::ACTION_WC_ORDER_ITEM_EDIT,
				];
				LogModel::maybe_save_log( $log_processed_data );
			}
		}

		// Log for totals.
		$total = [
			'total'          => 0,
			'shipping_total' => 0,
			'total_tax'      => 0,
			'discount_total' => 0,
		];
		if ( ! empty( $data_to_save['line_total'] ) ) {
			foreach ( $data_to_save['line_total'] as $val ) {
				$total['total'] += floatval( str_replace( ',', '.', $val ) );
			}
		}
		if ( ! empty( $data_to_save['line_tax'] ) ) {
			foreach ( $data_to_save['line_tax'] as $taxes ) {
				if ( ! empty( $taxes ) ) {
					foreach ( $taxes as $tax ) {
						$total['total_tax'] += floatval( str_replace( ',', '.', $tax ) );
						$total['total']     += floatval( str_replace( ',', '.', $tax ) );
					}
				}
			}
		}
		if ( ! empty( $data_to_save['shipping_taxes'] ) ) {
			foreach ( $data_to_save['shipping_taxes'] as $taxes ) {
				if ( ! empty( $taxes ) ) {
					foreach ( $taxes as $tax ) {
						$total['total_tax'] += floatval( str_replace( ',', '.', $tax ) );
						$total['total']     += floatval( str_replace( ',', '.', $tax ) );
					}
				}
			}
		}
		if ( ! empty( $data_to_save['shipping_cost'] ) ) {
			foreach ( $data_to_save['shipping_cost'] as $ship ) {
				$total['shipping_total'] += floatval( str_replace( ',', '.', $ship ) );
				$total['total']          += floatval( str_replace( ',', '.', $ship ) );
			}
		}
		if ( ! empty( $data_to_save['refund_line_tax'] ) ) {
			foreach ( $data_to_save['refund_line_tax'] as $refunds ) {
				if ( ! empty( $refunds ) ) {
					foreach ( $refunds as $refund ) {
						$total['discount_total'] += floatval( str_replace( ',', '.', $refund ) );
						$total['total']          -= floatval( str_replace( ',', '.', $refund ) );
					}
				}
			}
		}

		$change = [];

		foreach ( $total as $meta => $value ) {
			switch ( $meta ) {
				case 'total_tax':
					$old_total = $order->get_total_tax();
					break;
				case 'shipping_total':
					$old_total = $order->get_shipping_total();
					break;
				case 'discount_total':
					$old_total = $order->get_discount_total();
					break;
				default: // Total.
					$old_total = $order->get_total();
					break;
			}

			if ( wc_price( $value ) !== wc_price( $old_total ) ) {
				$change['old_values'][ $meta ] = $old_total;
				$change['new_values'][ $meta ] = $value;
			}

		}

		if ( ! empty( $change ) ) {
			$log_data = [
				'module' => LogModel::MOD_WC_ORDERS,
				'source' => LogModel::SRC_WC,
				'entry'  => LogEntry::ACTION_WC_ORDER_TOTALS,
				'data'   => [
					'order_id'   => $order_id,
					'order_name' => '#' . $order_id,
					'totals'     => $change,
				],
			];
			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Logs adding notes to order
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_add_order_note() {

		check_ajax_referer( 'add-order-note', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['post_id'], $_POST['note'], $_POST['note_type'] ) ) {
			return;
		}

		$post_id = absint( $_POST['post_id'] );
		$note    = wp_kses_post( trim( wp_unslash( $_POST['note'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'data'   => [
				'order_id'   => $post_id,
				'order_name' => '#' . $post_id,
				'note'       => $note,
			],
			'entry'  => LogEntry::ACTION_WC_ORDER_ADD_NOTE,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs coupons applicating on wc orders
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function wc_add_coupon_discount() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || empty( $_POST['coupon'] ) ) {
			return;
		}

		/**
		 * Var definition
		 *
		 * @var \WC_Coupon $coupon
		 */
		$code     = wc_format_coupon_code( wp_unslash( $_POST['coupon'] ) );
		$coupon   = new \WC_Coupon( $code );
		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$order    = wc_get_order( $order_id );

		if ( ! $order || ! $coupon ) {
			return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'entry'  => LogEntry::ACTION_WC_ORDER_ADD_COUPON,
			'data'   => [
				'order_id'    => $order_id,
				'order_name'  => '#' . $order_id,
				'coupon_id'   => $coupon->get_id(),
				'coupon_code' => $code,
			],
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs a refund
	 *
	 * @since 0.3.1
	 *
	 * @param int $order_id
	 * @param int $refund_id
	 *
	 * @throws \Exception
	 */
	public function wc_order_refund( $order_id, $refund_id ) {

		$refund   = new \WC_Order_Refund( $refund_id );
		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'entry'  => LogEntry::ACTION_WC_ORDER_ADD_REFUND,
			'data'   => [
				'order_id'   => $order_id,
				'order_name' => '#' . $order_id,
				'refund'     => [
					'id'     => $refund_id,
					'amount' => $refund->get_amount(),
				],
			],
		];
		LogModel::maybe_save_log( $log_data );

	}


	/**
	 * Logs the email notifications from WC Orders
	 *
	 * @since 0.3.1
	 *
	 * @param \WC_Order $order
	 * @param string    $action
	 *
	 * @throws \Exception
	 */
	public function wc_email_notifications( $order, $action ) {

		$auto = ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ) ? FALSE : TRUE;

		switch ( $action ) {
			case 'customer_invoice':
				if ( $order->has_status( array( 'completed', 'processing' ) ) ) {
					$entry = $auto ? LogEntry::ACTION_WC_ORDER_INV_EMAIL_A : LogEntry::ACTION_WC_ORDER_INV_EMAIL;
				}
				else {
					$entry = $auto ? LogEntry::ACTION_WC_ORDER_EMAIL_AUTO : LogEntry::ACTION_WC_ORDER_EMAIL;
				}
				break;
			case 'new_order':
				$entry = $auto ? LogEntry::ACTION_WC_ORDER_NEW_EMAIL_A : LogEntry::ACTION_WC_ORDER_NEW_EMAIL;
				break;
			default:
				return;
		}

		$log_data = [
			'source' => LogModel::SRC_WC,
			'module' => LogModel::MOD_WC_ORDERS,
			'data'   => [
				'order_id'   => $order->get_id(),
				'order_name' => '#' . $order->get_id(),
				'email_auto' => $auto,
			],
			'entry'  => $entry,
		];
		LogModel::maybe_save_log( $log_data );

	}


	/** ======================================
	 * WC COUPONS LOGS METHODS
	 * ====================================== */

	/**
	 * Checks data previous values for a Coupon
	 *
	 * @param int   $post_ID
	 * @param mixed $data
	 *
	 * @since 0.3.1
	 */
	public function before_coupon_save( $post_ID, $data ) {

		$post = get_post( $post_ID );

		if ( 'shop_coupon' !== $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}

		$coupon_data = [];

		// Remember for coupon data.
		foreach ( Helpers::get_woocommerce_coupon_data() as $property ) {
			if ( 'code' === $property ) {
				$value = $post->post_title;
			}
			elseif ( 'description' === $property ) {
				$value = $post->post_excerpt;
			}
			else {
				$value = get_post_meta( $post_ID, $property, TRUE );
			}
			$coupon_data[ $property ] = $value;
		}

		$transient_key_metadata = AtumCache::get_transient_key( 'log_data_coupon_' . $post_ID );
		AtumCache::set_transient( $transient_key_metadata, $coupon_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Logs edit WC_Coupon
	 *
	 * @since 0.3.1
	 *
	 * @param int      $post_ID
	 * @param \WP_Post $post
	 * @param mixed    $update
	 *
	 * @throws \Exception
	 */
	public function after_coupon_save( $post_ID, $post, $update ) {

		if ( 'shop_coupon' !== $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}

		if ( isset( $_POST, $_POST['original_post_status'] ) && 'auto-draft' === $_POST['original_post_status'] ) {

			$log_data = [
				'source' => LogModel::SRC_WC,
				'module' => LogModel::MOD_COUPONS,
				'data'   => [
					'id'            => $post_ID,
					'name'          => $post->post_title,
					'desc'          => $post->post_excerpt,
					'coupon_amount' => floatval( $_POST['coupon_amount'] ),
					'free_shipping' => isset( $_POST['free_shipping'] ) ? esc_attr( $_POST['free_shipping'] ) : FALSE,
					'expiry_date'   => esc_attr( $_POST['expiry_date'] ),
				],
				'entry'  => LogEntry::ACTION_WC_COUPON_CREATE,
			];
			LogModel::maybe_save_log( $log_data );

			return;
		}

		// Order data block.
		$transient_key_metadata = AtumCache::get_transient_key( 'log_data_coupon_' . $post_ID );
		$old_data               = AtumCache::get_transient( $transient_key_metadata, TRUE );

		if ( ! empty( $old_data ) ) {
			foreach ( Helpers::get_woocommerce_coupon_data() as $property ) {
				if ( 'code' === $property ) {
					$new_value = $post->post_title;
				}
				elseif ( 'description' === $property ) {
					$new_value = $post->post_excerpt;
				}
				else {
					$new_value = get_post_meta( $post_ID, $property, TRUE );
				}

				$equals = ( is_numeric( $new_value ) && floatval( $new_value ) === floatval( $old_data[ $property ] ) );

				if ( $old_data[ $property ] !== $new_value && ! $equals ) {

					switch ( $property ) {
						case 'product_ids':
						case 'exclude_product_ids':
						case 'excluded_product_ids':
							$oval = [];
							$nval = [];
							$olst = str_contains( $old_data[ $property ], ',' ) ? explode( ',', $old_data[ $property ] ) : [ $old_data[ $property ] ];
							$nlst = str_contains( $new_value, ',' ) ? explode( ',', $new_value ) : [ $new_value ];
							if ( ! empty( $olst ) ) {
								foreach ( $olst as $val ) {
									$product = wc_get_product( $val );
									if ( $product instanceof \WC_Product && $product->exists() ) {
										$oval[] = [
											'id'   => $val,
											'name' => $product->get_name(),
										];
									}
								}
							}

							if ( ! empty( $nlst ) ) {
								foreach ( $nlst as $val ) {
									$product = wc_get_product( $val );
									if ( $product instanceof \WC_Product && $product->exists() ) {
										$nval[] = [
											'id'   => $val,
											'name' => $product->get_name(),
										];
									}
								}
							}
							break;
						case 'product_categories':
						case 'exclude_product_categories':
						case 'excluded_product_categories':
							$oval = [];
							$nval = [];
							foreach ( $old_data[ $property ] as $val ) {
								$product = get_term( $val );
								$oval[]  = [
									'id'   => $val,
									'name' => $product->name,
								];
							}
							foreach ( $new_value as $val ) {
								$product = get_term( $val );
								$nval[]  = [
									'id'   => $val,
									'name' => $product->name,
								];
							}
							break;
						default:
							$oval = $old_data[ $property ];
							$nval = $new_value;
							break;
					}

					$log_data = [
						'source' => LogModel::SRC_WC,
						'module' => LogModel::MOD_COUPONS,
						'entry'  => LogEntry::ACTION_WC_COUPON_EDIT,
						'data'   => [
							'id'        => $post_ID,
							'name'      => $post->post_title,
							'field'     => $property,
							'old_value' => $oval,
							'new_value' => $nval,
						],
					];
					LogModel::maybe_save_log( $log_data );
				}
			}
		}
		AtumCache::delete_transients( $transient_key_metadata );
	}

	/** ======================================
	 * WC SETTINGS LOGS METHODS
	 * ====================================== */

	/**
	 * Logs settings changes
	 *
	 * @since 0.3.1
	 *
	 * @param mixed $value
	 * @param array $option
	 * @param mixed $raw_value
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function wc_save_settings( $value, $option, $raw_value ) {

		if ( isset( $option['id'] ) && isset( $option['type'] ) && ! ( isset( $option['is_option'] ) && FALSE === $option['is_option'] ) ) {

			if ( strstr( $option['id'], '[' ) ) {
				parse_str( $option['id'], $option_name_array );
				$option_name = current( array_keys( $option_name_array ) );
			}
			else {
				$option_name = $option['id'];
			}

			$old_value = get_option( $option_name, NULL );

			$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $old_value ) );

			if ( $value !== $old_value && ! $equals ) {
				$log_data = [
					'source' => LogModel::SRC_WC,
					'module' => LogModel::MOD_WC_SETTINGS,
					'entry'  => LogEntry::ACTION_WC_SETTINGS,
					'data'   => [
						'name'      => isset( $option['title'] ) ? $option['title'] : $option_name,
						'option'    => $option_name,
						'new_value' => $value,
						'old_value' => $old_value,
					],
				];
				LogModel::maybe_save_log( $log_data );
			}
		}

		return $value;
	}

	/** ======================================
	 * WC API LOGS METHODS
	 * ====================================== */

	/**
	 * Check previous values for an object in API Request
	 *
	 * @since 1.0.7
	 *
	 * @param \WC_Data         $object  Object object.
	 * @param \WP_REST_Request $request Request object.
	 *
	 * @return \WC_Data
	 */
	public function wc_v1_api_before( $object, $request ) {

		return $this->wc_api_before( $object, $request, isset( $request['id'] ) && absint( $request['id'] ) ? FALSE : TRUE );
	}

	public function wc_api_order_before( $response, $handler, $request ) {

		if ( str_contains( $request->get_route(), '/wc/v3/orders' ) ) {
			$order_id = absint( str_replace( '/wc/v3/orders/', '', $request->get_route() ) );

			if ( $order_id )
				//$this->wc_api_before( wc_get_order( $order_id ), $request, FALSE );
				$this->before_order_save( wc_get_order( $order_id ) );
		}

		return $response;

	}


	/**
	 * Check previous values for an object in API Request
	 *
	 * @since 1.0.7
	 *
	 * @param \WC_Data|\WP_Post $object   Object object.
	 * @param \WP_REST_Request  $request  Request object.
	 * @param bool              $creating If it is creating a new object.
	 *
	 * @return \WC_Data|\WP_Post
	 */
	public function wc_api_before( $object, $request, $creating ) {

		if ( is_null( $object ) || is_wp_error( $object ) || TRUE === $creating ) {
			return $object;
		}

		$object_id = $object instanceof \WP_Post ? $object->ID : $object->get_id();
		$post_obj  = get_post( $object_id );
		$post_type = $post_obj->post_type;

		switch ( $post_type ) {
			case 'product':
			case 'product_variation':
				$this->before_product_save( $object_id, $request );
				break;
			case InventoryLogs::POST_TYPE:
				$meta_data = get_metadata( 'post', $object_id, '', TRUE );
				$meta_data['_description'][0]  = $post_obj->post_content;
				$meta_data['_date_created'][0] = $post_obj->post_date;
				$meta_data['_status'][0]       = $object->get_status();

				$transient_key = AtumCache::get_transient_key( 'log_api_inventory_log_' . $object_id );
				AtumCache::set_transient( $transient_key, $meta_data, MINUTE_IN_SECONDS, TRUE );
				break;
			case PurchaseOrders::POST_TYPE:
				$meta_data = get_metadata( 'post', $object_id, '', TRUE );
				$meta_data['_description'][0] = $post_obj->post_content;
				$meta_data['_date'][0]        = $post_obj->post_date;
				$meta_data['_status'][0]      = $object->get_status();

				$transient_key = AtumCache::get_transient_key( 'log_api_purchase_order_' . $object_id );
				AtumCache::set_transient( $transient_key, $meta_data, MINUTE_IN_SECONDS, TRUE );
				break;
			case 'shop_order':
				//$this->before_order_save( $object_id, [] );
				break;
		}

		return $object;

	}

	/**
	 * Logs updated values for an object in API Request
	 *
	 * @since 1.0.7
	 *
	 * @param \WC_Data         $object   Object object.
	 * @param \WP_REST_Request $request  Request object.
	 * @param bool             $creating If it is creating a new object.
	 *
	 * @return \WC_Data
	 *
	 * @throws \Exception
	 */
	public function wc_api_after( $object, $request, $creating ) {

		if ( is_null( $object ) || is_wp_error( $object ) ) {
			return $object;
		}

		$object_id = $object instanceof \WP_Post ? $object->ID : $object->get_id();

		if ( AtumHelpers::is_using_hpos_tables() && OrderUtil::is_order( $object_id ) ) {
			$type = OrderUtil::get_order_type( $object_id );
		}
		else {
			$post_obj = get_post( $object_id );
			$type     = $post_obj->post_type;
		}

		switch ( $type ) {
			case 'product':
			case 'product_variation':
				$this->after_product_save( $object_id, $post_obj, $creating === FALSE );
				break;
			case InventoryLogs::POST_TYPE:
				/**
				 * Variable definition.
				 *
				 * @var Log $atum_order
				 */
				$atum_order = AtumHelpers::get_atum_order_model( $object_id, FALSE );

				if ( $creating ) {

					$log_data = [
						'source' => LogModel::SRC_ATUM,
						'module' => LogModel::MOD_INVENTORY_LOGS,
						'data'   => [
							'order_id'         => $object_id,
							'order_name'       => 'Log#' . $object_id,
							'atum_order_type'  => $atum_order->type,
							'post_status'      => $atum_order->get_status(),
							'reservation_date' => $atum_order->reservation_date,
							'return_date'      => $atum_order->return_date,
							'damage_date'      => $atum_order->damage_date,
							'shipping_company' => $atum_order->shipping_company,
							'custom_name'      => $atum_order->custom_name,
						],
						'entry'  => LogEntry::ACTION_IL_CREATE,
					];
					LogModel::maybe_save_log( $log_data );
				} else {
					$transient_key = AtumCache::get_transient_key( 'log_api_inventory_log_' . $object_id );
					$old_data = AtumCache::get_transient( $transient_key, TRUE );

					$metas = Helpers::get_atum_inventory_logs_metas();

					$new_data = get_metadata( 'post', $object_id, '', TRUE );

					foreach ( $metas as $m ) {
						$old_value = isset( $old_data[ '_' . $m ] ) ? $old_data[ '_' . $m ][0] : FALSE;
						$new_value = isset( $new_data[ '_' . $m ] ) ? $new_data[ '_' . $m ][0] : FALSE;

						if ( 'description' === $m ) {
							$new_value = $atum_order->get_post()->post_content;
						}
						elseif ( 'status' === $m ) {
							$new_value = $atum_order->get_status();
						}
						elseif ( 'date_created' === $m ) {
							$new_value = $atum_order->get_post()->post_date;
						}

						Helpers::maybe_log_inventory_log_detail( $object_id, $m, $old_value, $new_value );
					}

				}
				break;
			case PurchaseOrders::POST_TYPE:
				/**
				 * Variable definition.
				 *
				 * @var PurchaseOrder $atum_order
				 */
				$atum_order = AtumHelpers::get_atum_order_model( $object_id, FALSE );

				if ( $creating ) {

					$data = [
						'order_id'      => $atum_order->get_id(),
						'order_name'    => 'PO#' . $atum_order->get_id(),
						'date_expected' => $atum_order->date_expected,
					];

					$supplier = $atum_order->get_supplier();

					if ( ! is_null( $supplier ) ) {
						$data['supplier'] = [
							'id'   => $supplier->id,
							'name' => $supplier->name,
						];
					}

					$log_data = [
						'source' => LogModel::SRC_ATUM,
						'module' => LogModel::MOD_PURCHASE_ORDERS,
						'data'   => $data,
						'entry'  => LogEntry::ACTION_PO_CREATE,
					];
					LogModel::maybe_save_log( $log_data );

				} else {
					$transient_key = AtumCache::get_transient_key( 'log_api_purchase_order_' . $object_id );
					$old_data = AtumCache::get_transient( $transient_key, TRUE );

					$new_data = get_metadata( 'post', $object_id, '', TRUE );

					$fields = apply_filters( 'atum/logs/purchase_orders_fields', Helpers::get_purchase_order_metas() );

					foreach ( $fields as $m ) {
						$old_value = isset( $old_data[ '_' . $m ] ) ? $old_data[ '_' . $m ][0] : FALSE;
						$new_value = isset( $new_data[ '_' . $m ] ) ? $new_data[ '_' . $m ][0] : FALSE;

						if ( 'description' === $m ) {
							$new_value = $atum_order->get_post()->post_content;
						}
						elseif ( 'date' === $m ) {
							$new_value = $atum_order->get_post()->post_date;
						}
						elseif ( 'status' === $m ) {
							$new_value = $atum_order->get_status();
						}

						Helpers::maybe_log_purchase_order_detail( $object_id, $m, $old_value, $new_value );
					}
				}
				break;
			case 'shop_order':
				remove_action( 'woocommerce_new_order', array( $this, 'wc_create_order' ), PHP_INT_MAX );
				$order = wc_get_order( $object_id );

				if ( $creating ) {
					$this->wc_create_order( $object_id, $order );
				}
				else {
					$this->after_order_save( $object_id, $order );
				}
				break;
		}

		return $object;

	}

	/**
	 * Check previous values for an object in API Request
	 *
	 * @since 1.0.7
	 *
	 * @param bool    $supports_trash If object can be trashed.
	 * @param \WC_Data $object         Object object.
	 *
	 * @return bool
	 */
	public function wc_api_before_delete_object( $supports_trash, $object ) {

		$object_id = $object->get_id();

		if ( AtumHelpers::is_using_hpos_tables() && OrderUtil::is_order( $object_id ) ) {
			$type = OrderUtil::get_order_type( $object_id );
		}
		else {
			$post = get_post( $object_id );
			$type = $post->post_type;
		}

		if ( 'product_variation' === $type ) {

			/**
			 * @var \WC_Product_Variation $object
			 */
			$parent = wc_get_product( $object->get_parent_id() );

			$data = [
				'variation_id'   => $object_id,
				'variation_name' => $object->get_name(),
				'product_id'     => $object->get_parent_id(),
				'product_name'   => $parent->get_name(),
			];

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_variation_' . $object_id );
			AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );
		}
		elseif ( 'product' === $type ) {

			/**
			 * @var \WC_Product $object
			 */
			$data = [
				'id'   => $object_id,
				'name' => $object->get_name(),
			];

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_product_' . $object_id );
			AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );

		}
		elseif ( FALSE !== in_array( $type, [ 'shop_order', InventoryLogs::POST_TYPE, PurchaseOrders::POST_TYPE ] ) ) {

			switch( $type ) {
				case InventoryLogs::POST_TYPE:
					$order_name = 'IL#' . $object_id;
					break;
				case PurchaseOrders::POST_TYPE:
					$order_name = 'PO#' . $object_id;
					break;
				default:
					$order_name = '#' . $object_id;
					break;
			}

			$data = [
				'id'   => $object_id,
				'name' => $order_name,
			];

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_order_' . $object_id );
			AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );

		}

		return $supports_trash;
	}


	/**
	 * Log for API delete object
	 *
	 * @since 1.0.7
	 *
	 * @param \WC_Data          $object
	 * @param \WP_REST_Response $response
	 * @param \WP_REST_Request  $request
	 *
	 * @throws \Exception
	 */
	public function wc_api_delete_object( $object, $response, $request ) {

		if ( is_null( $object ) || is_wp_error( $object ) ) {
			return;
		}

		$resp_data = $response->data;
		$object_id = $resp_data['id'];
		$class     = get_class( $object );
		$source    = LogModel::SRC_WC;

		if ( 'WC_Product_Variation' === $class ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_variation_' . $object_id );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			if ( $object_id !== $old_data['variation_id'] )
				return;

			$module = LogModel::MOD_WC_PRODUCT_DATA;
			$entry  = LogEntry::ACTION_WC_VARIATION_DELETE;
		}
		elseif ( 'WC_Product' === $class ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_product_' . $object_id );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			if ( $object_id !== $old_data['id'] )
				return;

			$module = LogModel::MOD_WC_PRODUCT_DATA;
			$entry  = LogEntry::ACTION_WC_PRODUCT_TRASH;
		}
		elseif ( 'WC_Order' === $class ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_order_' . $object_id );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			if ( $object_id !== $old_data['id'] ) {
				return;
			}

			$module = LogModel::MOD_WC_ORDERS;
			$entry  = LogEntry::ACTION_WC_ORDER_TRASH;
		}
		elseif ( 'PurchaseOrder' === $class ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_order_' . $object_id );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			if ( $object_id !== $old_data['id'] )
				return;

			$module = LogModel::MOD_PURCHASE_ORDERS;
			$entry  = LogEntry::ACTION_PO_DEL;
		}
		elseif ( 'Log' === $class ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_delete_order_' . $object_id );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			if ( $object_id !== $old_data['id'] )
				return;

			$module = LogModel::MOD_INVENTORY_LOGS;
			$entry  = LogEntry::ACTION_IL_DEL;
		} else {
			return;
		}

		$old_data['api_request'] = (array) $request;

		$log_data = [
			'source' => $source,
			'module' => $module,
			'entry'  => $entry,
			'data'   => $old_data,
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * @param LogItem|POItem|\WC_Order_Item $item
	 * @param mixed                         $posted
	 */
	public function api_before_save_atum_order_items( $item, $posted ) {

		$item_id  = $item->get_id();
		$old_data = $item->get_data();

		$transient_key = AtumCache::get_transient_key( 'log_api_save_order_item_' . $item_id );
		AtumCache::set_transient( $transient_key, $old_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * After saving ATUM order items
	 *
	 * @param AtumOrderItemProduct $item
	 * @param mixed                $posted
	 *
	 * @throws \Exception
	 */
	public function api_after_save_atum_order_items( $item, $posted, $action ) {

		$item_id = $item->get_id();
		$order   = $item->get_atum_order_id() ? $item->get_order() : NULL;

		if ( is_null( $order ) || ! $order instanceof AtumOrderModel ) {
			return;
		}

		$order_post_type = $order->get_post_type();

		if ( PurchaseOrders::POST_TYPE === $order_post_type ) {
			$module = LogModel::MOD_PURCHASE_ORDERS;
			$name   = 'PO#' . $order->get_id();
		}
		else {
			$module = LogModel::MOD_INVENTORY_LOGS;
			$name   = 'Log#' . $order->get_id();
		}

		if ( 'create' === $action ) {

			$entry = PurchaseOrders::POST_TYPE === $order_post_type ? LogEntry::ACTION_PO_ADD_ITEM : LogEntry::ACTION_IL_ADD_ITEM;

			$log_data = [
				'module' => $module,
				'source' => LogModel::SRC_ATUM,
				'data'   => [
					'order_id'   => $order->get_id(),
					'order_name' => $name,
					'item_data'  => $posted,
				],
				'entry'  => $entry,
			];
			LogModel::maybe_save_log( $log_data );
			return;
		}

		$item = $order->get_item( $item_id );

		if ( ! $item instanceof AtumOrderItemProduct ) {
			return;
		}

		$transient_key = AtumCache::get_transient_key( 'log_api_save_order_item_' . $item_id );
		$old_data      = AtumCache::get_transient( $transient_key, TRUE );
		$new_data      = $item->get_data();

		$diff = Helpers::get_array_diff( $new_data, $old_data );
		AtumCache::delete_transients( $transient_key );

		if ( PurchaseOrders::POST_TYPE === $order_post_type ) {
			$module = LogModel::MOD_PURCHASE_ORDERS;
			$entry  = LogEntry::ACTION_PO_ITEM_CHANGED;
			$name   = 'PO#' . $order->get_id();
		}
		else {
			$module = LogModel::MOD_INVENTORY_LOGS;
			$entry  = LogEntry::ACTION_IL_ITEM_CHANGED;
			$name   = 'Log#' . $order->get_id();
		}

		foreach ( $diff as $field => $values ) {

			if ( 'meta_data' === $field ) {
				continue;
			}

			if ( ! isset( $values['new_value'] ) ) {
				continue;
			}

			$log_data = [
				'order_id'      => $order->get_id(),
				'order_name'    => $name,
				'item_id'       => $item->get_id(),
				'item_name'     => $item->get_name(),
				'field'         => $field,
				'old_value'     => $values['old_value'],
				'new_value'     => $values['new_value'],
			];

			$log_processed_data = [
				'module' => $module,
				'source' => LogModel::SRC_ATUM,
				'data'   => $log_data,
				'entry'  => $entry,
			];

			LogModel::maybe_save_log( $log_processed_data );

		}

	}

	/**
	 * Check for Supplier values before update
	 *
	 * @since 1.0.8
	 *
	 * @param \WP_Post         $post     Inserted or updated post object.
	 * @param \WP_REST_Request $request  Request object.
	 */
	public function api_before_save_atum_supplier( $post, $request ) {

		$supplier = new Supplier( $post->ID );

		$supplier_data = array(
			'id'             => $post->ID,
			'name'           => $post->post_title,
			'post_status'    => $post->post_status,
		);
		foreach ( Helpers::get_atum_supplier_metas() as $meta ) {
			$supplier_data[ $meta ] = $supplier->{$meta};
		}

		$transient_key = AtumCache::get_transient_key( 'log_api_save_supplier_' . $post->ID );
		AtumCache::set_transient( $transient_key, $supplier_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Logs Supplier save from API request
	 *
	 * @since 1.0.8
	 *
	 * @param \WP_Post         $post     Inserted or updated post object.
	 * @param \WP_REST_Request $request  Request object.
	 * @param bool             $creating True when creating a post, false when updating.
	 *
	 * @throws \Exception
	 */
	public function api_after_save_atum_supplier( $post, $request, $creating ) {

		$supplier = new Supplier( $post->ID );

		if ( FALSE === $creating ) {

			$transient_key = AtumCache::get_transient_key( 'log_api_save_supplier_' . $post->ID );
			$old_data      = AtumCache::get_transient( $transient_key, TRUE );

			foreach ( array_merge( [ 'name', 'post_status' ], Helpers::get_atum_supplier_metas() ) as $dt ) {

				$old_value = $old_data[ $dt ];

				switch ( $dt ) {
					case 'name':
						$new_value = $post->post_title;
						break;
					case 'post_status':
						$new_value = $post->post_status;
						break;
					default:
						$new_value = $supplier->{$dt};
						break;
				}

				if ( ( empty( $old_value ) && empty( $new_value ) ) || $old_value === $new_value )
					continue;

				if ( 'post_status' === $dt ) {
					$entry = LogEntry::ACTION_SUPPLIER_STATUS;
				}
				else {
					$entry = LogEntry::ACTION_SUPPLIER_DETAILS;
				}

				$log_data = [
					'source' => LogModel::SRC_ATUM,
					'module' => LogModel::MOD_SUPPLIERS,
					'data'   => [
						'id'        => $post->ID,
						'name'      => $post->post_title,
						'field'     => $dt,
						'old_value' => $old_value,
						'new_value' => $new_value,
					],
					'entry'  => $entry,
				];

				LogModel::maybe_save_log( $log_data );
			}

			AtumCache::delete_transients( $transient_key );
			return;
		}

		$supplier_data = array(
			'id'             => $post->ID,
			'name'           => $post->post_title,
		);
		foreach ( Helpers::get_atum_supplier_metas() as $meta ) {
			$supplier_data[ $meta ] = $supplier->{$meta};
		}
		$log_data = [
			'source' => LogModel::SRC_ATUM,
			'module' => LogModel::MOD_SUPPLIERS,
			'data'   => $supplier_data,
			'entry'  => LogEntry::ACTION_SUPPLIER_NEW,
		];
		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Logs ATUM tool has been executed.
	 *
	 * @since 1.0.8
	 *
	 * @param array            $tool    Details about the tool that has been executed.
	 * @param \WP_REST_Request $request The current WP_REST_Request object.
	 *
	 * @throws \Exception
	 */
	public function api_run_tool( $tool, $request ) {

		$id = $tool['id'];

		switch ( $id ) {
			case 'sync_calculated_stock':
				$source = LogModel::SRC_PL;
				$module = LogModel::MOD_PL_SETTINGS;
				break;
			default:
				$source = LogModel::SRC_ATUM;
				$module = LogModel::MOD_ATUM_SETTINGS;
				break;
		}

		$tool['tool'] = $tool['name'];

		$log_data = [
			'source' => $source,
			'module' => $module,
			'data'   => $tool,
			'entry'  => LogEntry::ACTION_SET_RUN_TOOL,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/****************************
	 * Instance methods
	 ****************************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_LOGS_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Hooks instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
