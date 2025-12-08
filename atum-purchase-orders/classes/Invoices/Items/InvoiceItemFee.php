<?php
/**
 * The class for the Invoice Item Fee objects
 *
 * @package         AtumPO\Invoices
 * @subpackage      Items
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.17
 */

namespace AtumPO\Invoices\Items;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;
use AtumPO\Abstracts\AtumPOOrders\Items\AtumPOOrderItemTrait;
use AtumPO\Invoices\Models\InvoiceItem;


class InvoiceItemFee extends AtumOrderItemFee {

	/**
	 * The Invoice item data array
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'po_item_id' => 0,
		'tax_class'  => '',
		'tax_status' => 'taxable',
		'total'      => '',
		'total_tax'  => '',
		'taxes'      => array(
			'total' => array(),
		),
	);

	/**
	 * Meta keys reserved for internal use
	 *
	 * @var array
	 */
	protected $internal_meta_keys = array( '_po_item_id', '_tax_class', '_tax_status', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data', '_tax_config' );

	/**
	 * Load the Invoice item
	 *
	 * @since 0.9.17
	 */
	protected function load() {

		/* @noinspection PhpParamsInspection PhpUnhandledExceptionInspection */
		$this->atum_order_item_model = new InvoiceItem( $this );

		if ( ! $this->atum_order_id ) {
			$this->atum_order_id = $this->atum_order_item_model->get_atum_order_id();
		}

		$this->read_meta_data();

	}

	/**
	 * Saves an item's meta data to the database
	 * Runs after both create and update, so $id will be set
	 *
	 * @since 0.9.17
	 */
	public function save_item_data() {

		$save_values = (array) apply_filters( 'atum/purchase_orders_pro/invoice_item_fee/save_data', array(
			'_po_item_id'    => $this->get_po_item_id( 'edit' ),
			'_tax_class'     => $this->get_tax_class( 'edit' ),
			'_tax_status'    => $this->get_tax_status( 'edit' ),
			'_line_total'    => $this->get_total( 'edit' ),
			'_line_tax'      => $this->get_total_tax( 'edit' ),
			'_line_tax_data' => $this->get_taxes( 'edit' ),
			'_tax_config'    => maybe_unserialize( $this->get_meta( '_tax_config' ) ),
		), $this );

		$this->atum_order_item_model->save_meta( $save_values );

	}

	/**
	 * Get invoice item type.
	 *
	 * @since 0.9.17
	 *
	 * @return string
	 */
	public function get_type() {
		return 'fee';
	}

	use AtumPOOrderItemTrait;

}
