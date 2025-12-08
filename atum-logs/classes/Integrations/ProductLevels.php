<?php
/**
 * Product Levels + Atum Action Logs integration
 *
 * @package        AtumLogs
 * @subpackage     Integrations
 * @author         BE REBEL - https://berebel.studio
 * @copyright      ©2025 Stock Management Labs™
 *
 * @since          0.4.1
 */

namespace AtumLogs\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Inc\Globals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Addons\Addons;
use AtumLogs\Inc\Helpers;
use AtumMultiInventory\Inc\Helpers as AtumMIHelpers;
use AtumMultiInventory\Models\Inventory;
use AtumLevels\Models\BOMModel;
use AtumLevels\Models\BOMOrderItemsModel;
use AtumLevels\Inc\Globals as PLGlobals;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use Automattic\WooCommerce\Utilities\OrderUtil;
use \WC_Order_Item_Product;
use \WC_Order;


class ProductLevels {
	
	/**
	 * The singleton instance holder
	 *
	 * @var ProductLevels
	 */
	private static $instance;

	/**
	 * Store current orders data to log changes.
	 *
	 * @since 0.5.1
	 *
	 * @var array
	 */
	private $current_orders_data = [];
	
	
	/**
	 * ProductLevels integration constructor.
	 *
	 * @since 0.4.1
	 */
	private function __construct() {

		if ( is_admin() ) {

			add_action( 'pre_post_update', array( $this, 'pl_before_save' ), 10, 2 );
			add_action( 'save_post', array( $this, 'pl_after_save' ), 10, 3 );
			add_action( 'woocommerce_ajax_add_order_item_meta', array( $this, 'pl_add_order_item' ), PHP_INT_MAX, 3 );

			foreach ( [ 'increase', 'decrease' ] as $action ) {
				add_filter( "atum/product_levels/maybe_{$action}_bom_stock_order_items", array( $this, "before_{$action}_bom_order_item_inventories" ), 1, 6 );
				add_filter( "atum/product_levels/maybe_{$action}_bom_stock_order_items", array( $this, "after_{$action}_bom_order_item_inventories" ), PHP_INT_MAX, 6 );
			}

			// Save BOM data for variation product.
			add_action( 'wp_ajax_woocommerce_save_variations', array( $this, 'wc_before_save_variations' ), 2 );
			add_action( 'woocommerce_ajax_save_product_variations', array( $this, 'wc_after_save_variations' ) );

		}

		add_filter( 'atum/logs/product_stock_levels', array( $this, 'check_bom_stock_levels' ), 10, 3 );
		add_action( 'atum/logs/check_product_stock_levels', array( $this, 'register_bom_stock_levels' ) );
		add_action( 'atum/logs/log_product_stock_levels', array( $this, 'log_bom_stock_levels' ), PHP_INT_MAX, 2 );

		add_action( 'atum/product_levels/after_change_bom_stock_order', array( $this, 'log_order_items_stock_levels' ), PHP_INT_MAX, 2 );
		add_action( 'atum/product_levels/updated_calculated_stock', array( $this, 'log_saved_calculated_stock' ) );

		add_action( 'atum/ajax/tool_sync_real_stock', array( $this, 'log_sync_calculated_stock' ), 10, 2 );

		add_action( 'atum/product_levels/produce_items/before_decrease_bom_stock', array( $this, 'log_decrease_bom_stock_after_produce' ), 10, 3 );
		add_action( 'atum/product_levels/produce_items/after_increase_product_stock', array( $this, 'log_produce_stock' ), 10, 3 );

	}

	/**
	 * Checks Linked BOMs previous values for a product
	 *
	 * @param int   $post_ID
	 * @param mixed $data
	 *
	 * @since 0.3.1
	 */
	public function pl_before_save( $post_ID, $data ) {

		$product = AtumHelpers::get_atum_product( $post_ID, TRUE );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );
		$dump_data   = [];

		foreach ( $linked_boms as $lb ) {
			$dump_data[ $lb->bom_id ] = (array) $lb;
		}

		$transient_key_bom_data = AtumCache::get_transient_key( 'log_product_bom_data_' . $post_ID );
		AtumCache::set_transient( $transient_key_bom_data, $dump_data, MINUTE_IN_SECONDS, TRUE );
	}

	/**
	 * Logs Linked BOMs changes for a product
	 *
	 * @param int      $post_ID
	 * @param \WP_Post $post
	 * @param mixed    $update
	 *
	 * @since 0.3.1
	 */
	public function pl_after_save( $post_ID, $post, $update ) {

		$product = AtumHelpers::get_atum_product( $post_ID, TRUE );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$is_variation = 'variation' === $product->get_type();

		$transient_key_bom_data = AtumCache::get_transient_key( 'log_product_bom_data_' . $post_ID );
		$old_data               = AtumCache::get_transient( $transient_key_bom_data, TRUE );

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );
		$new_data    = [];

		foreach ( $linked_boms as $lb ) {
			$new_data[ $lb->bom_id ] = (array) $lb;
		}

		if ( ! empty( $old_data ) ) {

			foreach ( $old_data as $bom_id => $odata ) {

				if ( apply_filters( 'atum/logs/is_product_bom', TRUE, $odata ) ) {
					$bom_product      = AtumHelpers::get_atum_product( $bom_id, TRUE );
					$bom_name         = $bom_product->get_name();
					$is_bom_variation = 'variation' === $bom_product->get_type();
					$bom_parent_id    = $bom_product->get_parent_id();
				}
				else {
					$is_bom_variation = $bom_parent_id = FALSE;
					$bom_name         = apply_filters( 'atum/logs/get_bom_name', '', $bom_id );
				}

				if ( ! isset( $new_data[ $bom_id ] ) ) {

					$data = [
						'product_id'   => $product->get_id(),
						'product_name' => $product->get_name(),
						'bom_id'       => $bom_id,
						'bom_name'     => $bom_name,
						'linked_bom'   => $odata,
					];

					if ( $is_variation ) {
						$data['product_parent'] = $product->get_parent_id();
					}

					if ( $is_bom_variation ) {
						$data['bom_parent'] = $bom_parent_id;
					}

					$log_data = [
						'source' => LogModel::SRC_PL,
						'module' => LogModel::MOD_PL_PRODUCT_DATA,
						'data'   => $data,
						'entry'  => 'raw_material' === $odata['bom_type'] ? LogEntry::ACTION_PL_UNLINK_RAW_MAT : LogEntry::ACTION_PL_UNLINK_PROD_PART,
					];

					LogModel::maybe_save_log( $log_data );

				}
				elseif ( $odata['qty'] !== $new_data[ $bom_id ]['qty'] ) {

					$data = [
						'product_id'   => $product->get_id(),
						'product_name' => $product->get_name(),
						'bom_id'       => $bom_id,
						'bom_name'     => $bom_name,
						'old_data'     => $odata,
						'new_data'     => $new_data[ $bom_id ],
					];

					if ( $is_variation ) {
						$data['product_parent'] = $product->get_parent_id();
					}

					if ( $is_bom_variation ) {
						$data['bom_parent'] = $bom_parent_id;
					}

					$log_data = [
						'source' => LogModel::SRC_PL,
						'module' => LogModel::MOD_PL_PRODUCT_DATA,
						'data'   => $data,
						'entry'  => LogEntry::ACTION_PL_BOM_EDIT_QTY,
					];

					LogModel::maybe_save_log( $log_data );

				}

			}

		}

		foreach ( $new_data as $bom_id => $ndata ) {

			if ( ! isset( $old_data[ $bom_id ] ) ) {

				if ( apply_filters( 'atum/logs/is_product_bom', TRUE, $ndata ) ) {
					$bom_product      = AtumHelpers::get_atum_product( $bom_id, TRUE );
					$bom_name         = $bom_product->get_name();
					$is_bom_variation = 'variation' === $bom_product->get_type();
					$bom_parent_id    = $bom_product->get_parent_id();
				}
				else {
					$is_bom_variation = $bom_parent_id = FALSE;
					$bom_name         = apply_filters( 'atum/logs/get_bom_name', '', $bom_id );
				}

				$data = [
					'product_id'   => $product->get_id(),
					'product_name' => $product->get_name(),
					'bom_id'       => $bom_id,
					'bom_name'     => $bom_name,
					'linked_bom'   => $ndata,
				];

				if ( $is_variation ) {
					$data['product_parent'] = $product->get_parent_id();
				}

				if ( $is_bom_variation ) {
					$data['bom_parent'] = $bom_parent_id;
				}

				$log_data = [
					'source' => LogModel::SRC_PL,
					'module' => LogModel::MOD_PL_PRODUCT_DATA,
					'data'   => $data,
					'entry'  => 'raw_material' === $ndata['bom_type'] ? LogEntry::ACTION_PL_LINK_RAW_MAT : LogEntry::ACTION_PL_LINK_PROD_PART,
				];

				LogModel::maybe_save_log( $log_data );

			}
		}

		AtumCache::delete_transients( $transient_key_bom_data );

	}

	/**
	 * Logs adding an line item to a wc order
	 *
	 * @param integer               $item_id
	 * @param WC_Order_Item_Product $item
	 * @param WC_Order              $order
	 *
	 * @since 0.3.1
	 *
	 * @throws \Exception
	 */
	public function pl_add_order_item( $item_id, $item, $order ) {

		$product = $item->get_product();

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );

		if ( empty( $linked_boms ) ) {
			return;
		}

		$data['order_id']        = $order->get_id();
		$data['order_name']      = '#' . $order->get_id();
		$data['order_item_id']   = $item_id;
		$data['order_item_name'] = $item->get_name();
		$data['product_id']      = $product->get_id();
		$data['product_name']    = $product->get_name();

		if ( 'variation' === $product->get_type() ) {
			$data['product_parent'] = $product->get_parent_id();
		}

		foreach ( $linked_boms as $linked_bom ) {

			// Make sure it's a product part or a raw material.
			if ( ! in_array( $linked_bom->bom_type, PLGlobals::get_linked_bom_product_types() ) ) {
				continue;
			}

			$bom_product = AtumHelpers::get_atum_product( $linked_bom->bom_id, TRUE );

			$bom_data = array(
				'id'       => $linked_bom->bom_id,
				'name'     => $bom_product->get_name(),
				'quantity' => $linked_bom->qty * $item->get_quantity(),
				'stock'    => $bom_product->get_stock_quantity(),
			);

			if ( 'variation' === $bom_product->get_type() ) {
				$bom_data['parent'] = $bom_product->get_parent_id();
			}

			$data['bom_list'][] = apply_filters( 'atum/action_logs/order_item_extra_data', $bom_data, $linked_bom->bom_id );

		}

		// Default entry.
		$entry = LogEntry::ACTION_PL_BOM_USED;

		if ( Addons::is_addon_active( 'multi_inventory' ) && AtumMIHelpers::is_product_multi_inventory_compatible( $product ) ) {
			$entry = LogEntry::ACTION_PL_BOM_INV_USED;
		}

		$log_data = [
			'source' => LogModel::SRC_PL,
			'module' => LogModel::MOD_PL_ORDERS,
			'data'   => $data,
			'entry'  => $entry,
		];

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Call to the store current order data function before the changes.
	 *
	 * @since 0.5.1
	 *
	 * @param bool                                       $decrease
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 *
	 * @return bool
	 */
	public function before_increase_bom_order_item_inventories( $decrease, $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		$this->log_bom_order_item_changes( 'decrease', $order_item, $bom_id, $qty, $changed_qty, $order_type_id );
		$this->store_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id );

		return $decrease;

	}

	/**
	 * Call to the store current order data function before the changes.
	 *
	 * @since 0.5.1
	 *
	 * @param bool                                       $increase
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 *
	 * @return bool
	 */
	public function before_decrease_bom_order_item_inventories( $increase, $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		$this->log_bom_order_item_changes( 'increase', $order_item, $bom_id, $qty, $changed_qty, $order_type_id );
		$this->store_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id );

		return $increase;
	}

	/**
	 * Log if the current BOM order has changed
	 *
	 * @since 0.5.1
	 *
	 * @param string                                     $operation
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 */
	private function log_bom_order_item_changes( $operation, $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		if ( $qty - $changed_qty ) {

			switch ( $order_type_id ) {
				// WC Orders.
				case Globals::get_order_type_id():
					$order_id = $order_item->get_order_id();
					$entry    = LogEntry::ACTION_PL_ORDER_ITEM_QTY;
					break;

				// Purchase Orders.
				case Globals::get_order_type_id( \Atum\PurchaseOrders\PurchaseOrders::POST_TYPE ):
					$order_id = $order_item->get_atum_order_id();
					$entry    = LogEntry::ACTION_PL_PO_ORDER_ITEM_QTY;
					break;

				// Inventory Logs.
				case Globals::get_order_type_id( \Atum\InventoryLogs\InventoryLogs::POST_TYPE ):
					$order_id = $order_item->get_atum_order_id();
					$entry    = LogEntry::ACTION_PL_IL_ORDER_ITEM_QTY;
					break;

				default:
					return;
			}

			$log_data = [
				'source' => LogModel::SRC_PL,
				'module' => LogModel::MOD_PL_ORDERS,
				'data'   => [
					'order_id'        => $order_id,
					'order_name'      => '#' . $order_id,
					'order_item_id'   => $order_item->get_id(),
					'order_item_name' => $order_item->get_name(),
					'linked_bom'      => $bom_id,
					'old_value'       => $changed_qty,
					'new_value'       => $qty,
				],
				'entry'  => $entry,
			];

			if ( Addons::is_addon_active( 'multi_inventory' ) ) {

				$bom_order_items = BOMOrderItemsModel::get_bom_order_items( $order_item->get_id(), $order_type_id, FALSE );

				if ( ! empty( $bom_order_items ) ) {
					foreach ( $bom_order_items as $bom_order_item ) {
						if ( $bom_order_item->bom_id === $bom_id && ! is_null( $bom_order_item->inventory_id ) ) {

							$inventory = new Inventory( $bom_order_item->inventory_id );

							if ( ! is_wp_error( $inventory ) ) {

								$log_data['data']['inventories'][] = array(
									'id'   => $bom_order_item->inventory_id,
									'name' => $inventory->name,
								);
							}

						}
					}
				}

			}

			LogModel::maybe_save_log( $log_data );

		}
	}

	/**
	 * Store the current bom order data inventories info in a local variable to log the changes after processing
	 *
	 * @since 0.5.1
	 *
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 */
	private function store_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		if ( ! Addons::is_addon_active( 'multi_inventory' ) ) {
			return;
		}

		$order_id = Globals::get_order_type_id() === $order_type_id ? $order_item->get_order_id() : $order_item->get_atum_order_id();
		if ( ! isset( $this->current_orders_data[ $order_id ] ) ) {
			$this->current_orders_data[ $order_id ] = [];
		}

		// Only execute this once.
		$order_item_id = $order_item->get_id();
		if ( ! isset( $this->current_orders_data[ $order_id ][ $order_item_id ] ) ) {

			$this->current_orders_data[ $order_id ][ $order_item_id ] = [];
			$bom_order_items = BOMOrderItemsModel::get_bom_order_items( $order_item_id, $order_type_id, FALSE );

			foreach ( $bom_order_items as $bom_order_item ) {

				if ( $bom_order_item->inventory_id ) {

					if ( ! isset( $this->current_orders_data[ $order_id ][ $order_item_id ][ $bom_order_item->bom_id ] ) ) {
						$this->current_orders_data[ $order_id ][ $order_item_id ][ $bom_order_item->bom_id ] = [];
					}

					$this->current_orders_data[ $order_id ][ $order_item_id ][ $bom_order_item->bom_id ][ $bom_order_item->inventory_id ] = $bom_order_item;

				}

			}

		}

	}

	/**
	 * Call to the store current order data function before the changes.
	 *
	 * @since 0.5.1
	 *
	 * @param bool                                       $decrease
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 *
	 * @return bool
	 */
	public function after_increase_bom_order_item_inventories( $decrease, $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		$this->log_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id );

		return $decrease;

	}

	/**
	 * Call to the store current order data function before the changes.
	 *
	 * @since 0.5.1
	 *
	 * @param bool                                       $increase
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 *
	 * @return bool
	 */
	public function after_decrease_bom_order_item_inventories( $increase, $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		$this->log_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id );

		return $increase;
	}

	/**
	 * Log the changes in the bom order items inventories.
	 *
	 * @since 0.5.1
	 *
	 * @param WC_Order_Item_Product|AtumOrderItemProduct $order_item
	 * @param int                                        $bom_id
	 * @param float|int                                  $qty
	 * @param float|int                                  $changed_qty
	 * @param int                                        $order_type_id
	 */
	private function log_current_order_data_info( $order_item, $bom_id, $qty, $changed_qty, $order_type_id ) {

		if ( ! Addons::is_addon_active( 'multi_inventory' ) ) {
			return;
		}

		$order_id = Globals::get_order_type_id() === $order_type_id ? $order_item->get_order_id() : $order_item->get_atum_order_id();

		// Only execute this once.
		if ( ! isset( $this->current_orders_data[ $order_id ] ) ) {
			return;
		}

		$order_item_id = $order_item->get_id();
		if ( isset( $this->current_orders_data[ $order_id ][ $order_item_id ] ) ) {

			$bom_order_items = BOMOrderItemsModel::get_bom_order_items( $order_item_id, $order_type_id, FALSE );
			$new_order_items = [];

			foreach ( $bom_order_items as $bom_order_item ) {

				if ( $bom_order_item->inventory_id ) {

					if ( ! isset( $new_order_items[ $bom_order_item->bom_id ] ) ) {
						$new_order_items[ $bom_order_item->bom_id ] = [];
					}

					$new_order_items[ $bom_order_item->bom_id ][ $bom_order_item->inventory_id ] = $bom_order_item;

				}

			}

			foreach ( $new_order_items as $current_bom_id => $bom_order_item_inventories ) {

				$changed              = FALSE;
				$original_inventories = $new_inventories = $used_inventories = [];

				foreach ( $bom_order_item_inventories as $inventory_id => $bom_order_item_inventory ) {

					$inventory                         = new Inventory( $inventory_id );
					$new_inventories[]                 = [
						'id'   => $inventory_id,
						'name' => $inventory->name,
					];
					$used_inventories[ $inventory_id ] = $inventory->name;

					if ( empty( $this->current_orders_data[ $order_id ][ $order_item_id ][ $current_bom_id ][ $inventory_id ] ) ) {
						$changed = TRUE;
					}

				}

				if ( $changed ) {
					foreach ( $this->current_orders_data[ $order_id ][ $order_item_id ][ $current_bom_id ] as $inventory_id => $bom_order_item_inventory ) {

						if ( array_key_exists( $inventory_id, $used_inventories ) ) {
							$original_inventories[] = [
								'id'   => $inventory_id,
								'name' => $used_inventories[ $inventory_id ],
							];
						}
						else {
							$inventory              = new Inventory( $inventory_id );
							$original_inventories[] = [
								'id'   => $inventory_id,
								'name' => $inventory->name,
							];
						}

					}

					$bom_product = AtumHelpers::get_atum_product( $current_bom_id, TRUE );

					$data = [
						'product_id'         => $current_bom_id,
						'product_name'       => $bom_product->get_name(),
						'source_inventories' => $original_inventories,
						'target_inventories' => $new_inventories,
						'order_id'           => $order_id,
						'order_name'         => '#' . $order_id,
						'order_item_id'      => $order_item_id,
						'order_item_name'    => $order_item->get_name(),
					];

					if ( 'variation' === $bom_product->get_type() ) {
						$data['product_parent'] = $bom_product->get_parent_id();
					}

					$log_data = [
						'source' => LogModel::SRC_PL,
						'module' => LogModel::MOD_PL_ORDERS,
						'data'   => $data,
						'entry'  => LogEntry::ACTION_PL_ORDER_ITEM_BOMS,
					];
					LogModel::maybe_save_log( $log_data );

				}
			}

			unset( $this->current_orders_data[ $order_id ][ $order_item_id ] );
		}

	}

	/**
	 * Checks previous BOM data from variation products
	 *
	 * @since 1.0.0
	 */
	public function wc_before_save_variations() {

		check_ajax_referer( 'save-variations', 'security' );

		if ( ! current_user_can( 'edit_products' ) || empty( $_POST ) || empty( $_POST['product_id'] ) ) {
			return;
		}

		$product_id = absint( $_POST['product_id'] );
		$variations = get_posts(
			array(
				'post_parent'    => $product_id,
				'posts_per_page' => - 1,
				'post_type'      => 'product_variation',
				'fields'         => 'ids',
				'post_status'    => Globals::get_queryable_product_statuses(),
			)
		);

		$old_data = [];

		if ( ! empty( $variations ) ) {
			foreach ( $variations as $variation_id ) {
				$variation_boms = BOMModel::get_linked_bom( $variation_id );
				if ( ! empty( $variation_boms ) ) {
					foreach ( $variation_boms as $variation_bom ) {
						$old_data[ $variation_id ][ $variation_bom->bom_id ] = (array) $variation_bom;
					}
				}
			}
		}

		$transient_key = AtumCache::get_transient_key( 'log_pl_variations_' . $product_id );
		AtumCache::set_transient( $transient_key, $old_data, MINUTE_IN_SECONDS, TRUE );

	}

	/**
	 * Log save BOM data from variation products
	 *
	 * @since 1.0.0
	 *
	 * @param int $product_id
	 */
	public function wc_after_save_variations( $product_id ) {

		$transient_key = AtumCache::get_transient_key( 'log_pl_variations_' . $product_id );
		$old_data      = AtumCache::get_transient( $transient_key, TRUE );
		$variations    = get_posts(
			array(
				'post_parent'    => $product_id,
				'posts_per_page' => - 1,
				'post_type'      => 'product_variation',
				'fields'         => 'ids',
				'post_status'    => Globals::get_queryable_product_statuses(),
			)
		);

		if ( ! empty( $variations ) ) {

			$new_data = [];

			foreach ( $variations as $variation_id ) {

				$variation_boms = BOMModel::get_linked_bom( $variation_id );

				if ( ! empty( $variation_boms ) ) {
					foreach ( $variation_boms as $variation_bom ) {
						$new_data[ $variation_id ][ $variation_bom->bom_id ] = (array) $variation_bom;
					}
				}
			}
		}

		if ( ! empty( $new_data ) ) {

			foreach ( $new_data as $variation_id => $new_variation_data ) {

				$variation = AtumHelpers::get_atum_product( $variation_id, TRUE );

				foreach ( $new_variation_data as $bom_id => $new_bom_data ) {

					if ( ! isset( $old_data[ $variation_id ][ $bom_id ] ) ) {

						$bom_product      = AtumHelpers::get_atum_product( $bom_id, TRUE );
						$is_bom_variation = 'variation' === $bom_product->get_type();

						$data = [
							'product_id'     => $variation->get_id(),
							'product_name'   => $variation->get_name(),
							'product_parent' => $variation->get_parent_id(),
							'bom_id'         => $bom_id,
							'bom_name'       => $bom_product->get_name(),
							'linked_bom'     => $new_bom_data,
						];

						if ( $is_bom_variation ) {
							$data['bom_parent'] = $bom_product->get_parent_id();
						}

						$log_data = [
							'source' => LogModel::SRC_PL,
							'module' => LogModel::MOD_PL_PRODUCT_DATA,
							'data'   => $data,
							'entry'  => 'raw_material' === $new_bom_data['bom_type'] ? LogEntry::ACTION_PL_LINK_RAW_MAT : LogEntry::ACTION_PL_LINK_PROD_PART,
						];
						LogModel::maybe_save_log( $log_data );
					}
					elseif ( $new_bom_data['qty'] !== $old_data[ $variation_id ][ $bom_id ]['qty'] ) {

						$bom_product      = AtumHelpers::get_atum_product( $bom_id, TRUE );
						$is_bom_variation = 'variation' === $bom_product->get_type();

						$data = [
							'product_id'     => $variation->get_id(),
							'product_name'   => $variation->get_name(),
							'product_parent' => $variation->get_parent_id(),
							'bom_id'         => $bom_id,
							'bom_name'       => $bom_product->get_name(),
							'old_data'       => $new_bom_data,
							'new_data'       => $new_data[ $variation_id ][ $bom_id ],
						];
						if ( $is_bom_variation ) {
							$data['bom_parent'] = $bom_product->get_parent_id();
						}

						$log_data = [
							'source' => LogModel::SRC_PL,
							'module' => LogModel::MOD_PL_PRODUCT_DATA,
							'data'   => $data,
							'entry'  => LogEntry::ACTION_PL_BOM_EDIT_QTY,
						];
						LogModel::maybe_save_log( $log_data );
					}
				}
			}
		}

		if ( ! empty( $old_data ) ) {

			foreach ( $old_data as $variation_id => $old_variation_data ) {

				$variation = AtumHelpers::get_atum_product( $variation_id, TRUE );

				foreach ( $old_variation_data as $bom_id => $new_bom_data ) {

					if ( ! isset( $new_data[ $variation_id ][ $bom_id ] ) ) {

						$bom_product = AtumHelpers::get_atum_product( $bom_id, TRUE );
						$data        = [
							'product_id'     => $variation->get_id(),
							'product_name'   => $variation->get_name(),
							'product_parent' => $variation->get_parent_id(),
							'bom_id'         => $bom_id,
							'bom_name'       => $bom_product->get_name(),
							'linked_bom'     => $new_bom_data,
						];

						if ( 'variation' === $bom_product->get_type() ) {
							$data['bom_parent'] = $bom_product->get_parent_id();
						}

						$log_data = [
							'source' => LogModel::SRC_PL,
							'module' => LogModel::MOD_PL_PRODUCT_DATA,
							'data'   => $data,
							'entry'  => 'raw_material' === $new_bom_data['bom_type'] ? LogEntry::ACTION_PL_UNLINK_RAW_MAT : LogEntry::ACTION_PL_UNLINK_PROD_PART,
						];

						LogModel::maybe_save_log( $log_data );

					}

				}

			}

		}

	}

	/**
	 * Searchs for BOM stock levels
	 *
	 * @since 1.0.8
	 *
	 * @param array       $list
	 * @param int         $index
	 * @param \WC_Product $product
	 */
	public function check_bom_stock_levels( $list, $index, $product ) {

		if ( ! $product instanceof \WC_Product ) {
			return $list;
		}

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );

		if ( ! empty( $linked_boms ) ) {

			foreach ( $linked_boms as $linked_bom ) {

				// Make sure it's a product part or a raw material.
				if ( ! in_array( $linked_bom->bom_type, PLGlobals::get_linked_bom_product_types() ) ) {
					continue;
				}

				$bom_product = AtumHelpers::get_atum_product( $linked_bom->bom_id, TRUE );

				if ( $bom_product instanceof \WC_Product ) {
					$list[ $index . $bom_product->get_id() ] = array(
						'product_id'   => $bom_product->get_id(),
						'product_name' => $bom_product->get_name(),
						'stock'        => $bom_product->get_stock_quantity(),
						'status'       => $bom_product->get_stock_status(),
					);
					$list                                    = apply_filters( 'atum/logs/product_stock_levels', $list, $index, $bom_product );
				}
			}
		}

		return $list;
	}

	/**
	 * Checks and remember previous stock levels values for the BOMs of a product.
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Product $product
	 */
	public function register_bom_stock_levels( $product ) {

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );

		if ( empty( $linked_boms ) ) {
			return;
		}

		foreach ( $linked_boms as $linked_bom ) {

			// Make sure it's a product part or a raw material.
			if ( ! in_array( $linked_bom->bom_type, PLGlobals::get_linked_bom_product_types() ) ) {
				continue;
			}

			$bom_product = AtumHelpers::get_atum_product( $linked_bom->bom_id, TRUE );
			do_action( 'atum/logs/check_product_stock_levels', $bom_product );
			Helpers::check_product_stock_levels( $bom_product );
		}

	}

	/**
	 * Log changes in stock levels values for the BOMs of a product.
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Product $product
	 */
	public function log_bom_stock_levels( $product, $order_id ) {

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$linked_boms = BOMModel::get_linked_bom( $product->get_id() );

		if ( empty( $linked_boms ) ) {
			return;
		}

		foreach ( $linked_boms as $linked_bom ) {

			// Make sure it's a product part or a raw material.
			if ( ! in_array( $linked_bom->bom_type, PLGlobals::get_linked_bom_product_types() ) ) {
				continue;
			}

			$bom_product = AtumHelpers::get_atum_product( $linked_bom->bom_id, TRUE );
			do_action( 'atum/logs/log_product_stock_levels', $bom_product, $order_id );
			Helpers::log_product_stock_levels( $bom_product, $order_id );

		}

	}

	/**
	 * Logs changes for stock levels in order items list
	 *
	 * @since 1.0.8
	 *
	 * @param int   $order_id
	 * @param array $items
	 */
	public function log_order_items_stock_levels( $order_id, $items ) {

		if ( AtumHelpers::is_using_hpos_tables() && OrderUtil::is_order( $order_id ) ) {
			$type = OrderUtil::get_order_type( $order_id );
		}
		else {
			$type = get_post_type( $order_id );
		}

		if ( ! $type ) {
			return;
		}

		/**
		 * Variable definition
		 *
		 * @var \WC_Order|AtumOrderModel $order
		 */
		$order = 'shop_order' === $type ? wc_get_order( $order_id ) : AtumHelpers::get_atum_order_model( $order_id, TRUE );

		if ( empty( $order ) || is_wp_error( $order ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			/**
			 * Variable definition
			 *
			 * @var WC_Order_Item_Product $item
			 */
			if ( 'line_item' !== $item->get_type() ) {
				continue;
			}

			do_action( 'atum/logs/log_product_stock_levels', $item->get_product(), $order_id );
			Helpers::log_product_stock_levels( $item->get_product(), $order_id );

		}

	}

	/**
	 * Logs changes for stock levels when calculated stock is saved.
	 *
	 * @since 1.0.8
	 *
	 * @param \WC_Product $product
	 */
	public function log_saved_calculated_stock( $product ) {

		Helpers::log_product_stock_levels( $product, 0, TRUE );

	}

	/**
	 * Sync every time the sync real stock function is executed.
	 *
	 * @since 1.2.2
	 *
	 * @param int $step
	 * @param int $offset
	 */
	public function log_sync_calculated_stock( $step, $offset ) {

		if ( is_null( $step ) ) {
			$data = [ 'type' => 'complete' ];
		}
		else {
			$data = [
				'type'   => 'partial',
				'step'   => $step,
				'offset' => $offset,
			];
		}

		$data['tool'] = __( 'Sync WooCommerce stock with calculated stock', ATUM_LOGS_TEXT_DOMAIN );

		LogModel::maybe_save_log( [
			'source' => LogModel::SRC_PL,
			'module' => LogModel::MOD_PL_SETTINGS,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_SET_RUN_TOOL,
		] );

	}

	/**
	 * Log BOM stock reduction after produce products.
	 *
	 * @since 1.4.0
	 *
	 * @param integer   $bom_id
	 * @param integer   $product_id
	 * @param int|float $quantity
	 *
	 * @throws \Exception
	 */
	public function log_decrease_bom_stock_after_produce( $bom_id, $product_id, $quantity ) {
		$product     = AtumHelpers::get_atum_product( $product_id );
		$bom_product = AtumHelpers::get_atum_product( $bom_id );

		$data = [
			'bom_id'       => $bom_id,
			'bom_name'     => $bom_product->get_name(),
			'old_stock'    => $bom_product->get_stock_quantity(),
			'new_stock'    => $bom_product->get_stock_quantity() - $quantity,
			'product_id'   => $product_id,
			'product_name' => $product->get_name(),
		];

		LogModel::maybe_save_log( [
			'source' => LogModel::SRC_PL,
			'module' => LogModel::MOD_PL_PRODUCT_DATA,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_PL_PRODUCE_BOM_STOCK,
		] );

	}

	/**
	 * Log BOM stock reduction after produce products.
	 *
	 * @since 1.4.0
	 *
	 * @param integer         $product_id
	 * @param int|float       $quantity
	 * @param Inventory|false $inventory
	 *
	 * @throws \Exception
	 */
	public function log_produce_stock( $product_id, $quantity, $inventory = FALSE ) {

		$product = AtumHelpers::get_atum_product( $product_id );
		$entry   = LogEntry::ACTION_PL_PRODUCE_STOCK;

		$data = [
			'product_id'   => $product_id,
			'product_name' => $product->get_name(),
			'qty'          => $quantity,
			'old_stock'    => $product->get_stock_quantity() - $quantity,
			'new_stock'    => $product->get_stock_quantity(),
		];

		if ( $inventory && Addons::is_addon_active( 'multi_inventory' ) ) {
			$data['inventory'] = array(
				'id'   => $inventory->id,
				'name' => $inventory->name,
			);
			$entry = LogEntry::ACTION_PL_PRODUCE_STOCK_INV;
		}

		LogModel::maybe_save_log( [
			'source' => LogModel::SRC_PL,
			'module' => LogModel::MOD_PL_PRODUCT_DATA,
			'data'   => $data,
			'entry'  => $entry,
		] );

	}

	/******************
	 * Instace methods
	 ******************/
	
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
	 * @return ProductLevels instance
	 */
	public static function get_instance() {
		
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
}
