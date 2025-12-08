<?php
/**
 * Purchase Orders PRO + Atum Action Logs integration
 *
 * @since       1.1.9
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumLogs
 * @subpackage  Integrations
 */

namespace AtumLogs\Integrations;

defined( 'ABSPATH' ) || die;

use Atum\Addons\Addons;
use Atum\Components\AtumCache;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\PurchaseOrders\PurchaseOrders as AtumPurchaseOrders;
use AtumLogs\Inc\Helpers;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Deliveries\Models\DeliveryItem;
use AtumPO\Inc\Globals as POGlobals;
use AtumLogs\Models\LogEntry;
use AtumLogs\Models\LogModel;
use AtumPO\Invoices\Models\Invoice;
use AtumPO\Models\POExtended;


class PurchaseOrders {

	/**
	 * The singleton instance holder
	 *
	 * @var PurchaseOrders
	 */
	private static $instance;

	/**
	 * PurchaseOrders singleton constructor
	 *
	 * @since 1.1.9
	 */
	private function __construct() {

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}

	}

	/**
	 * Register the hooks for the admin side
	 *
	 * @since 1.1.9
	 */
	public function register_admin_hooks() {

		// Apply text/params for internal PO logs.
		add_filter( 'atum/logs/get_entry_text', array( $this, 'get_entry_text' ), 10, 3 );
		add_filter( 'atum/logs/get_entry_params', array( $this, 'get_entry_params' ), 10, 3 );

		// Use custom params for PO Logs.
		add_filter( 'atum/logs/parse_params_custom_format', array( $this, 'get_custom_params' ), 10, 2 );

		// Log extra fields on edit PO.
		add_filter( 'atum/logs/purchase_orders_fields', array( $this, 'purchase_orders_fields' ) );

		// Get requisitioner user data.
		add_filter( 'atum/logs/parse_field_value', array( $this, 'parse_field_value' ), 10, 2 );

		// Get field name from PO metaboxes.
		add_filter( 'atum/logs/purchase_orders_field_name', array( $this, 'purchase_orders_field_name' ) );

		// Get field default value from PO metaboxes.
		add_filter( 'atum/logs/purchase_orders_field_default_value', array( $this, 'purchase_orders_field_default_value' ), 10, 2 );

		// Get orders logs for display.
		add_action( 'atum/purchase_orders_pro/after_po_meta_boxes', array( $this, 'add_logs_meta_box' ) );

		// Log PO email sent.
		add_action( 'atum/purchase_orders_pro/email_sent', array( $this, 'log_email_sent' ), 10, 5 );

		// Log PO Delivery created.
		add_action( 'atum/purchase_orders_pro/after_delivery_added', array( $this, 'log_added_delivery' ) );

		// Log PO Delivery removed.
		add_action( 'atum/purchase_orders_pro/before_delivery_remove', array( $this, 'log_delivery_remove' ) );

		// Log PO edit Delivery items.
		add_action( 'atum/purchase_orders_pro/ajax/before_edit_delivery_item', array( $this, 'log_edit_delivery_item' ), 1, 3 );

		// Log PO add delivery items to stock.
		add_action( 'atum/purchase_orders_pro/ajax/before_add_delivery_item_to_stock', array( $this, 'register_add_delivery_item_to_stock' ), 10, 2 );
		add_action( 'atum/purchase_orders_pro/delivery/after_stock_change', array( $this, 'log_add_delivery_item_to_stock' ), 10, 4 );

		// Lod PO added delivery file.
		add_action( 'atum/purchase_orders_pro/ajax/delivery_file_added', array( $this, 'log_po_delivery_file_added' ), 10, 2 );

		// Lod PO removed delivery file.
		add_action( 'atum/purchase_orders_pro/ajax/delivery_file_removed', array( $this, 'log_po_delivery_file_removed' ), 10, 2 );

		// Log PO remove delivery item.
		add_filter( 'atum/purchase_orders_pro/ajax/before_remove_delivery_item', array( $this, 'log_remove_delivery_item' ), 10, 2 );

		// Lod PO added file.
		add_action( 'atum/purchase_orders_pro/ajax/file_added', array( $this, 'log_po_file_added' ), 10, 2 );

		// Lod PO removed file.
		add_action( 'atum/purchase_orders_pro/ajax/file_removed', array( $this, 'log_po_file_removed' ), 10, 2 );

		// Log PO Invoice created.
		add_action( 'atum/purchase_orders_pro/after_invoice_added', array( $this, 'log_added_invoice' ) );

		// Log PO Invoice removed.
		add_action( 'atum/purchase_orders_pro/before_invoice_remove', array( $this, 'log_invoice_remove' ) );

		// Log PO edit Invoice items.
		add_action( 'atum/purchase_orders_pro/ajax/before_update_invoice_item', array( $this, 'log_edit_invoice_item' ), 10, 3 );

		// Log PO remove Invoice items.
		add_action( 'atum/purchase_orders_pro/before_invoice_item_remove', array( $this, 'log_remove_invoice_item' ), 10, 2 );

		// Log merge PO.
		add_action( 'atum/purchase_orders_pro/after_merge_po', array( $this, 'log_merge_po' ), 10, 3 );

		// Log clone PO.
		add_action( 'atum/purchase_orders_pro/po_duplicate_after_save', array( $this, 'log_clone_po' ), 10, 2 );

		// Log PO approval by requisitioner.
		add_action( 'atum/orders/status_changed', array( $this, 'log_approval_po' ), 1, 4 );
	}

	/**
	 * Check current page is a PO.
	 *
	 * @since 1.1.9
	 *
	 * @return bool
	 */
	private function check_current_page() {

		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( ! is_null( $screen ) ) {
				$screen_id = $screen->id;
			}
		}
		if ( ! isset( $screen_id ) ) {
			$screen_id = isset( $_REQUEST['screen'] ) ? $_REQUEST['screen'] : '';
		}

		return AtumPurchaseOrders::POST_TYPE === $screen_id;

	}

	/**
	 * Show different text entry for PO.
	 *
	 * @since 1.1.9
	 *
	 * @param string $text
	 * @param string $entry
	 * @param bool   $save
	 *
	 * @return string
	 */
	public function get_entry_text( $text, $entry, $save = FALSE ) {

		if ( $this->check_current_page() && ! $save ) {

			switch ( $entry ) {
				case LogEntry::ACTION_PO_CREATE:
					$text = __( 'PO created', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_EDIT_STATUS:
					/* Translators: %s: status */
					$text = __( 'PO status changed to %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_EDIT_TOTALS:
					$text = __( 'Changed the Totals', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_EDIT_DATA:
					/* Translators: %1$s: field name, %2$s: value */
					$text = __( '%1$s changed to %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ADD_ITEM:
					/* Translators: %s: product name */
					$text = __( 'Added the product %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ADD_FEE_SHIP:
					/* Translators: %s fee/shipping cost */
					$text = __( 'Added a new %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ADD_TAX:
					$text = __( 'Added a new tax', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ITEM_CHANGED:
					/* Translators: %1$s: field name, %2$s: order_item */
					$text = __( 'Changed the %1$s for the order item %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ITEM_META:
					/* Translators: %s: order_item */
					$text = __( 'Added meta to the order item %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DEL_ITEM_META:
					/* Translators: %s: order_item */
					$text = __( 'Removed meta from the order item %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DEL_ORDER_ITEM:
					/* Translators: %s: order item id */
					$text = __( 'Deleted the order item %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ADD_META:
					$text = __( 'Added meta data to the PO', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_PURCHASE_PRICE:
					/* Translators: %s: Product link */
					$text = __( 'Set the purchase price for the product %s from this PO', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_ADD_NOTE:
					$text = __( 'Added a note', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DEL_NOTE:
					$text = __( 'Removed a note', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_GENERATE_PDF:
					$text = __( 'PDF generated', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_STOCK_LEVELS:
					/* Translators: %s: Product link */
					$text = __( 'The stock levels of product %s changed after changing the PO status', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_TRASH:
					$text = __( 'Moved to trash', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_UNTRASH:
					$text = __( 'Restored from trash', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_EMAILED:
					$text = __( 'PO has been mailed', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_ADD:
					/* Translators: %s: Delivery name */
					$text = __( 'A new delivery %s has been added', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_DEL:
					/* Translators: %s: Delivery name */
					$text = __( 'The delivery %s was removed', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_EDIT:
					/* Translators: %1$s: Product name, %2$s: Delivery name */
					$text = __( 'Edited %1$s at the Delivery %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_INV_EDIT:
					/* Translators: %1$s: Inventory name, %2$s: Delivery name */
					$text = __( 'Edited %1$s at the Delivery %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_STOCK:
					/* Translators: %1$s: Product link, %2$s; Delivery name */
					$text = __( 'Increased the stock for the product %1$s from the Delivery %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_ITEM_DEL:
					/* Translators: %1$s: Item name, %2$s; Delivery name */
					$text = __( 'The item %1$s in the Delivery %2$s was removed', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_FILE_ADDED:
					/* Translators: %s: file name */
					$text = __( 'Added the file %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_FILE_DEL:
					/* Translators: %s: file name */
					$text = __( 'Removed the file %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_INVOICE_ADD:
					/* Translators: %s: Invoice name */
					$text = __( 'A new invoice %s has been added', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_INVOICE_DEL:
					/* Translators: %s: Invoice name */
					$text = __( 'The invoice %s has been removed', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_INVOICE_EDIT:
				case LogEntry::ACTION_PO_INVOICE_TAX_EDIT:
				case LogEntry::ACTION_PO_INVOICE_FEE_EDIT:
				case LogEntry::ACTION_PO_INVOICE_SHIP_EDIT:
					/* Translators: %s: Invoice name */
					$text = __( 'Edited %1$s at the Invoice %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_INVOICE_ITEM_DEL:
					/* Translators: %s: Invoice name */
					$text = __( 'Removed %1$s from the Invoice %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_FILE_ADD:
					/* Translators: %1$s: File name, %2$s: Delivery name */
					$text = __( 'Added the file %1$s to the Delivery %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_DELIVERY_FILE_DEL:
					/* Translators: %1$s: File name, %2$s: Delivery name */
					$text = __( 'Removed the file %1$s from the Delivery %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_MERGE:
					/* Translators: %s: PO link */
					$text = __( 'Merged with PO %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_CLONE:
					/* Translators: %s: PO link */
					$text = __( 'PO cloned from the PO %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_PO_APPROVAL:
					/* Translators: %s: requisitioner name */
					$text = __( 'PO approved by %s', ATUM_LOGS_TEXT_DOMAIN );
					break;
				case LogEntry::ACTION_MI_PO_ITEM_INV_ADD:
					/* Translators: %1$s: inventory id, %2$s: order item id */
					$text = __( 'Added the inventory %1$s for the item %2$s', ATUM_LOGS_TEXT_DOMAIN );
					break;
			}
		}

		return $text;

	}

	/**
	 * Use different params for PO entry.
	 *
	 * @since 1.1.9
	 *
	 * @param array  $params
	 * @param string $entry
	 * @param bool   $save
	 *
	 * @return array
	 */
	public function get_entry_params( $params, $entry, $save = FALSE ) {

		if ( $this->check_current_page() && ! $save ) {

			switch ( $entry ) {
				case LogEntry::ACTION_PO_CREATE:
				case LogEntry::ACTION_PO_EDIT_TOTALS:
				case LogEntry::ACTION_PO_ADD_TAX:
				case LogEntry::ACTION_PO_ADD_META:
				case LogEntry::ACTION_PO_ADD_NOTE:
				case LogEntry::ACTION_PO_DEL_NOTE:
				case LogEntry::ACTION_PO_GENERATE_PDF:
				case LogEntry::ACTION_PO_TRASH:
				case LogEntry::ACTION_PO_UNTRASH:
				case LogEntry::ACTION_PO_EMAILED:
					$params = [];
					break;
				case LogEntry::ACTION_PO_EDIT_STATUS:
					$params = [
						'new_value' => 'status',
					];
					break;
				case LogEntry::ACTION_PO_EDIT_DATA:
					$params = [
						'field'     => 'uc_rep_only',
						'new_value' => 'inside_name',
					];
					break;
				case LogEntry::ACTION_PO_ADD_FEE_SHIP:
					$params = [
						'field' => 'uc_rep',
					];
					break;
				case LogEntry::ACTION_PO_ADD_ITEM:
					$params = [
						'product' => 'inside_name',
					];
					break;
				case LogEntry::ACTION_PO_ITEM_CHANGED:
					$params = [
						'field'     => 'uc_rep',
						'item_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_ITEM_META:
				case LogEntry::ACTION_PO_DEL_ITEM_META:
				case LogEntry::ACTION_PO_DEL_ORDER_ITEM:
					$params = [
						'item_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_PURCHASE_PRICE:
				case LogEntry::ACTION_PO_STOCK_LEVELS:
					$params = [
						'product_id'   => 'link',
						'product_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_ADD:
				case LogEntry::ACTION_PO_DELIVERY_DEL:
					$params = [
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_EDIT:
					$params = [
						'product_name'  => 'content',
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_INV_EDIT:
					$params = [
						'inventory_name' => 'content',
						'delivery_name'  => 'content',
					];
					break;
				case LogEntry::ACTION_PO_INVOICE_ADD:
				case LogEntry::ACTION_PO_INVOICE_DEL:
					$params = [
						'invoice_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_INVOICE_EDIT:
				case LogEntry::ACTION_PO_INVOICE_TAX_EDIT:
				case LogEntry::ACTION_PO_INVOICE_FEE_EDIT:
				case LogEntry::ACTION_PO_INVOICE_SHIP_EDIT:
				case LogEntry::ACTION_PO_INVOICE_ITEM_DEL:
					$params = [
						'item_name'    => 'content',
						'invoice_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_ITEM_DEL:
					$params = [
						'item_name'     => 'content',
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_STOCK:
					$params = [
						'product_id'    => 'link',
						'product_name'  => 'content',
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_FILE_ADDED:
					$params = [
						'file' => 'inside_name',
					];
					break;
				case LogEntry::ACTION_PO_FILE_DEL:
					$params = [
						'file' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_FILE_ADD:
					$params = [
						'file'          => 'inside_name',
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_DELIVERY_FILE_DEL:
					$params = [
						'file'          => 'content',
						'delivery_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_MERGE:
				case LogEntry::ACTION_PO_CLONE:
					$params = [
						'source_id'   => 'link',
						'source_name' => 'content',
					];
					break;
				case LogEntry::ACTION_PO_APPROVAL:
					$params = [
						'user_name' => 'inside_name',
					];
					break;
				case LogEntry::ACTION_MI_PO_ITEM_INV_ADD:
					$params = [
						'inventory'  => 'name_content',
						'order_item' => 'name_content',
					];
					break;
			}
		}

		return $params;

	}

	/**
	 * Get custom params for POs.
	 *
	 * @since 1.1.9
	 *
	 * @param string $value
	 * @param string $format
	 *
	 * @return string
	 */
	public function get_custom_params( $value, $format ) {

		if ( $this->check_current_page() ) {

			switch ( $format ) {
				case 'status':
					$statuses = POGlobals::get_statuses();
					$value    = isset( $statuses[ $value ] ) ? $statuses[ $value ] : $value;
					break;
				case 'inside_name':
					if ( is_array( $value ) ) {
						foreach ( $value as $i => $v ) {
							if ( 'name' === $i || strpos( $i, '_name' ) > 0 ) {
								$value = $value[ $i ];
							}
						}
					}
					break;
			}

		}

		return $value;
	}

	/**
	 * Register the PO Logs meta box
	 *
	 * @since 1.2.2
	 */
	public function add_logs_meta_box() {

		global $post, $post_type;

		$is_returning_po = AtumPurchaseOrders::POST_TYPE === $post_type && in_array( $post->post_status, [ 'atum_returning', 'atum_returned' ] );

		add_meta_box(
			'po_logs',
			! $is_returning_po ? __( 'PO Logs', ATUM_LOGS_TEXT_DOMAIN ) : __( 'Returning PO Logs', ATUM_LOGS_TEXT_DOMAIN ),
			array( $this, 'display_logs_meta_box' ),
			AtumPurchaseOrders::POST_TYPE,
			'normal',
			'high'
		);

	}

	/**
	 * Display PO logs meta box
	 *
	 * @since 1.1.9
	 *
	 * @param \WP_Post $post
	 */
	public function display_logs_meta_box( $post ) {
		$order_logs = LogModel::get_order_logs( $post->ID );
		AtumHelpers::load_view( ATUM_LOGS_PATH . 'views/log-orders', compact( 'order_logs' ) );
	}

	/**
	 * Add extra fields to log.
	 *
	 * @since 1.2.0
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function purchase_orders_fields( $fields ) {
		return Helpers::get_purchase_order_pro_metas();
	}

	/**
	 * Parse field value to log.
	 *
	 * @since 1.2.0
	 *
	 * @param mixed  $value
	 * @param string $field
	 *
	 * @return mixed
	 */
	public function parse_field_value( $value, $field ) {

		if ( 'requisitioner' === $field ) {

			$user = get_user_by( 'id', $value );

			return [
				'id'   => $user ? $user->ID : FALSE,
				'name' => $user ? $user->display_name : FALSE,
			];
		}

		return $value;
	}

	/**
	 * Add extra fields to log.
	 *
	 * @since 1.2.0
	 *
	 * @param string $field
	 *
	 * @return string
	 */
	public function purchase_orders_field_name( $field ) {

		if ( 'currency' === $field ) {
			return 'supplier_currency';
		}

		return $field;
	}

	/**
	 * Get default value for a field.
	 *
	 * @since 1.2.5
	 *
	 * @param mixed  $value
	 * @param string $field
	 *
	 * @return mixed
	 */
	public function purchase_orders_field_default_value( $value, $field ) {

		switch ( $field ) {
			case 'pdf_template':
				$value = AtumHelpers::get_option( 'po_default_pdf_template', 'default' );
				break;
			case 'email_template':
				$value = AtumHelpers::get_option( 'po_default_emails_template', 'default' );
				break;
		}

		return $value;
	}

	/**
	 * Log when a PO is sent by email.
	 *
	 * @since 1.1.9
	 *
	 * @param POExtended $po
	 * @param string     $to
	 * @param string     $subject
	 * @param array      $attachments
	 * @param array      $extra_data
	 *
	 * @throws \Exception
	 */
	public function log_email_sent( $po, $to, $subject, $attachments, $extra_data ) {

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'    => $po->get_id(),
				'order_name'  => '#' . $po->get_id(),
				'to'          => $to,
				'subject'     => $subject,
				'attachments' => $attachments,
			],
			'entry'  => LogEntry::ACTION_PO_EMAILED,
		];

		foreach ( [ 'cc', 'bcc', 'body' ] as $param ) {
			if ( isset( $extra_data[ $param ] ) ) {
				$log_data['data'][ $param ] = $extra_data[ $param ];
			}
		}

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Log delivery created in PO.
	 *
	 * @since 1.2.0
	 *
	 * @param Delivery $delivery
	 *
	 * @throws \Exception
	 */
	public function log_added_delivery( $delivery ) {

		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'      => $po->get_id(),
				'order_name'    => '#' . $po->get_id(),
				'delivery_id'   => $delivery->get_id(),
				'delivery_name' => $delivery->get_title(),
			],
			'entry'  => LogEntry::ACTION_PO_DELIVERY_ADD,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log delivery removed from PO.
	 *
	 * @since 1.2.0
	 *
	 * @param Delivery $delivery
	 *
	 * @throws \Exception
	 */
	public function log_delivery_remove( $delivery ) {

		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'      => $po->get_id(),
				'order_name'    => '#' . $po->get_id(),
				'delivery_id'   => $delivery->get_id(),
				'delivery_name' => $delivery->get_title(),
			],
			'entry'  => LogEntry::ACTION_PO_DELIVERY_DEL,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log editing delivery item.
	 *
	 * @since 1.2.0
	 *
	 * @param array         $item_data
	 * @param PurchaseOrder $po
	 * @param Delivery      $delivery
	 *
	 * @throws \Exception
	 */
	public function log_edit_delivery_item( $item_data, $po, $delivery ) {

		$po_item_id = absint( $item_data['id'] );
		$found_item = FALSE;

		foreach ( $delivery->get_items( [ 'delivery_item', 'delivery_item_inventory' ] ) as $delivery_item ) {
			/**
			 * Variable definition
			 *
			 * @var $delivery_item DeliveryItemProduct|DeliveryItemProductInventory
			 */
			if ( $delivery_item instanceof DeliveryItemProduct && 'product' === $item_data['type'] && $delivery_item->get_po_item_id() === $po_item_id ) {
				$found_item = TRUE;
				break;
			}
			if ( 'inventory' === $item_data['type'] && isset( $item_data['inventoryId'] ) &&
				$delivery_item instanceof DeliveryItemProductInventory && $delivery_item->get_inventory_id() === absint( $item_data['inventoryId'] )
			) {
				$found_item = TRUE;
				break;
			}
		}

		if ( $found_item && floatval( $item_data['delivered'] ) !== floatval( $delivery_item->get_quantity() ) ) {
			$log_data = [
				'source' => LogModel::SRC_PO,
				'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
				'data'   => [
					'order_id'      => $po->get_id(),
					'order_name'    => '#' . $po->get_id(),
					'delivery_id'   => $delivery->get_id(),
					'delivery_name' => $delivery->get_title(),
					'old_quantity'  => $delivery_item->get_quantity(),
					'new_quantity'  => floatval( $item_data['delivered'] ),
				],
				'entry'  => 'inventory' === $item_data['type'] ? LogEntry::ACTION_PO_DELIVERY_INV_EDIT : LogEntry::ACTION_PO_DELIVERY_EDIT,
			];

			if ( $delivery_item instanceof DeliveryItemProduct ) {
				$log_data['data']['product_id']   = $delivery_item->get_product_id();
				$log_data['data']['product_name'] = $delivery_item->get_product()->get_formatted_name();
			}
			if ( 'inventory' === $item_data['type'] && isset( $item_data['inventoryId'] ) ) {
				$log_data = apply_filters( 'atum/action_logs/add_delivery_item_log_data', $log_data, $item_data );
			}

			$delivery_item = NULL;

			LogModel::maybe_save_log( $log_data );
		}
	}

	/**
	 * Log adding delivery items to stock.
	 *
	 * @since 1.2.0
	 *
	 * @param array    $item_data
	 * @param Delivery $delivery
	 *
	 * @throws \Exception
	 */
	public function register_add_delivery_item_to_stock( $item_data, $delivery ) {

		$data = array();

		if ( 'inventory' === $item_data['type'] ) {
			/**
			 * Variable definition
			 *
			 * @var DeliveryItemProductInventory $delivery_item
			 */
			$delivery_item = $delivery->get_item( $item_data['id'], 'delivery_item_inventory' );
			if ( Addons::is_addon_active( 'multi_inventory' ) ) {
				$inventory = \AtumMultiInventory\Inc\Helpers::get_inventory( $delivery_item->get_inventory_id() );
				if ( ! $inventory ) {
					return;
				}
				$data      = array(
					'quantity' => $inventory->stock_quantity,
				);
			}
		}
		else {
			/**
			 * Variable definition
			 *
			 * @var DeliveryItemProduct $delivery_item
			 */
			$delivery_item = $delivery->get_item( $item_data['id'] );
			$product       = AtumHelpers::get_atum_product( $delivery_item->get_product_id() );
			if ( ! $product ) {
				return;
			}
			$data          = array(
				'quantity' => $product->get_stock_quantity(),
			);
		}

		$transient_key = AtumCache::get_transient_key( 'log_delivery_item_stock_' . $item_data['id'] );
		AtumCache::set_transient( $transient_key, $data, MINUTE_IN_SECONDS, TRUE );
	}

	/**
	 * Log adding delivery items to stock.
	 *
	 * @since 1.2.0
	 *
	 * @param DeliveryItemProduct|DeliveryItemProductInventory $delivery_item
	 * @param float                                            $quantity
	 * @param string                                           $action
	 * @param Delivery                                         $delivery
	 *
	 * @throws \Exception
	 */
	public function log_add_delivery_item_to_stock( $delivery_item, $quantity, $action, $delivery ) {

		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$transient_key = AtumCache::get_transient_key( 'log_delivery_item_stock_' . $delivery_item->get_id() );
		$old_data      = AtumCache::get_transient( $transient_key, TRUE );

		if ( ! empty( $old_data ) ) {
			$old_stock = $old_data['quantity'];
		}

		$data = [
			'order_id'      => $po->get_id(),
			'order_name'    => '#' . $po->get_id(),
			'delivery_id'   => $delivery->get_id(),
			'delivery_name' => $delivery->get_title(),
			'quantity'      => $delivery_item->get_quantity(),
		];

		if ( 'delivery_item' === $delivery_item->get_type() ) {
			$product = $delivery_item->get_product();

			if ( ! $product instanceof \WC_Product ) {
				return;
			}

			$data['product_id']   = $delivery_item->get_product_id();
			$data['product_name'] = $product->get_formatted_name();
			$data['old_stock']    = $old_stock ?? NULL;
			$data['new_stock']    = $product->get_stock_quantity();
		}

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => $data,
			'entry'  => LogEntry::ACTION_PO_DELIVERY_STOCK,
		];

		if ( 'delivery_item_inventory' === $delivery_item->get_type() ) {
			$log_data = apply_filters( 'atum/action_logs/add_delivery_item_log_data', $log_data, $delivery_item );

			if ( empty( $log_data ) ) {
				return;
			}
		}

		LogModel::maybe_save_log( $log_data );

	}

	/**
	 * Log removing delivery item.
	 *
	 * @since 1.2.0
	 *
	 * @param Delivery $delivery
	 * @param integer  $delivery_item_id
	 *
	 * @throws \Exception
	 * @return Delivery
	 */
	public function log_remove_delivery_item( $delivery, $delivery_item_id ) {

		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$delivery_item = $delivery->get_item( $delivery_item_id );

		if ( $delivery_item instanceof DeliveryItem ) {
			$log_data = [
				'source' => LogModel::SRC_PO,
				'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
				'data'   => [
					'order_id'      => $po->get_id(),
					'order_name'    => '#' . $po->get_id(),
					'delivery_id'   => $delivery->get_id(),
					'delivery_name' => $delivery->get_title(),
					'item_name'     => $delivery_item->get_name(),
				],
				'entry'  => LogEntry::ACTION_PO_DELIVERY_ITEM_DEL,
			];

			LogModel::maybe_save_log( $log_data );

		}

		return $delivery;
	}

	/**
	 * Log for added file to PO.
	 *
	 * @since 1.2.0
	 *
	 * @param PurchaseOrder $po
	 * @param array         $file
	 *
	 * @throws \Exception
	 */
	public function log_po_file_added( $po, $file ) {
		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'   => $po->get_id(),
				'order_name' => '#' . $po->get_id(),
				'file'       => $file,
			],
			'entry'  => LogEntry::ACTION_PO_FILE_ADDED,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log for remove file from PO.
	 *
	 * @since 1.2.1
	 *
	 * @param integer       $file
	 * @param PurchaseOrder $po
	 *
	 * @throws \Exception
	 */
	public function log_po_file_removed( $file, $po ) {
		$file_name = basename( get_attached_file( $file ) );
		$log_data  = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'   => $po->get_id(),
				'order_name' => '#' . $po->get_id(),
				'file'       => $file_name,
			],
			'entry'  => LogEntry::ACTION_PO_FILE_DEL,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log invoice created in PO.
	 *
	 * @since 1.2.0
	 *
	 * @param Invoice $invoice
	 *
	 * @throws \Exception
	 */
	public function log_added_invoice( Invoice $invoice ) {

		$po = AtumHelpers::get_atum_order_model( $invoice->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'     => $po->get_id(),
				'order_name'   => '#' . $po->get_id(),
				'invoice_id'   => $invoice->get_id(),
				'invoice_name' => $invoice->document_number,
			],
			'entry'  => LogEntry::ACTION_PO_INVOICE_ADD,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log invoice removed from PO.
	 *
	 * @since 1.2.0
	 *
	 * @param Invoice $invoice
	 *
	 * @throws \Exception
	 */
	public function log_invoice_remove( $invoice ) {

		$po = AtumHelpers::get_atum_order_model( $invoice->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'     => $po->get_id(),
				'order_name'   => '#' . $po->get_id(),
				'invoice_id'   => $invoice->get_id(),
				'invoice_name' => $invoice->document_number,
			],
			'entry'  => LogEntry::ACTION_PO_INVOICE_DEL,
		];
		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log editing invoice item.
	 *
	 * @since 1.2.0
	 *
	 * @param array         $item_data
	 * @param PurchaseOrder $po
	 * @param Invoice       $invoice
	 *
	 * @throws \Exception
	 */
	public function log_edit_invoice_item( $item_data, $po, $invoice ) {

		$item = absint( $item_data['id'] ) ? $invoice->get_item( $item_data['id'] ) : $po->get_item( $item_data['poItem'] );

		if ( empty( $item ) ) {
			return;
		}

		switch ( $item->get_type() ) {
			case 'invoice_tax':
			case 'tax':
				$entry = LogEntry::ACTION_PO_INVOICE_TAX_EDIT;
				break;
			case 'invoice_fee':
			case 'fee':
				$entry = LogEntry::ACTION_PO_INVOICE_FEE_EDIT;
				break;
			case 'invoice_shipping':
			case 'shipping':
				$entry = LogEntry::ACTION_PO_INVOICE_SHIP_EDIT;
				break;
			case 'invoice_item':
			case 'line_item':
			default:
				$entry = LogEntry::ACTION_PO_INVOICE_EDIT;
				break;
		}

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'     => $po->get_id(),
				'order_name'   => '#' . $po->get_id(),
				'invoice_id'   => $invoice->get_id(),
				'invoice_name' => $invoice->document_number,
				'quantity'     => $item_data['qty'],
			],
			'entry'  => $entry,
		];

		if ( in_array( $item->get_type(), [ 'line_item', 'invoice_item' ] ) ) {
			$log_data['data']['product_id']   = $item->get_product_id();
			$log_data['data']['product_name'] = $item->get_product() instanceof \WC_Product ? $item->get_product()->get_formatted_name() : $item->get_name();
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log editing invoice item.
	 *
	 * @since 1.2.1
	 *
	 * @param integer $invoice_item_id
	 * @param Invoice $invoice
	 *
	 * @throws \Exception
	 */
	public function log_remove_invoice_item( $invoice_item_id, $invoice ) {

		$invoice_item = $invoice->get_atum_order_item( $invoice_item_id );
		$po           = AtumHelpers::get_atum_order_model( $invoice->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'     => $po->get_id(),
				'order_name'   => '#' . $po->get_id(),
				'invoice_id'   => $invoice->get_id(),
				'invoice_name' => $invoice->document_number,
				'item_name'    => $invoice_item->get_name(),
			],
			'entry'  => LogEntry::ACTION_PO_INVOICE_ITEM_DEL,
		];

		if ( in_array( $invoice_item->get_type(), [ 'line_item', 'invoice_item' ] ) ) {
			$log_data['data']['product_id']   = $invoice_item->get_product_id();
			$log_data['data']['product_name'] = $invoice_item->get_product()->get_formatted_name();
		}

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log add file to delivery
	 *
	 * @since 1.2.1
	 *
	 * @param Delivery $delivery
	 * @param array    $files
	 *
	 * @throws \Exception
	 */
	public function log_po_delivery_file_added( $delivery, $files ) {

		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );

		foreach ( $files as $file ) {
			$log_data = [
				'source' => LogModel::SRC_PO,
				'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
				'data'   => [
					'file'          => $file,
					'order_id'      => $po->get_id(),
					'order_name'    => '#' . $po->get_id(),
					'delivery_id'   => $delivery->get_id(),
					'delivery_name' => $delivery->document_number,
				],
				'entry'  => LogEntry::ACTION_PO_DELIVERY_FILE_ADD,
			];

			LogModel::maybe_save_log( $log_data );
		}

	}

	/**
	 * Log remove file from delivery
	 *
	 * @since 1.2.1
	 *
	 * @param integer  $file
	 * @param Delivery $delivery
	 *
	 * @throws \Exception
	 */
	public function log_po_delivery_file_removed( $file, $delivery ) {

		$po        = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, AtumPurchaseOrders::POST_TYPE );
		$file_name = basename( get_attached_file( $file ) );

		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'file'          => $file_name,
				'order_id'      => $po->get_id(),
				'order_name'    => '#' . $po->get_id(),
				'delivery_id'   => $delivery->get_id(),
				'delivery_name' => $delivery->document_number,
			],
			'entry'  => LogEntry::ACTION_PO_DELIVERY_FILE_DEL,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log merge POs.
	 *
	 * @since 1.2.2
	 *
	 * @param POExtended $po
	 * @param POExtended $merge_po
	 * @param array      $settings
	 *
	 * @throws \Exception
	 */
	public function log_merge_po( $po, $merge_po, $settings ) {
		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'    => $po->get_id(),
				'order_name'  => '#' . $po->get_id(),
				'source_id'   => $merge_po->get_id(),
				'source_name' => '#' . $merge_po->get_id(),
				'settings'    => $settings,
			],
			'entry'  => LogEntry::ACTION_PO_MERGE,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log clone PO from another.
	 *
	 * @since 1.2.3
	 *
	 * @param POExtended $po
	 * @param POExtended $src_po
	 *
	 * @throws \Exception
	 */
	public function log_clone_po( $po, $src_po ) {
		$log_data = [
			'source' => LogModel::SRC_PO,
			'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
			'data'   => [
				'order_id'    => $po->get_id(),
				'order_name'  => '#' . $po->get_id(),
				'source_id'   => $src_po->get_id(),
				'source_name' => '#' . $src_po->get_id(),
			],
			'entry'  => LogEntry::ACTION_PO_CLONE,
		];

		LogModel::maybe_save_log( $log_data );
	}

	/**
	 * Log approval PO by requisitioner.
	 *
	 * @since 1.2.5
	 *
	 * @param int        $order_id
	 * @param string     $old_status
	 * @param string     $new_status
	 * @param POExtended $order
	 *
	 * @throws \Exception
	 */
	public function log_approval_po( $order_id, $old_status, $new_status, $order ) {

		if ( 'yes' === AtumHelpers::get_option( 'po_required_requisition', 'no' ) ) {

			$is_requisitioner = $order->requisitioner && get_current_user_id() === $order->requisitioner;

			if ( $is_requisitioner && 'atum_approval' === $old_status && 'atum_approved' === $new_status ) {

				$user = get_user_by( 'id', $order->requisitioner );

				$log_data = [
					'source' => LogModel::SRC_PO,
					'module' => LogModel::MOD_PO_PURCHASE_ORDERS,
					'data'   => [
						'order_id'   => $order_id,
						'order_name' => '#' . $order_id,
						'user_id'    => $user->ID,
						'user_name'  => $user->display_name,
					],
					'entry'  => LogEntry::ACTION_PO_APPROVAL,
				];

				LogModel::maybe_save_log( $log_data );

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
	 * @return PurchaseOrders instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
