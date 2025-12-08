<?php
/**
 * Meta Box: Product Data
 *
 * Updates the Product Data meta box.
 *
 * @package WC_Store_Credit/Admin/Meta_Boxes
 * @since   3.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Store_Credit_Admin_Send_Credit_Page class.
 */
class WC_Store_Credit_Meta_Box_Product_Data {

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', [ $this, 'product_data_tabs' ], 10, 2 );
		add_action( 'woocommerce_product_data_panels', [ $this, 'product_data_panels' ] );
		add_action( 'woocommerce_product_options_pricing', [ $this, 'product_data_options_pricing' ], 20 );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_product_data' ] );
	}

	/**
	 * Customizes the product data tabs.
	 *
	 * @since 3.2.0
	 *
	 * @param array $tabs An array with the product data tabs.
	 * @return array
	 */
	public function product_data_tabs( $tabs ) {
		$tab_classes = [
			'inventory' => 'show_if_store_credit',
			'shipping'  => 'hide_if_store_credit',
		];

		foreach ( $tab_classes as $tab => $class ) {
			if ( isset( $tabs[ $tab ] ) ) {
				$classes   = ( ! empty( $tabs[ $tab ]['class'] ) ? $tabs[ $tab ]['class'] : [] );
				$classes[] = $class;

				$tabs[ $tab ]['class'] = $classes;
			}
		}

		$tabs['store_credit'] = [
			'label'    => __( 'Store credit', 'woocommerce-store-credit' ),
			'target'   => 'store_credit_product_data',
			'class'    => [ 'show_if_store_credit' ],
			'priority' => 25,
		];

		return $tabs;
	}

	/**
	 * Gets the fields for the 'Store Credit' tab displayed in the product data meta box.
	 *
	 * @since 3.2.0
	 * @since 4.0.0 Added parameter `$section`.
	 *
	 * @param WC_Product $product Product object.
	 * @param string     $section Optional. The section in which the fields will be displayed. Default empty.
	 * @return array
	 */
	protected function get_store_credit_fields( $product, $section = '' ) {
		$fields = [];
		$values = $product->get_meta( '_store_credit_data' );

		if ( ! $values ) {
			$values = [];
		}

		if ( ! $section || 'options_pricing' === $section ) {
			$currency_symbol = get_woocommerce_currency_symbol();

			$fields = [
				'amount'              => [
					'id'          => '_store_credit_amount',
					'label'       => __( 'Coupon amount', 'woocommerce-store-credit' ) . " ({$currency_symbol})",
					'type'        => 'text',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => __( 'Value of the coupon. Default: regular price.', 'woocommerce-store-credit' ),
					'value'       => $values['amount'] ?? '',
				],
				'preset_amounts'      => [
					'id'          => '_store_credit_preset_amounts',
					'label'       => __( 'Preset amounts', 'woocommerce-store-credit' ) . " ({$currency_symbol})",
					'type'        => 'text',
					'desc_tip'    => true,
					'description' => __( 'List of predefined credit amounts. Use "|" to separate the different amounts. For example: 10 | 20 | 30.', 'woocommerce-store-credit' ),
					'value'       => $values['preset_amounts'] ?? '',
				],
				'allow_custom_amount' => [
					'id'          => '_store_credit_allow_custom_amount',
					'label'       => __( 'Custom amount', 'woocommerce-store-credit' ),
					'description' => __( 'Allow the customer to choose the amount of credit to purchase.', 'woocommerce-store-credit' ),
					'type'        => 'checkbox',
					'value'       => $values['allow_custom_amount'] ?? 'no',
				],
				'min_custom_amount'   => [
					'id'          => '_store_credit_min_custom_amount',
					'label'       => __( 'Minimum amount', 'woocommerce-store-credit' ) . " ({$currency_symbol})",
					'type'        => 'text',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => __( 'The minimum amount of credit to purchase.', 'woocommerce-store-credit' ),
					'value'       => $values['min_custom_amount'] ?? '',
				],
				'max_custom_amount'   => [
					'id'          => '_store_credit_max_custom_amount',
					'label'       => __( 'Maximum amount', 'woocommerce-store-credit' ) . " ({$currency_symbol})",
					'type'        => 'text',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => __( 'The maximum amount of credit to purchase.', 'woocommerce-store-credit' ),
					'value'       => $values['max_custom_amount'] ?? '',
				],
				'custom_amount_step'  => [
					'id'          => '_store_credit_custom_amount_step',
					'label'       => __( 'Amount step', 'woocommerce-store-credit' ) . " ({$currency_symbol})",
					'type'        => 'text',
					'data_type'   => 'price',
					'desc_tip'    => true,
					'description' => __( 'The credit amount must be in the specified interval.', 'woocommerce-store-credit' ),
					'value'       => $values['custom_amount_step'] ?? '',
				],
			];
		}

		if ( ! $section || 'store_credit' === $section ) {
			$fields['expiration'] = [
				'id'           => '_store_credit_expiration',
				'label'        => __( 'Coupon expiration', 'woocommerce-store-credit' ),
				'period_label' => __( 'Coupon expiration period', 'woocommerce-store-credit' ),
				'type'         => 'time_period',
				'desc_tip'     => true,
				'description'  => __( 'The coupon will expire passed this period. Leave empty to not expire.', 'woocommerce-store-credit' ),
				'placeholder'  => __( 'N/A', 'woocommerce-store-credit' ),
				'value'        => $values['expiration'] ?? '',
			];

			if ( wc_store_credit_coupons_can_inc_tax() ) {
				$fields['inc_tax'] = [
					'id'          => '_store_credit_inc_tax',
					'label'       => __( 'Include tax', 'woocommerce-store-credit' ),
					'description' => __( 'Check this box if the coupon amount includes taxes.', 'woocommerce-store-credit' ),
					'desc_tip'    => true,
					'type'        => 'select',
					'value'       => $values['inc_tax'] ?? '',
					'options'     => [
						''    => __( 'Default', 'woocommerce-store-credit' ),
						'yes' => __( 'Yes', 'woocommerce-store-credit' ),
						'no'  => __( 'No', 'woocommerce-store-credit' ),
					],
				];
			}

			if ( wc_shipping_enabled() ) {
				$fields['apply_to_shipping'] = [
					'id'          => '_store_credit_apply_to_shipping',
					'label'       => __( 'Apply to shipping', 'woocommerce-store-credit' ),
					'description' => __( 'Check this box to apply the remaining coupon amount to the shipping costs.', 'woocommerce-store-credit' ),
					'desc_tip'    => true,
					'type'        => 'select',
					'value'       => $values['apply_to_shipping'] ?? '',
					'options'     => [
						''    => __( 'Default', 'woocommerce-store-credit' ),
						'yes' => __( 'Yes', 'woocommerce-store-credit' ),
						'no'  => __( 'No', 'woocommerce-store-credit' ),
					],
				];
			}

			$fields['different_receiver_group'] = [
				'type' => 'options_group',
			];

			$fields['allow_different_receiver'] = [
				'id'          => '_store_credit_allow_different_receiver',
				'label'       => __( 'Send to someone', 'woocommerce-store-credit' ),
				'description' => __( 'Allow purchasing credit for a different person.', 'woocommerce-store-credit' ),
				'type'        => 'checkbox',
				'value'       => $values['allow_different_receiver'] ?? 'yes',
			];

			$fields['receiver_fields_title'] = [
				'id'          => '_store_credit_receiver_fields_title',
				'label'       => __( 'Title to display', 'woocommerce-store-credit' ),
				'description' => __( 'Add a title for the receiver form.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'text',
				'value'       => $values['receiver_fields_title'] ?? '',
				'placeholder' => __( 'Send credit to someone?', 'woocommerce-store-credit' ),
			];

			$fields['display_receiver_fields'] = [
				'id'          => '_store_credit_display_receiver_fields',
				'label'       => __( 'Display receiver fields', 'woocommerce-store-credit' ),
				'description' => __( 'How to display the receiver fields on page load.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'select',
				'value'       => $values['display_receiver_fields'] ?? 'collapsed',
				'options'     => [
					'collapsed' => __( 'Collapsed', 'woocommerce-store-credit' ),
					'expanded'  => __( 'Expanded', 'woocommerce-store-credit' ),
				],
			];

			$fields['usage_restriction_section'] = [
				'title' => __( 'Usage restriction', 'woocommerce-store-credit' ),
				'type'  => 'options_group',
			];

			$fields['individual_use'] = [
				'id'          => '_store_credit_individual_use',
				'label'       => __( 'Individual use only', 'woocommerce-store-credit' ),
				'description' => __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce-store-credit' ),
				'type'        => 'checkbox',
				'value'       => $values['individual_use'] ?? 'no',
			];

			$fields['exclude_sale_items'] = [
				'id'          => '_store_credit_exclude_sale_items',
				'label'       => __( 'Exclude sale items', 'woocommerce-store-credit' ),
				'description' => __( 'Check this box if the coupon should not apply to items on sale.', 'woocommerce-store-credit' ),
				'type'        => 'checkbox',
				'value'       => $values['exclude_sale_items'] ?? 'no',
			];

			$fields['products_group'] = [
				'type' => 'options_group',
			];

			$fields['product_ids'] = [
				'id'          => '_store_credit_product_ids',
				'label'       => __( 'Products', 'woocommerce-store-credit' ),
				'description' => __( 'Product that the coupon will be applied to, or that need to be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'product_search',
				'multiple'    => true,
				'value'       => $values['product_ids'] ?? [],
			];

			$fields['excluded_product_ids'] = [
				'id'          => '_store_credit_excluded_product_ids',
				'label'       => __( 'Exclude products', 'woocommerce-store-credit' ),
				'description' => __( 'Product that the coupon will not be applied to, or that cannot be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'product_search',
				'multiple'    => true,
				'value'       => $values['excluded_product_ids'] ?? [],
			];

			$fields['product_categories_group'] = [
				'type' => 'options_group',
			];

			$fields['product_categories'] = [
				'id'          => '_store_credit_product_categories',
				'label'       => __( 'Product categories', 'woocommerce-store-credit' ),
				'description' => __( 'Product categories that the coupon will be applied to, or that need to be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'product_categories',
				'value'       => $values['product_categories'] ?? [],
			];

			$fields['excluded_product_categories'] = [
				'id'          => '_store_credit_excluded_product_categories',
				'label'       => __( 'Exclude categories', 'woocommerce-store-credit' ),
				'description' => __( 'Product categories that the coupon will not be applied to, or that cannot be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'desc_tip'    => true,
				'type'        => 'product_categories',
				'value'       => $values['excluded_product_categories'] ?? [],
			];
		}

		/**
		 * Filters the 'Store Credit' fields to display in the different sections of the product data meta box.
		 *
		 * @since 3.2.0
		 * @since 4.0.0 Added parameter `$section`.
		 *
		 * @param array      $fields  An array with the fields' data.
		 * @param WC_Product $product Product object.
		 * @param string     $section The fields' section.
		 */
		return apply_filters( 'wc_store_credit_product_data_fields', $fields, $product, $section );
	}

	/**
	 * Outputs custom pricing options in the 'General' product data panel.
	 *
	 * @since 4.0.0
	 *
	 * @global WC_Product $product_object The current product object.
	 */
	public function product_data_options_pricing() {
		global $product_object;

		$fields = $this->get_store_credit_fields( $product_object, 'options_pricing' );

		include 'views/html-product-data-options-pricing.php';
	}

	/**
	 * Outputs custom product data panels.
	 *
	 * @since 3.2.0
	 *
	 * @global WC_Product $product_object The current product object.
	 */
	public function product_data_panels() {
		global $product_object;

		$fields = $this->get_store_credit_fields( $product_object, 'store_credit' );

		include 'views/html-product-data-store-credit.php';
	}

	/**
	 * Saves additional product data.
	 *
	 * @since 3.2.0
	 *
	 * @param WC_Product $product Product object.
	 */
	public function save_product_data( $product ) {
		$fields = $this->get_store_credit_fields( $product );
		$values = [];

		foreach ( $fields as $key => $field ) {
			if ( isset( $field['type'] ) && 'options_group' === $field['type'] ) {
				continue;
			}

			$values[ $key ] = wc_store_credit_sanitize_meta_box_field( $field );
		}

		/**
		 * Filters the product data values of a 'Store Credit' product.
		 *
		 * @since 3.2.0
		 *
		 * @param array      $values  The product data values.
		 * @param array      $fields  An array with the fields' data.
		 * @param WC_Product $product Product object.
		 */
		$values = apply_filters( 'wc_store_credit_product_data_values', array_filter( $values ), $fields, $product );

		if ( ! empty( $values ) ) {
			$product->update_meta_data( '_store_credit_data', $values );
		} else {
			$product->delete_meta_data( '_store_credit_data' );
		}
	}

	/**
	 * Gets the value for a 'Store Credit' product field.
	 *
	 * @since 3.6.0
	 *
	 * @global WC_Product $product The current product.
	 *
	 * @param string $key     The field key.
	 * @param mixed  $default Optional. The default value. Default false.
	 * @return mixed
	 */
	public static function get_field_value( $key, $default = false ) {
		global $product;

		$values = $product->get_meta( '_store_credit_data' );

		return ( isset( $values[ $key ] ) ) ? $values[ $key ] : $default;
	}
}

return new WC_Store_Credit_Meta_Box_Product_Data();
