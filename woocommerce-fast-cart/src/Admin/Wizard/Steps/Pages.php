<?php

namespace Barn2\Plugin\WC_Fast_Cart\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Step;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Util as WizardUtils;
use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Api;

/**
 * Register the Fast Cart setup wizard
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Pages extends Step {

	/**
	 * Constructor
	 */
	public function init() {
		$this->set_id( 'pages' );
		$this->set_name( __( 'Replace Pages', 'wc-fast-cart' ) );
		$this->set_description( __( 'Force customers to use the fast cart by preventing access to the standard WooCommerce cart/checkout', 'wc-fast-cart' ) );
		$this->set_title( __( 'Replace Pages', 'wc-fast-cart' ) );
	}

	/**
	 * Add fields for Pages step
	 *
	 * @return array
	 */
	public function setup_fields() {

		$options = Util::get_settings();

		$fields = [];

		$fields['replace_cart_page']     = [
			'type'        => 'checkbox',
			'title'       => __( 'Replace cart page', 'wc-fast-cart' ),
			'value'       => $options['replace_cart_page'] === true,
			'description' => false,
			'classes'     => [ 'pages--replace-cart-page-toggle', 'barn2-no-bottom-margin' ],
		];
		$fields['replace_checkout_page'] = [
			'type'        => 'checkbox',
			'title'       => __( 'Replace checkout page', 'wc-fast-cart' ),
			'value'       => $options['replace_checkout_page'] === true,
			'description' => false,
			'classes'     => [ 'pages--replace-checkout-page-toggle', 'barn2-no-bottom-margin' ],
		];

		return $fields;
	}

	/**
	 * Add fields for Pages step
	 *
	 * @return array
	 */
	private function get_wc_fields() {

		$fields = [];
		foreach ( $this->setup_fields() as $key => $options ) {
			$fields[] = Util::OPTION_NAME . "[$key]";
		}

		return WizardUtils::pluck_wc_settings(
			Util::get_settings_list( $this->get_plugin(), 'fast-cart' ),
			$fields
		);
	}

	/**
	 * Save settings
	 *
	 * @param array $values array of values from the wizard input
	 * @return \WP_Rest_Response
	 */
	public function submit( array $values ) {

		$fields = $this->get_wc_fields();
		$values = Util::prepare_wizard_fields_for_wc( $values );

		$options = get_option( 'wc_fast_cart_settings' );
		$values  = $values + $options;

		update_option( Util::OPTION_NAME, $values, 'yes' );

		return Api::send_success_response();
	}
}
