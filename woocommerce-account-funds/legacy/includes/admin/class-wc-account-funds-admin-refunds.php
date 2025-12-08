<?php
/**
 * Class for handling the order refunds in the admin screens.
 *
 * @package WC_Account_Funds/Admin
 * @since   2.9.0
 */

defined( 'ABSPATH' ) or exit;


/**
 * Class WC_Account_Funds_Admin_Refunds.
 */
class WC_Account_Funds_Admin_Refunds {

	/**
	 * Constructor.
	 *
	 * @since 2.9.0
	 */
	public function __construct() {
		add_filter( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'woocommerce_create_refund', [ $this, 'process_refund' ], 10, 2 );
		add_filter( 'woocommerce_order_refund_get_parent_id', [ $this, 'filter_refund_parent_id' ], 10, 2 );
	}

	/**
	 * Enqueues admin scripts.
	 *
	 * @since 2.6.0
	 */
	public function enqueue_scripts() {
		// The AJAX requests data cannot be filtered prior to WC 6.8.
		if ( version_compare( WC_VERSION, '6.8', '<' ) ) {
			return;
		}

		// It isn't the edit-order screen.
		if (
			wc_account_funds_get_current_screen_id() !== wc_account_funds_get_order_admin_screen() ||
			! isset( $_GET['action'] ) || 'edit' !== wc_clean( wp_unslash( $_GET['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		) {
			return;
		}

		$order = wc_get_order();

		if ( ! $order || ! $order->get_customer_id() || \Kestrel\Account_Funds\Gateway::ID === $order->get_payment_method() || $this->order_contains_deposit_only( $order ) ) {
			return;
		}

		$refund_amount = '<span class="wc-order-refund-amount">' . wc_price( 0, [ 'currency' => $order->get_currency() ] ) . '</span>';

		wp_enqueue_script( 'wc-account-funds-order-refund', \WC_ACCOUNT_FUNDS_URL . 'assets/js/admin/order-refund.min.js', [ 'jquery' ], WC_ACCOUNT_FUNDS_VERSION, true );
		wp_localize_script(
			'wc-account-funds-order-refund',
			'wc_account_funds_order_refund_params',
			[
				'button_text' => sprintf(
					/* translators: Placeholders: %1$s - Refund amount, %2$s - Account funds label, e.g. "Account funds" */
					esc_html__( 'Refund %1$s via %2$s', 'woocommerce' ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					wp_kses_post( $refund_amount ),
					\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->to_string()
				),
			]
		);
	}

	/**
	 * Gets if the order contains only deposit products.
	 *
	 * @since 4.0.0
	 *
	 * @param WC_Order $order
	 * @return bool
	 */
	private function order_contains_deposit_only( WC_Order $order ) : bool {

		/** @var WC_Order_Item_Product[] $items */
		$items = $order->get_items();

		foreach ( $items as $item ) {
			$product = $item->get_product();

			if ( $product && ! $product->is_type( [ 'deposit', 'topup' ] ) ) {
				return false;
			}
		}

		return ! empty( $items );
	}

	/**
	 * Processes the refund to account funds.
	 *
	 * @since 2.9.0
	 *
	 * @param WC_Order_Refund $refund Order refund object.
	 * @param array           $args   New refund arguments.
	 */
	public function process_refund( $refund, $args ) {
		// Don't refund to account funds or already refunded.
		if (
			$args['refund_payment'] || empty( $_POST['account_funds_refund'] ) || // phpcs:ignore WordPress.Security.NonceVerification
			'yes' === $refund->get_meta( 'account_funds_refunded' )
		) {
			return;
		}

		$order       = wc_get_order( $refund->get_parent_id( 'edit' ) );
		$customer_id = $order->get_customer_id();
		$amount      = $refund->get_amount();

		\Kestrel\Account_Funds\Users::increase_user_funds(
			$customer_id,
			floatval( $amount ),
			\Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event::ORDER_REFUNDED,
			$refund->get_id()
		);

		$refund->add_meta_data( 'account_funds_refunded', 'yes' );
		$refund->save();

		$order->add_order_note(
			sprintf(
				/* translators: Placeholders: %1$s - Refund amount, %2$s - Account funds label, e.g. "Account funds" */
				__( 'Refunded %1$s via %2$s.', 'woocommerce-account-funds' ),
				wc_price( $amount, [ 'currency' => $order->get_currency() ] ),
				\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->to_string()
			)
		);
	}

	/**
	 * Filters the property 'parent_id' of a refund object.
	 *
	 * There is no hook before deleting a refund.
	 *
	 * @since 2.9.0
	 *
	 * @param int             $order_id Order ID.
	 * @param WC_Order_Refund $refund   Refund object.
	 * @return int
	 */
	public function filter_refund_parent_id( $order_id, $refund ) {
		$backtrace  = wp_debug_backtrace_summary( 'WP_Hook', 0, false ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
		$save_index = array_search( 'WC_Abstract_Order->get_parent_id', $backtrace, true );

		if ( 'WC_AJAX::delete_refund' === $backtrace[ $save_index + 1 ] ) {
			$this->before_delete_refund( $refund );
		}

		return $order_id;
	}

	/**
	 * Processes a refund before deleting it.
	 *
	 * @since 2.9.0
	 *
	 * @param WC_Order_Refund $refund Refund object.
	 */
	public function before_delete_refund( $refund ) {
		if ( 'yes' !== $refund->get_meta( 'account_funds_refunded' ) ) {
			return;
		}

		$order       = wc_get_order( $refund->get_parent_id( 'edit' ) );
		$customer_id = $order->get_customer_id();
		$amount      = (float) $refund->get_amount();
		$wallet      = \Kestrel\Account_Funds\Store_Credit\Wallet::get( $customer_id );
		$balance     = $wallet->balance();

		if ( $balance < $amount ) {
			$order_note = sprintf(
				/* Translators: Placeholders: %1$s - Store credit amount to be removed, %$2s - Customer store credit balance amount -  %3$s - Label used to describe store credit */
				__( 'Insufficient %1$s in customer\'s balance (%2$s) to remove the amount of %3$s.', 'woocommerce-account-funds' ),
				\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->to_string(),
				wc_price( $balance, [ 'currency' => $order->get_currency() ] ),
				wc_price( $amount, [ 'currency' => $order->get_currency() ] )
			);
		} else {
			\Kestrel\Account_Funds\Users::decrease_user_funds(
				$customer_id,
				$amount,
				\Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event::ORDER_REFUNDED,
				$refund->get_id()
			);

			$order_note = sprintf(
				/* Translators: Placeholders: %1$s - Store credit amount, %2$s - Label used to describe store credit */
				__( 'Removed %1$s in %2$s from customer\'s balance.', 'woocommerce-account-funds' ),
				wc_price( $amount, [ 'currency' => $order->get_currency() ] ),
				\Kestrel\Account_Funds\Settings\Store_Credit_Label::plural()->lowercase()->to_string()
			);
		}

		$order->add_order_note( $order_note );
	}
}

return new WC_Account_Funds_Admin_Refunds();
