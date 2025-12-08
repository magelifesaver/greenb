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
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WooCommerce\Gateway as Abstract_Gateway;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;
use WC_Order;
use WC_Subscription;

/**
 * Account Funds payment gateway.
 *
 * @since 1.0.0
 */
final class Gateway extends Abstract_Gateway {

	/** @var string the gateway identifier used internally */
	public const ID = 'accountfunds';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin|null $plugin
	 */
	public function __construct( ?Plugin $plugin = null ) {

		parent::__construct( $plugin );

		$this->id       = self::ID;
		$this->supports = [
			'products',
			'refunds',
			'subscriptions',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_date_changes',
			'subscription_payment_method_change',
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
		];

		// the setting is not ready yet but this is only for showing the disabled title in the admin area anyway
		$labels = get_option( 'kestrel_account_funds_store_credit_label', [] );
		$title  = is_array( $labels ) && isset( $labels['singular'] ) ? $labels['singular'] : '';

		$this->title = $title;

		$this->init_form_fields();
		$this->init_settings();

		// subscriptions compatibility
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'process_subscription_payment' ], 10, 2 );
		add_filter( 'woocommerce_my_subscriptions_recurring_payment_method', [ $this, 'subscription_payment_method_name' ], 10, 3 );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
		add_action( 'woocommerce_subscriptions_paid_for_failed_renewal_order', [ $this, 'failed_renewal_order_paid' ], 5, 2 );
		add_action( 'subscriptions_activated_for_order', [ $this, 'subscriptions_activated_for_order' ], 5 );
	}

	/**
	 * Return the gateway's title used in the front end views.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function get_title() : string {

		return wp_strip_all_tags( Store_Credit_Label::plural()->to_string() );
	}

	/**
	 * Return the gateway's method title used in the admin area.
	 *
	 * @since 4.0.0
	 *
	 * @return mixed|string
	 */
	public function get_method_title() {

		return apply_filters( 'woocommerce_gateway_method_title', Store_Credit_Label::plural()->to_string(), $this ); // phpcs:ignore
	}

	/**
	 * Return the gateway's method description used in the admin area.
	 *
	 * @since 4.0.0
	 *
	 * @return mixed|string
	 */
	public function get_method_description() {

		return __( 'This gateway allows customers to pay for an order at checkout using their available store credit.', 'woocommerce-account-funds' );
	}

	/**
	 * Gets the default gateway's description.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_default_description() : string {

		/* translators: Placeholder: %s - literal {store_credit_balance} merge tag */
		return sprintf( __( 'Available balance: %s', 'woocommerce-account-funds' ), '{store_credit_balance}' );
	}

	/**
	 * Gets the gateway's description.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_description() {

		if ( ! $this->description ) {

			$balance     = '<strong>' . Wallet::get( get_current_user_id() )->to_string() . '</strong>';
			$description = $this->get_option( 'description', $this->get_default_description() );
			$description = str_replace( [ '{funds_amount}', '{store_credit_balance}' ], (string) $balance, $description ); // backwards compatibility for legacy merge tag

			$this->description = $description;
		}

		return parent::get_description();
	}

	/**
	 * Init form fields.
	 *
	 * @since 2.0.0
	 * @internal
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$this->form_fields = [
			'enabled'         => [
				/* translators: Context: Payment gateway enabled or disabled status */
				'title'   => __( 'Enabled', 'woocommerce-account-funds' ),
				'type'    => 'checkbox',
				'default' => 'yes',
				'label'   => __( 'Enable store credit', 'woocommerce-account-funds' ),
			],
			'title'           => [
				/* translators: Context: Payment gateway title */
				'title'             => __( 'Title', 'woocommerce-account-funds' ),
				'type'              => 'text',
				'desc_tip'          => __( 'This controls the payment gateway title which the user sees during checkout.', 'woocommerce-account-funds' ),
				'default'           => $this->title,
				'value'             => $this->title,
				'description'       => sprintf(
					/* translators: Placeholders: %1$s - opening HTML link tag, %2$s - closing HTML link tag */
					__( 'This value can be set and overridden by defining the customer-facing label for store credit in the plugin\'s %1$sgeneral settings%2$s.', 'woocommerce-account-funds' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=store-credit-settings' ) ) . '">',
					'</a>'
				),
				'custom_attributes' => [
					'disabled' => 'disabled',
				],
			],
			'description'     => [
				'title'       => __( 'Description', 'woocommerce-account-funds' ),
				'type'        => 'textarea',
				'description' => sprintf(
					/* translators: Placeholder: %s - Literal {store_credit_balance} merge tag */
					__( 'Payment method description that the customer will see on your checkout. Available placeholder: %s.', 'woocommerce-account-funds' ),
					'<code>{store_credit_balance}</code>(' . lcfirst( __( "Displays the current customer's store credit balance", 'woocommerce-account-funds' ) ) . ')'
				),
				'placeholder' => $this->get_default_description(),
				'desc_tip'    => false,
			],
			'partial_payment' => [
				'type'        => 'checkbox',
				'title'       => __( 'Partial payment', 'woocommerce-account-funds' ),
				'label'       => __( 'Allow customers to apply available store credit and pay the difference via another gateway', 'woocommerce-account-funds' ),
				'description' => __( "If disabled, users must pay for the entire order using store credit: if they don't have enough store credit, the gateway will not be available at checkout.", 'woocommerce-account-funds' ),
				'default'     => 'yes',
			],
		];
	}

	/**
	 * Initializes the payment gateway settings.
	 *
	 * @since 2.8.0
	 * @internal
	 *
	 * @return void
	 */
	public function init_settings() {

		parent::init_settings();

		$this->settings['title'] = $this->title;
	}

	/**
	 * Gets the order total in checkout and pay for order page.
	 *
	 * @since 2.3.6
	 * @internal
	 *
	 * @return float
	 */
	public function get_order_total() {

		// Use the subscription total on the subscription details page.
		// This allows showing/hiding the action "Add payment/Change payment" when "Store credit" is the unique available payment gateway for subscriptions.
		if ( function_exists( 'wcs_get_subscription' ) ) {
			$subscription_id = absint( get_query_var( 'view-subscription' ) );

			if ( ! $subscription_id ) {
				$subscription_id = absint( get_query_var( 'subscription-payment-method' ) );
			}

			if ( $subscription_id > 0 ) {
				$subscription = wcs_get_subscription( $subscription_id );

				return (float) $subscription->get_total();
			}
		}

		return (float) parent::get_order_total();
	}

	/**
	 * Determines if the gateway is enabled.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return 'yes' === $this->get_option( 'enabled', 'yes' );
	}

	/**
	 * Determines if the gateway allows partial payment with store credit
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function allows_partial_payment() : bool {

		$partial_payment = $this->get_option( 'account_funds_partial_payment', 'yes' );

		/**
		 * Filters whether customers can partially pay for an order using store credit.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $partial_payment
		 */
		return (bool) apply_filters( 'wc_account_funds_partial_payments_enabled', $this->is_enabled() && wc_string_to_bool( $partial_payment ) );
	}

	/**
	 * Returns the URL to the payment gateway settings page.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function settings_url() : string {

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . self::ID );
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * Implements {@see WC_Payment_Gateway::is_available()}.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_available() {

		// @phpstan-ignore-next-line cart may be not initialized yet
		if ( ! WC()->cart || ! is_user_logged_in() || ! $this->is_enabled() ) {
			return false;
		}

		// customer is using partial store credit payment
		if ( Cart::is_using_account_funds_partially() ) {
			return false;
		}

		// customer is purchasing store credit (via top-up or deposit, etc.)
		if ( Cart::cart_contains_account_funds_deposit() ) {
			return false;
		}

		// customer is not allowed to use store credit
		if ( ! Cart::current_user_can_apply_account_funds() ) {
			return false;
		}

		$wallet = Wallet::get( get_current_user_id() );

		// this may happen when a large discount may make the order free, but in this way we can let the customer change payment method and remove the discount if they so wish
		if ( $this->chosen ) {
			add_filter( 'woocommerce_cart_needs_payment', '__return_true' );
		}

		return $this->get_order_total() <= $wallet->available_balance();
	}

	/**
	 * Processes a payment.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param int|WC_Order $order_id order ID
	 * @return array<string, mixed> result array
	 */
	public function process_payment( $order_id ) {

		if ( ! is_user_logged_in() ) {
			$error = __( 'You must be logged in to use this payment method.', 'woocommerce-account-funds' );

			/* translators: Placeholder: %s - Error message */
			wc_add_notice( sprintf( __( 'Payment error: %s', 'woocommerce-account-funds' ), $error ), 'error' );

			return [ 'result' => 'error' ];
		}

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			$error = __( 'Order not found.', 'woocommerce-account-funds' );

			/* translators: Placeholder: %s - Error message */
			wc_add_notice( sprintf( __( 'Payment error: %s', 'woocommerce-account-funds' ), $error ), 'error' );

			return [ 'result' => 'error' ];
		}

		// changing the subscription's payment method
		if ( $order instanceof WC_Subscription ) {
			return [
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			];
		}

		try {
			Order::get( $order )->pay_with_store_credit();
		} catch ( Exception $exception ) {
			/* translators: Placeholder: %s - Error message */
			wc_add_notice( sprintf( __( 'Payment error: %s', 'woocommerce-account-funds' ), $exception->getMessage() ), 'error' );

			return [ 'result' => 'error' ];
		}

		$order->payment_complete();

		WC()->cart->empty_cart();

		return [
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		];
	}

	/**
	 * Process scheduled subscription payment.
	 *
	 * @since 2.4.0
	 * @internal
	 *
	 * @param float|int $order_total renewal order total
	 * @param int|WC_Order $order renewal order
	 * @return void
	 */
	public function process_subscription_payment( $order_total, $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		try {
			Order::get( $order )->pay_with_store_credit( (float) $order_total );
		} catch ( Exception $exception ) {
			/* translators: Placeholder: %s - Error message */
			$order->add_order_note( sprintf( __( 'Payment error: %s', 'woocommerce-account-funds' ), $exception->getMessage() ) );

			$this->payment_failed_for_subscriptions_on_order( $order );
			return;
		}

		/**
		 * Force save to ensure _funds_removed meta is persisted to the database before payment_complete() triggers hooks that may check this flag.
		 *
		 * @see Orders::debit_store_credit_upon_payment_complete())
		 */
		$order->save();

		$order->payment_complete();
	}

	/**
	 * Processes a refund.
	 *
	 * @since 2.4.0
	 * @internal
	 *
	 * @param int|mixed $order_id order ID
	 * @param float|mixed|null $amount refund amount
	 * @param mixed|string $reason refund reason
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

		$order = wc_get_order( $order_id );

		if ( ! $order || 0 >= (float) $amount ) {
			return false;
		}

		$wallet = Wallet::get( $order, $order->get_currency() );

		try {
			$wallet->credit( Transaction::seed( [
				'amount'   => (float) $amount,
				'event'    => Transaction_Event::ORDER_REFUNDED,
				'event_id' => $order_id,
				'note'     => $reason ?: '',
			] ) );

			$funds_refunded = (float) $order->get_meta( '_funds_refunded' );

			// @phpstan-ignore-next-line WC function has the wrong type hint
			$order->update_meta_data( '_funds_refunded', $funds_refunded + (float) $amount );
			$order->save_meta_data();

			$order->add_order_note( sprintf(
				/* translators: Placeholders: %1$s- refund amount, %2$s - payment gateway title */
				__( 'Refunded %1$s via %2$s.', 'woocommerce-account-funds' ),
				$wallet->to_string(),
				$this->get_method_title()
			) );

		} catch ( Exception $exception ) {
			Logger::warning( sprintf( 'Could not refund store credit for order #%1$s: %2$s', $order_id, $exception->getMessage() ) );

			return false;
		}

		return true;
	}

	/**
	 * Handles a failed payment for subscriptions in a given order.
	 *
	 * @since 2.1.7
	 *
	 * @param int|WC_Order $order order ID or order object
	 */
	protected function payment_failed_for_subscriptions_on_order( $order ) {

		foreach ( $this->get_subscriptions_for_order( $order ) as $subscription ) {

			// If "Store credit" is the unique payment gateway that support subscriptions, no payment gateways will be available during checkout.
			// So, we set the subscription to manual renewal.
			// @phpstan-ignore-next-line
			if ( ! $subscription->is_manual() ) {
				$subscription->set_requires_manual_renewal( true ); // @phpstan-ignore-line
				$subscription->add_meta_data( '_restore_auto_renewal', 'yes', true ); // @phpstan-ignore-line
				$subscription->save(); // @phpstan-ignore-line
			}

			$subscription->payment_failed(); // @phpstan-ignore-line
		}

		do_action( 'processed_subscription_payment_failure_for_order', $order ); // phpcs:ignore
	}

	/**
	 * Returns the subscriptions from a given order.
	 *
	 * @since 2.1.7
	 *
	 * @param int|WC_Order $order order ID or order object
	 * @return WC_Subscription[] list of subscriptions
	 *
	 * @phpstan-ignore-next-line
	 */
	protected function get_subscriptions_for_order( $order ) : array {

		// @phpstan-ignore-next-line
		return wcs_get_subscriptions_for_order( $order, [
			'order_type' => [ 'parent', 'renewal' ],
		] );
	}

	/**
	 * Payment method name for subscriptions.
	 *
	 * @since 2.3.8
	 * @internal
	 *
	 * @param mixed|string $payment_method_to_display the payment method name to display
	 * @param array|mixed $subscription_details subscription details
	 * @param mixed|WC_Order $order order object
	 * @return mixed|string
	 */
	public function subscription_payment_method_name( $payment_method_to_display, $subscription_details, $order ) {

		if ( ! $order instanceof WC_Order || ! $order->get_customer_id() || $this->id !== $order->get_meta( '_recurring_payment_method' ) ) {
			return $payment_method_to_display;
		}

		return sprintf(
			/* translators: Placeholder: %s - Title of the payment gateway an order was paid via */
			__( 'Via %s', 'woocommerce-account-funds' ),
			lcfirst( $this->get_method_title() )
		);
	}

	/**
	 * Processes a subscription after its failed renewal order has been paid.
	 *
	 * @since 2.3.8
	 * @internal
	 *
	 * @param mixed|WC_Order $order renewal order successfully paid
	 * @param mixed|WC_Subscription $subscription subscription related to the renewed order
	 * @return void
	 */
	public function failed_renewal_order_paid( $order, $subscription ) : void {

		$this->restore_auto_renewal( $subscription );
	}

	/**
	 * Processes subscriptions after being activated due to the payment of a renewal order.
	 *
	 * @since 2.3.8
	 * @internal
	 *
	 * @param int|mixed $order_id order ID
	 * @return void
	 */
	public function subscriptions_activated_for_order( $order_id ) : void {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// @phpstan-ignore-next-line
		$subscriptions = $this->get_subscriptions_for_order( $order );

		foreach ( $subscriptions as $subscription ) {
			$this->restore_auto_renewal( $subscription );
		}
	}

	/**
	 * Restores the subscription auto-renew previously deactivated when the payment with store credit failed.
	 *
	 * @since 2.3.8
	 *
	 * @param mixed|WC_Subscription $subscription subscription object
	 * @return void
	 */
	protected function restore_auto_renewal( $subscription ) : void {

		// @phpstan-ignore-next-line
		if ( $subscription instanceof WC_Subscription || ! $subscription->get_meta( '_restore_auto_renewal' ) ) {
			return;
		}

		$subscription->set_requires_manual_renewal( false ); // @phpstan-ignore-line
		$subscription->delete_meta_data( '_restore_auto_renewal' ); // @phpstan-ignore-line
		$subscription->save(); // @phpstan-ignore-line
	}

}

class_alias(
	__NAMESPACE__ . '\Gateway',
	'\WC_Gateway_Account_Funds'
);
