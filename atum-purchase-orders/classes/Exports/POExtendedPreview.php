<?php
/**
 * Extends the Purchase Order Extended Export Class and allow to preview
 *
 * @package         AtumPO\Exports
 * @author          BE REBEL - https://berebel.studio
 * @copyright       ©2025 Stock Management Labs™
 *
 * @since           0.9.7
 */

namespace AtumPO\Exports;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Helpers as AtumHelpers;
use AtumPO\Inc\Globals;


class POExtendedPreview extends POExtendedExport {

	/**
	 * The company data
	 *
	 * @var array
	 */
	private $company_data = [];

	/**
	 * POModel constructor
	 *
	 * @since 0.9.27
	 *
	 * @param array $data
	 */
	public function __construct( $data = [] ) {
		
		$this->load_company_data();

		$this->number         = 'PO-XXX';
		$this->dummy_supplier = 'My supplier, 555th Main St, Supplier City';
		$this->ship_via       = 'ship';
		$this->delivery_terms = '';
		$this->subtotal       = 0;
		$this->requisitioner  = get_current_user_id();

		$this->currency           = get_option( 'woocommerce_currency' );
		$this->currency_pos       = get_option( 'woocommerce_currency_pos', 'left' );
		$this->price_thousand_sep = get_option( 'woocommerce_price_thousand_sep', ',' );
		$this->price_decimal_sep  = get_option( 'woocommerce_price_decimal_sep', '.' );
		$this->price_num_decimals = get_option( 'woocommerce_price_num_decimals', 2 );

		if ( isset( $data['template'] ) ) {
			$this->template = $data['template'];
		}
		if ( isset( $data['template_fields'] ) ) {
			$this->template_fields = $data['template_fields'];
		}
		if ( isset( $data['template_color'] ) ) {
			$this->template_color = $data['template_color'];
		}

	}

	/**
	 * Get all extra data not present in a PO by default
	 *
	 * @since 0.9.27
	 */
	private function load_company_data() {

		$same_shipping_address = 'yes' === AtumHelpers::get_option( 'same_ship_address', 'yes' );

		$countries         = WC()->countries;
		$default_country   = $countries->get_base_country();
		$default_city      = $countries->get_base_city();
		$default_adress    = $countries->get_base_address();
		$default_address_2 = $countries->get_base_address_2();
		$default_postcode  = $countries->get_base_postcode();

		// Company data.
		$this->company_data = array(
			'company'    => AtumHelpers::get_option( 'company_name', '' ),
			'address_1'  => AtumHelpers::get_option( 'address_1', $default_adress ),
			'address_2'  => AtumHelpers::get_option( $same_shipping_address ? 'address_2' : 'ship_address_2', $default_address_2 ),
			'city'       => AtumHelpers::get_option( $same_shipping_address ? 'city' : 'ship_city', $default_city ),
			'state'      => '',
			'postcode'   => AtumHelpers::get_option( $same_shipping_address ? 'zip' : 'ship_zip', $default_postcode ),
			'country'    => AtumHelpers::get_option( $same_shipping_address ? 'country' : 'ship_country', $default_country ),
			'tax_number' => AtumHelpers::get_option( 'tax_number', '' ),
		);

	}

	/**
	 * Getter for the debug mode
	 *
	 * @since 0.9.27
	 */
	public function get_debug_mode() {
		return FALSE;
	}

	/**
	 * Return dummy supplier address
	 *
	 * @since 0.9.27
	 *
	 * @return string
	 */
	public function get_supplier_address() {

		return $this->dummy_supplier;

	}

	/**
	 * Return formatted company address
	 *
	 * @since 0.9.27
	 *
	 * @return string
	 */
	public function get_shipping_address() {

		$shipping_address = WC()->countries->get_formatted_address( $this->company_data );
		$shipping_methods = Globals::get_shipping_methods();
		$template_fields  = $this->template_fields;
		$template_fields  = $template_fields['options'] ?? [];

		if (
			array_key_exists( 'ship_via', $template_fields ) && 'yes' === $template_fields['ship_via'] &&
			$this->ship_via && array_key_exists( $this->ship_via, $shipping_methods )
		) {
			/* translators: the ship via field value */
			$shipping_address .= '<br><br><strong>' . __( 'Ship Via:', ATUM_PO_TEXT_DOMAIN ) . '</strong> ' . $shipping_methods[ $this->ship_via ];
		}

		return apply_filters( 'atum/purchase_orders_pro/po_export/shipping_address', $shipping_address, $this->company_data, $this->id );

	}

	/**
	 * Dummy getter for PO taxes
	 *
	 * @since 0.9.27
	 *
	 * @return array
	 */
	public function get_taxes() {
		return [];
	}

	/**
	 * Dummy getter for the po items
	 *
	 * @since 0.9.27
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_items( $type = '' ) {

		if ( FALSE === in_array( $type, [ '', 'line_items' ] ) ) {
			return [];
		}

		$item1 = new DummyItem( 'Sample product 1', 1, 10 );
		$item2 = new DummyItem( 'Sample product 2', 5, 25.45 );
		$item3 = new DummyItem( 'Sample product 3', 7, 46.89 );

		return [
			$item1,
			$item2,
			$item3,
		];
	}

	/**
	 * Return header content if exist
	 *
	 * @since 0.9.27
	 *
	 * @return string
	 */
	public function get_content() {

		$total_text_colspan = 3;
		$post_type          = get_post_type_object( get_post_type( $this->get_id() ) );
		$currency           = $this->currency;
		$discount           = $this->get_total_discount();

		if ( $discount ) {
			$desc_percent = 50;
			$total_text_colspan++;
		}
		else {
			$desc_percent = 60;
		}

		$taxes               = $this->get_taxes();
		$n_taxes             = count( $taxes );
		$desc_percent       -= $n_taxes * 10;
		$total_text_colspan += $n_taxes;

		$line_items_fee       = $this->get_items( 'fee' );
		$line_items_shipping  = $this->get_items( 'shipping' );
		$po                   = $this;
		$pdf_template         = $this->template;
		$requires_requisition = 'yes' === AtumHelpers::get_option( 'po_required_requisition', 'no' );
		$template_fields      = $this->template_fields;
		$template_fields      = $template_fields['options'] ?? [];
		$template_color       = $this->template_color;

		ob_start();

		AtumHelpers::load_view( ATUM_PO_PATH . "views/pdf-templates/$pdf_template/purchase-order-html", compact( 'po', 'total_text_colspan', 'post_type', 'currency', 'discount', 'desc_percent', 'taxes', 'n_taxes', 'line_items_fee', 'line_items_shipping', 'requires_requisition', 'template_fields', 'template_color' ) );

		return ob_get_clean();

	}

	/**
	 * Getter for the pdf template
	 *
	 * @since 0.9.27
	 *
	 * @return mixed|string
	 */
	public function get_pdf_template() {
		return $this->template;
	}

	/**
	 * Getter for the company data array
	 *
	 * @since 0.9.27
	 *
	 * @return array
	 */
	public function get_company_data() {
		return $this->company_data;
	}

	/**
	 * Getter for the company's Tax/VAT number
	 *
	 * @since 0.9.27
	 *
	 * @return string
	 */
	public function get_tax_number() {

		return $this->company_data['tax_number'];
	}

	/**
	 * Gets ATUM order's total - formatted for display
	 * NOTE: this is just the same method as the parent class but using the format_price method for price formatting.
	 *
	 * @since 0.9.27
	 *
	 * @param  string $tax_display  Optional. Type of tax display.
	 * @param  bool   $subtotal     Optional. If should return the tax free Subtotal instead.
	 *
	 * @return string
	 */
	public function get_formatted_total( $tax_display = '', $subtotal = FALSE ) {

		$amount = 0;
		foreach ( $this->get_items() as $item ) {
			$amount += $item->get_subtotal();
		}

		$formatted_total = $this->format_price( $amount );

		return apply_filters( 'atum/purchase_orders_pro/get_formatted_total', $formatted_total, $this, $tax_display, $subtotal );

	}

}

// phpcs:disable
class DummyItem {

	public function __construct( $name, $qty, $price ) {
		$this->name  = $name;
		$this->qty   = $qty;
		$this->price = $price;
	}

	public function get_name() {

		return $this->name;
	}

	public function get_quantity() {

		return $this->qty;
	}

	public function get_subtotal() {

		return $this->price;
	}

	public function get_total() {

		return $this->price;
	}

	public function get_taxes() {

		return [];
	}

	public function get_product() {

		return FALSE;
	}

	public function get_order() {

		return NULL;
	}

	public function get_formatted_meta_data() {

		return [];
	}
}
// phpcs:enable
