<?php
/**
 * The model class for the Invoice Item objects
 *
 * @package         AtumPO\Invoices
 * @subpackage      Models
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.6
 */

namespace AtumPO\Invoices\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumException;
use Atum\Components\AtumOrders\Models\AtumOrderItemModel;


class InvoiceItem extends AtumOrderItemModel {

	/**
	 * InvoiceItem constructor
	 *
	 * @param \WC_Order_Item $invoice_item The factory object for initialization.
	 *
	 * @throws AtumException
	 */
	public function __construct( \WC_Order_Item $invoice_item ) {
		$this->atum_order_item = $invoice_item;
		parent::__construct( $invoice_item->get_id() );
	}

	/* @noinspection PhpDocMissingThrowsInspection PhpDocRedundantThrowsInspection */
	/**
	 * Read an Invoice item from the database
	 *
	 * @since 0.9.6
	 *
	 * @throws AtumException
	 */
	protected function read() {

		try {

			parent::read();

			// Read the Invoice item props from db.
			$line_total    = $this->get_meta( '_line_total' );
			$line_subtotal = $this->get_meta( '_line_subtotal' );
			$this->atum_order_item->set_props( array(
				'product_id'   => $this->get_meta( '_product_id' ),
				'variation_id' => $this->get_meta( '_variation_id' ),
				'quantity'     => $this->get_meta( '_qty' ),
				'po_item_id'   => $this->get_meta( '_po_item_id' ),
				'tax_class'    => $this->get_meta( '_tax_class' ),
				'subtotal'     => $line_subtotal < $line_total ? $line_total : $line_subtotal,
				'total'        => $line_total,
				'taxes'        => $this->get_meta( '_line_tax_data' ),
			) );

			$this->atum_order_item->set_object_read( TRUE );

		} catch ( AtumException $e ) {

			if ( ATUM_DEBUG ) {
				error_log( __METHOD__ . '::' . $e->getMessage() );
			}

		}

	}

}
