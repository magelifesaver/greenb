<?php
/**
 * Store Credit Settings.
 *
 * @package WC_Store_Credit/Admin
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Store_Credit_Admin_Settings', false ) ) {
	return new WC_Store_Credit_Admin_Settings();
}

/**
 * WC_Store_Credit_Admin_Settings class.
 */
class WC_Store_Credit_Admin_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 *
	 * @since 3.0.0
	 */
	public function __construct() {
		$this->id    = 'store_credit';
		$this->label = __( 'Store credit', 'woocommerce-store-credit' );

		parent::__construct();

		add_filter( "woocommerce_get_settings_{$this->id}", [ $this, 'register_settings' ], 0 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_wc_store_credit_cart_notice', [ $this, 'sanitize_cart_notice' ] );
	}

	/**
	 * Registers the plugin settings.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function register_settings() {
		$settings = [
			[
				'id'   => 'store_credit_general',
				/* translators: Context: Settings section matching plugin name */
				'name' => __( 'Store credit', 'woocommerce-store-credit' ),
				'desc' => __( 'The following options are specific to store credit coupons.', 'woocommerce-store-credit' ),
				'type' => 'title',
			],
			[
				'id'      => 'wc_store_credit_show_my_account',
				/* translators: Context: Settings label matching the WooCommerce "My Account" page title */
				'name'    => __( 'My Account', 'woocommerce-store-credit' ),
				'desc'    => __( 'Display store credit on the My Account page.', 'woocommerce-store-credit' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'id'      => 'wc_store_credit_delete_after_use',
				'name'    => __( 'Delete after use', 'woocommerce-store-credit' ),
				'desc'    => __( 'Delete the coupon when the credit is exhausted.', 'woocommerce-store-credit' ),
				'type'    => 'checkbox',
				'default' => 'yes',
			],
			[
				'id'      => 'wc_store_credit_individual_use',
				/* translators: Context: Settings label matching the WooCommerce "Individual use" coupon option */
				'name'    => __( 'Individual use', 'woocommerce-store-credit' ),
				'desc'    => __( 'The coupon cannot be used in conjunction with other coupons.', 'woocommerce-store-credit' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],
		];

		$setting_inc_tax = [
			'id'       => 'wc_store_credit_inc_tax',
			/* translators: Context: Tax-inclusive coupon */
			'name'     => __( 'Include tax', 'woocommerce-store-credit' ),
			'desc'     => __( 'The coupon amount includes taxes.', 'woocommerce-store-credit' ),
			'desc_tip' => __( "The options 'Prices entered with tax' and 'Round tax at subtotal' must be enabled.", 'woocommerce-store-credit' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		];

		// Disable it if the option is not available.
		if ( ! wc_store_credit_coupons_can_inc_tax() ) {
			$setting_inc_tax['value']             = 'no';
			$setting_inc_tax['custom_attributes'] = [ 'disabled' => true ];
		}

		$settings[] = $setting_inc_tax;

		$setting_apply_to_shipping = [
			'id'      => 'wc_store_credit_apply_to_shipping',
			/* translators: Context: Setting label to apply a store credit coupon to shipping */
			'name'    => __( 'Apply to shipping', 'woocommerce-store-credit' ),
			'desc'    => __( 'Apply the remaining coupon amount to the shipping costs.', 'woocommerce-store-credit' ),
			'type'    => 'checkbox',
			'default' => 'no',
		];

		// Disable it if the option is not available.
		if ( ! wc_shipping_enabled() ) {
			$setting_apply_to_shipping['value']             = 'no';
			$setting_apply_to_shipping['desc_tip']          = __( 'Shipping not enabled.', 'woocommerce-store-credit' );
			$setting_apply_to_shipping['custom_attributes'] = [ 'disabled' => true ];
		}

		$settings[] = $setting_apply_to_shipping;

		/* translators: Placeholders: %s - List of placeholders */
		$placeholder_text = sprintf( __( 'Available placeholders: %s', 'woocommerce-store-credit' ), '{coupon_code}' );

		$settings[] = [
			'id'          => 'wc_store_credit_code_format',
			/* translators: Context: Setting label to format the store credit coupon code */
			'name'        => __( 'Coupon code format', 'woocommerce-store-credit' ),
			'desc'        => $placeholder_text,
			'desc_tip'    => true,
			'type'        => 'text',
			'placeholder' => '{coupon_code}',
		];

		$settings[] = [
			'id'      => 'wc_store_credit_show_cart_notice',
			/* translators: Context: Setting label for the store credit coupon display options */
			'name'    => __( 'Display coupons', 'woocommerce-store-credit' ),
			'desc'    => __( "Display the customer's coupons on the Cart and Checkout pages.", 'woocommerce-store-credit' ),
			'type'    => 'checkbox',
			'default' => 'yes',
		];

		$notice_text_instructions = __( 'Enter the message to display on the cart and checkout pages when the customer has coupons available. HTML is not supported.', 'woocommerce-store-credit' );
		/* translators: Placeholder: %s - Represents a merge tag., ie [link] surrounded by <code> HTML tags */
		$notice_text_instructions .= ' ' . sprintf( __( 'You can use a %s merge tag to toggle showing the coupons list. However, this tag will not work when using the cart and checkout blocks and should be omitted then.', 'woocommerce-store-credit' ), '<code>[link]</code>' );
		$notice_text_instructions .= '<br><br>';

		$settings[] = [
			'id'                => 'wc_store_credit_cart_notice',
			'name'              => __( 'Coupons notice', 'woocommerce-store-credit' ),
			'desc'              => $notice_text_instructions,
			'desc_tip'          => false,
			'type'              => 'textarea',
			'placeholder'       => sprintf(
				'%1$s [link]%2$s[/link]',
				__( 'You have store credit coupons available!', 'woocommerce-store-credit' ),
				__( 'View coupons', 'woocommerce-store-credit' )
			),
			'custom_attributes' => [
				'rows' => 3,
			],
		];

		$settings[] = [
			'id'   => 'store_credit_general',
			'type' => 'sectionend',
		];

		return $settings;
	}

	/**
	 * Sanitizes the option 'cart_notice'.
	 *
	 * @since 4.2.0
	 *
	 * @param string $value The option value.
	 * @return string
	 */
	public function sanitize_cart_notice( $value ) {
		if ( ! empty( $value ) && ! preg_match( '/\[link].+\[\/link]/', $value ) ) {
			$value  = str_replace( [ '[link]', '[/link]' ], '', $value );
			$value .= ' [link]' . __( 'View coupons', 'woocommerce-store-credit' ) . '[/link]';
		}

		return $value;
	}
}

return new WC_Store_Credit_Admin_Settings();
