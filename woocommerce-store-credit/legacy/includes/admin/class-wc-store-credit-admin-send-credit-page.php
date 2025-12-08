<?php
/**
 * The Send Store Credit admin page.
 *
 * @package WC_Store_Credit/Admin
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Store_Credit_Admin_Send_Credit_Page class.
 */
class WC_Store_Credit_Admin_Send_Credit_Page {

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	private static $errors = [];

	/**
	 * Update messages.
	 *
	 * @var array
	 */
	private static $messages = [];

	/**
	 * Initializes the page.
	 *
	 * @since 3.0.0
	 */
	public static function init() {
		if (
			! empty( $_POST['save'] ) && ! empty( $_POST['_wpnonce'] ) &&
			wp_verify_nonce( wc_clean( wp_unslash( $_POST['_wpnonce'] ) ), 'wc_send_store_credit' )
		) {
			self::save();
		}
	}

	/**
	 * Adds a message.
	 *
	 * @since 3.0.0
	 *
	 * @param string $text Message.
	 */
	public static function add_message( $text ) {
		self::$messages[] = $text;
	}

	/**
	 * Adds an error.
	 *
	 * @since 3.0.0
	 *
	 * @param string $text Message.
	 */
	public static function add_error( $text ) {
		self::$errors[] = $text;
	}

	/**
	 * Outputs messages + errors.
	 *
	 * @since 3.0.0
	 */
	public static function show_messages() {
		if ( count( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error ) {
				echo '<div id="message" class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
			}
		} elseif ( count( self::$messages ) > 0 ) {
			foreach ( self::$messages as $message ) {
				echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}
	}

	/**
	 * Gets the form fields.
	 *
	 * @since 3.0.0
	 * @internal
	 *
	 * @return array<array<string, mixed>>
	 */
	public static function get_form_fields() : array {

		$product_categories_options = wc_store_credit_get_product_categories_choices( true );

		$fields = [
			[
				'id'   => 'send_store_credit_section',
				'type' => 'title',
				'desc' => __( 'Send a store credit coupon to a customer or multiple customers at once.', 'woocommerce-store-credit' ),
			],
			[
				'id'          => 'credit_amount',
				'title'       => __( 'Credit amount', 'woocommerce-store-credit' ),
				'desc'        => __( 'The amount the store credit coupon is worth.', 'woocommerce-store-credit' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'class'       => 'wc_input_price',
				'default'     => '',
				'placeholder' => wp_strip_all_tags( wc_price( '10', [ 'price_format' => '%2$s' ] ) ), // remove currency from format
			],
			[
				'id'                => 'customer_id',
				'title'             => __( 'Customer(s)', 'woocommerce-store-credit' ),
				'desc_tip'          => __( 'The customers who will receive the coupon.', 'woocommerce-store-credit' ),
				'type'              => 'multiselect',
				'desc'              => __( 'You can also enter emails from non-registered customers.', 'woocommerce-store-credit' ),
				'class'             => 'wc-customer-search',
				'multiple'          => true,
				'options'           => [],
				'custom_attributes' => [
					'data-placeholder' => __( 'Choose one or more customers&hellip;', 'woocommerce-store-credit' ),
					'data-tags'        => true, // allow guest users
				],
			],
			[
				'id'                => 'customer_note',
				/* translators: Context: Form field label for a store credit coupon note */
				'title'             => __( 'Note', 'woocommerce-store-credit' ),
				'type'              => 'textarea',
				'desc_tip'          => true,
				'desc'              => __( 'A note for the customer who will receive the coupon.', 'woocommerce-store-credit' ),
				'placeholder'       => __( 'Enter a note or the reason for this coupon.', 'woocommerce-store-credit' ),
				'custom_attributes' => [
					'rows' => 5,
				],
			],
			[
				'id'       => 'expiration',
				/* translators: Context: Form field label for a store credit coupon expiration */
				'title'    => __( 'Expiration', 'woocommerce-store-credit' ),
				'type'     => 'select',
				'desc_tip' => __( 'Define when the coupon expires.', 'woocommerce-store-credit' ),
				'value'    => 'never',
				'options'  => [
					/* translators: Context: Store credit coupon never expires */
					'never'  => __( 'Never expires', 'woocommerce-store-credit' ),
					/* translators: Context: Store credit coupon expires at a specific date */
					'date'   => __( 'Specific date', 'woocommerce-store-credit' ),
					/* translators: Context: Store credit coupon expires after a specific period */
					'period' => __( 'Specific period', 'woocommerce-store-credit' ),
				],
			],
			[
				'id'                => 'expiration_date',
				'desc_tip'          => __( 'The coupon will expire on the specified date.', 'woocommerce-store-credit' ),
				'type'              => 'text',
				'class'             => 'date-picker',
				'css'               => 'width:150px;',
				'placeholder'       => gmdate( 'Y-m-d' ),
				'custom_attributes' => [
					'pattern'      => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
					'maxlength'    => 10,
					'data-minDate' => gmdate( 'Y-m-d' ),
				],
			],
			[
				'id'       => 'expiration_period',
				'desc_tip' => __( 'The coupon will expire when the period passes.', 'woocommerce-store-credit' ),
				'type'     => 'relative_date_selector',
				'options'  => [],
			],
			[
				'id'   => 'send_store_credit_section',
				'type' => 'sectionend',
			],
			[
				'id'    => 'send_store_credit_restrictions_section',
				'type'  => 'title',
				/* translators: Context: Section title for the usage restrictions of a store credit coupon */
				'title' => __( 'Usage restriction', 'woocommerce-store-credit' ),
			],
			[
				'id'    => 'individual_use',
				/* translators: Context: Form field label for a store credit coupon individual use */
				'title' => __( 'Individual use only', 'woocommerce-store-credit' ),
				'desc'  => __( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce-store-credit' ),
				'type'  => 'checkbox',
				'value' => get_option( 'wc_store_credit_individual_use', 'no' ),
			],
			[
				'id'    => 'exclude_sale_items',
				'title' => __( 'Exclude sale items', 'woocommerce-store-credit' ),
				'desc'  => __( 'Check this box if the coupon should not apply to items on sale.', 'woocommerce-store-credit' ),
				'type'  => 'checkbox',
				'value' => 'no',
			],
			[
				'id'                => 'product_ids',
				'title'             => __( 'Products', 'woocommerce-store-credit' ),
				'desc_tip'          => __( 'Product that the coupon will be applied to, or that need to be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'type'              => 'multiselect',
				'class'             => 'wc-product-search',
				'options'           => [],
				'custom_attributes' => [
					'multiple'         => true,
					'data-placeholder' => __( 'Search for a product&hellip;', 'woocommerce-store-credit' ),
				],
			],
			[
				'id'                => 'excluded_product_ids',
				'title'             => __( 'Exclude products', 'woocommerce-store-credit' ),
				'desc_tip'          => __( 'Product that the coupon will not be applied to, or that cannot be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'type'              => 'multiselect',
				'class'             => 'wc-product-search',
				'options'           => [],
				'custom_attributes' => [
					'multiple'         => true,
					'data-placeholder' => __( 'Search for a product&hellip;', 'woocommerce-store-credit' ),
				],
			],
			[
				'id'                => 'product_categories',
				'title'             => __( 'Product categories', 'woocommerce-store-credit' ),
				'desc_tip'          => __( 'Product categories that the coupon will be applied to, or that need to be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'options'           => $product_categories_options,
				'custom_attributes' => [
					'data-placeholder' => __( 'Select product categories&hellip;', 'woocommerce-store-credit' ),
				],
			],
			[
				'id'                => 'excluded_product_categories',
				'title'             => __( 'Exclude categories', 'woocommerce-store-credit' ),
				'desc_tip'          => __( 'Product categories that the coupon will not be applied to, or that cannot be in the cart in order to be applied.', 'woocommerce-store-credit' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'options'           => $product_categories_options,
				'custom_attributes' => [
					'data-placeholder' => __( 'Select product categories&hellip;', 'woocommerce-store-credit' ),
				],
			],
			[
				'id'   => 'send_store_credit_restrictions_section',
				'type' => 'sectionend',
			],
		];

		/**
		 * Filters the 'Send Store Credit' form fields.
		 *
		 * @since 3.0.0
		 *
		 * @param array $fields The form fields.
		 */
		return apply_filters( 'wc_store_credit_send_credit_form_fields', $fields );
	}

	/**
	 * Gets the form field value.
	 *
	 * @since 3.1.0
	 *
	 * @param array $field The field data.
	 * @return mixed
	 */
	public static function get_form_field_value( $field ) {
		return ( isset( $_POST[ $field['id'] ] ) ? wc_clean( wp_unslash( $_POST[ $field['id'] ] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Outputs the page content.
	 *
	 * @since 3.0.0
	 */
	public static function output() {
		$fields = self::get_form_fields();

		// Populate the form fields' values if the form contains errors.
		if ( ! empty( self::$errors ) ) {
			$fields = self::populate_form_fields_values( $fields );
		}

		include __DIR__ . '/views/html-admin-page-send-credit.php';
	}

	/**
	 * Saves the page form.
	 *
	 * @since 3.0.0
	 * @internal
	 *
	 * @return void
	 */
	public static function save() : void {

		$data   = self::get_sanitized_data();
		$amount = wc_format_decimal( $data['credit_amount'], false, true );

		if ( empty( $amount ) ) {
			self::add_error( __( 'You need to provide a credit amount for the coupon.', 'woocommerce-store-credit' ) );
			return;
		}

		if ( empty( $data['customer_id'] ) ) {
			self::add_error( __( 'You need to choose a customer who to send the coupon.', 'woocommerce-store-credit' ) );
			return;
		}

		$emails         = array_map( 'trim', is_array( $data['customer_id'] ) ? $data['customer_id'] : explode( ',', $data['customer_id'] ) );
		$invalid_emails = [];

		foreach ( $emails as $email ) {
			$email = wc_store_credit_get_customer_email( $email );

			if ( ! $email ) {
				$invalid_emails[] = $email;
			}
		}

		if ( ! empty( $invalid_emails ) ) {
			/* translators: Placeholder: %s - Comma-separated list of invalid emails */
			self::add_error( sprintf( esc_html__( 'The following emails are invalid: %s.', 'woocommerce-store-credit' ), implode( ', ', wc_clean( $invalid_emails ) ) ) );
			return;
		}

		if ( 'date' === $data['expiration'] && ! $data['expiration_date'] ) {
			self::add_error( __( 'An expiration date is required.', 'woocommerce-store-credit' ) );
			return;
		}

		if ( 'period' === $data['expiration'] && ! $data['expiration_period']['number'] ) {
			self::add_error( __( 'An expiration period is required.', 'woocommerce-store-credit' ) );
			return;
		}

		$args = [];

		if ( ! empty( $data['customer_note'] ) ) {
			$args['description'] = $data['customer_note'];
		}

		if ( 'never' !== $data['expiration'] ) {
			$args['expiration'] = $data[ "expiration_{$data['expiration']}" ];
		}

		$bool_props = [ 'individual_use', 'exclude_sale_items' ];

		foreach ( $bool_props as $bool_prop ) {
			$args[ $bool_prop ] = wc_string_to_bool( isset( $data[ $bool_prop ] ) && $data[ $bool_prop ] );
		}

		$keys = [ 'product_ids', 'excluded_product_ids', 'product_categories', 'excluded_product_categories' ];

		foreach ( $keys as $key ) {
			if ( ! empty( $data[ $key ] ) ) {
				$args[ $key ] = $data[ $key ];
			}
		}

		if ( wc_store_credit_send_credit_to_customer( $emails, $amount, $args ) ) {
			self::add_message( _n( 'Store credit sent to the customer.', 'Store credit sent to the customers.', count( $emails ), 'woocommerce-store-credit' ) );
		} else {
			self::add_error( __( 'An unexpected error occurred.', 'woocommerce-store-credit' ) );
		}
	}

	/**
	 * Gets if the field needs to be validated.
	 *
	 * @since 3.1.0
	 *
	 * @param array $field The field data.
	 * @return bool
	 */
	protected static function needs_validation( $field ) {
		return (
			! empty( $field['id'] ) &&
			! empty( $field['type'] ) &&
			! in_array( $field['type'], [ 'title', 'sectionend' ], true )
		);
	}

	/**
	 * Populates the form fields' values.
	 *
	 * @since 3.1.0
	 *
	 * @param array $fields An array with the fields' data.
	 * @return array
	 */
	protected static function populate_form_fields_values( $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( ! self::needs_validation( $field ) ) {
				continue;
			}

			$value = self::get_form_field_value( $field );

			if ( 'customer_id' === $field['id'] ) {
				$customers = (array) $value;
				$options   = [];

				foreach ( $customers as $customer_id ) {
					$label                   = is_numeric( $customer_id ) ? wc_store_credit_get_customer_choice_label( intval( $customer_id ) ) : $customer_id;
					$options[ $customer_id ] = $label;
				}

				$field['options'] = $options;
				$field['value']   = $value;
			} elseif ( 'checkbox' === $field['type'] ) {
				$field['value'] = wc_bool_to_string( $value );
			} elseif ( 'product_ids' === $field['id'] || 'excluded_product_ids' === $field['id'] ) {
				$product_ids = array_filter( (array) $value );

				$field['options'] = array_combine( $product_ids, array_map( 'wc_store_credit_get_product_choice_label', $product_ids ) );
				$field['value']   = $value;
			} else {
				$field['value'] = $value;
			}

			$fields[ $key ] = $field;
		}

		return $fields;
	}

	/**
	 * Sanitizes the posted data.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	protected static function get_sanitized_data() {
		$data   = [];
		$fields = self::get_form_fields();

		foreach ( $fields as $field ) {
			if ( ! self::needs_validation( $field ) ) {
				continue;
			}

			$value = self::get_form_field_value( $field );

			switch ( $field['id'] ) {
				case 'credit_amount':
					$value = wc_format_decimal( $value );
					break;
				case 'customer_id':
					$value = wc_clean( $value ); // could be an email or a customer ID, or multiple comma-separated values
					break;
				case 'customer_note':
					$value = sanitize_textarea_field( $value );
					break;
				case 'expiration_period':
					$value = wc_parse_relative_date_option( $value );
					break;
				default:
					$value = wc_clean( $value );
					break;
			}

			$data[ $field['id'] ] = $value;
		}

		/**
		 * Filters the posted data in the 'Send Store Credit' form.
		 *
		 * @since 3.0.0
		 *
		 * @param array $data   The posted data.
		 * @param array $fields The form fields.
		 */
		return apply_filters( 'wc_store_credit_send_credit_form_data', $data, $fields );
	}
}
