<?php

namespace Barn2\Plugin\WC_Fast_Cart\Admin\Wizard\Steps;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Setup_Wizard\Step;
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
class Cart extends Step {

	private $fields;
	private $field_keys = [
		'cart_show_item_images',
		'cart_show_item_subtotal',
		'cart_show_item_price',
		'cart_show_item_qty',
		'cart_show_item_del',
		'cart_show_coupons',
		'cart_show_cart_subtotal',
		'cart_show_cart_shipping',
		'cart_show_cross_sells',
		'cart_show_keep_shopping',
	];

	/**
	 * Undocumented function
	 */
	public function init() {
		$this->set_id( 'cart' );
		$this->set_name( __( 'Cart', 'wc-fast-cart' ) );
		$this->set_description( __( 'Choose what to include in the popup cart.', 'wc-fast-cart' ) );
		$this->set_title( __( 'Cart', 'wc-fast-cart' ) );
	}

	/**
	 * Add fields for Cart step
	 *
	 * @return array
	 */
	public function setup_fields() {

		$fields = [];

		$fields['my_heading'] = [
			'type'  => 'heading',
			'label' => __( 'Cart features', 'wc-fast-cart' ),
		];

		$settings_list = Util::get_settings_list( $this->get_plugin() );

		$plucked = $this->get_cart_fields(
			$settings_list,
			$this->field_keys,
		);

		$fields = array_merge( $fields, $plucked );

		$this->fields = $fields;

		return $fields;
	}

	/**
	 * Get list of setup-wizard compatible checkbox fields.
	 *
	 * @param array $all_settings WooCommerce settings array
	 * @param array $checkbox_indexes List of checkbox feature ids needed.
	 * @return array
	 */
	public function get_cart_fields( $all_settings, $checkbox_indexes ) {

		$fields = [];

		$values = Util::get_settings();

		foreach ( $all_settings as $setting ) {
			if ( ! isset( $setting['id'] ) ) {
				continue;
			}
			if ( ! preg_match( '/^wc_fast_cart_settings\[([a-z-_]+)\]$/', $setting['id'], $id ) ) {
				continue;
			}
			if ( ! in_array( $id[1], $checkbox_indexes, true ) ) {
				continue;
			}
			$fields[ $id[1] ] = [
				'type'        => 'checkbox',
				'title'       => $setting['desc'],
				'value'       => $values[ $id[1] ] ?? false,
				'border'      => false,
				'description' => false,
				'classes'     => [ 'barn2-no-bottom-margin' ],
			];
		}

		return $fields;
	}

	/**
	 * Save settings.
	 *
	 * @param array $values array of values from the wizard input
	 * @return \WP_Rest_Response
	 */
	public function submit( array $values ) {

		$options = get_option( Util::OPTION_NAME );

		foreach ( $values as $option_key => $option_value ) {
			if ( in_array( $option_key, $this->field_keys ) ) {
				if ( filter_var( $option_value, FILTER_VALIDATE_BOOLEAN ) ) {
					$options[ $option_key ] = 'yes';
				} else {
					$options[ $option_key ] = 'no';
				}
			}
		}

		update_option( Util::OPTION_NAME, $options, 'yes' );

		return Api::send_success_response();
	}
}
