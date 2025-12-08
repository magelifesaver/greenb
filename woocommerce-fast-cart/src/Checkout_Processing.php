<?php
namespace Barn2\Plugin\WC_Fast_Cart;

use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Service\Premium_Service;
use Barn2\Plugin\WC_Fast_Cart\Dependencies\Lib\Registerable;

/**
 * Loads the various scripts and styles needed for the cart modal
 *
 * @package   Barn2\wc-fast-cart
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
final class Checkout_Processing implements Premium_Service, Registerable {

	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = Util::get_settings();
	}

	/**
	 * Install checkout template filters and actions.
	 *
	 * @return void
	 */
	public function register() {

		add_filter( 'woocommerce_checkout_no_payment_needed_redirect', [ $this, 'maybe_apply_wfc_key' ], 10, 2 );
		add_filter( 'woocommerce_payment_successful_result', [ $this, 'maybe_apply_wfc_key' ], 10, 2 );
		add_filter( 'woocommerce_get_checkout_order_received_url', [ $this, 'maybe_redirect_order_completed_url' ], 99, 2 );

		add_filter( 'woocommerce_checkout_fields', [ $this, 'maybe_add_wfc_checkout_field' ], 9999 );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_wfc_checkout_field' ] );
		add_action( 'woocommerce_store_api_checkout_update_order_meta', [ $this, 'save_wfc_checkout_field' ] );

		add_filter( 'template_include', [ $this, 'maybe_get_success_template' ], 99 );
		add_filter( 'template_include', [ $this, 'maybe_get_payment_template' ], 99 );

		add_filter( 'woocommerce_payment_successful_result', [ $this, 'maybe_redirect_payment_processing_url' ], 99, 2 );

		add_action( 'woocommerce_login_form_start', [ $this, 'maybe_add_wfc_login_field' ] );
		add_filter( 'woocommerce_login_redirect', [ $this, 'maybe_redirect_login' ] );

		woocommerce_store_api_register_endpoint_data( [
			'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema::IDENTIFIER,
			'namespace'       => 'wc-fast-cart',
			'data_callback'   => [ $this, 'additional_checkout_data' ],
			'schema_callback' => [ $this, 'additional_checkout_schema' ],
			'schema_type'     => ARRAY_A,
		] );
	}

	/**
	 * Adds default data to the checkout api schema for Fast Cart retrieval
	 *
	 * @since  v1.1.20
	 * @return array
	 */
	public function additional_checkout_data() {
		return [
			'_uses_wfc'  => '',
			'_using_wfc' => '',
		];
	}

	/**
	 * Adds default data to the checkout api schema for Fast Cart retrieval
	 *
	 * @since  v1.1.20
	 * @return array
	 */
	public function additional_checkout_schema() {
		return [
			'_using_wfc' => array(
				'description' => __( 'Fast Cart In Progress', 'wc-fast-cart' ),
				'type'        => array( 'hidden', 'null' ),
				'readonly'    => true,
			),
			'_uses_wfc'  => [
				'description' => __( 'Fast Cart Utilized During Checkout', 'wc-fast-cart' ),
				'type'        => array( 'hidden', 'null' ),
				'readonly'    => true,
			],
		];
	}

	/**
	 * Adds field to checkout fields indicating checkout through WFC
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   mixed   $fields checkout form fields
	 * @return  mixed   new fields
	 */
	public function maybe_add_wfc_checkout_field( $fields ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['wfc-checkout'] ) || ! empty( $_POST['_used_wfc'] ) ) {
			$_POST['_used_wfc']  = 'true';
			$fields['billing']['_used_wfc'] = [
				'type'  => 'hidden',
				'value' => 'true',
			];
		}

		return $fields;
	}

	/**
	 * Adds field to checkout login form to properly redirect after login
	 *
	 * @since   v0.1
	 * @access  public
	 */
	public function maybe_add_wfc_login_field() {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['wfc-checkout'] ) ) {
			echo '<input type="hidden" name="_using_wfc" value="true">';
		}
	}

	/**
	 * Take user back to Fast Cart checkout after logging in.
	 *
	 * @param [type] $url Checkout url
	 * @return string New URL
	 */
	public function maybe_redirect_login( $url ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['_using_wfc'] ) ) {
			$url = add_query_arg( 'wfc-checkout', 'true', $url );
		}

		return $url;
	}

	/**
	 * Adds meta value to order if user checked out through fast cart.
	 * Not strictly necessary when using the checkout block API but worth adding anyway.
	 *
	 * @since   v0.1
	 * @param   string  $order_id post id of order or WC_Order object
	 * @return  void
	 */
	public function save_wfc_checkout_field( $order ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_POST['_used_wfc'] ) ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}
			$order->update_meta_data( '_used_wfc', 'true' );
			$order->save();
		}
	}

	/**
	 * Checks to see if user checked out through fast cart and adds parameter to redirect url
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   array   $result redirection parameters array
	 * @param   object  $order  WC_Order object
	 * @return  string  new redirection array
	 */
	public function maybe_apply_wfc_key( $result, $order ) {

		if ( is_string( $result ) && $order->get_meta( '_used_wfc' ) ) {
			$result = add_query_arg( 'wfc-checkout', true, $result );
		}
		if ( is_array( $result ) ) {
			$order = wc_get_order( $order );
			if ( filter_var( $order->get_meta( '_used_wfc' ), FILTER_VALIDATE_BOOLEAN ) ) {
				$order->update_meta_data( '_order_receipt_url', $result['redirect'] );
				$result['redirect'] = add_query_arg( 'wfc-checkout', 'complete', $result['redirect'] );
				$order->save();
			}
		}

		return $result;
	}

	/**
	 * Redirect order confirmation to WFC template
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   string  $template   template path
	 * @return  string  new template
	 */
	public function maybe_get_success_template( $template ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['wfc-checkout'] ) && $_GET['wfc-checkout'] === 'complete' ) {
			$template = Util::get_template_path( 'fast-checkout/checkout-complete.php' );
			add_filter( 'wfc_checkout_script_params', [ $this, 'filter_checkout_complete_params' ] );
			show_admin_bar( false );
		}

		return $template;
	}

	/**
	 * Filters checkout parameters used during the receipt template redirect
	 *
	 * @since   v1.1.20
	 * @access  public
	 * @param array $params The parameters passed to the checkout JS
	 * @return void
	 */
	public function filter_checkout_complete_params( $params ) {

		if ( ! empty( $_REQUEST['key'] ) ) {
			$order_key = wc_clean( wp_unslash( $_REQUEST['key'] ) );
			$order     = wc_get_order( wc_get_order_id_by_order_key( $order_key ) );
			if ( $order && ! is_wp_error( $order ) ) {
				$redirect_to = $order->get_meta( '_order_receipt_url' );
				if ( $redirect_to ) {
					$params['receiptUrl'] = $redirect_to;
				}
			}
		}

		return apply_filters( 'wfc_checkout_complete_script_params', $params, $order );
	}

	/**
	 * Replace payment confirmation template with WFC template
	 *
	 * @since   v0.1
	 * @access  public
	 * @param   string  $template   template path
	 * @return  string  new template
	 */
	public function maybe_get_payment_template( $template ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET['_redirect_to_payment'] ) ) {
			$template = Util::get_template_path( 'fast-checkout/payment-complete.php' );
			show_admin_bar( false );
		}

		return $template;
	}

	/**
	 * Redirect the page to WFC template for later processing to open outside the checkout iframe
	 *
	 * @since   v0.1
	 * @param   array   $result     the redirection details
	 * @param   string  $order_id   post id of the order
	 * @return  array new redirection values
	 */
	public function maybe_redirect_payment_processing_url( $result, $order_id ) {

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( filter_var( $_REQUEST['pay_for_order'] ?? false, FILTER_VALIDATE_BOOLEAN ) ) {
			return $result;
		}

		if ( isset( $result['redirect'] ) && strpos( $result['redirect'], 'paypal.com' ) ) {
			$order = wc_get_order( $order_id );
			$nonce = wp_create_nonce( $result['redirect'] );

			$order->update_meta_data( '_payment_redirect', $result['redirect'] );
			$order->save();

			$wc_checkout  = wc_get_page_id( 'checkout' );
			$checkout_url = $wc_checkout ? get_permalink( $wc_checkout ) : trailingslashit( get_bloginfo( 'url' ) ) . 'checkout/';

			$result['redirect'] = add_query_arg(
				[
					'_redirect_to_payment' => $order_id,
					'_redirect_hash'       => $nonce,
				],
				$checkout_url
			);
		}

		return $result;
	}

	/**
	 * Redirect the order received URL to WFC template
	 *
	 * @since   v1.1.20
	 * @param   string     $url   the original redirect url
	 * @param   \WC_Order  $order post id of the order
	 * @return  string            new redirection url
	 */
	public function maybe_redirect_order_completed_url( $url, $order ) {

		$used_wfc = filter_var( $order->get_meta( '_used_wfc' ), FILTER_VALIDATE_BOOLEAN );
		if ( ! $used_wfc ) {
			$used_wfc = str_contains( $_SERVER['HTTP_REFERER'] ?? '', 'wfc-checkout=true' );
		}
		if ( $used_wfc ) {
			$order->update_meta_data( '_order_receipt_url', $url );
			$order->save();

			$url = add_query_arg( 'wfc-checkout', 'complete', $url );
		}

		return $url;
	}
}
