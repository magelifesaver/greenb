<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Blocks\Cart_Checkout_Integration;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Blocks as Blocks_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Contracts\WordPress_Plugin;

/**
 * Blocks handler.
 *
 * @since 5.1.0
 *
 * @see Blocks_Handler
 */
final class Blocks extends Blocks_Handler {

	/** @var Cart_Checkout_Integration|null */
	private ?Cart_Checkout_Integration $cart_checkout_integration = null;

	/**
	 * Blocks handler constructor.
	 *
	 * @since 5.1.0
	 *
	 * @param WordPress_Plugin $plugin
	 */
	protected function __construct( WordPress_Plugin $plugin ) {

		parent::__construct( $plugin );

		self::add_action( 'init', [ $this, 'register_block_integration_scripts' ] );
		self::add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_block_integration_styles' ] );
		self::add_action( 'woocommerce_blocks_loaded', [ $this, 'register_woocommerce_block_integrations' ] );
	}

	/**
	 * Returns the instance of the Cart & Checkout blocks integration.
	 *
	 * @since 5.1.0
	 *
	 * @return Cart_Checkout_Integration
	 */
	private function get_cart_checkout_integration() : Cart_Checkout_Integration {

		if ( ! $this->cart_checkout_integration ) {
			$this->cart_checkout_integration = new Cart_Checkout_Integration();
		}

		return $this->cart_checkout_integration;
	}

	/**
	 * Registers the block integration scripts.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	protected function register_block_integration_scripts() : void {

		wp_register_script(
			self::plugin()->handle( 'store-credit-cart-checkout-blocks' ),
			self::plugin()->assets_url( 'js/blocks/cart-checkout-blocks-integration.js' ),
			[],
			self::plugin()->version(),
			[ 'in_footer' => true ]
		);
	}

	/**
	 * Enqueues the block integration styles.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	protected function enqueue_block_integration_styles() : void {

		if ( ! is_singular() ) {
			return;
		}

		$is_cart     = is_cart();
		$is_checkout = is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page();

		if ( ! $is_cart && ! $is_checkout ) {
			return;
		}

		if ( $is_cart && ! has_block( 'woocommerce/cart' ) ) {
			return;
		}

		if ( $is_checkout && ! has_block( 'woocommerce/checkout' ) ) {
			return;
		}

		wp_enqueue_style(
			self::plugin()->handle( 'store-credit-cart-checkout-blocks' ),
			self::plugin()->assets_url( 'css/blocks/cart-checkout-blocks-integration.css' ),
			[],
			self::plugin()->version()
		);
	}

	/**
	 * Registers the WooCommerce Blocks integrations.
	 *
	 * @since 5.1.0
	 *
	 * @return void
	 */
	protected function register_woocommerce_block_integrations() : void {

		$block_integration_instance = $this->get_cart_checkout_integration();

		add_action( 'woocommerce_blocks_cart_block_registration', function( $integration_registry ) use ( $block_integration_instance ) {
			$integration_registry->register( $block_integration_instance );
		} );

		add_action( 'woocommerce_blocks_checkout_block_registration', function( $integration_registry ) use ( $block_integration_instance ) {
			$integration_registry->register( $block_integration_instance );
		} );

		woocommerce_store_api_register_update_callback(
			[
				'namespace' => 'kestrel/store-credit',
				'callback'  => fn( $request_data ) => $this->apply_store_credit_coupon( $request_data ),
			]
		);
	}

	/**
	 * Handles the block request for applying a store credit coupon.
	 *
	 * @since 5.1.0
	 *
	 * @param array<string, mixed>|mixed $request_data
	 * @return void
	 */
	protected function apply_store_credit_coupon( $request_data = [] ) : void {

		if ( ! is_array( $request_data ) || empty( $request_data['couponCode'] ) ) {
			return;
		}

		WC()->cart->apply_coupon( sanitize_text_field( $request_data['couponCode'] ) );
	}

}
