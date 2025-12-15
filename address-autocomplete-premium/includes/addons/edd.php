<?php
class WPSunshine_Address_Autocomplete_EDD {

	public function __construct() {

		add_filter( 'wps_aa_addons', array( $this, 'register' ), 99 );

		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return;
		}

		add_action( 'wps_aa_instances', array( $this, 'add_instances' ) );

	}

	public function register( $addons ) {
		$addons['edd_checkout']  = __( 'EDD Checkout', 'address-autocomplete-anything' );
		$addons['edd_myaccount'] = __( 'EDD My Account', 'address-autocomplete-anything' );
		return $addons;
	}

	public function add_instances( $instances ) {
		global $post;

		$addons = get_option( 'wps_aa_addons' );
		if ( empty( $addons ) ) {
			return $instances;
		}

		$build_instances = false;

		if ( in_array( 'edd_checkout', $addons ) && ( edd_is_checkout() || is_admin() ) ) {
			$build_instances = true;
		}

		if ( in_array( 'edd_myaccount', $addons ) && ( has_shortcode( $post->post_content, 'edd_profile_editor' ) || is_admin() ) ) {
			$build_instances = true;
		}

		if ( $build_instances ) {

			if ( ! array_key_exists( 'edd_checkout', $instances ) ) {

				$fields = array();
				// Build instance data for Billing
				$fields[] = array(
					'selector' => '#billing_country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#card_address',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#card_address_2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#card_city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#card_state',
					'data'     => '{administrative_area_level_1:short_name}',
					'delay'    => 500,
				);
				$fields[] = array(
					'selector' => '#card_zip',
					'data'     => '{postal_code:long_name}',
				);

				$instances['edd_checkout'] = array(
					'label'  => 'EDD Checkout',
					'init'   => '#card_address',
					'page'   => ( edd_get_option( 'purchase_page' ) ),
					'fields' => $fields,
				);

			}

			if ( ! array_key_exists( 'edd_myaccount', $instances ) ) {

				$fields = array();
				// Build instance data for Billing
				$fields[] = array(
					'selector' => '#edd_address_country',
					'data'     => '{country:short_name}',
				);
				$fields[] = array(
					'selector' => '#edd_address_line1',
					'data'     => '{address1:long_name}',
				);
				$fields[] = array(
					'selector' => '#edd_address_line2',
					'data'     => '{address2:long_name}',
				);
				$fields[] = array(
					'selector' => '#edd_address_city',
					'data'     => '{locality:long_name}',
				);
				$fields[] = array(
					'selector' => '#edd_address_state',
					'data'     => '{administrative_area_level_1:short_name}',
					'delay'    => 500,
				);
				$fields[] = array(
					'selector' => '#edd_address_zip',
					'data'     => '{postal_code:long_name}',
				);

				$instances['edd_myaccount'] = array(
					'label'  => 'EDD My Account',
					'init'   => '#edd_address_line1',
					'fields' => $fields,
				);

			}
		}

		return $instances;

	}

}

$wps_aa_edd = new WPSunshine_Address_Autocomplete_EDD();
