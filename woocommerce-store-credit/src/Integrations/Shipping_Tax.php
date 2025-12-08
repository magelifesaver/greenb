<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Store_Credit\Integrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WordPress\Plugins;

/**
 * WooCommerce Shipping & Tax integration.
 *
 * @since 5.0.0
 */
final class Shipping_Tax implements Integration {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param Plugin $plugin
	 */
	protected function __construct( Plugin $plugin ) {

		self::$plugin = $plugin;

		self::add_filter( 'wc_store_credit_calculate_shipping_discounts_for_cart', [ $this, 'calculate_shipping_discounts_for_cart' ] );
	}

	/**
	 * Integration loads if the WooCommerce Services are active.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-services/woocommerce-services.php' );
	}

	/**
	 * Determines whether to calculate the shipping discounts for the specified cart.
	 *
	 * @since 5.0.0
	 *
	 * @param bool|mixed $calculate_discounts
	 * @return bool|mixed
	 */
	protected function calculate_shipping_discounts_for_cart( $calculate_discounts ) {

		if ( ! is_scalar( $calculate_discounts ) || empty( $calculate_discounts ) ) {
			return $calculate_discounts;
		}

		$backtrace  = wp_debug_backtrace_summary( 'WP_Hook', 0, false ); // phpcs:ignore
		$save_index = array_search( "do_action('woocommerce_after_calculate_totals')", $backtrace, true );

		/**
		 * WooCommerce Shipping & Tax calls again to the method {@see WC_Cart::calculate_totals()} inside the callback bound to the hook `woocommerce_after_calculate_totals`.
		 * So, we need to discard the nested call and apply the changes only in the call made by {@see WC_Cart::calculate_totals()}.
		 */
		if ( 'WC_Cart->calculate_totals' !== $backtrace[ $save_index + 1 ] ) {
			return false;
		}

		return $calculate_discounts;
	}

}
