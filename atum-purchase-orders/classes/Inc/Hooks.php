<?php
/**
 * Class Hooks
 *
 * @since       0.0.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Inc
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumAdminNotices;
use Atum\Components\AtumOrders\AtumComments;
use Atum\Components\AtumOrders\AtumOrderPostType;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Models\AtumOrderModel;
use Atum\Components\AtumStockDecimals;
use Atum\Models\Interfaces\AtumProductInterface;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Models\POExtended;
use Atum\Inc\Helpers as AtumHelpers;


final class Hooks {

	/**
	 * The singleton instance holder
	 *
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * Store inbound items already delivered stock for the Inbound List Table
	 *
	 * @since 1.0.3
	 *
	 * @var array
	 */
	private $delivered_inbound = [];


	/**
	 * Hooks singleton constructor
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		if ( is_admin() ) {
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

		// Use the right templates and args when adding a new PO item fee or PO item shipping.
		add_filter( 'atum/load_view/meta-boxes/atum-order/item-fee', array( $this, 'po_item_fee_view' ), 10, 2 );
		add_filter( 'atum/load_view/meta-boxes/atum-order/item-shipping', array( $this, 'po_item_shipping_view' ), 10, 2 );
		add_filter( 'atum/load_view_args/meta-boxes/atum-order/item-fee', array( $this, 'po_item_fee_and_shipping_view_args' ) );
		add_filter( 'atum/load_view_args/meta-boxes/atum-order/item-shipping', array( $this, 'po_item_fee_and_shipping_view_args' ) );

		// Add and sanitize PO extended extra data when saving a PO.
		add_filter( 'atum/purchase_orders/save_meta_boxes_props', array( $this, 'save_po_extra_props' ), 10, 2 );

		// Alter bulk actions for the PO.
		add_filter( 'atum/' . PurchaseOrders::POST_TYPE . '/bulk_actions', array( $this, 'po_bulk_actions' ) );
		add_filter( 'atum/purchase_orders/bulk_messages', array( $this, 'po_bulk_messages' ), 10, 2 );

		// Disable the automatic stock change for POs (we'll handle it manually in PO PRO).
		add_filter( 'atum/purchase_orders/can_reduce_order_stock', '__return_false', 101 ); // MI and PL are using this hook with a priority of 100.
		add_filter( 'atum/purchase_orders/can_restore_order_stock', '__return_false', 101 );
		add_action( 'atum/orders/status_changed', array( $this, 'prevent_decreasing_stock' ), 9, 4 ); // ATUM tries to decrease the stock with priority 10.
		add_action( 'atum/orders/status_atum_received', array( $this, 'prevent_increasing_stock' ), 9, 2 ); // ATUM tries to increase the stock with priority 10.

		// Parse added comments for searching users mentions.
		add_action( 'atum/ajax/atum_order/note_added', array( $this, 'parse_added_note' ), 10, 2 );

		// Use our own export class for PDF exports.
		add_filter( 'atum/purchase_orders/export_class', array( $this, 'pdf_export_class' ) );

		// Process the bulk actions.
		add_action( 'atum/ajax/list_table/bulk_action_applied', array( $this, 'process_bulk_action' ) );

		// Set the discount_config and tax_config meta keys as internal PO item meta keys.
		add_filter( 'atum/atum_order/order_item_internal_meta_keys', array( $this, 'po_item_internal_meta_keys' ), 10, 2 );

		// Save the discount_config and tax_config to the order items.
		add_filter( 'atum/orders/item_product/save_data', array( $this, 'save_po_item_data' ), 10, 2 );
		add_filter( 'atum/orders/item_fee/save_data', array( $this, 'save_po_item_data' ), 10, 2 );
		add_filter( 'atum/orders/item_shipping/save_data', array( $this, 'save_po_item_data' ), 10, 2 );

		// Change the PO titles.
		add_filter( 'admin_title', array( $this, 'change_admin_po_titles' ), 10, 2 );

		// Get PO when adding WC order items to it.
		add_filter( 'atum/ajax/import_wc_order_items/get_atum_order', array( $this, 'get_po_extended' ), 10, 2 );

		// Check whether to exclude some order items from the WC items import.
		add_filter( 'atum/ajax/import_wc_order_items/maybe_import_item', array( $this, 'maybe_exclude_imported_order_items' ), 10, 3 );

		// Filter the statuses used for the Inbound Stock list.
		add_filter( 'atum/purchase_orders/due_statuses', array( $this, 'purchase_orders_inbound_due_statuses' ) );

		// Modify the Inbound Stock ListTable data.
		add_filter( 'atum/inbound_stock_list/column_purchase_order', array( $this, 'po_numbers_on_inbound_stock_list' ), 10, 3 );
		add_action( 'atum/inbound_stock_list/after_prepare_items', array( $this, 'calculate_inbound_list_delivered_items' ) );
		add_filter( 'atum/inbound_stock_list/column_inbound_stock', array( $this, 'adjust_item_inbound_stock' ), 10, 3 );
		add_filter( 'atum/list_table/totalizers', array( $this, 'adjust_inbound_stock_total' ) );

		// Modify the product's inbound stock SQL query.
		add_filter( 'atum/product_inbound_stock/sql_select', array( $this, 'product_inbound_stock_select' ), 10, 2 );
		add_filter( 'atum/product_inbound_stock/sql_joins', array( $this, 'product_inbound_stock_joins' ), 10, 2 );

		// Recalculate the Gross Profit value in SC within the PO Settings.
		add_filter( 'atum/list_table/column_gross_profit', array( $this, 'calculate_gross_profit' ), 9, 3 ); // A lower priority so MI overrides it for MI products with multi-price.

		// Load PO PRO help guides.
		add_filter( 'atum/help_guides/guides_paths', array( $this, 'load_help_guides' ) );

		// Set PO bulk actions as executed.
		add_filter( 'atum/ajax/executed_bulk_action', array( $this, 'check_executed_bulk_action' ), 10, 3 );

		// Maybe bypass the supplier check when importing WC order items.
		add_filter( 'atum/orders/maybe_bypass_supplier_check', array( $this, 'maybe_bypass_supplier_check' ), 10, 2 );

	}

	/**
	 * Register the hooks that are executed globally
	 *
	 * @since 0.0.1
	 */
	public function register_global_hooks() {

		// Just replace the PO statuses with the PRO ones.
		add_filter( 'atum/purchase_orders/statuses', array( '\AtumPO\Inc\Globals', 'get_statuses' ) );
		add_filter( 'atum/purchase_orders/status_colors', array( '\AtumPO\Inc\Globals', 'get_status_colors' ) );

		// Use the POExtended model class instead of the ATUM's PurchaseOrder model.
		add_filter( 'atum/order_model_class', array( $this, 'get_po_extended_model_class' ), 10, 2 );

		// Trigger actions when the PO status changes.
		add_action( 'atum/orders/status_changed', array( $this, 'po_status_changed' ), 10, 4 );

	}

	/**
	 * Replaces the PurchaseOrder class by the POExtended model class
	 *
	 * @since 0.7.2
	 *
	 * @param string $model_class
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function get_po_extended_model_class( $model_class, $post_type ) {

		if ( PurchaseOrders::POST_TYPE === $post_type ) {
			$model_class = '\AtumPO\Models\POExtended';
		}

		return $model_class;
	}

	/**
	 * Save extra data when saving a PO.
	 *
	 * @since 0.8.6
	 *
	 * @param array      $props
	 * @param POExtended $po
	 *
	 * @return array
	 */
	public function save_po_extra_props( $props, $po ) {

		$extra_props = array();

		// Prepare the extra fields' formats.
		$meta_keys = array(
			'customer_name'         => 'string',
			'delivery_date'         => 'date',
			'delivery_terms'        => 'html',
			'delivery_to_warehouse' => 'string',
			'email_template'        => 'string',
			'number'                => 'string',
			'pdf_template'          => 'string',
			'requisitioner'         => 'integer',
			'sales_order_number'    => 'string',
			'ships_from'            => 'string',
			'ship_via'              => 'string',
			'fob'                   => 'string',
			'supplier_code'         => 'string',
			'supplier_currency'     => 'string',
			'supplier_discount'     => 'float',
			'supplier_reference'    => 'string',
			'supplier_tax_rate'     => 'float',
			'warehouse'             => 'string',
			'purchaser_name'        => 'string',
			'purchaser_address'     => 'string',
			'purchaser_address_2'   => 'string',
			'purchaser_city'        => 'string',
			'purchaser_postal_code' => 'string',
			'purchaser_state'       => 'string',
			'purchaser_country'     => 'string',
			'currency_pos'          => 'string',
			'price_thousand_sep'    => 'string',
			'price_decimal_sep'     => 'string',
			'price_num_decimals'    => 'integer',
			'exchange_rate'         => 'float',
		);

		// Sanitize the values.
		foreach ( $meta_keys as $key => $type ) {

			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}

			switch ( $type ) {
				case 'float':
					$extra_props[ $key ] = (float) $_POST[ $key ];
					break;

				case 'integer':
					$extra_props[ $key ] = (int) $_POST[ $key ];
					break;

				case 'date':
					$extra_props[ $key ] = ! empty( $_POST[ $key ] ) ? date_i18n( 'Y-m-d H:i:s', strtotime( $_POST[ $key ] ) ) : '';
					break;

				case 'html':
					$extra_props[ $key ] = wp_kses_post( $_POST[ $key ] );
					break;

				default:
					$extra_props[ $key ] = esc_attr( $_POST[ $key ] );
					break;
			}

		}

		// The PO number must be unique, so check it before saving a new one.
		if ( isset( $extra_props['number'] ) && $po->number !== $extra_props['number'] ) {

			if ( ! $extra_props['number'] ) {
				AtumAdminNotices::add_notice( __( 'The PO must have a number', ATUM_PO_TEXT_DOMAIN ), 'purchase_orders_pro_missing_number', 'error', TRUE, TRUE );
			}
			else {

				global $wpdb;
				$existing_po_number = $wpdb->get_var( $wpdb->prepare( "
					SELECT COUNT(*) FROM $wpdb->posts p 
				    LEFT JOIN $wpdb->postmeta pm ON (p.ID = pm.post_id AND pm.meta_key = '_number')
					WHERE p.post_type = %s AND pm.meta_value = %s AND p.ID != %d
				", PurchaseOrders::POST_TYPE, $extra_props['number'], $po->get_id() ) );

				if ( $existing_po_number ) {

					if ( $po->number ) {
						AtumAdminNotices::add_notice( __( 'The PO number must be unique. Your PO number change has been reverted.', ATUM_PO_TEXT_DOMAIN ), 'purchase_orders_pro_existing_number_reverted', 'error', TRUE, TRUE );
					}
					else {
						AtumAdminNotices::add_notice( __( 'The PO number must be unique. A new auto-number has been generated.', ATUM_PO_TEXT_DOMAIN ), 'purchase_orders_pro_existing_number_generated', 'error', TRUE, TRUE );
						$po->set_number();
					}

					$extra_props['number'] = $po->number;

				}

			}

		}

		return array_merge( $props, $extra_props );

	}

	/**
	 * Trigger actions after the PO status changes
	 *
	 * @since 0.8.9
	 *
	 * @param int            $id
	 * @param string         $old_status
	 * @param string         $new_status
	 * @param AtumOrderModel $atum_order
	 */
	public function po_status_changed( $id, $old_status, $new_status, $atum_order ) {

		if ( $atum_order instanceof POExtended ) {

			// Decrease the PO items when transitioning to "Returned" or "Cancelled" (if needed).
			// or increase when transitioning back to "Returning" from "Returned".
			if (
				( 'atum_cancelled' === $new_status && 'trash' !== $old_status ) ||
				( 'atum_returned' === $new_status && 'atum_returning' === $old_status ) ||
				( 'atum_returning' === $new_status && 'atum_returned' === $old_status )
			) {

				$po         = $atum_order->is_returning() ? AtumHelpers::get_atum_order_model( $atum_order->related_po, TRUE, PurchaseOrders::POST_TYPE ) : $atum_order;
				$po_items   = $atum_order->get_items();
				$deliveries = Deliveries::get_po_orders( $po->get_id() );
				$action     = ( 'atum_returning' === $new_status && 'atum_returned' === $old_status ) ? 'increase' : 'decrease';

				$po_item_qtys = [];

				// Recap the quantities needed for every product.
				foreach ( $po_items as $po_item ) {
					/**
					 * Variable definition
					 *
					 * @var POItemProduct $po_item
					 */
					$product_id = $po_item->get_variation_id() ? $po_item->get_variation_id() : $po_item->get_product_id();

					$po_item_qtys[ $product_id ] = $po_item->get_quantity();
				}

				foreach ( $deliveries as $delivery ) {

					/**
					 * Variable definition.
					 *
					 * @var Delivery $delivery
					 */
					$delivery_items = $delivery->get_items( array_values( apply_filters( 'atum/purchase_orders_pro/delivery/item_group_to_type', [ 'delivery_item' ] ) ) );

					foreach ( $delivery_items as $delivery_item ) {

						if ( ! $delivery_item instanceof DeliveryItemProduct ) {
							do_action( 'atum/purchase_orders_pro/po_status_changed/maybe_process_delivery_item', $delivery_item, $delivery, $atum_order, $action );
							continue;
						}

						// If this item's stock was increased previously, must undo the change.
						if ( 'yes' === $delivery_item->get_stock_changed() ) {

							$delivery_item_quantity = $total_delivery_item_qty = (float) $delivery_item->get_quantity() - $delivery_item->get_returned_qty();

							// The returning POs can be partially returned.
							if ( $atum_order->is_returning() ) {

								$product_id = $delivery_item->get_variation_id() ? $delivery_item->get_variation_id() : $delivery_item->get_product_id();

								if ( ! empty( $po_item_qtys[ $product_id ] ) ) {

									if ( $po_item_qtys[ $product_id ] <= $delivery_item_quantity ) {
										$delivery_item_quantity      = $po_item_qtys[ $product_id ];
										$po_item_qtys[ $product_id ] = 0; // Update the value so it isn't discounted again.
									}
									else {
										$po_item_qtys[ $product_id ] -= $delivery_item_quantity; // Update the value so it isn't discounted again.
									}

								}
								else {
									$delivery_item_quantity = 0;
								}

							}

							if ( $delivery_item_quantity > 0 ) {

								$delivery->change_product_stock( $delivery_item, $delivery_item_quantity, $action );
								//$delivery->change_product_stock( $delivery_item, $delivery_item_quantity, 'decrease' );

								// NOTE: it can be a partial return and, later, there could be another one. So we have to control the units discounted on each.
								if ( $total_delivery_item_qty !== (float) $delivery_item_quantity ) {
									$delivery_item->set_stock_changed( TRUE );
									$delivery_item->save();
								}

							}

						}
					}

				}

			}

		}

	}

	/**
	 * Edit bulk actions for the POs
	 *
	 * @since 0.8.9
	 *
	 * @param array $actions
	 *
	 * @return array;
	 */
	public function po_bulk_actions( $actions ) {

		if ( ! empty( $actions['trash'] ) ) {
			$actions['trash'] = __( 'Archive', ATUM_PO_TEXT_DOMAIN );
			unset( $actions['atum_order_mark_trash'] );
		}

		return $actions;
	}

	/**
	 * Edit the bulk messages shown after processing a bulk action
	 *
	 * @since 0.8.9
	 *
	 * @param array $bulk_messages
	 * @param array $bulk_counts
	 *
	 * @return array
	 */
	public function po_bulk_messages( $bulk_messages, $bulk_counts ) {

		/* translators: the number of purchase orders moved to the trash */
		$bulk_messages[ PurchaseOrders::POST_TYPE ]['trashed'] = _n( '%s PO was archived.', '%s POs were archived.', $bulk_counts['trashed'], ATUM_PO_TEXT_DOMAIN );
		/* translators: the number of purchase orders restored from the trash */
		$bulk_messages[ PurchaseOrders::POST_TYPE ]['untrashed'] = _n( '%s PO was restored.', '%s POs were restored.', $bulk_counts['untrashed'], ATUM_PO_TEXT_DOMAIN );

		return $bulk_messages;

	}

	/**
	 * Parse the added order note
	 *
	 * @param AtumOrderModel $atum_order
	 * @param int            $comment_id
	 */
	public function parse_added_note( $atum_order, $comment_id ) {

		if ( ! $atum_order instanceof AtumOrderModel ) {
			return;
		}

		if ( PurchaseOrders::POST_TYPE !== $atum_order->get_post_type() ) {
			return;
		}

		$comment = get_comment( $comment_id );

		if ( AtumComments::NOTES_KEY !== $comment->comment_type ) {
			return;
		}

		$note = $comment->comment_content;

		preg_match_all( '/@([^ \.,]+)/', $note, $mentions );

		if ( ! empty( $mentions ) && isset( $mentions[1] ) ) {

			$mentions = $mentions[1];
			$users    = array();

			foreach ( $mentions as $mention ) {

				if ( 'everyone' === $mention ) {
					$users[0] = TRUE;
				}
				else {
					$user = get_user_by( 'login', $mention );
					if ( empty( $user ) ) {
						$user = get_user_by( 'login', str_replace( '_', ' ', $mention ) );
					}

					$users[ $user->ID ] = TRUE;
				}
			}
			if ( ! empty( $users ) ) {
				add_comment_meta( $comment_id, 'po_notification', $users );
			}
		}

	}

	/**
	 * Use the extended export class for the PO PDF exports
	 *
	 * @since 0.9.7
	 *
	 * @param string $class_name
	 *
	 * @return string
	 */
	public function pdf_export_class( $class_name ) {
		return 'AtumPO\Exports\POExtendedExport';
	}

	/**
	 * Run bulk actions
	 *
	 * @since 0.9.15
	 *
	 * @param array $args
	 */
	public function process_bulk_action( &$args ) {

		/**
		 * Variable definition
		 *
		 * @var string $bulk_action
		 * @var int[]  $ids
		 * @var bool   $executed
		 * @var array  $extra_data
		 */
		extract( $args );

		$success = $failed = array();

		switch ( $bulk_action ) {
			case 'markPODraft':
			case 'markPONew':
			case 'markPOApproval':
			case 'markPOApproved':
			case 'markPOSent':
			case 'markPOReceived':
			case 'markPOOnthewayin':
			case 'markPOReceiving':
			case 'markPOPartiallyReceiving':
			case 'markPOQualityCheck':
			case 'markPOAdded':
			case 'markPOPartiallyAdded':
			case 'markPOCompleted':
			case 'markPOCancelled':
			case 'markPOReturned':
			case 'markPOArchived':
				foreach ( $ids as $id ) {

					if ( ! is_numeric( $id ) ) {
						continue;
					}

					$po = AtumHelpers::get_atum_order_model( absint( $id ), TRUE, PurchaseOrders::POST_TYPE );

					if ( ! $po->exists() ) {
						continue;
					}

					$new_status = '';

					switch ( $bulk_action ) {
						case 'markPODraft':
							$new_status = 'atum_pending';
							break;

						case 'markPONew':
							$new_status = 'atum_new';
							break;

						case 'markPOApproval':
							$new_status = 'atum_approval';
							break;

						case 'markPOApproved':
							$new_status = 'atum_approved';
							break;

						case 'markPOSent':
							$new_status = 'atum_ordered';
							break;

						case 'markPOReceived':
							$new_status = 'atum_vendor_received';
							break;

						case 'markPOOnthewayin':
							$new_status = 'atum_onthewayin';
							break;

						case 'markPOReceiving':
							$new_status = 'atum_receiving';
							break;

						case 'markPOPartiallyReceiving':
							$new_status = 'atum_part_receiving';
							break;

						case 'markPOQualityCheck':
							$new_status = 'atum_quality_check';
							break;

						case 'markPOAdded':
							$new_status = 'atum_added';
							break;

						case 'markPOPartiallyAdded':
							$new_status = 'atum_partially_added';
							break;

						case 'markPOCompleted':
							$new_status = 'atum_received';
							break;

						case 'markPOCancelled':
							$new_status = 'atum_cancelled';
							break;

						case 'markPOReturned':
							$new_status = 'atum_returned';
							break;

						case 'markPOArchived':
							$new_status = 'trash';
							break;
					}

					$changed = Helpers::maybe_change_po_status( $po, $new_status );

					if ( $changed ) {
						$success[] = $id;
					}
					else {
						$failed[] = $id;
					}

				}

				if ( wp_doing_ajax() ) {

					if ( ! empty( $failed ) || ( empty( $failed ) && empty( $success ) ) ) {

						if ( ! empty( $success ) ) {
							$message = __( "Action wasn't applied to some selected POs due to status change restrictions.", ATUM_PO_TEXT_DOMAIN );
							/* translators: the list of failed PO IDs */
							$message .= '<br>' . sprintf( _n( 'The failed PO is: #%s', 'The failed POs are: #%s', count( $failed ), ATUM_PO_TEXT_DOMAIN ), implode( ', #', $failed ) );
						}
						else {
							$message = _n( "Action wasn't applied to the selected PO due to status change restrictions.", "Action wasn't applied to any of the selected POs due to status change restrictions.", count( $ids ), ATUM_PO_TEXT_DOMAIN );
						}

						wp_send_json_error( $message );

					}
					elseif ( ! empty( $success ) ) {
						return; // Show the default message here.
					}

				}

				// Notify ATUM that it was executed.
				$args['executed'] = TRUE;
				break;

			case 'poClone':
				foreach ( $ids as $id ) {

					if ( ! is_numeric( $id ) ) {
						continue;
					}

					$po = AtumHelpers::get_atum_order_model( absint( $id ), TRUE, PurchaseOrders::POST_TYPE );

					if ( ! $po->exists() ) {
						continue;
					}

					$settings = array(
						'deliveries' => 'no',
						'invoices'   => 'no',
					);

					if ( ! empty( $extra_data['cloneDeliveries'] ) && 1 === absint( $extra_data['cloneDeliveries'] ) ) {
						$settings = [];
					}

					$duplicate_po = DuplicatePO::get_instance();
					$duplicate_po->duplicate( $po, $settings );

				}

				// Notify ATUM that it was executed.
				$args['executed'] = TRUE;
				break;

			case 'poUnarchive':
				foreach ( $ids as $id ) {

					if ( ! is_numeric( $id ) ) {
						continue;
					}

					$po = AtumHelpers::get_atum_order_model( absint( $id ), TRUE, PurchaseOrders::POST_TYPE );

					if ( ! $po->exists() ) {
						continue;
					}

					$old_status = $po->get_status();

					if ( 'trash' === $old_status ) {
						$po->set_status( 'atum_pending' );
						$po->save_meta();
					}

					$post = $po->get_post();

					if ( $old_status !== $post->post_status ) {
						wp_update_post( [
							'ID'          => $po->get_id(),
							'post_status' => $old_status,
						], FALSE, FALSE );
					}

				}

				// Notify ATUM that it was executed.
				$args['executed'] = TRUE;
				break;

			case 'poForceDelete':
				foreach ( $ids as $id ) {

					if ( ! is_numeric( $id ) ) {
						continue;
					}

					$po = AtumHelpers::get_atum_order_model( absint( $id ), TRUE, PurchaseOrders::POST_TYPE );

					if ( ! $po->exists() ) {
						continue;
					}

					$po->delete( TRUE );

				}

				// Notify ATUM that it was executed.
				$args['executed'] = TRUE;
				break;

			case 'createReturningPO':
				foreach ( $ids as $id ) {

					if ( ! is_numeric( $id ) ) {
						continue;
					}

					/**
					 * Variable definition
					 *
					 * @var POExtended $po
					 */
					$po = AtumHelpers::get_atum_order_model( absint( $id ), TRUE, PurchaseOrders::POST_TYPE );

					if ( ! $po->exists() || $po->is_returning() ) {
						continue;
					}

					ReturningPOs::create_returning_po( $po );

				}

				// Notify ATUM that it was executed.
				$args['executed'] = TRUE;
				break;
		}

	}

	/**
	 * Add custom PO item internal meta keys
	 *
	 * @since 0.9.24
	 *
	 * @param string[]             $internal_meta_keys
	 * @param AtumOrderItemProduct $atum_order_item
	 *
	 * @return string[]
	 */
	public function po_item_internal_meta_keys( $internal_meta_keys, $atum_order_item ) {

		$internal_meta_keys[] = '_discount_config';
		$internal_meta_keys[] = '_tax_config';

		return $internal_meta_keys;

	}

	/**
	 * Save the PO PRO's extra data to the PO items
	 *
	 * @since 0.9.24
	 *
	 * @param array                $item_data
	 * @param AtumOrderItemProduct $atum_order_item
	 *
	 * @return array
	 */
	public function save_po_item_data( $item_data, $atum_order_item ) {

		if ( $atum_order_item instanceof POItemProduct ) {
			$item_data['_discount_config'] = maybe_unserialize( $atum_order_item->get_meta( '_discount_config' ) );
			$item_data['_tax_config']      = maybe_unserialize( $atum_order_item->get_meta( '_tax_config' ) );
		}
		elseif ( $atum_order_item instanceof POItemFee || $atum_order_item instanceof POItemShipping ) {
			$item_data['_tax_config'] = maybe_unserialize( $atum_order_item->get_meta( '_tax_config' ) );
		}

		return $item_data;

	}

	/**
	 * Prevent decreasing stock when changing status except the status is changing for an old PO Free that was already marked as received (completed)
	 *
	 * @since 0.9.25
	 *
	 * @param int           $order_id
	 * @param string        $old_status
	 * @param string        $new_status
	 * @param PurchaseOrder $order
	 */
	public function prevent_decreasing_stock( $order_id, $old_status, $new_status, $order ) {

		if ( $order instanceof PurchaseOrder ) {

			$remove_action = TRUE;

			if ( 'atum_received' === $old_status && 'atum_received' === get_post_meta( $order_id, '_old_po_free_status', TRUE ) ) {

				$is_stock_returned = get_post_meta( $order_id, '_po_free_stock_returned', TRUE );
				$deliveries        = Deliveries::get_po_orders( $order_id );

				if ( 'yes' !== $is_stock_returned && empty( $deliveries ) ) {

					update_post_meta( $order_id, '_po_free_stock_returned', 'yes' );

					$remove_action = FALSE;
					remove_filter( 'atum/purchase_orders/can_reduce_order_stock', '__return_false', 101 );
					remove_filter( 'atum/multi_inventory/bypass_' . PurchaseOrders::get_post_type() . '_stock_change', '__return_true', 10 );
				}
			}

			if ( $remove_action ) {
				remove_action( 'atum/orders/status_changed', array( PurchaseOrders::get_instance(), 'maybe_decrease_stock_levels' ) );
			}
		}

	}

	/**
	 * Prevent increasing stock when new status is Received
	 *
	 * @since 0.9.25
	 *
	 * @param int           $order_id
	 * @param PurchaseOrder $order
	 */
	public function prevent_increasing_stock( $order_id, $order ) {

		if ( $order instanceof PurchaseOrder ) {
			remove_action( 'atum/orders/status_atum_received', array( PurchaseOrders::get_instance(), 'maybe_increase_stock_levels' ) );
		}
	}

	/**
	 * Load our custom view for PO item fees
	 *
	 * @since 0.9.24
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 */
	public function po_item_fee_view( $view, $args ) {

		if ( ! Helpers::is_po_post() ) {
			return $view;
		}

		return ATUM_PO_PATH . 'views/meta-boxes/po-items/item-fee';

	}

	/**
	 * Load our custom view for PO item shippings
	 *
	 * @since 0.9.24
	 *
	 * @param string $view
	 * @param array  $args
	 *
	 * @return string
	 */
	public function po_item_shipping_view( $view, $args ) {

		if ( ! Helpers::is_po_post() ) {
			return $view;
		}

		return ATUM_PO_PATH . 'views/meta-boxes/po-items/item-shipping';

	}

	/**
	 * Alter the args passed to the PO item fee and PO item shipping views
	 *
	 * @since 0.9.24
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function po_item_fee_and_shipping_view_args( $args ) {

		if ( ! Helpers::is_po_post() ) {
			return $args;
		}

		// Replace the coming PO with its extended version.
		$args['atum_order']        = AtumHelpers::get_atum_order_model( Helpers::get_po_id(), TRUE, PurchaseOrders::POST_TYPE );
		$args['currency']          = $args['atum_order']->supplier_currency;
		$args['currency_template'] = sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol( $args['currency'] ), '%value%' );
		$args['decimal_sep']       = wc_get_price_decimal_separator();
		$args['step']              = AtumStockDecimals::get_input_step();
		$args['field_name_prefix'] = 'atum_order_item_';

		return $args;

	}

	/**
	 * Change the PO titles at backend
	 *
	 * @since 0.9.27
	 *
	 * @param string $admin_title
	 * @param string $title
	 *
	 * @return string
	 */
	public function change_admin_po_titles( $admin_title, $title ) {

		global $typenow, $pagenow, $post;

		if ( $post && PurchaseOrders::POST_TYPE === $typenow && 'post.php' === $pagenow ) {
			/**
			 * Variable definition
			 *
			 * @var POExtended $po
			 */
			$po          = AtumHelpers::get_atum_order_model( $post->ID, FALSE, PurchaseOrders::POST_TYPE );
			$po_title    = $po->number ?: $po->get_id();
			$admin_title = ( ! $po->is_returning() ? __( 'Edit PO', ATUM_PO_TEXT_DOMAIN ) : __( 'Edit Returning PO', ATUM_PO_TEXT_DOMAIN ) ) . " \"#$po_title\"";
		}

		return $admin_title;
		
	}

	/**
	 * Get PO Extended instance
	 *
	 * @since 0.9.27
	 *
	 * @param AtumOrderModel|null $atum_order
	 * @param int                 $atum_order_id
	 *
	 * @return AtumOrderModel
	 */
	public function get_po_extended( $atum_order, $atum_order_id ) {

		if ( PurchaseOrders::POST_TYPE === get_post_type( $atum_order_id ) ) {
			$atum_order = AtumHelpers::get_atum_order_model( $atum_order_id, TRUE, PurchaseOrders::POST_TYPE );
		}

		return $atum_order;

	}

	/**
	 * Check whether to exclude some order items from the WC items import.
	 *
	 * @since 1.2.1
	 *
	 * @param bool                                                                                 $include
	 * @param \WC_Order_Item_Product|\WC_Order_Item_Fee|\WC_Order_Item_Shipping|\WC_Order_Item_Tax $item
	 * @param AtumOrderModel                                                                       $atum_order
	 *
	 * @return bool True to include or false to exclude.
	 */
	public function maybe_exclude_imported_order_items( $include, $item, $atum_order ) {

		if ( ! $atum_order instanceof POExtended || ! $item instanceof \WC_Order_Item_Product ) {
			return $include;
		}

		// If the supplier products restriction is disabled, include it.
		if ( 'no' === AtumHelpers::get_option( 'po_supplier_products_restriction', 'yes' ) ) {
			return TRUE;
		}

		$po_supplier = $atum_order->get_supplier( 'id' );

		// If the PO has no supplier assigned, there is no need to check restrictions.
		if ( ! $po_supplier ) {
			return TRUE;
		}

		// If the no supplier products is enabled, and the current item product has no supplier, include it.
		$product          = AtumHelpers::get_atum_product( $item->get_product_id() );
		$product_supplier = $product->get_supplier_id();

		if ( $po_supplier !== $product_supplier ) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Update the statuses considered as Due in PO PRO when calculating inbound stock.
	 *
	 * @since 1.0.2
	 *
	 * @param string[] $statuses
	 *
	 * @return string[]
	 */
	public function purchase_orders_inbound_due_statuses( $statuses ) {
		return array_diff( array_keys( Globals::get_statuses() ), array_merge( [ PurchaseOrders::FINISHED ], Globals::get_closed_statuses() ) );
	}

	/**
	 * Show the PO numbers on the Inbound Stock ListTable.
	 *
	 * @since 1.0.3
	 *
	 * @param string                                                 $po_link
	 * @param \WP_Post                                               $item
	 * @param \WC_Product|\WC_Product_Variation|AtumProductInterface $list_item
	 *
	 * @return string
	 */
	public function po_numbers_on_inbound_stock_list( $po_link, $item, $list_item ) {

		$po = AtumHelpers::get_atum_order_model( $item->po_id, FALSE, PurchaseOrders::POST_TYPE );

		return '<a href="' . get_edit_post_link( $item->po_id ) . '" target="_blank">#' . ( $po->number ?: $item->po_id ) . '</a>';

	}

	/**
	 * Store in cache the already delivered items for currently shown products in the Inbound ListTable
	 *
	 * @since 1.0.3
	 *
	 * @param array $po_products
	 */
	public function calculate_inbound_list_delivered_items( $po_products ) {

		global $wpdb;

		if ( empty( $po_products ) ) {
			return;
		}

		$stock_decimals = AtumStockDecimals::get_stock_decimals();
		$po_item_ids    = implode( ',', array_column( $po_products, 'order_item_id' ) );

		// We don't need to filter by order status because only due orders are included in $po_products.
		// phpcs:disable WordPress.DB.PreparedSQL
		$sql = "
			SELECT doim_po.`meta_value` order_item_id, SUM( CAST( doim_q.`meta_value` AS DECIMAL(10, $stock_decimals) ) ) delivered
			FROM `{$wpdb->atum_order_itemmeta}` doim_po
				INNER JOIN `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` doi ON doim_po.`order_item_id` = doi.`order_item_id` AND doi.`order_item_type` = 'delivery_item'
				INNER JOIN `{$wpdb->atum_order_itemmeta}` doim_q ON doi.`order_item_id` = doim_q.`order_item_id` AND doim_q.`meta_key` = '_qty'
				INNER JOIN `{$wpdb->posts}` dor ON doi.`order_id` = dor.`ID` AND dor.`post_type` = 'atum_po_delivery'
			WHERE doim_po.`meta_key` = '_po_item_id'  AND doim_po.`meta_value` IN ($po_item_ids)
			GROUP BY order_item_id
		";

		$results = $wpdb->get_results( $sql );
		// phpcs:enable

		if ( $results ) {

			foreach ( $results as $result ) {
				$this->delivered_inbound[ $result->order_item_id ] = $result->delivered;
			}

		}

	}

	/**
	 * Adjust the inbound qty if any product has been delivered.
	 *
	 * @since 1.0.3
	 *
	 * @param float|int   $qty
	 * @param \WP_Post    $item
	 * @param \WC_Product $list_item
	 *
	 * @return float|int
	 */
	public function adjust_item_inbound_stock( $qty, $item, $list_item ) {

		$po_item_id = $item->po_item_id;

		if ( ! empty( $this->delivered_inbound[ $po_item_id ] ) ) {
			$qty -= $this->delivered_inbound[ $po_item_id ];
		}

		return $qty;
	}

	/**
	 * Remove delivered stock to the totals
	 *
	 * @since 1.0.3
	 *
	 * @param array $totalizers
	 *
	 * @return array
	 */
	public function adjust_inbound_stock_total( $totalizers ) {

		if ( isset( $totalizers['calc_inbound_stock'] ) ) {

			$totalizers['calc_inbound_stock'] -= array_sum( $this->delivered_inbound );
		}

		return $totalizers;
	}

	/**
	 * Add delivery stuff to the product's inbound stock query select.
	 *
	 * @since 1.0.3
	 *
	 * @param string      $select
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function product_inbound_stock_select( $select, $product ) {

		return 'SUM(oim2.`meta_value` - IFNULL(doi_pro.`d_qty`,0)) AS quantity';
	}

	/**
	 * Add delivery stuff to the product's inbound stock query joins.
	 *
	 * @since 1.0.3
	 *
	 * @param string[]    $joins
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function product_inbound_stock_joins( $joins, $product ) {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return array_merge( $joins, [
			$wpdb->prepare( "LEFT JOIN 
				(SELECT doim_p.`meta_value` po_item_id, SUM( doim_q.`meta_value`) d_qty 
					FROM `$wpdb->atum_order_itemmeta` doim_p
					INNER JOIN `$wpdb->prefix" . AtumOrderPostType::ORDER_ITEMS_TABLE . "` AS doi ON doi.`order_item_id` = doim_p.`order_item_id` AND doi.`order_item_type` = 'delivery_item'
					INNER JOIN `$wpdb->atum_order_itemmeta` AS doim_pro ON doim_p.`order_item_id` = doim_pro.`order_item_id` AND doim_pro.`meta_key` IN('_product_id', '_variation_id') AND doim_pro.`meta_value` = %d
					INNER JOIN `$wpdb->atum_order_itemmeta` AS doim_q ON doi.`order_item_id` = doim_q.`order_item_id` AND  doim_q.`meta_key` = '_qty'
					 WHERE doim_p.`meta_key` = '_po_item_id'
					    GROUP BY doim_p.`meta_value` ) doi_pro
			ON oi.`order_item_id` = doi_pro.`po_item_id`", $product->get_id() ),
		] );
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

	}

	/**
	 * Calculate gross profit within PO Pro settings.
	 *
	 * @since 1.0.4
	 *
	 * @param string      $gross_profit
	 * @param \WP_Post    $item
	 * @param \WC_Product $product
	 *
	 * @return string
	 */
	public function calculate_gross_profit( $gross_profit, $item, $product ) {

		$product        = AtumHelpers::get_atum_product( $product );
		$purchase_price = (float) $product->get_purchase_price();
		$price          = (float) $product->get_price();
		$supplier       = new Supplier( $product->get_supplier_id() );

		return Helpers::calculate_supplier_gross_profit( $gross_profit, $product, $price, $purchase_price, $supplier );

	}

	/**
	 * Load the PO PRO help guides
	 *
	 * @since 1.1.3
	 *
	 * @param string[] $guides_paths
	 *
	 * @return string[]
	 */
	public function load_help_guides( $guides_paths ) {

		$guides_paths = array_merge( $guides_paths, array(
			'atum_po_deliveries'   => ATUM_PO_PATH . 'help-guides/deliveries-meta-box',
			'atum_po_first_access' => ATUM_PO_PATH . 'help-guides/first-po-access',
			'atum_po_invoices'     => ATUM_PO_PATH . 'help-guides/invoices-meta-box',
			'atum_po_merge_modal'  => ATUM_PO_PATH . 'help-guides/merge-modal',
			'atum_po_items'        => ATUM_PO_PATH . 'help-guides/po-items-meta-box',
			'atum_po_list_table'   => ATUM_PO_PATH . 'help-guides/pos-list-table',
			'atum_po_email_modal'  => ATUM_PO_PATH . 'help-guides/send-email-popup',
		) );

		return $guides_paths;

	}

	public function check_executed_bulk_action( $executed, $bulk_action, $ids ) {

		switch ( $bulk_action ) {
			case 'markPODraft':
			case 'markPONew':
			case 'markPOApproval':
			case 'markPOApproved':
			case 'markPOReceived':
			case 'markPOSent':
			case 'markPOOnthewayin':
			case 'markPOReceiving':
			case 'markPOPartiallyReceiving':
			case 'markPOQualityCheck':
			case 'markPOAdded':
			case 'markPOPartiallyAdded':
			case 'markPOCompleted':
			case 'markPOCancelled':
			case 'markPOReturned':
			case 'markPOArchived':
				$executed = TRUE;
				break;
		}

		return $executed;
	}

	/**
	 * Check if the supplier products restriction should be bypassed
	 *
	 * @since 1.2.4
	 *
	 * @param bool $bypass
	 * @param POExtended $po
	 *
	 * @return bool
	 */
	public function maybe_bypass_supplier_check( $bypass, $po ) {

		if ( $po instanceof POExtended && 'no' === AtumHelpers::get_option( 'po_supplier_products_restriction', 'yes' ) ) {
			$bypass = TRUE;
		}

		return $bypass;
	}

	/*******************
	 * Instance methods
	 *******************/

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
	 * @return Hooks instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
