<?php
class WPSunshine_Address_Autocomplete_WooCommerce {

	public function __construct() {

		add_filter( 'wps_aa_addons', array( $this, 'register' ), 99 );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'wps_aa_instances', array( $this, 'add_instances' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_checkout_block' ) );

	}

	public function register( $addons ) {
		$addons['woocommerce_checkout']  = __( 'WooCommerce Checkout', 'address-autocomplete-anything' );
		$addons['woocommerce_myaccount'] = __( 'WooCommerce My Account', 'address-autocomplete-anything' );
		return $addons;
	}

	public function add_instances( $instances ) {

		$addons = get_option( 'wps_aa_addons' );
		if ( empty( $addons ) ) {
			return $instances;
		}

		$build_instances = false;

		if ( in_array( 'woocommerce_checkout', $addons ) && ( is_checkout() || is_admin() ) ) {
			$build_instances = true;
		}

		if ( in_array( 'woocommerce_myaccount', $addons ) && ( is_wc_endpoint_url( 'edit-address' ) || is_admin() ) ) {
			$build_instances = true;
		}

		if ( $build_instances ) {

			$countries = new WC_Countries();
			$countries = $countries->get_allowed_countries();

			if ( ! array_key_exists( 'woocommerce_checkout_billing', $instances ) ) {

				$fields = array();
				// Build instance data for Billing
				$fields[] = array(
					'selector' => '#billing_country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#billing_address_1',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing_address_2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing_city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing_state',
					'data'     => '{administrative_area_level_1:short_name}',
				);
				$fields[] = array(
					'selector' => '#billing_postcode',
					'data'     => '{postal_code:long_name}',
				);

				$instances['woocommerce_checkout_billing'] = array(
					'label'             => 'WooCommerce Billing',
					'init'              => '#billing_address_1',
					'page'              => '',
					'allowed_countries' => ( count( $countries ) <= 5 ) ? array_keys( $countries ) : '',
					'fields'            => $fields,
				);

			}

			if ( ! array_key_exists( 'woocommerce_checkout_billing_block', $instances ) ) {

				$fields = array();
				// Build instance data for Billing
				$fields[] = array(
					'selector' => '#billing-country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#billing-address_1',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing-address_2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing-city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#billing-state',
					'data'     => '{administrative_area_level_1:short_name}',
				);
				$fields[] = array(
					'selector' => '#billing-postcode',
					'data'     => '{postal_code:long_name}',
				);

				$instances['woocommerce_checkout_billing_block'] = array(
					'label'             => 'WooCommerce Billing (Block)',
					'init'              => '#billing-address_1',
					'page'              => '',
					'allowed_countries' => ( count( $countries ) <= 5 ) ? array_keys( $countries ) : '',
					'fields'            => $fields,
				);

			}

			if ( ! array_key_exists( 'woocommerce_checkout_shipping', $instances ) ) {

				$fields = array();
				// Build instance data for Shipping
				$fields[] = array(
					'selector' => '#shipping_country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#shipping_address_1',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping_address_2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping_city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping_state',
					'data'     => '{administrative_area_level_1:short_name}',
				);
				$fields[] = array(
					'selector' => '#shipping_postcode',
					'data'     => '{postal_code:long_name}',
				);

				$instances['woocommerce_checkout_shipping'] = array(
					'label'             => 'WooCommerce Shipping',
					'init'              => '#shipping_address_1',
					'page'              => '',
					'allowed_countries' => ( count( $countries ) <= 5 ) ? array_keys( $countries ) : '',
					'fields'            => $fields,
				);

			}

			if ( ! array_key_exists( 'woocommerce_checkout_shipping_block', $instances ) ) {

				$fields = array();
				// Build instance data for Shipping in Checkout Block
				$fields[] = array(
					'selector' => '#shipping-country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#shipping-address_1',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping-address_2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping-city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#shipping-state',
					'data'     => '{administrative_area_level_1:short_name}',
				);
				$fields[] = array(
					'selector' => '#shipping-postcode',
					'data'     => '{postal_code:long_name}',
				);

				$instances['woocommerce_checkout_shipping_block'] = array(
					'label'             => 'WooCommerce Shipping (Block)',
					'init'              => '#shipping-address_1',
					'page'              => '',
					'allowed_countries' => ( count( $countries ) <= 5 ) ? array_keys( $countries ) : '',
					'fields'            => $fields,
				);

			}
		}

		return $instances;

	}

	public function enqueue_checkout_block() {

		if ( is_checkout() ) {

			wp_register_script(
				'wps-aa-woocommerce-checkout-block',
				WPS_AA_PREMIUM_PLUGIN_URL . 'assets/js/woocommerce-checkout-block.js', // Adjust the path to your script
				array( 'jquery', 'wp-blocks', 'wp-element', 'wp-components', 'wp-i18n', 'wc-blocks-checkout', 'wp-data', 'wp-hooks' ), // Dependencies
				WPS_AA_PREMIUM_VERSION,
				true
			);

			wp_enqueue_script( 'wps-aa-woocommerce-checkout-block' );
			wp_localize_script(
				'wps-aa-woocommerce-checkout-block',
				'wc_store_api_nonce',
				array(
					'nonce' => wp_create_nonce( 'wc_store_api' ),
				)
			);

		}

	}

}

$wps_aa_woocommerce = new WPSunshine_Address_Autocomplete_WooCommerce();
