<?php
/**
 * Class Ajax
 *
 * @package     AtumPO\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since 0.8.0
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumCache;
use Atum\Components\AtumCapabilities;
use Atum\Components\AtumException;
use Atum\Components\AtumStockDecimals;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use Atum\Settings\Settings as AtumSettings;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Exports\POExtendedExport;
use AtumPO\Exports\POExtendedPreview;
use AtumPO\Invoices\Invoices;
use AtumPO\Invoices\Items\InvoiceItemFee;
use AtumPO\Invoices\Items\InvoiceItemProduct;
use AtumPO\Invoices\Items\InvoiceItemShipping;
use AtumPO\Invoices\Models\Invoice;
use AtumPO\ListTables\Lists\ListTable;
use AtumPO\MetaBoxes\POMetaBoxes;
use AtumPO\Models\POExtended;
use Atum\Suppliers\Supplier;
use Atum\Suppliers\Suppliers;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;

class Ajax {

	/**
	 * The singleton instance holder
	 *
	 * @var Ajax
	 */
	private static $instance;

	/**
	 * Email sender name
	 *
	 * @var string
	 */
	private $email_sender_name;

	/**
	 * Ajax singleton constructor.
	 *
	 * @since 0.8.0
	 */
	private function __construct() {

		// TODO: HANDLE CAPABILITIES FOR ANY PO CHANGES.
		if ( is_admin() ) {

			// Search for products from AddItem modal.
			add_action( 'wp_ajax_atum_po_json_search_products', array( $this, 'search_products' ) );

			// Search for suppliers from the corresponding enhanced select.
			add_action( 'wp_ajax_atum_po_json_search_suppliers', array( $this, 'search_suppliers' ) );

			// Search for users from the requisitioner select.
			add_action( 'wp_ajax_atum_po_json_search_users', array( $this, 'search_users' ) );

			// Add items to the PO.
			add_action( 'wp_ajax_atum_po_add_item', array( $this, 'add_po_items' ) );

			// Add all the out-of-stock/low-stock/restock status items from the same supplier(s) to the PO.
			add_action( 'wp_ajax_atum_po_add_suppliers_items', array( $this, 'add_suppliers_items' ) );

			// Add fee to the PO items.
			add_action( 'wp_ajax_atum_po_add_fee', array( $this, 'add_fee' ) );

			// Add shipping cost to the PO items.
			add_action( 'wp_ajax_atum_po_add_shipping', array( $this, 'add_shipping' ) );

			// Add a new delivery to the PO.
			add_action( 'wp_ajax_atum_po_add_delivery', array( $this, 'add_delivery' ) );

			// Remove a delivery from a PO.
			add_action( 'wp_ajax_atum_po_remove_delivery', array( $this, 'remove_delivery' ) );

			// Link files to deliveries.
			add_action( 'wp_ajax_atum_po_add_delivery_file', array( $this, 'add_delivery_file' ) );

			// Unlink files from deliveries.
			add_action( 'wp_ajax_atum_po_delete_delivery_file', array( $this, 'delete_delivery_file' ) );

			// Remove an item from a delivery.
			add_action( 'wp_ajax_atum_po_remove_delivery_item', array( $this, 'remove_delivery_item' ) );

			// Get the available delivery items for any delivery.
			add_action( 'wp_ajax_atum_po_get_delivery_items', array( $this, 'get_delivery_items' ) );

			// Update items for any delivery.
			add_action( 'wp_ajax_atum_po_update_delivery_items', array( $this, 'update_delivery_items' ) );

			// Add delivery items to stock.
			add_action( 'wp_ajax_atum_po_add_delivery_items_to_stock', array( $this, 'add_delivery_items_to_stock' ) );

			// Get delivery modal items.
			add_action( 'wp_ajax_atum_po_get_delivery_modal_items', array( $this, 'get_delivery_modal_items' ) );

			// Get invoice modal items.
			add_action( 'wp_ajax_atum_po_get_invoice_modal_items', array( $this, 'get_invoice_modal_items' ) );

			// Add a new invoice to the PO.
			add_action( 'wp_ajax_atum_po_add_invoice', array( $this, 'add_invoice' ) );

			// Update an invoice.
			add_action( 'wp_ajax_atum_po_update_invoice', array( $this, 'update_invoice' ) );

			// Save all the invoices.
			add_action( 'wp_ajax_atum_po_save_invoices', array( $this, 'save_invoices' ) );

			// Remove an invoice item.
			add_action( 'wp_ajax_atum_po_remove_invoice_item', array( $this, 'remove_invoice_item' ) );

			// Remove an invoice.
			add_action( 'wp_ajax_atum_po_remove_invoice', array( $this, 'remove_invoice' ) );

			// Add a file to the PO.
			add_action( 'wp_ajax_atum_po_add_file', array( $this, 'add_file' ) );

			// Add a file's description.
			add_action( 'wp_ajax_atum_po_update_file_description', array( $this, 'update_file_description' ) );

			// Remove a PO file.
			add_action( 'wp_ajax_atum_po_remove_file', array( $this, 'remove_file' ) );

			// Count unread comments for notifications badge.
			add_action( 'wp_ajax_atum_po_count_notifications', array( $this, 'count_notifications' ) );
			add_action( 'wp_ajax_atum_po_list_count_notifications', array( $this, 'count_notifications' ) );

			// Update comments read status for the current user.
			add_action( 'wp_ajax_atum_set_comment_read_status', array( $this, 'set_comment_read_status' ) );

			// Bulk actions for comments.
			add_action( 'wp_ajax_atum_po_comments_bulk_actions', array( $this, 'handle_comments_bulk_actions' ) );

			// Send PO email.
			add_action( 'wp_ajax_atum_po_send_email', array( $this, 'send_email' ) );

			// Find the order customer when entering a sales order ID.
			add_action( 'wp_ajax_atum_po_find_customer', array( $this, 'find_customer' ) );

			// Check if requested POs has order items added.
			// TODO: THE ITEMS AREN'T BEING CHECKED ANYMORE WHEN SWITCHING STATUSES. SHOULD WE?
			//add_action( 'wp_ajax_atum_po_items_added_status', array( $this, 'check_po_items' ) );

			// Search and filter from POs list table.
			add_action( 'wp_ajax_atum_po_fetch_list', array( $this, 'fetch_pos_list' ) );

			// Merge a PO with another.
			add_action( 'wp_ajax_atum_po_merge_purchase_orders', array( $this, 'merge_purchase_orders' ) );

			// Save the meta boxes sizes.
			add_action( 'wp_ajax_atum_po_save_meta_boxes_sizes', array( $this, 'meta_box_sizing' ) );

			// Search POs.
			add_action( 'wp_ajax_atum_po_json_search_po', array( $this, 'search_po' ) );

			// Auto-save PO.
			add_action( 'wp_ajax_atum_po_auto_save', array( $this, 'auto_save_po' ) );

			// Convert requisitioner statuses to normal statuses.
			add_action( 'wp_ajax_atum_po_convert_requisitioner_statuses', array( $this, 'convert_requisitioner_statuses' ) );

			// Reload PO order items after import.
			add_action( 'wp_ajax_atum_po_reload_order_items', array( $this, 'reload_po_items' ) );

			// Display email preview.
			add_action( 'wp_ajax_atum_po_email_preview', array( $this, 'po_email_preview' ) );

			// Show the new PO number preview.
			add_action( 'wp_ajax_atum_po_preview_next_number', array( $this, 'preview_next_po_number' ) );

			// Save a custom PO number.
			add_action( 'wp_ajax_atum_po_save_po_number', array( $this, 'save_po_number' ) );

			// Auto-generate and save the next available PO number.
			add_action( 'wp_ajax_atum_po_autogenerate_po_number', array( $this, 'autogenerate_po_number' ) );

			// Display PDF preview.
			add_action( 'wp_ajax_atum_po_pdf_preview', array( $this, 'po_pdf_preview' ) );

			// Save the PO's screen options.
			add_action( 'wp_ajax_atum_po_save_screen_options', array( $this, 'save_po_screen_options' ) );

			// Tool to update purchase prices taxes from ATUM tools.
			add_action( 'wp_ajax_atum_tool_po_purchase_price_taxes', array( $this, 'update_purchase_prices_tool' ) );

			// Get a PO preview from preview modal.
			add_action( 'wp_ajax_atum_po_list_get_preview', array( $this, 'get_po_preview' ) );

			// Create returning PO.
			add_action( 'wp_ajax_atum_po_create_returning', array( $this, 'create_returning_po' ) );

			// Return PO items.
			add_action( 'wp_ajax_atum_po_return_items', array( $this, 'return_po_items' ) );

		}

	}

	/**
	 * Search for products from the AddItem modal
	 *
	 * @since 0.8.0
	 *
	 * @package PO Items
	 */
	public function search_products() {

		check_ajax_referer( 'search-products', 'security' );

		if ( empty( $_GET['term'] ) ) {
			wp_die( [] );
		}

		$term  = (string) wc_clean( wp_unslash( $_GET['term'] ) );
		$po_id = absint( $_GET['id'] );

		if ( empty( $term ) ) {
			wp_die( [] );
		}

		$limit       = ! empty( $_GET['limit'] ) ? absint( $_GET['limit'] ) : absint( apply_filters( 'atum/purchase_orders_pro/ajax/search_products/json_search_limit', 30 ) );
		$include_ids = ! empty( $_GET['include'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['include'] ) ) : [];
		$exclude_ids = ! empty( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : [];

		$ids = AtumHelpers::search_products( $term, '', TRUE, FALSE, $limit, $include_ids, $exclude_ids );

		if ( ! $po_id ) {

			$url = wp_parse_url( wp_get_referer() );
			parse_str( $url['query'], $url_query );

			if ( ! empty( $url_query['post'] ) ) {
				$po_id = absint( $url_query['post'] );
			}

		}

		$included = [];

		if ( $po_id ) {

			/**
			 * Variable definition
			 *
			 * @var POExtended $po
			 */
			$po = AtumHelpers::get_atum_order_model( $po_id, FALSE, PurchaseOrders::POST_TYPE );

			if ( $po->exists() && $po->get_supplier( 'id' ) ) {

				// The Purchase Orders only should allow products from the current PO's supplier (if any).
				if ( 'yes' === AtumHelpers::get_option( 'po_supplier_products_restriction', 'yes' ) ) {

					// Get all the products linked to the supplier or with no supplier assigned.
					$supplier_products    = Suppliers::get_supplier_products( $po->get_supplier( 'id' ), [
						'product',
						'product_variation',
					], FALSE );
					$supplier_products    = is_array( $supplier_products ) ? array_map( 'absint', $supplier_products ) : [];
					$no_supplier_products = array_map( 'absint', Suppliers::get_no_supplier_products() );

					if ( ! empty( $no_supplier_products ) ) {

						// Include or exclude all the no supplier products to/from the array depending on this option.
						if ( 'yes' === AtumHelpers::get_option( 'po_no_supplier_products', 'yes' ) ) {
							$supplier_products = array_merge( $supplier_products, $no_supplier_products );
						}
						else {
							$excluded = $no_supplier_products;
						}

					}

					// If the PO supplier has no linked products, it must return an empty array.
					$included = $supplier_products;

				}

			}

		}

		if ( ! empty( $included ) ) {
			$ids = array_intersect( $ids, $included );
		}

		if ( ! empty( $excluded ) ) {
			$ids = array_diff( $ids, $excluded );
		}

		wp_send_json( apply_filters( 'atum/purchase_orders_pro/ajax/json_search_found_products', $this->prepare_json_search_products( $ids, $po ?? NULL ) ) );

	}

	/**
	 * Prepare the list of products to be returned to the ajax search
	 *
	 * @since 0.9.15
	 *
	 * @param int[]      $ids
	 * @param POExtended $po
	 *
	 * @return array
	 */
	private function prepare_json_search_products( $ids, $po = NULL ) {

		// Exclude variable products from results.
		$exclude_types = (array) apply_filters( 'atum/purchase_orders_pro/ajax/search_products/excluded_product_types', array_diff( AtumGlobals::get_inheritable_product_types(), [ 'grouped', 'bundle' ] ) );
		$products      = [];

		foreach ( $ids as $id ) {

			if ( ! is_numeric( $id ) ) {
				continue;
			}

			$product = AtumHelpers::get_atum_product( $id );

			if ( ! wc_products_array_filter_readable( $product ) ) {
				continue;
			}

			// Prevent orphan variations.
			if ( 'variation' === $product->get_type() ) {
				if ( ! $product->get_parent_id() || ! wc_get_product( $product->get_parent_id() ) ) {
					continue;
				}
			}

			if ( in_array( $product->get_type(), $exclude_types, TRUE ) ) {
				continue;
			}

			$product_id = $product->get_id();
			$image      = '';

			if ( $product->get_image_id() ) {
				$image = wp_get_attachment_image_url( $product->get_image_id() );
			}
			elseif ( $product->get_parent_id() ) {

				$parent_product = wc_get_product( $product->get_parent_id() );

				if ( $parent_product ) {
					$image = wp_get_attachment_image_url( $parent_product->get_image_id() );
				}

			}

			$supplier_id   = $product->get_supplier_id();
			$supplier_name = '&ndash;';

			if ( $supplier_id ) {
				$supplier      = new Supplier( $supplier_id );
				$supplier_name = $supplier->name;
			}

			$backorders = '&#45;';
			$stock      = $product->get_stock_quantity();

			if ( 0 > $stock ) {
				$backorders = $stock;
				$stock      = 0;
			}

			$product_data = apply_filters( 'atum/purchase_orders_pro/ajax/search_product_data', array(
				'id'          => $product_id,
				'name'        => $product->get_name(),
				'thumb'       => $image,
				'cost'        => $product->get_purchase_price(),
				'supplier'    => $supplier_name,
				'supplier_id' => $supplier_id,
				'stock'       => $stock,
				'backorders'  => $backorders,
				'meta'        => array(
					array(
						'label' => __( 'SKU', ATUM_PO_TEXT_DOMAIN ),
						'value' => $product->get_sku(),
					),
					array(
						'label' => __( 'Sup. SKU', ATUM_PO_TEXT_DOMAIN ),
						'value' => $product->get_supplier_sku(),
					),
					array(
						'label' => __( 'Stock', ATUM_PO_TEXT_DOMAIN ),
						'value' => $product->get_stock_quantity(),
					),
				),
			), $product, $po );

			if ( ! empty( $product_data ) ) {
				$products[ $product_id ] = $product_data;
			}

		}

		return array_filter( $products );

	}

	/**
	 * Add items to the PO
	 *
	 * @since 0.8.2
	 *
	 * @package PO Items
	 *
	 * @throws AtumException
	 */
	public function add_po_items() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		try {

			if ( empty( $_POST['atum_order_id'] ) ) {
				throw new AtumException( 'invalid_data', __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
			}

			/**
			 * Variable definition
			 *
			 * @var POExtended|\WP_Error $atum_order
			 */
			$atum_order = AtumHelpers::get_atum_order_model( absint( $_POST['atum_order_id'] ), TRUE, PurchaseOrders::POST_TYPE );

			if ( is_wp_error( $atum_order ) ) {
				throw new AtumException( $atum_order->get_error_code(), $atum_order->get_error_message() );
			}

			if ( ! $atum_order->exists() ) {
				$message = __( 'Invalid purchase order', ATUM_PO_TEXT_DOMAIN );
				throw new AtumException( 'invalid_atum_order', $message );
			}

			parse_str( $_POST['items_data'], $items_data );
			$html = array();

			if ( ! empty( $items_data['qty'] ) ) {

				$view_args = array(
					'atum_order'        => $atum_order,
					'currency'          => $atum_order->supplier_currency,
					'currency_template' => sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol( $atum_order->supplier_currency ), '%value%' ),
					'decimal_sep'       => wc_get_price_decimal_separator(),
					'step'              => AtumStockDecimals::get_input_step(),
					'supplier'          => $atum_order->get_supplier(),
					'field_name_prefix' => 'atum_order_item_',
				);

				foreach ( $items_data['qty'] as $product_id => $qty ) {

					if ( ! in_array( get_post_type( $product_id ), [ 'product', 'product_variation' ] ) ) {
						continue;
					}

					$cost = $items_data['cost'][ $product_id ] ?? 0;

					// Check if there is a conversion rate (when the PO's currency is distinct from the site's currency).
					if ( isset( $items_data['rate_from'], $items_data['rate_to'] ) ) {

						$rate_from = floatval( $items_data['rate_from'] );
						$rate_to   = floatval( $items_data['rate_to'] );

						if ( $rate_from !== $rate_to ) {
							$cost = ( $cost / $rate_from ) * $rate_to;
						}

					}

					// Add the product to the ATUM Order.
					$product        = AtumHelpers::get_atum_product( $product_id );
					$item           = $atum_order->add_product( $product, $qty, [], $cost );
					$item_id        = $item->get_id();
					$display_fields = AtumHelpers::get_option( 'po_display_extra_fields', [] );
					$display_fields = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];

					do_action( 'atum/atum_order/add_order_item_meta', $item_id, $item, $atum_order ); // Using the default ATUM hook here.

					// Load the item template.
					$view_args = array_merge( $view_args, [
						'item'           => $item,
						'item_id'        => $item_id,
						'class'          => 'new_row',
						'display_fields' => $display_fields,
					] );

					$html[ $item_id ]['products'][] = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/po-items/item', $view_args );

				}

			}

			wp_send_json_success( apply_filters( 'atum/purchase_orders_pro/after_adding_order_items', $html, $items_data, $atum_order ) );

		} catch ( AtumException $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}

	/**
	 * Add all the out-of-stock/low-stock/restock status items from the same supplier(s) to the PO
	 *
	 * @since 0.9.15
	 *
	 * @package PO Items
	 */
	public function add_suppliers_items() {

		check_ajax_referer( 'atum-po-add-to-po', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['suppliers'] ) ) {
			wp_send_json_error( __( 'At least one supplier ID is needed in order to retrieve its missing products', ATUM_PO_TEXT_DOMAIN ) );
		}

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;
		$ids                     = [];
		$po_product_ids          = [];
		$atum_order_id           = ! empty( $_POST['atum_order_id'] ) ? absint( $_POST['atum_order_id'] ) : FALSE;
		$modes                   = $_POST['modes'] ?? [];
		$post_statuses           = AtumGlobals::get_queryable_product_statuses();

		// If it's an actual PO, exclude its saved items.
		if ( $atum_order_id ) {

			$po       = AtumHelpers::get_atum_order_model( $atum_order_id, TRUE, PurchaseOrders::POST_TYPE );
			$po_items = $po->get_items();

			foreach ( $po_items as $po_item ) {
				$po_product_ids[] = absint( $po_item->get_variation_id() ?: $po_item->get_product_id() );
			}

		}

		// Exclude all the products that were in the results list already.
		if ( ! empty( $_POST['exclude'] ) ) {
			$po_product_ids = array_merge( $po_product_ids, array_map( 'absint', $_POST['exclude'] ) );
		}

		$suppliers = array_unique( array_map( 'absint', $_POST['suppliers'] ?? [] ) );

		// If the supplier's restriction is disabled, avoid the supplier filter.
		if ( $atum_order_id && 'no' === AtumHelpers::get_option( 'po_supplier_products_restriction', 'yes' ) ) {
			$suppliers = [];
		}

		$query_where = array(
			"p.post_type IN ('product', 'product_variation')",
			"p.post_status IN ('" . implode( "','", $post_statuses ) . "')",
			'apd.inheritable != 1',
		);

		if ( ! empty( $suppliers ) ) {

			if ( in_array( 0, $suppliers ) ) {
				$query_where[] = '( apd.supplier_id IN (' . implode( ',', $suppliers ) . ') OR apd.supplier_id IS NULL )';
			}
			else {
				$query_where[] = 'apd.supplier_id IN (' . implode( ',', $suppliers ) . ')';
			}

		}

		if ( ! empty( $po_product_ids ) ) {
			$query_where[] = 'p.ID NOT IN (' . implode( ',', array_unique( $po_product_ids ) ) . ')';
		}

		$query_join = [ "$atum_product_data_table apd ON (p.ID = apd.product_id)" ];

		foreach ( $modes as $mode ) {

			switch ( $mode ) {
				case 'low_stock':
					$low_stock_threshold = get_option( 'woocommerce_notify_low_stock_amount' );

					// Get all the products that have their stock under low stock amount value.
					$low_stock_join = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/low_stock_join', array_merge( $query_join, [
						"$wpdb->postmeta lsa ON (p.ID = lsa.post_id AND lsa.meta_key = '_low_stock_amount')",
						"$wpdb->postmeta s ON (p.ID = s.post_id AND s.meta_key = '_stock')",
					] ) );

					$global_low_stock_clause = FALSE === $low_stock_threshold || '' === $low_stock_threshold ? '' : "OR ( ( lsa.meta_value IS NULL OR lsa.meta_value = '' ) AND s.meta_value + 0 <= $low_stock_threshold )";

					$low_stock_where = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/low_stock_where', array_merge( $query_where, [
						's.meta_value IS NOT NULL',
						"( ( lsa.meta_value IS NOT NULL AND s.meta_value + 0 <= lsa.meta_value + 0 )
						$global_low_stock_clause )",
					] ) );

					$sql = "SELECT p.ID FROM $wpdb->posts p
					       LEFT JOIN " . implode( "\nLEFT JOIN ", $low_stock_join ) . '
					       WHERE ' . implode( "\nAND ", $low_stock_where );

					break;

				case 'restock':
					$restock_join  = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/restock_join', $query_join );
					$restock_where = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/restock_where', array_merge( $query_where, [
						'apd.restock_status = 1',
					] ) );

					// Get all the products with restock status and aren't inheritables.
					$sql = "SELECT p.ID FROM $wpdb->posts p
							LEFT JOIN " . implode( "\nLEFT JOIN ", $restock_join ) . '
							WHERE ' . implode( "\nAND ", $restock_where );
					break;

				case 'out_stock':
				default:
					$out_stock_join  = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/out_stock_join', $query_join );
					$out_stock_where = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items/out_stock_where', array_merge( $query_where, [
						"apd.atum_stock_status != 'instock'",
					] ) );

					// Get all the products that aren't in stock and aren't inheritables.
					$sql = "SELECT p.ID FROM $wpdb->posts p 
    						LEFT JOIN " . implode( "\nLEFT JOIN ", $out_stock_join ) . '
							WHERE ' . implode( "\nAND ", $out_stock_where );
					break;

			}

			$found_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$found_ids = apply_filters( 'atum/purchase_orders_pro/ajax/add_supplier_items', $found_ids, $mode, array_map( 'absint', $_POST['suppliers'] ), $atum_order_id );

			$ids = array_unique( array_merge( $ids, $found_ids ) );

		}

		wp_send_json_success( ! empty( $ids ) ? apply_filters( 'atum/purchase_orders/ajax/prepare_items', $this->prepare_json_search_products( $ids, $po ?? NULL ), $ids ) : [] );

	}

	/**
	 * Add PO fee
	 *
	 * @since 0.9.10
	 *
	 * @package PO items
	 *
	 * @throws AtumException
	 */
	public function add_fee() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit Purchase Orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['atum_order_id'] ) || empty( $_POST['fee'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );

			/**
			 * Variable definition
			 *
			 * @var POExtended|\WP_Error $po
			 */
			$po = AtumHelpers::get_atum_order_model( $atum_order_id, TRUE, PurchaseOrders::POST_TYPE );

			if ( is_wp_error( $po ) ) {
				throw new AtumException( $po->get_error_code(), $po->get_error_message() );
			}

			parse_str( $_POST['fee'], $fee );

			if ( ! is_array( $fee ) || empty( $fee['name'] ) || empty( $fee['meta-value-tax'] ) || empty( $fee['type'] ) ) {
				wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
			}

			$display_fields    = AtumHelpers::get_option( 'po_display_extra_fields', [] );
			$display_fields    = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];

			$amount = floatval( $fee['meta-value-tax'] );

			if ( 'percentage' === $fee['type'] ) {

				// We need to calculate totals first, so that $order->get_total() is correct.
				$po->calculate_totals();
				$amount = $po->total * ( $amount / 100 );

			}

			$fee_item = new POItemFee();
			$fee_item->set_amount( $amount );
			$fee_item->set_total( $amount );
			$fee_item->set_name( sanitize_text_field( $fee['name'] ) );

			// Add a fee line item.
			$item    = $po->add_fee( $fee_item );
			$item_id = $item->get_id();

			do_action( 'atum/ajax/atum_order/fee_added', $po, $item ); // Using ATUM's original hook name here.

			// Load template.
			// NOTE: we are loading the ATUM's original view so all the needed hooks are executed.
			$html = AtumHelpers::load_view_to_string( 'meta-boxes/atum-order/item-fee', compact( 'po', 'item', 'item_id', 'display_fields' ) );

			wp_send_json_success( $html );

		} catch ( AtumException $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}

	/**
	 * Add PO shipping
	 *
	 * @since 0.9.25
	 *
	 * @package PO items
	 *
	 * @throws \WC_Data_Exception
	 */
	public function add_shipping() {

		check_ajax_referer( 'atum-order-item', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit Purchase Orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['atum_order_id'] ) || empty( $_POST['shipping'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		try {

			$atum_order_id = absint( $_POST['atum_order_id'] );

			/**
			 * Variable definition
			 *
			 * @var POExtended|\WP_Error $po
			 */
			$po = AtumHelpers::get_atum_order_model( $atum_order_id, TRUE, PurchaseOrders::POST_TYPE );

			if ( is_wp_error( $po ) ) {
				throw new \WC_Data_Exception( $po->get_error_code(), $po->get_error_message() );
			}

			parse_str( $_POST['shipping'], $shipping );

			if ( ! is_array( $shipping ) || empty( $shipping['name'] ) || empty( $shipping['meta-value-tax'] ) || empty( $shipping['type'] ) ) {
				wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
			}

			$amount = floatval( $shipping['meta-value-tax'] );

			if ( 'percentage' === $shipping['type'] ) {

				// We need to calculate totals first, so that $order->get_total() is correct.
				$po->calculate_totals();
				$amount = $po->total * ( $amount / 100 );

			}

			$shipping_item = new POItemShipping();
			$shipping_item->set_total( $amount );
			$shipping_item->set_name( sanitize_text_field( $shipping['name'] ) );

			// Add a shipping line item.
			$item    = $po->add_shipping_cost( $shipping_item );
			$item_id = $item->get_id();

			$display_fields = AtumHelpers::get_option( 'po_display_extra_fields', [] );
			$display_fields = ! empty( $display_fields['options'] ) && is_array( $display_fields['options'] ) ? $display_fields['options'] : [];

			do_action( 'atum/ajax/atum_order/shipping_cost_added', $po, $item ); // Using ATUM's original hook name here.

			// Load template.
			// NOTE: we are loading the ATUM's original view so all the needed hooks are executed.
			$html = AtumHelpers::load_view_to_string( 'meta-boxes/atum-order/item-shipping', compact( 'po', 'item', 'item_id', 'display_fields' ) );

			wp_send_json_success( $html );

		} catch ( \WC_Data_Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

	}

	/**
	 * Search for suppliers from the suppliers select
	 *
	 * @since 0.8.3
	 *
	 * @package PO Data
	 */
	public function search_suppliers() {

		check_ajax_referer( 'search-suppliers', 'security' );

		global $wpdb;
		$where = '';

		if ( is_numeric( $_GET['term'] ) ) {
			$supplier_id = absint( $_GET['term'] );
			$where       = "AND ID LIKE $supplier_id";
		}
		elseif ( ! empty( $_GET['term'] ) ) {
			$supplier_name = $wpdb->esc_like( $_GET['term'] );
			$where         = "AND post_title LIKE '%%{$supplier_name}%%'";
		}
		else {
			wp_die( [] );
		}

		// Get all the orders with IDs starting with the provided number.
		$max_results   = absint( apply_filters( 'atum/purchase_orders_pro/search_suppliers/max_results', 10 ) );
		$post_statuses = AtumCapabilities::current_user_can( 'edit_private_suppliers' ) ? [ 'private', 'publish' ] : [ 'publish' ];

		// phpcs:disable WordPress.DB.PreparedSQL
		$query = $wpdb->prepare( "
			SELECT DISTINCT ID from $wpdb->posts 
			WHERE post_type = %s $where
			AND post_status IN ('" . implode( "','", $post_statuses ) . "')
			ORDER by post_title ASC
			LIMIT %d",
			Suppliers::POST_TYPE,
			$max_results
		);
		// phpcs:enable

		$supplier_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $supplier_ids ) ) {
			wp_die( [] );
		}

		$supplier_results = [];
		foreach ( $supplier_ids as $supplier_id ) {

			$supplier = new Supplier( $supplier_id );
			$image    = has_post_thumbnail( $supplier_id ) ? wp_get_attachment_image_url( get_post_thumbnail_id( $supplier_id ), 'medium' ) : '';

			$supplier_results[ $supplier_id ] = array(
				'name'                  => $supplier->name,
				'thumb'                 => $image,
				'code'                  => $supplier->code,
				'discount'              => $supplier->discount,
				'tax'                   => $supplier->tax_rate,
				'currency'              => $supplier->currency,
				'editLink'              => get_edit_post_link( $supplier_id ),
				'description'           => $supplier->description,
				'useDefaultDescription' => $supplier->use_default_description,
				'deliveryTerms'         => $supplier->delivery_terms,
				'useDefaultTerms'       => $supplier->use_default_terms,
				'lang'                  => $supplier->wpml_lang,
			);

		}

		wp_send_json( apply_filters( 'atum/purchase_orders_pro/json_search_found_suppliers', array_filter( $supplier_results ) ) );

	}

	/**
	 * Search for users from the requisitioner select
	 *
	 * @since 0.8.4
	 *
	 * @package PO Data
	 */
	public function search_users() {

		check_ajax_referer( 'search-users', 'security' );
		
		$search = '';

		$is_mention = isset( $_GET['mention'] ) && 'true' === $_GET['mention'];

		if ( is_numeric( $_GET['term'] ) ) {
			$search = absint( $_GET['term'] );
		}
		elseif ( ! empty( $_GET['term'] ) ) {
			$search = '*' . esc_attr( $_GET['term'] ) . '*';
		}
		else {
			wp_die( [] );
		}

		$users = get_users( [
			'search'  => $search,
			'orderby' => 'user_login',
			'number'  => $is_mention ? 6 : -1,
		] );

		if ( empty( $users ) ) {
			wp_die( [] );
		}

		$user_results = array();
		foreach ( $users as $user ) {

			/**
			 * Variable definition
			 *
			 * @var \WP_User $user
			 */
			if ( $is_mention && ! $user->has_cap( ATUM_PREFIX . 'read_order_notes' ) ) {
				continue;
			}

			$user_results[ $user->ID ] = array(
				'name'  => $user->user_login,
				'thumb' => get_avatar( $user->ID ),
			);

		}

		wp_send_json( apply_filters( 'atum/purchase_orders_pro/json_search_found_users', array_filter( $user_results ) ) );

	}

	/**
	 * Add a new delivery to the PO
	 *
	 * @since 0.9.1
	 *
	 * @package PO Deliveries
	 */
	public function add_delivery() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery']['items'] ) || ! is_array( $_POST['delivery']['items'] ) ) {
			wp_send_json_error( __( 'A delivery must have items', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivery_data = $_POST['delivery'];

		// Create the new delivery.
		$delivery = new Delivery();
		$delivery->set_props( array(
			'name'            => $delivery_data['name'],
			'date_created'    => $delivery_data['date'],
			'po'              => $_POST['po_id'],
			'document_number' => $delivery_data['documentNumber'],
		) );
		$success = $delivery->save();

		if ( is_wp_error( $success ) ) {
			wp_send_json_error( $success->get_error_message() );
		}

		// Add PO items to the delivery.
		$po = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE );
		foreach ( $_POST['delivery']['items'] as $item ) {

			do_action( 'atum/purchase_orders_pro/ajax/before_add_delivery_item', $item, $po, $delivery );

			// Support for other item types like inventories.
			if ( 'product' !== $item['type'] ) {
				continue;
			}

			$delivered = (float) $item['delivered'];

			// Do not add items that have not been marked as delivered.
			if ( $delivered <= 0 ) {
				continue;
			}

			$po_item = $po->get_item( absint( $item['id'] ) );

			// Add the product to the Delivery.
			$product = AtumHelpers::get_atum_product( $po_item->get_variation_id() ?: $po_item->get_product_id() );
			$delivery->add_product( $product, $delivered, [ 'po_item_id' => $po_item->get_id() ] );

		}

		$delivery_items = $delivery->get_items();

		// Run all the deliveries for this PO and recalculate quantities.
		$deliveries          = Deliveries::get_po_orders( $delivery->po );
		$delivery_items_qtys = [];

		foreach ( $deliveries as $deliv ) {
			$delivery_items_qtys = Delivery::calculate_delivery_items_qtys( $deliv->get_items(), $po, $deliv->get_id() );
		}

		/**
		 * Variables definition
		 *
		 * @var array $expected_qtys
		 * @var array $already_in_qtys
		 * @var array $delivered_qtys
		 * @var array $pending_qtys
		 */
		extract( $delivery_items_qtys );

		// Get only the delivery items.
		$expected_qtys   = $expected_qtys['delivery_item'] ?? [];
		$already_in_qtys = $already_in_qtys['delivery_item'] ?? [];
		$delivered_qtys  = $delivered_qtys['delivery_item'] ?? [];
		$pending_qtys    = $pending_qtys['delivery_item'] ?? [];

		$view_args     = compact( 'po', 'delivery', 'delivery_items', 'expected_qtys', 'already_in_qtys', 'delivered_qtys', 'pending_qtys' );
		$delivery_html = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/deliveries/delivery', $view_args );

		do_action( 'atum/purchase_orders_pro/after_delivery_added', $delivery );

		wp_send_json_success( $delivery_html );

	}

	/**
	 * Remove a new delivery from the PO
	 *
	 * @since 0.9.1
	 *
	 * @package PO Deliveries
	 */
	public function remove_delivery() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'delete_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to delete purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Before deleting the delivery, if any item was already added to stock, restore it.
		$delivery_id = absint( $_POST['delivery'] );
		$delivery    = AtumHelpers::get_atum_order_model( $delivery_id, TRUE, Deliveries::POST_TYPE );

		do_action( 'atum/purchase_orders_pro/before_delivery_remove', $delivery );

		$delivery->delete( TRUE );

		wp_send_json_success( __( 'Delivery successfully removed', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Link files to deliveries.
	 *
	 * @since 0.9.3
	 *
	 * @package PO Deliveries
	 */
	public function add_delivery_file() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery'] ) ) {
			wp_send_json_error( __( 'No delivery ID was specified', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['files'] ) ) {
			wp_send_json_error( __( 'Missing delivery files', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivery = new Delivery( absint( $_POST['delivery'] ) );

		if ( ! $delivery->get_id() ) {
			wp_send_json_error( __( 'Invalid delivery', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Add the files post meta.
		$delivery->set_files( wp_list_pluck( $_POST['files'], 'id' ) );
		$delivery->save_meta();

		do_action( 'atum/purchase_orders_pro/ajax/delivery_file_added', $delivery, wp_list_pluck( $_POST['files'], 'filename' ) );

		wp_send_json_success( _n( 'File linked to delivery successfully', 'Files linked to delivery successfully', count( $_POST['files'] ), ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Unlink a file from a delivery
	 *
	 * @since 0.9.3
	 *
	 * @package PO Deliveries
	 */
	public function delete_delivery_file() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery'] ) ) {
			wp_send_json_error( __( 'No delivery ID was specified', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['file'] ) ) {
			wp_send_json_error( __( 'File not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivery = new Delivery( absint( $_POST['delivery'] ) );

		if ( ! $delivery->get_id() ) {
			wp_send_json_error( __( 'Invalid delivery', ATUM_PO_TEXT_DOMAIN ) );
		}

		$files = array_diff( $delivery->files, [ absint( $_POST['file'] ) ] );
		$delivery->set_files( $files, FALSE, FALSE );
		$delivery->save_meta();

		do_action( 'atum/purchase_orders_pro/ajax/delivery_file_removed', $_POST['file'], $delivery );

		wp_send_json_success( __( 'File deleted from this delivery successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Remove an item from a delivery
	 *
	 * @since 0.9.3
	 *
	 * @package PO Deliveries
	 */
	public function remove_delivery_item() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery'] ) || empty( $_POST['delivery_item'] ) || empty( $_POST['po_item'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivery         = new Delivery( absint( $_POST['delivery'] ) );
		$delivery_item_id = absint( $_POST['delivery_item'] );

		// Allow external changes to this delivery.
		$delivery = apply_filters( 'atum/purchase_orders_pro/ajax/before_remove_delivery_item', $delivery, $delivery_item_id );

		$delivery->remove_item( $delivery_item_id );
		$saved = $delivery->save();

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( $saved->get_error_message() );
		}

		// TODO: RESTORE THE STOCK IF IT WAS ALREADY ADDED FOR THIS ITEM.

		// Send the updated deliveries items.
		wp_send_json_success( $this->prepare_delivery_items( AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE ) ) );

	}

	/**
	 * Get the available items for any delivery
	 *
	 * @since 0.9.3
	 *
	 * @package PO Deliveries
	 */
	public function get_delivery_items() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( empty( $_POST['delivery_id'] ) || empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivery = new Delivery( absint( $_POST['delivery_id'] ) );
		$po       = AtumHelpers::get_atum_order_model( absint( $_POST['po_id'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $delivery->exists() ) {
			wp_send_json_error( __( 'The delivery no longer exists', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po_items       = $po->get_items();
		$delivery_items = $delivery->get_items();

		wp_send_json_success( AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/deliveries/edit-delivery-item-rows', compact( 'po_items', 'delivery', 'delivery_items' ) ) );

	}

	/**
	 * Update items for the specified delivery
	 *
	 * @since 0.9.3
	 *
	 * @package PO Deliveries
	 */
	public function update_delivery_items() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['delivery']['id'] ) || empty( $_POST['po_id'] ) || empty( $_POST['delivery']['items'] ) || ! is_array( $_POST['delivery']['items'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$delivered_qtys_sum = array_sum( array_map( 'floatval', wp_list_pluck( $_POST['delivery']['items'], 'delivered' ) ) );

		if ( $delivered_qtys_sum <= 0 ) {
			wp_send_json_error( __( 'A delivery must have items.<br>If you want to remove all the items, you can delete the entire delivery instead.', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Create the new delivery.
		$delivery = new Delivery( absint( $_POST['delivery']['id'] ) );

		if ( ! $delivery->exists() ) {
			wp_send_json_error( __( 'Delivery not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Update the delivery items and add any additional item if needed.
		$po             = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE );
		$delivery_items = $delivery->get_items();

		$sent_items = count( $_POST['delivery']['items'] );
		$err_items  = 0;

		foreach ( $_POST['delivery']['items'] as $item ) {

			do_action( 'atum/purchase_orders_pro/ajax/before_edit_delivery_item', $item, $po, $delivery );

			// Support for other item types like inventories.
			if ( 'product' !== $item['type'] ) {
				continue;
			}

			$po_item_id            = absint( $item['id'] );
			$delivered             = (float) $item['delivered'];
			$current_delivery_item = NULL;

			foreach ( $delivery_items as $delivery_item ) {
				/**
				 * Variable definition
				 *
				 * @var DeliveryItemProduct $delivery_item
				 */
				if ( $delivery_item->get_po_item_id() === $po_item_id ) {

					// Check if product was deleted.
					if ( ! $delivery_item->get_product() instanceof \WC_Product ) {
						$err_items++;
						continue;
					}

					$current_delivery_item = $delivery_item;
					break;
				}
			}

			// Found: edit the existing item.
			if ( $current_delivery_item ) {

				// Remove the item from the delivery.
				if ( $delivered <= 0 ) {

					// Restore the stock (if needed).
					if ( 'yes' === $delivery_item->get_stock_changed() ) {
						$delivery->change_product_stock( $delivery_item, $delivery_item->get_quantity(), 'decrease' );
					}

					$delivery->remove_item( $current_delivery_item->get_id() );
					$delivery->save_items();

				}
				else {

					$old_quantity = $delivery_item->get_quantity();
					$current_delivery_item->set_quantity( $delivered );
					$current_delivery_item->save();

					// Change the product's stock automatically if it was already changed.
					if ( 'yes' === $delivery_item->get_stock_changed() ) {

						$new_quantity = $delivery_item->get_quantity();

						if ( $new_quantity !== $old_quantity ) {

							if ( $old_quantity > $new_quantity ) {
								$change_quantity = $old_quantity - $new_quantity;
								$action          = 'decrease';
							}
							else {
								$change_quantity = $new_quantity - $old_quantity;
								$action          = 'increase';
							}

							$delivery->change_product_stock( $delivery_item, $change_quantity, $action );

						}
					}

				}

			}
			// Not found: add a new item.
			else {

				// Do not add items that has not been marked as delivered.
				if ( $delivered <= 0 ) {
					continue;
				}

				$po_item = $po->get_item( $po_item_id );

				if ( ! $po_item->get_product() instanceof \WC_Product ) {
					$err_items++;
					continue;
				}

				// Add the product to the Delivery.
				$product = AtumHelpers::get_atum_product( $po_item->get_variation_id() ?: $po_item->get_product_id() );
				$delivery->add_product( $product, $delivered, [ 'po_item_id' => $po_item_id ] );

			}

		}

		if ( $err_items >= $sent_items ) {
			wp_send_json_error( _n( "The selected product doesn't exist", "The selected products don't exist", $err_items, ATUM_PO_TEXT_DOMAIN ) );
		}

		// Send the updated deliveries items.
		wp_send_json_success( $this->prepare_delivery_items( $po ) );

	}

	/**
	 * Add the specified delivery items to stock
	 *
	 * @since 0.9.5
	 *
	 * @package PO Deliveries
	 */
	public function add_delivery_items_to_stock() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( empty( $_POST['deliveryId'] ) || empty( $_POST['items'] ) || ! is_array( $_POST['items'] ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_PO_TEXT_DOMAIN ) );
		}

		/**
		 * Variable definition
		 *
		 * @var Delivery $delivery
		 */
		$delivery = AtumHelpers::get_atum_order_model( absint( $_POST['deliveryId'] ), TRUE, Deliveries::POST_TYPE );

		foreach ( $_POST['items'] as $item_data ) {

			do_action( 'atum/purchase_orders_pro/ajax/before_add_delivery_item_to_stock', $item_data, $delivery );

			if ( 'product' === $item_data['type'] ) {

				$delivery_item = $delivery->get_item( $item_data['id'] );

				if ( 'yes' !== $delivery_item->get_stock_changed() && $delivery_item->get_product() instanceof \WC_Product ) {
					$delivery->change_product_stock( $delivery_item, $delivery_item->get_quantity(), 'increase' );
				}

			}

		}

		// Auto-switch the PO status accordingly.
		$po                       = AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE );
		$not_added_to_stock_items = $po->get_po_items_not_added_to_stock();

		$po->set_status( empty( $not_added_to_stock_items ) ? 'atum_added' : 'atum_partially_added' );
		$po->save();

		$return_data = array(
			'items'     => $this->prepare_delivery_items( AtumHelpers::get_atum_order_model( $delivery->po, TRUE, PurchaseOrders::POST_TYPE ) ),
			'po_status' => $po->get_status(),
		);

		wp_send_json_success( $return_data );

	}

	/**
	 * Prepare the deliveries' items for the Ajax response
	 *
	 * @since 0.9.5
	 *
	 * @param POExtended $po
	 *
	 * @return string[]
	 */
	private function prepare_delivery_items( $po ) {

		// As one of the deliveries has been modified, make sure we don't return cached items.
		AtumCache::disable_cache();

		// We must update all the deliveries' items.
		$deliveries             = Deliveries::get_po_orders( $po->get_id() );
		$is_editable            = $po->is_editable();
		$output                 = array();
		$already_in_items_total = $delivered_items_total = $pending_items_total = 0;

		foreach ( $deliveries as $delivery ) {

			$delivery_items      = $delivery->get_items();
			$delivery_items_qtys = Delivery::calculate_delivery_items_qtys( $delivery_items, $po, $delivery->get_id() );

			/**
			 * Variables definition
			 *
			 * @var array $expected_qtys
			 * @var array $already_in_qtys
			 * @var array $delivered_qtys
			 * @var array $pending_qtys
			 * @var int   $delivered_total
			 * @var int   $already_in_total
			 * @var int   $pending_total
			 */
			extract( $delivery_items_qtys );

			// Get only the delivery items.
			$expected_qtys   = $expected_qtys['delivery_item'] ?? [];
			$already_in_qtys = $already_in_qtys['delivery_item'] ?? [];
			$delivered_qtys  = $delivered_qtys['delivery_item'] ?? [];
			$pending_qtys    = $pending_qtys['delivery_item'] ?? [];

			$view_args = compact( 'po', 'delivery', 'delivery_items', 'expected_qtys', 'already_in_qtys', 'delivered_qtys', 'pending_qtys', 'already_in_items_total', 'delivered_items_total', 'pending_items_total', 'is_editable' );

			$output[ $delivery->get_id() ] = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/deliveries/delivery-items', $view_args );

		}

		return $output;

	}

	/**
	 * Get the updated delivery items for the Add Delivery Modal
	 *
	 * @since 0.9.5
	 *
	 * @package PO Deliveries
	 */
	public function get_delivery_modal_items() {

		check_ajax_referer( 'po-delivery-nonce', 'security' );

		if ( empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po         = AtumHelpers::get_atum_order_model( absint( $_POST['po_id'] ), TRUE, PurchaseOrders::POST_TYPE );
		$deliveries = Deliveries::get_po_orders( $po->get_id() );

		wp_send_json( AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/js-templates/add-delivery-modal', compact( 'po', 'deliveries' ) ) );

	}

	/**
	 * Get invoice modal items
	 *
	 * @since 0.9.21
	 *
	 * @package PO Invoices
	 */
	public function get_invoice_modal_items() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'Missing PO ID', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po_id'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( 'Invalid PO', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Just return the updated template.
		wp_send_json_success( AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/js-templates/add-invoice-modal', compact( 'po' ) ) );

	}

	/**
	 * Add a new invoice to the PO
	 *
	 * @since 0.9.6
	 *
	 * @package PO Invoices
	 */
	public function add_invoice() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice'] ) || empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice']['items'] ) || ! is_array( $_POST['invoice']['items'] ) ) {
			wp_send_json_error( __( 'An invoice must have items', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice_data = $_POST['invoice'];

		// Create the new invoice.
		$invoice = new Invoice();
		$invoice->set_props( array(
			'date_created'    => $invoice_data['date'],
			'po'              => $_POST['po_id'],
			'document_number' => $invoice_data['documentNumber'],
			'files'           => $invoice_data['file'] ? [ absint( $invoice_data['file'] ) ] : NULL,
		) );
		$invoice->save(); // Save the invoice before adding items.

		// Add PO items to the invoice.
		$po = AtumHelpers::get_atum_order_model( $invoice->po, TRUE, PurchaseOrders::POST_TYPE );
		foreach ( $_POST['invoice']['items'] as $item ) {

			do_action( 'atum/purchase_orders_pro/ajax/before_add_invoice_item', $item, $po, $invoice );

			$qty = (float) $item['qty'];

			// Do not add items with 0 qty.
			if ( $qty <= 0 ) {
				continue;
			}

			$po_item_type = 'invoice_item' === $item['type'] ? 'line_item' : $item['type'];
			$po_item      = $po->get_item( absint( $item['poItem'] ), $po_item_type );

			if ( $po_item ) {

				$props = [
					'po_item_id' => $po_item->get_id(),
				];

				switch ( $item['type'] ) {
					// Add the product to the Invoice.
					case 'line_item':
						$product = AtumHelpers::get_atum_product( $po_item->get_variation_id() ?: $po_item->get_product_id() );

						if ( $product instanceof \WC_Product ) {
							$invoice->add_product( $product, $qty, $props );
						}
						break;

					// Add fee to the Invoice.
					case 'fee':
						$invoice->add_fee( $po_item, $props );
						break;

					// Add shipping to the Invoice.
					case 'shipping':
						$invoice->add_shipping_cost( $po_item, $props );
						break;
				}

			}

		}

		$invoice->calculate_totals();
		$success = $invoice->save();

		if ( is_wp_error( $success ) ) {
			wp_send_json_error( $success->get_error_message() );
		}

		$invoice_items = $invoice->get_items( [ $invoice->get_line_item_type(), 'fee', 'shipping' ] );
		$invoice_html  = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/invoices/invoice', compact( 'invoice', 'invoice_items', 'po' ) );

		do_action( 'atum/purchase_orders_pro/after_invoice_added', $invoice );

		wp_send_json_success( $invoice_html );

	}

	/**
	 * Update an invoice
	 *
	 * @since 0.9.6
	 *
	 * @package PO Invoices
	 */
	public function update_invoice() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice'] ) || empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice']['items'] ) || ! is_array( $_POST['invoice']['items'] ) ) {
			wp_send_json_error( __( 'An invoice must have items', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice_data = $_POST['invoice'];

		// Get the invoice to update.
		$invoice = AtumHelpers::get_atum_order_model( absint( $invoice_data['id'] ), TRUE, Invoices::POST_TYPE );

		if ( is_wp_error( $invoice ) ) {
			wp_send_json_error( __( 'Invoice not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice->set_props( array(
			'date_created'    => $invoice_data['date'],
			'document_number' => $invoice_data['documentNumber'],
			'files'           => $invoice_data['file'] ? [ absint( $invoice_data['file'] ) ] : NULL,
			'po'              => absint( $_POST['po_id'] ), // This is required.
		) );

		// Update/Add invoice items.
		$po            = AtumHelpers::get_atum_order_model( $invoice->po, TRUE, PurchaseOrders::POST_TYPE );
		$invoice_items = $invoice->get_items( [ 'invoice_item', 'fee', 'shipping' ] );

		foreach ( $_POST['invoice']['items'] as $item ) {

			do_action( 'atum/purchase_orders_pro/ajax/before_update_invoice_item', $item, $po, $invoice );

			$qty             = (float) $item['qty'];
			$invoice_item_id = absint( $item['id'] );
			$found_item      = $invoice_item_id && array_key_exists( $invoice_item_id, $invoice_items );

			// Remove the item if exists and the qty is 0.
			if ( $qty <= 0 ) {

				if ( ! $found_item ) {
					continue;
				}

				$invoice->remove_item( $invoice_item_id );

			}
			else {

				$po_item_type = 'invoice_item' === $item['type'] ? 'line_item' : $item['type'];
				$po_item      = $po->get_item( absint( $item['poItem'] ), $po_item_type );

				// Update the item.
				if ( $found_item ) {

					$found_item_obj = $invoice_items[ $invoice_item_id ];

					switch ( $found_item_obj->get_type() ) {
						case 'invoice_item':
							/**
							 * Variable definition
							 *
							 * @var InvoiceItemProduct $found_item_obj
							 */
							$found_item_obj->set_quantity( $qty );
							break;

						case 'shipping':
							/**
							 * Variable definition
							 *
							 * @var InvoiceItemShipping $found_item_obj
							 */
							$found_item_obj->set_total( $po_item->get_total() );
							$found_item_obj->set_method_id( $po_item->get_method_id() );
							$found_item_obj->set_taxes( $po_item->get_taxes() );
							$found_item_obj->set_method_title( $po_item->get_method_title() );
							$found_item_obj->set_name( $po_item->get_name() );
							$found_item_obj->add_meta_data( '_total_tax', $po_item->get_total_tax( 'edit' ), TRUE );
							break;

						case 'fee':
							/**
							 * Variable definition
							 *
							 * @var InvoiceItemFee $found_item_obj
							 */
							$found_item_obj->set_total( $po_item->get_total() );
							$found_item_obj->set_total_tax( $po_item->get_total_tax() );
							$found_item_obj->set_tax_status( $po_item->get_tax_status() );
							$found_item_obj->set_taxes( $po_item->get_taxes() );
							$found_item_obj->set_tax_class( $po_item->get_tax_class() );
							$found_item_obj->set_name( $po_item->get_name() );

							break;
					}

					$found_item_obj->save();

				}
				// Add a new item.
				else {

					$props = [ 'po_item_id' => $po_item->get_id() ];

					switch ( $po_item->get_type() ) {
						case 'fee':
							$invoice->add_fee( $po_item, $props );
							break;

						case 'shipping':
							$invoice->add_shipping_cost( $po_item, $props );
							break;

						case 'line_item':
						default:
							$product = AtumHelpers::get_atum_product( $po_item->get_variation_id() ?: $po_item->get_product_id() );
							$invoice->add_product( $product, $qty, $props );
							break;
					}

				}

			}

		}

		$invoice->calculate_totals();
		$success = $invoice->save();

		if ( ! $success ) {
			wp_send_json_error( __( 'The invoice could not be saved', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice_items = $invoice->get_items();
		$invoice_html  = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/invoices/invoice', compact( 'invoice', 'invoice_items', 'po' ) );

		wp_send_json_success( $invoice_html );

	}

	/**
	 * Save the invoices to the PO
	 *
	 * @since 0.9.6
	 *
	 * @package PO Invoices
	 */
	public function save_invoices() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoices'] ) || ! is_array( $_POST['invoices'] ) || empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		foreach ( $_POST['invoices'] as $invoice_data ) {

			$invoice = AtumHelpers::get_atum_order_model( absint( $invoice_data['id'] ), TRUE, Invoices::POST_TYPE );

			if ( is_wp_error( $invoice ) ) {
				continue;
			}

			parse_str( $invoice_data['items'], $invoice_items );

			if ( ! empty( $invoice_items ) ) {
				$invoice->save_order_items( $invoice_items );
			}

		}

		ob_start();
		POMetaBoxes::get_instance()->show_invoices_meta_box( absint( $_POST['po'] ) );

		wp_send_json_success( ob_get_clean() );

	}

	/**
	 * Remove an invoice item
	 *
	 * @since 0.9.6
	 *
	 * @package PO Invoices
	 */
	public function remove_invoice_item() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice'] ) || empty( $_POST['invoice_item'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice         = new Invoice( absint( $_POST['invoice'] ) );
		$invoice_item_id = absint( $_POST['invoice_item'] );

		do_action( 'atum/purchase_orders_pro/before_invoice_item_remove', $invoice_item_id, $invoice );

		$invoice->remove_item( $invoice_item_id );
		$invoice->calculate_totals();
		$saved = $invoice->save();

		if ( is_wp_error( $saved ) ) {
			wp_send_json_error( $saved->get_error_message() );
		}

		wp_send_json_success();

	}

	/**
	 * Remove an invoice
	 *
	 * @since 0.9.6
	 *
	 * @package PO Invoices
	 */
	public function remove_invoice() {

		check_ajax_referer( 'po-invoice-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['invoice'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$invoice_id = absint( $_POST['invoice'] );
		$invoice    = AtumHelpers::get_atum_order_model( $invoice_id, TRUE, Invoices::POST_TYPE );

		do_action( 'atum/purchase_orders_pro/before_invoice_remove', $invoice );

		$invoice->delete( TRUE );

		wp_send_json_success( __( 'Invoice removed successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Add a file to the PO
	 *
	 * @since 0.9.7
	 *
	 * @package PO Files
	 */
	public function add_file() {

		check_ajax_referer( 'po-file-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['file'] ) || ! is_array( $_POST['file'] ) || empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		/**
		 * Variable definition
		 *
		 * @var POExtended $po
		 */
		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		// Add the file to the PO.
		$files    = $po->files ?: [];
		$new_file = $_POST['file'];

		if ( ! empty( wp_list_filter( $files, [ 'id' => $new_file['id'] ] ) ) ) {
			wp_send_json_error( __( 'This file was already added to this PO', ATUM_PO_TEXT_DOMAIN ) );
		}

		$sanitized_file = array(
			'id'   => absint( $new_file['id'] ),
			'desc' => esc_textarea( $new_file['description'] ),
		);

		if ( 'yes' === $new_file['fromSupplier'] ) {
			$sanitized_file['supplier'] = 'yes';
		}

		$files[] = $sanitized_file;
		$po->set_files( $files );
		$po->save_meta();

		do_action( 'atum/purchase_orders_pro/ajax/file_added', $po, $new_file );

		wp_send_json_success( __( 'File added successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Update a PO file's description
	 *
	 * @since 0.9.7
	 *
	 * @package PO Files
	 */
	public function update_file_description() {

		check_ajax_referer( 'po-file-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['file_id'] ) || empty( $_POST['po'] ) || ! isset( $_POST['description'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		/**
		 * Variable definition
		 *
		 * @var POExtended $po
		 */
		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		$files = $po->files ?: [];

		foreach ( $files as &$file ) {
			if ( (int) $file['id'] === (int) $_POST['file_id'] ) {
				$file['desc'] = esc_textarea( $_POST['description'] );
				break;
			}
		}

		$po->set_files( $files );
		$po->save_meta();

		wp_send_json_success( __( 'File description updated successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Remove a PO file
	 *
	 * @since 0.9.7
	 *
	 * @package PO Files
	 */
	public function remove_file() {

		check_ajax_referer( 'po-file-nonce', 'security' );

		if ( ! AtumCapabilities::current_user_can( 'edit_purchase_orders' ) ) {
			wp_send_json_error( __( 'You are not allowed to edit purchase orders', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['file_id'] ) || empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		/**
		 * Variable definition
		 *
		 * @var POExtended $po
		 */
		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		$files = $po->files ?: [];

		foreach ( $files as $index => $file ) {
			if ( (int) $file['id'] === (int) $_POST['file_id'] ) {
				unset( $files[ $index ] );
				break;
			}
		}

		$po->set_files( $files );
		$po->save_meta();
		do_action( 'atum/purchase_orders_pro/ajax/file_removed', absint( $_POST['file_id'] ), $po );

		wp_send_json_success( __( 'File removed successfully', ATUM_PO_TEXT_DOMAIN ) );

	}
	
	/**
	 * Send the unread notifications quantity for the current user to the notifications badge.
	 *
	 * @since 0.9.6
	 *
	 * @package PO Comments
	 */
	public function count_notifications() {

		check_ajax_referer( 'po-count-notifications-nonce', 'security' );

		$atum_order_id = absint( $_POST['order_id'] );

		$target = doing_action( 'wp_ajax_atum_po_list_count_notifications' ) ? 'user' : 'all';

		wp_send_json_success( Helpers::count_po_notifications( $atum_order_id, 'unread', $target ) );

	}

	/**
	 * Update comments read status for the current user.
	 *
	 * @since 0.9.6
	 *
	 * @param int    $comment_id
	 * @param string $status
	 *
	 * @package PO Comments
	 */
	private static function update_comment_read_status( $comment_id, $status ) {

		$user_id = get_current_user_id();

		$result_status = 'read' === $status ? 'unread' : 'read';
		$read_status   = get_comment_meta( $comment_id, 'po_read_status', TRUE );

		if ( empty( $read_status ) ) {
			$read_status = array();
		}

		if ( 'read' === $status ) {
			$read_status[ $user_id ] = TRUE;
			$result_status           = $status;
		}
		else {
			if ( isset( $read_status[ $user_id ] ) ) {
				unset( $read_status[ $user_id ] );
			}
			$result_status = $status;
		}

		if ( empty( $read_status ) ) {
			delete_comment_meta( $comment_id, 'po_read_status' );
		}
		else {
			update_comment_meta( $comment_id, 'po_read_status', $read_status );
		}

		return $result_status;

	}

	/**
	 * Ajax callback for update comments read status for the current user.
	 *
	 * @since 0.9.6
	 *
	 * @package PO Comments
	 */
	public function set_comment_read_status() {
		check_ajax_referer( 'po-comments-nonce', 'security' );

		$comment_id = absint( $_POST['comment_id'] );
		$status     = esc_attr( $_POST['status'] );

		wp_send_json_success( self::update_comment_read_status( $comment_id, $status ) );
	}

	/**
	 * Handle the PO comments bulk actions.
	 *
	 * @since 0.9.6
	 *
	 * @package PO Comments
	 */
	public function handle_comments_bulk_actions() {
		check_ajax_referer( 'po-comments-nonce', 'security' );

		if (
			defined( 'DOING_AJAX' ) && DOING_AJAX === TRUE && isset( $_POST['mode'] ) &&
			in_array( $_POST['mode'], [
				'mark_read',
				'mark_unread',
				'remove',
			], TRUE )
		) {
			$comment_ids = array_map( 'absint', $_POST['ids'] );
			$result      = [];

			if ( empty( $comment_ids ) ) {
				wp_die();
			}

			switch ( $_POST['mode'] ) {
				case 'mark_read':
				case 'mark_unread':
					$status = substr( $_POST['mode'], 5 );

					foreach ( $comment_ids as $comment_id ) {
						if ( self::update_comment_read_status( $comment_id, $status ) === $status ) {
							$result[] = $comment_id;
						}
					}
					break;
				case 'remove':
					foreach ( $comment_ids as $comment_id ) {
						if ( wp_delete_comment( $comment_id, TRUE ) ) {
							$result[] = $comment_id;
						}
					}
					break;
			}
			wp_send_json_success( $result );

		}

		wp_die();

	}

	/**
	 * Send PO email.
	 *
	 * @since 0.9.8
	 *
	 * @package PO Email Popup
	 *
	 * @throws MpdfException
	 */
	public function send_email() {

		check_ajax_referer( 'po-email-nonce', 'security' );

		if ( ! isset( $_POST['po_id'], $_POST['from'], $_POST['to'], $_POST['cc'], $_POST['bcc'], $_POST['subject'], $_POST['body'], $_POST['template'] ) ) {
			wp_send_json_error( __( 'Invalid or missing data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po_id       = absint( $_POST['po_id'] );
		$from        = trim( $_POST['from'] );
		$name        = isset( $_POST['name'] ) ? wc_clean( trim( $_POST['name'] ) ) : '';
		$to          = trim( $_POST['to'] );
		$cc          = trim( $_POST['cc'] );
		$bcc         = trim( $_POST['bcc'] );
		$subject     = stripslashes( sanitize_text_field( $_POST['subject'] ) );
		$body        = stripslashes( str_replace( 'data-mce-style', 'style', wp_kses_post( $_POST['body'] ) ) );
		$template    = esc_attr( $_POST['template'] );
		$attachments = array();
		$include_pdf = wc_string_to_bool( $_POST['include_pdf'] );

		$this->email_sender_name = $name;

		$po = AtumHelpers::get_atum_order_model( $po_id, TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( 'Purchase Order not found.', ATUM_PO_TEXT_DOMAIN ) );
		}

		// If changed, save email template for future emails.
		if ( $template && $po->email_template !== $template ) {
			$po->set_email_template( $template );
			$po->save();
		}

		$headers = [
			"from:$from",
			"reply-to:$from",
			'content-type:text/html',
		];

		// Path for attachments.
		$uploads  = wp_upload_dir();
		$path_dir = $uploads['basedir'] . '/atum/attachments/';
		if ( ! is_dir( $path_dir ) ) {
			mkdir( $path_dir );
		}

		// Add PO PDF to attachments.
		if ( $include_pdf ) {
			$export        = new POExtendedExport( $po->get_id() );
			$pdf_content   = $export->generate( Destination::STRING_RETURN );
			$pdf_file      = "{$path_dir}PO-{$po->number}.pdf";
			$attachments[] = $pdf_file;

			file_put_contents( $pdf_file, $pdf_content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		}

		// Check attachments.
		if ( ! empty( $_FILES ) ) {

			foreach ( $_FILES as $file ) {

				if ( $file['name'] ) {
					$attached_file = $path_dir . $file['name'];
					$attachments[] = $attached_file;

					move_uploaded_file( $file['tmp_name'], $attached_file );
				}

			}

		}

		// Validate sender address.
		if ( empty( $from ) ) {
			wp_send_json_error( __( "The sender's email is missing", ATUM_PO_TEXT_DOMAIN ) );
		}
		elseif ( str_contains( $from, ',' ) ) {

			foreach ( explode( ',', $from ) as $email ) {
				$email = sanitize_email( $email );
				if ( ! is_email( $email ) ) {
					/* translators: the email address */
					wp_send_json_error( sprintf( __( "Invalid sender's email: %s", ATUM_PO_TEXT_DOMAIN ), $email ) );
				}
			}

		}
		elseif ( str_contains( $from, ';' ) ) {

			foreach ( explode( ';', $from ) as $email ) {
				$email = sanitize_email( $email );
				if ( ! is_email( $email ) ) {
					/* translators: the email address */
					wp_send_json_error( sprintf( __( "Invalid sender's email: %s", ATUM_PO_TEXT_DOMAIN ), $email ) );
				}
			}

		}
		elseif ( ! is_email( $from ) ) {
			/* translators: the email address */
			wp_send_json_error( sprintf( __( "Invalid sender's email: %s", ATUM_PO_TEXT_DOMAIN ), $from ) );
		}

		// Validate recipient address.
		if ( empty( $to ) ) {
			wp_send_json_error( __( "The recipient's email is missing", ATUM_PO_TEXT_DOMAIN ) );
		}
		elseif ( str_contains( $to, ',' ) ) {

			foreach ( explode( ',', $to ) as $email ) {
				$email = sanitize_email( $email );
				if ( ! is_email( $email ) ) {
					/* translators: the email address */
					wp_send_json_error( sprintf( __( "Invalid recipient's email: %s", ATUM_PO_TEXT_DOMAIN ), $email ) );
				}
			}

		}
		elseif ( str_contains( $to, ';' ) ) {

			foreach ( explode( ';', $to ) as $email ) {
				$email = sanitize_email( $email );
				if ( ! is_email( $email ) ) {
					/* translators: the email address */
					wp_send_json_error( sprintf( __( "Invalid recipient's email: %s", ATUM_PO_TEXT_DOMAIN ), $email ) );
				}
			}

		}
		elseif ( ! is_email( $to ) ) {
			/* translators: the email address */
			wp_send_json_error( sprintf( __( "Invalid recipient's email: %s", ATUM_PO_TEXT_DOMAIN ), $to ) );
		}

		// Process CC address.
		if ( is_email( $cc ) ) {
			$headers[] = 'cc:' . sanitize_email( $cc );
		}
		elseif ( str_contains( $cc, ',' ) ) {

			$cc_list = array();

			foreach ( explode( ',', $cc ) as $email ) {
				$email = sanitize_email( $email );
				if ( is_email( $email ) ) {
					$cc_list[] = $email;
				}
			}

			$headers[] = 'cc:' . implode( ',', $cc_list );

		}
		elseif ( str_contains( $cc, ';' ) ) {

			$cc_list = array();

			foreach ( explode( ';', $cc ) as $email ) {
				$email = sanitize_email( $email );
				if ( is_email( $email ) ) {
					$cc_list[] = $email;
				}
			}

			$headers[] = 'cc:' . implode( ',', $cc_list );

		}

		// Process BCC address.
		if ( is_email( $bcc ) ) {
			$headers[] = 'bcc:' . sanitize_email( $bcc );
		}
		elseif ( str_contains( $bcc, ',' ) ) {

			$bcc_list = array();

			foreach ( explode( ',', $bcc ) as $email ) {
				$email = sanitize_email( $email );
				if ( is_email( $email ) ) {
					$bcc_list[] = $email;
				}
			}

			$headers[] = 'bcc:' . implode( ',', $bcc_list );

		}
		elseif ( str_contains( $bcc, ';' ) ) {

			$bcc_list = array();

			foreach ( explode( ';', $bcc ) as $email ) {
				$email = sanitize_email( $email );
				if ( is_email( $email ) ) {
					$bcc_list[] = $email;
				}
			}

			$headers[] = 'bcc:' . implode( ',', $bcc_list );

		}

		// Validate subject.
		if ( empty( $subject ) ) {
			wp_send_json_error( __( "The email's subject is missing", ATUM_PO_TEXT_DOMAIN ) );
		}

		/* translators: first is the year number and second is the company name */
		$footer        = AtumHelpers::get_option( 'po_default_emails_footer', sprintf( __( '&copy;%1$d %2$s. All rights reserved.', ATUM_PO_TEXT_DOMAIN ), date_i18n( 'Y' ), AtumHelpers::get_option( 'company_name', '' ) ) );
		$logo_id       = AtumHelpers::get_option( 'po_default_emails_template_logo', '' );
		$logo          = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
		$primary_color = AtumHelpers::get_option( 'po_default_emails_template_color', '#00B8DB' );
		$company_name  = $po->purchaser_name;
		$email_content = AtumHelpers::load_view_to_string( ATUM_PO_PATH . "views/email-templates/$template/body", compact( 'body', 'subject', 'footer', 'logo', 'company_name', 'primary_color' ) );

		if ( ! $email_content ) {
			wp_send_json_error( __( "The email template wasn't found", ATUM_PO_TEXT_DOMAIN ) );
		}

		$get_email_from_name = function ( $name_from ) {
			return $this->email_sender_name;
		};

		add_filter( 'wp_mail_from_name', $get_email_from_name );
		$email_transfer = wp_mail( $to, $subject, $email_content, $headers, $attachments );
		remove_filter( 'wp_mail_from_name', $get_email_from_name );

		if ( FALSE === $email_transfer ) {
			wp_send_json_error( __( "The email couldn't be sent", ATUM_PO_TEXT_DOMAIN ) );
		}

		$extra_data = array(
			'cc'   => $cc,
			'bcc'  => $bcc,
			'body' => $body,
		);

		update_post_meta( $po_id, 'po_email_sent', date_i18n( 'Y/m/d H:i:s', FALSE, TRUE ) );

		do_action( 'atum/purchase_orders_pro/email_sent', $po, $to, $subject, $attachments, $extra_data );

		// Update PO status.
		if ( in_array( $po->get_status(), [ 'atum_pending', 'atum_new', 'atum_approval', 'atum_approved' ] ) ) {
			$po->update_status( 'atum_ordered' );
		}

		// Remove files from server.
		foreach ( $attachments as $attachment ) {
			unlink( $attachment );
		}

		wp_send_json_success( [
			'email_status' => $email_transfer,
		] );

	}

	/**
	 * Find the order customer when entering a sales order ID.
	 *
	 * @since 0.9.9
	 *
	 * @package PO Data
	 */
	public function find_customer() {

		check_ajax_referer( 'po-sales-order', 'security' );

		if ( empty( $_POST['order_id'] ) ) {
			wp_send_json_error( __( 'Invalid sales order number', ATUM_PO_TEXT_DOMAIN ) );
		}

		$order = wc_get_order( absint( $_POST['order_id'] ) );

		if ( ! $order instanceof \WC_Order ) {
			wp_send_json_error( __( 'Sales order not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		$customer      = new \WC_Customer( $order->get_customer_id() );
		$customer_data = array(
			'name' => $customer->get_display_name(),
		);

		// Check if we have to import the customer shipping address too.
		if ( 'yes' === AtumHelpers::get_option( 'po_copy_shipping_address', 'no' ) ) {

			$state   = $order->get_shipping_state();
			$country = $order->get_shipping_country();

			if ( $state && $country ) {
				$state = WC()->countries->get_states( $country )[ $state ] ?? $state;
			}

			$customer_data['state']    = $state;
			$customer_data['country']  = $country;
			$customer_data['city']     = $order->get_shipping_city();
			$customer_data['address']  = $order->get_shipping_address_1();
			$customer_data['address2'] = $order->get_shipping_address_2();
			$customer_data['postcode'] = $order->get_shipping_postcode();

		}

		wp_send_json_success( $customer_data );

	}

	/**
	 * Check PO's order items before changing PO status.
	 *
	 * @since 0.9.9
	 *
	 * @package Bulk Actions
	 */
	/*public function check_po_items() {
		check_ajax_referer( 'po-check-items-nonce', 'security' );

		$polist = [
			'added'    => [],
			'received' => [],
			'stocked'  => [],
		];
		$modes  = array_map( 'wc_clean', $_POST['modes'] );
		$pos    = array_map( 'absint', $_POST['orders'] );
		$data   = [
			'message' => '',
			'orders'  => [],
		];

		$pos_messages = [];

		// Retrieve every POs that don't accomplishes the conditions.
		foreach ( $pos as $po_id ) {
			$po    = AtumHelpers::get_atum_order_model( $po_id, TRUE );
			$items = $po->get_items();

			if ( empty( $items ) ) {
				$polist['added'][] = $po_id;
			}
			else {
				$remaining_rcv_items = $remaining_stk_items = array_keys( $items );
				$deliveries          = Deliveries::get_po_orders( $po->get_id() );
				$received_items      = [];
				$stocked_items       = [];

				foreach ( $deliveries as $delivery ) {
					$delivery_items = $delivery->get_items();
					foreach ( $delivery_items as $delivery_item ) {
						$received_items[] = $delivery_item->get_po_item_id();
						if ( 'yes' === $delivery_item->get_stock_changed() ) {
							$stocked_items[] = $delivery_item->get_po_item_id();
						}
					}
				}
				$remaining_rcv_items = array_diff( $remaining_rcv_items, $received_items );
				$remaining_stk_items = array_diff( $remaining_stk_items, $stocked_items );
				if ( ! empty( $remaining_rcv_items ) ) {
					$polist['received'][] = $po_id;
				}
				if ( ! empty( $remaining_stk_items ) ) {
					$polist['stocked'][] = $po_id;
				}
			}
		}

		// Select the priority mode.
		foreach ( [ 'added', 'received', 'stocked' ] as $mode ) {
			if ( empty( $polist[ $mode ] ) || FALSE === in_array( $mode, $modes ) ) {
				continue;
			}
			foreach ( $polist[ $mode ] as $po_id ) {
				$data['orders'][] = $po_id;
				$pos_messages[]   = '<a href="' . get_edit_post_link( $po_id ) . '" target="_blank">' . $po_id . '</a>';
			}
			break;
		}

		// Set the message.
		switch ( $mode ) {
			case 'added':
				$data['message'] = sprintf(
					_n( "There is a PO that hasn't any item added: %s", "There are some POs that haven't any item added: %s", count( $pos_messages ), ATUM_PO_TEXT_DOMAIN ),
					implode( ', ', $pos_messages )
				);
				break;
			case 'received':
				$data['message'] = sprintf(
					_n( 'There is a PO that is missing items to receive : %s', 'There are some POs that are missing items to receive: %s', count( $pos_messages ), ATUM_PO_TEXT_DOMAIN ),
					implode( ', ', $pos_messages )
				);
				break;
			case 'stocked':
				$data['message'] = sprintf(
					_n( 'There is a PO whose items have not been added to stock : %s', 'There are some POs whose items have not been added to stock : %s', count( $pos_messages ), ATUM_PO_TEXT_DOMAIN ),
					implode( ', ', $pos_messages )
				);
				break;
		}

		if ( empty( $data['orders'] ) ) {
			wp_send_json_success( $data );
		}
		else {
			wp_send_json_error( $data );
		}

	}*/

	/**
	 * Loads the POs ListTable class and calls ajax_response method
	 *
	 * @package POs List Table
	 *
	 * @since 0.9.12
	 */
	public function fetch_pos_list() {

		check_ajax_referer( 'atum-list-table-nonce', 'security' );

		$args = array(
			'per_page' => ! empty( $_REQUEST['per_page'] ) ? absint( $_REQUEST['per_page'] ) : AtumHelpers::get_option( 'po_list_posts_per_page', AtumSettings::DEFAULT_POSTS_PER_PAGE ),
			'show_cb'  => ! empty( $_REQUEST['show_cb'] ) ? (bool) $_REQUEST['show_cb'] : FALSE,
			'screen'   => esc_attr( $_REQUEST['screen'] ),
		);

		do_action( 'atum/purchase_orders_pro/ajax/pos_list/before_fetch_list' );

		if ( ! empty( $_REQUEST['view'] ) && 'all' === $_REQUEST['view'] ) {
			$_REQUEST['view'] = '';
		}

		/**
		 * Variable definition
		 *
		 * @var ListTable $list
		 */
		$list = new ListTable( $args );
		$list->ajax_response();

	}

	/**
	 * Search a PO.
	 *
	 * @since 0.9.12
	 *
	 * @package Merge POs
	 */
	public function search_po() {
		check_ajax_referer( 'search-po', 'security' );

		if ( empty( $_GET['term'] ) ) {
			wp_die();
		}

		$term = stripslashes( $_GET['term'] );

		if ( empty( $term ) ) {
			wp_die();
		}

		global $wpdb;

		$like_term = '%' . $wpdb->esc_like( $term ) . '%';
		$post_type = PurchaseOrders::POST_TYPE;

		$query = "
			SELECT p.ID FROM $wpdb->posts p
			LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_number'
			WHERE CONCAT( p.ID, '~', IFNULL( pm.meta_value, '' ) ) LIKE '$like_term' AND p.post_type = '$post_type'
			ORDER BY IFNULL(pm.meta_value,p.ID) ASC
		";

		$pos_ids = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids     = wp_parse_id_list( $pos_ids );
		$orders  = array();

		foreach ( $ids as $po_id ) {

			/**
			 * Variable definition.
			 *
			 * @var PurchaseOrder $purchase_order
			 */
			$purchase_order = AtumHelpers::get_atum_order_model( $po_id, FALSE, PurchaseOrders::POST_TYPE );

			if ( ! is_wp_error( $purchase_order ) ) {
				$orders[ $po_id ] = apply_filters( 'atum/purchase_orders_pro/ajax/search_po', array(
					'id'          => $po_id,
					'name'        => ! empty( $purchase_order->number ) ? $purchase_order->number : '#' . $po_id,
					'supplier'    => $purchase_order->get_supplier( 'id' ) ? $purchase_order->get_supplier()->name : __( 'Multiple suppliers', ATUM_PO_TEXT_DOMAIN ),
					'supplier_id' => $purchase_order->get_supplier( 'id' ),
				), $purchase_order );
			}

		}

		wp_send_json( apply_filters( 'atum/purchase_orders_pro/ajax/json_search_found_pos', $orders ) );
	}

	/**
	 * Merge a PO with another.
	 *
	 * @since 0.9.11
	 *
	 * @package Merge POs
	 */
	public function merge_purchase_orders() {

		check_ajax_referer( 'po-merge-nonce', 'security' );

		if ( empty( $_POST['po_id'] ) || empty( $_POST['merge_id'] ) || empty( $_POST['config'] ) || ! is_array( $_POST['config'] ) ) {
			wp_send_json_error( __( 'Invalid data.', ATUM_PO_TEXT_DOMAIN ) );
		}

		$target_po_id = absint( $_POST['po_id'] );
		$source_po_id = absint( $_POST['merge_id'] );
		$settings     = array_filter( $_POST['config'], 'wc_bool_to_string' );

		$defaults = [
			'comments'      => 'yes',
			'deliveries'    => 'yes',
			'files'         => 'yes',
			'info'          => 'yes',
			'invoices'      => 'yes',
			'items'         => 'yes',
			'replace_items' => 'no',
		];

		$settings = array_merge( $defaults, $settings );

		$target_po = AtumHelpers::get_atum_order_model( $target_po_id, TRUE, PurchaseOrders::POST_TYPE );
		$source_po = AtumHelpers::get_atum_order_model( $source_po_id, TRUE, PurchaseOrders::POST_TYPE );

		if ( $target_po_id === $source_po_id ) {
			wp_send_json_error( __( 'Can not merge a PO with itself.', ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( $source_po->get_supplier( 'id' ) && $source_po->get_supplier( 'id' ) !== $target_po->get_supplier( 'id' ) ) {
			wp_send_json_error( __( "The target PO's supplier doesn't match with the current PO's supplier.", ATUM_PO_TEXT_DOMAIN ) );
		}

		$merge = new MergePO( $source_po, $target_po );

		$merge->merge_data( $settings );

		do_action( 'atum/purchase_orders_pro/after_merge_po', $target_po, $source_po, $settings );

		wp_send_json_success( __( 'The POs were successfully merged.', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Save the resizable meta boxes' sizes.
	 *
	 * @since 0.9.12
	 *
	 * @package PO Meta Boxes Resizing
	 */
	public function meta_box_sizing() {

		check_ajax_referer( 'po-meta-box-resizing-nonce', 'security' );

		if ( empty( $_POST['po_id'] ) || empty( $_POST['sizes'] ) || ! is_array( $_POST['sizes'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po_id'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( 'PO not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Save the sizes to the PO.
		$sizes = array_map( 'esc_attr', $_POST['sizes'] );
		$po->set_meta_box_sizes( $sizes );
		$po->save_meta();

		wp_send_json_success();

	}

	/**
	 * Auto-save POs through Ajax
	 *
	 * @since 0.9.20
	 *
	 * @package Auto-saver modal
	 */
	public function auto_save_po() {

		check_ajax_referer( 'po-auto-save-nonce', 'security' );

		if ( empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Add all the form data to the global $_POST, so it can be processed as if the form was submitted.
		parse_str( $_POST['po'], $_POST );
		$_REQUEST = $_POST;

		global $action;
		$action = $_POST['action'];

		edit_post();

		wp_send_json_success( __( 'PO saved successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Convert requisitioner statuses to normal statuses after disabling the requisitioner requisition
	 *
	 * @since 0.9.23
	 *
	 * @package PO Settings page
	 */
	public function convert_requisitioner_statuses() {

		check_ajax_referer( 'po-settings-nonce', 'security' );

		global $wpdb;

		$wpdb->query( $wpdb->prepare( "
			UPDATE $wpdb->posts
			SET post_status = 'atum_new'
			WHERE post_type = %s AND post_status IN ('atum_approval', 'atum_approved')
		", PurchaseOrders::POST_TYPE ) );

		wp_send_json_success();

	}

	/**
	 * Reload PO items after importing WC Sale Order items.
	 *
	 * @since 0.9.27
	 *
	 * @package PO Items
	 */
	public function reload_po_items() {

		check_ajax_referer( 'atum-order-item', 'security' );

		$po_id      = absint( $_POST['po_id'] );
		$atum_order = AtumHelpers::get_atum_order_model( $po_id, TRUE, PurchaseOrders::get_post_type() );

		AtumHelpers::load_view( ATUM_PO_PATH . 'views/meta-boxes/po-items/items', compact( 'atum_order' ) );

	}

	/**
	 * Display PO email preview.
	 *
	 * @since 0.9.27
	 *
	 * @package PO Email
	 */
	public function po_email_preview() {

		check_ajax_referer( 'po-email-preview', 'nonce' );

		// Preview template from PO email modal.
		if ( isset( $_POST['po_id'] ) ) {
			$subject       = sanitize_text_field( $_POST['subject'] );
			$body          = str_replace( 'data-mce-style', 'style', wp_kses_post( urldecode( $_POST['body'] ) ) );
			$template      = sanitize_text_field( $_POST['template'] );
			$po_id         = absint( $_POST['po_id'] );
			$po            = AtumHelpers::get_atum_order_model( $po_id, TRUE, PurchaseOrders::POST_TYPE );
			$company_name  = $po->purchaser_name ?? __( 'The Company', ATUM_PO_TEXT_DOMAIN );
			$primary_color = AtumHelpers::get_option( 'po_default_emails_template_color', '#00B8DB' );
			$logo_id       = AtumHelpers::get_option( 'po_default_emails_template_logo', '' );
		}
		// Preview template from settings page.
		else {
			/* translators: the PO number */
			$subject       = sprintf( __( 'Purchase Order Request - #%s', ATUM_PO_TEXT_DOMAIN ), 'PO-XXX' );
			$template      = $_POST['template'] ? sanitize_text_field( $_POST['template'] ) : AtumHelpers::get_option( 'po_default_emails_template', 'default' );
			$company_name  = __( 'The Company', ATUM_PO_TEXT_DOMAIN );
			$footer        = str_replace( 'data-mce-style', 'style', wp_kses_post( urldecode( $_POST['footer'] ) ) );
			$primary_color = $_POST['color'] ? sanitize_text_field( $_POST['color'] ) : AtumHelpers::get_option( 'po_default_emails_template_color', '#00B8DB' );
			$logo_id       = $_POST['logo'] ? sanitize_text_field( $_POST['logo'] ) : AtumHelpers::get_option( 'po_default_emails_template_logo', '' );
			$body          = str_replace( 'data-mce-style', 'style', wp_kses_post( urldecode( $_POST['body'] ) ) );
		}

		if ( ! strlen( $body ) ) {
			$body = '
				<p>
				Lorem ipsum dolor sit amet, consectetur adipiscing elit. Praesent in mi suscipit, cursus enim vel, auctor nisi.
			    Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Maecenas in eros
			    tristique, pellentesque nunc et, condimentum turpis. Sed cursus mauris ut sapien ultricies, ut placerat leo
			    consequat. Suspendisse faucibus dolor massa, sit amet accumsan eros cursus nec. Cras sit amet cursus dui.
			    Quisque eget posuere urna. Donec sit amet felis id arcu pretium placerat ut nec felis.
				</p>
				<p>
				Nullam sit amet lectus quis metus iaculis iaculis. Duis dictum posuere justo sit amet ultricies.
				Curabitur cursus elementum venenatis. Suspendisse porta posuere lacus vel vestibulum. Nam feugiat feugiat nunc
				gravida egestas. Sed quis elit magna. Vestibulum sed rhoncus erat, ullamcorper vulputate nisl.
				</p>
				<p>
				Pellentesque rutrum porttitor suscipit. Phasellus hendrerit elit et luctus tincidunt. Morbi eu diam interdum,
				pharetra dolor mollis, tristique nisi. Fusce ut magna facilisis, tristique metus eget, dictum magna. Praesent
				urna diam, pharetra quis mi sit amet, condimentum mollis urna. Nulla porttitor elementum risus ut elementum.
				Pellentesque dictum congue faucibus. Vestibulum id risus mi.
				</p>
			';
		}

		if ( ! strlen( $footer ) ) {
			/* translators: first is the current year and second is the company name */
			$footer = AtumHelpers::get_option( 'po_default_emails_footer', sprintf( __( '&copy;%1$d %2$s. All rights reserved.', ATUM_PO_TEXT_DOMAIN ), date_i18n( 'Y' ), AtumHelpers::get_option( 'company_name', '' ) ) );
		}

		$logo = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

		AtumHelpers::load_view( ATUM_PO_PATH . "views/email-templates/$template/body", compact( 'body', 'subject', 'footer', 'logo', 'company_name', 'primary_color' ) );

	}
	
	/**
	 * Display PO PDF preview.
	 *
	 * @since 0.9.27
	 */
	public function po_pdf_preview() {

		check_ajax_referer( 'po-pdf-preview', 'nonce' );

		$default_template_fields = array(
			'options' => array(
				'ship_via'       => 'yes',
				'fob'            => 'yes',
				'requisitioner'  => 'yes',
				'delivery_terms' => 'yes',
				'description'    => 'yes',
			),
		);

		$pdf_template    = $_POST['template'] ? sanitize_text_field( $_POST['template'] ) : AtumHelpers::get_option( 'po_default_pdf_template', 'default' );
		$template_color  = $_POST['color'] ? sanitize_text_field( $_POST['color'] ) : AtumHelpers::get_option( 'po_default_emails_template_color', '#00B8DB' );
		$template_fields = isset( $_POST['atum_settings'], $_POST['atum_settings']['po_pdf_template_fields'] ) ?
			$_POST['atum_settings']['po_pdf_template_fields'] : AtumHelpers::get_option( 'po_pdf_template_fields', $default_template_fields );

		$po = new POExtendedPreview( [
			'template_fields' => $template_fields,
			'template'        => $pdf_template,
			'template_color'  => $template_color,
		] );

		$po->generate();

	}

	/**
	 * Returns the preview for the next PO number
	 *
	 * @since 1.0.0
	 *
	 * @package PO Settings page
	 */
	public function preview_next_po_number() {

		check_ajax_referer( 'po-settings-nonce', 'security' );

		if ( ! isset( $_POST['pattern'], $_POST['counter'], $_POST['padding_zeros'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		wp_send_json_success( Helpers::get_next_po_number( $_POST['pattern'], $_POST['counter'], $_POST['padding_zeros'] ) );

	}

	/**
	 * Save a PO number edited manually
	 *
	 * @since 1.0.1
	 *
	 * @package PO Number Editor
	 */
	public function save_po_number() {

		check_ajax_referer( 'po-edit-po-number-nonce', 'security' );

		if ( empty( $_POST['po'] ) ) {
			wp_send_json_error( __( "The PO number couldn't be saved. PO not found.", ATUM_PO_TEXT_DOMAIN ) );
		}

		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( "The PO number couldn't be saved. PO not found.", ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( empty( $_POST['number'] ) ) {
			wp_send_json_error( __( 'The PO number is required.', ATUM_PO_TEXT_DOMAIN ) );
		}

		// The PO number must be unique.
		$po_number           = sanitize_text_field( $_POST['number'] );
		$existing_po_numbers = Helpers::find_po_number( $po_number, $po->get_id() );

		if ( ! empty( $existing_po_numbers ) ) {
			wp_send_json_error( __( 'The number you have entered is already taken by another PO and it must be unique.<br>Please, try with a distinct one or auto-generate it.', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po->set_number( $po_number );
		$po->save_meta();

		wp_send_json_success( $po_number );

	}

	/**
	 * Autogenerate the next available PO number and save it.
	 *
	 * @since 1.0.1
	 *
	 * @package PO Number Editor
	 */
	public function autogenerate_po_number() {

		check_ajax_referer( 'po-edit-po-number-nonce', 'security' );

		if ( empty( $_POST['po'] ) ) {
			wp_send_json_error( __( "The PO number couldn't be generated. PO not found.", ATUM_PO_TEXT_DOMAIN ) );
		}

		/**
		 * Variable definition
		 *
		 * @var POExtended $po
		 */
		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( "The PO number couldn't be generated. PO not found.", ATUM_PO_TEXT_DOMAIN ) );
		}

		$next_po_number = $po->generate_next_po_number();

		if ( is_wp_error( $next_po_number ) ) {
			wp_send_json_error( $next_po_number->get_error_message() );
		}

		$po->set_number( $next_po_number );
		$po->save_meta();

		wp_send_json_success( $next_po_number );

	}

	/**
	 * Save the PO's screen options.
	 *
	 * @since 1.0.3
	 *
	 * @package PO Screen Options
	 */
	public function save_po_screen_options() {

		check_ajax_referer( 'po-screen-options-nonce', 'security' );

		if ( empty( $_POST['po'] ) || empty( $_POST['field_name'] ) || empty( $_POST['value'] ) ) {
			wp_send_json_error( __( 'Invalid Data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$field_name = esc_attr( $_POST['field_name'] );

		if ( '_' !== substr( $field_name, 0, 1 ) ) {
			$field_name = "_$field_name";
		}

		$value = sanitize_text_field( $_POST['value'] );

		// There is no need to make use of the POExtended model for these options, just save them as hidden meta.
		update_post_meta( absint( $_POST['po'] ), $field_name, $value );
		wp_send_json_success();

	}

	/**
	 * Tool to update purchase prices taxes from ATUM tools
	 *
	 * @since 1.0.3
	 *
	 * @package ATUM tools
	 */
	public function update_purchase_prices_tool() {

		check_ajax_referer( 'atum-script-runner-nonce', 'security' );

		if ( ! isset( $_POST['option'], $_POST['option']['action'], $_POST['option']['percentage'] ) ) {
			wp_send_json_error( __( 'Invalid data', ATUM_PO_TEXT_DOMAIN ) );
		}

		$percentage = floatval( $_POST['option']['percentage'] );
		$action     = esc_attr( $_POST['option']['action'] );

		if ( $percentage <= 0 ) {
			wp_send_json_error( __( 'The percentage must be greater than 0 and equal or lower than 100', ATUM_PO_TEXT_DOMAIN ) );
		}

		global $wpdb;
		$atum_product_data_table = $wpdb->prefix . AtumGlobals::ATUM_PRODUCT_DATA_TABLE;
		$decimal_tax             = 1 + ( $percentage / 100 );

		if ( 'add' === $action ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "
				UPDATE $atum_product_data_table apd
				SET apd.purchase_price = ROUND( (apd.purchase_price * $decimal_tax), 2 )
				WHERE apd.purchase_price > 0 AND apd.purchase_price IS NOT NULL
			" );
			// phpcs:enable

		}
		elseif ( 'deduct' === $action ) {

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "
				UPDATE $atum_product_data_table apd
				SET apd.purchase_price = ROUND( (apd.purchase_price / $decimal_tax), 2 )
				WHERE apd.purchase_price > 0 AND apd.purchase_price IS NOT NULL
			" );
			// phpcs:enable

		}

		do_action( 'atum/purchase_orders_pro/after_update_purchase_prices', $action, $decimal_tax );

		wp_send_json_success( __( 'All the purchase prices were updated successfully', ATUM_PO_TEXT_DOMAIN ) );

	}

	/**
	 * Get the PO preview modal content
	 *
	 * @since 1.0.4
	 *
	 * @package POs List Table
	 */
	public function get_po_preview() {

		check_ajax_referer( 'po-preview-nonce', 'security' );

		if ( empty( $_POST['po_id'] ) ) {
			wp_send_json_error( __( 'PO not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po_id = absint( $_POST['po_id'] );
		$po    = AtumHelpers::get_atum_order_model( $po_id, FALSE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( 'PO not found', ATUM_PO_TEXT_DOMAIN ) );
		}

		$statuses      = Globals::get_statuses();
		$status_colors = Globals::get_status_colors();

		$view_args       = array(
			'atum_order'    => $po,
			'po_status'     => $po->status,
			'statuses'      => $statuses,
			'status_colors' => $status_colors,
			'status_color'  => $status_colors[ $po->status ] ?? 'transparent',
			'status_label'  => $statuses[ $po->status ] ?? __( 'Unknown', ATUM_PO_TEXT_DOMAIN ),
		);
		$status_dropdown = AtumHelpers::load_view_to_string( ATUM_PO_PATH . 'views/meta-boxes/po-data/po-status-dropdown', $view_args );

		wp_send_json_success( array(
			'preview' => '<iframe src="' . PurchaseOrders::get_pdf_generation_link( $po_id, TRUE ) . '"></iframe>',
			'actions' => '
				<div class="atum-modal-actions">
					<button type="button" class="btn btn-sm btn-primary clone-po atum-tooltip" title="' . esc_attr__( 'Clone PO', ATUM_PO_TEXT_DOMAIN ) . '"><i class="atum-icon atmi-duplicate"></i></button>
					<button type="button" class="btn btn-sm btn-danger archive-po atum-tooltip" title="' . esc_attr__( 'Archive PO', ATUM_PO_TEXT_DOMAIN ) . '"><i class="atum-icon atmi-trash"></i></button>
					<button type="button" class="btn btn-sm btn-success print-po atum-tooltip" title="' . esc_attr__( 'Print PO', ATUM_PO_TEXT_DOMAIN ) . '"><i class="atum-icon atmi-printer"></i></button>' .
					$status_dropdown . '
				</div>
			',
		) );

	}

	/**
	 * Create a Returning PO from any received PO
	 *
	 * @since 1.1.3
	 *
	 * @package Create Returning PO button
	 */
	public function create_returning_po() {

		check_ajax_referer( 'po-return-po-nonce', 'security' );

		if ( empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Missing PO ID', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( "The PO doesn't exist", ATUM_PO_TEXT_DOMAIN ) );
		}

		$returning_po = ReturningPOs::create_returning_po( $po );

		if ( is_wp_error( $returning_po ) ) {
			wp_send_json_error( $returning_po->get_error_message() );
		}

		wp_send_json_success( array(
			/* translators: the returning PO number */
			'msg' => sprintf( __( 'The Returning PO "%s" was created successfully.<br>Click "OK" to open it on a new tab.', ATUM_PO_TEXT_DOMAIN ), $returning_po->number ),
			'url' => rawurlencode( get_edit_post_link( $returning_po->get_id(), '' ) ),
		) );

	}

	/**
	 * Return PO items to the supplier (discount the stocks automatically).
	 *
	 * @since 1.1.3
	 *
	 * @package Return button
	 */
	public function return_po_items() {

		check_ajax_referer( 'po-return-po-nonce', 'security' );

		if ( empty( $_POST['po'] ) ) {
			wp_send_json_error( __( 'Missing PO ID', ATUM_PO_TEXT_DOMAIN ) );
		}

		$po = AtumHelpers::get_atum_order_model( absint( $_POST['po'] ), TRUE, PurchaseOrders::POST_TYPE );

		if ( ! $po->exists() ) {
			wp_send_json_error( __( "The PO doesn't exist", ATUM_PO_TEXT_DOMAIN ) );
		}

		$po->set_status( 'atum_returned' );
		$po->save();

		wp_send_json_success( __( 'The items were returned successfully', ATUM_PO_TEXT_DOMAIN ) );

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
	 * @return Ajax instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}
