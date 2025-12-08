<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Conditional;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Util as Lib_Util;

use WC_Shortcode_Cart;
use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;

/**
 * Loads the various scripts and styles needed for the cart modal
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Frontend_Templates implements Premium_Service, Registerable, Conditional {

	private $version;
	private $settings;

	/**
	 * Constructor
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
	 * Adds Fast Cart templates to WordPress and WooCommerce actions and filters
	 *
	 * @return void
	 */
	public function register() {

		add_filter( 'template_include', [ $this, 'maybe_get_checkout_template' ], 99 );
		add_filter( 'template_include', [ $this, 'maybe_get_cart_template' ], 99 );

		if ( filter_var( $this->settings['enable_cart_button'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			add_action( 'wp_footer', [ $this, 'show_fast_cart_button' ], 999 );
		}

		add_action( 'woocommerce_before_cart', [ $this, 'remove_cart_functions' ], 99 );
		add_filter( 'wfc_wrapper_classes', [ $this, 'cart_classes' ], 99 );

		add_action( 'wfc_before_cart', [ $this, 'prepare_fast_cart' ] );
		add_action( 'wfc_before_cart', [ $this, 'add_cart_open_wrapper' ], 9 );
		add_action( 'wfc_before_cart', 'woocommerce_output_all_notices', 10 );

		add_action( 'wfc_after_cart', [ $this, 'add_cart_close_wrapper' ], 99 );

		add_filter( 'woocommerce_get_cart_url', [ $this, 'add_wfc_flag_to_redirect' ] );

		add_filter( 'wc_get_template', [ $this, 'maybe_change_totals_template' ], 10, 2 );

		add_action( 'wfc_checkout_before_content', [ get_class( $this ), 'open_checkout_wrapper' ], 10 );
		add_action( 'wfc_checkout_after_content', [ get_class( $this ), 'close_checkout_wrapper' ], 10 );
		add_action( 'wfc_checkout_the_content', 'the_content', 10 );

		add_action( 'wfc_after_shop_loop_item', [ self::class, 'open_cart_actions_wrapper' ], 1 );
		add_action( 'wfc_after_shop_loop_item', [ self::class, 'close_cart_actions_wrapper' ], 99999 );

		add_action( 'woocommerce_add_to_cart', [ $this, 'maybe_trigger_the_cart_open' ] );

		/*
		if ( filter_var( $this->settings['send_cors_headers'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			add_action( 'set_logged_in_cookie', [ $this, 'set_logged_in_cookie' ] );
			add_action( 'send_headers', [ $this, 'maybe_send_cors_headers' ] );
		}

		if ( did_action('send_headers') ) {
			header('X-WP-Headers: sent');
		}*/

		add_action( 'init', [ $this, 'add_fast_cart_shortcode' ] );
	}

	/**
	 * Overrides default WC checkout template when 'wfc-checkout' is present GET request parameters hooks into `template_include` filter
	 *
	 * @since   v0.1
	 * @param   string   $template   original template from WC
	 * @return  string
	 */
	public function maybe_get_checkout_template( $template ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! is_checkout() || empty( $_REQUEST['wfc-checkout'] ) ) {
			return $template;
		}

		$template = Util::get_template_path( 'fast-checkout/checkout.php' );
		show_admin_bar( false );

		return $template;
	}

	/**
	 * Overrides default WC cart template when 'wfc-cart' is present GET request parameters
	 * hooks into `template_include` filter
	 *
	 * @since   v0.1
	 * @param   mixed   $template   original template from WC
	 * @return  mixed
	 */
	public function maybe_get_cart_template( $template ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! is_cart() || ! isset( $_REQUEST['wfc-cart'] ) ) {
			return $template;
		}

		$template = Util::get_template_path( 'fast-cart/cart.php' );

		return $template;
	}

	/**
	 * Outputs fast cart button on page
	 *
	 * @since   v0.1
	 * @return  mixed
	 */
	public function show_fast_cart_button() {

		if ( is_cart() || is_checkout() ) {
			return;
		}

		$args = $this->get_cart_button_args();

		Util::load_template(
			'open-cart-button.php',
			[
				'fill_bg'       => $args['fill_bg'],
				'fill_color'    => $args['fill_color'],
				'count_bg'      => $args['count_bg'],
				'count_color'   => $args['count_color'],
				'hidden'        => $args['hidden'],
				'position'      => $args['position'],
				'border_radius' => $args['border_radius'],
				'button_style'  => $args['button_style'],
				'button_text'   => $args['button_style'] !== 'icon' ? $args['button_text'] : '',
			]
		);
	}

	/**
	 * DEPRECATED. disables various cart functions on Fast Cart according to settings
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function remove_cart_functions() {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_REQUEST['wfc-cart'] ) ) {
			return;
		}

		if ( ! filter_var( $this->settings['cart_show_coupons'], FILTER_VALIDATE_BOOLEAN ) ) {
			add_filter( 'woocommerce_coupons_enabled', '__return_false' );
		}
	}

	/**
	 * Adds classes to Fast Cart wrapper to hide unavailable features of Fast Cart
	 *
	 * @since   v0.1
	 * @param   array   $classes    original wfc wrapper classes
	 * @return  array
	 */
	public function cart_classes( $classes ) {
		$class_settings = [
			'cart_show_item_price'    => [ 'hide-item-price' ],
			'cart_show_item_subtotal' => [ 'hide-item-subtotal' ],
			'cart_show_item_images'   => [ 'hide-item-images' ],
			'cart_show_item_qty'      => [ 'hide-item-qty-picker' ],
			'cart_show_item_del'      => [ 'hide-item-delete' ],
			'cart_show_cart_subtotal' => [ 'hide-cart-subtotal' ],
			'cart_show_coupons'       => [ 'hide-coupon-code' ],
			'cart_show_keep_shopping' => [ 'hide-keep-shopping' ],
			'cart_show_headings'      => [ 'hide-cart-headings' ],
		];
		foreach ( $class_settings as $setting => $class ) {
			if ( ! filter_var( $this->settings[ $setting ], FILTER_VALIDATE_BOOLEAN ) ) {
				foreach ( $class as $class_name ) {
					$classes[] = $class_name;
				}
			}
		}
		return $classes;
	}

	/**
	 * Sets up actions for display of Fast Cart template functions
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function prepare_fast_cart() {

		if ( filter_var( $this->settings['cart_show_cross_sells'], FILTER_VALIDATE_BOOLEAN ) ) {
			add_action( 'wfc_cart_collaterals', [ $this, 'wfc_cross_sells' ], 20 );
		}
		add_action( 'wfc_cart_collaterals', [ $this, 'wfc_cart_totals' ], 10 );

		add_action( 'wfc_cart_is_empty', 'wc_empty_cart_message' );
		add_action( 'wfc_cart_actions', [ $this, 'wfc_coupons' ] );

		add_action( 'woocommerce_proceed_to_checkout', 'wc_get_pay_buttons', 10 );
		add_action( 'woocommerce_proceed_to_checkout', [ $this, 'wfc_checkout_buttons' ], 19 );
		remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );

		add_action( 'wfc_cross_sells_loop_product', [ $this, 'wfc_show_product' ] );

		add_action( 'wfc_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
		add_action( 'wfc_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		add_action( 'wfc_before_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 15 );
		add_action( 'wfc_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
		add_action( 'wfc_after_shop_loop_item_title', [ $this, 'wfc_template_loop_rating' ], 5 );
		add_action( 'wfc_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
		add_action( 'wfc_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
		add_action( 'wfc_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

		$nonce_value = $_POST['woocommerce-shipping-calculator-nonce'] ?? '';
		if ( ! empty( $_POST['calc_shipping'] ) && ( wp_verify_nonce( $nonce_value, 'woocommerce-shipping-calculator' ) || wp_verify_nonce( $nonce_value, 'woocommerce-cart' ) ) ) { // WPCS: input var ok.
			WC_Shortcode_Cart::calculate_shipping();
		}
		WC()->cart->calculate_totals();

		do_action( 'woocommerce_check_cart_items' );
	}

	/**
	 * Cart totals template
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function wfc_cart_totals() {
		Util::load_template(
			'fast-cart/cart-totals.php',
			[
				'hide_shipping' => ! filter_var( $this->settings['cart_show_cart_shipping'], FILTER_VALIDATE_BOOLEAN ),
			]
		);
	}

	/**
	 * Outputs cart checkout buttons
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function wfc_checkout_buttons() {
		Util::load_template( 'fast-cart/proceed-to-checkout-buttons.php' );
	}

	/**
	 * Outputs cross sells carousel
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function wfc_cross_sells() {

		$cross_sells = array_filter( array_map( 'wc_get_product', WC()->cart->get_cross_sells() ), 'wc_products_array_filter_visible' );

		Util::load_template( 'fast-cart/cross-sells.php', [ 'cross_sells' => $cross_sells ] );
	}

	/**
	 * Outputs coupon code html
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function wfc_coupons() {

		if ( wc_coupons_enabled() && filter_var( $this->settings['cart_show_coupons'], FILTER_VALIDATE_BOOLEAN ) ) {

			?>
			<div class="wfc-coupon">
				<h2><label for="coupon_code"><?php esc_html_e( 'Coupon Code', 'wc-fast-cart' ); ?></label></h2>
				<div class="wfc-coupon__inner-contents">
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'wc-fast-cart' ); ?>" />
					<button type="submit" class="wfc-button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'wc-fast-cart' ); ?>"><?php esc_attr_e( 'Apply coupon', 'wc-fast-cart' ); ?></button>
				</div>
				<?php

				/**
				 * Replacement for woocommerce_cart_coupon
				 *
				 * Fast Cart tries to implement standard WooCommerce templates wherever possible, but in some
				 * cases this creates unintended conflicts with plugins that try to integrate with WooCommerce.
				 * This hook is in the place of woocommerce_cart_coupon, and implements all of its standard
				 * functions.
				 *
				 * @since 1.0.0
				 */
				do_action( 'wfc_cart_coupon' );

				?>
			</div>
			<?php
		}
	}

	/**
	 * Outputs product star ratings
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public function wfc_template_loop_rating() {
		Util::load_template( 'fast-cart/cross-sells-product-rating.php' );
	}

	/**
	 * Outputs product within cross sells carousel
	 *
	 * @since   v0.1
	 * @param   \WP_Post $post WP_Post or WC_Product object
	 * @return  void
	 */
	public function wfc_show_product( $post ) {
		Util::load_template( 'fast-cart/cross-sells-product.php', [ 'product' => $post ] );
	}

	/**
	 * Adds wfc-cart to GET request parameters of woocommerce cart redirection url when parameter is present in request headers
	 *
	 * @since   v0.1
	 * @param   string $url url to redirect after cart action
	 * @return  string
	 */
	public function add_wfc_flag_to_redirect( $url ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['wfc-cart'] ) ) {
			$url = add_query_arg( 'wfc-cart', 'true', $url );
		}
		return $url;
	}

	/**
	 * Outputs opening tag for cart template controlled by Fast Cart.
	 *
	 * @return void
	 */
	public function add_cart_open_wrapper() {

		echo '<div id="wfc-cart-page">';
	}

	/**
	 * Closing tag for cart template.
	 *
	 * @return void
	 */
	public function add_cart_close_wrapper() {

		echo '</div>';
	}

	/**
	 * DEPRECATED replaces cart totals template with fast cart version
	 *
	 * @since   v0.1
	 * @param   string $template original template path
	 * @param   string $template_name name of template
	 * @return  string new template path
	 */
	public function maybe_change_totals_template( $template, $template_name ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( $template_name === 'cart/cart-totals.php' && isset( $_REQUEST['wfc-cart'] ) ) {
			$template = Util::get_template_path( 'fast-cart/cart-totals.php' );
		}
		return $template;
	}

	/**
	 * Adds div wrapper to wfc checkout template
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public static function open_checkout_wrapper() {

		echo '<div class="wfc-checkout__inner-contents">';
	}

	/**
	 * Closes div wrapper on wfc checkout template
	 *
	 * @since   v0.1
	 * @return  void
	 */
	public static function close_checkout_wrapper() {

		echo '</div>';
	}

	/**
	 * Outputs opening tag for cart actions DOM
	 *
	 * @return void
	 */
	public static function open_cart_actions_wrapper() {

		echo '<div class="wfc-cross-sells__item-actions">';
	}

	/**
	 * Outputs closing tag for cart actions DOM
	 *
	 * @return void
	 */
	public static function close_cart_actions_wrapper() {

		echo '</div>';
	}

	/**
	 * Hooks a cart open event when a product is added by URL
	 *
	 * @return void
	 */
	public function maybe_trigger_the_cart_open() {
		wp_add_inline_script(
			'jquery',
			"
			document.addEventListener( 'DOMContentLoaded', function () {
				setTimeout( function(){
					jQuery( document.body ).trigger( 'wc-fast-cart|item-added', document ); 
				}, 100 );
			}, false );
		",
			'after'
		);
	}

	/**
	 * When the login cookies are set, they are not available until the next page reload. (Modified from WooCommerce Store API)
	 *
	 * @param string $logged_in_cookie The value for the logged in cookie.
	 */
	public function set_logged_in_cookie( $logged_in_cookie ) {
		if ( ! defined( 'LOGGED_IN_COOKIE' ) ) {
			return;
		}
		$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
	}

	/**
	 * Add CORS headers to a response object, not needed for most websites.
	 *
	 * @return void
	 */
	public function maybe_send_cors_headers() {

		header( 'X-WFC-CORS-Enabled: true' );

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( $_REQUEST['wfc-cart'] ) && ! isset( $_REQUEST['add-to-cart'] ) ) {
			return;
		}

		header( 'X-Fast-Cart-Request: true' );

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			// let WC handle the headers
			header( 'X-Use-WC-CORS: true' );
			return;
		}

		$current_url = parse_url( $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? $_SERVER['HTTP_HOST'] ?? '' );
		if ( ! $current_url ) {
			return;
		}
		$origin_scheme = ( $current_url['scheme'] ?? 'https' ) . '://' . ( $current_url['host'] ?? $current_url['path'] );

		header( 'X-HTTP-Origin: ' . $origin_scheme );

		if ( ! is_allowed_http_origin( $origin_scheme ) ) {
			return;
		}

		// Send standard CORS headers.
		header( 'Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE' );
		header( 'Access-Control-Allow-Credentials: true' );
		header( 'Vary: Origin' );
		header( 'Access-Control-Allow-Origin: sc_url_raw( $origin_scheme' );

		return;
	}

	public function add_fast_cart_shortcode() {
		add_shortcode( 'fast_cart', [ $this, 'fast_cart_shortcode_output' ] );
	}

	/**
	 * Outputs the [fast_cart] shortcode button
	 *
	 * @return string fast cart button
	 */
	public function fast_cart_shortcode_output( $atts ) {
		if ( is_cart() || is_checkout() ) {
			return;
		}
		// Start output buffering
		ob_start();

		$args             = $this->get_cart_button_args();
		$args['hidden']   = false;
		$args['position'] = 'content';
		if ( isset( $atts['text'] ) ) {
			$args['button_text'] = $atts['text'];
		}
		if ( isset( $atts['layout'] ) ) {
			$args['button_style'] = $atts['layout'];
		}

		Util::load_template(
			'open-cart-button.php',
			[
				'fill_bg'       => $args['fill_bg'],
				'fill_color'    => $args['fill_color'],
				'count_bg'      => $args['count_bg'],
				'count_color'   => $args['count_color'],
				'hidden'        => $args['hidden'],
				'position'      => $args['position'],
				'border_radius' => $args['border_radius'],
				'button_style'  => $args['button_style'],
				'button_text'   => $args['button_style'] !== 'icon' ? $args['button_text'] : '',
			]
		);

		return ob_get_clean();
	}

	/**
	 * Returns the args for showing the fast cart button
	 *
	 * @return array a list of parameters for fast cart button
	 */
	public function get_cart_button_args() {
		$args['fill_bg']       = $this->settings['cart_icon_bg'] ?: '#000000';
		$args['fill_color']    = $this->settings['cart_icon_fill'] ?: Util::color_yiq( $fill_bg );
		$args['count_bg']      = $this->settings['cart_count_bg'] ?: '#25b354';
		$args['count_color']   = $this->settings['cart_count_color'] ?: Util::color_yiq( $count_bg );
		$args['hidden']        = WC()->cart->cart_contents_count ? 'false' : 'true';
		$args['position']      = $this->settings['cart_icon_position'];
		$args['border_radius'] = $this->settings['cart_button_radius'];
		$args['button_style']  = $this->settings['cart_button_style'];
		$args['button_text']   = $this->settings['cart_button_text'];
		if ( empty( $args['position'] ) || ! in_array( $args['position'], [ 'top', 'center' ], true ) ) {
			$args['position'] = 'bottom';
		}

		return apply_filters( 'wfc_cart_button_args', $args );
	}
}
