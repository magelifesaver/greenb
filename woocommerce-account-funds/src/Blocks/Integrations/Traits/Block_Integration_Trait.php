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

namespace Kestrel\Account_Funds\Blocks\Integrations\Traits;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Kestrel\Account_Funds\API\Store\Cart_Extension_Endpoint;
use Kestrel\Account_Funds\Blocks;
use Kestrel\Account_Funds\Blocks\Integrations\Account_Funds_Payment_Method;
use Kestrel\Account_Funds\Gateway;
use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use Kestrel\Account_Funds\Store_Credit\Wallet;
use WC_Payment_Gateway;

/**
 * Collection of methods shared by classes implementing the {@see IntegrationInterface} interface or the {@see AbstractPaymentMethodType} abstract class.
 *
 * @see Account_Funds_Payment_Method
 *
 * @since 3.1.0
 *
 * @method static Plugin plugin()
 */
trait Block_Integration_Trait {

	/** @var string block integration namespace */
	protected string $namespace = 'kestrel';

	/** @var string the block the integration is for */
	protected string $integration_block_name = '';

	/** @var string[] block integration script dependencies */
	protected array $script_dependencies = [
		'wc-blocks-registry',
		'wc-blocks-checkout',
		'wc-settings',
		'wp-element',
		'wp-html-entities',
		'wp-i18n',
		'wp-data',
	];

	/** @var string[] main stylesheet dependencies */
	protected array $stylesheet_dependencies = [];

	/**
	 * Initializes the integration.
	 *
	 * Implements {@see IntegrationInterface::initialize()}.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return void
	 */
	public function initialize() {

		$this->register_assets();
	}

	/**
	 * Gets the integration slug (dashes).
	 *
	 * This is mainly used to register the integration where an identifier is required, e.g. when registering assets.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_slug() : string {

		return 'wc-account-funds-cart-checkout-block';
	}

	/**
	 * Gets the integration id (underscores).
	 *
	 *  This is mainly used in hook names implemented by the integration.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_id() : string {

		return 'wc_account_funds_cart_checkout_block';
	}

	/**
	 * Registers the block integration assets.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function register_assets() : void {

		wp_register_script( $this->get_script_handle(), $this->get_script_url(), $this->get_script_dependencies(), static::plugin()->version(), [ 'in_footer' => true ] );

		wp_set_script_translations( $this->get_script_handle(), 'woocommerce-account-funds' );

		wp_register_style( $this->get_stylesheet_handle(), $this->get_stylesheet_url(), $this->get_stylesheet_dependencies(), static::plugin()->version() );

		add_action( 'wp_enqueue_scripts', fn() => $this->maybe_enqueue_assets() );
	}

	/**
	 * Enqueues the block integration assets if the current context is cart or checkout.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	protected function maybe_enqueue_assets() : void {

		// should handle style assets also for the editor
		wp_enqueue_block_style( $this->get_name(), [
			'handle' => $this->get_stylesheet_handle(),
			'src'    => $this->get_stylesheet_url(),
			'deps'   => $this->get_stylesheet_dependencies(),
			'ver'    => static::plugin()->version(),
		] );

		if ( $this->should_enqueue_assets() ) {

			wp_enqueue_script( $this->get_script_handle() );
			wp_enqueue_style( $this->get_stylesheet_handle() );
		}
	}

	/**
	 * Determines whether the block integration assets should be enqueued.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	private function should_enqueue_assets() : bool {

		$should_enqueue = is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page();

		return $should_enqueue
			&& ( ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) || Blocks::is_cart_block_in_use() || Blocks::is_checkout_block_in_use() );
	}

	/**
	 * Gets an array of script handles to enqueue in the frontend context.
	 *
	 * Implements {@see IntegrationInterface::get_script_handles()}.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public function get_script_handles() {

		return [ $this->get_script_handle() ];
	}

	/**
	 * Gets an array of script handles to enqueue in the editor context.
	 *
	 * Implements {@see IntegrationInterface::get_editor_script_handles()}.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {

		return [ $this->get_script_handle() ];
	}

	/**
	 * Gets the integration script handle.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_script_handle() : string {

		/**
		 * Filters the store credit block integration script handle.
		 *
		 * @since 3.1.0
		 *
		 * @param string $handle
		 * @param IntegrationInterface $integration
		 */
		return (string) apply_filters( "{$this->get_id()}_script_handle", $this->get_slug(), $this );
	}

	/**
	 * Gets the integration script URL.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_script_url() : string {

		/**
		 * Filters the store credit block integration script handle.
		 *
		 * @since 3.1.0
		 *
		 * @param string $handle
		 * @param IntegrationInterface $integration
		 */
		return (string) apply_filters( "{$this->get_id()}_script_url", static::plugin()->assets_url( 'js/blocks/' . $this->get_slug() . '.js' ), $this );
	}

	/**
	 * Gets the integration stylesheet handle.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_stylesheet_handle() : string {

		/**
		 * Filters the store credit block integration stylesheet handle.
		 *
		 * @since 3.1.0
		 *
		 * @param string $handle
		 * @param IntegrationInterface $integration
		 */
		return (string) apply_filters( "{$this->get_id()}_stylesheet_handle", $this->get_slug(), $this );
	}

	/**
	 * Gets the integration stylesheet URL.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	protected function get_stylesheet_url() : string {

		/**
		 * Filters the store credit block integration stylesheet URL.
		 *
		 * @since 3.1.0
		 *
		 * @param string $url
		 * @param IntegrationInterface $integration
		 */
		return (string) apply_filters( "{$this->get_id()}_stylesheet_url", static::plugin()->assets_url( 'css/blocks/' . $this->get_slug() . '.css' ), $this );
	}

	/**
	 * Gets the integration script dependencies.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	protected function get_script_dependencies() : array {

		/**
		 * Filters the store credit block integration script dependencies.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $dependencies
		 * @param IntegrationInterface $integration
		 */
		return (array) apply_filters( "{$this->get_id()}_script_dependencies", $this->script_dependencies, $this );
	}

	/**
	 * Gets the integration stylesheet dependencies.
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	protected function get_stylesheet_dependencies() : array {

		/**
		 * Filters the store credit block integration script stylesheet dependencies.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $dependencies
		 * @param IntegrationInterface $integration
		 */
		return (array) apply_filters( "{$this->get_id()}_stylesheet_dependencies", $this->stylesheet_dependencies, $this );
	}

	/**
	 * Gets array of key-value pairs of data made available to the block on the client side.
	 *
	 * Implements {@see IntegrationInterface::get_script_data()}.
	 * Uses {@see Cart_Extension_Endpoint::get_session_data()}.
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_script_data() {

		$account_funds_gateway = $this->get_gateway();

		/**
		 * Filters the integration script data.
		 *
		 * @since 3.1.0
		 *
		 * @param array<string, mixed> $data
		 * @param IntegrationInterface $integration
		 */
		return (array) apply_filters( "{$this->get_id()}_script_data", array_merge( [
			'title'       => $account_funds_gateway && $account_funds_gateway->method_title ? $account_funds_gateway->get_method_title() : Store_Credit_Label::plural()->uppercase_first()->to_string(), // as the "Payment Method" or section title
			'name'        => Gateway::ID,
			'id'          => Gateway::ID,
			'type'        => 'account-funds', // internal-use only
			'description' => $account_funds_gateway ? $this->get_description_parts( $account_funds_gateway ) : [],
			'supports'    => $account_funds_gateway ? (array) $account_funds_gateway->supports : [],
			'flags'       => [
				'is_cart'     => is_cart(),
				'is_checkout' => is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page(),
			],
		], Cart_Extension_Endpoint::get_session_data() ), $this );
	}

	/**
	 * Gets the description parts for the store credit payment method.
	 *
	 * @NOTE this should probably be a helper method on the gateway itself. The frontend needs this data to avoid dealing with HTML.
	 * In the future we might allow merchants setting a custom description for the store credit CTA using similar placeholders. Consider moving/refactoring this then.
	 *
	 * @since 2.7.0
	 * @internal
	 *
	 * @param Gateway $gateway the store credit gateway instance
	 * @return array<string, mixed>
	 *
	 * @phpstan-ignore-next-line
	 */
	private function get_description_parts( Gateway $gateway ) : array {

		$description = $gateway->get_option( 'description', $gateway->get_default_description() );
		$description = str_replace( [ '{funds_amount}', '{available_funds}' ], '{store_credit_balance}', $description ); // legacy tag handling
		$balance     = Wallet::get( get_current_user_id() )->balance();

		return [
			'text'         => $description,
			'placeholders' => [
				'store_credit_balance' => (int) wc_add_number_precision( $balance ), // in cents
			],
		];
	}

	/**
	 * Gets the gateway instance if available.
	 *
	 * @since 3.1.0
	 *
	 * @return Gateway|null
	 */
	protected function get_gateway() : ?Gateway {

		/** @var array<string, WC_Payment_Gateway> $gateways */
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();

		if ( isset( $gateways[ Gateway::ID ] ) ) {
			/** @var Gateway $account_funds_gateway */
			$account_funds_gateway = $gateways[ Gateway::ID ];
		} else {
			$account_funds_gateway = null;
		}

		return $account_funds_gateway instanceof Gateway ? $account_funds_gateway : null;
	}

}

class_alias(
	__NAMESPACE__ . '\Block_Integration_Trait',
	'\Kestrel\WooCommerce\Account_Funds\Blocks\Integrations\Traits\Block_Integration_Trait',
	false
);
