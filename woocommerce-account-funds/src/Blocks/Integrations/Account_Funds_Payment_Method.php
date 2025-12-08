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

namespace Kestrel\Account_Funds\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Kestrel\Account_Funds\Blocks;
use Kestrel\Account_Funds\Blocks\Integrations\Traits\Block_Integration_Trait;
use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Has_Plugin_Instance;

defined( 'ABSPATH' ) or exit;

/**
 * Store credit block integration as WooCommerce Checkout block payment method.
 *
 * This integration will be used to list store credit at checkout to allow customers to pay with their store credit balance.
 *
 * @since 3.1.0
 */
final class Account_Funds_Payment_Method extends AbstractPaymentMethodType {
	use Block_Integration_Trait;

	use Has_Plugin_Instance;

	/**
	 * Constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		$this->integration_block_name = 'checkout';
	}

	/**
	 * Gets the WooCommerce integration name.
	 *
	 * Implements {@see IntegrationInterface::get_name()} with a namespace.
	 * This is specific to WooCommerce blocks and used internally to register the integration.
	 * A corresponding store API route will be registered with this name, {@see Blocks::register_woocommerce_block_integrations()}.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return string
	 */
	public function get_name() {

		return "{$this->namespace}/account-funds-payment-method";
	}

	/**
	 * Determines whether the block integration is active.
	 *
	 * Normally for payment gateways this would check if the gateway is enabled and available.
	 * However, we need to run some front end logic even when it isn't.
	 *
	 * @see AbstractPaymentMethodType::is_active()
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	public function is_active() {

		return true; // default behavior, same as parent but implementing here as a reminder as to why we need to return true
	}

	/**
	 * Gets the payment method script handles for the frontend context.
	 *
	 * @see AbstractPaymentMethodType::get_payment_method_script_handles()
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {

		return [ $this->get_script_handle() ];
	}

	/**
	 * Gets the payment method data.
	 *
	 * @see AbstractPaymentMethodType::get_payment_method_data()
	 *
	 * @since 3.1.0
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data() {

		return $this->get_script_data();
	}

	/**
	 * Gets the payment method supported features.
	 *
	 * @see AbstractPaymentMethodType::get_supported_features()
	 *
	 * @since 3.1.0
	 *
	 * @return string[]
	 */
	public function get_supported_features() {

		$account_funds_gateway = $this->get_gateway();

		return $account_funds_gateway && ! empty( $account_funds_gateway->supports ) ? $account_funds_gateway->supports : parent::get_supported_features();
	}

}

class_alias(
	__NAMESPACE__ . '\Account_Funds_Payment_Method',
	'\Kestrel\WooCommerce\Account_Funds\Blocks\Integrations\Account_Funds_Payment_Method'
);
