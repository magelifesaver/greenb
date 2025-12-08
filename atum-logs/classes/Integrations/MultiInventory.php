<?php
/**
 * Multi-Inventory + Atum Action Logs integration
 *
 * @since       0.4.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs
 * @subpackage  Integrations
 */

namespace AtumLogs\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumExport\Queries\AtumOrderItem;
use AtumLogs\Inc\Helpers;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumMultiInventory\Inc\Helpers as AtumMIHelpers;
use AtumMultiInventory\Models\Inventory;
use AtumMultiInventory\Models\MainInventory;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use Automattic\WooCommerce\Utilities\OrderUtil;


class MultiInventory {

	/**
	 * The singleton instance holder
	 *
	 * @var MultiInventory
	 */
	private static $instance;

	/**
	 * MultiInventory singleton constructor
	 *
	 * @since 0.4.1
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

		$this->register_global_hooks();

	}

	/**
	 * Register the hooks for the admin side
	 *
	 * @since 0.4.1
	 */
	public function register_admin_hooks() {

		// MI Products.
		add_action( 'atum/ajax/mi_remove_inventory', array( $this, 'mi_remove_inventory' ) );
		add_action( 'atum/ajax/mi_write_off_inventory', array( $this, 'mi_write_off_inventory' ), 1, 2 );
		add_action( 'atum/product_data/before_save_product_meta_boxes', array( $this, 'mi_preview_meta_boxes' ), 1 );
		add_action( 'atum/product_data/before_save_product_variation_meta_boxes', array( $this, 'mi_preview_meta_boxes' ), 1, 2 );
		add_action( 'atum/product_data/after_save_product_meta_boxes', array( $this, 'mi_save_meta_boxes' ), PHP_INT_MAX );
		add_action( 'atum/product_data/after_save_product_variation_meta_boxes', array( $this, 'mi_save_meta_boxes' ), PHP_INT_MAX, 2 );

		// MI Orders.
		add_action( 'wp_ajax_woocommerce_save_order_items', array( $this, 'mi_save_order_items' ), 1 );
		add_action( 'pre_post_update', array( $this, 'mi_before_order_save' ), 10, 2 );
		add_action( 'save_post', array( $this, 'mi_after_order_save' ), 10, 3 );

		// MI PO.
		add_action( 'atum/multi_inventory/before_calculate_update_mi_order_lines', array( $this, 'mi_po_before_inventory' ), 1, 2 );
		add_action( 'atum/multi_inventory/after_update_mi_order_lines', array( $this, 'mi_po_after_inventory' ), PHP_INT_MAX, 3 );
		add_action( 'atum/multi_inventory/after_add_mi_order_item', array( $this, 'mi_po_auto_inventory' ), 10, 3 );
		add_filter( 'atum/action_logs/check_purchase_order_stock_levels', array( $this, 'maybe_register_po_inventories_stock_levels' ), 10, 2 );

		// Add inventory info to log data.
		add_filter( 'atum/action_logs/add_delivery_item_log_data', array( $this, 'add_inventory_to_log_data' ), 10, 2 );

		// Log Inventory changes from Stock Central.
		add_action( 'atum/product_data/after_save_data', array( $this, 'sc_log_update_product_data' ), 10, 2 );

		// Inventory created from modal.
		add_action( 'atum/multi_inventory/ajax/inventory_created', array( $this, 'mi_log_create_inventory' ), 10, 2 );

		// Order item inventory added.
		add_action( 'atum/purchase_orders_pro/added_order_item_inventory', array( $this, 'mi_log_added_order_item_inventory' ), 10, 4 );

		// Register MI data for ST logs.
		add_filter( 'atum/action_logs/check_item_stock_levels', array( $this, 'check_item_stock_levels' ) );

		// Log MI stock levels after reconcile stock.
		add_filter( 'atum/action_logs/log_item_stock_levels', array( $this, 'log_item_stock_levels' ), 10, 3 );
	}

	/**
	 * Register the global hooks
	 *
	 * @since 0.4.1
	 */
	public function register_global_hooks() {

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'mi_order_inventories_check' ), PHP_INT_MAX, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'store_api_mi_order_inventories_check' ), PHP_INT_MAX );

		add_action( 'atum/multi_inventory/expired_inventory', array( $this, 'mi_expired_inventory' ) );
		add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'mi_preview_inventories' ), 1, 2 );
		add_filter( 'woocommerce_can_restore_order_stock', array( $this, 'mi_preview_inventories' ), 1, 2 );
		add_action( 'atum/multi_inventory/reduce_order_stock', array( $this, 'mi_saved_inventories' ) );
		add_action( 'atum/multi_inventory/restore_order_stock', array( $this, 'mi_saved_inventories' ) );

		// API Hooks.
		add_action( 'atum/multi_inventory/api/rest_after_insert_inventory', array( $this, 'mi_api_created_inventory' ), 10, 3 );
		add_action( 'atum/multi_inventory/api/rest_before_insert_inventory', array( $this, 'mi_api_before_update_inventory' ), 10, 3 );
		add_action( 'atum/multi_inventory/api/rest_insert_inventory', array( $this, 'mi_api_after_updated_inventory' ), 10, 3 );
		add_action( 'atum/multi_inventory/api/rest_delete_inventory', array( $this, 'mi_api_delete_inventory' ), 10, 3 );

		// Collect MI data for PL logs.
		add_filter( 'atum/action_logs/order_item_extra_data', array( $this, 'get_log_order_item_extra_data' ), 10, 2 );
	}

	/**
	 * Checks MI availability on a product
	 *
	 * @since 0.5.1
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public function check_mi_product( $product_id ) {

		$product = AtumHelpers::get_atum_product( $product_id );

		if ( ! $product instanceof \WC_Product)
			return FALSE;

		$product_status = AtumMiHelpers::get_product_multi_inventory_status( $product );
		$default_status = AtumHelpers::get_option( 'mi_default_multi_inventory', 'no' );

		if ( 'yes' === $product_status || ( 'global' === $product_status && 'yes' === $default_status ) )
			return TRUE;

		return FALSE;
	}

	/**
	 * Checks for inventories data before saving
	 *
	 * @param int  $product_id
	 * @param null $loop
	 *
	 * @since 0.3.1
	 */
	public function mi_preview_meta_boxes( $product_id, $loop = NULL ) {

		$metas     = Helpers::get_mi_product_metas();
		$dump_data = [];

		foreach ( $metas as $meta ) {
			switch ( $meta ) {
				case '_multi_inventory':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_multi_inventory_status( $product_id, TRUE );
					break;
				case '_inventory_iteration':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_inventory_iteration( $product_id, TRUE );
					break;
				case '_expirable_inventories':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_expirable_inventories( $product_id, TRUE );
					break;
				case '_price_per_inventory':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_price_per_inventory( $product_id, TRUE );
					break;
				case '_inventory_sorting_mode':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_inventory_sorting_mode( $product_id, TRUE );
					break;
				case '_selectable_inventories':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_selectable_inventories( $product_id, TRUE );
					break;
				case '_selectable_inventories_mode':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_selectable_inventories_mode( $product_id, TRUE );
					break;
				case '_show_write_off_inventories':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_show_write_off_inventories( $product_id, TRUE );
					break;
				case '_show_out_of_stock_inventories':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_show_out_of_stock_inventories( $product_id, TRUE );
					break;
				case '_low_stock_threshold_by_inventory':
					$dump_data[ $meta ] = AtumMIHelpers::get_product_low_stock_threshold_by_inventory( $product_id, TRUE );
					break;
			}
		}

		$inventories = AtumMIHelpers::get_product_inventories_sorted( $product_id );

		foreach ( $inventories as $inventory ) {
			$dump_data['inventories'][ $inventory->id ] = $inventory->get_all_data();
		}

		$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_product_data_' . $product_id );
		AtumCache::set_transient( $transient_key_metadata, $dump_data, MINUTE_IN_SECONDS, TRUE );
	}

	/**
	 * Logs changes in MI products and its inventories
	 *
	 * @param int  $product_id
	 * @param null $loop
	 *
	 * @since 0.3.1
	 */
	public function mi_save_meta_boxes( $product_id, $loop = NULL ) {

		$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_product_data_' . $product_id );
		$old_data               = AtumCache::get_transient( $transient_key_metadata, TRUE );
		$product                = AtumHelpers::get_atum_product( $product_id );
		$is_variation           = 'variation' === $product->get_type();

		foreach ( Helpers::get_mi_product_metas() as $meta ) {

			switch ( $meta ) {
				case '_multi_inventory':
					$new_value = AtumMIHelpers::get_product_multi_inventory_status( $product_id, TRUE );
					break;
				case '_inventory_iteration':
					$new_value = AtumMIHelpers::get_product_inventory_iteration( $product_id, TRUE );
					break;
				case '_expirable_inventories':
					$new_value = AtumMIHelpers::get_product_expirable_inventories( $product_id, TRUE );
					break;
				case '_price_per_inventory':
					$new_value = AtumMIHelpers::get_product_price_per_inventory( $product_id, TRUE );
					break;
				case '_inventory_sorting_mode':
					$new_value = AtumMIHelpers::get_product_inventory_sorting_mode( $product_id, TRUE );
					break;
				case '_selectable_inventories':
					$new_value = AtumMIHelpers::get_product_selectable_inventories( $product_id, TRUE );
					break;
				case '_selectable_inventories_mode':
					$new_value = AtumMIHelpers::get_product_selectable_inventories_mode( $product_id, TRUE );
					break;
				case '_show_write_off_inventories':
					$new_value = AtumMIHelpers::get_product_show_write_off_inventories( $product_id, TRUE );
					break;
				case '_show_out_of_stock_inventories':
					$new_value = AtumMIHelpers::get_product_show_out_of_stock_inventories( $product_id, TRUE );
					break;
				case '_low_stock_threshold_by_inventory':
					$new_value = AtumMIHelpers::get_product_low_stock_threshold_by_inventory( $product_id, TRUE );
					break;
			}

			if ( isset( $old_data[ $meta ] ) && $old_data[ $meta ] instanceof \WC_DateTime ) {
				/**
				 * Variable definition
				 *
				 * @var \WC_DateTime $new_value
				 * @var \WC_DateTime $old_value
				 */
				$old_value = $old_data[ $meta ];

				if ( $old_value instanceof \WC_DateTime && $new_value instanceof \WC_DateTime && $old_value->getTimestamp() === $new_value->getTimestamp() ) {
					continue;
				}
			}

			if ( isset( $old_data[ $meta ] ) && $old_data[ $meta ] !== $new_value ) {
				$log_data = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_PRODUCT_DATA,
					'data'   => [
						'id'        => $product_id,
						'name'      => $product->get_name(),
						'field'     => $meta,
						'new_value' => $new_value,
						'old_value' => $old_data[ $meta ],
					],
					'entry'  => LogEntry::ACTION_MI_PD_EDIT,
				];

				if ( $is_variation ) {
					$log_data['data']['parent'] = $product->get_parent_id();
				}

				LogModel::maybe_save_log( $log_data );
			}
		}

		if ( ! empty( $old_data ) && isset( $old_data['inventories'] ) ) {
			foreach ( $old_data['inventories'] as $inventory_id => $old_inventory_data ) {

				$inventory = AtumMIHelpers::get_inventory( $inventory_id );

				$this->log_inventory_edit( $inventory, $old_inventory_data );

			}
		}

		// New inventories.
		if ( isset( $_POST['atum_mi'] ) ) {

			$inventories = NULL === $loop ? $_POST['atum_mi'] : $_POST['atum_mi'][ $loop ];

			$product_inventories = AtumMIHelpers::get_product_inventories_sorted( $product_id );

			if ( ! empty( $inventories ) && ! empty( $product_inventories ) && ! empty( $old_data ) && ! empty( $old_data['inventories'] ) ) {

				foreach ( $inventories as $inventory_id => $data ) {

					if ( str_contains( $inventory_id, 'new_' ) ) {

						foreach ( $product_inventories as $pi_id => $product_inventory ) {
							$pi_data = $product_inventory->get_all_data();
							if ( FALSE === in_array( $pi_data['id'], array_keys( $old_data['inventories'] ) ) && $data['_inventory_name'] === $pi_data['name'] &&
								floatval( $data['_stock_quantity'] ) === floatval( $pi_data['stock_quantity'] ) && $data['_sku'] === $pi_data['sku']
							) {

								$this->log_inventory_create( $pi_data, array( 'type' => 'product_data' ) );

							}
						}
					}
				}
			}
		}
		AtumCache::delete_transients( $transient_key_metadata );
	}

	/**
	 * Saves the log from an inventory update
	 *
	 * @since 1.0.7
	 *
	 * @param Inventory $inventory
	 * @param array     $old_inventory_data The get_all_data() array from the Inventory before being updated.
	 */
	public function log_inventory_edit( $inventory, $old_inventory_data ) {

		$new_data = $inventory->get_all_data();

		$inventory_id = $inventory->id;
		$product_id   = $inventory->product_id;
		$product      = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product || empty( $old_inventory_data ) ) {
			return;
		}

		$is_variation = 'variation' === $product->get_type();

		foreach ( $old_inventory_data as $key => $value ) {
			if ( FALSE !== in_array( $key, [ 'inventory_date', 'update_date' ] ) ) {
				continue;
			}
			if ( 'priority' === $key && 'manual' !== AtumMIHelpers::get_product_inventory_sorting_mode( $product_id ) )
				continue;

			if ( $value instanceof \WC_DateTime && $new_data[ $key ] instanceof \WC_DateTime ) {
				/**
				 * Variable definition
				 *
				 * @var \WC_DateTime $new_value
				 * @var \WC_DateTime $value
				 */
				$new_value = $new_data[ $key ];
				if ( $value->getTimestamp() === $new_value->getTimestamp() ) {
					continue;
				}
			}

			if ( empty( $value ) && empty( $new_data[ $key ] ) ) continue;

			$equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $new_data[ $key ] ) ) ? TRUE : FALSE;

			if ( $value !== $new_data[ $key ] && ! $equals ) {

				if ( 'stock_status' === $key && 'outofstock' === $new_data[ $key ] && $new_data['out_stock_threshold'] >= $new_data['stock_quantity'] ) {

					$entry = LogEntry::ACTION_MI_INV_OUT_STOCK;
				}
				else {
					$entry = LogEntry::ACTION_MI_EDIT_INVENTORY;
				}

				// Location values.
				if ( 'location' === $key ) {
					$new_value = [];
					$old_value = [];
					foreach ( $new_data[ $key ] as $new_location ) {
						$loc_term    = get_term( $new_location );
						$new_value[] = [
							'id'   => $new_location,
							'name' => $loc_term->name,
						];
					}
					foreach ( $value as $old_location ) {
						$loc_term    = get_term( $old_location );
						$old_value[] = [
							'id'   => $old_location,
							'name' => $loc_term->name,
						];
					}
				}
				// Location values.
				elseif ( 'region' === $key ) {
					$region_restriction_mode = AtumMIHelpers::get_region_restriction_mode();
					if ( 'shipping-zones' !== $region_restriction_mode ) {
						$reg_list = WC()->countries->get_countries();
					}

					$new_value = [];
					$old_value = [];
					foreach ( $new_data[ $key ] as $new_region ) {
						if ( 'shipping-zones' === $region_restriction_mode ) {
							$zone = new \WC_Shipping_Zone( $new_region );
							$name = $zone->get_zone_name();
						} else {
							$name = $reg_list[ $new_region ];
						}
						$new_value[] = [
							'id'   => $new_region,
							'name' => $name,
						];
					}
					foreach ( $value as $old_region ) {
						if ( 'shipping-zones' === $region_restriction_mode ) {
							$zone = new \WC_Shipping_Zone( $old_region );
							$name = $zone->get_zone_name();
						} else {
							$name = $reg_list[ $old_region ];
						}
						$old_value[] = [
							'id'   => $old_region,
							'name' => $name,
						];
					}
				} else {
					$new_value = $new_data[ $key ];
					$old_value = $value;
				}
				$log_data = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_PRODUCT_DATA,
					'data'   => [
						'product_id'   => $product_id,
						'product_name' => $product->get_name(),
						'inventory'    => [
							'id'   => $inventory_id,
							'name' => $new_data['name'],
						],
						'field'        => $key,
						'new_value'    => $new_value,
						'old_value'    => $old_value,
					],
					'entry'  => $entry,
				];

				if ( $is_variation ) {
					$log_data['data']['product_parent'] = $product->get_parent_id();
				}
				LogModel::maybe_save_log( $log_data );
			}
		}

	}


	/**
	 * Saves the log from an inventory creation
	 *
	 * @since 1.0.7
	 *
	 * @param array $pi_data     The get_all_data() array from an Inventory.
	 * @param array $source_data Data about source creating.
	 */
	public function log_inventory_create( $pi_data, $source_data ) {

		$save_data = array();

		$product_id   = $pi_data['product_id'];
		$product      = wc_get_product( $product_id );
		$is_variation = 'variation' === $product->get_type();

		foreach ( $pi_data as $k => $v ) {
			if ( empty( $v ) )
				continue;
			$save_data[ $k ] = $v;
		}
		// Supplier data.
		if ( isset( $save_data['supplier_id'] ) ) {
			$supplier              = new Supplier( $save_data['supplier_id'] );
			$supplier_data         = [
				'id'   => $save_data['supplier_id'],
				'name' => $supplier->name,
			];
			$save_data['supplier'] = $supplier_data;
			unset( $save_data['supplier_id'] );
		}
		// Region data.
		if ( isset( $save_data['region'] ) && ! empty( $save_data['region'] ) ) {
			$reg_data = [];
			if ( is_array( $save_data['region'] ) ) {
				foreach ( $save_data['region'] as $region ) {
					if ( 'shipping-zones' === AtumMIHelpers::get_region_restriction_mode() ) {
						$zone = new \WC_Shipping_Zone( $region );
						$name = $zone->get_zone_name();
					}
					else {
						$reg_list = WC()->countries->get_countries();
						$name     = $reg_list[ $region ];
					}
					$reg_data[] = [
						'id'   => $region,
						'name' => $name,
					];
				}
			}
			else {
				if ( 'shipping-zones' === AtumMIHelpers::get_region_restriction_mode() ) {
					$zone = new \WC_Shipping_Zone( $save_data['region'] );
					$name = $zone->get_zone_name();
				}
				else {
					$reg_list = WC()->countries->get_countries();
					$name     = $reg_list[ $save_data['region'] ];
				}
				$reg_data[] = [
					'id'   => $save_data['region'],
					'name' => $name,
				];
			}
			if ( ! empty( $reg_data ) )
				$save_data['region'] = $reg_data;
		}
		// Location data.
		if ( isset( $save_data['location'] ) && ! empty( $save_data['location'] ) ) {
			$loc_data = [];
			if ( is_array( $save_data['location'] ) ) {
				foreach ( $save_data['location'] as $location ) {
					$loc_term   = get_term( $location );
					$loc_data[] = [
						'id'   => $location,
						'name' => $loc_term->name,
					];
				}
			}
			else {
				$loc_term   = get_term( $save_data['location'] );
				$loc_data[] = [
					'id'   => $save_data['location'],
					'name' => $loc_term->name,
				];
			}
			if ( ! empty( $loc_data ) )
				$save_data['location'] = $loc_data;
		}
		$log_data = [
			'source' => LogModel::SRC_MI,
			'module' => LogModel::MOD_MI_PRODUCT_DATA,
			'data'   => [
				'product_id'   => $product_id,
				'product_name' => $product->get_name(),
				'inventory'    => [
					'id'   => $pi_data['id'],
					'name' => $pi_data['name'],
				],
				'data'         => $save_data,
			],
			'entry'  => LogEntry::ACTION_MI_INVENTORY_CREATE,
		];

		if ( $is_variation ) {
			$log_data['data']['product_parent'] = $product->get_parent_id();
		}

		// Adding source data.
		if ( is_array( $source_data ) && isset( $source_data['type'] ) ) {
			$log_data['data']['source'] = $source_data['type'];

			if ( 'purchase_order' === $source_data['type'] ) {
				$log_data['data']['order_id']   = $source_data['id'];
				$log_data['data']['order_name'] = 'PO#' . $source_data['id'];
			}
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Logs removing inventory from product
	 *
	 * @since 0.3.1
	 *
	 * @param mixed $inventory_ids
	 */
	public function mi_remove_inventory( $inventory_ids ) {

		foreach ( $inventory_ids as $inventory_id ) {
			$inventory = AtumMIHelpers::get_inventory( $inventory_id );
			$data      = $inventory->get_all_data();
			$product   = AtumHelpers::get_atum_product( $data['product_id'] );

			if ( ! $inventory || ! $product || ! $this->check_mi_product( $product->get_id() ) ) {
				return;
			}

			$idata = $inventory->get_all_data();

			$log_data = [
				'source' => LogModel::SRC_MI,
				'module' => LogModel::MOD_MI_PRODUCT_DATA,
				'data'   => [
					'product_id'   => $product->get_id(),
					'product_name' => $product->get_name(),
					'inventory'    => [
						'id'   => $inventory_id,
						'name' => $idata['name'],
					],
				],
				'entry'  => LogEntry::ACTION_MI_INVENTORY_DELETE,
			];

			if ( 'variation' === $product->get_type() ) {
				$log_data['data']['product_parent'] = $product->get_parent_id();
			}

			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Logs mark/unmark inventory as write off
	 *
	 * @since 0.3.1
	 *
	 * @param array  $inventory_ids
	 * @param string $write_off
	 */
	public function mi_write_off_inventory( $inventory_ids, $write_off ) {

		$product     = FALSE;
		$inventories = array();

		foreach ( $inventory_ids as $inventory_id ) {
			$inventory = AtumMIHelpers::get_inventory( $inventory_id );
			$data      = $inventory->get_all_data();
			if ( ! $product ) {
				$product = AtumHelpers::get_atum_product( $data['product_id'] );
			}
			$inventories[ $inventory_id ] = [
				'id'   => $inventory_id,
				'name' => $data['name'],
			];
		}

		if ( $this->check_mi_product( $product ) ) {
			$log_data = [
				'source' => LogModel::SRC_MI,
				'module' => LogModel::MOD_MI_PRODUCT_DATA,
				'data'   => [
					'product_id'   => $product->get_id(),
					'product_name' => $product->get_name(),
					'write_off'    => $write_off,
					'inventories'  => $inventories,
				],
				'entry'  => 'yes' === $write_off ? LogEntry::ACTION_MI_MARK_WRITE_OFF : LogEntry::ACTION_MI_UNMARK_WRITE_OFF,
			];

			if ( 'variation' === $product->get_type() ) {
				$log_data['data']['product_parent'] = $product->get_parent_id();
			}

			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Logs inventory expiration
	 *
	 * @param Inventory|MainInventory $inventory
	 *
	 * @since 0.3.1
	 */
	public function mi_expired_inventory( $inventory ) {

		$data       = $inventory->get_all_data();
		$product_id = $data['product_id'];
		$product    = AtumHelpers::get_atum_product( $product_id );

		if ( ! $this->check_mi_product( $data['product_id'] ) )
			return;

		$log_data = [
			'source' => LogModel::SRC_MI,
			'module' => LogModel::MOD_MI_PRODUCT_DATA,
			'data'   => [
				'inventory'    => [
					'id'   => $data['id'],
					'name' => $data['name'],
				],
				'product_id'   => $product_id,
				'product_name' => $product->get_name(),
			],
			'entry'  => LogEntry::ACTION_MI_INVENTORY_EXPIRED,
		];

		if ( 'variation' === $product->get_type() ) {
			$log_data['data']['product_parent'] = $product->get_parent_id();
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Checks order items products stock quantity and status
	 *
	 * @param bool      $allowed
	 * @param \WC_Order $order
	 *
	 * @return bool
	 * @since 0.3.1
	 */
	public function mi_preview_inventories( $allowed, $order ) {

		$dump_data = array();

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( $this->check_mi_product( $item->get_product_id() ) ) {

				$order_post_type = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
				$inventories     = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id( $order_post_type ) );

				if ( ! empty( $inventories ) ) {

					foreach ( $inventories as $inventory ) {
						$inventory_data                        = AtumMIHelpers::get_inventory( $inventory->inventory_id );
						$dump_data[ $inventory->inventory_id ] = $inventory_data->get_all_data();
					}

				}
			}
		}

		if ( ! empty( $dump_data ) ) {
			$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_inventory_order_data_' . $order->get_id() );
			AtumCache::set_transient( $transient_key_metadata, $dump_data, MINUTE_IN_SECONDS, TRUE );
		}

		return $allowed;

	}

	/**
	 * Logs stock changes on inventory stocks from an order
	 *
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function mi_saved_inventories( $order ) {

		$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_inventory_order_data_' . $order->get_id() );
		$old_data               = AtumCache::get_transient( $transient_key_metadata, TRUE );
		$restriction_mode       = AtumMIHelpers::get_region_restriction_mode();
		$order_type             = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();

		foreach ( $order->get_items() as $item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {

				continue;
			}

			$inventory_data = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id( $order_type ) );

			if ( ! empty( $inventory_data ) ) {

				$product                = AtumHelpers::get_atum_product( $item->get_product() );
				$is_variation           = 'variation' === $product->get_type();
				$product_has_multiprice = AtumMIHelpers::has_multi_price( $product );
				$inventories_sorted     = AtumMIHelpers::get_product_inventories_sorted( $product->get_id() );
				$product_inventories    = Inventory::get_product_inventories( $product->get_id() );
				$restricted_inventories = [];

				if ( 'no-restriction' !== $restriction_mode ) {

					foreach ( $product_inventories as $idata ) {

						$found = FALSE;

						foreach ( $inventories_sorted as $sdata ) {
							if ( $idata->id === $sdata->id ) {
								$found = TRUE;
								break;
							}
						}

						if ( ! $found ) {
							$all_data                 = $idata->get_all_data();
							$restricted_inventories[] = [
								'id'   => $idata->id,
								'name' => $all_data['name'],
							];
						}

					}

					foreach ( $restricted_inventories as $r_inv ) {

						$log_data = [
							'source' => LogModel::SRC_MI,
							'module' => LogModel::MOD_MI_ORDERS,
							'data'   => [
								'order_id'   => $order->get_id(),
								'order_name' => '#' . $order->get_id(),
								'order_item' => [
									'id'   => $item->get_id(),
									'name' => $item->get_name(),
								],
								'inventory'  => $r_inv,
							],
							'entry'  => LogEntry::ACTION_MI_INV_REG_RESTRICT,
						];

						LogModel::maybe_save_log( $log_data );

					}

				}

				if ( $product_has_multiprice ) {

					$multiprice_data = [
						'order_id'      => $order->get_id(),
						'order_item_id' => $item->get_id(),
						'product_id'    => $product->get_id(),
						'product_name'  => $product->get_name(),
						'inventories'   => [],
					];

					if ( $is_variation ) {
						$multiprice_data['product_parent'] = $product->get_parent_id();
					}

				}

				foreach ( $inventory_data as $idata ) {

					if ( ! isset( $old_data[ $idata->inventory_id ] ) ) {
						continue;
					}

					$main      = 'yes' === $old_data[ $idata->inventory_id ]['is_main'];
					$inventory = $main ? new MainInventory( $idata->inventory_id, $idata->product_id ) : new Inventory( $idata->inventory_id );

					$new_data = $inventory->get_all_data();

					foreach ( $new_data as $meta => $value ) {

						if ( FALSE !== in_array( $meta, [ 'inventory_date', 'update_date' ] ) ) {
							continue;
						}

						if ( $value instanceof \WC_DateTime && $old_data[ $idata->inventory_id ][ $meta ] instanceof \WC_DateTime ) {
							/**
							 * Variable definition
							 *
							 * @var \WC_DateTime $value
							 * @var \WC_DateTime $old_value
							 */
							$old_value = $old_data[ $idata->inventory_id ][ $meta ];

							if ( $old_value->getTimestamp() === $value->getTimestamp() ) {
								continue;
							}
						}

						$num_equals = ( is_numeric( $value ) && floatval( $value ) === floatval( $old_data[ $idata->inventory_id ][ $meta ] ) ) ? TRUE : FALSE;

						if ( $value !== $old_data[ $idata->inventory_id ][ $meta ] && ! $num_equals ) {

							if ( 'stock_status' === $meta && 'outofstock' === $value ) {

								if ( $new_data['stock_quantity'] > 0 && $new_data['out_stock_threshold'] >= $new_data['stock_quantity'] ) {
									$entry = LogEntry::ACTION_MI_INV_OUT_STOCK;
								}
								else {
									$entry = LogEntry::ACTION_MI_INV_DEPLETED;
									// $entry = LogEntry::ACTION_MI_INV_USE_NEXT;
								}

							}
							else {
								$entry = LogEntry::ACTION_MI_EDIT_INVENTORY;
							}

							$log_data = [
								'source' => LogModel::SRC_MI,
								'module' => LogModel::MOD_MI_PRODUCT_DATA,
								'data'   => [
									'product_id'   => $product->get_id(),
									'product_name' => $product->get_name(),
									'inventory'    => [
										'id'   => $idata->inventory_id,
										'name' => $new_data['name'],
									],
									'field'        => $meta,
									'new_value'    => $value,
									'old_value'    => $old_data[ $idata->inventory_id ][ $meta ],
								],
								'entry'  => $entry,
							];

							if ( $is_variation ) {
								$log_data['data']['product_parent'] = $product->get_parent_id();
							}

							LogModel::maybe_save_log( $log_data );

							// Log stock changes within order.
							if ( 'stock_quantity' === $meta && $old_data[ $idata->inventory_id ][ $meta ] - $value > 0 ) {
								$log_data = [
									'source' => LogModel::SRC_MI,
									'module' => LogModel::MOD_MI_ORDERS,
									'data'   => [
										'product_id'   => $product->get_id(),
										'product_name' => $product->get_name(),
										'inventory'    => [
											'id'   => $idata->inventory_id,
											'name' => $new_data['name'],
										],
										'order_id'     => $order->get_id(),
										'order_name'   => '#' . $order->get_id(),
										'order_item'   => [
											'id'   => $item->get_id(),
											'name' => $item->get_name(),
										],
										'qty_used'     => $old_data[ $idata->inventory_id ][ $meta ] - $value,
										'new_value'    => $value,
										'old_value'    => $old_data[ $idata->inventory_id ][ $meta ],
									],
									'entry'  => LogEntry::ACTION_MI_ORDER_ITEM_QTY,
								];

								LogModel::maybe_save_log( $log_data );

								if ( $product_has_multiprice && in_array( $meta, [ 'stock_quantity', 'price' ] ) ) {
									$currency = method_exists( $item, 'get_atum_order_id' ) ? $order->currency : $order->get_currency();
									$value    = floatval( $old_data[ $idata->inventory_id ][ $meta ] ) - floatval( $value );
									$multiprice_data['inventories'][ $idata->inventory_id ] = [
										'inventory' => [
											'id'   => $idata->inventory_id,
											'name' => $new_data['name'],
										],
										'qty'       => $value,
										'price'     => $new_data['price'] . $currency,
									];
								}

							}
						}
					}
				}

				if ( $product_has_multiprice && ! empty( $multiprice_data['inventories'] ) ) {

					foreach ( $multiprice_data['inventories'] as $mpd ) {

						$log_data = [
							'source' => LogModel::SRC_MI,
							'module' => LogModel::MOD_MI_ORDERS,
							'data'   => $mpd,
							'entry'  => LogEntry::ACTION_MI_MULTIPRICE_SOLD,
						];
						LogModel::maybe_save_log( $log_data );
					}

				}
			}
		}

		AtumCache::delete_transients( $transient_key_metadata );

	}

	/**
	 * Logs automatically item inventories used in order
	 *
	 * @param int                      $order_id
	 * @param mixed                    $posted_data
	 * @param \WC_Order|AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function mi_order_inventories_check( $order_id, $posted_data, $order ) {

		$order_post_type = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {
				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id( $order_post_type ) );
			$inv_ids     = array();

			if ( ! empty( $inventories ) ) {

				foreach ( $inventories as $inventory ) {
					$inv_obj   = AtumMIHelpers::get_inventory( $inventory->inventory_id );
					$idata     = $inv_obj->get_all_data();
					$inv_ids[] = [
						'id'   => $inventory->inventory_id,
						'name' => $idata['name'],
					];
				}

				$log_data = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => [
						'order_id'    => $order_id,
						'order_name'  => '#' . $order_id,
						'order_item'  => [
							'id'   => $item->get_id(),
							'name' => $item->get_name(),
						],
						'inventories' => $inv_ids,
					],
					'entry'  => LogEntry::ACTION_MI_INVENTORIES_USED,
				];

				LogModel::maybe_save_log( $log_data );

			}
		}

	}

	/**
	 * Logs automatically item inventories used in order (through the Store API)
	 *
	 * @since 1.4.3
	 *
	 * @param \WC_Order $order
	 */
	public function store_api_mi_order_inventories_check( $order ) {
		$this->mi_order_inventories_check( $order->get_id(), NULL, $order );
	}

	/**
	 * Logs inventory data changes
	 *
	 * @since 0.3.1
	 */
	public function mi_save_order_items() {

		check_ajax_referer( 'order-item', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['order_id'], $_POST['items'] ) ) {
			return;
		}

		$order_id      = intval( $_POST['order_id'] );
		$order         = wc_get_order( $order_id );
		$order_items   = $order->get_items();
		$old_data      = [];
		$new_data      = [];
		$data_to_check = [ 'qty', 'refund_qty', 'subtotal', 'total', 'refund_total' ];
		$changes       = [];
		$added         = [];
		$removed       = [];
		$order_type    = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();

		foreach ( $order_items as $order_item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $order_item
			 */
			if ( ! $this->check_mi_product( $order_item->get_product_id() ) ) {

				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $order_item->get_id(), Globals::get_order_type_id( $order_type ) );

			foreach ( $inventories as $inventory ) {
				$inventory_id   = $inventory->inventory_id;
				$inventory_data = (array) $inventory;

				foreach ( $data_to_check as $check ) {
					$old_data[ $order_item->get_id() ][ $inventory_id ][ $check ] = isset( $inventory_data[ $check ] ) ? $inventory_data[ $check ] : FALSE;
				}

			}

		}

		wp_parse_str( urldecode( $_POST['items'] ), $data_to_save );

		foreach ( $data_to_check as $check ) {

			if ( isset( $data_to_save[ 'oi_inventory_' . $check ] ) && ! empty( $data_to_save[ 'oi_inventory_' . $check ] ) ) {
				foreach ( $data_to_save[ 'oi_inventory_' . $check ] as $i => $data ) {
					foreach ( $data as $j => $v ) {
						$new_data[ $i ][ $j ][ $check ] = $v;
					}
				}
			}

		}

		// Searching for changes and removed inventories.
		foreach ( $old_data as $item_id => $odt ) {

			foreach ( $odt as $old_inventory_id => $old_inventory_data ) {
				$changes_inventories = [];
				$removed_inventories = [];

				$inventory = AtumMIHelpers::get_inventory( $old_inventory_id );

				if ( ! $inventory->exists() || ! $this->check_mi_product( $inventory->product_id ) ) {
					continue;
				}

				$idata = $inventory->get_all_data();

				if ( ! isset( $new_data[ $item_id ][ $old_inventory_id ] ) ) {

					$removed_inventories = [
						'id'       => $old_inventory_id,
						'name'     => $idata['name'],
						'old_data' => $old_inventory_data,
					];

				}
				elseif ( ! empty( array_diff( $old_inventory_data, $new_data[ $item_id ][ $old_inventory_id ] ) ) ) {

					$changes_inventories = [
						'id'       => $old_inventory_id,
						'name'     => $idata['name'],
						'old_data' => $old_inventory_data,
						'new_data' => $new_data[ $item_id ][ $old_inventory_id ],
					];

				}

				if ( ! empty( $changes_inventories ) || ! empty( $removed_inventories ) ) {

					$item = $order->get_item( $item_id );

					if ( ! empty( $changes_inventories ) ) {
						$changes[ $item_id ]['order_item']                       = [
							'id'   => $item_id,
							'name' => $item->get_name(),
						];
						$changes[ $item_id ]['inventories'][ $old_inventory_id ] = $changes_inventories;
					}

					if ( ! empty( $removed_inventories ) ) {
						$removed[ $item_id ]['order_item']                       = [
							'id'   => $item_id,
							'name' => $item->get_name(),
						];
						$removed[ $item_id ]['inventories'][ $old_inventory_id ] = $removed_inventories;
					}

				}
			}

		}

		// Searching for new inventories.
		foreach ( $new_data as $item_id => $ndt ) {

			foreach ( $ndt as $new_inventory_id => $new_inventory_data ) {

				if ( ! isset( $old_data[ $item_id ][ $new_inventory_id ] ) ) {

					$item      = $order->get_item( $item_id );
					$inventory = AtumMIHelpers::get_inventory( $new_inventory_id );

					if ( ! $inventory->exists() || ! $this->check_mi_product( $inventory->product_id ) ) {
						continue;
					}

					$idata   = $inventory->get_all_data();
					$added[] = [
						'order_item' => [
							'id'   => $item_id,
							'name' => $item->get_name(),
						],
						'inventory'  => [
							'id'   => $new_inventory_id,
							'name' => $idata['name'],
						],
						'data'       => $new_inventory_data,
					];
				}

			}

		}

		if ( ! empty( $changes ) ) {

			foreach ( $changes as $change ) {

				$change['order_id']   = $order_id;
				$change['order_name'] = '#' . $order_id;
				$log_data             = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $change,
					'entry'  => LogEntry::ACTION_MI_INVENTORIES_EDIT,
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

		if ( ! empty( $added ) ) {

			foreach ( $added as $add ) {

				$add['order_id']   = $order_id;
				$add['order_name'] = '#' . $order_id;
				$log_data          = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $add,
					'entry'  => LogEntry::ACTION_MI_ORDERITEM_INV_ADD,
				];

				LogModel::maybe_save_log( $log_data );

			}

		}

		if ( ! empty( $removed ) ) {

			foreach ( $removed as $rmv ) {

				$rmv['order_id']   = $order_id;
				$rmv['order_name'] = '#' . $order_id;
				$log_data          = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $rmv,
					'entry'  => LogEntry::ACTION_MI_ORDERITEM_INV_DEL,
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

	}

	/**
	 * Checks inventory data previous values
	 *
	 * @param int   $post_ID
	 * @param mixed $data
	 *
	 * @since 0.3.1
	 */
	public function mi_before_order_save( $post_ID, $data ) {

		$mi_data = array();
		$order   = wc_get_order( $post_ID );

		if ( ! $order || 'auto-draft' === $order->get_status() ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {
				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id() );

			if ( empty( $inventories ) ) {
				continue;
			}

			foreach ( $inventories as $inventory ) {
				$inventory_id                                = $inventory->inventory_id;
				$inventory                                   = (array) $inventory;
				$mi_data[ $item->get_id() ][ $inventory_id ] = $inventory;
			}
		}

		if ( ! empty( $mi_data ) ) {
			$transient_key_mi_data = AtumCache::get_transient_key( 'log_mi_inventory_order_items_data_' . $order->get_id() );
			AtumCache::set_transient( $transient_key_mi_data, $mi_data, MINUTE_IN_SECONDS, TRUE );
		}
	}

	/**
	 * Logs edit order items intentories
	 *
	 * @param int      $post_ID
	 * @param \WP_Post $post
	 * @param mixed    $update
	 *
	 * @since 0.3.1
	 */
	public function mi_after_order_save( $post_ID, $post, $update ) {

		$new_data = array();
		$changes  = array();
		$added    = array();
		$removed  = array();

		$order = wc_get_order( $post_ID );

		if ( empty( $order ) || 'auto-draft' === $order->get_status() ) {
			return;
		}

		$transient_key_mi_data = AtumCache::get_transient_key( 'log_mi_inventory_order_items_data_' . $order->get_id() );
		$old_data              = AtumCache::get_transient( $transient_key_mi_data, TRUE );

		if ( empty( $old_data ) ) {
			return;
		}

		// Recover new data.
		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {
				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id() );

			if ( empty( $inventories ) ) {
				continue;
			}

			foreach ( $inventories as $inventory ) {
				$inventory_id                                 = $inventory->inventory_id;
				$inventory                                    = (array) $inventory;
				$new_data[ $item->get_id() ][ $inventory_id ] = $inventory;
			}

		}

		// Searching for changes and removed inventories.
		foreach ( $old_data as $item_id => $dt ) {

			foreach ( $dt as $inventory_id => $inventory_data ) {

				$changes_inventories = [];
				$removed_inventories = [];

				$inventory = AtumMIHelpers::get_inventory( $inventory_id );

				if ( ! $this->check_mi_product( $inventory->product_id ) ) {
					continue;
				}

				$idata = $inventory->get_all_data();

				if ( ! isset( $new_data[ $item_id ][ $inventory_id ] ) ) {

					$removed_inventories = [
						'id'       => $inventory_id,
						'name'     => $idata['name'],
						'old_data' => $inventory_data,
					];

				}
				elseif ( ! empty( array_diff( $inventory_data, $new_data[ $item_id ][ $inventory_id ] ) ) ) {

					$changes_inventories = [
						'id'       => $inventory_id,
						'name'     => $idata['name'],
						'old_data' => $inventory_data,
						'new_data' => $new_data[ $item_id ][ $inventory_id ],
					];

				}

				if ( ! empty( $changes_inventories ) || ! empty( $removed_inventories ) ) {

					$item = $order->get_item( $item_id );

					if ( ! empty( $changes_inventories ) ) {

						$changes[ $item_id ]['order_item']                   = [
							'id'   => $item_id,
							'name' => $item->get_name(),
						];
						$changes[ $item_id ]['inventories'][ $inventory_id ] = $changes_inventories;

					}

					if ( ! empty( $removed_inventories ) ) {

						$removed[ $item_id ]['order_item']                   = [
							'id'   => $item_id,
							'name' => $item->get_name(),
						];
						$removed[ $item_id ]['inventories'][ $inventory_id ] = $removed_inventories;

					}

				}

			}

		}

		// Searching for new inventories.
		foreach ( $new_data as $item_id => $dt ) {

			foreach ( $dt as $inventory_id => $inventory_data ) {

				if ( ! isset( $old_data[ $item_id ][ $inventory_id ] ) ) {

					$item      = $order->get_item( $item_id );
					$inventory = AtumMIHelpers::get_inventory( $inventory_id );

					if ( ! $this->check_mi_product( $inventory->product_id ) ) {
						continue;
					}

					$idata   = $inventory->get_all_data();
					$added[] = [
						'order_item' => [
							'id'   => $item_id,
							'name' => $item->get_name(),
						],
						'inventory'  => [
							'id'   => $inventory_id,
							'name' => $idata['name'],
						],
						'data'       => $inventory_data,
					];

				}

			}

		}

		if ( ! empty( $changes ) ) {

			foreach ( $changes as $change ) {

				$change['order_id'] = $post_ID;
				$log_data           = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $change,
					'entry'  => LogEntry::ACTION_MI_INVENTORIES_EDIT,
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

		if ( ! empty( $added ) ) {

			foreach ( $added as $add ) {

				$add['order_id']   = $post_ID;
				$add['order_name'] = '#' . $post_ID;
				$log_data          = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $add,
					'entry'  => LogEntry::ACTION_MI_ORDERITEM_INV_ADD,
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

		if ( ! empty( $removed ) ) {

			foreach ( $removed as $rmv ) {

				$rmv['order_id']   = $post_ID;
				$rmv['order_name'] = '#' . $post_ID;
				$log_data          = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_ORDERS,
					'data'   => $rmv,
					'entry'  => LogEntry::ACTION_MI_ORDERITEM_INV_DEL,
				];
				LogModel::maybe_save_log( $log_data );

			}

		}

		AtumCache::delete_transients( $transient_key_mi_data );

	}

	/**
	 * Checks
	 *
	 * @param PurchaseOrder $order
	 * @param mixed         $items
	 *
	 * @since 0.3.1
	 */
	public function mi_po_before_inventory( $order, $items ) {

		$dump_data = [];

		foreach ( $order->get_items() as $item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {
				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $item->get_id(), 2 );
			foreach ( $inventories as $inventory ) {

				$inventory_obj = AtumMIHelpers::get_inventory( $inventory->inventory_id );

				$dump_data[ $inventory->order_item_id ][ $inventory->inventory_id ] = [
					'qty'      => $inventory->qty,
					'subtotal' => $inventory->subtotal,
					'total'    => $inventory->total,
					'stock'    => $inventory_obj->stock_quantity,
				];
			}
		}

		if ( ! empty( $dump_data ) ) {
			$transient_key_mi_po_data = AtumCache::get_transient_key( 'log_mi_inventory_po_data_' . $order->get_id() );
			AtumCache::set_transient( $transient_key_mi_po_data, $dump_data, MINUTE_IN_SECONDS, TRUE );
		}
	}

	/**
	 * Logs changes of inventories in a purchase order
	 *
	 * @param PurchaseOrder $order
	 * @param mixed         $changed_order_item_inventories
	 * @param mixed         $items
	 *
	 * @since 0.3.1
	 */
	public function mi_po_after_inventory( $order, $changed_order_item_inventories, $items ) {

		$order_post_type          = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();
		$transient_key_mi_po_data = AtumCache::get_transient_key( 'log_mi_inventory_po_data_' . $order->get_id() );
		$old_data                 = AtumCache::get_transient( $transient_key_mi_po_data, TRUE );

		if ( empty( $old_data ) ) {
			return;
		}

		foreach ( $old_data as $item_id => $old_inventories ) {

			$new_inventories = Inventory::get_order_item_inventories( $item_id, 2 );

			foreach ( $old_inventories as $old_inventory_id => $old_inventory_data ) {
				$found = FALSE;

				foreach ( $new_inventories as $new_inventory ) {
					if ( absint( $new_inventory->inventory_id ) === $old_inventory_id ) {
						$found = TRUE;
						break;
					}
				}

				if ( FALSE === $found ) {
					$old_inventory_object = AtumMIHelpers::get_inventory( $old_inventory_id );

					if ( ! $this->check_mi_product( $old_inventory_object->product_id ) )
						continue;

					$old_oi_data   = $old_inventory_object->get_all_data();
					$old_item_data = $order->get_item( $item_id );

					$removed  = [
						'order_id'   => $order->get_id(),
						'order_name' => '#' . $order->get_id(),
						'order_item' => [
							'id'   => $item_id,
							'name' => $old_item_data->get_name(),
						],
						'inventory'  => [
							'id'   => $old_inventory_id,
							'name' => $old_oi_data['name'],
						],
						'old_data'   => $old_inventory_data,
					];
					$log_data = [
						'source' => LogModel::SRC_MI,
						'module' => PurchaseOrders::POST_TYPE === $order_post_type ? LogModel::MOD_MI_PURCHASE_ORDERS : LogModel::MOD_MI_INVENTORY_LOGS,
						'data'   => $removed,
						'entry'  => PurchaseOrders::POST_TYPE === $order_post_type ? LogEntry::ACTION_MI_ORDERITEM_INV_PO_DEL : LogEntry::ACTION_MI_ORDERITEM_INV_IL_DEL,
					];
					LogModel::maybe_save_log( $log_data );
				}
			}
		}

		foreach ( $order->get_items() as $item ) {

			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item_Product $item
			 */
			if ( ! $this->check_mi_product( $item->get_product_id() ) ) {
				continue;
			}

			$inventories = Inventory::get_order_item_inventories( $item->get_id(), Globals::get_order_type_id( PurchaseOrders::POST_TYPE ) );
			$new_data    = [];
			$inv_names   = [];

			foreach ( $inventories as $inventory ) {
				$inventory_object = AtumMIHelpers::get_inventory( $inventory->inventory_id );
				$inventory_data   = $inventory_object->get_all_data();

				$inv_names[ $inventory->inventory_id ] = $inventory_data['name'];

				if ( ! isset( $old_data[ $item->get_id() ][ $inventory->inventory_id ] ) ) {
					$log_data = [
						'source' => LogModel::SRC_MI,
						'module' => LogModel::MOD_MI_PURCHASE_ORDERS,
						'data'   => [
							'order_id'   => $order->get_id(),
							'order_name' => 'PO#' . $order->get_id(),
							'order_item' => [
								'id'   => $item->get_id(),
								'name' => $item->get_name(),
							],
							'inventory'  => [
								'id'   => $inventory->inventory_id,
								'name' => $inv_names[ $inventory->inventory_id ],
							],
							'new_data'   => [
								'qty'      => $inventory->qty,
								'subtotal' => $inventory->subtotal,
								'total'    => $inventory->total,
							],
						],
						'entry'  => LogEntry::ACTION_MI_PO_ITEM_INV_ADD,
					];
					LogModel::maybe_save_log( $log_data );
				}
				elseif ( $old_data[ $item->get_id() ][ $inventory->inventory_id ]['qty'] !== $inventory->qty ) {
					$log_data = [
						'source' => LogModel::SRC_MI,
						'module' => LogModel::MOD_MI_PURCHASE_ORDERS,
						'data'   => [
							'order_id'   => $order->get_id(),
							'order_name' => 'PO#' . $order->get_id(),
							'order_item' => [
								'id'   => $item->get_id(),
								'name' => $item->get_name(),
							],
							'inventory'  => [
								'id'   => $inventory->inventory_id,
								'name' => $inv_names[ $inventory->inventory_id ],
							],
							'old_data'   => $old_data[ $item->get_id() ][ $inventory->inventory_id ]['qty'],
							'new_data'   => $inventory->qty,
						],
						'entry'  => LogEntry::ACTION_MI_PO_ITEM_QTY,
					];
					LogModel::maybe_save_log( $log_data );
				}
				elseif ( $old_data[ $item->get_id() ][ $inventory->inventory_id ]['stock'] !== $inventory_data['stock_quantity'] ) {
					$log_data = [
						'source' => LogModel::SRC_MI,
						'module' => LogModel::MOD_MI_PURCHASE_ORDERS,
						'data'   => [
							'order_id'     => $order->get_id(),
							'order_name'   => 'PO#' . $order->get_id(),
							'order_item'   => [
								'id'   => $item->get_id(),
								'name' => $item->get_name(),
							],
							'product_id'   => $item->get_product_id(),
							'product_name' => $item->get_product()->get_name(),
							'inventory'    => [
								'id'   => $inventory->inventory_id,
								'name' => $inv_names[ $inventory->inventory_id ],
							],
							'old_data'     => $old_data[ $item->get_id() ][ $inventory->inventory_id ]['stock'],
							'new_data'     => $inventory_data['stock_quantity'],
						],
						'entry'  => LogEntry::ACTION_MI_PO_ITEM_STLEVEL,
					];
					LogModel::maybe_save_log( $log_data );
				}
				else {
					$new_data[ $inventory->inventory_id ] = [
						'qty'      => $inventory->qty,
						'subtotal' => $inventory->subtotal,
						'total'    => $inventory->total,
					];
				}
			}

			if ( ! empty( $new_data ) && $new_data !== $old_data[ $item->get_id() ] ) {
				$inventories_log = [];
				foreach ( $new_data as $inv_id => $inv_data ) {

					$inventories_log[ $inv_id ] = [
						'id'       => $inv_id,
						'name'     => $inv_names[ $inv_id ],
						'old_data' => $old_data[ $item->get_id() ][ $inv_id ],
						'new_data' => $inv_data,
					];
				}
				$log_data = [
					'source' => LogModel::SRC_MI,
					'module' => LogModel::MOD_MI_PURCHASE_ORDERS,
					'data'   => [
						'order_id'    => $order->get_id(),
						'order_name'  => 'PO#' . $order->get_id(),
						'order_item'  => [
							'id'   => $item->get_id(),
							'name' => $item->get_name(),
						],
						'inventories' => $inventories_log,
					],
					'entry'  => LogEntry::ACTION_MI_PO_INV_EDIT,
				];
				LogModel::maybe_save_log( $log_data );
			}
		}

		AtumCache::delete_transients( $transient_key_mi_po_data );
	}

	/**
	 * Register previous values for PO items inventories.
	 *
	 * @since 1.2.9
	 *
	 * @param bool          $check
	 * @param PurchaseOrder $order
	 *
	 * @return bool
	 */
	public function maybe_register_po_inventories_stock_levels( $check, $order ) {

		$this->mi_po_before_inventory( $order, [] );

		return $check;
	}

	/**
	 * Logs automatically usage of inventory in PO
	 *
	 * @param \WC_Order_Item $item
	 * @param mixed          $updated_order_item_inventories
	 * @param AtumOrderModel $order
	 *
	 * @since 0.3.1
	 */
	public function mi_po_auto_inventory( $item, $updated_order_item_inventories, $order ) {

		if ( empty( $order ) ) {
			return;
		}

		$inv_ids    = array();
		$order_type = $order instanceof \WC_Order ? $order->get_type() : $order->get_post_type();

		if ( PurchaseOrders::POST_TYPE === $order_type ) {
			$module = LogModel::MOD_MI_PURCHASE_ORDERS;
			$name   = 'PO#' . $order->get_id();
			$entry  = LogEntry::ACTION_MI_PO_INV_USED;
		}
		else {
			$module = LogModel::MOD_MI_ORDERS;
			$name   = '#' . $order->get_id();
			$entry  = LogEntry::ACTION_MI_INVENTORIES_USED;
		}

		foreach ( $updated_order_item_inventories[ $order->get_id() ][ $item->get_id() ]['insert'] as $inventory_id => $data ) {

			$inventory_object = AtumMIHelpers::get_inventory( $inventory_id );
			$inventory_data   = $inventory_object->get_all_data();

			if ( ! $this->check_mi_product( $inventory_object->product_id ) ) {
				continue;
			}

			$inv_ids[] = [
				'id'   => $inventory_id,
				'name' => $inventory_data['name'],
			];

			$log_data = [
				'source' => LogModel::SRC_MI,
				'module' => $module,
				'data'   => [
					'order_id'    => $order->get_id(),
					'order_name'  => $name,
					'order_item'  => [
						'id'   => $item->get_id(),
						'name' => $item->get_name(),
					],
					'inventories' => $inv_ids,
				],
				'entry'  => $entry,
			];

			LogModel::maybe_save_log( $log_data );

		}

	}

	/**
	 * Log for the API Request to create inventory
	 *
	 * @since 1.0.7
	 *
	 * @param Inventory        $prepared_inventory Inserted or updated post object.
	 * @param \WP_REST_Request $request            Request object.
	 * @param bool             $creating           True when creating a post, false when updating.
	 */
	public function mi_api_created_inventory( $prepared_inventory, $request, $creating ) {

		$data = $prepared_inventory->get_all_data();

		$this->log_inventory_create( $data, array( 'type' => 'api' ) );

	}

	/**
	 * Check for the previous values of inventory before edit
	 *
	 * @since 1.0.7
	 *
	 * @param Inventory        $prepared_inventory Inserted or updated post object.
	 * @param \WP_REST_Request $request            Request object.
	 * @param bool             $creating           True when creating a post, false when updating.
	 */
	public function mi_api_before_update_inventory( $prepared_inventory, $request, $creating ) {

		$dump_data = $prepared_inventory->get_all_data();

		$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_api_inventory_data_' . $prepared_inventory->id );
		AtumCache::set_transient( $transient_key_metadata, $dump_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Log for the API Request to edit inventory
	 *
	 * @since 1.0.7
	 *
	 * @param Inventory        $inventory   Inserted or updated post object.
	 * @param \WP_REST_Request $request     Request object.
	 * @param bool             $creating    True when creating a post, false when updating.
	 */
	public function mi_api_after_updated_inventory( $inventory, $request, $creating ) {

		$transient_key_metadata = AtumCache::get_transient_key( 'log_mi_api_inventory_data_' . $inventory->id );
		$old_data               = AtumCache::get_transient( $transient_key_metadata, TRUE );

		$this->log_inventory_edit( $inventory, $old_data );

	}

	/**
	 * Log for the API Request to delete inventory
	 *
	 * @since 1.0.7
	 *
	 * @param Inventory         $inventory   Inserted or updated post object.
	 * @param \WP_REST_Response $response    Response object.
	 * @param \WP_REST_Request  $request     Request object.
	 */
	public function mi_api_delete_inventory( $inventory, $response, $request ) {

		$data    = $response->get_data();
		$product = wc_get_product( $data['product_id'] );

		$log_data = [
			'source' => LogModel::SRC_MI,
			'module' => LogModel::MOD_MI_PRODUCT_DATA,
			'data'   => [
				'product_id'   => $product->get_id(),
				'product_name' => $product->get_name(),
				'inventory'    => [
					'id'   => $data['id'],
					'name' => $data['name'],
				],
			],
			'entry'  => LogEntry::ACTION_MI_INVENTORY_DELETE,
		];

		if ( 'variation' === $product->get_type() ) {
			$log_data['data']['product_parent'] = $product->get_parent_id();
		}

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Collect MI information for PL logs.
	 *
	 * @param array   $data
	 * @param integer $product_id
	 *
	 * @since 1.1.8
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function get_log_order_item_extra_data( $data, $product_id ) {

		if ( AtumMIHelpers::is_product_multi_inventory_compatible( $product_id ) ) {
			$inventories = AtumMIHelpers::get_product_inventories_sorted( $product_id );

			if ( ! empty( $inventories ) ) {
				foreach ( $inventories as $inventory ) {

					$data['inventories'][] = array(
						'id'   => $inventory->id,
						'name' => $inventory->name,
					);
				}
			}
		}

		return $data;

	}

	/**
	 * Add inventory info to log data.
	 *
	 * @since 1.2.0
	 *
	 * @param array $log_data
	 * @param array|DeliveryItemProductInventory $item_data
	 *
	 * @return array
	 */
	public function add_inventory_to_log_data( $log_data, $item_data ) {

		if ( $item_data instanceof DeliveryItemProductInventory || 'inventory' === $item_data['type'] && isset( $item_data['inventoryId'] ) ) {
			if ( $item_data instanceof DeliveryItemProductInventory ) {
				$inventory = AtumMIHelpers::get_inventory( $item_data->get_inventory_id() );
			}
			else {
				$inventory = AtumMIHelpers::get_inventory( $item_data['inventoryId'] );
			}

			if ( ! $inventory ) {
				return [];
			}

			if ( isset( $log_data['data']['product_name'] ) ) {
				$log_data['data']['product_name'] .= ' - ' . $inventory->name;
			}
			$log_data['data']['old_quantity']   = $inventory->stock_quantity;
			$log_data['data']['inventory_id']   = $inventory->id;
			$log_data['data']['inventory_name'] = $inventory->name;
		}

		return $log_data;
	}

	/**
	 * Log Inventory changes from Stock Central.
	 *
	 * @since 1.2.2
	 *
	 * @param array       $data
	 * @param \WC_Product $product
	 *
	 * @throws \Exception
	 */
	public function sc_log_update_product_data( $data, $product ) {

		$product_inventories = AtumMIHelpers::get_product_inventories_sorted( $product->get_id() );

		foreach ( $product_inventories as $inv ) {

			$transient_key = AtumCache::get_transient_key( 'log_list_table_' . $product->get_id() . '_' . $inv->id );
			$old_values    = AtumCache::get_transient( $transient_key, TRUE );

			if ( empty( $old_values ) ) {
				continue;
			}

			if ( ! isset( $old_values['inv'] ) || empty( $old_values['inv'] ) ) {
				continue;
			}

			$inventory = AtumMIHelpers::get_inventory( $old_values['inv'] );

			unset( $old_values['inv'] );

			$this->log_inventory_edit( $inventory, $old_values );
		}
	}

	/**
	 * Log inventory create.
	 *
	 * @since 1.2.5
	 *
	 * @param Inventory    $inventory
	 * @param array|string $source
	 */
	public function mi_log_create_inventory( $inventory, $source ) {

		$data = $inventory->get_all_data();

		if ( is_numeric( $source ) && PurchaseOrders::POST_TYPE === get_post_type( absint( $source ) ) ) {

			$source = array(
				'type' => 'purchase_order',
				'id'   => absint( $source ),
			);

		}
		elseif ( $source && ! is_array( $source ) ) {

			$source = array(
				'type' => $source,
			);

		}

		$this->log_inventory_create( $data, $source );

	}

	/**
	 * After adding an order item inventory
	 *
	 * @param AtumOrderModel $atum_order
	 * @param AtumOrderItem  $item
	 * @param Inventory      $inventory
	 * @param int|float      $qty
	 *
	 * @throws \Exception
	 */
	public function mi_log_added_order_item_inventory( $atum_order, $item, $inventory, $qty ) {
		$log_data = [
			'source' => LogModel::SRC_MI,
			'module' => LogModel::MOD_MI_PURCHASE_ORDERS,
			'data'   => [
				'order_id'   => $atum_order->get_id(),
				'order_name' => 'PO#' . $atum_order->get_id(),
				'order_item' => [
					'id'   => $item->get_id(),
					'name' => $item->get_name(),
				],
				'inventory'  => [
					'id'   => $inventory->id,
					'name' => $inventory->name,
				],
				'quantity'   => $qty,
			],
			'entry'  => LogEntry::ACTION_MI_PO_ITEM_INV_ADD,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Store transient with inventory stock levels
	 *
	 * @since 1.3.4
	 *
	 * @param \WC_Product|Inventory $product
	 *
	 * @return \WC_Product|Inventory
	 */
	public function check_item_stock_levels( $product ) {

		if ( $product instanceof Inventory ) {
			$inventory_data = [
				'stock'  => $product->stock_quantity,
				'status' => $product->stock_status,
			];

			$transient_key_stocklevel = AtumCache::get_transient_key( 'log_inventory_stocklevels_' . $product->id );
			AtumCache::set_transient( $transient_key_stocklevel, $inventory_data, MINUTE_IN_SECONDS, TRUE );
		}

		return $product;
	}

	/**
	 * @since 1.3.4
	 *
	 * @param boolean               $check
	 * @param \WC_Product|Inventory $product
	 * @param AtumOrderModel        $atum_order
	 *
	 * @return bool|mixed
	 * @throws \Exception
	 */
	public function log_item_stock_levels( $check, $product, $atum_order ) {

		if ( $product instanceof Inventory ) {

			$inventory = $product;
			$product   = AtumHelpers::get_atum_product( $inventory->product_id );

			$new_stock  = $inventory->stock_quantity;
			$new_status = $inventory->stock_status;

			$transient_key = AtumCache::get_transient_key( 'log_inventory_stocklevels_' . $inventory->id );
			$stock_levels  = AtumCache::get_transient( $transient_key, TRUE );

			$old_stock  = $stock_levels['stock'];
			$old_status = $stock_levels['status'];

			if ( ! empty( $stock_levels ) && ( $new_stock !== $old_stock || $new_status !== $old_status ) ) {

				$log_data = [
					'source' => LogModel::SRC_ST,
					'module' => LogModel::MOD_ST_ORDERS,
					'entry'  => LogEntry::ACTION_ST_RECONCILE_INV_STLVL,
					'data'   => [
						'st_id'          => $atum_order->get_id(),
						'st_name'        => $atum_order->get_title(),
						'inventory_id'   => $inventory->id,
						'inventory_name' => $inventory->name,
						'product_id'     => $product->get_id(),
						'product_name'   => $product->get_name(),
						'old_stock'      => $old_stock,
						'new_stock'      => $new_stock,
						'old_status'     => $old_status,
						'new_status'     => $new_status,
					],
				];
				if ( 'variation' === $product->get_type() ) {
					$log_data['data']['product_parent'] = $product->get_parent_id();
				}
				LogModel::maybe_save_log( $log_data );
			}

			AtumCache::delete_transients( $transient_key );

			return TRUE;
		}

		return $check;
	}

	/********************
	 * Instance methods
	 ********************/

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
	 * @return MultiInventory instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
