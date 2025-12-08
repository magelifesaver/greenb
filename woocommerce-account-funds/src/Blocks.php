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

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Kestrel\Account_Funds\Blocks\Integrations\Account_Funds_Payment_Method;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Blocks as Base_Blocks_Handler;
use WP_Post;

/**
 * Blocks handler.
 *
 * @since 3.1.0
 *
 * @method static Plugin plugin()
 */
final class Blocks extends Base_Blocks_Handler {

	/** @var Account_Funds_Payment_Method|null */
	private ?Account_Funds_Payment_Method $payment_method_integration = null;

	/**
	 * Blocks handler constructor.
	 *
	 * @since 3.1.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		parent::__construct( $plugin );

		self::add_action( 'woocommerce_blocks_loaded', [ $this, 'register_woocommerce_block_integrations' ] );
	}

	/**
	 * Gets the checkout block integration instance as a block payment method.
	 *
	 * @since 3.1.0
	 *
	 * @return Account_Funds_Payment_Method
	 */
	private function get_payment_method_integration() : Account_Funds_Payment_Method {

		if ( ! $this->payment_method_integration instanceof IntegrationInterface ) {
			$this->payment_method_integration = new Account_Funds_Payment_Method( self::plugin() );
		}

		return $this->payment_method_integration;
	}

	/**
	 * Registers the WooCommerce Blocks integrations.
	 *
	 * @since 3.1.0
	 * @internal
	 *
	 * @return void
	 */
	protected function register_woocommerce_block_integrations() : void {

		$block_integration_instance = $this->get_payment_method_integration();

		add_action( 'woocommerce_blocks_payment_method_type_registration', function( $integration_registry ) use ( $block_integration_instance ) {
			$integration_registry->register( $block_integration_instance );
		} );
	}

	/**
	 * Determines if the checkout page is using the checkout block.
	 *
	 * @since 3.1.0
	 *
	 * @return bool false when using the legacy checkout shortcode
	 */
	public static function is_checkout_block_in_use() : bool {

		return class_exists( CartCheckoutUtils::class ) && CartCheckoutUtils::is_checkout_block_default();
	}

	/**
	 * Determines if a page contains the checkout block.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|WP_Post $page
	 * @return bool
	 */
	public static function page_contains_checkout_block( $page ) : bool {

		return has_block( 'woocommerce/checkout', $page );
	}

	/**
	 * Determines if a page contains a checkout shortcode.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|WP_Post $page
	 * @return bool
	 */
	public static function page_contains_checkout_shortcode( $page ) : bool {

		return self::page_contains_shortcode( '[woocommerce_checkout]', $page );
	}

	/**
	 * Determines if the cart page is using the cart block.
	 *
	 * @since 3.1.0
	 *
	 * @return bool false if using the legacy cart shortcode
	 */
	public static function is_cart_block_in_use() : bool {

		return class_exists( CartCheckoutUtils::class ) && CartCheckoutUtils::is_cart_block_default();
	}

	/**
	 * Determines if a page contains the cart block.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|WP_Post $page
	 * @return bool
	 */
	public static function page_contains_cart_block( $page ) : bool {

		return has_block( 'woocommerce/cart', $page );
	}

	/**
	 * Determines if a page contains a cart shortcode.
	 *
	 * @since 3.1.0
	 *
	 * @param int|string|WP_Post $page
	 * @return bool
	 */
	public static function page_contains_cart_shortcode( $page ) : bool {

		return self::page_contains_shortcode( '[woocommerce_cart]', $page );
	}

	/**
	 * Determines if a page contains a cart or checkout shortcode.
	 *
	 * @since 3.1.0
	 *
	 * @param string $shortcode
	 * @param int|string|WP_Post $page
	 * @return bool
	 */
	private static function page_contains_shortcode( string $shortcode, $page ) : bool {

		if ( is_numeric( $page ) || is_string( $page ) ) {
			$page = get_post( $page );
		}

		return $page instanceof WP_Post && has_shortcode( $page->post_content, $shortcode );
	}

}

class_alias(
	__NAMESPACE__ . '\Blocks',
	'\Kestrel\WooCommerce\Account_Funds\Blocks'
);
