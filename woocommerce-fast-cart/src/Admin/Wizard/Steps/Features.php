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
class Features extends Step {

	/**
	 * Undocumented function
	 */
	public function init() {
		$this->set_id( 'features' );
		$this->set_name( __( 'Features', 'wc-fast-cart' ) );
		$this->set_description( __( 'Select your features', 'wc-fast-cart' ) );
		$this->set_title( __( 'Features', 'wc-fast-cart' ) );
	}

	/**
	 * Add fields for Features step
	 *
	 * @return array
	 */
	public function setup_fields() {

		$options = Util::get_settings();

		$fields = [];

		$display_selected = 'both';
		if ( ! filter_var( $options['enable_fast_checkout'], FILTER_VALIDATE_BOOLEAN ) ) {
			$display_selected = 'cart';
		} elseif ( ! filter_var( $options['enable_fast_cart'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			$display_selected = 'checkout';
		}

		$display_options             = [
			'cart'     => esc_html__( 'Cart', 'wc-fast-cart' ),
			'checkout' => esc_html__( 'Checkout', 'wc-fast-cart' ),
			'both'     => esc_html__( 'Both', 'wc-fast-cart' ),
		];
		$fields['fast_cart_display'] = [
			'type'    => 'radio',
			'label'   => __( 'Which stages of the ordering process will be available in the Fast Cart?', 'wc-fast-cart' ),
			'options' => WizardUtils::parse_array_for_radio( $display_options ),
			'value'   => $display_selected,
			'classes' => [ 'features--fast-cart-mode-radio' ],
		];

		return $fields;
	}

	/**
	 * Get fields for Cart step
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

		switch ( $values['fast_cart_display'] ) {
			case 'cart':
				$values['enable_fast_cart']       = 'yes';
				$values['enable_fast_checkout']   = 'no';
				$values['enable_direct_checkout'] = 'no';
				break;
			case 'checkout':
				$values['enable_fast_cart']       = 'no';
				$values['enable_fast_checkout']   = 'yes';
				$values['enable_direct_checkout'] = 'yes';
				break;
			case 'both':
				$values['enable_fast_cart']       = 'yes';
				$values['enable_fast_checkout']   = 'yes';
				$values['enable_direct_checkout'] = 'no';
				break;
		}
		unset( $values['fast_cart_display'] );

		$options = get_option( 'wc_fast_cart_settings' );
		$values  = $values + $options;

		update_option( Util::OPTION_NAME, $values, 'yes' );

		return Api::send_success_response();
	}
}
