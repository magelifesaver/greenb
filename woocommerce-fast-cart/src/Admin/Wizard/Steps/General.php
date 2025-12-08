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
class General extends Step {

	/**
	 * Undocumented function
	 */
	public function init() {
		$this->set_id( 'general' );
		$this->set_name( __( 'Display', 'wc-fast-cart' ) );
		$this->set_description( __( 'Control the overall layout and behavior of the fast cart', 'wc-fast-cart' ) );
		$this->set_title( __( 'Display', 'wc-fast-cart' ) );
	}

	/**
	 * Undocumented function
	 *
	 * @return array
	 */
	private function get_wc_fields() {
		return WizardUtils::pluck_wc_settings(
			Util::get_settings_list( $this->get_plugin(), 'fast-cart' ),
			[
				Util::OPTION_NAME . '[fast_cart_mode]',
				Util::OPTION_NAME . '[enable_auto_open]',
				Util::OPTION_NAME . '[enable_cart_button]',
				Util::OPTION_NAME . '[enable_fast_cart]',
				Util::OPTION_NAME . '[enable_direct_checkout]',
				Util::OPTION_NAME . '[enable_fast_checkout]',
			]
		);
	}

	/**
	 * Add fields for Display step
	 *
	 * @return array
	 */
	public function setup_fields() {

		$settings = $this->get_wc_fields();
		$options  = Util::get_settings();

		$fields = [];

		$layout_modes             = [
			'side'  => esc_html__( 'Side cart', 'wc-fast-cart' ),
			'modal' => esc_html__( 'Centered popup', 'wc-fast-cart' ),
		];
		$fields['fast_cart_mode'] = [
			'type'    => 'radio',
			'label'   => $settings[ Util::OPTION_NAME . '[fast_cart_mode]' ]['label'],
			'options' => WizardUtils::parse_array_for_radio( $layout_modes ),
			'value'   => $options['fast_cart_mode'],
			'classes' => [ 'general--btn-position-options' ],
		];

		$fields['enable_auto_open'] = [
			'type'    => 'checkbox',
			'title'   => $settings[ Util::OPTION_NAME . '[enable_auto_open]' ]['label'],
			'label'   => __( 'Open after adding a product to the cart', 'wc-fast-cart' ),
			'value'   => $options['enable_auto_open'],
			'classes' => [ 'general--auto-open-toggle' ],
		];

		$cart_button_options          = [
			'top'    => esc_html__( 'Top', 'wc-fast-cart' ),
			'center' => esc_html__( 'Center', 'wc-fast-cart' ),
			'bottom' => esc_html__( 'Bottom', 'wc-fast-cart' ),
			'none'   => esc_html__( 'Hidden', 'wc-fast-cart' ),
		];
		$cart_button_selected         = $options['enable_cart_button'] === false ? 'none' : $options['cart_icon_position'];
		$fields['cart_icon_position'] = [
			'type'      => 'select',
			'label'     => __( 'Floating cart icon', 'wc-fast-cart' ),
			'alt_label' => __( 'Floating cart icon', 'wc-fast-cart' ),
			'options'   => WizardUtils::parse_array_for_dropdown( $cart_button_options ),
			'value'     => $cart_button_selected,
			'classes'   => [ 'general--btn-position-options' ],
		];
		$button_style_options = [
			'icon'      => __( 'Icon only', 'wc-fast-cart' ),
			'text'      => __( 'Text only', 'wc-fast-cart' ),
			'text_icon' => __( 'Icon and text', 'wc-fast-cart' ),
		];
		$button_style_selected = $options['cart_button_style'] === false ? 'icon' : $options['cart_button_style'];
		$fields['cart_button_style'] = [
			'type'      => 'select',
			'label'     => __( 'Button style', 'wc-fast-cart' ),
			'options'   => WizardUtils::parse_array_for_dropdown( $button_style_options ),
			'value'     => $button_style_selected,
		];
		$fields['cart_button_text'] = [
			'type'      => 'text',
			'label'     => __( 'Button text', 'wc-fast-cart' ),
			'value'     => $options['cart_button_text'],
			'conditions' => [
				'cart_button_style' => [
					'op'    => 'neq',
					'value' => 'icon',
				]
			],
		];
		return $fields;
	}

	/**
	 * Save settings.
	 *
	 * @param array $values array of values from the wizard input
	 * @return \WP_Rest_Response
	 */
	public function submit( array $values ) {

		$fields = $this->get_wc_fields();
		$values = Util::prepare_wizard_fields_for_wc( $values );

		$options = get_option( 'wc_fast_cart_settings' );
		if ( ! empty( $options ) && is_array( $options ) ) {
			$values = $values + $options;
		}

		if ( $values['cart_icon_position'] === 'none' ) {
			$values['enable_cart_button'] = 'no';
		} else {
			$values['enable_cart_button'] = 'yes';
		}

		update_option( Util::OPTION_NAME, $values, true );

		return Api::send_success_response();
	}
}
