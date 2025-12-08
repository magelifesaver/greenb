<?php
/**
 * Extended Model for Purchase Orders PRO
 *
 * @since       0.1.0
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumPO\Models
 */

namespace AtumPO\Models;

defined( 'ABSPATH' ) || die;

use Atum\Components\AtumOrders\Items\AtumOrderItemFee;
use Atum\Components\AtumOrders\Items\AtumOrderItemProduct;
use Atum\Components\AtumOrders\Items\AtumOrderItemShipping;
use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\PurchaseOrders\Items\POItemFee;
use Atum\PurchaseOrders\Items\POItemProduct;
use Atum\PurchaseOrders\Items\POItemShipping;
use Atum\PurchaseOrders\Models\PurchaseOrder;
use AtumPO\Deliveries\Deliveries;
use AtumPO\Deliveries\Items\DeliveryItemProduct;
use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;


/**
 * Class POExtended
 *
 * @property string    $currency_pos
 * @property string    $customer_name
 * @property int       $deliveries_counter
 * @property string    $delivery_date
 * @property string    $delivery_terms
 * @property string    $delivery_to_warehouse
 * @property string    $email_template
 * @property float     $exchange_rate
 * @property array     $files
 * @property string    $fob
 * @property bool      $has_taxes_enabled
 * @property array     $meta_box_sizes
 * @property string    $number
 * @property string    $pdf_template
 * @property string    $price_decimal_sep
 * @property int       $price_num_decimals
 * @property string    $price_thousand_sep
 * @property string    $purchaser_address
 * @property string    $purchaser_address_2
 * @property string    $purchaser_city
 * @property string    $purchaser_country
 * @property string    $purchaser_name
 * @property string    $purchaser_postal_code
 * @property string    $purchaser_state
 * @property int       $related_po
 * @property int       $requisitioner
 * @property string    $sales_order_number
 * @property string    $ships_from
 * @property string    $ship_via
 * @property string    $supplier_code
 * @property string    $supplier_currency
 * @property float     $supplier_discount
 * @property string    $supplier_reference
 * @property int|float $supplier_tax_rate
 * @property string    $warehouse
 */
class POExtended extends PurchaseOrder {

	/**
	 * PO extended extra meta
	 *
	 * @var array
	 */
	protected $extra_meta = array(
		'currency_pos'          => 'left',
		'customer_name'         => '',
		'deliveries_counter'    => 1,
		'delivery_date'         => '',
		'delivery_terms'        => '',
		'delivery_to_warehouse' => 'no',
		'email_template'        => 'default',
		'exchange_rate'         => 1,
		'files'                 => [],
		'fob'                   => '',
		'has_taxes_enabled'     => NULL,
		'meta_box_sizes'        => [],
		'number'                => '',
		'pdf_template'          => 'default',
		'price_decimal_sep'     => '.',
		'price_num_decimals'    => 2,
		'price_thousand_sep'    => ',',
		'purchaser_address'     => '',
		'purchaser_address_2'   => '',
		'purchaser_city'        => '',
		'purchaser_country'     => '',
		'purchaser_name'        => '',
		'purchaser_postal_code' => '',
		'purchaser_state'       => '',
		'related_po'            => NULL,
		'requisitioner'         => NULL,
		'sales_order_number'    => '',
		'ships_from'            => '',
		'ship_via'              => '',
		'supplier_code'         => '',
		'supplier_currency'     => '',
		'supplier_discount'     => NULL,
		'supplier_reference'    => '',
		'supplier_tax_rate'     => NULL,
		'warehouse'             => '',
	);

	/**
	 * Array of data key names used in PO items when submitting a PO
	 *
	 * @var array
	 */
	protected $posted_data_keys = [
		'atum_order_item_cost',
		'atum_order_item_discount',
		'atum_order_item_discount_config',
		'atum_order_item_id',
		'atum_order_item_name',
		'atum_order_item_qty',
		'atum_order_item_tax',
		'atum_order_item_tax_config',
		'atum_order_item_total',
	];


	/**
	 * PO constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param int  $id         Optional. The ATUM Order ID to initialize.
	 * @param bool $read_items Optional. Whether to read the inner items.
	 */
	public function __construct( $id = 0, $read_items = TRUE ) {

		$this->set_extra_meta_defaults();

		// Add extra meta to the PO.
		$this->meta = array_merge( $this->meta, $this->extra_meta );

		parent::__construct( $id, $read_items );

		if ( $id ) {
			$this->read_meta();
		}
		else {
			$this->add_default_info();
		}

	}

	/**
	 * Set default values for extra_meta.
	 *
	 * @since 0.9.20
	 */
	private function set_extra_meta_defaults() {

		$this->extra_meta['pdf_template']       = AtumHelpers::get_option( 'po_default_pdf_template', 'default' );
		$this->extra_meta['email_template']     = AtumHelpers::get_option( 'po_default_emails_template', 'default' );
		$this->extra_meta['currency_pos']       = get_option( 'woocommerce_currency_pos', 'left' );
		$this->extra_meta['price_thousand_sep'] = get_option( 'woocommerce_price_thousand_sep', ',' );
		$this->extra_meta['price_decimal_sep']  = get_option( 'woocommerce_price_decimal_sep', '.' );
		$this->extra_meta['price_num_decimals'] = get_option( 'woocommerce_price_num_decimals', 2 );

	}

	/**
	 * Read the PO's metadata from db
	 *
	 * @since 0.1.0
	 */
	public function read_meta() {

		if ( $this->post ) {

			// Read the generic meta.
			parent::read_meta();

			// Only add the default info when the PO is being created from the WP backend's UI (so the inherent post is still in 'auto-draft').
			if ( 'auto-draft' === $this->post->post_status ) {
				$this->add_default_info();
			}

			// Get taxes setting for POs without meta.
			if ( is_null( $this->has_taxes_enabled ) ) {
				// If it's an old PO with taxes, this meta could not exist yet, so we check the tax_totals value.
				$this->set_has_taxes_enabled( 0 < (float) $this->get_tax_totals() || Helpers::are_po_taxes_enabled() );
			}

		}

	}

	/**
	 * Bring the default info when a new PO is created
	 *
	 * @since 0.9.9
	 */
	private function add_default_info() {

		// Do not add default info more than once.
		if ( $this->number ) {
			return;
		}

		// Check whether the purchaser info should be autofilled.
		$auto_fill_purchaser = 'yes' === AtumHelpers::get_option( 'po_auto_fill_purchaser_info', 'yes' );

		if ( $auto_fill_purchaser ) {

			$purchaser_info_source = AtumHelpers::get_option( 'po_purchaser_info_source', 'store_details' );

			// Get info from Store Details.
			if ( 'store_details' === $purchaser_info_source ) {

				// Set the purchaser's info.
				$default_country   = get_option( 'woocommerce_default_country', '' );
				$countries         = WC()->countries;
				$default_city      = $countries->get_base_city();
				$default_adress    = $countries->get_base_address();
				$default_address_2 = $countries->get_base_address_2();
				$default_postcode  = $countries->get_base_postcode();

				if ( 'yes' === AtumHelpers::get_option( 'same_ship_address', 'yes' ) ) {
					$company_name  = AtumHelpers::get_option( 'company_name', '' );
					$address       = AtumHelpers::get_option( 'address_1', $default_adress );
					$address_2     = AtumHelpers::get_option( 'address_2', $default_address_2 );
					$city          = AtumHelpers::get_option( 'city', $default_city );
					$country_state = wc_format_country_state_string( AtumHelpers::get_option( 'country', $default_country ) );
					$postal_code   = AtumHelpers::get_option( 'zip', $default_postcode );
				}
				else {
					$company_name  = AtumHelpers::get_option( 'ship_to', '' );
					$address       = AtumHelpers::get_option( 'ship_address_1', $default_adress );
					$address_2     = AtumHelpers::get_option( 'ship_address_2', $default_address_2 );
					$city          = AtumHelpers::get_option( 'ship_city', $default_city );
					$country_state = wc_format_country_state_string( AtumHelpers::get_option( 'ship_country', $default_country ) );
					$postal_code   = AtumHelpers::get_option( 'ship_zip', $default_postcode );
				}

				$this->set_purchaser_name( $company_name );
				$this->set_purchaser_address( $address );
				$this->set_purchaser_address_2( $address_2 );
				$this->set_purchaser_city( $city );
				$this->set_purchaser_postal_code( $postal_code );
				$this->set_purchaser_country( $country_state['country'] ?? '' );

				if ( ! empty( $country_state['state'] ) ) {

					$states = WC()->countries->get_states();

					// Get the friendly name for the state.
					if ( ! empty( $states[ $country_state['country'] ][ $country_state['state'] ] ) ) {
						$this->set_purchaser_state( $states[ $country_state['country'] ][ $country_state['state'] ] );
					}

				}

			}
			// Get info from a chosen location.
			else {

				$location = get_term_by( 'id', $purchaser_info_source, AtumGlobals::PRODUCT_LOCATION_TAXONOMY );

				if ( ! is_wp_error( $location ) && $location ) {
					$this->set_purchaser_name( $location->name );
					$this->set_purchaser_address( get_term_meta( $location->term_id, 'address', TRUE ) );
					$this->set_purchaser_address_2( get_term_meta( $location->term_id, 'address_2', TRUE ) );
					$this->set_purchaser_city( get_term_meta( $location->term_id, 'city', TRUE ) );
					$this->set_purchaser_state( get_term_meta( $location->term_id, 'state', TRUE ) );
					$this->set_purchaser_postal_code( get_term_meta( $location->term_id, 'postal_code', TRUE ) );
					$this->set_purchaser_country( get_term_meta( $location->term_id, 'country', TRUE ) );
				}

			}

		}

		$this->set_description( AtumHelpers::get_option( 'po_default_description', '' ) );
		$this->set_delivery_terms( AtumHelpers::get_option( 'po_default_delivery_terms', '' ) );
		$this->set_has_taxes_enabled( Helpers::are_po_taxes_enabled() );
		if ( ! $this->get_meta( 'number' ) ) {
			$this->set_number(); // Make sure the automatic number is generated (if needed).
		}
	}

	/**
	 * Add a product line item to the PO
	 *
	 * @since 0.9.9
	 *
	 * @param \WC_Product $product
	 * @param int|float   $qty
	 * @param array       $props
	 * @param float|int   $cost
	 *
	 * @return POItemProduct|\WP_Error The product item added to the PO or the error if the product does not exist.
	 */
	public function add_product( $product, $qty = NULL, $props = array(), $cost = NULL ) {

		if ( ! $product instanceof \WC_Product ) {
			return new \WP_Error( __( "The product doesn't exist", ATUM_PO_TEXT_DOMAIN ) );
		}

		if ( is_null( $qty ) ) {
			$qty = $product->get_min_purchase_quantity();
		}

		if ( ! AtumHelpers::is_atum_product( $product ) ) {
			$product = AtumHelpers::get_atum_product( $product );
		}

		// Get the cost (if passed) or the purchase price (if set).
		$price = ! is_null( $cost ) ? $cost : $product->get_purchase_price();

		if ( empty( $qty ) ) {
			$price = 0.0;
		}

		$subtotal = $total = (float) $price;

		// Apply the supplier's discount by default to the total.
		$total     = $this->apply_supplier_discount( $total );
		$total    *= $qty;
		$subtotal *= $qty;

		// Handle taxes.
		if ( Helpers::may_use_po_taxes() && $product->is_taxable() ) {

			/**
			 * Variable definition
			 *
			 * @var float $subtotal
			 * @var float $total
			 * @var float $subtotal_tax
			 * @var float $total_tax
			 * @var array $total_taxes
			 * @var array $tax_rates
			 */
			extract( $this->add_default_taxes( $subtotal, $total, $product->get_tax_class( 'unfiltered' ) ) );

		}

		$default_props = array(
			'name'         => $product->get_name(),
			'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
			'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
			'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
			'subtotal'     => $subtotal,
			'total'        => $total,
			'quantity'     => $qty,
		);

		if ( ! empty( $total_taxes ) ) {

			$default_props['tax_class'] = $product->get_tax_class(); // TODO: WHAT IF THE PRODUCT HAS A TAX CLASS? HOW DO WE HANDLE IT WITH OUR OWN TAXING SYSTEM?
			$default_props['total_tax'] = wc_round_tax_total( $total_tax );
			$default_props['taxes']     = array(
				'subtotal' => [ $subtotal_tax ],
				'total'    => [ $total_tax ],
			);

		}

		$props      = wp_parse_args( $props, $default_props );
		$item_class = $this->get_items_class( $this->line_item_group );

		/**
		 * Variable definition
		 *
		 * @var POItemProduct $item
		 */
		$item = new $item_class();
		$item->set_props( $props );
		$item->set_backorder_meta();
		$this->check_order_id(); // Make sure the current PO already exists before adding products or it could fail.
		$item->set_atum_order_id( $this->id );

		// Add the tax config meta data, so it can be represented correctly.
		if ( ! empty( $tax_rates ) ) {

			$tax_rate = current( $tax_rates );
			$item->add_meta_data( '_tax_config', array(
				'fieldValue' => $tax_rate['rate'],
				'type'       => 'percentage',
			), TRUE );

		}

		// Add the discount config meta data, so it can be represented correctly.
		if ( $this->supplier_discount > 0 ) {

			$item->add_meta_data( '_discount_config', array(
				'fieldValue' => $this->supplier_discount,
				'type'       => 'percentage',
			), TRUE );

		}

		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Add a fee item to the PO
	 *
	 * @since 0.9.22
	 *
	 * @param \WC_Order_Item_Fee $fee Optional. Fee item to import.
	 *
	 * @return POItemFee  The fee item added to the PO.
	 */
	public function add_fee( \WC_Order_Item_Fee $fee = NULL ) {

		$item_class = $this->get_items_class( 'fee_lines' );

		/**
		 * Variable definition
		 *
		 * @var POItemFee $item
		 */
		$item = new $item_class();
		$this->check_order_id(); // Make sure the current PO already exists before adding products or it could fail.
		$item->set_atum_order_id( $this->id );

		$props = array();

		if ( $fee ) {

			$props['name'] = $fee->get_name();
			$total         = $props['amount'] = $props['total'] = $fee->get_total();
			$subtotal      = $fee->get_amount() ?: $total;

			// Handle taxes.
			if ( Helpers::may_use_po_taxes() ) {

				/**
				 * Variable definition
				 *
				 * @var float $subtotal
				 * @var float $total
				 * @var float $subtotal_tax
				 * @var float $total_tax
				 * @var array $total_taxes
				 * @var array $tax_rates
				 */
				extract( $this->add_default_taxes( $subtotal, $total ) );

			}

			if ( ! empty( $total_taxes ) ) {

				$props['tax_class'] = $fee->get_tax_class();
				$props['total_tax'] = wc_round_tax_total( $total_tax );
				$props['taxes']     = array(
					'subtotal' => [ $subtotal_tax ],
					'total'    => [ $total_tax ],
				);

			}

		}

		$item->set_props( $props );
		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Add a shipping cost item to the PO
	 *
	 * @since 0.9.22
	 *
	 * @param \WC_Order_Item_Shipping $shipping Optional. Shipping cost item to import.
	 *
	 * @return POItemShipping  The shipping cost item added to the PO.
	 */
	public function add_shipping_cost( \WC_Order_Item_Shipping $shipping = NULL ) {

		$item_class = $this->get_items_class( 'shipping_lines' );

		/**
		 * Variable definition
		 *
		 * @var POItemShipping $item
		 */
		$item = new $item_class();
		$this->check_order_id(); // Make sure the current PO already exists before adding products or it could fail.
		$item->set_atum_order_id( $this->id );

		$props = array();

		if ( $shipping ) {

			$props['name'] = $props['mehod_title'] = $shipping->get_name();
			$total         = $subtotal = $props['total'] = $shipping->get_total();

			// Handle taxes.
			if ( Helpers::may_use_po_taxes() ) {

				/**
				 * Variable definition
				 *
				 * @var float $subtotal
				 * @var float $total
				 * @var float $subtotal_tax
				 * @var float $total_tax
				 * @var array $total_taxes
				 * @var array $tax_rates
				 */
				extract( $this->add_default_taxes( $subtotal, $total ) );

			}

			if ( ! empty( $total_taxes ) ) {

				$props['tax_class'] = $shipping->get_tax_class();
				$props['total_tax'] = wc_round_tax_total( $total_tax );
				$props['taxes']     = array(
					'subtotal' => [ $subtotal_tax ],
					'total'    => [ $total_tax ],
				);

			}

		}

		$item->set_props( $props );
		$item->save();
		$this->add_item( $item );

		return $item;

	}

	/**
	 * Apply the supplier's discount to the item's total
	 *
	 * @since 0.9.10
	 *
	 * @param float $total The amount to which the supplier's discount will be applied.
	 *
	 * @return float
	 */
	public function apply_supplier_discount( $total ) {

		$discount = $this->supplier_discount ?: 0;

		if ( $discount > 0 ) {
			// The supplier discount is a percentage.
			$total -= ( $total * (float) $discount ) / 100;
		}

		return $total;

	}

	/**
	 * Add the default taxes to any order item
	 *
	 * @since 0.9.10
	 *
	 * @param float  $subtotal
	 * @param float  $total
	 * @param string $tax_class
	 *
	 * @return array
	 */
	public function add_default_taxes( $subtotal, $total, $tax_class = '' ) {

		$tax_rates = [];

		// If the PO has a tax rate set for the supplier, use it.
		if ( $this->supplier_tax_rate ) {

			// TODO: WE SHOULD IMPROVE RATES HANDLING (SOMETHING LIKE WC RATES UI). FOR PO PRO?
			$tax_rates = array(
				array(
					'rate'     => $this->supplier_tax_rate,
					'label'    => 'SUPPL',
					'shipping' => 'no',
					'compound' => 'no',
				),
			);

		}
		// Just use the default WC configuration (ATUM free way).
		elseif ( 'yes' === AtumHelpers::get_option( 'po_use_system_taxes', 'yes' ) ) {
			$tax_rates = \WC_Tax::get_base_tax_rates( $tax_class );
		}

		$total_taxes  = [];
		$subtotal_tax = $total_tax = 0;

		if ( ! empty( $tax_rates ) ) {

			$price_includes_tax = 'yes' === AtumHelpers::get_option( 'po_purchase_price_including_taxes', wc_prices_include_tax() ? 'yes' : 'no' );
			$total_taxes        = \WC_Tax::calc_tax( $total, $tax_rates, $price_includes_tax );
			$total_tax          = array_sum( $total_taxes );
			$subtotal_taxes     = \WC_Tax::calc_tax( $subtotal, $tax_rates, $price_includes_tax );
			$subtotal_tax       = array_sum( $subtotal_taxes );

			if ( $price_includes_tax ) {
				$total    = \WC_Tax::round( $total - $total_tax );
				$subtotal = \WC_Tax::round( $subtotal - $subtotal_tax );
			}
			else {
				$total    = \WC_Tax::round( $total );
				$subtotal = \WC_Tax::round( $subtotal );
			}

		}
		else {
			$total    = \WC_Tax::round( $total );
			$subtotal = \WC_Tax::round( $subtotal );
		}

		return compact( 'subtotal', 'total', 'total_taxes', 'subtotal_tax', 'total_tax', 'tax_rates' );

	}

	/**
	 * Add a tax item to the PO
	 *
	 * @since 0.9.9
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
	 * @return bool  Not applicable
	 */
	public function add_tax( array $values, \WC_Order_Item_Tax $tax = NULL ) {
		return FALSE;
	}

	/**
	 * Calculate totals by looking at the contents of the ATUM Order
	 * Stores the totals and returns the ATUM order's final total
	 *
	 * @since 0.9.21
	 *
	 * @param bool $and_taxes Optional. Calc taxes if true.
	 *
	 * @return float Calculated grand total
	 */
	public function calculate_totals( $and_taxes = FALSE ) {

		// Never let WC to calculate taxes in POs.
		return parent::calculate_totals( FALSE );
	}

	/**
	 * Calculate taxes for all line items and shipping, and store the totals and tax rows
	 *
	 * @since 0.9.9
	 *
	 * @param array $args Optional. To pass things like location.
	 */
	public function calculate_taxes( $args = array() ) {
		// We are allowing to set manual taxes, so we don't need to recalculate them automatically for now...
	}

	/**
	 * Update tax lines for the ATUM Order based on the line item taxes themselves
	 *
	 * @since 0.9.9
	 */
	public function update_taxes() {

		$cart_taxes = $shipping_taxes = array();

		foreach ( $this->get_items( [ $this->line_item_type, 'fee' ] ) as $item ) {

			$taxes = $item->get_taxes();

			foreach ( $taxes['total'] as $tax_rate_id => $tax ) {
				$cart_taxes[ $tax_rate_id ] = isset( $cart_taxes[ $tax_rate_id ] ) ? $cart_taxes[ $tax_rate_id ] + (float) $tax : (float) $tax;
			}

		}

		foreach ( $this->get_shipping_methods() as $item ) {

			$taxes = $item->get_taxes();

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
	 * Save ATUM Order items. Uses the CRUD
	 *
	 * @since 0.7.2
	 *
	 * @param array $items_data Order items to save.
	 */
	public function save_order_items( $items_data ) {

		// Allow other plugins to check changes in ATUM Order items before they are saved.
		do_action( 'atum/orders/before_save_items', $this, $items_data );  // Use the ATUM hook here to preserve compatibility.

		// Line items.
		if ( isset( $items_data['atum_order_item_id'] ) ) {

			$data_keys = array(
				'atum_order_item_cost'     => 0,
				'atum_order_item_discount' => 0,
				'atum_order_item_qty'      => 0,
				'atum_order_item_total'    => 0,
				'atum_order_item_tax'      => 0,
			);

			foreach ( $items_data['atum_order_item_id'] as $item_id ) {

				/**
				 * Variable definition
				 *
				 * @var POItemProduct $item
				 */
				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				if ( ! $item instanceof POItemProduct ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $items_data[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items_data[ $key ][ $item_id ] ) ) : $default;
				}

				if ( '0' === $item_data['atum_order_item_qty'] ) {
					$item->delete();
					continue;
				}

				$quantity       = wc_stock_amount( $item_data['atum_order_item_qty'] );
				$line_total     = (float) $item_data['atum_order_item_total']; // The total comes with the discount already applied.
				$line_subtotal  = $line_total + (float) $item_data['atum_order_item_discount'] * $quantity;
				$line_tax_total = wc_round_tax_total( $item_data['atum_order_item_tax'] ); // The tax was calculated after applying discounts.

				$item->set_props( array(
					'quantity' => $quantity,
					'total'    => $line_total,
					'subtotal' => $line_subtotal < $line_total ? $line_total : $line_subtotal,
					'taxes'    => array(
						'total'    => [ $line_tax_total ],
						'subtotal' => [ $line_subtotal < $line_total && $line_total ? wc_round_tax_total( ( $line_subtotal * $line_tax_total ) / $line_total ) : $line_tax_total ],
					),
				) );

				if ( isset( $items_data['meta_key'][ $item_id ], $items_data['meta_value'][ $item_id ] ) ) {
					$this->save_item_meta( $item, $items_data['meta_key'][ $item_id ], $items_data['meta_value'][ $item_id ] );
				}

				$this->add_item( $item );
				$this->add_item_config_data( $item, $items_data );
				$item->save();

			}

		}

		// Shipping items.
		if ( isset( $items_data['atum_order_shipping_id'] ) ) {

			$data_keys = array(
				'atum_order_item_name' => '',
				'atum_order_item_cost' => 0,
				'atum_order_item_tax'  => 0,
			);

			foreach ( $items_data['atum_order_shipping_id'] as $item_id ) {

				/**
				 * Variable definition
				 *
				 * @var AtumOrderItemShipping
				 */
				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				if ( ! $item instanceof AtumOrderItemShipping ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $items_data[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items_data[ $key ][ $item_id ] ) ) : $default;
				}

				$item->set_props( array(
					'method_title' => $item_data['atum_order_item_name'],
					'total'        => $item_data['atum_order_item_cost'],
					'taxes'        => array(
						'total' => [ $item_data['atum_order_item_tax'] ], // It's awaiting an array.
					),
				) );

				if ( isset( $items_data['meta_key'][ $item_id ], $items_data['meta_value'][ $item_id ] ) ) {
					$this->save_item_meta( $item, $items_data['meta_key'][ $item_id ], $items_data['meta_value'][ $item_id ] );
				}

				$this->add_item( $item );
				$this->add_item_config_data( $item, $items_data );
				$item->save();

			}

		}

		// Fee items.
		if ( isset( $items_data['atum_order_fee_id'] ) ) {

			$data_keys = array(
				'atum_order_item_name' => '',
				'atum_order_item_cost' => 0,
				'atum_order_item_tax'  => 0,
			);

			foreach ( $items_data['atum_order_fee_id'] as $item_id ) {

				/**
				 * Variable definition
				 *
				 * @var AtumOrderItemFee
				 */
				if ( ! $item = $this->get_atum_order_item( absint( $item_id ) ) ) {
					continue;
				}

				if ( ! $item instanceof AtumOrderItemFee ) {
					continue;
				}

				$item_data = array();

				foreach ( $data_keys as $key => $default ) {
					$item_data[ $key ] = isset( $items_data[ $key ][ $item_id ] ) ? wc_clean( wp_unslash( $items_data[ $key ][ $item_id ] ) ) : $default;
				}

				$item->set_props( array(
					'name'  => $item_data['atum_order_item_name'],
					'total' => $item_data['atum_order_item_cost'],
					'taxes' => array(
						'total' => [ $item_data['atum_order_item_tax'] ],
					),
				) );

				$this->add_item( $item );
				$this->add_item_config_data( $item, $items_data );
				$item->save();

			}

		}

		// Updates tax totals.
		$this->update_taxes();

		// Calc totals - this also triggers save.
		$this->calculate_totals();

		// Inform other plugins that the items have been saved.
		do_action( 'atum/orders/after_save_items', $this, $items_data ); // Use the ATUM hook here to preserve compatibility.

	}

	/**
	 * Add the config data to the item (if any)
	 *
	 * @since 0.9.10
	 *
	 * @param POItemProduct|POItemShipping|POItemFee $item
	 * @param array                                  $items_data
	 */
	public function add_item_config_data( $item, $items_data ) {

		$item_id = $item->get_id();

		if ( ! empty( $items_data['atum_order_item_discount_config'] ) && ! empty( $items_data['atum_order_item_discount_config'][ $item_id ] ) ) {

			$discount_config = $items_data['atum_order_item_discount_config'][ $item_id ];

			if ( ! is_array( $discount_config ) ) {
				$discount_config = (array) json_decode( stripslashes( $discount_config ), TRUE );
			}

			$discount_config = array_map( 'sanitize_text_field', $discount_config );
			$item->add_meta_data( '_discount_config', $discount_config, TRUE );

		}

		if ( ! empty( $items_data['atum_order_item_tax_config'] ) && ! empty( $items_data['atum_order_item_tax_config'][ $item_id ] ) ) {

			$tax_config = $items_data['atum_order_item_tax_config'][ $item_id ];

			if ( ! is_array( $tax_config ) ) {
				$tax_config = (array) json_decode( stripslashes( $tax_config ), TRUE );
			}

			$tax_config = array_map( 'sanitize_text_field', $tax_config );
			$item->add_meta_data( '_tax_config', $tax_config, TRUE );

		}

	}

	/**
	 * Add sever metadata to an item
	 *
	 * @since 0.9.10
	 *
	 * @param POItemProduct|POItemShipping|POItemFee $item
	 * @param array                                  $items_data
	 */
	public function add_bulk_item_data( $item, $items_data ) {

		if ( ! $items_data ) {
			return;
		}

		foreach ( $items_data as $meta_data ) {
			$item->add_meta_data( $meta_data->key, $meta_data->value );
		}

		$item->save_meta_data();

	}

	/**
	 * Get item subtotal - this is the cost before discount
	 *
	 * @since 1.1.4
	 *
	 * @param AtumOrderItemProduct $item
	 * @param bool                 $inc_tax
	 * @param bool                 $round
	 *
	 * @return float
	 */
	public function get_item_subtotal( $item, $inc_tax = FALSE, $round = TRUE ) {

		$subtotal = 0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) ) {

			$qty = ! empty( $item->get_quantity() ) ? $item->get_quantity() : 1;

			if ( $inc_tax && Helpers::may_use_po_taxes() ) {
				$subtotal = ( (float) $item->get_subtotal() + (float) $item->get_subtotal_tax() ) / $qty;
			}
			else {
				$subtotal = (float) $item->get_subtotal() / $qty;
			}

			$subtotal = $round ? number_format( (float) $subtotal, $this->price_num_decimals, $this->price_decimal_sep, $this->price_thousand_sep ) : $subtotal;

		}

		return apply_filters( 'atum/orders/amount_item_subtotal', $subtotal, $this, $item, $inc_tax, $round );

	}

	/**
	 * Get total taxes
	 *
	 * @since 0.7.6
	 *
	 * @return float
	 */
	public function get_tax_totals() {

		// Actually, we only support one tax per line.
		return $this->total_tax;

	}

	/**
	 * Gets ATUM order's total - formatted for display
	 * NOTE: this is just the same method as the parent class but using the format_price method for price formatting.
	 *
	 * @since 0.9.17
	 *
	 * @param  string $tax_display  Optional. Type of tax display.
	 * @param  bool   $subtotal     Optional. If should return the tax free Subtotal instead.
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $subtotal = FALSE ) {

		$amount          = $subtotal ? $this->get_subtotal() : $this->total;
		$formatted_total = $this->format_price( $amount );
		$tax_string      = '';

		// Tax for inclusive prices.
		if ( wc_tax_enabled() && 'incl' === $tax_display && ! $subtotal ) {

			$tax_string_array = array();

			// TODO: FOR NOW IS NOT POSSIBLE TO SHOW ITEMIZED TAXES IN POs AS "get_tax_totals" RETURNS A SINGLE VALUE.
			/*if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {

				foreach ( $this->get_tax_totals() as $code => $tax ) {
					$tax_amount         = $tax->formatted_amount;
					$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
				}

			}
			else {*/
				$tax_amount         = $this->total_tax;
				$tax_string_array[] = sprintf( '%s %s', $this->format_price( $tax_amount ), WC()->countries->tax_or_vat() );
			//}

			if ( ! empty( $tax_string_array ) ) {
				/* translators: a list of comma-separated taxes */
				$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', ATUM_PO_TEXT_DOMAIN ), implode( ', ', $tax_string_array ) ) . '</small>';
			}

		}

		$formatted_total .= $tax_string;

		return apply_filters( 'atum/purchase_orders_pro/get_formatted_total', $formatted_total, $this, $tax_display, $subtotal );

	}

	/**
	 * Get the total amount of items in this PO
	 *
	 * @since 0.9.2
	 *
	 * @return int|float
	 */
	public function get_ordered_items_total() {

		$ordered_total = 0;

		if ( ! empty( $this->items['line_items'] ) ) {

			foreach ( $this->items['line_items'] as $line_item ) {
				/**
				 * Variable definition
				 *
				 * @var POItemProduct $line_item
				 */
				$ordered_total += $line_item->get_quantity();
			}

		}

		return $ordered_total;
	}

	/**
	 * Checks whether this PO can be edited
	 *
	 * @since 0.9.15
	 *
	 * @return bool
	 */
	public function is_editable() {

		$status = $this->status;

		return apply_filters( 'atum/purchase_orders_pro/is_editable', (
			( ! $status || 'auto-draft' === $status || array_key_exists( $status, Globals::get_statuses() ) ) &&
			! in_array( $status, [ 'atum_cancelled', 'trash', 'atum_returned', 'atum_received' ] )
		) );

	}

	/**
	 * Checks whether this PO is a "Returning PO"
	 *
	 * @since 1.1.3
	 *
	 * @return bool
	 */
	public function is_returning() {

		$status = $this->status;

		return apply_filters( 'atum/purchase_orders_pro/is_returning', in_array( $status, [ 'atum_returning', 'atum_returned' ] ) );

	}

	/**
	 * Checks whether this PO is cancelled
	 *
	 * @since 1.1.3
	 *
	 * @return bool
	 */
	public function is_cancelled() {

		$status = $this->status;

		return apply_filters( 'atum/purchase_orders_pro/is_cancelled', 'atum_cancelled' === $status );

	}

	/**
	 * Check whether this PO is in "due" status
	 *
	 * @since 0.9.16
	 *
	 * @return bool
	 */
	public function is_due() {
		return in_array( $this->status, Globals::get_due_statuses() );
	}

	/**
	 * Get the price format depending on the currency position.
	 *
	 * @return string
	 */
	public function get_price_format() {

		$currency_pos = $this->currency_pos ?: get_option( 'woocommerce_currency_pos', 'left' );
		$format       = '%1$s%2$s';

		switch ( $currency_pos ) {
			case 'left':
				$format = '%1$s%2$s';
				break;
			case 'right':
				$format = '%2$s%1$s';
				break;
			case 'left_space':
				$format = '%1$s&nbsp;%2$s';
				break;
			case 'right_space':
				$format = '%2$s&nbsp;%1$s';
				break;
		}

		return apply_filters( 'atum/purchase_orders_pro/price_format', $format, $currency_pos );

	}

	/**
	 * Wrapper around wc_price function but using the current PO's currency options
	 *
	 * @since 0.9.17
	 *
	 * @param int|float $price
	 *
	 * @return string
	 */
	public function format_price( $price ) {

		return wc_price( $price, [
			'currency'           => $this->currency,
			'decimal_separator'  => $this->price_decimal_sep,
			'thousand_separator' => $this->price_thousand_sep,
			'decimals'           => $this->price_num_decimals,
			'price_format'       => $this->get_price_format(),
		] );

	}

	/**
	 * Get the PO items that were still not added to stock completely
	 *
	 * @since 0.9.24
	 *
	 * @return POItemProduct[]
	 */
	public function get_po_items_not_added_to_stock() {

		$deliveries           = Deliveries::get_po_orders( $this->id );
		$added_to_stock_items = $not_added_to_stock_items = [];

		// Get all the added delivery items across all deliveries.
		foreach ( $deliveries as $delivery ) {

			$delivery_items = $delivery->get_items();

			/**
			 * Variable definition
			 *
			 * @var DeliveryItemProduct $delivery_item
			 */
			foreach ( $delivery_items as $delivery_item ) {

				$po_item_id    = $delivery_item->get_po_item_id();
				$stock_changed = apply_filters( 'atum/purchase_orders_pro/po_items_not_added_to_stock/stock_changed', $delivery_item->get_stock_changed(), $delivery_item, $delivery );

				if ( 'yes' === $stock_changed ) {

					$qty = $delivery_item->get_quantity();

					if ( array_key_exists( $po_item_id, $added_to_stock_items ) ) {
						$added_to_stock_items[ $po_item_id ] += $qty;
					}
					else {
						$added_to_stock_items[ $po_item_id ] = $qty;
					}

				}

			}

		}

		$po_items = $this->get_items();

		foreach ( $po_items as $po_item ) {

			$po_item_id = $po_item->get_id();

			if ( ! array_key_exists( $po_item_id, $added_to_stock_items ) || $added_to_stock_items[ $po_item_id ] !== $po_item->get_quantity() ) {
				$not_added_to_stock_items[] = $po_item;
			}

		}

		return $not_added_to_stock_items;

	}

	/**
	 * Getter to collect all the Purchase Order PRO data
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_data() {

		// Prepare the data array based on the WC_Order_Data structure.
		$data = parent::get_data();

		$po_data   = [];

		foreach ( $this->extra_meta as $meta_key => $meta_value ) {

			$meta_key_name = ltrim( $meta_key, '_' );
			$method        = "set_$meta_key_name";
			$value         = array_key_exists( $meta_key, $this->meta ) ? $this->meta[ $meta_key ] : $meta_value;

			if ( is_callable( array( $this, $method ) ) && array_key_exists( $meta_key_name, $this->meta ) ) {
				switch ( $meta_key_name ) {
					case 'has_taxes_enabled':
						$po_data[ $meta_key_name ] = (bool) $value;
						break;
					case 'price_num_decimals':
						$po_data[ $meta_key_name ] = absint( $value );
						break;
					case 'supplier_discount':
					case 'exchange_rate':
						$po_data[ $meta_key_name ] = floatval( $value );
						break;
					default:
						$po_data[ $meta_key_name ] = $value;
						break;
				}
			}

		}

		return array_merge( $data, $po_data );

	}


	/**********
	 * SETTERS
	 **********/

	/**
	 * Setter for the PO's delivery date
	 *
	 * @since 0.1.0
	 *
	 * @param string $delivery_date
	 * @param bool   $skip_change
	 */
	public function set_delivery_date( $delivery_date, $skip_change = FALSE ) {

		$delivery_date = wc_clean( $delivery_date );

		if ( $delivery_date !== $this->delivery_date ) {

			if ( ! $skip_change ) {
				$this->register_change( 'delivery_date' );
			}

			$this->set_meta( 'delivery_date', $delivery_date );
		}

	}

	/**
	 * Setter for the PO's sales order number
	 *
	 * @since 0.1.0
	 *
	 * @param string $sales_order_number
	 * @param bool   $skip_change
	 */
	public function set_sales_order_number( $sales_order_number, $skip_change = FALSE ) {

		$sales_order_number = wc_clean( $sales_order_number );

		if ( $sales_order_number !== $this->sales_order_number ) {

			if ( ! $skip_change ) {
				$this->register_change( 'sales_order_number' );
			}

			$this->set_meta( 'sales_order_number', $sales_order_number );
		}

	}

	/**
	 * Setter for the PO's customer name
	 *
	 * @since 0.1.0
	 *
	 * @param string $customer_name
	 * @param bool   $skip_change
	 */
	public function set_customer_name( $customer_name, $skip_change = FALSE ) {

		$customer_name = wc_clean( $customer_name );

		if ( $customer_name !== $this->customer_name ) {

			if ( ! $skip_change ) {
				$this->register_change( 'customer_name' );
			}

			$this->set_meta( 'customer_name', $customer_name );
		}

	}

	/**
	 * Setter for the PO's PDF template
	 *
	 * @since 0.1.0
	 *
	 * @param string $pdf_template
	 * @param bool   $skip_change
	 */
	public function set_pdf_template( $pdf_template, $skip_change = FALSE ) {

		$pdf_template = wc_clean( $pdf_template );

		if ( $pdf_template !== $this->pdf_template ) {

			if ( ! $skip_change ) {
				$this->register_change( 'pdf_template' );
			}

			$this->set_meta( 'pdf_template', $pdf_template );
		}

	}

	/**
	 * Setter for the PO's email template
	 *
	 * @since 0.1.0
	 *
	 * @param string $email_template
	 * @param bool   $skip_change
	 */
	public function set_email_template( $email_template, $skip_change = FALSE ) {

		$email_template = wc_clean( $email_template );

		if ( $email_template !== $this->email_template ) {

			if ( ! $skip_change ) {
				$this->register_change( 'email_template' );
			}

			$this->set_meta( 'email_template', $email_template );
		}

	}

	/**
	 * Setter for the PO's requisitioner (name of the person creating the PO)
	 *
	 * @since 0.1.0
	 *
	 * @param int  $requisitioner
	 * @param bool $skip_change
	 */
	public function set_requisitioner( $requisitioner, $skip_change = FALSE ) {

		$requisitioner = absint( $requisitioner );

		if ( absint( $this->requisitioner ) !== $requisitioner ) {

			if ( ! $skip_change ) {
				$this->register_change( 'requisitioner' );
			}

			$this->set_meta( 'requisitioner', $requisitioner );
		}

	}

	/**
	 * Setter for the PO's ship via
	 *
	 * @since 0.1.0
	 *
	 * @param string $ship_via
	 * @param bool   $skip_change
	 */
	public function set_ship_via( $ship_via, $skip_change = FALSE ) {

		$ship_via = array_key_exists( $ship_via, Globals::get_shipping_methods() ) ? $ship_via : '';

		if ( $ship_via !== $this->ship_via ) {

			if ( ! $skip_change ) {
				$this->register_change( 'ship_via' );
			}

			$this->set_meta( 'ship_via', $ship_via );
		}

	}

	/**
	 * Setter for the PO's F.O.B.
	 *
	 * @since 0.9.16
	 *
	 * @param string $fob
	 * @param bool   $skip_change
	 */
	public function set_fob( $fob, $skip_change = FALSE ) {

		$fob = wc_clean( $fob );

		if ( $fob !== $this->fob ) {

			if ( ! $skip_change ) {
				$this->register_change( 'fob' );
			}

			$this->set_meta( 'fob', $fob );
		}

	}

	/**
	 * Setter for the PO's ships from
	 *
	 * @since 0.1.0
	 *
	 * @param string $ships_from
	 * @param bool   $skip_change
	 */
	public function set_ships_from( $ships_from, $skip_change = FALSE ) {

		$ships_from = wc_clean( $ships_from );

		if ( $ships_from !== $this->ships_from ) {

			if ( ! $skip_change ) {
				$this->register_change( 'ships_from' );
			}

			$this->set_meta( 'ships_from', $ships_from );
		}

	}

	/**
	 * Setter for the PO's delivery terms
	 *
	 * @since 0.7.1
	 *
	 * @param string $delivery_terms
	 * @param bool   $skip_change
	 */
	public function set_delivery_terms( $delivery_terms, $skip_change = FALSE ) {

		$delivery_terms = wp_kses_post( $delivery_terms );

		if ( $delivery_terms !== $this->delivery_terms ) {

			if ( ! $skip_change ) {
				$this->register_change( 'delivery_terms' );
			}

			$this->set_meta( 'delivery_terms', $delivery_terms );
		}

	}

	/**
	 * Setter for the PO's supplier code
	 *
	 * @since 0.1.0
	 *
	 * @param string $code
	 * @param bool   $skip_change
	 */
	public function set_supplier_code( $code, $skip_change = FALSE ) {

		$code = wc_clean( $code );

		if ( $code !== $this->supplier_code ) {

			if ( ! $skip_change ) {
				$this->register_change( 'supplier_code' );
			}

			$this->set_meta( 'supplier_code', $code );

		}

	}

	/**
	 * Setter for the PO's supplier reference
	 *
	 * @since 0.1.0
	 *
	 * @param string $reference
	 * @param bool   $skip_change
	 */
	public function set_supplier_reference( $reference, $skip_change = FALSE ) {

		$reference = wc_clean( $reference );

		if ( $reference !== $this->supplier_reference ) {

			if ( ! $skip_change ) {
				$this->register_change( 'supplier_reference' );
			}

			$this->set_meta( 'supplier_reference', $reference );
		}

	}

	/**
	 * Setter for the PO's supplier discount
	 *
	 * @since 0.1.0
	 *
	 * @param float $discount
	 * @param bool  $skip_change
	 */
	public function set_supplier_discount( $discount, $skip_change = FALSE ) {

		// NOTE: This field is being initially auto-generated from the supplier's
		// but as could change in the future, and it's needed for calculations we must save it per PO (always).
		$discount = (float) $discount;

		// If it has decimals, allow max 2.
		if ( floor( $discount ) !== $discount ) {
			$discount = wc_format_decimal( $discount, 2, TRUE );
		}

		if ( $discount !== (float) $this->supplier_discount ) {

			if ( ! $skip_change ) {
				$this->register_change( 'supplier_discount' );
			}

			$this->set_meta( 'supplier_discount', $discount );
		}

	}

	/**
	 * Setter for the PO's supplier tax rate
	 *
	 * @since 0.1.0
	 *
	 * @param int|float $tax_rate
	 * @param bool      $skip_change
	 */
	public function set_supplier_tax_rate( $tax_rate, $skip_change = FALSE ) {

		// NOTE: This field is being initially auto-generated from the supplier's
		// but as could change in the future, and it's needed for calculations we must save it per PO (always).
		$tax_rate = (float) $tax_rate;

		// If it has decimals, allow max 2.
		if ( floor( $tax_rate ) !== $tax_rate ) {
			$tax_rate = wc_format_decimal( $tax_rate, 2, TRUE );
		}

		if ( $tax_rate !== (float) $this->supplier_tax_rate ) {

			if ( ! $skip_change ) {
				$this->register_change( 'supplier_tax_rate' );
			}

			$this->set_meta( 'supplier_tax_rate', $tax_rate );
		}

	}

	/**
	 * Setter for the PO's supplier currency
	 *
	 * @since 0.1.0
	 *
	 * @param string $currency
	 * @param bool   $skip_change
	 */
	public function set_supplier_currency( $currency, $skip_change = FALSE ) {

		// NOTE: This field is being initially auto-generated from the supplier's
		// but as could change in the future, and it's needed for calculations we must save it per PO (always).
		$currency = wc_clean( $currency );

		if ( ! array_key_exists( $currency, get_woocommerce_currencies() ) ) {
			$currency = get_woocommerce_currency();
		}

		if ( $currency !== $this->supplier_currency ) {

			$this->set_meta( 'supplier_currency', $currency );

			// Save it to the AtumOrderModel's currency prop too for backwards compatibility.
			$this->set_meta( 'currency', $currency );

			if ( ! $skip_change ) {
				$this->register_change( 'supplier_currency' );
				$this->register_change( 'currency' );
			}

		}

	}

	/**
	 * Setter for the PO's warehouse (Warehouses add-on required)
	 *
	 * @since 0.1.0
	 *
	 * @param string $warehouse
	 * @param bool   $skip_change
	 */
	public function set_warehouse( $warehouse, $skip_change = FALSE ) {

		$warehouse = wc_clean( $warehouse );

		if ( $warehouse !== $this->warehouse ) {

			if ( ! $skip_change ) {
				$this->register_change( 'warehouse' );
			}

			$this->set_meta( 'warehouse', $warehouse );
		}

	}

	/**
	 * Setter for the PO's delivery to warehouse (Warehouses add-on required)
	 *
	 * @since 0.1.0
	 *
	 * @param string|bool $delivery_to_warehouse
	 * @param bool        $skip_change
	 */
	public function set_delivery_to_warehouse( $delivery_to_warehouse, $skip_change = FALSE ) {

		$delivery_to_warehouse = wc_bool_to_string( $delivery_to_warehouse );

		if ( $delivery_to_warehouse !== $this->delivery_to_warehouse ) {

			if ( ! $skip_change ) {
				$this->register_change( 'delivery_warehouse' );
			}

			$this->set_meta( 'delivery_warehouse', $delivery_to_warehouse );
		}

	}

	/**
	 * Setter for the PO purchaser's name
	 *
	 * @since 0.1.0
	 *
	 * @param string $purchaser_name
	 * @param bool   $skip_change
	 */
	public function set_purchaser_name( $purchaser_name, $skip_change = FALSE ) {

		$purchaser_name = wc_clean( $purchaser_name );

		if ( $purchaser_name !== $this->purchaser_name ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_name' );
			}

			$this->set_meta( 'purchaser_name', $purchaser_name );
		}

	}

	/**
	 * Setter for the PO purchaser's address
	 *
	 * @since 0.1.0
	 *
	 * @param string $purchaser_address
	 * @param bool   $skip_change
	 */
	public function set_purchaser_address( $purchaser_address, $skip_change = FALSE ) {

		$purchaser_address = wc_clean( $purchaser_address );

		if ( $purchaser_address !== $this->purchaser_address ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_address' );
			}

			$this->set_meta( 'purchaser_address', $purchaser_address );
		}

	}

	/**
	 * Setter for the PO purchaser's address 2
	 *
	 * @since 1.0.3
	 *
	 * @param string $purchaser_address_2
	 * @param bool   $skip_change
	 */
	public function set_purchaser_address_2( $purchaser_address_2, $skip_change = FALSE ) {

		$purchaser_address_2 = wc_clean( $purchaser_address_2 );

		if ( $purchaser_address_2 !== $this->purchaser_address_2 ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_address_2' );
			}

			$this->set_meta( 'purchaser_address_2', $purchaser_address_2 );
		}

	}

	/**
	 * Setter for the PO purchaser's city
	 *
	 * @since 0.7.3
	 *
	 * @param string $purchaser_city
	 * @param bool   $skip_change
	 */
	public function set_purchaser_city( $purchaser_city, $skip_change = FALSE ) {

		$purchaser_city = wc_clean( $purchaser_city );

		if ( $purchaser_city !== $this->purchaser_city ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_city' );
			}

			$this->set_meta( 'purchaser_city', $purchaser_city );
		}

	}

	/**
	 * Setter for the PO purchaser's state
	 *
	 * @since 0.1.0
	 *
	 * @param string $purchaser_state
	 * @param bool   $skip_change
	 */
	public function set_purchaser_state( $purchaser_state, $skip_change = FALSE ) {

		$purchaser_state = wc_clean( $purchaser_state );

		if ( $purchaser_state !== $this->purchaser_state ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_state' );
			}

			$this->set_meta( 'purchaser_state', $purchaser_state );
		}

	}

	/**
	 * Setter for the PO purchaser's postal code
	 *
	 * @since 0.1.0
	 *
	 * @param string $purchaser_postal_code
	 * @param bool   $skip_change
	 */
	public function set_purchaser_postal_code( $purchaser_postal_code, $skip_change = FALSE ) {

		$purchaser_postal_code = wc_clean( $purchaser_postal_code );

		if ( $purchaser_postal_code !== $this->purchaser_postal_code ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_postal_code' );
			}

			$this->set_meta( 'purchaser_postal_code', $purchaser_postal_code );
		}

	}

	/**
	 * Setter for the PO purchaser's country
	 *
	 * @since 0.1.0
	 *
	 * @param string $purchaser_country
	 * @param bool   $skip_change
	 */
	public function set_purchaser_country( $purchaser_country, $skip_change = FALSE ) {

		$country_obj       = new \WC_Countries();
		$purchaser_country = array_key_exists( $purchaser_country, $country_obj->get_countries() ) ? $purchaser_country : '';

		if ( $purchaser_country !== $this->purchaser_country ) {

			if ( ! $skip_change ) {
				$this->register_change( 'purchaser_country' );
			}

			$this->set_meta( 'purchaser_country', $purchaser_country );
		}

	}

	/**
	 * Set the number for this PO
	 *
	 * @since 0.8.8
	 *
	 * @param string $number      Optional. Leave it empty to auto-generate a new number.
	 * @param bool   $skip_change
	 */
	public function set_number( $number = '', $skip_change = FALSE ) {

		if ( $number ) {

			$number = wc_clean( $number );

			if ( $number !== $this->number ) {
				$this->set_meta( 'number', $number );

				if ( ! $skip_change ) {
					$this->register_change( 'number' );
				}
			}

			return;
		}

		if ( $this->number ) {
			return; // Already set.
		}

		// POst IDs as numbers.
		if ( 'ids' === AtumHelpers::get_option( 'po_numbering_system', 'ids' ) ) {

			$this->set_meta( 'number', $this->id );

			if ( ! $skip_change ) {
				$this->register_change( 'number' );
			}

			return;

		}

		$po_number = $this->generate_next_po_number();

		if ( ! is_wp_error( $po_number ) ) {

			$this->set_meta( 'number', $po_number );

			if ( ! $skip_change ) {
				$this->register_change( 'number' );
			}
			$this->save_meta();

		}

	}

	/**
	 * Generate the next custom PO number and increment the counter in Settings
	 *
	 * @since 1.0.1
	 *
	 * @return string|\WP_Error
	 */
	public function generate_next_po_number() {

		if ( 'custom' !== AtumHelpers::get_option( 'po_numbering_system', 'ids' ) ) {
			return new \WP_Error( 'custom_numbers_disabled', __( 'The custom numbering system must be enable from setttings to auto-generate new numbers', ATUM_PO_TEXT_DOMAIN ) );
		}

		// Custom pattern for numbers.
		$pattern   = AtumHelpers::get_option( 'po_numbering_custom_pattern', '' );
		$pattern   = $pattern ?: 'PO{counter}';
		$po_number = $pattern;

		// Get all the tags between curly braces.
		preg_match_all( '/{(.*?)}/', $pattern, $matches );

		// Replace all the tags.
		if ( ! empty( $matches[0] ) ) {

			// NOTE: For now we don't support supplier-related tags because the number is being set when the PO is created
			// and it could have no supplier assigned yet.
			// {supplier_counter} tag.
			/*if ( in_array( '{supplier_counter}', $matches[0] ) ) {

				$supplier_counter = '';

				if ( $this->supplier_obj ) {
					$supplier_counter = absint( $this->supplier_obj->po_counter );
					$supplier_counter = apply_filters( 'atum/purchase_orders_pro/current_custom_supplier_counter', str_pad( $supplier_counter, 4, '0', STR_PAD_LEFT ), $supplier_counter, $this );
				}

				$po_number = str_replace( '{supplier_counter}', $supplier_counter, $po_number );

			}

			// {supplier_code} tag.
			if ( in_array( '{supplier_code}', $matches[0] ) ) {
				$po_number = str_replace( '{supplier_code}', $this->supplier_code, $po_number );
			}*/

			// {year} tag.
			if ( in_array( '{year}', $matches[0] ) ) {
				$po_number = str_replace( '{year}', date_i18n( 'Y' ), $po_number );
			}

			// {date:format} tag.
			foreach ( $matches[1] as $index => $tag ) {

				if ( strpos( $tag, 'date:' ) === 0 ) {

					// Get the format.
					$date_format = substr( $tag, 5 );
					$po_number   = str_replace( $matches[0][ $index ], date_i18n( $date_format ), $po_number );

				}

			}

			// {counter} tag.
			// NOTE: It must be replaced the last one in order to check for existing numbers.
			if ( in_array( '{counter}', $matches[0] ) ) {

				$cipher_length   = absint( AtumHelpers::get_option( 'po_numbering_custom_zeros', 4 ) );
				$current_counter = absint( AtumHelpers::get_option( 'po_numbering_custom_counter', 1 ) );
				$counter_str     = str_pad( $current_counter, $cipher_length, '0', STR_PAD_LEFT );

				// Before setting the counter, make sure it isn't already used.
				do {

					$temp_po_number = str_replace( '{counter}', $counter_str, $po_number );
					$found_numbers  = Helpers::find_po_number( $temp_po_number, $this->id );

					if ( ! empty( $found_numbers ) ) {
						++$current_counter;
						$counter_str = str_pad( $current_counter, $cipher_length, '0', STR_PAD_LEFT );
					}

				} while ( ! empty( $found_numbers ) );

				$po_number = str_replace( '{counter}', $counter_str, $po_number );

				// Increase the global counter.
				AtumHelpers::update_atum_setting( 'po_numbering_custom_counter', ++$current_counter );

			}

		}

		return apply_filters( 'atum/purchase_orders_pro/custom_number', $po_number, $pattern, $this );

	}

	/**
	 * Setter for the PO's files
	 *
	 * @since 0.9.7
	 *
	 * @param array $files
	 * @param bool  $skip_change
	 */
	public function set_files( $files, $skip_change = FALSE ) {

		if ( ! is_array( $files ) ) {

			$files = maybe_unserialize( $files );

			if ( ! is_array( $files ) ) {
				$files = [];
			}

		}

		if ( maybe_serialize( $files ) !== maybe_serialize( $this->files ) ) {

			if ( ! $skip_change ) {
				$this->register_change( 'files' );
			}

			$this->set_meta( 'files', $files );

		}

	}

	/**
	 * Setter for the PO's meta boxes sizes
	 *
	 * @since 0.9.12
	 *
	 * @param array $sizes
	 * @param bool  $skip_change
	 */
	public function set_meta_box_sizes( $sizes, $skip_change = FALSE ) {

		if ( ! is_array( $sizes ) ) {

			$sizes = maybe_unserialize( $sizes );

			if ( ! is_array( $sizes ) ) {
				$sizes = [];
			}

		}

		if ( maybe_serialize( $sizes ) !== maybe_serialize( $this->meta_box_sizes ) ) {

			if ( ! $skip_change ) {
				$this->register_change( 'meta_box_sizes' );
			}

			$this->set_meta( 'meta_box_sizes', $sizes );

		}

	}

	/**
	 * Setter for the PO currency position
	 *
	 * @since 0.9.17
	 *
	 * @param string $currency_pos
	 * @param bool   $skip_change
	 */
	public function set_currency_pos( $currency_pos, $skip_change = FALSE ) {

		if ( ! in_array( $currency_pos, [ 'left', 'right', 'left_space', 'right_space' ] ) ) {
			$currency_pos = get_option( 'woocommerce_currency_pos', 'left' );
		}

		if ( $currency_pos !== $this->currency_pos ) {

			if ( ! $skip_change ) {
				$this->register_change( 'currency_pos' );
			}

			$this->set_meta( 'currency_pos', $currency_pos );
		}

	}

	/**
	 * Setter for the PO price thousands separator
	 *
	 * @since 0.9.17
	 *
	 * @param string $price_thousand_sep
	 * @param bool   $skip_change
	 */
	public function set_price_thousand_sep( $price_thousand_sep, $skip_change = FALSE ) {

		$price_thousand_sep = wc_clean( $price_thousand_sep );

		if ( $price_thousand_sep !== $this->price_thousand_sep ) {

			if ( ! $skip_change ) {
				$this->register_change( 'price_thousand_sep' );
			}

			$this->set_meta( 'price_thousand_sep', $price_thousand_sep );
		}

	}

	/**
	 * Setter for the PO price decimals separator
	 *
	 * @since 0.9.17
	 *
	 * @param string $price_decimal_sep
	 * @param bool   $skip_change
	 */
	public function set_price_decimal_sep( $price_decimal_sep, $skip_change = FALSE ) {

		$price_decimal_sep = wc_clean( $price_decimal_sep );

		if ( $price_decimal_sep !== $this->price_decimal_sep ) {

			if ( ! $skip_change ) {
				$this->register_change( 'price_decimal_sep' );
			}

			$this->set_meta( 'price_decimal_sep', $price_decimal_sep );
		}

	}

	/**
	 * Setter for the PO price number of decimals
	 *
	 * @since 0.9.17
	 *
	 * @param int  $price_num_decimals
	 * @param bool $skip_change
	 */
	public function set_price_num_decimals( $price_num_decimals, $skip_change = FALSE ) {

		$price_num_decimals = absint( $price_num_decimals );

		if ( $price_num_decimals !== $this->price_num_decimals ) {

			if ( ! $skip_change ) {
				$this->register_change( 'price_num_decimals' );
			}

			$this->set_meta( 'price_num_decimals', $price_num_decimals );
		}

	}

	/**
	 * Setter for the PO's currency exchange rate
	 *
	 * @since 1.0.3
	 *
	 * @param int  $exchange_rate
	 * @param bool $skip_change
	 */
	public function set_exchange_rate( $exchange_rate, $skip_change = FALSE ) {

		$exchange_rate = (float) $exchange_rate;

		if ( $exchange_rate !== $this->exchange_rate ) {

			if ( ! $skip_change ) {
				$this->register_change( 'exchange_rate' );
			}

			$this->set_meta( 'exchange_rate', $exchange_rate );
		}

	}

	/**
	 * Setter for the PO has taxes enabled.
	 *
	 * @since 0.9.21
	 *
	 * @param string|bool $has_taxes_enabled
	 * @param bool        $skip_change
	 */
	public function set_has_taxes_enabled( $has_taxes_enabled, $skip_change = FALSE ) {

		$has_taxes_enabled = wc_string_to_bool( $has_taxes_enabled );

		if ( $has_taxes_enabled !== $this->has_taxes_enabled ) {

			if ( ! $skip_change ) {
				$this->register_change( 'has_taxes_enabled' );
			}

			$this->set_meta( 'has_taxes_enabled', $has_taxes_enabled );
		}

	}

	/**
	 * Setter for the deliveries counter
	 *
	 * @since 0.9.27
	 *
	 * @param int  $deliveries_counter
	 * @param bool $skip_change
	 */
	public function set_deliveries_counter( $deliveries_counter, $skip_change = FALSE ) {

		$deliveries_counter = absint( $deliveries_counter );

		if ( $deliveries_counter !== $this->deliveries_counter ) {

			if ( ! $skip_change ) {
				$this->register_change( 'deliveries_counter' );
			}

			$this->set_meta( 'deliveries_counter', $deliveries_counter );
		}

	}

	/**
	 * Setter for the related PO (only for Returning POs)
	 *
	 * @since 1.1.3
	 *
	 * @param int  $related_po
	 * @param bool $skip_change
	 */
	public function set_related_po( $related_po, $skip_change = FALSE ) {

		$related_po = absint( $related_po );

		if ( $related_po !== $this->related_po ) {

			if ( ! $skip_change ) {
				$this->register_change( 'related_po' );
			}

			$this->set_meta( 'related_po', $related_po );
		}

	}

}
