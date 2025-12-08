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

namespace Kestrel\Account_Funds\API\Store;

defined( 'ABSPATH' ) or exit;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Kestrel\Account_Funds\API\Exceptions\Account_Funds_Store_API_Exception;
use Kestrel\Account_Funds\Cart;
use Kestrel\Account_Funds\Gateway;
use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Wallet;

/**
 * Cart extension endpoint for the Store API.
 *
 * @since 3.1.0
 *
 * @method static Plugin plugin()
 */
final class Cart_Extension_Endpoint {
	use Is_Handler;

	/** @var string the endpoint identifier */
	public const IDENTIFIER = 'account-funds';

	/**
	 * Store API constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin the plugin instance
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		self::add_action( 'woocommerce_blocks_loaded', [ $this, 'register_store_api_endpoint' ] );
	}

	/**
	 * Registers a store credit extension endpoint with the WooCommerce Store API cart payload.
	 *
	 * Adds a `cart/extensions/account-funds` endpoint to the Store API cart payload.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return void
	 */
	protected function register_store_api_endpoint() : void {

		woocommerce_store_api_register_endpoint_data( [
			'endpoint'        => CartSchema::IDENTIFIER,
			'namespace'       => self::IDENTIFIER,
			'data_callback'   => [ $this, 'handle_endpoint_get_request' ],
			'schema_callback' => [ $this, 'get_endpoint_schema' ],
			'schema_type'     => ARRAY_A, // @phpstan-ignore-line WP constant
		] );

		woocommerce_store_api_register_update_callback( [
			'namespace' => self::IDENTIFIER,
			'callback'  => [ $this, 'handle_endpoint_update_request' ],
		] );
	}

	/**
	 * Gets the session data for store credits.
	 *
	 * This will be primarily used in payloads such as in Store API, Blocks, etc.
	 *
	 * @see Cart_Extension_Endpoint::handle_endpoint_get_request()
	 * @see Block_Integration_Trait::get_script_data()
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, mixed>
	 */
	public static function get_session_data() : array {

		$current_user_id           = get_current_user_id();
		$partial_payment_available = $payment_gateway_available = $is_using_store_credit_partially = false;
		$can_apply_store_credit    = Cart::current_user_can_apply_account_funds();
		$wallet                    = Wallet::get( $current_user_id );
		$available_store_credit    = $wallet->available_balance();

		if ( $can_apply_store_credit ) {

			$store_credit_gateway            = Gateway::instance();
			$payment_gateway_available       = $store_credit_gateway->is_available(); // @phpstan-ignore-line
			$partial_payment_available       = $store_credit_gateway->allows_partial_payment();
			$is_using_store_credit_partially = $partial_payment_available && Cart::is_using_account_funds_partially();

			if ( $is_using_store_credit_partially ) {
				$partial_payment_available = true;
				$payment_gateway_available = false;
			} elseif ( $available_store_credit >= Cart::get_total() ) {
				$partial_payment_available = false;
			}
		}

		if ( $is_using_store_credit_partially || Cart::get_applied_account_funds_amount() > 0 ) {
			$status = 'applied'; // store credits are being used in session, could be removed
		} elseif ( $can_apply_store_credit ) {
			$status = 'available'; // user is eligible to use store credit
		} else {
			$status = 'unavailable'; // user is not eligible to use store credit
		}

		return [
			'status'                    => $status,
			'user_id'                   => $current_user_id,
			'partial_payment_available' => $partial_payment_available,
			'payment_gateway_available' => $payment_gateway_available,
			'cart_contains_deposit'     => Cart::cart_contains_account_funds_deposit(),
			'available_funds'           => [
				'amount' => self::format_amount_in_cents( $available_store_credit ),
			],
			'applied_funds'             => [
				'amount' => self::format_amount_in_cents( Cart::get_applied_account_funds_amount() ),
			],
			'remaining_funds'           => [
				'amount' => self::format_amount_in_cents( Cart::get_remaining_account_funds_amount() ),
			],
		];
	}

	/**
	 * Formats an amount into cents.
	 *
	 * @since 3.1.0
	 *
	 * @param float $amount
	 * @return int
	 */
	private static function format_amount_in_cents( float $amount ) : int {

		return (int) wc_add_number_precision( $amount, true );
	}

	/**
	 * Handles a WooCommerce Store API request to the store credit endpoint.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return array<string, mixed>
	 */
	public function handle_endpoint_get_request() : array {

		return self::get_session_data();
	}

	/**
	 * Handles a WooCommerce Store API POST request to the store credit endpoint.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @param array<string, mixed>|mixed $request_data the request data
	 * @return void
	 * @throws Account_Funds_Store_API_Exception
	 */
	public function handle_endpoint_update_request( $request_data = [] ) : void {

		$request_data        = is_array( $request_data ) ? $request_data['data'] ?? $request_data : [];
		$account_funds_label = Store_Credit_Label::plural()->lowercase()->to_string();

		if ( ! is_array( $request_data ) ) {
			/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" (default) */
			throw new Account_Funds_Store_API_Exception( esc_html( sprintf( __( 'Invalid request data to handle %s.', 'woocommerce-account-funds' ), $account_funds_label ) ), 400 );
		}

		// @phpstan-ignore-next-line
		if ( empty( $request_data['refresh'] ) && ! WC()->session ) {
			/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" (default) */
			throw new Account_Funds_Store_API_Exception( esc_html( sprintf( __( 'Session not available to handle %s.', 'woocommerce-account-funds' ), $account_funds_label ) ), 500 );
		}

		if ( empty( $request_data['refresh'] ) && ! self::plugin()->gateway()->allows_partial_payment() ) {
			/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" (default) */
			throw new Account_Funds_Store_API_Exception( esc_html( sprintf( __( 'Partial payments using %s are not allowed.', 'woocommerce-account-funds' ), $account_funds_label ) ), 401 );
		}

		if ( ! Cart::current_user_can_apply_account_funds() ) {
			/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" (default) */
			throw new Account_Funds_Store_API_Exception( esc_html( sprintf( __( 'Customer is not eligible to use %s.', 'woocommerce-account-funds' ), $account_funds_label ) ), 401 );
		}

		if ( ! empty( $request_data['refresh'] ) ) {
			Cart::recalculate_totals(); // helps the frontend update the cart in case the mode switched between partial payment or gateway
		} elseif ( ! empty( $request_data['applyFunds'] ) ) {
			Cart::apply_account_funds_to_cart();
		} elseif ( ! empty( $request_data['removeFunds'] ) ) {
			Cart::remove_account_funds_from_cart();
			Cart::recalculate_totals();
		} else {
			/* translators: Placeholder: %s - Label used to describe store credit, e.g. "Store credit" (default) */
			throw new Account_Funds_Store_API_Exception( esc_html( sprintf( __( 'Invalid request data to handle %s.', 'woocommerce-account-funds' ), $account_funds_label ) ), 400 );
		}
	}

	/**
	 * Gets the store credit Store API endpoint schema.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return array<string, mixed>
	 */
	public function get_endpoint_schema() : array {

		return [
			'status'                    => [
				'description' => __( 'The current state of store credit for the current cart session.', 'woocommerce-account-funds' ),
				'type'        => 'string',
				'enum'        => [ 'applied', 'available', 'unavailable' ],
				'readonly'    => true,
			],
			'user_id'                   => [
				'description' => __( 'The ID of the user.', 'woocommerce-account-funds' ),
				'type'        => 'integer',
				'readonly'    => true,
			],
			'partial_payment_available' => [
				'description' => __( 'Whether partial payments using store credit are allowed.', 'woocommerce-account-funds' ),
				'type'        => 'boolean',
				'readonly'    => true,
			],
			'payment_gateway_available' => [
				'description' => __( 'Whether the store credit payment gateway is available.', 'woocommerce-account-funds' ),
				'type'        => 'boolean',
				'readonly'    => true,
			],
			'cart_contains_deposit'     => [
				'description' => __( 'Whether the cart contains a store credit deposit.', 'woocommerce-account-funds' ),
				'type'        => 'boolean',
				'readonly'    => true,
			],
			'available_funds'           => [
				'description' => __( 'The store credit balance available to the current user.', 'woocommerce-account-funds' ),
				'type'        => 'object',
				'properties'  => [
					'amount' => [
						'description' => __( 'The amount of store credit available.', 'woocommerce-account-funds' ),
						'type'        => 'number',
						'format'      => 'integer',
						'readonly'    => true,
					],
				],
				'readonly'    => true,
			],
			'applied_funds'             => [
				'description' => __( 'The store credit used in the current session.', 'woocommerce-account-funds' ),
				'type'        => 'object',
				'properties'  => [
					'amount' => [
						'description' => __( 'The amount of store credit used, in cents.', 'woocommerce-account-funds' ),
						'type'        => 'number',
						'format'      => 'integer',
						'readonly'    => true,
					],
				],
				'readonly'    => true,
			],
			'remaining_funds'           => [
				'description' => __( 'The store credit remaining to be used in the current session.', 'woocommerce-account-funds' ),
				'type'        => 'object',
				'properties'  => [
					'amount' => [
						'description' => __( 'The amount of store credit remaining, in cents.', 'woocommerce-account-funds' ),
						'type'        => 'number',
						'format'      => 'integer',
						'readonly'    => true,
					],
				],
				'readonly'    => true,
			],
		];
	}

}

class_alias(
	__NAMESPACE__ . '\Cart_Extension_Endpoint',
	'\Kestrel\WooCommerce\Account_Funds\API\Store\Cart_Extension_Endpoint'
);
