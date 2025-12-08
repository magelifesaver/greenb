<?php
/**
 * Class for adding barcodes to documents (PDFs and emails)
 *
 * @package     AtumBarcodes\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.2.4
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Models\POItem;
use Atum\PurchaseOrders\Models\PurchaseOrder;


class Documents {

	/**
	 * The singleton instance holder
	 *
	 * @var Documents
	 */
	private static $instance;

	/**
	 * To store the option configured for WC order emails
	 *
	 * @var string
	 */
	private $wc_order_emails_option;

	/**
	 * To store the option configured for PO PDFs
	 *
	 * @var string
	 */
	private $po_pdfs_option;


	/**
	 * Documents singleton constructor
	 *
	 * @since 0.2.4
	 */
	private function __construct() {

		$this->wc_order_emails_option = AtumHelpers::get_option( 'bp_shop_order_emails', 'items_and_orders' );
		$this->po_pdfs_option         = AtumHelpers::get_option( 'bp_po_pdfs', 'items_and_orders' );

		// Add the hooks for the WC Orders emails.
		if ( 'no' !== $this->wc_order_emails_option ) {

			if ( 'items_only' !== $this->wc_order_emails_option ) {
				add_action( 'woocommerce_email_before_order_table', array( $this, 'add_barcode_to_document_header' ), 100, 4 );

				/* PDF Invoices & Packing Slips support */
				add_action( 'wpo_wcpdf_after_item_meta', array( $this, 'add_barcode_to_pdf_invoice_items' ), 10, 3 );
			}

			if ( 'orders_only' !== $this->wc_order_emails_option ) {
				add_action( 'woocommerce_order_item_meta_start', array( $this, 'add_barcode_to_document_items' ), 10, 4 );

				/* PDF Invoices & Packing Slips support */
				add_action( 'wpo_wcpdf_after_order_data', array( $this, 'add_barcode_to_pdf_invoice' ), 10, 2 );
			}

		}

		// Add the hooks for the PO PDFs.
		if ( 'no' !== $this->po_pdfs_option ) {

			if ( 'items_only' !== $this->po_pdfs_option ) {
				add_action( 'atum/atum_order/po_report/after_header', array( $this, 'add_barcode_to_document_header' ), 100 );
				add_action( 'atum/purchase_orders_pro/po_report/after_header', array( $this, 'add_barcode_to_document_header' ), 100 );
			}

			if ( 'orders_only' !== $this->po_pdfs_option ) {
				add_action( 'atum/purchase_orders_pro/po_report/after_item_product', array( $this, 'add_barcode_to_document_items' ), 10, 3 );
			}

		}

	}

	/**
	 * Add the ATUM barcode to document headers
	 *
	 * @since 0.2.4
	 *
	 * @param \WC_Order|PurchaseOrder $order
	 * @param bool                    $sent_to_admin
	 * @param bool                    $plain_text
	 * @param string                  $email
	 */
	public function add_barcode_to_document_header( $order, $sent_to_admin = NULL, $plain_text = NULL, $email = NULL ) {

		if ( apply_filters( 'atum/barcodes_pro/bypass_order_barcode', FALSE, $order, $sent_to_admin, $email ) ) {
			return;
		}

		$barcode         = $order->get_meta( Globals::ATUM_BARCODE_META_KEY );
		$default_barcode = '';

		if ( ! $barcode && 'yes' === AtumHelpers::get_option( 'bp_orders_barcodes', 'yes' ) ) {
			$default_barcode = $order->get_id();
		}

		if ( $barcode || $default_barcode ) {

			$barcode_img = Helpers::generate_barcode( $barcode ?: $default_barcode, apply_filters( 'atum/barcodes_pro/document_header_barcode_options', [], $order ) );

			if ( $barcode_img && ! is_wp_error( $barcode_img ) ) {
				echo '<br>' . $barcode_img; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

		}

	}

	/**
	 * Add the ATUM barcode to document items
	 *
	 * @since 0.2.4
	 *
	 * @param int                     $item_id
	 * @param \WC_Order_Item|POItem   $item
	 * @param \WC_Order|PurchaseOrder $order
	 * @param string                  $plain_text
	 */
	public function add_barcode_to_document_items( $item_id, $item, $order, $plain_text = NULL ) {

		if ( apply_filters( 'atum/barcodes_pro/bypass_order_item_barcode', FALSE, $item, $order ) ) {
			return;
		}

		if ( $item instanceof \WC_Order_Item_Product || $item instanceof POItemProduct ) {

			$product_id = $item->get_variation_id() ?: $item->get_product_id();
			$product    = AtumHelpers::get_atum_product( $product_id, TRUE );

			if ( $product instanceof \WC_Product && ! apply_filters( 'atum/barcodes_pro/bypass_document_item_barcode', FALSE, $product, $item, $order ) ) {

				$barcode      = $product->get_barcode();
				$barcode_type = $product->get_barcode_type();

				$barcode_img = Helpers::generate_barcode( $barcode, apply_filters( 'atum/barcodes_pro/document_item_barcode_options', [ 'type' => $barcode_type ], $item, $order ) );

				if ( $barcode_img && ! is_wp_error( $barcode_img ) ) {
					echo '<br>' . $barcode_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

			}

		}

	}

	/**
	 * Set the barcode format to PNG
	 *
	 * @since 0.2.6
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function set_barcode_format( $options ) {
		$options['format'] = 'png';

		return $options;
	}

	/**
	 * Add barcode to PDF invoices
	 *
	 * @since 0.2.6
	 *
	 * @supports PDF Invoices & Packing Slips support
	 *
	 * @param string    $type
	 * @param \WC_Order $order
	 */
	public function add_barcode_to_pdf_invoice( $type, $order ) {

		// SVG are not supported by the domPDF library used here.
		add_filter( 'atum/barcodes_pro/document_header_barcode_options', array( $this, 'set_barcode_format' ), 100 );
		'<tr><td colspan="2">' . $this->add_barcode_to_document_header( $order ) . '</td></tr>';
		remove_filter( 'atum/barcodes_pro/document_header_barcode_options', array( $this, 'set_barcode_format' ), 100 );

	}

	/**
	 * Add barcode to PDF invoice items
	 *
	 * @since 0.2.6
	 *
	 * @supports PDF Invoices & Packing Slips support
	 *
	 * @param string    $type
	 * @param array     $item_data
	 * @param \WC_Order $order
	 */
	public function add_barcode_to_pdf_invoice_items( $type, $item_data, $order ) {

		$item = new \WC_Order_Item_Product( $item_data['item_id'] );

		// SVG are not supported by the domPDF library used here.
		add_filter( 'atum/barcodes_pro/document_item_barcode_options', array( $this, 'set_barcode_format' ), 100 );
		$this->add_barcode_to_document_items( $item_data['item_id'], $item, $order );
		remove_filter( 'atum/barcodes_pro/document_item_barcode_options', array( $this, 'set_barcode_format' ), 100 );

	}


	/*******************
	 * Instance methods
	 *******************/

	/**
	 * Cannot be cloned
	 */
	public function __clone() {

		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Cannot be serialized
	 */
	public function __sleep() {
		_doing_it_wrong( __FUNCTION__, esc_attr__( 'Cheatin&#8217; huh?', ATUM_BARCODES_TEXT_DOMAIN ), '1.0.0' );
	}

	/**
	 * Get Singleton instance
	 *
	 * @return Documents instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
