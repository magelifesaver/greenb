<?php
/**
 * Class responsible of Merging two POs
 *
 * @since       0.9.18
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Inc
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\AtumComments;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Deliveries\Items\DeliveryItemProductInventory;
use AtumPO\Deliveries\Models\Delivery;
use AtumPO\Invoices\Invoices;
use AtumPO\Invoices\Items\InvoiceItemProduct;
use AtumPO\Invoices\Models\Invoice;
use AtumPO\Models\POExtended;

class MergePO {

	/**
	 * The original PO (to be merged with the target)
	 *
	 * @var POExtended
	 */
	private $source_po;

	/**
	 * The target PO that will receive the merged data
	 *
	 * @var POExtended
	 */
	private $target_po;

	/**
	 * MergePO constructor
	 *
	 * @since 0.9.18
	 *
	 * @param POExtended $source_po
	 * @param POExtended $target_po
	 */
	public function __construct( $source_po, $target_po ) {

		$this->source_po = $source_po;
		$this->target_po = $target_po;

	}

	/**
	 * Merge the POs
	 *
	 * @since 0.9.18
	 *
	 * @param array $settings {
	 *  The array of settings to merge.
	 *
	 *  @type string $comments      yes or no.
	 *  @type string $deliveries    yes or no.
	 *  @type string $files         yes or no.
	 *  @type string $info          yes or no.
	 *  @type string $invoices      yes or no.
	 *  @type string $items         yes or no.
	 *  @type string $replace_items yes or no.
	 * }
	 *
	 * @return POExtended|void The merged PO or nothing.
	 */
	public function merge_data( $settings ) {

		$source_po_id = $this->source_po->get_id();
		$target_po_id = $this->target_po->get_id();

		// The POs must be distinct and must exist.
		if ( ! $source_po_id || ! $target_po_id || $source_po_id === $target_po_id ) {
			return;
		}

		// Import PO data.
		if ( isset( $settings['info'] ) && 'yes' === $settings['info'] ) {
			$status = $settings['status'] ?? FALSE;
			$this->import_po_data( $status );
		}

		// Import PO items.
		if ( isset( $settings['items'] ) && 'yes' === $settings['items'] ) {
			$this->import_po_items( isset( $settings['replace_items'] ) && 'yes' === $settings['replace_items'] );
		}

		// Import deliveries.
		if ( isset( $settings['deliveries'] ) && 'yes' === $settings['deliveries'] ) {
			$this->import_deliveries();
		}

		// Import invoices.
		if ( isset( $settings['invoices'] ) && 'yes' === $settings['invoices'] ) {
			$this->import_invoices();
		}

		// Import files.
		if ( isset( $settings['files'] ) && 'yes' === $settings['files'] ) {
			$this->import_files();
		}

		// Import comments.
		if ( isset( $settings['comments'] ) && 'yes' === $settings['comments'] ) {
			$this->import_comments();
		}

		// Return the merged PO.
		return $this->target_po;

	}

	/**
	 * Import the PO data from the source PO
	 *
	 * @param false|string $status
	 *
	 * @since 0.9.18
	 */
	public function import_po_data( $status = FALSE ) {

		$data = array_merge( $this->source_po->get_meta(), $this->source_po->get_data() );
		unset( $data['id'], $data['number'], $data['title'], $data['files'], $data['status'], $data['date_created'] );

		$this->target_po->set_props( $data );

		$status = $status ?: $this->source_po->get_status();
		$this->target_po->update_status( $status );

		// Re-instance target PO.
		$this->target_po = AtumHelpers::get_atum_order_model( $this->target_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

	}

	/**
	 * Import PO items from the source PO
	 *
	 * @since 0.9.18
	 *
	 * @param bool $replace_items Optional. Whether to replace all the items from the target PO. By default won't replace them.
	 */
	public function import_po_items( $replace_items = FALSE ) {

		$po_items = $this->target_po->get_items( [ 'line_item', 'fee', 'shipping' ] );

		// Remove previous items when replacing (if any).
		if ( $replace_items && ! empty( $po_items ) ) {
			foreach ( $po_items as $po_item ) {
				$po_item->delete( TRUE );
			}
		}

		$merged_items = $this->source_po->get_items( [ 'line_item', 'fee', 'shipping' ] );

		foreach ( $merged_items as $merged_item ) {

			switch ( $merged_item->get_type() ) {
				case 'line_item':
					$merged_product = $merged_item->get_variation_id() ?: $merged_item->get_product_id();
					break;
				default:
					$merged_name = $merged_item->get_name();
					break;
			}
			$found = FALSE;

			if ( ! $replace_items ) {

				// When merging (not replacing), try to find out the items on the target PO to merge data first.
				foreach ( $po_items as $po_item ) {

					if ( $merged_item->get_type() !== $po_item->get_type() ) {
						continue;
					}

					if ( 'line_item' === $po_item->get_type() ) {
						/**
						 * Variable definition
						 *
						 * @var POItemProduct $po_item
						 */
						$item_product = $po_item->get_variation_id() ?: $po_item->get_product_id();

						if ( $merged_product === $item_product ) {

							$found = TRUE;
							$po_item->set_props( array(
								'atum_order_id' => $this->target_po->get_id(),
								'product_id'    => $merged_item->get_product_id(),
								'variation_id'  => $merged_item->get_variation_id(),
								'quantity'      => (float) $merged_item->get_quantity() + (float) $po_item->get_quantity(),
								'tax_class'     => $merged_item->get_tax_class(),
								'subtotal'      => (float) $merged_item->get_subtotal() + (float) $po_item->get_subtotal(),
								'subtotal_tax'  => $merged_item->get_subtotal_tax(),
								'total'         => (float) $merged_item->get_total() + (float) $po_item->get_total(),
								'total_tax'     => $merged_item->get_total_tax(),
								'taxes'         => $merged_item->get_taxes(),
							) );
							$po_item->save();

							$meta_data = $this->get_item_external_meta( $merged_item );
							$this->target_po->add_bulk_item_data( $po_item, $meta_data );

							$extra_data = array();

							$discount_config = $merged_item->get_meta( '_discount_config', TRUE );
							$tax_config      = $merged_item->get_meta( '_tax_config', TRUE );

							if ( ! empty( $discount_config ) && is_array( $discount_config ) ) {
								$extra_data['atum_order_item_discount_config'][ $po_item->get_id() ] = $discount_config;
							}
							if ( ! empty( $tax_config ) && is_array( $tax_config ) ) {
								$extra_data['atum_order_item_tax_config'][ $po_item->get_id() ] = $tax_config;
							}
							$this->target_po->add_item_config_data( $po_item, $extra_data );

							do_action( 'atum/purchase_orders_pro/merge_order_items', $po_item, $merged_item );
							break;

						}

					}
					elseif ( 'fee' === $po_item->get_type() ) {
						/**
						 * Variable definition
						 *
						 * @var POItemFee $po_item
						 */
						if ( $merged_name === $po_item->get_name() ) {

							$found = TRUE;
							$po_item->set_props( array(
								'atum_order_id' => $this->target_po->get_id(),
								'total'         => $merged_item->get_total(),
								'taxes'         => $merged_item->get_taxes(),
								'total_tax'     => $merged_item->get_total_tax(),
								'tax_class'     => $merged_item->get_tax_class(),
								'tax_status'    => $merged_item->get_tax_status(),
							) );
							$po_item->save();
						}
					}
					elseif ( 'shipping' === $po_item->get_type() ) {
						/**
						 * Variable definition
						 *
						 * @var POItemShipping $po_item
						 */
						if ( $merged_name === $po_item->get_name() ) {

							$found = TRUE;
							$po_item->set_props( array(
								'atum_order_id' => $this->target_po->get_id(),
								'total'         => $merged_item->get_total(),
								'taxes'         => $merged_item->get_taxes(),
								'total_tax'     => $merged_item->get_total_tax(),
								'method_title'  => $merged_item->get_method_title(),
								'method_id'     => $merged_item->get_method_id(),
							) );
							$po_item->save();
						}
					}
				}

			}

			// If wasn't merged, just add it to the target PO.
			if ( ! $found ) {
				$item = clone $merged_item;
				$item->set_id( 0 );
				$item->set_atum_order_id( $this->target_po->get_id() );
				$this->target_po->add_item( $item );

				$meta_data = $this->get_item_external_meta( $merged_item );
				$this->target_po->add_bulk_item_data( $item, $meta_data );

				$item->save();

				$extra_data = array();

				$discount_config = maybe_unserialize( $merged_item->get_meta( '_discount_config', TRUE ) );
				$tax_config      = maybe_unserialize( $merged_item->get_meta( '_tax_config', TRUE ) );

				if ( ! empty( $discount_config ) ) {
					$extra_data['atum_order_item_discount_config'][ $item->get_id() ] = $discount_config;
				}
				if ( ! empty( $tax_config ) ) {
					$extra_data['atum_order_item_tax_config'][ $item->get_id() ] = $tax_config;
				}
				$this->target_po->add_item_config_data( $item, $extra_data );

				do_action( 'atum/purchase_orders_pro/merge_order_items', $item, $merged_item );
			}

		}

		// Re-instance target PO with probably new items included.
		$this->target_po = AtumHelpers::get_atum_order_model( $this->target_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		$this->target_po->update_taxes();
		$this->target_po->calculate_totals();

	}

	/**
	 * Import deliveries from the source PO
	 *
	 * @since 0.9.18
	 */
	public function import_deliveries() {

		// Re-instance target PO.
		$this->target_po = AtumHelpers::get_atum_order_model( $this->target_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		$target_deliveries = Deliveries::get_po_orders( $this->target_po->get_id() );
		$source_deliveries = Deliveries::get_po_orders( $this->source_po->get_id() );

		foreach ( $source_deliveries as $source_delivery ) {

			$delivery = NULL;
			foreach ( $target_deliveries as $target_delivery ) {

				if ( $source_delivery->document_number === $target_delivery->document_number && $source_delivery->name === $target_delivery->name ) {
					$delivery = $target_delivery;
					break;
				}

			}

			if ( is_null( $delivery ) ) {
				// New delivery.
				$delivery = new Delivery();
				$delivery->set_po( $this->target_po->get_id() );
			}

			// Delivery data & meta.
			$data = array_merge( $source_delivery->get_meta(), $source_delivery->get_data() );
			unset( $data['id'], $data['po'], $data['items'] );

			$delivery->set_props( $data );
			$delivery->set_document_number( $source_delivery->document_number );
			$delivery->set_total( $source_delivery->total );
			$delivery->save_meta();
			$delivery->save();

			// Entity Files.
			$current_files = $delivery->files;
			$merged_files  = $source_delivery->files;
			$delivery->set_files( array_merge( $current_files, $merged_files ) );

			$delivery_id = $delivery->save();

			$delivery_item_types   = [ 'delivery_item', 'delivery_item_inventory' ];
			$source_delivery_items = $source_delivery->get_items( $delivery_item_types );

			// Delivery Items.
			foreach ( $source_delivery_items as $source_delivery_item ) {

				// Re-instance delivery in order to get the imported items.
				$delivery = new Delivery( $delivery_id );

				/**
				 * Variable definition
				 *
				 * @var DeliveryItemProduct|DeliveryItemProductInventory $source_delivery_item
				 */
				$merged_product   = 'delivery_item_inventory' !== $source_delivery_item->get_type() ? ( $source_delivery_item->get_variation_id() ?: $source_delivery_item->get_product_id() ) : FALSE;
				$merged_inventory = 'delivery_item_inventory' === $source_delivery_item->get_type() ? $source_delivery_item->get_inventory_id() : FALSE;
				$found_item       = FALSE;
				$delivery_items   = $delivery->get_items( $delivery_item_types );

				foreach ( $delivery_items as $delivery_item ) {

					/**
					 * Variable definition
					 *
					 * @var DeliveryItemProduct|DeliveryItemProductInventory $delivery_item
					 */
					if ( $source_delivery_item->get_name() !== $delivery_item->get_name() ) {
						continue;
					}

					$item_product   = 'delivery_item_inventory' !== $delivery_item->get_type() ? ( $delivery_item->get_variation_id() ?: $delivery_item->get_product_id() ) : FALSE;
					$item_inventory = 'delivery_item_inventory' === $delivery_item->get_type() ? $delivery_item->get_inventory_id() : FALSE;

					if ( ( $merged_product && $merged_product === $item_product ) || ( $merged_inventory && $merged_inventory === $item_inventory ) ) {
						$delivery_item->set_quantity( $source_delivery_item->get_quantity() );
						$delivery_item->save();
						$found_item = TRUE;
						break;
					}

				}

				if ( ! $found_item ) {

					if ( 'delivery_item_inventory' === $source_delivery_item->get_type() ) {
						do_action( 'atum/purchase_orders_pro/merge_po/add_delivery_item_inventory', $source_delivery_item, $delivery );
					}
					else {

						$po_item_id = FALSE;

						// Find the order_item_id.
						foreach ( $this->target_po->get_items() as $po_item ) {
							if ( $source_delivery_item->get_product_id() === $po_item->get_product_id() ) {
								$po_item_id = $po_item->get_id();
								break;
							}
						}

						if ( $po_item_id ) {
							$delivery->add_product( $source_delivery_item->get_product(), $source_delivery_item->get_quantity(), [ 'po_item_id' => $po_item_id ] );
						}

					}

					$delivery->save();

				}

			}

			// Calculate Qtys.
			$delivery = new Delivery( $delivery_id );
			Delivery::calculate_delivery_items_qtys( $delivery->get_items(), $this->target_po, $delivery->get_id() );

		}

	}

	/**
	 * Import invoices from the source PO
	 *
	 * @since 0.9.18
	 */
	public function import_invoices() {

		// Re-instance target PO.
		$this->target_po = AtumHelpers::get_atum_order_model( $this->target_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		$target_invoices = Invoices::get_po_orders( $this->target_po->get_id() );
		$source_invoices = Invoices::get_po_orders( $this->source_po->get_id() );

		foreach ( $source_invoices as $source_invoice ) {

			$invoice = NULL;
			foreach ( $target_invoices as $target_invoice ) {

				if ( $source_invoice->document_number === $target_invoice->document_number ) {
					$invoice = $target_invoice;
					break;
				}

			}

			if ( is_null( $invoice ) ) {
				// New invoice.
				$invoice = new Invoice();
				$invoice->set_po( $this->target_po->get_id() );
			}

			// Invoice data & meta.
			$data = array_merge( $source_invoice->get_meta(), $source_invoice->get_data() );
			unset( $data['id'], $data['po'], $data['items'] );

			$invoice->set_props( $data );
			$invoice->set_document_number( $source_invoice->document_number );
			$invoice->set_total( $source_invoice->total );
			$invoice->set_discount_total( $source_invoice->get_total_discount() );
			$invoice->save_meta();
			$invoice->save();

			// Invoice Files.
			$current_files = $invoice->files;
			$merged_files  = $source_invoice->files;
			$invoice->set_files( array_merge( $current_files, $merged_files ) );

			$invoice_id           = $invoice->save();
			$source_invoice_items = $source_invoice->get_items( [ 'invoice_item', 'fee', 'shipping' ] );

			// Invoice Items.
			foreach ( $source_invoice_items as $source_invoice_item ) {

				// Re-instance the invoice in order to get the imported items.
				$invoice = new Invoice( $invoice_id );

				if ( 'invoice_item' === $source_invoice_item->get_type() ) {
					/**
					 * Variable definition
					 *
					 * @var InvoiceItemProduct $source_invoice_item
					 */
					$merged_product = $source_invoice_item->get_variation_id() ?: $source_invoice_item->get_product_id();
				}
				$found_item     = FALSE;
				$invoice_items  = $invoice->get_items( [ 'invoice_item', 'fee', 'shipping' ] );

				foreach ( $invoice_items as $invoice_item ) {

					if ( $source_invoice_item->get_type() !== $invoice_item->get_type() || $source_invoice_item->get_name() !== $invoice_item->get_name() ) {
						continue;
					}

					if ( 'invoice_item' === $invoice_item->get_type() ) {
						$item_product = $invoice_item->get_variation_id() ?: $invoice_item->get_product_id();

						if ( $merged_product && $merged_product === $item_product ) {
							$invoice_item->set_quantity( $source_invoice_item->get_quantity() );
							$invoice_item->set_subtotal( $source_invoice_item->get_subtotal() );
							$invoice_item->set_total( $source_invoice_item->get_total() );
							$invoice_item->set_taxes( $source_invoice_item->get_taxes() );
							$invoice_item->set_tax_class( $source_invoice_item->get_tax_class() );
							$invoice_item->set_subtotal_tax( $source_invoice_item->get_subtotal_tax() );
							$invoice_item->set_total_tax( $source_invoice_item->get_total_tax() );
							$invoice_item->save();
							$found_item = TRUE;
							break;
						}
					}
					elseif ( 'fee' === $invoice_item->get_type() ) {
						$invoice_item->set_total( $source_invoice_item->get_total() );
						$invoice_item->set_taxes( $source_invoice_item->get_taxes() );
						$invoice_item->set_total_tax( $source_invoice_item->get_total_tax() );
						$invoice_item->set_tax_class( $source_invoice_item->get_tax_class() );
						$invoice_item->set_tax_status( $source_invoice_item->get_tax_status() );
						$invoice_item->save();
						$found_item = TRUE;
						break;
					}
					elseif ( 'shipping' === $invoice_item->get_type() ) {
						$invoice_item->set_total( $source_invoice_item->get_total() );
						$invoice_item->set_taxes( $source_invoice_item->get_taxes() );
						$invoice_item->set_method_id( $source_invoice_item->get_method_id() );
						$invoice_item->set_method_title( $source_invoice_item->get_method_title() );
						$invoice_item->save();
						$found_item = TRUE;
						break;
					}
				}

				if ( ! $found_item ) {

					$po_item_id = FALSE;

					// Find the order_item_id.
					foreach ( $this->target_po->get_items( [ 'line_item', 'fee', 'shipping' ] ) as $po_item ) {

						if ( $source_invoice_item->get_name() === $po_item->get_name() ) {
							$po_item_id = $po_item->get_id();
							break;
						}
					}

					if ( $po_item_id ) {
						if ( 'invoice_item' === $source_invoice_item->get_type() ) {
							$invoice->add_product( $source_invoice_item->get_product(), $source_invoice_item->get_quantity(), [ 'po_item_id' => $po_item_id ] );
						}
						elseif ( 'fee' === $source_invoice_item->get_type() ) {
							$invoice->add_fee( $source_invoice_item, [ 'po_item_id' => $po_item_id ] );
						}
						elseif ( 'shipping' === $source_invoice_item->get_type() ) {
							$invoice->add_shipping_cost( $source_invoice_item, [ 'po_item_id' => $po_item_id ] );
						}
					}

					$invoice->save();

				}

			}

			$invoice->calculate_totals();

		}

	}

	/**
	 * Import files from the source PO
	 *
	 * @since 0.9.18
	 */
	public function import_files() {

		// Re-instance target PO.
		$this->target_po = AtumHelpers::get_atum_order_model( $this->target_po->get_id(), TRUE, PurchaseOrders::POST_TYPE );

		$target_po_files = $this->target_po->files;
		$source_po_files = $this->source_po->files;

		foreach ( $source_po_files as $source_po_file ) {
			$found_file = FALSE;
			foreach ( $target_po_files as $target_po_file ) {
				if ( $target_po_file['id'] === $source_po_file['id'] ) {
					$found_file = TRUE;
					break;
				}
			}
			if ( ! $found_file ) {
				$target_po_files[] = $source_po_file;
			}
		}

		$this->target_po->set_files( $target_po_files );
		$this->target_po->save();

	}

	/**
	 * Import comments from the source PO
	 *
	 * @since 0.9.18
	 */
	public function import_comments() {

		$source_po_id = $this->source_po->get_id();
		$target_po_id = $this->target_po->get_id();

		$args = array(
			'post_id' => $source_po_id,
			'orderby' => 'comment_date_gmt',
			'order'   => 'ASC',
		);

		$po_last_merge = get_post_meta( $target_po_id, 'last_merge_comments', TRUE );

		if ( ! is_array( $po_last_merge ) ) {
			$po_last_merge = [];
		}
		elseif ( isset( $po_last_merge[ $source_po_id ] ) ) {
			$args['date_query'] = array(
				array(
					'column' => 'comment_date_gmt',
					'after'  => AtumHelpers::date_format( strtotime( $po_last_merge[ $source_po_id ] ), TRUE, TRUE ),
				),
			);
		}

		$atum_comments = AtumComments::get_instance();

		remove_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );
		$comments = get_comments( $args );
		add_filter( 'comments_clauses', array( $atum_comments, 'exclude_atum_order_notes' ) );

		foreach ( $comments as $comment ) {

			/**
			 * Variable definition
			 *
			 * @var \WP_Comment $comment
			 */
			$data       = array(
				'comment_post_ID'      => $target_po_id,
				'comment_author'       => $comment->comment_author,
				'comment_author_email' => $comment->comment_author_email,
				'comment_author_url'   => $comment->comment_author_url,
				'comment_author_IP'    => $comment->comment_author_IP,
				'comment_date'         => $comment->comment_date,
				'comment_date_gmt'     => $comment->comment_date_gmt,
				'comment_content'      => $comment->comment_content,
				'comment_karma'        => $comment->comment_karma,
				'comment_approved'     => $comment->comment_approved,
				'comment_agent'        => $comment->comment_agent,
				'comment_type'         => $comment->comment_type,
				'comment_parent'       => $comment->comment_parent,
			);
			$comment_id = wp_insert_comment( $data );

			$meta = get_comment_meta( $comment->comment_ID );

			foreach ( $meta as $meta_key => $meta_value ) {
				add_comment_meta( $comment_id, $meta_key, $meta_value );
			}

		}

		$po_last_merge[ $source_po_id ] = AtumHelpers::date_format( '', TRUE, TRUE );
		update_post_meta( $target_po_id, 'last_merge_comments', $po_last_merge );

	}

	/**
	 * Return the non-internal item's meta
	 *
	 * @since 1.1.8
	 *
	 * @param POItemProduct|POItemShipping|POItemFee $item
	 *
	 * @return mixed
	 */
	public function get_item_external_meta( $item ) {

		$item->read_meta_data();
		$meta_data = $item->get_meta_data();
		return array_filter( $meta_data, function( $obj ) use ( $item ) {
			return ! $item->is_internal_meta( $obj->key );
		} );

	}

}
