<?php
/**
 * The model class for the Invoice objects
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

use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\PurchaseOrders;
use AtumPO\Abstracts\AtumPOOrders\Models\AtumPOOrder;
use AtumPO\Invoices\Invoices;
use AtumPO\Invoices\Items\InvoiceItemFee;
use AtumPO\Invoices\Items\InvoiceItemShipping;
use AtumPO\Invoices\Items\InvoiceItemProduct;
use AtumPO\Models\POExtended;


/**
 * Class Invoice
 *
 * Meta props available through the __get magic method:
 *
 * @property int   $document_number
 * @property int[] $files
 * @property int   $po
 */
class Invoice extends AtumPOOrder {

	/**
	 * Array to store the metadata to add/update
	 * NOTE: We are just replacing the parent meta instead of extending them because we don't need some meta.
	 *
	 * @var array
	 */
	protected $meta = [
		'status'             => 'publish', // We don't need any custom status here.
		'date_created'       => '',
		'document_number'    => '',
		'currency'           => '',
		'discount_total'     => NULL,
		'discount_tax'       => NULL,
		'shipping_total'     => NULL,
		'shipping_tax'       => NULL,
		'cart_tax'           => NULL,
		'total'              => NULL,
		'total_tax'          => NULL,
		'po'                 => NULL,
		'files'              => [],
		'created_via'        => '',
		'prices_include_tax' => 'no',
	];

	/**
	 * The default line item type
	 *
	 * @var string
	 */
	protected $line_item_type = 'invoice_item';

	/**
	 * The default line item group
	 *
	 * @var string
	 */
	protected $line_item_group = 'invoice_items';

	/**
	 * Array of data key names used in invoice items when submitting an Invoice
	 *
	 * @var array
	 */
	protected $posted_data_keys = [
		'invoice_item_cost',
		'invoice_item_discount',
		'invoice_item_discount_config',
		'invoice_item_qty',
		'invoice_item_total',
		'invoice_item_tax',
		'invoice_item_tax_config',
	];

	/**
	 * Convert a type to a types group
	 *
	 * @since 0.9.17
	 *
	 * @param string $type
	 *
	 * @return string group
	 */
	protected function type_to_group( $type ) {

		$type_to_group = (array) apply_filters( 'atum/order/item_type_to_group', array(
			$this->line_item_type => $this->line_item_group,
			'fee'                 => 'invoice_fee_items',
			'shipping'            => 'invoice_shipping_items',
		) );

		return isset( $type_to_group[ $type ] ) ? $type_to_group[ $type ] : '';

	}

	/**
	 * Convert a type of group to a type
	 *
	 * @since 0.9.17
	 *
	 * @param string $group
	 *
	 * @return string type
	 */
	protected function group_to_type( $group ) {

		$group_to_type = (array) apply_filters( 'atum/order/item_group_to_type', array(
			$this->line_item_group   => $this->line_item_type,
			'invoice_fee_items'      => 'fee',
			'invoice_shipping_items' => 'shipping',
		) );

		return isset( $group_to_type[ $group ] ) ? $group_to_type[ $group ] : '';

	}

	/**
	 * Save the Invoice data to the database
	 *
	 * @since 0.9.6
	 *
	 * @param bool $including_meta Optional. Whether to save the meta too.
	 *
	 * @return int|\WP_Error order ID or an error
	 */
	public function save( $including_meta = TRUE ) {

		if ( ! $this->po ) {
			return new \WP_Error( 'invalid_po', __( 'The Invoice cannot be saved without a parent PO', ATUM_PO_TEXT_DOMAIN ) );
		}

		return parent::save( $including_meta );

	}

	/**
	 * Save Invoice items. Uses the CRUD
	 *
	 * @since 0.9.6
	 *
	 * @param array $items_data Invoice items to save.
	 */
	public function save_order_items( $items_data ) {

		// Allow other plugins to check changes in ATUM Order items before they are saved.
		do_action( 'atum/purchase_orders_pro/before_save_invoice_items', $this, $items_data );

		// Line items.
		if ( isset( $items_data['atum_order_item_id'] ) ) {

			$data_keys = array(
				'invoice_item_cost'            => 0,
				'invoice_item_discount'        => 0,
				'invoice_item_qty'             => 0,
				'invoice_item_total'           => 0,
				'invoice_item_tax'             => 0,
				'invoice_item_discount_config' => '',
				'invoice_item_tax_config'      => '',
			);

			foreach ( $items_data['atum_order_item_id'] as $item_id ) {

				/**
				 * Variable definition
				 *
				 * @var InvoiceItemProduct $item
				 */
				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				if ( ! $item instanceof InvoiceItemProduct ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $items_data[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items_data[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['invoice_item_qty'] ) {
					$item->delete();
					continue;
				}

				$quantity       = wc_stock_amount( $item_data['invoice_item_qty'] );
				$line_total     = (float) $item_data['invoice_item_total']; // The total comes with the discount already applied.
				$line_subtotal  = $line_total + (float) $item_data['invoice_item_discount'] * $quantity;
				$line_tax_total = wc_round_tax_total( $item_data['invoice_item_tax'] ); // The tax was calculated after applying discounts.

				$item->set_props( array(
					'quantity' => $quantity,
					'total'    => $line_total,
					'subtotal' => $line_subtotal < $line_total ? $line_total : $line_subtotal,
					'taxes'    => array(
						'total'    => [ $line_tax_total ],
						'subtotal' => [ $line_subtotal < $line_total && $line_total ? wc_round_tax_total( ( $line_subtotal * $line_tax_total ) / $line_total ) : $line_tax_total ],
					),
				) );

				$this->add_item( $item );

				// Save the invoice item's config data (if any).
				if ( ! empty( $item_data['invoice_item_discount_config'] ) ) {
					$item->add_meta_data( '_discount_config', json_decode( stripslashes( $item_data['invoice_item_discount_config'] ), TRUE ), TRUE );
				}

				if ( ! empty( $item_data['invoice_item_tax_config'] ) ) {
					$item->add_meta_data( '_tax_config', json_decode( stripslashes( $item_data['invoice_item_tax_config'] ), TRUE ), TRUE );
				}

				$item->save();

			}

		}

		// TODO: AND SHIPPING AND FEES? AREN'T BEING SAVED??

		// Updates tax totals.
		$this->update_taxes();

		// Calc totals - this also triggers save.
		$this->calculate_totals();

		// Inform other plugins that the items have been saved.
		do_action( 'atum/purchase_orders_pro/after_save_items', $this, $items_data );

	}

	/**
	 * Add a product line item to the Invoice
	 *
	 * @since 0.9.6
	 *
	 * @param  \WC_Product $product
	 * @param  int|float   $qty
	 * @param  array       $props
	 *
	 * @return InvoiceItemProduct The product item added to the invoice
	 */
	public function add_product( $product, $qty = NULL, $props = array() ) {

		if ( $product instanceof \WC_Product ) {

			// Get the default pricing values from the associated PO item.
			$po_item     = new POItemProduct( $props['po_item_id'] );
			$po_item_qty = (float) $po_item->get_quantity();

			$discount_config = maybe_unserialize( $po_item->get_meta( '_discount_config' ) );
			$tax_config      = maybe_unserialize( $po_item->get_meta( '_tax_config' ) );

			// Calculate the proportional invoice item's totals from the related PO item's totals.
			if ( (float) $qty !== $po_item_qty ) {

				$po_item_unit_cost         = (float) $po_item->get_subtotal() / $po_item_qty;
				$po_item_unit_discount     = ( (float) $po_item->get_subtotal() - (float) $po_item->get_total() ) / $po_item_qty;
				$po_item_unit_subtotal_tax = (float) $po_item->get_subtotal_tax() / $po_item_qty;
				$po_item_unit_total_tax    = (float) $po_item->get_total_tax() / $po_item_qty;

				$invoice_item_subtotal     = $po_item_unit_cost * $qty;
				$invoice_item_total        = $invoice_item_subtotal - ( $po_item_unit_discount * $qty );
				$invoice_item_tax_subtotal = $po_item_unit_subtotal_tax * $qty;
				$invoice_item_tax_total    = $po_item_unit_total_tax * $qty;

				// Adjust the discount proportionally if the discount config is a fixed value.
				if ( is_array( $discount_config ) && isset( $discount_config['type'] ) && 'percentage' !== $discount_config['type'] ) {
					$discount_config['fieldValue'] = ( (float) $discount_config['fieldValue'] / $po_item_qty ) * $qty;
				}

				// Adjust the tax proportionally if the tax config is a fixed value.
				if ( is_array( $tax_config ) && isset( $tax_config['type'] ) && 'percentage' !== $tax_config['type'] ) {
					$tax_config['fieldValue'] = ( (float) $tax_config['fieldValue'] / $po_item_qty ) * $qty;
				}

			}
			// If it's the whole qty, just use the PO item's data.
			else {
				$invoice_item_subtotal     = $po_item->get_subtotal();
				$invoice_item_total        = $po_item->get_total();
				$invoice_item_tax_subtotal = $po_item->get_subtotal_tax();
				$invoice_item_tax_total    = $po_item->get_total_tax();
			}

			$default_args = array(
				'name'         => $product->get_name(),
				'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
				'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
				'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : [],
				'subtotal'     => $invoice_item_subtotal,
				'total'        => $invoice_item_total,
				'taxes'        => array(
					'subtotal' => [ $invoice_item_tax_subtotal ],
					'total'    => [ $invoice_item_tax_total ],
				),
				'quantity'     => $qty,
			);

		}
		else {

			$default_args = array(
				'quantity' => $qty,
			);

		}

		$props      = wp_parse_args( $props, $default_args );
		$item_class = $this->get_items_class( $this->line_item_group );

		/**
		 * Variable definition
		 *
		 * @var InvoiceItemProduct $item
		 */
		$item = new $item_class();
		$item->set_props( $props );
		$item->set_atum_order_id( $this->id );

		if ( ! empty( $discount_config ) ) {
			$item->add_meta_data( '_discount_config', $discount_config, TRUE );
		}

		if ( ! empty( $tax_config ) ) {
			$item->add_meta_data( '_tax_config', $tax_config, TRUE );
		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Add a fee item to the Invoice
	 *
	 * @since 0.9.17
	 *
	 * @param \WC_Order_Item_Fee $fee   Optional. Fee item to import.
	 * @param array              $props Optional. Properties to set.
	 *
	 * @return InvoiceItemFee  The fee item added to the Invoice.
	 */
	public function add_fee( \WC_Order_Item_Fee $fee = NULL, $props = array() ) {

		$item_class = $this->get_items_class( 'invoice_fee_items' );

		/**
		 * Variable definition
		 *
		 * @var InvoiceItemFee $item
		 */
		$item = new $item_class();
		$this->check_order_id(); // Make sure the current PO already exists before adding products or it could fail.
		$item->set_atum_order_id( $this->id );
		$item->set_props( $props );

		if ( $fee ) {

			$item->set_total( $fee->get_total() );
			$item->set_total_tax( $fee->get_total_tax() );
			$item->set_tax_status( $fee->get_tax_status() );
			$item->set_taxes( $fee->get_taxes() );
			$item->set_tax_class( $fee->get_tax_class() );
			$item->set_name( $fee->get_name() );

			$tax_config = maybe_unserialize( $fee->get_meta( '_tax_config' ) );

			if ( ! empty( $tax_config ) ) {
				$item->add_meta_data( '_tax_config', $tax_config );
			}

		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/* @noinspection PhpDocMissingThrowsInspection */
	/**
	 * Add a shipping cost item to the Invoice
	 *
	 * @since 0.9.17
	 *
	 * @param \WC_Order_Item_Shipping $shipping  Optional. Shipping cost item to import.
	 * @param array                   $props     Optional. Properties to set.
	 *
	 * @return InvoiceItemShipping  The shipping cost item added to the Invoice.
	 */
	public function add_shipping_cost( \WC_Order_Item_Shipping $shipping = NULL, $props = array() ) {

		$item_class = $this->get_items_class( 'invoice_shipping_items' );

		/**
		 * Variable definition
		 *
		 * @var InvoiceItemShipping $item
		 */
		$item = new $item_class();
		$item->set_shipping_rate( new \WC_Shipping_Rate() );
		$this->check_order_id(); // Make sure the current PO already exists before adding products or it could fail.
		$item->set_atum_order_id( $this->id );
		$item->set_props( $props );

		if ( $shipping ) {

			$item->set_total( $shipping->get_total() );
			$item->set_method_id( $shipping->get_method_id() );
			$item->set_taxes( $shipping->get_taxes() );
			$item->set_method_title( $shipping->get_method_title() );
			$item->set_name( $shipping->get_name() );

			$tax_config = maybe_unserialize( $shipping->get_meta( '_tax_config' ) );

			if ( ! empty( $tax_config ) ) {
				$item->add_meta_data( '_tax_config', $tax_config );
			}

		}

		$item->save();
		$this->add_item( $item );

		if ( $shipping ) {
			$item->add_meta_data( '_total_tax', $shipping->get_total_tax( 'edit' ), TRUE );
		}

		return $item;

	}

	/**
	 * Add a tax item to the Invoice
	 *
	 * @since 0.9.21
	 *
	 * @param array              $values {
	 *      The array of tax values to add to the created tax item.
	 *
	 *      @type int    $rate_id            The tax rate ID
	 *      @type string $name               The tax item name
	 *      @type float  $tax_total          The tax total
	 *      @type float  $shipping_tax_total The shipping tax total
	 *
	 * }
	 * @param \WC_Order_Item_Tax $tax Optional. Tax item to import.
	 *
	 * @return bool Not applicable
	 */
	public function add_tax( array $values, \WC_Order_Item_Tax $tax = NULL ) {
		return FALSE;
	}

	/**
	 * Calculate taxes for all invoice items and shipping, and store the totals and tax rows
	 *
	 * @since 0.9.21
	 *
	 * @param array $args Optional. To pass things like location.
	 */
	public function calculate_taxes( $args = array() ) {
		$this->update_taxes();
	}

	/**
	 * Update tax lines for the Invoice based on the line item taxes themselves
	 *
	 * @since 0.9.21
	 */
	public function update_taxes() {

		$cart_taxes = $shipping_taxes = array();

		foreach ( $this->get_items( [ $this->line_item_type, 'fee' ] ) as $item ) {

			$taxes = $item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$cart_taxes[ $tax_rate_id ] = isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] + (float) $tax : (float) $tax;
			}

		}

		foreach ( $this->get_shipping_methods() as $shipping_item ) {

			$taxes = $shipping_item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$shipping_taxes[ $tax_rate_id ] = isset( $shipping_taxes[ $tax_rate_id ] ) ? $shipping_taxes[ $tax_rate_id ] + (float) $tax : (float) $tax;
			}

		}

		// Save tax totals.
		$this->set_shipping_tax( \WC_Tax::round( array_sum( $shipping_taxes ) ) );
		$this->set_cart_tax( \WC_Tax::round( array_sum( $cart_taxes ) ) );
		$this->save();

	}

	/**
	 * Delete the invoice
	 *
	 * @since 0.9.21
	 *
	 * @param bool $force_delete
	 */
	public function delete( $force_delete = FALSE ) {

		// The invoices have no trash, so they must be deleted permanently.
		parent::delete( TRUE );

	}


	/*********
	 * GETTERS
	 *********/
	
	/**
	 * Get the Invoice's post type
	 *
	 * @since 0.9.6
	 *
	 * @return string
	 */
	public function get_post_type() {
		return Invoices::POST_TYPE;
	}

	/**
	 * Get an invoice item
	 *
	 * @since 0.9.6
	 *
	 * @param \WC_Order_Item|object|int $item
	 *
	 * @return InvoiceItemProduct|InvoiceItemFee|InvoiceItemShipping|false
	 */
	public function get_atum_order_item( $item = NULL ) {

		if ( $item instanceof \WC_Order_Item ) {
			/**
			 * Variable definition
			 *
			 * @var \WC_Order_Item $item
			 */
			$item_type = $item->get_type();
			$id        = $item->get_id();
		}
		elseif ( is_object( $item ) && ! empty( $item->order_item_type ) ) {
			$id        = $item->order_item_id;
			$item_type = $item->order_item_type;
		}
		elseif ( is_numeric( $item ) && ! empty( $this->items ) ) {

			$id = $item;

			foreach ( $this->items as $group => $group_items ) {

				foreach ( $group_items as $item_id => $stored_item ) {
					// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					if ( $id == $item_id ) {
						$item_type = $this->group_to_type( $group );
						break 2;
					}
				}

			}

		}
		else {
			$id = $item_type = FALSE;
		}

		if ( $id && isset( $item_type ) && $item_type ) {

			$item_group = $this->type_to_group( $item_type );
			$classname  = $this->get_items_class( $item_group );

			if ( $classname && class_exists( $classname ) ) {

				try {
					return new $classname( $id );
				} catch ( \Exception $e ) {
					return FALSE;
				}

			}

		}

		return FALSE;

	}

	/**
	 * Get key for where a certain item type is stored in items prop
	 *
	 * @since 0.9.6
	 *
	 * @param \WC_Order_Item $item Invoice item object.
	 *
	 * @return string
	 */
	protected function get_items_key( $item ) {

		$items_namespace = '\\AtumPO\\Invoices\\Items\\';
		$items_key       = '';

		if ( is_a( $item, "{$items_namespace}InvoiceItemProduct" ) ) {
			$items_key = $this->line_item_group;
		}
		elseif ( is_a( $item, "{$items_namespace}InvoiceItemFee" ) ) {
			$items_key = 'invoice_fee_items';
		}
		elseif ( is_a( $item, "{$items_namespace}InvoiceItemShipping" ) ) {
			$items_key = 'invoice_shipping_items';
		}

		return apply_filters( 'atum/purchase_orders_pro/invoice/get_items_key', $items_key, $item ); // We only use products on invoices.
	}

	/**
	 * This method is the inverse of the get_items_key method
	 * Gets the ATUM Order item's class given its key
	 *
	 * @since 0.9.6
	 *
	 * @param string $items_key The items key.
	 *
	 * @return string
	 */
	protected function get_items_class( $items_key ) {
		switch ( $items_key ) {
			case 'invoice_fee_items':
				$class = '\\AtumPO\\Invoices\\Items\\InvoiceItemFee';
				break;
			case 'invoice_shipping_items':
				$class = '\\AtumPO\\Invoices\\Items\\InvoiceItemShipping';
				break;
			default:
				$class = '\\AtumPO\\Invoices\\Items\\InvoiceItemProduct';
				break;
		}

		return apply_filters( 'atum/purchase_orders_pro/invoice/get_items_class', $class, $items_key ); // We only have one kind of item here.
	}

	/**
	 * Getter to collect all the Invoice data within an array
	 *
	 * @since 0.9.6
	 *
	 * @return array
	 */
	public function get_data() {

		return array(
			'id'                 => $this->id,
			'status'             => $this->status,
			'currency'           => $this->currency ?: get_woocommerce_currency(),
			'document_number'    => $this->document_number,
			'files'              => $this->files,
			'total'              => $this->total,
			'discount_total'     => $this->discount_total,
			'discount_tax'       => $this->discount_tax,
			'total_tax'          => $this->total_tax,
			'shipping_total'     => $this->shipping_total,
			'shipping_tax'       => $this->shipping_tax,
			'cart_tax'           => $this->cart_tax,
			'prices_include_tax' => metadata_exists( 'post', $this->id, '_prices_include_tax' ) ? 'yes' === $this->prices_include_tax : 'yes' === get_option( 'woocommerce_prices_include_tax' ),
			'items'              => $this->get_items(),
		);

	}

	/**
	 * Gets Invoice's total - formatted for display
	 *
	 * @since 0.9.6
	 *
	 * @param  string $tax_display  Optional. Type of tax display.
	 * @param  bool   $subtotal     Optional. If should return the tax free Subtotal instead.
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $subtotal = FALSE ) {

		$amount          = $subtotal ? $this->get_subtotal() : $this->total;
		$po              = $this->get_po_object();
		$formatted_total = $po->format_price( $amount );

		return apply_filters( 'atum/purchase_orders_pro/invoice/get_formatted_total', $formatted_total, $this, $tax_display, $subtotal );

	}

	/**
	 * Gets order subtotal
	 *
	 * @since 0.9.17
	 *
	 * @return float
	 */
	public function get_subtotal() {

		$subtotal = 0;

		foreach ( $this->get_items( [ 'invoice_item', 'fee', 'shipping' ] ) as $item ) {
			if ( 'invoice_item' === $item->get_type() ) {
				/**
				 * Variable definition
				 *
				 * @var InvoiceItemProduct $item
				 */
				$subtotal += $item->get_subtotal();
			}
			else {
				/**
				 * Variable definition
				 *
				 * @var InvoiceItemFee|InvoiceItemShipping $item
				 */
				$subtotal += $item->get_total();
			}
		}

		return apply_filters( 'atum/purchase_orders_pro/get_subtotal', (float) $subtotal, $this );

	}

}
