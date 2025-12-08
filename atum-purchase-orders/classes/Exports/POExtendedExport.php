<?php
/**
 * Extends the Purchase Order Extended Class and exports it as PDF
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
use Atum\PurchaseOrders\PurchaseOrders;
use Atum\Suppliers\Supplier;
use AtumPO\Inc\Globals;
use AtumPO\Inc\Helpers;
use AtumPO\Models\POExtended;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;


class POExtendedExport extends POExtended {
	
	/**
	 * The company data
	 *
	 * @var array
	 */
	private $company_data = [];

	/**
	 * The shipping data
	 *
	 * @var array
	 */
	private $shipping_data = [];

	/**
	 * Only for PDF debugging during development.
	 *
	 * @var bool
	 */
	private $debug_mode = FALSE;

	/**
	 * Whether to return the HTML code instead of a PDF
	 *
	 * @var bool
	 */
	private $return_html;

	
	/**
	 * POModel constructor
	 *
	 * @since 0.9.7
	 *
	 * @param int  $id   The PO ID.
	 * @param bool $html Optional. Whether to return the HTML code instead of a PDF.
	 */
	public function __construct( $id = 0, bool $html = FALSE ) {

		$post_type         = get_post_type( $id );
		$this->return_html = $html;
		
		if ( PurchaseOrders::get_post_type() !== $post_type ) {
			/* translators: the post ID */
			wp_die( sprintf( esc_html__( 'Not a Purchase Order (%d)', ATUM_PO_TEXT_DOMAIN ), (int) $id ) );
		}
		
		// Always read items.
		parent::__construct( $id );

		$this->load_extra_data();
		
	}

	/**
	 * Get all extra data not present in a PO by default
	 *
	 * @since 1.0.1
	 */
	private function load_extra_data() {

		$countries         = WC()->countries;
		$default_country   = $countries->get_base_country();
		$default_state     = $countries->get_base_state();
		$default_city      = $countries->get_base_city();
		$country_state     = wc_format_country_state_string( AtumHelpers::get_option( 'country', '' ) ?: $default_country );
		$default_address   = $countries->get_base_address();
		$default_address_2 = $countries->get_base_address_2();
		$default_postcode  = $countries->get_base_postcode();
		$shp_country_state = wc_format_country_state_string( AtumHelpers::get_option( 'ship_country', '' ) ?: $default_country );

		if ( 'yes' === AtumHelpers::get_option( 'same_ship_address', 'yes' ) ) {
			$default_billing_company  = AtumHelpers::get_option( 'company_name', '' );
			$default_billing_address1 = AtumHelpers::get_option( 'address_1', '' ) ?: $default_address;
			$default_billing_address2 = AtumHelpers::get_option( 'address_2', '' ) ?: $default_address_2;
			$default_billing_city     = AtumHelpers::get_option( 'city', '' ) ?: $default_city;
			$default_billing_state    = $country_state['state'] ?: $default_state;
			$default_billing_postcode = AtumHelpers::get_option( 'zip', '' ) ?: $default_postcode;
			$default_billing_country  = $country_state['country'];
		}
		else {
			$default_billing_company  = AtumHelpers::get_option( 'ship_to', '' );
			$default_billing_address1 = AtumHelpers::get_option( 'ship_address_1', '' ) ?: $default_address;
			$default_billing_address2 = AtumHelpers::get_option( 'ship_address_2', '' ) ?: $default_address_2;
			$default_billing_city     = AtumHelpers::get_option( 'ship_city', '' ) ?: $default_city;
			$default_billing_state    = $shp_country_state['state'] ?: $default_state;
			$default_billing_postcode = AtumHelpers::get_option( 'ship_zip', '' ) ?: $default_postcode;
			$default_billing_country  = $shp_country_state['country'];
		}

		// Company data.
		$this->company_data = apply_filters( 'atum/purchase_orders_pro/po_export/company_data', array(
			'company'    => AtumHelpers::get_option( 'company_name', '' ),
			'address_1'  => AtumHelpers::get_option( 'address_1', '' ) ?: $default_address,
			'address_2'  => AtumHelpers::get_option( 'address_2', '' ) ?: $default_address_2,
			'city'       => AtumHelpers::get_option( 'city', '' ) ?: $default_city,
			'state'      => $country_state['state'] ?: $default_state,
			'postcode'   => AtumHelpers::get_option( 'zip', '' ) ?: $default_postcode,
			'country'    => $country_state['country'] ?? '',
			'tax_number' => AtumHelpers::get_option( 'tax_number', '' ),
		) );

		// Delivery location data.
		$this->shipping_data = apply_filters( 'atum/purchase_orders_pro/po_export/shipping_data', array(
			'company'   => $this->purchaser_name ?: $default_billing_company,
			'address_1' => $this->purchaser_address ?: $default_billing_address1,
			'address_2' => $this->purchaser_address_2 ?: $default_billing_address2,
			'city'      => $this->purchaser_city ?: $default_billing_city,
			'state'     => $this->purchaser_state ?: $default_billing_state,
			'postcode'  => $this->purchaser_postal_code ?: $default_billing_postcode,
			'country'   => $this->purchaser_country ?: $default_billing_country,
		) );

	}

	/**
	 * Return header content if exist
	 *
	 * @since 0.9.7
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

		if ( Helpers::may_use_po_taxes( $this ) ) {
			$desc_percent -= 10;
			$total_text_colspan++;
		}

		$line_items_fee          = $this->get_items( 'fee' );
		$line_items_shipping     = $this->get_items( 'shipping' );
		$po                      = $this;
		$pdf_template            = $this->get_pdf_template();
		$requires_requisition    = 'yes' === AtumHelpers::get_option( 'po_required_requisition', 'no' );
		$default_template_fields = array(
			'options' => array(
				'ship_via'       => 'yes',
				'fob'            => 'yes',
				'requisitioner'  => 'yes',
				'delivery_terms' => 'yes',
				'description'    => 'yes',
			),
		);
		$template_fields         = AtumHelpers::get_option( 'po_pdf_template_fields', $default_template_fields );
		$template_fields         = $template_fields['options'] ?? [];
		$template_color          = AtumHelpers::get_option( 'po_default_pdf_template_color', '' );

		$display_thumbnails = ( ! array_key_exists( 'thumbnails', $template_fields ) || 'yes' === $template_fields['thumbnails'] );

		if ( $display_thumbnails ) {
			$total_text_colspan++;
		}

		ob_start();

		AtumHelpers::load_view( ATUM_PO_PATH . "views/pdf-templates/$pdf_template/purchase-order-html", compact( 'po', 'total_text_colspan', 'post_type', 'currency', 'discount', 'desc_percent', 'line_items_fee', 'line_items_shipping', 'requires_requisition', 'template_fields', 'template_color', 'display_thumbnails' ) );

		return ob_get_clean();
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public function get_company_address() {
		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
		$company_address = WC()->countries->get_formatted_address( $this->company_data );
		remove_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );

		return apply_filters( 'atum/purchase_orders_pro/po_export/company_address', $company_address, $this->company_data );

	}
	
	/**
	 * Return formatted supplier address (includes VAT number if saved)
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public function get_supplier_address() {
		
		$address     = '';
		$supplier_id = $this->get_supplier( 'id' );
		
		if ( $supplier_id ) {

			$supplier = new Supplier( $supplier_id );

			$address = WC()->countries->get_formatted_address( array(
				'company'   => $supplier->name,
				'address_1' => $supplier->address,
				'address_2' => $supplier->address_2,
				'city'      => $supplier->city,
				'state'     => $supplier->state,
				'postcode'  => $supplier->zip_code,
				'country'   => $supplier->country,
			) );

			if ( $supplier->tax_number ) {
				/* translators: the VAT number */
				$address .= '<br>' . sprintf( esc_html__( 'Tax/VAT Number: %s', ATUM_PO_TEXT_DOMAIN ), $supplier->tax_number );
			}
			
		}
		
		return apply_filters( 'atum/purchase_orders_pro/po_export/supplier_address', $address, $supplier_id );
		
	}
	
	/**
	 * Return formatted company address
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public function get_shipping_address() {

		add_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
		$shipping_address = WC()->countries->get_formatted_address( $this->shipping_data );
		remove_filter( 'woocommerce_formatted_address_force_country_display', '__return_true' );
		$shipping_methods = Globals::get_shipping_methods();
		$template_fields  = AtumHelpers::get_option( 'po_pdf_template_fields', [] );
		$template_fields  = $template_fields['options'] ?? [];

		if (
			array_key_exists( 'ship_via', $template_fields ) && 'yes' === $template_fields['ship_via'] &&
			$this->ship_via && array_key_exists( $this->ship_via, $shipping_methods )
		) {
			/* translators: the ship via field value */
			$shipping_address .= '<br><br><strong>' . __( 'Ship Via:', ATUM_PO_TEXT_DOMAIN ) . '</strong> ' . $shipping_methods[ $this->ship_via ];
		}

		if ( array_key_exists( 'fob', $template_fields ) && 'yes' === $template_fields['fob'] && $this->fob ) {
			/* translators: the fob via field value */
			$shipping_address .= '<br><strong>' . __( 'F.O.B.:', ATUM_PO_TEXT_DOMAIN ) . '</strong> ' . $this->fob;
		}

		return apply_filters( 'atum/purchase_orders_pro/po_export/shipping_address', $shipping_address, $this->company_data, $this->id );

	}

	/**
	 * Getter for the company data array
	 *
	 * @since 0.9.7
	 *
	 * @return array
	 */
	public function get_company_data() {
		return $this->company_data;
	}

	/**
	 * Getter for the company's Tax/VAT number
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public function get_tax_number() {

		return $this->company_data['tax_number'];
	}

	/**
	 * Return an array with stylesheets needed to include in the pdf
	 *
	 * @since 0.9.7
	 *
	 * @param string $output Whether the output array of stylesheets are returned as a path or as an URL.
	 *
	 * @return array
	 */
	public function get_stylesheets( $output = 'path' ) {

		$po_prefix    = 'url' === $output ? ATUM_PO_URL : ATUM_PO_PATH;
		$atum_prefix  = 'url' === $output ? ATUM_URL : ATUM_PATH;
		$pdf_template = $this->get_pdf_template();

		return apply_filters( 'atum/purchase_orders_pro/po_export/css', array(
			$atum_prefix . 'assets/css/atum-icons.css',
			$po_prefix . "assets/css/atum-po-export-$pdf_template.css",
		), $output, $this );
	}

	/**
	 * Getter for the debug mode
	 *
	 * @since 0.9.7
	 */
	public function get_debug_mode() {
		return $this->debug_mode || ( ! empty( $_GET['debug'] ) && 1 === absint( $_GET['debug'] ) );
	}

	/**
	 * Get the PDF template
	 *
	 * @since 0.9.7
	 *
	 * @return string
	 */
	public function get_pdf_template() {

		$pdf_template = $this->meta['pdf_template'];

		if ( ! $pdf_template ) {
			$pdf_template = AtumHelpers::get_option( 'po_default_pdf_template', 'default' );
		}

		return $pdf_template;

	}

	/**
	 * Generate the PO PDF/HTML
	 *
	 * @since 0.9.7
	 *
	 * @param Destination $destination_mode
	 *
	 * @return string|\WP_Error
	 *
	 * @throws \Mpdf\MpdfException
	 */
	public function generate( $destination_mode = Destination::INLINE ) {

		try {

			$return_html = TRUE === $this->get_debug_mode() || TRUE === $this->return_html;
			$atum_dir    = AtumHelpers::get_atum_uploads_dir();
			$temp_dir    = $atum_dir . apply_filters( 'atum/purchase_orders/po_export/temp_pdf_dir', 'tmp' ); // Using original ATUM hook.

			if ( ! is_dir( $temp_dir ) ) {

				// Try to create it.
				$success = mkdir( $temp_dir, 0755, TRUE );

				// If wasn't created, use default uploads folder.
				if ( ! $success || ! is_writable( $temp_dir ) ) {
					$temp_dir = $atum_dir;
				}

			}

			do_action( 'atum/purchase_orders/po_export/generate', $this->id ); // Using original ATUM hook.

			$pdf_template = $this->get_pdf_template();

			$mpdf_options = array(
				'mode'    => 'utf-8',
				'format'  => 'A4',
				'tempDir' => $temp_dir,
			);

			if ( 'template1' === $pdf_template ) {
				$mpdf_options = array_merge( $mpdf_options, array(
					'margin_left'   => 0,
					'margin_right'  => 0,
					'margin_top'    => 0,
					'margin_bottom' => 0,
					'margin_header' => 0,
					'margin_footer' => 0,
				) );
			}

			// Try to set the backtrack limit to a higher value and avoid issues with huge amount of data.
			@ini_set( 'pcre.backtrack_limit', '9999999' );

			$mpdf = new Mpdf( $mpdf_options );

			// Add support for non-Latin languages.
			$mpdf->useAdobeCJK      = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mpdf->autoScriptToLang = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$mpdf->autoLangToFont   = TRUE; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			$mpdf->SetTitle( ! $this->is_returning() ? __( 'Purchase Order', ATUM_PO_TEXT_DOMAIN ) : __( 'Returning PO', ATUM_PO_TEXT_DOMAIN ) );

			// Add the icon fonts to mPDF.
			$mpdf->AddFontDirectory( ATUM_PATH . 'assets/fonts/' );
			$fontdata = array(
				'atum-icon-font' => array(
					'R' => 'atum-icon-font.ttf',
				),
			);

			foreach ( $fontdata as $f => $fs ) {
				$mpdf->fontdata[ $f ] = $fs;

				foreach ( [ 'R', 'B', 'I', 'BI' ] as $style ) {
					if ( isset( $fs[ $style ] ) && $fs[ $style ] ) {
						$mpdf->available_unifonts[] = $f . trim( $style, 'R' );
					}
				}
			}
			$mpdf->default_available_fonts = $mpdf->available_unifonts;

			$template_bg_path = apply_filters( 'atum/purchase_orders_pro/po_export/template_bg_path', ATUM_PO_PATH . "views/pdf-templates/$pdf_template/images/bg.png" );
			$template_bg_url  = apply_filters( 'atum/purchase_orders_pro/po_export/template_bg_url', ATUM_PO_URL . "views/pdf-templates/$pdf_template/images/bg.png" );

			if ( file_exists( $template_bg_path ) ) {
				$mpdf->SetDefaultBodyCSS( 'background', "url('" . $template_bg_url . "') no-repeat" );
				$mpdf->SetDefaultBodyCSS( 'background-image-resize', 4 );
			}

			$html = '';
			$css  = $this->get_stylesheets( $return_html ? 'url' : 'path' );

			foreach ( $css as $file ) {

				if ( $return_html ) {
					$html .= '<link rel="stylesheet" href="' . $file . '" media="all">'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
				}
				else {
					$stylesheet = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
					$mpdf->WriteHTML( $stylesheet, 1 );
				}

			}

			if ( $return_html ) {
				$html .= $this->get_content();

				return $html;
			}

			$mpdf->WriteHTML( $this->get_content() );

			return $mpdf->Output( apply_filters( 'atum/purchase_orders_pro/po_export/file_name', strtolower( "po-$this->number" ) ) . '.pdf', $destination_mode );

		} catch ( MpdfException $e ) {
			return new \WP_Error( 'atum_pdf_generation_error', $e->getMessage() );
		}

	}

}
