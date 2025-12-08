<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;

/**
 * Loads the various scripts and styles needed for the cart modal
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Frontend_Scripts implements Registerable, Conditional, Premium_Service {

	const SCRIPT_HANDLE = 'wc-fast-cart';

	private $version;
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param string $version Plugin version
	 */
	public function __construct( $version ) {
		$this->version  = $version;
		$this->settings = Util::get_settings();
	}

	/**
	 * Tests to see if templates need to be loaded at all
	 *
	 * @return boolean
	 */
	public function is_required() {
		return Lib_Util::is_front_end();
	}

	/**
	 * Hooks actions and filters to enqueue front-end assets.
	 *
	 * @return void
	 */
	public function register() {

		add_action( 'template_redirect', [ $this, 'register_head' ], 99 );
	}

	/**
	 * Registers actions and filters to load front-end assets
	 *
	 * @return void
	 */
	public function register_head() {

		if ( ! apply_filters( 'wfc_should_load_front_end_assets', true ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ], 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ], 100 );

		add_filter( 'wfc_script_params', [ $this, 'default_strings' ], 9 );

		add_action( 'wp_head', [ $this, 'css_vars' ] );
	}

	/**
	 * Registers front-end assets
	 *
	 * @return void
	 */
	public function register_scripts() {

		$debug_level = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 2 : ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 1 : 0 );

		try {
			$dependencies = file_get_contents( Util::get_asset_path( 'js/wp-dependencies.json' ) );
			$dependencies = json_decode( $dependencies, true );
		} catch( \Exception $e ) {
			$dependencies = [];
		}

		wp_register_style(
			self::SCRIPT_HANDLE,
			Util::get_asset_url( 'css/wc-fast-cart.css' ),
			[],
			$debug_level ? filemtime( Util::get_asset_path( 'css/wc-fast-cart.css' ) ) : $this->version
		);

		wp_register_script(
			self::SCRIPT_HANDLE,
			Util::get_asset_url( 'js/wfc-cart.js' ),
			[ 'jquery' ],
			$debug_level ? filemtime( Util::get_asset_path( 'js/wfc-cart.js' ) ) : $this->version,
			true
		);

		wp_register_script(
			'wfc-checkout',
			Util::get_asset_url( 'js/wfc-checkout.js' ),
			[ 'jquery' ],
			$debug_level ? filemtime( Util::get_asset_path( 'js/wfc-checkout.js' ) ) : $this->version,
			true
		);

		wp_register_script(
			'wfc-blocks-checkout',
			Util::get_asset_url( 'js/wfc-blocks-checkout.js' ),
			[ 'wp-data', 'wc-blocks-checkout' ], //$dependencies['wfc-block-checkout.js']['dependencies'] ?? [],
			$debug_level
				? filemtime( Util::get_asset_path( 'js/wfc-blocks-checkout.js' ) ) 
				: ( $dependencies['wfc-blocks-checkout.js']['version'] ?? $this->version ),
			true
		);

		$wc_cart     = wc_get_page_id( 'cart' );
		$wc_checkout = wc_get_page_id( 'checkout' );

		$script_data = [
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'shippingNonce' => wp_create_nonce( 'update-shipping-method' ),
			'endpoints'     => [
				'cart'     => $wc_cart ? get_permalink( $wc_cart ) : trailingslashit( get_bloginfo( 'url' ) ) . 'cart/',
				'checkout' => $wc_checkout ? get_permalink( $wc_checkout ) : trailingslashit( get_bloginfo( 'url' ) ) . 'checkout/',
				'shipping' => add_query_arg( 'wfc-cart', 'true', \WC_AJAX::get_endpoint( 'update_shipping_method' ) ),
			],
			'options'       => [
				'displayMode'     => $this->settings['fast_cart_mode'],
				'floatingIcon'    => filter_var( $this->settings['enable_cart_button'], FILTER_VALIDATE_BOOLEAN )
					? $this->settings['cart_icon_position']
					: false,
				'replaceCart'     => filter_var( $this->settings['replace_cart_page'], FILTER_VALIDATE_BOOLEAN )
				                  && ! filter_var( $this->settings['enable_direct_checkout'], FILTER_VALIDATE_BOOLEAN ),
				'replaceCheckout' => filter_var( $this->settings['enable_fast_checkout'], FILTER_VALIDATE_BOOLEAN )
				                  && filter_var( $this->settings['replace_checkout_page'], FILTER_VALIDATE_BOOLEAN ),
				'fastCheckout'    => filter_var( $this->settings['enable_fast_checkout'], FILTER_VALIDATE_BOOLEAN ),
				'directCheckout'  => filter_var( $this->settings['enable_fast_checkout'], FILTER_VALIDATE_BOOLEAN )
				                  && filter_var( $this->settings['enable_direct_checkout'], FILTER_VALIDATE_BOOLEAN ),
				'autoOpen'        => filter_var( $this->settings['enable_auto_open'] ?? false, FILTER_VALIDATE_BOOLEAN ),
				'redirectReceipt' => false,
			],
			'autocomplete'  => [
				'fields'       => [ 'address_components' ],
				'strictBounds' => false,
			],
			'classes'       => [
				/**
				 * Classes added to the Fast Cart DOM wrapper
				 *
				 * By default no additional classes are added, but `wc-fast-cart` will always be added by the front-end JavaScript.
				 *
				 * @since 1.0.0
				 *
				 * @param type  $var Enqueue scripts.
				 */
				'cart'   => apply_filters( 'wfc_wrapper_classes', [] ),

				/**
				 * Classes to add to the default checkout button.
				 *
				 * Fast Cart generates a checkout button in the background to determine a color scheme for UI elements in the cart. It looks at background color, text color, and border size. After generation, various buttons, fields, and links will inherit styles determined from a button with these classes assigned.
				 *
				 * @since 1.0.0
				 *
				 * @param string $var A space-separated list of classes to apply to the button template.
				 */
				'button' => apply_filters( 'wfc_default_button_classes', 'button checkout-button alt wc-forward' ),
			],
			'selectors'     => [],
			'strings'       => [],
			'debug'         => $debug_level,
		];

		if ( filter_var( $this->settings['enable_autocomplete'], FILTER_VALIDATE_BOOLEAN ) && ! empty( $this->settings['maps_api'] ) ) {
			$checkout_data['autocomplete_api'] = ( empty( $this->settings['maps_api_status'] ) || $this->settings['maps_api_status'] === 'success' ) ? $this->settings['maps_api'] : null;
		} else {
			$checkout_data['autocomplete_api'] = null;
		}

		wp_add_inline_script(
			self::SCRIPT_HANDLE,
			'var wc_fast_cart_params = ' . wp_json_encode(
				/**
				 * Properties assigned to a global-scope variable containing Fast Cart settings.
				 *
				 * Use this to change Fast Cart behavior before it is provided to the Fast Cart front-end scripts.
				 *
				 * @param array $script_data Array of Fast Cart parameters.
				 */
				apply_filters( 'wfc_script_params', $script_data )
			),
			'before'
		);

		wp_add_inline_script(
			'wfc-checkout',
			'var wc_fast_cart_checkout_params = ' . wp_json_encode(
				/**
				 * Properties assigned to a global-scope variable containing Fast Cart checkout page settings.
				 *
				 * Use this to change Fast Cart behavior before it is provided to the Fast Cart checkout scripts.
				 *
				 * @param array $script_data Array of Fast Cart parameters.
				 */
				apply_filters( 'wfc_checkout_script_params', $checkout_data )
			),
			'before'
		);

		/**
		 * Fired after all Fast Cart global scripts have been registered.
		 *
		 * Executed within the wp_enqueue_scripts action.
		 *
		 * @param $var Plugin version
		 * @param $var 1 if WP_DEBUG is on, 2 if SCRIPT_DEBUG is on
		 * @since 1.0.0
		 */
		do_action( 'wfc_register_scripts', $this->version, $debug_level );

		add_filter( 'body_class', [ $this, 'add_body_classes' ] );

	}

	/**
	 * Conditionally loads cart and checkout front-end assets.
	 *
	 * @return void
	 */
	public function load_scripts() {

		if ( $this->should_load_cart_scripts() ) {
			$this->enqueue_cart_styles();
			$this->enqueue_cart_scripts();

			/**
			 * Fired after all Fast Cart cart scripts have been enqueued.
			 *
			 * Executed within the wp_enqueue_scripts action. Not executed if cart-related scripts have not been loaded (for instance, on the default WC cart page, ironically.)
			 *
			 * @since 1.0.0
			 */
			do_action( 'wfc_load_cart_scripts' );
		}
		if ( $this->should_load_checkout_scripts() ) {
			$this->enqueue_checkout_styles();
			$this->enqueue_checkout_scripts();

			/**
			 * Fired after all Fast Cart checkout scripts have been enqueued.
			 *
			 * Executed within the wp_enqueue_scripts action. Not executed if checkout-related scripts have not been loaded, usually this will only be fired from within the Fast Cart checkout iframe.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wfc_load_checkout_scripts' );
		}

		/**
		 * Fired after all Fast Cart page-specific scripts have been enqueued.
		 *
		 * Executed within the wp_enqueue_scripts action.
		 *
		 * @since 1.0.0
		 */
		do_action( 'wfc_load_scripts' );
	}

	/**
	 * Enqueue cart stylesheet
	 *
	 * @return void
	 */
	private function enqueue_cart_styles() {
		wp_enqueue_style( self::SCRIPT_HANDLE );
	}

	/**
	 * Enqueue cart JavaScript
	 *
	 * @return void
	 */
	private function enqueue_cart_scripts() {
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Enqueue checkout stylesheet
	 *
	 * @return void
	 */
	private function enqueue_checkout_styles() {
		wp_enqueue_style( self::SCRIPT_HANDLE );
	}

	/**
	 * Enqueue checkout JavaScript
	 *
	 * @return void
	 */
	private function enqueue_checkout_scripts() {
		wp_enqueue_script( 'wfc-checkout' );

		if ( \WC_Blocks_Utils::has_block_in_page( wc_get_page_id('checkout'), 'woocommerce/checkout' ) ) {
			wp_enqueue_script( 'wfc-blocks-checkout' );
		}

	}
	/**
	 * DEPRECATED. determines if default cart scripts should be disabled.
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   boolean $value  existing status of cart scripts
	 * @return  boolean new status
	 */
	public function maybe_disable_default_add_to_cart( $value ) {
		return $value;
	}

	/**
	 * Determines if WFC cart scripts should be loaded on the page
	 *
	 * @since   v0.1
	 * @return  boolean
	 */
	private function should_load_cart_scripts() {
		/**
		 * Determines if front-end cart scripts should be enqueued.
		 *
		 * By default cart-related scripts are loaded on all pages.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $var Enqueue scripts.
		 */
		return apply_filters( 'wfc_enabled_on_page', true );
	}

	/**
	 * Determines if WFC checkout scripts should be loaded on the page
	 *
	 * @since   v0.1
	 * @return  boolean
	 */
	private function should_load_checkout_scripts() {
		/**
		 * Determines if front-end checkout scripts should be enqueued.
		 *
		 * By default checkout-related scripts are only loaded from within the Fast Cart checkout iframe.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $var Enqueue scripts.
		 */
		return apply_filters( 'wfc_enabled_on_checkout', is_checkout() && isset( $_GET['wfc-checkout'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Adds wfc classes to WordPress body_classes function
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   array   $classes    existing body classes
	 * @return  array   new classes
	 */
	public function add_body_classes( $classes ) {
		if ( ! in_array( 'wfc-enabled', $classes, true ) ) {
			$classes[] = 'wfc-enabled';
		}
		if ( $this->should_load_checkout_scripts() ) {
			$classes[] = 'wfc-checkout';
		}
		return $classes;
	}

	/**
	 * Defines in-cart and in-checkout scripts for localization
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   array   $data   wfc cart params localization data
	 * @return  array   existing data with strings added
	 */
	public function default_strings( $data ) {

		$data['strings'] = [
			'cartTitle'          => __( 'My Cart', 'wc-fast-cart' ),
			'checkoutTitle'      => __( 'Checkout', 'wc-fast-cart' ),
			'emptyCart'          => __( 'Your cart is currently empty.', 'wc-fast-cart' ),
			// translators: %s references name of product
			'addedToCartMessage' => __( '%s added to the cart.', 'wc-fast-cart' ),
			'viewCartBtnLabel'   => __( 'View Cart', 'wc-fast-cart' ),
			'genericProductName' => __( 'Item', 'wc-fast-cart' ),
		];

		return $data;
	}

	/**
	 * Adds css vars with WFC styles to page body
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function css_vars() {

		?>
		<style id="wfc-style-variables">
			body {
				--wfc-btn-bg-color: <?php echo sanitize_hex_color( $this->settings['cart_icon_bg'] ); ?>;
				--wfc-btn-color: <?php echo sanitize_hex_color( $this->settings['cart_icon_fill'] ); ?>;
				--wfc-btn-notification-bg: <?php echo sanitize_hex_color( $this->settings['cart_count_bg'] ); ?>;
				--wfc-btn-notification-color: <?php echo sanitize_hex_color( $this->settings['cart_count_color'] ); ?>;
				--wfc-btn-bg-color: <?php echo sanitize_hex_color( $this->settings['checkout_btn_bg'] ); ?>;
				--wfc-btn-border-color: <?php echo sanitize_hex_color( $this->settings['checkout_btn_bg'] ); ?>;
				--wfc-btn-color: <?php echo sanitize_hex_color( $this->settings['checkout_btn_color'] ); ?>;
			}
		</style>
		<?php
	}
}
