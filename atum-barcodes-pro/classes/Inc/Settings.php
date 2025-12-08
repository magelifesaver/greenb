<?php
/**
 * Class Settings
 *
 * @since       0.1.1
 * @author      BE REBEL - https://berebel.studio
 * @copyright   ©2025 Stock Management Labs™
 *
 * @package     AtumBarcodes\Inc
 */

namespace AtumBarcodes\Inc;

defined( 'ABSPATH' ) || exit;

use Atum\Inc\Helpers as AtumHelpers;
use Atum\InventoryLogs\InventoryLogs;
use Atum\PurchaseOrders\PurchaseOrders;


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
	 * @since 0.1.1
	 */
	private function __construct() {

		add_filter( 'atum/settings/tabs', array( $this, 'add_settings_tab' ), 11 );
		add_filter( 'atum/settings/defaults', array( $this, 'add_settings_defaults' ), 11 );

		// Special sanitization for the allowed entities.
		add_filter( 'atum/settings/sanitize', array( $this, 'sanitize_allowed_entities' ), 10, 2 );

	}

	/**
	 * Add a new tab to the ATUM settings page
	 *
	 * @since 0.1.1
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_settings_tab( $tabs ) {

		$tabs['barcodes_pro'] = array(
			'label'    => __( 'Barcodes', ATUM_BARCODES_TEXT_DOMAIN ),
			'icon'     => 'atmi-barcodes-pro',
			'sections' => array(
				'bp_barcodes' => __( 'Barcodes PRO Options', ATUM_BARCODES_TEXT_DOMAIN ),
			),
		);

		return $tabs;

	}

	/**
	 * Add fields to the ATUM settings page
	 *
	 * @since 0.1.1
	 *
	 * @param array $defaults
	 *
	 * @return array
	 */
	public function add_settings_defaults( $defaults ) {

		$allowed_entities = array_merge( Globals::get_allowed_post_types(), Globals::get_allowed_taxonomies() );
		$entities_options = array();

		// All enabled by default.
		foreach ( $allowed_entities as $entity_label => $entity_key ) {
			$entities_options[ $entity_key ] = array(
				'value' => 'yes',
				'name'  => $entity_label,
			);
		}

		$bp_settings = array(
			'bp_default_barcode_type'  => array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Default barcode type', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Choose the barcode type that will be used by default for all the entities that support barcodes.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => 'EAN13',
				'options' => array(
					'values' => Globals::get_allowed_barcodes(),
					'style'  => 'width:200px',
				),
			),
			'bp_show_text'             => array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Show barcode text', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Show the related text under the barcode images (only applicable to 1D barcodes).', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'bp_color'                 => array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Barcode color', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Choose a color for the barcode.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'color',
				'default' => '#000',
			),
			'bp_orders_barcodes'       => array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Automatic barcodes for orders', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Create barcodes for all the order entities (WooCommerce Orders, Purchase Orders, Inventory Logs, etc) based on their ID or number.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'bp_entities_support'      => array(
				'group'           => 'barcodes_pro',
				'section'         => 'bp_barcodes',
				'name'            => __( 'Entities that support barcodes', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'            => __( 'Choose the entities to which you want to add barcodes support.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'            => 'multi_checkbox',
				'default_options' => $entities_options,
			),
			'bp_list_table_barcodes'   => array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Show barcodes in List Tables', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Show the barcode images instead of text on all the ATUM list tables that include this column.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'switcher',
				'default' => 'yes',
			),
			'bp_tool_convert_barcodes' => array(
				'group'   => 'tools',
				'section' => 'tools',
				'name'    => __( 'Convert barcodes from 3rd party plugins', ATUM_BARCODES_TEXT_DOMAIN ) . '<br><span class="label label-secondary">' . __( 'Barcodes PRO', ATUM_BARCODES_TEXT_DOMAIN ) . '</span>',
				'desc'    => __( "Convert barcode previously saved by 3rd party plugins as custom meta keys to ATUM barcodes. If you already have a value for the ATUM barcode it won't be overridden", ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'script_runner',
				'options' => array(
					'fields'        => array(
						array(
							'type'        => 'text',
							'placeholder' => __( 'Enter the meta key name to convert...', ATUM_BARCODES_TEXT_DOMAIN ),
							'value'       => '',
						),
					),
					'button_text'   => __( 'Convert Now!', ATUM_BARCODES_TEXT_DOMAIN ),
					'script_action' => 'atum_tool_bp_convert_barcodes',
					'confirm_msg'   => esc_attr( __( 'This will convert all the meta keys found with the name provided to ATUM barcodes.', ATUM_BARCODES_TEXT_DOMAIN ) ),
					'wrapper_class' => 'bp-convert-barcodes',
				),
			),
		);

		// Add the email/pdf settings depending on the enabled entities.
		$enabled_entities = AtumHelpers::get_option( 'bp_entities_support', [] );

		if ( empty( $enabled_entities ) || ( ! empty( $enabled_entities['options'] ) && 'yes' === $enabled_entities['options']['shop_order'] ) ) {

			$bp_settings['bp_shop_order_emails'] = array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Add barcodes to WC Order emails', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Choose whether to add ATUM barcodes to WooCommerce order emails.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => 'items_and_orders',
				'options' => array(
					'values' => array(
						'items_and_orders' => __( 'To items and orders', ATUM_BARCODES_TEXT_DOMAIN ),
						'orders_only'      => __( 'To orders only', ATUM_BARCODES_TEXT_DOMAIN ),
						'items_only'       => __( 'To items only', ATUM_BARCODES_TEXT_DOMAIN ),
						'no'               => __( 'Do not add', ATUM_BARCODES_TEXT_DOMAIN ),
					),
					'style'  => 'width:200px',
				),
			);

		}

		if ( empty( $enabled_entities ) || ( ! empty( $enabled_entities['options'] ) && 'yes' === $enabled_entities['options'][ PurchaseOrders::POST_TYPE ] ) ) {

			$bp_settings['bp_po_pdfs'] = array(
				'group'   => 'barcodes_pro',
				'section' => 'bp_barcodes',
				'name'    => __( 'Add barcodes to PO PDFs', ATUM_BARCODES_TEXT_DOMAIN ),
				'desc'    => __( 'Choose whether to add ATUM barcodes to Purchase Orders PDFs.', ATUM_BARCODES_TEXT_DOMAIN ),
				'type'    => 'select',
				'default' => 'items_and_orders',
				'options' => array(
					'values' => array(
						'items_and_orders' => __( 'To items and orders', ATUM_BARCODES_TEXT_DOMAIN ),
						'orders_only'      => __( 'To orders only', ATUM_BARCODES_TEXT_DOMAIN ),
						'items_only'       => __( 'To items only', ATUM_BARCODES_TEXT_DOMAIN ),
						'no'               => __( 'Do not add', ATUM_BARCODES_TEXT_DOMAIN ),
					),
					'style'  => 'width:200px',
				),
			);

		}

		return array_merge( $defaults, apply_filters( 'atum/barcodes_pro/add_settings_options', $bp_settings ) );

	}

	/**
	 * Special sanitization for the allowed entities.
	 *
	 * @since 0.2.0
	 *
	 * @param array $options
	 * @param array $input
	 *
	 * @return array
	 */
	public function sanitize_allowed_entities( $options, $input ) {

		// No matter the HPOS is enabled or not, save all the possible entity names for orders always.
		if ( ! empty( $input['settings_section'] ) && 'barcodes_pro' === $input['settings_section'] && ! empty( $input['bp_entities_support']['options'] ) ) {

			if ( ! empty( $input['bp_entities_support']['options']['shop_order'] ) ) {
				$options['bp_entities_support']['options']['woocommerce_page_wc-orders'] = $input['bp_entities_support']['options']['shop_order'];
			}
			elseif ( ! empty( $input['bp_entities_support']['options']['woocommerce_page_wc-orders'] ) ) {
				$options['bp_entities_support']['options']['shop_order'] = $input['bp_entities_support']['options']['woocommerce_page_wc-orders'];
			}

		}

		return $options;

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
	 * @return Settings instance
	 */
	public static function get_instance() {
		if ( ! ( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
