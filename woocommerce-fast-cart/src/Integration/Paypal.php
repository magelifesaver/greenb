<?php
namespace Barn2\Plugin\WC_Fast_Cart\Integration;

use Barn2\Plugin\WC_Fast_Cart\Util;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;

/**
 * Integrates with Paypal Express Plugin
 *
 * Provided for legacy integration, this plugin is no longer supported by the WooCOmmerce team.
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Paypal implements Premium_Service, Conditional, Registerable {

	private $settings;
	private $theme;

	private $was_minicart_rendered = false;

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
		return class_exists( 'WooCommerce\PayPalCommerce\Button\Assets\SmartButton' );
	}

	/**
	 * Register action and filter for PayPal integration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wfc_after_proceed_to_checkout_buttons', [ self::class, 'echo_paypal_button_cart_container' ] );
		add_action( 'wfc_register_scripts', [ self::class, 'maybe_enqueue_assets' ], 10, 2 );
		add_filter( 'woocommerce_widget_cart_is_hidden', [ $this, 'minicart_was_rendered' ] );
		add_action( 'wp_footer', [ $this, 'maybe_add_paypal_button_container' ], 9999 );
	}

	/**
	 * This hook does nothing to the filter it is attached to, but is used to determine if the minicart widget ever attempted to render.
	 *
	 * @param [type] $is_visible
	 * @return bool
	 */
	public function minicart_was_rendered( $is_visible ) {
		$this->was_minicart_rendered = true;
		return $is_visible;
	}

	/**
	 * Attach a container for the paypal buttons to inject into on Fast Cart
	 *
	 * @return void
	 */
	public static function echo_paypal_button_cart_container() {

		echo '<div class="wfc-checkout-buttons__paypal"></div>';
	}

	/**
	 * Used as a fallback if the theme does not have a minicart but minicart is enabled.
	 *
	 * @return void
	 */
	public function maybe_add_paypal_button_container() {

		if ( $this->was_minicart_rendered ) {
			return;
		}

		echo '<div id="wc-fast-cart-paypal-checkout-container" style="display:none"><p id="ppc-button-minicart" class="woocommerce-mini-cart__buttons buttons"></p></div>';
	}


	/**
	 * Enqueue the PayPal front-end integration scripts
	 *
	 * @param [type] $version
	 * @param [type] $debug_level
	 * @return void
	 */
	public static function maybe_enqueue_assets( $version, $debug_level ) {

		if ( is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			'wfc-paypal-integration',
			Util::get_asset_url( 'js/wfc-paypal.js' ),
			[ 'jquery' ],
			filemtime( Util::get_asset_path( 'js/wfc-paypal.js' ) ),
			true
		);
	}
}
