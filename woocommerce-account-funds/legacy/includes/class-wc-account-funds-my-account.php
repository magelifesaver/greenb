<?php
/**
 * Class to customize the 'My Account' page.
 *
 * @package WC_Account_Funds
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WC_Account_Funds_My_Account.
 */
class WC_Account_Funds_My_Account {

	/**
	 * Top-up deposits.
	 *
	 * @var array
	 */
	private $deposits = null;

	/**
	 * Top-up items in cart.
	 *
	 * @var array
	 */
	private $topup_in_cart = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register the endpoint, even on the admin pages.
		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_query_vars' ] );
		add_filter( 'woocommerce_endpoint_account-funds_title', [ $this, 'change_endpoint_title' ] );

		if ( ! wc_account_funds_is_request( 'frontend' ) ) {
			return;
		}

		// Adds the tab/page into the My Account page.
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_items' ] );
		add_action( 'woocommerce_account_account-funds_endpoint', [ $this, 'endpoint_content' ] );

		// Account Funds tab data.
		add_action( 'woocommerce_account_funds_content', [ $this, 'account_funds_content' ] );
		add_action( 'woocommerce_account_funds_recent_deposit_items_data', [ $this, 'recent_deposit_items_data' ] );

		add_action( 'wp', [ $this, 'topup_handler' ] );
	}

	/**
	 * Adds endpoint into query vars.
	 *
	 * @since 2.2.0
	 *
	 * @param array<string, string>|mixed $vars
	 * @return array<string, string>|mixed
	 */
	public function add_query_vars( $vars ) {

		if ( ! is_array( $vars ) ) {
			return $vars;
		}

		$vars['account-funds'] = 'account-funds';

		return $vars;
	}

	/**
	 * Changes the page title on account funds page.
	 *
	 * @since 2.0.12
	 *
	 * @return string
	 */
	public function change_endpoint_title() : string {

		return \Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->uppercase_first()->to_string();
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @since 2.0.12
	 *
	 * @param array $menu_items Menu items.
	 * @return array
	 */
	public function add_menu_items( $menu_items ) {

		// Try inserting after orders.
		$key_to_add   = 'account-funds';
		$value_to_add = \Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->uppercase_first()->to_string();

		$index_for_adding = array_search( 'orders', array_keys( $menu_items ), true );

		if ( false === $index_for_adding ) {
			$menu_items[ $key_to_add ] = $value_to_add;
		} else {
			++$index_for_adding;
			$menu_items = array_merge(
				array_slice( $menu_items, 0, intval( $index_for_adding ) ),
				[ $key_to_add => $value_to_add ],
				array_slice( $menu_items, $index_for_adding )
			);
		}

		return $menu_items;
	}

	/**
	 * Endpoint HTML content.
	 *
	 * @since 2.0.12
	 */
	public function endpoint_content() {
		wc_account_funds_get_template( 'myaccount/account-funds.php' );
	}

	/**
	 * Outputs the account funds content.
	 *
	 * @since 2.2.0
	 */
	public function account_funds_content() {

		if ( \Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up::enabled() ) {
			$this->my_account_topup();
		} else {
			$this->my_account_products();
		}

		$deposits = $this->get_deposits();

		if ( ! empty( $deposits ) ) {
			wc_account_funds_get_template( 'myaccount/recent-deposits.php' );
		}
	}

	/**
	 * Handle top-ups
	 */
	public function topup_handler() {
		if ( isset( $_POST['wc_account_funds_topup'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( wc_clean( wp_unslash( $_POST['_wpnonce'] ) ), 'account-funds-topup' ) ) {
			$min          = max( 0.01, floatval( \Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up::minimum_top_up() ) );
			$max          = \Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up::maximum_top_up();
			$topup_amount = ( isset( $_POST['topup_amount'] ) ? wc_clean( wp_unslash( $_POST['topup_amount'] ) ) : 1 );

			if ( $topup_amount < $min ) {
				/* translators: Placeholder- %s - minimum account funds deposits top-up amount */
				wc_add_notice( sprintf( __( 'The minimum amount that can be topped up is %s', 'woocommerce-account-funds' ), wc_price( $min ) ), 'error' );
				return;
			} elseif ( $max && $topup_amount > $max ) {
				/* translators: Placeholder: %s - maximum account funds deposits top-up amount */
				wc_add_notice( sprintf( __( 'The maximum amount that can be topped up is %s', 'woocommerce-account-funds' ), wc_price( $max ) ), 'error' );
				return;
			}

			$add_to_cart = WC()->cart->add_to_cart( wc_get_page_id( 'myaccount' ), true, '', '', [ 'top_up_amount' => $topup_amount ] );

			if ( false !== $add_to_cart ) {
				if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
					wp_safe_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
				} else {
					$view_cart_button = '<a href="' . esc_url( wc_get_cart_url() ) . '" class="button wc-forward">' . __( 'View cart', 'woocommerce-account-funds' ) . '</a>';
					/* translators: Placeholder: %s - Account funds deposits top-up amount */
					wc_add_notice( sprintf( __( 'Top-up of %1$s has been added to cart.', 'woocommerce-account-funds' ), wc_price( $topup_amount ) ) . $view_cart_button, 'success' );
				}
			}
		}
	}

	/**
	 * Show top up form
	 */
	public function my_account_topup() {
		$topup_in_cart = $this->get_topup_in_cart();
		$min           = \Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up::minimum_top_up();
		$max           = \Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up::maximum_top_up();

		if ( $max && isset( $topup_in_cart['data'] ) ) {
			$vars = [ 'topup_title_in_cart' => $topup_in_cart['data']->get_title() ];
			wc_account_funds_get_template( 'myaccount/account-funds/topup-in-cart-notice.php', $vars );
		} else {
			wc_account_funds_get_template( 'myaccount/topup-form.php', [
				'min_topup' => $min ? max( 0.01, $min ) : null,
				'max_topup' => $max > 0 ? max( (float) $min, $max ) : null,
			] );
		}
	}

	/**
	 * Get top-up items in cart.
	 *
	 * @since 2.0.6
	 *
	 * @return array
	 */
	private function get_topup_items_in_cart() {
		$topup_items = [];

		if ( WC()->cart instanceof WC_Cart && ! WC()->cart->is_empty() ) {
			$topup_items = array_filter( WC()->cart->get_cart(), [ $this, 'filter_topup_items' ] );
		}

		return (array) $topup_items;
	}

	/**
	 * Cart items filter callback to filter top-up product.
	 *
	 * @since 2.0.6
	 *
	 * @param array $item Cart item.
	 * @return bool Returns true if the cart item is a top-up product. False otherwise.
	 */
	public function filter_topup_items( $item ) {
		if ( isset( $item['data'] ) && is_callable( [ $item['data'], 'get_type' ] ) ) {
			return ( 'topup' === $item['data']->get_type() );
		}

		return false;
	}

	/**
	 * Show top up products
	 */
	private function my_account_products() {
		$product_ids = wc_get_products(
			[
				'return' => 'ids',
				'type'   => 'deposit',
			]
		);

		if ( $product_ids ) {
			echo do_shortcode( '[products ids="' . implode( ',', $product_ids ) . '"]' );
		}
	}

	/**
	 * Get deposits data
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function get_deposits() {
		if ( is_null( $this->deposits ) ) {
			$this->deposits = wc_get_orders(
				[
					'type'        => 'shop_order',
					'limit'       => 10,
					'status'      => [ 'wc-completed', 'wc-processing', 'wc-on-hold' ],
					'customer_id' => get_current_user_id(),
					'funds_query' => [
						[
							'key'   => '_funds_deposited',
							'value' => '1',
						],
					],
				]
			);
		}

		return $this->deposits;
	}

	/**
	 * Get top-up in cart
	 *
	 * @since 2.2.0
	 *
	 * @return array|null
	 */
	private function get_topup_in_cart() {
		if ( count( $this->topup_in_cart ) < 1 ) {
			$items_in_cart = $this->get_topup_items_in_cart();

			$this->topup_in_cart = array_shift( $items_in_cart );
		}

		// cast to array to avoid returning null from array_shift
		return (array) $this->topup_in_cart;
	}

	/**
	 * Get HTML string of recent deposits items.
	 *
	 * @since 2.2.0
	 */
	public function recent_deposit_items_data() {
		foreach ( $this->get_deposits() as $deposit ) {
			$funded = 0;

			foreach ( $deposit->get_items() as $item ) {
				$product = $item->get_product();

				if ( ! $product ) {
					continue;
				}

				if ( $product->is_type( 'deposit' ) || $product->is_type( 'topup' ) ) {
					$funded += $deposit->get_line_total( $item );
				}
			}

			wc_account_funds_get_template( 'myaccount/account-funds/deposit-item-data.php', [
				'deposit' => [
					'funded'            => $funded,
					'order_date'        => ( $deposit->get_date_created() ? gmdate( 'Y-m-d H:i:s', $deposit->get_date_created()->getOffsetTimestamp() ) : '' ),
					'order_url'         => $deposit->get_view_order_url(),
					'order_number'      => $deposit->get_order_number(),
					'order_status_name' => wc_get_order_status_name( $deposit->get_status() ),
				],
			] );
		}
	}
}

new WC_Account_Funds_My_Account();
