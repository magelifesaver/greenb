<?php
/**
 * Account Funds for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Account_Funds;

defined( 'ABSPATH' ) or exit;

use Exception;
use Kestrel\Account_Funds\Orders\Order;
use Kestrel\Account_Funds\Products\Store_Credit_Top_Up;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Plugins;
use Kestrel\Account_Funds\Settings\Store_Credit_Account_Top_Up;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Reward_Status;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use WC_Cart;
use WC_Order;

/**
 * Cart and checkout handler.
 *
 * @since 3.1.0
 *
 * @method static Plugin plugin()
 */
final class Cart {
	use Is_Handler;

	/** @var string session key that flags that store credit are being used for the current cart */
	private static string $using_store_credit_session_key = 'use-account-funds';

	/** @var string session key that records the amount of the store credit applied to the current cart */
	private static string $used_store_credit_session_key = 'used-account-funds';

	/** @var string flag used in the cart/checkout forms to apply store credit */
	private string $apply_store_credit_flag = 'apply_account_funds';

	/** @var string flag used in the cart/checkout forms to apply store credit */
	private string $applied_store_credit_flag = 'wc_account_funds_apply';

	/** @var string flag used in the cart/checkout forms to remove applied store credit */
	private string $remove_store_credit_flag = 'remove_account_funds';

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin the plugin instance
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		self::add_filter( 'wc_account_funds_partial_payments_enabled', [ $this, 'should_allow_partial_payments_using_store_credit' ] );

		// force registration during checkout if store credit top-up or deposit products are in the cart
		self::add_filter( 'woocommerce_checkout_registration_required', [ $this, 'require_customer_registration' ] );
		self::add_action( 'woocommerce_before_checkout_process', [ $this, 'force_customer_registration' ] );

		if ( ! is_admin() ) {
			self::add_filter( 'option_woocommerce_enable_signup_and_login_from_checkout', [ $this, 'enable_signup_and_login_at_checkout' ] );
			self::add_filter( 'option_woocommerce_enable_guest_checkout', [ $this, 'disable_guest_checkout' ] );
		}

		// display store credit CTAs on the legacy shortcode-based cart and checkout pages
		self::add_action( 'woocommerce_review_order_before_order_total', [ $this, 'display_store_credit' ] );
		self::add_action( 'woocommerce_cart_totals_before_order_total', [  $this, 'display_store_credit' ] );

		// maybe apply store credit to the cart
		self::add_action( 'woocommerce_checkout_update_order_review', [ $this, 'update_order_review' ] );
		self::add_action( 'wp_loaded', [ $this, 'maybe_apply_store_credit_to_cart' ], 15 );

		// handle cart totals calculations when store credits are applied
		self::add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'calculate_totals' ], 99, 2 );
		self::add_action( 'woocommerce_before_calculate_totals', [ $this, 'before_calculate_totals' ], 99 );

		// handle store credit deposits and top-up for the cart session
		self::add_filter( 'woocommerce_add_cart_item', [ $this, 'set_store_credit_top_up_amount' ], 10, 1 );
		self::add_filter( 'woocommerce_get_cart_item_from_session', [ $this, 'set_store_credit_top_up_amount_from_session' ], 10, 2 );

		// display a configured image for the top-up product in the cart when using blocks
		self::add_filter( 'woocommerce_store_api_cart_item_images', [ $this, 'display_top_up_product_image' ], 10, 2 );

		/**
		 * When WooCommerce tax services are used via {@see WC_Connect_TaxJar_Integration} the priority of the following action must be lower than 20.
		 * Otherwise, avoid double usage of store credit may occur when automatic taxes are calculated.
		 */
		self::add_action( 'woocommerce_after_calculate_totals', [ $this, 'after_calculate_totals' ], Plugins::is_plugin_active( 'woocommerce-services/woocommerce-services.php' ) ? 19 : 99, 2 );

		// display cashback rewards information at cart and checkout
		self::add_action( 'woocommerce_cart_totals_after_order_total', [ $this, 'display_cashback_reward_information' ] );
		self::add_action( 'woocommerce_review_order_after_cart_contents', [ $this, 'display_cashback_reward_information' ] );
	}

	/**
	 * Displays information about the cashback rewards that can be earned for the current cart at cart and checkout.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	protected function display_cashback_reward_information() : void {

		if ( ! wp_doing_ajax() && current_action() !== 'woocommerce_cart_totals_after_order_total' ) {
			return;
		}

		/**
		 * Filters whether to display the cashback reward information for the current order.
		 *
		 * @since 4.0.0
		 *
		 * @param bool $display_cashback_reward_information
		 */
		if ( false === apply_filters( 'wc_account_funds_should_display_cashback_reward_information_for_order', true ) ) {
			return;
		}

		$rewards = Cashback::find_many( [
			'status'  => Reward_Status::ACTIVE,
			'deleted' => false,
		] );

		if ( $rewards->is_empty() ) {
			return;
		}

		$customer       = wp_get_current_user();
		$customer_email = $customer->user_email;

		if ( ! $customer_email ) {
			$customer_email = is_email( get_query_var( 'customer_email' ) ) ? get_query_var( 'customer_email' ) : null;
		}

		$wallet = Wallet::get( $customer_email ?: $customer );

		try {
			// set a mock order with the cart contents to obtain applicable cashback rewards
			$wc_order = new WC_Order();
			$wc_order->set_customer_id( $wallet->id() );
			$wc_order->set_billing_email( $wallet->email() );

			foreach ( WC()->cart->get_cart() as $cart_item ) {

				if ( ! isset( $cart_item['data'] ) || ! $cart_item['data'] instanceof \WC_Product ) {
					continue;
				}

				$variation_data = isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? [ 'variation' => $cart_item['variation'] ] : [];

				$wc_order->add_product( $cart_item['data'], intval( $cart_item['quantity'] ) ?: 1, $variation_data );
			}

			$wc_order->calculate_totals();
		} catch ( Exception $exception ) {
			// @phpstan-ignore-next-line
			if ( isset( $wc_order ) && $wc_order instanceof WC_Order ) {
				$wc_order->delete( true );
			}

			return;
		}

		$order   = Order::get( $wc_order );
		$rewards = $order->applicable_cashback_rewards( $wallet );
		$amount  = array_reduce( $rewards, static function( float $carry, Wallet\Transaction $reward ) : float {
			return $carry + $reward->get_amount();
		}, 0.0 );

		// we need to call this because WooCommerce will persist the order even if we don't call save() ಠ_ಠ
		$wc_order->delete( true );

		if ( $amount > 0.0 ) {
			/**
			 * Filters the message displayed for the cashback reward information in the cart and checkout forms
			 *
			 * @since 4.0.0
			 *
			 * @param float $amount
			 * @param Wallet $wallet
			 * @param Order $order
			 * @param string $message
			 */
			$message = (string) apply_filters( 'wc_account_funds_cashback_reward_information_for_order', sprintf(
				/* translators: Placeholders: %1$s - formatted store credit amount HTML, %2$s - store credit label */
				esc_html__( 'Earn %1$s in %2$s for placing this order!', 'woocommerce-account-funds' ),
				'<strong>' . wc_price( $amount ) . '</strong>',
				wc_account_funds_store_credit_label()
			), $amount, $wallet, $order );

			echo '<p class="store-credit-reward store-credit-cashback store-credit-cashback-reward">' . $message . '</p>'; // phpcs:ignore
		}
	}

	/**
	 * Sets the top-up image in block cart.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $product_images the product images
	 * @param array<string, mixed>|mixed $cart_item the cart item
	 * @return array<string, mixed>|mixed
	 */
	protected function display_top_up_product_image( $product_images, $cart_item ) {

		if ( ! is_array( $product_images ) || ! is_array( $cart_item ) || ! isset( $cart_item['top_up_amount'] ) ) {
			return $product_images;
		}

		return Store_Credit_Account_Top_Up::image_data();
	}

	/**
	 * Sets the cart item price for when a {@see Store_Credit_Top_Up} product is added to the cart by the customer.
	 *
	 * Since it's up to the customer to define the top-up value, this comes from the cast item variable, but it's not set on the item product yet.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $cart_item
	 * @return array<string, mixed>|mixed
	 */
	protected function set_store_credit_top_up_amount( $cart_item ) {

		if ( is_array( $cart_item ) && ! empty( $cart_item['top_up_amount'] ) ) {

			$cart_item['data']->set_price( $cart_item['top_up_amount'] );

			$cart_item['variation'] = [];
		}

		return $cart_item;
	}

	/**
	 * Sets the cart item price for when a {@see Store_Credit_Top_Up} is retrieved from the session.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed>|mixed $cart_item
	 * @param array<string, mixed>|mixed $values
	 * @return array<string, mixed>|mixed
	 */
	protected function set_store_credit_top_up_amount_from_session( $cart_item, $values ) {

		if ( is_array( $cart_item ) && is_array( $values ) && ! empty( $values['top_up_amount'] ) ) {

			$cart_item['top_up_amount'] = $values['top_up_amount'];

			$cart_item = $this->set_store_credit_top_up_amount( $cart_item );
		}

		return $cart_item;
	}

	/**
	 * Applies store credit to the current cart.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function apply_account_funds_to_cart() : void {

		// @phpstan-ignore-next-line
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( self::$using_store_credit_session_key, true );
	}

	/**
	 * Determines if store credits are being used to pay partially for the current cart.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function is_using_account_funds_partially() : bool {

		return WC()->session && WC()->session->get( self::$using_store_credit_session_key, false ); // @phpstan-ignore-line
	}

	/**
	 * Sets the applied store credit in the current session.
	 *
	 * @since 3.1.0
	 *
	 * @param float|int $amount the amount of funds applied
	 * @return void
	 */
	public static function set_applied_account_funds_amount( $amount ) : void {

		$amount = empty( $amount ) ? (float) $amount : $amount;

		// @phpstan-ignore-next-line
		if ( ! WC()->session || ! is_numeric( $amount ) ) {
			return;
		}

		WC()->session->set( self::$used_store_credit_session_key, (float) $amount );
	}

	/**
	 * Gets the amount of store credit used to pay for the current cart, if any.
	 *
	 * @since 3.1.0
	 *
	 * @return float
	 */
	public static function get_applied_account_funds_amount() : float {

		if ( self::account_funds_gateway_is_chosen() ) {
			$available_balance  = Wallet::get( get_current_user_id() )->available_balance();
			$used_account_funds = min( $available_balance, self::get_total() );
		} else {
			$used_account_funds = WC()->session ? WC()->session->get( self::$used_store_credit_session_key, 0 ) : 0; // @phpstan-ignore-line
		}

		return (float) is_numeric( $used_account_funds ) ? $used_account_funds : 0;
	}

	/**
	 * Calculates the remaining balance if store credits were applied to the current cart.
	 *
	 * @since 3.1.0
	 *
	 * @return float
	 */
	public static function get_remaining_account_funds_amount() : float {

		$available_balance = Wallet::get( get_current_user_id() )->available_balance();

		return (float) max( 0, $available_balance - self::get_applied_account_funds_amount() );
	}

	/**
	 * Removes store credits from the current cart session.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function remove_account_funds_from_cart() : void {

		// @phpstan-ignore-next-line
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( self::$using_store_credit_session_key, false );
		WC()->session->set( self::$used_store_credit_session_key, 0 );
	}

	/**
	 * Determines if the current user can apply store credit to the cart.
	 *
	 * This works either when applying store credits partially or using the payment method to cover for the entire order.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function current_user_can_apply_account_funds() : bool {

		$available_balance    = Wallet::get( get_current_user_id() )->available_balance();
		$can_use_store_credit = $available_balance > 0;

		if ( $can_use_store_credit ) {

			if ( self::cart_contains_account_funds_deposit() ) {

				// cannot buy store credit with store credit
				$can_use_store_credit = false;

			} else {

				$cart_total = self::get_total();

				// customer can use some store credits to pay for the order
				$can_use_store_credit = $cart_total > $available_balance && self::plugin()->gateway()->allows_partial_payment();

				// alternatively, the store credits should cover the entire order
				if ( ! $can_use_store_credit ) {
					$can_use_store_credit = $cart_total <= $available_balance;
				}
			}
		}

		/**
		 * Filters if the customer can use store credit.
		 *
		 * @since 2.3.0
		 *
		 * @param bool $can_use_store_credit whether the customer can use store credit
		 */
		return (bool) apply_filters( 'wc_account_funds_can_use_funds', $can_use_store_credit );
	}

	/**
	 * Determines if a cart contains a store credit top-up or deposit.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function cart_contains_account_funds_deposit() : bool {

		// @phpstan-ignore-next-line
		if ( ! WC()->cart ) {
			return false;
		}

		$contains_deposit_or_top_up = false;

		foreach ( WC()->cart->get_cart() as $item ) {

			if ( $item['data']->is_type( 'deposit' ) || $item['data']->is_type( 'topup' ) ) {

				$contains_deposit_or_top_up = true;
				break;
			}
		}

		/**
		 * Filters whether the cart contains a store credit deposit or top-up.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $contains_deposit_or_top_up
		 */
		return (bool) apply_filters( 'wc_account_funds_cart_contains_deposit', $contains_deposit_or_top_up );
	}

	/**
	 * Gets the label for applying store credit in the cart or checkout.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	private static function get_pay_with_store_credit_label() : string {

		$user_wallet   = Wallet::get( get_current_user_id() );
		$account_funds = (float) max( 0, min( self::get_total(), $user_wallet->available_balance() ) );

		$label = sprintf(
			/* translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credits (default: "Store credit") */
			__( 'Use <strong>%1$s</strong> from your %2$s balance.', 'woocommerce-account-funds' ),
			wc_price( $account_funds ),
			Store_Credit_Label::plural()->lowercase()->to_string()
		);

		/**
		 * Filters the customer-facing label for applying store credit in cart or checkout.
		 *
		 * @since 3.1.0
		 *
		 * @param string $label HTML
		 */
		return (string) apply_filters( 'wc_account_funds_apply_funds_cart_label', $label );
	}

	/**
	 * Gets the customer-facing label for removing store credit used in cart or checkout.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private static function get_remove_applied_store_credit_label() : string {

		/* translators: Context: Button label to remove applied store credit from cart */
		$label = __( 'Remove', 'woocommerce-account-funds' );

		/**
		 * Filters the customer-facing label for removing store credit from cart or checkout.
		 *
		 * @since 3.1.0
		 *
		 * @param string $label HTML
		 */
		return (string) apply_filters( 'wc_account_funds_remove_funds_cart_label', $label );
	}

	/**
	 * Displays store credit CTAs on the cart and checkout pages.
	 *
	 * This will only work on the legacy shortcode-based pages.
	 * For the blocks handling, {@see Blocks}.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function display_store_credit() : void {

		if ( self::is_using_account_funds_partially() ) :

			$funds_used = self::get_applied_account_funds_amount();

			if ( $funds_used > 0 ) :

				?>
				<tr class="order-discount account-funds-discount">
					<th>
						<?php
						echo esc_html( Store_Credit_Label::plural()->to_string() );

						?>
					</th>
					<td>
						<?php echo wp_kses_post( sprintf( '-%1$s <a href="%2$s">[%3$s]</a>', wc_price( $funds_used ), esc_url( add_query_arg( $this->remove_store_credit_flag, true, wc_get_page_permalink( is_cart() ? 'cart' : 'checkout' ) ) ), self::get_remove_applied_store_credit_label() ) ); ?>
					</td>
				</tr>
				<?php

			endif;

		elseif ( self::current_user_can_apply_account_funds() && ! self::account_funds_gateway_is_available() ) :

			?>
			<tr class="account-funds">
				<th>
					<?php echo esc_html( Store_Credit_Label::plural()->to_string() ); ?>
				</th>
				<td>
					<input id="<?php echo esc_attr( $this->apply_store_credit_flag ); ?>" name="<?php echo esc_attr( $this->apply_store_credit_flag ); ?>" type="checkbox" value="1" />
					<label for="<?php echo esc_attr( $this->apply_store_credit_flag ); ?>"><?php echo wp_kses_post( self::get_pay_with_store_credit_label() ); ?></label>
				</td>
			</tr>
			<?php

		endif;

		/** @see Cart::maybe_apply_store_credit_to_cart() */
		if ( is_cart() ) :

			wc_enqueue_js( "
				$( document ).on( 'change', '#" . esc_js( $this->apply_store_credit_flag ) . "', function( event ) {
					if ( event.target.checked ) {
						$( '.woocommerce-cart-form' ).append( '<input type=\"hidden\" name=\"" . esc_js( $this->applied_store_credit_flag ) . "\" value=\"1\" />' );
						$( '.woocommerce-cart-form :input[name=\"update_cart\"]' )
							.prop( 'disabled', false )
							.attr( 'aria-disabled', false )
							.trigger( 'click' );
					}
				} );
			" );

		endif;

		if ( is_checkout() && ! is_checkout_pay_page() && ! is_order_received_page() ) :

			wc_enqueue_js( "
				$( document )
					.on( 'change', 'input[name=payment_method]', function() {
						if ( $( '#payment_method_accountfunds' ).length ) {
							$( 'body' ).trigger( 'update_checkout' );
						}
					} )
					.on( 'change', '#" . esc_js( $this->apply_store_credit_flag ) . "', function( event ) {
						if ( event.target.checked ) {
							$( 'body' ).trigger( 'update_checkout' );
						}
					});
			" );

		endif;
	}

	/**
	 * Processes the updated order review.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @param mixed|string $post_data the post data
	 * @return void
	 */
	protected function update_order_review( $post_data ) : void {

		if ( ! is_string( $post_data ) ) {
			return;
		}

		parse_str( $post_data, $data );

		$data = wc_clean( wp_unslash( $data ) );

		if ( ! empty( $data[ $this->apply_store_credit_flag ] ) && self::current_user_can_apply_account_funds() ) {
			self::apply_account_funds_to_cart();
		}
	}

	/**
	 * Maybe applies store credit in cart.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return void
	 */
	protected function maybe_apply_store_credit_to_cart() : void {

		if ( ! self::current_user_can_apply_account_funds() ) {
			return;
		}

		if ( ! empty( $_POST[ $this->applied_store_credit_flag ] ) ) { // phpcs:ignore

			self::apply_account_funds_to_cart();

		} elseif ( ! empty( $_GET[ $this->remove_store_credit_flag ] ) ) { // phpcs:ignore

			self::remove_account_funds_from_cart();

			wp_safe_redirect( esc_url_raw( remove_query_arg( 'remove_account_funds' ) ) );
			exit;
		}
	}

	/**
	 * Removes store credit before calculating totals.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return void
	 */
	protected function before_calculate_totals() : void {

		if ( self::is_using_account_funds_partially() && ! self::current_user_can_apply_account_funds() ) {
			self::remove_account_funds_from_cart();
		}
	}

	/**
	 * After calculate totals.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed|WC_Cart $cart the cart object
	 * @return void
	 */
	protected function after_calculate_totals( $cart ) {

		$cart = $cart ?: WC()->cart;

		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		// remove the discount if the payment gateway is no longer available and calculate the totals again
		if ( ! isset( $available_gateways[ Gateway::ID ] ) && Gateway::ID === WC()->session->get( 'chosen_payment_method' ) ) { // @phpstan-ignore-line
			WC()->session->set( 'chosen_payment_method', '' );
		}

		/**
		 * Filters whether the cart totals should be calculated.
		 *
		 * @since 3.1.0
		 *
		 * @param WC_Cart $cart the cart object
		 * @param bool $should_skip_calculating_cart_total whether the cart totals should be calculated
		 */
		if ( (bool) apply_filters( 'wc_account_funds_should_skip_setting_cart_totals', property_exists( $cart, 'recurring_cart_key' ) || ! self::is_using_account_funds_partially(), $cart ) ) {
			return;
		}

		$cart_total        = (float) $cart->get_total( 'edit' );
		$available_balance = Wallet::get( get_current_user_id() )->available_balance();

		// use the payment gateway instead
		if ( $available_balance >= $cart_total ) {

			self::remove_account_funds_from_cart();
			self::recalculate_totals();

			return;
		}

		$cart->set_total( max( 0, $cart_total - $available_balance ) ); // @phpstan-ignore-line bad types in WC

		self::set_applied_account_funds_amount( max( 0, min( $available_balance, $cart_total ) ) );
		self::apply_account_funds_to_cart();
	}

	/**
	 * Calculates the cart totals.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param mixed|WC_Cart $cart the cart object
	 * @return void
	 */
	protected function calculate_totals( $cart ) {

		$cart = $cart instanceof WC_Cart ? $cart : WC()->cart; // @phpstan-ignore-line

		/**
		 * Filters whether the cart totals should be calculated.
		 *
		 * @since 3.1.0
		 *
		 * @param WC_Cart $cart the cart object
		 * @param bool $should_skip_calculating_cart_total whether the cart totals should be calculated
		 */
		if ( (bool) apply_filters( 'wc_account_funds_should_skip_calculating_cart_totals', false, $cart ) ) {
			return;
		}

		if ( self::account_funds_gateway_is_chosen() ) {
			self::recalculate_totals();
		}
	}

	/**
	 * Recalculates the cart totals.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public static function recalculate_totals() : bool {

		// @phpstan-ignore-next-line
		if ( ! WC()->cart ) {
			return false;
		}

		WC()->cart->calculate_totals();
		WC()->cart->calculate_shipping();

		return true;
	}

	/**
	 * Gets the total amount of the cart.
	 *
	 * @since 3.1.0
	 *
	 * @return float
	 */
	public static function get_total() : float {

		return (float) ( WC()->cart ? (float) WC()->cart->get_total( 'edit' ) : 0 ); // @phpstan-ignore-line
	}

	/**
	 * Determines if customers can partially pay for an order using store credit.
	 *
	 * @since 3.1.0
	 *
	 * @param bool|mixed $allow whether partial payments are allowed
	 * @return bool|mixed
	 */
	protected function should_allow_partial_payments_using_store_credit( $allow ) {

		// @phpstan-ignore-next-line
		if ( ! $allow || ! WC()->cart ) {
			return $allow;
		}

		// avoid calling the gateway instance directly to prevent a recursion issue
		$gateway_settings = get_option( 'woocommerce_accountfunds_settings', [] );

		if ( ! $gateway_settings || ! isset( $gateway_settings['enabled'] ) || 'yes' === $gateway_settings['enabled'] ) {
			return $allow;
		}

		$wallet = Wallet::get( get_current_user_id() );

		if ( $wallet->available_balance() >= self::get_total() ) {
			$allow = false;
		}

		return $allow;
	}

	/**
	 * Requires customer registration if the cart contains an store credit deposit.
	 *
	 * @since 3.1.0
	 *
	 * @param bool|mixed $registration_required
	 * @return bool|mixed
	 */
	protected function require_customer_registration( $registration_required ) {

		if ( ! $registration_required && self::cart_contains_account_funds_deposit() && ! is_user_logged_in() ) {
			$registration_required = true;
		}

		return $registration_required;
	}

	/**
	 * Forces customer registration when the cart contains a store credit deposit.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function force_customer_registration() : void {

		if ( empty( $_POST['createaccount'] ) && self::cart_contains_account_funds_deposit() && ! is_user_logged_in() ) {
			$_POST['createaccount'] = 1;
		}
	}

	/**
	 * Enables customer signup and login at checkout if the cart contains a store credit deposit or top-up product.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed|string $value
	 * @return mixed|string
	 */
	protected function enable_signup_and_login_at_checkout( $value ) {

		return self::cart_contains_account_funds_deposit() ? 'yes' : $value;
	}

	/**
	 * Disables guest checkout if the cart contains a store credit deposit or top-up product.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed|string $value
	 * @return mixed|string
	 */
	protected function disable_guest_checkout( $value ) {

		return self::cart_contains_account_funds_deposit() ? 'no' : $value;
	}

	/**
	 * Applies a discount to the cart for using store credit.
	 *
	 * @since 3.1.0
	 * @deprecated 4.0.0
	 * @internal
	 *
	 * @return void
	 */
	public static function apply_account_funds_discount() : void {

		wc_deprecated_function( __METHOD__, '4.0.0' );
	}

	/**
	 * Determines if the store credit payment gateway is available.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	private static function account_funds_gateway_is_available() : bool {

		$gateways = WC()->payment_gateways();

		$available_gateways = $gateways ? $gateways->get_available_payment_gateways() : null; // @phpstan-ignore-line

		return is_array( $available_gateways ) && isset( $available_gateways[ Gateway::ID ] ); // @phpstan-ignore-line
	}

	/**
	 * Determines if the store credit payment gateway was selected.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed>|null $checkout_data
	 * @return bool
	 */
	public static function account_funds_gateway_is_chosen( ?array $checkout_data = null ) : bool {

		$gateways           = WC()->payment_gateways();
		$available_gateways = $gateways ? $gateways->get_available_payment_gateways() : []; // @phpstan-ignore-line
		$post_data          = $checkout_data ?: $_POST; // phpcs:ignore

		return ( isset( $available_gateways[ Gateway::ID ]->chosen ) && $available_gateways[ Gateway::ID ]->chosen ) // @phpstan-ignore-line
			|| ( ! empty( $post_data['payment_method'] ) && Gateway::ID === $post_data['payment_method'] ); // @phpstan-ignore-line
	}

}

class_alias(
	__NAMESPACE__ . '\Cart',
	'\Kestrel\WooCommerce\Account_Funds\Cart'
);
