<?php
namespace Barn2\Plugin\WC_Fast_Cart\Integration;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;

// woo_pp_ec_button_checkout

/**
 * Integrates with Paypal Express Plugin
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Paypal_Legacy implements Premium_Service, Conditional, Registerable {

	private $settings;
	private $theme;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = Util::get_settings();
	}

	/**
	 * Checks if this integration is required
	 */
	public function is_required() {
		return function_exists( 'wc_gateway_ppec' );
	}

	/**
	 * Register action and filter for PayPal integration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_paypal' ], 11 );
		add_filter( 'woocommerce_paypal_express_checkout_payment_button_data', [ $this, 'localize_paypal' ], 11, 2 );
	}

	/**
	 * Disabled some paypal functions that are incompatible outside of the checkout page
	 * hooks into `woocommerce_paypal_express_checkout_payment_button_data`
	 *
	 * @since   v0.1
	 * @param   array   $data   paypal settings provided by ppec plugin
	 * @param   string  $page   context
	 * @return  array
	 */
	public function localize_paypal( $data, $page ) {

		if ( is_checkout() ) {
			return $data;
		}

		$settings = wc_gateway_ppec()->settings;
		$client   = wc_gateway_ppec()->client;

		$data['disallowed_methods'] = [ 'CARD', 'CREDIT', 'PAYLATER' ];
		$data['allowed_methods']    = [];

		$data['page'] = 'cart';

		return $data;
	}

	/**
	 * Enqueues paypal scripts on every page for use by Fast Cart
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function enqueue_paypal() {
		wp_enqueue_script( 'wc-gateway-ppec-smart-payment-buttons' );
	}
}
