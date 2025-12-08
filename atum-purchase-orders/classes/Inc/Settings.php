<?php
/**
 * Add Purchase Orders Settings' tab to ATUM Settings
 *
 * @package     AtumPO\Inc
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @since       0.8.8
 */

namespace AtumPO\Inc;

defined( 'ABSPATH' ) || die;

use Atum\Inc\Globals as AtumGlobals;
use Atum\Inc\Helpers as AtumHelpers;
use Atum\Settings\Settings as AtumSettings;
use AtumPO\Deliveries\DeliveryLocations;


class Settings {

	/**
	 * The singleton instance holder
	 *
	 * @var Settings
	 */
	private static $instance;


	/**
	 * Settings singleton constructor
	 *
	 * @since 0.8.8
	 */
	private function __construct() {

		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 11 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 11 );

		add_filter( 'atum/settings/display_image_selector', array( $this, 'display_email_template_preview' ), 10, 2 );
		add_filter( 'atum/settings/display_image_selector', array( $this, 'display_pdf_template_preview' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 0.8.8
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		$tabs['purchase_orders'] = array(
			'label'    => __( 'Purchase Orders', ATUM_PO_TEXT_DOMAIN ),
			'icon'     => 'atmi-po-pro',
			'sections' => array(
				'pop_purchase_orders' => __( 'Purchase Orders PRO Options', ATUM_PO_TEXT_DOMAIN ),
				'pop_pdf_template'    => __( 'PO PDF Template', ATUM_PO_TEXT_DOMAIN ),
				'pop_emails_template' => __( 'PO Emails Template', ATUM_PO_TEXT_DOMAIN ),
				'pop_pos_list_table'  => __( 'POs List Table', ATUM_PO_TEXT_DOMAIN ),
			),
		);

		return $tabs;
	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 0.8.8
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$delivery_locations = DeliveryLocations::get_locations();
		$locations          = array();

		foreach ( $delivery_locations as $location ) {
			$locations[ $location['id'] ] = $location['name'];
		}

		$po_label = '<br><span class="label label-secondary">' . __( 'Purchase Orders PRO', ATUM_PO_TEXT_DOMAIN ) . '</span>';

		$po_settings = array(
			'po_numbering_system'               => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_purchase_orders',
				'name'       => __( 'Numbering system', ATUM_PO_TEXT_DOMAIN ),
				'desc'       => __( 'Choose a numbering system for your automatic PO numbers.', ATUM_PO_TEXT_DOMAIN ),
				'type'       => 'select',
				'default'    => 'ids',
				'options'    => array(
					'values' => array(
						'ids'    => __( 'Post IDs', ATUM_PO_TEXT_DOMAIN ),
						'custom' => __( 'Custom', ATUM_PO_TEXT_DOMAIN ),
					),
					'style'  => 'width:200px',
				),
				'dependency' => array(
					array(
						'field'    => 'po_numbering_custom_pattern',
						'value'    => 'custom',
						'animated' => FALSE,
					),
					array(
						'field'    => 'po_numbering_custom_counter',
						'value'    => 'custom',
						'animated' => FALSE,
					),
					array(
						'field'    => 'po_numbering_custom_zeros',
						'value'    => 'custom',
						'animated' => FALSE,
					),
				),
			),
			'po_numbering_custom_pattern'       => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_purchase_orders',
				'name'       => __( 'Number pattern', ATUM_PO_TEXT_DOMAIN ),
				/* translators: first is the opening anchor tag and second is the closing anchor tag */
				'desc'       => sprintf( __( 'Set a special pattern for building the PO numbers. Available tags:<ul><li><strong>{counter}</strong> The current PO number.</li><li><strong>{year}</strong> Add the current year.</li><li><strong>{date:format}</strong> Add any valid %1$sPHP date format%2$s.</li></ul><pre><code>Example 1: <strong>PO{year}-{counter}</strong> would be converted to <strong>PO2023-0001</strong></code></pre><pre><code>Example 2: <strong>P{date:y}-{counter}</strong> would be converted to <strong>P22-0001</strong></code></pre>', ATUM_PO_TEXT_DOMAIN ), '<a href="https://www.php.net/manual/en/datetime.formats.date.php" target="_blank">', '</a>' ),
				'type'       => 'text',
				'default'    => '',
				'options'    => array(
					'placeholder' => 'PO{counter}',
				),
				'validation' => array( $this, 'validate_number_pattern' ),
			),
			'po_numbering_custom_counter'       => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'PO counter', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'The next number to be used for your POs by replacing the {counter} tag in your number pattern above.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 1,
				'options' => array(
					'min'  => 1,
					'step' => 1,
				),
			),
			'po_numbering_custom_zeros'         => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Padding zeros', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( "The number of padding zeros to be used when the counter's cipher doesn't reach the specified length. For example: 4 will convert the counter 1 to 0001", ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 4,
				'options' => array(
					'min'  => 0,
					'step' => 1,
				),
			),
			'po_required_requisition'           => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Require requisition', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Enable to require all the Purchase Orders to be approved by a requisitioner.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'po_supplier_products_restriction'  => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_purchase_orders',
				'name'       => __( 'Supplier products restriction', ATUM_PO_TEXT_DOMAIN ),
				'desc'       => __( 'When enabled, when you assign any supplier to a PO, only the products and inventories assigned to that same supplier will be available for adding. If you disable this, all the products will be available, always.', ATUM_PO_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'yes',
				'dependency' => array(
					array(
						'field' => 'po_no_supplier_products',
						'value' => 'yes',
					),
				),
			),
			'po_no_supplier_products'           => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'No supplier products', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Whether to show products with no supplier assigned when adding products to any PO with a supplier assigned.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'po_enable_taxes'                   => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_purchase_orders',
				'name'       => __( 'Enable taxes', ATUM_PO_TEXT_DOMAIN ),
				'desc'       => __( 'Enable taxes on Purchase Orders.', ATUM_PO_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => wc_tax_enabled() ? 'yes' : 'no',
				'dependency' => array(
					array(
						'field' => 'po_use_system_taxes',
						'value' => 'yes',
					),
					array(
						'field' => 'po_purchase_price_including_taxes',
						'value' => 'yes',
					),
					array(
						'field' => 'po_set_purchase_price_taxes',
						'value' => 'yes',
					),
				),
			),
			'po_use_system_taxes'               => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Use system taxes for new POs', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( "When the PO supplier has no taxes assigned on their profile, use the default WooCommerce's taxes configuration. If disabled, won't add any taxes at all.", ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'po_purchase_price_including_taxes' => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Purchase price including taxes', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( "Set up how you've entered your purchase prices for all your products. If you've entered the purchase prices as 'taxes inclusive', when you add any product to a PO, the taxes will be deducted from the purchase price and added to the tax column. however, if you've entered the purchase prices as 'taxes exclusive', the taxes (if any) will be added apart and not deducted.", ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => wc_prices_include_tax() ? 'yes' : 'no',
			),
			'po_set_purchase_price_taxes'       => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( "Set purchase price's taxes", ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select which behaviour you want regarding taxes when using the &quot;Set purchase price&quot; modal for any PO item.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => 'no_taxes',
				'options' => array(
					'values' => [
						'no_taxes'        => __( 'Save without any taxes', ATUM_PO_TEXT_DOMAIN ),
						'shop_base_taxes' => __( 'Add shop base taxes', ATUM_PO_TEXT_DOMAIN ),
						'po_taxes'        => __( 'Save with PO taxes', ATUM_PO_TEXT_DOMAIN ),
					],
					'style'  => 'width:200px',
				),
			),
			'po_auto_fill_purchaser_info'       => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_purchase_orders',
				'name'       => __( 'Auto-fill purchaser info', ATUM_PO_TEXT_DOMAIN ),
				'desc'       => __( 'Choose whether you want to auto-fill new purchase orders with content when they are created.', ATUM_PO_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'yes',
				'dependency' => array(
					array(
						'field' => 'po_purchaser_info_source',
						'value' => 'yes',
					),
				),
			),
			'po_purchaser_info_source'          => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Purchaser info source', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select the source for the purchaser info that will be filled by default.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => 'store_details',
				'options' => array(
					'values' => [ 'store_details' => __( 'Store Details', ATUM_PO_TEXT_DOMAIN ) ] + $locations,
					'style'  => 'width:200px',
				),
			),
			'po_confirm_status_changes'         => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Confirm status changes', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'When enabled, the system will ask for confirmation before setting any PO to a new status.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'po_status_flow_restriction'        => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'PO status flow restriction', ATUM_PO_TEXT_DOMAIN ),
				/* translators: first is the anchor open tag and second is the anchor close tag */
				'desc'    => sprintf( __( 'When enabled, the status flow restriction will be active, what means that the PO status changes can only be made by following the status flow logic as explained %1$shere%2$s.', ATUM_PO_TEXT_DOMAIN ), '<a href="https://stockmanagementlabs.crunch.help/atum-purchase-orders-pro/po-status-logic" target="_blank">', '</a>' ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'po_default_description'            => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Default PO description', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'If you want to auto-fill the &quot;description&quot; field for new POs when they are created, write it here.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'editor',
				'default' => '',
			),
			'po_default_delivery_terms'         => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Default payment and delivery terms', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'If you want to auto-fill the &quot;payment and delivery terms&quot; field for new POs when they are created, write it here.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'editor',
				'default' => '',
			),
			'po_display_extra_fields'           => array(
				'group'           => 'purchase_orders',
				'section'         => 'pop_purchase_orders',
				'name'            => __( 'Display PO item additional columns', ATUM_PO_TEXT_DOMAIN ),
				'desc'            => __( 'Enable or disable the following columns to display them at the PO items table.', ATUM_PO_TEXT_DOMAIN ),
				'type'            => 'multi_checkbox',
				'default'         => 'yes',
				'main_switcher'   => FALSE,
				'default_options' => [
					'stock'                => [
						'value' => 'yes',
						'name'  => __( 'Current stock', ATUM_PO_TEXT_DOMAIN ),
						'desc'  => __( 'When enabled, the current stock is displayed in the PO Item lines.', ATUM_PO_TEXT_DOMAIN ),
					],
					'last_week_sales'      => [
						'value' => 'yes',
						'name'  => __( 'Last week sales', ATUM_PO_TEXT_DOMAIN ),
						'desc'  => __( 'When enabled, the last week sales of each product are displayed in the PO Item lines.', ATUM_PO_TEXT_DOMAIN ),
					],
					'inbound_stock'        => [
						'value' => 'yes',
						'name'  => __( 'Inbound stock', ATUM_PO_TEXT_DOMAIN ),
						'desc'  => __( 'When enabled, the inbound stock is displayed for each product in the PO Item lines.', ATUM_PO_TEXT_DOMAIN ),
					],
					'recommended_quantity' => [
						'value' => 'yes',
						'name'  => __( 'Recommended order quantity', ATUM_PO_TEXT_DOMAIN ),
						'desc'  => __( 'When enabled, the recommended order quantity of each product is displayed in the PO Item lines.', ATUM_PO_TEXT_DOMAIN ),
					],
				],
			),
			'po_copy_shipping_address'        => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_purchase_orders',
				'name'    => __( 'Copy customer shipping address', ATUM_PO_TEXT_DOMAIN ),
				/* translators: first is the anchor open tag and second is the anchor close tag */
				'desc'    => __( "When this feature is enabled, creating a new Purchase Order from a WooCommerce Order will automatically set the customer's shipping address as the Purchaser's information.<br>This functionality is particularly useful for dropshipping.", ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'no',
			),
			'po_default_pdf_template'           => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_pdf_template',
				'name'    => __( 'Default template', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select the template that will be used by default for all the PO PDFs.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'image_selector',
				'default' => 'default',
				'options' => array(
					'values' => Helpers::get_po_pdf_templates(),
				),
			),
			'po_default_pdf_template_color'     => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_pdf_template',
				'name'    => __( 'Primary color', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select the primary color for the PDF template', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#00B8DB',
			),
			'po_default_pdf_template_logo'      => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_pdf_template',
				'name'    => __( 'Company logo', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Upload/Select a logo to be used on the chosen template.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'image_uploader',
				'options' => array(
					'modal-title'  => __( 'Choose a company logo for the PDF template', ATUM_PO_TEXT_DOMAIN ),
					'modal-button' => __( 'Use this logo', ATUM_PO_TEXT_DOMAIN ),
				),
			),
			'po_pdf_template_fields'            => array(
				'group'           => 'purchase_orders',
				'section'         => 'pop_pdf_template',
				'name'            => __( 'PDF fields', ATUM_PO_TEXT_DOMAIN ),
				'desc'            => __( "Select the optional fields you want to be displayed in your POs' PDFs.", ATUM_PO_TEXT_DOMAIN ),
				'type'            => 'multi_checkbox',
				'default'         => 'yes',
				'default_options' => array(
					'ship_via'       => [
						'value' => 'yes',
						'name'  => __( 'Ship Via', ATUM_PO_TEXT_DOMAIN ),
					],
					'fob'            => [
						'value' => 'yes',
						'name'  => __( 'F.O.B.', ATUM_PO_TEXT_DOMAIN ),
					],
					'requisitioner'  => [
						'value' => 'yes',
						'name'  => __( 'Requisitioner', ATUM_PO_TEXT_DOMAIN ),
					],
					'delivery_terms' => [
						'value' => 'yes',
						'name'  => __( 'Delivery Terms', ATUM_PO_TEXT_DOMAIN ),
					],
					'description'    => [
						'value' => 'yes',
						'name'  => __( 'Description', ATUM_PO_TEXT_DOMAIN ),
					],
					'thumbnails'     => [
						'value' => 'yes',
						'name'  => __( 'Product Thumbnails', ATUM_PO_TEXT_DOMAIN ),
					],
				),
			),
			'po_default_emails_sender'          => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( "Default sender's email", ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Email address that will be used as the sender when emailing POs to suppliers. You can change it later.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'text',
				'default' => get_option( 'admin_email' ),
			),
			'po_use_email_template'             => array(
				'group'      => 'purchase_orders',
				'section'    => 'pop_emails_template',
				'name'       => __( 'Use an email template', ATUM_PO_TEXT_DOMAIN ),
				'desc'       => __( 'Enable to use a template on all the emails sent to suppliers.', ATUM_PO_TEXT_DOMAIN ),
				'type'       => 'switcher',
				'default'    => 'yes',
				'dependency' => array(
					array(
						'field' => 'po_default_emails_template',
						'value' => 'yes',
					),
					array(
						'field' => 'po_default_emails_template_color',
						'value' => 'yes',
					),
					array(
						'field' => 'po_default_emails_template_logo',
						'value' => 'yes',
					),
				),
			),
			'po_default_emails_template'        => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( 'Email template', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select the template that will be used by default for all the PO emails sent to suppliers.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'image_selector',
				'default' => 'default',
				'options' => array(
					'values' => Helpers::get_po_email_templates(),
				),
			),
			'po_default_emails_template_color'  => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( 'Primary color', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Select the primary color for the email template', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#00B8DB',
			),
			'po_default_emails_template_logo'   => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( 'Company logo', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Upload/Select a logo to be used on the chosen template.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'image_uploader',
				'options' => array(
					'modal-title'  => __( 'Choose a company logo for the email template', ATUM_PO_TEXT_DOMAIN ),
					'modal-button' => __( 'Use this logo', ATUM_PO_TEXT_DOMAIN ),
				),
			),
			'po_default_emails_body'            => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( "Default email's body", ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Email body that will be used by default when emailing POs to suppliers. You can change it later.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'editor',
				'default' => wp_kses_post( '
					<p>' . __( 'Dear Supplier,', ATUM_PO_TEXT_DOMAIN ) . '</p>
					<p>' . __( 'Please, find a new Purchase Order attached.', ATUM_PO_TEXT_DOMAIN ) . '<br>
					' . __( 'You can reply to this email when the order gets delivered.', ATUM_PO_TEXT_DOMAIN ) . '</p>
					<p>' . __( 'Thank you!', ATUM_PO_TEXT_DOMAIN ) . '</p>
				' ),
			),
			'po_default_emails_footer'          => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_emails_template',
				'name'    => __( "Default email's footer", ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'Text that will be placed in the footer of all the emails sent to suppliers.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'editor',
				/* translators: first is the year number and second is the company name */
				'default' => sprintf( __( '&copy;%1$d %2$s. All rights reserved.', ATUM_PO_TEXT_DOMAIN ), date_i18n( 'Y' ), AtumHelpers::get_option( 'company_name', '' ) ),
			),
			'po_list_posts_per_page'            => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_pos_list_table',
				'name'    => __( 'POs per page', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( "Controls the number of purchase orders displayed per page within the POs List Table screen. Please note, you can set this value within the 'Screen Option' tab as well and this last value will have preference over this one as will be saved per user. Enter '-1' to remove the pagination and display all available POs on one page (not recommended if your store contains a large number of POs as it may affect the performance).", ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => AtumSettings::DEFAULT_POSTS_PER_PAGE,
				'options' => array(
					'min' => - 1,
					'max' => 500,
				),
			),
			'po_list_due_soon_days'             => array(
				'group'   => 'purchase_orders',
				'section' => 'pop_pos_list_table',
				'name'    => __( 'Due soon days', ATUM_PO_TEXT_DOMAIN ),
				'desc'    => __( 'The number of days before the expected date that ATUM will use to consider a PO as "Due Soon".', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'number',
				'default' => 3,
				'options' => array(
					'min' => 0,
					'max' => 500,
				),
			),
			'po_purchase_price_taxes'           => array(
				'group'   => 'tools',
				'section' => 'tools',
				'name'    => __( "Update purchase prices' taxes", ATUM_PO_TEXT_DOMAIN ) . $po_label,
				'desc'    => __( 'Add or deduct the specified amount of taxes (in percentage) to/from purchase prices of all your products at once.', ATUM_PO_TEXT_DOMAIN ),
				'type'    => 'script_runner',
				'options' => array(
					'fields'        => array(
						array(
							'type'    => 'select',
							'name'    => 'action',
							'options' => array(
								'add'    => __( 'Add', ATUM_PO_TEXT_DOMAIN ),
								'deduct' => __( 'Deduct', ATUM_PO_TEXT_DOMAIN ),
							),
						),
						array(
							'type'  => 'number',
							'name'  => 'percentage',
							'min'   => 0,
							'max'   => 100,
							'value' => 0,
						),
					),
					'button_text'   => __( 'Update Now!', ATUM_PO_TEXT_DOMAIN ),
					'script_action' => 'atum_tool_po_purchase_price_taxes',
					'confirm_msg'   => esc_attr( __( 'This will update the purchase prices for all your products at once.', ATUM_PO_TEXT_DOMAIN ) ),
				),
			),
		);

		return array_merge( $defaults, $po_settings );

	}

	/**
	 * Validate the numbering pattern entered on the settings page
	 *
	 * @since 0.8.9
	 *
	 * @param string $pattern
	 *
	 * @return string
	 */
	public function validate_number_pattern( $pattern ) {

		// The pattern must have a number.
		if ( $pattern && ! str_contains( $pattern, '{counter}' ) /*&& ! str_contains( $pattern, '{supplier_counter}' )*/ ) {
			add_settings_error( 'atum_settings', 'invalid_pattern', __( 'Error saving: the pattern must have at least a counter tag ({counter}).', ATUM_PO_TEXT_DOMAIN ) );
			$pattern = AtumHelpers::get_option( 'po_numbering_custom_pattern', '' ); // Return to the previous value.
		}

		return $pattern;
	}

	/**
	 * Display preview for email template.
	 *
	 * @since 0.9.27
	 *
	 * @param string $content
	 * @param array  $args
	 *
	 * @return string
	 */
	public function display_email_template_preview( $content, $args ) {

		if ( 'po_default_emails_template' === $args['id'] ) {

			$content .= '
				<span class="btn btn-link" id="atum_po_preview_email_template">' . esc_html__( 'Preview', ATUM_PO_TEXT_DOMAIN ) . '</span>
			';

		}

		return $content;
	}

	/**
	 * Display preview for PDF template.
	 *
	 * @since 0.9.27
	 *
	 * @param string $content
	 * @param array  $args
	 *
	 * @return string
	 */
	public function display_pdf_template_preview( $content, $args ) {

		if ( 'po_default_pdf_template' === $args['id'] ) {

			$content .= '
				<span class="btn btn-link" id="atum_po_preview_pdf_template">' . esc_html__( 'Preview', ATUM_PO_TEXT_DOMAIN ) . '</span>
			';

		}

		return $content;
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 0.9.23
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_scripts( $hook ) {

		// Enqueue the PO PRO's settings script to ATUM settings.
		if ( in_array( $hook, [ AtumGlobals::ATUM_UI_HOOK . '_page_' . AtumSettings::UI_SLUG, 'toplevel_page_' . AtumSettings::UI_SLUG ] ) ) {

			wp_register_script( 'atum-po-settings', ATUM_PO_URL . 'assets/js/build/atum-po-settings.js', [ 'jquery', AtumSettings::UI_SLUG ], ATUM_PO_VERSION, TRUE );

			$vars = array(
				'areYouSure'              => __( 'Are you sure?', ATUM_PO_TEXT_DOMAIN ),
				'cancel'                  => __( 'Cancel', ATUM_PO_TEXT_DOMAIN ),
				'doIt'                    => __( 'Yes, do it!', ATUM_PO_TEXT_DOMAIN ),
				'loading'                 => __( 'Loading...', ATUM_PO_TEXT_DOMAIN ),
				'nextPONumber'            => __( 'Next PO number', ATUM_PO_TEXT_DOMAIN ),
				'nonce'                   => wp_create_nonce( 'po-settings-nonce' ),
				'poEmailPreviewNonce'     => wp_create_nonce( 'po-email-preview' ),
				'poPdfPreviewNonce'       => wp_create_nonce( 'po-pdf-preview' ),
				'requisitionerDisableMsg' => __( "After disabling the requisitioner requisition, all the existing POs having the 'Awaiting Approval' or 'Approved' statuses will be changed to the 'New' status automatically.", ATUM_PO_TEXT_DOMAIN ),
			);

			wp_localize_script( 'atum-po-settings', 'atumPOSettingsVars', $vars );
			wp_enqueue_script( 'atum-po-settings' );

		}

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
	 * @return Settings instance
	 */
	public static function get_instance() {

		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
