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
use WC_Order;

/**
 * Avalara (formerly AvaTax) integration.
 *
 * @since 5.0.0
 */
final class Avalara implements Integration {
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
		self::add_filter( 'wc_store_credit_discounts_order_tax_rate', [ $this, 'get_tax_rate_for_store_credit_discount' ], 10, 3 );
		self::add_filter( 'wc_avatax_api_tax_transaction_request_data', [ $this, 'exclude_store_credit_discount_lines_from_request' ] );
	}

	/**
	 * Integration loads if Avalara is active.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-avatax/woocommerce-avatax.php' );
	}

	/**
	 * Determines whether to calculate the shipping discounts for the specified cart.
	 *
	 * @since 5.0.0
	 *
	 * @param bool|mixed $calculate_discounts whether to calculate the shipping discounts
	 * @return bool|mixed
	 */
	protected function calculate_shipping_discounts_for_cart( $calculate_discounts ) {

		if ( ! is_scalar( $calculate_discounts ) || empty( $calculate_discounts ) ) {
			return $calculate_discounts;
		}

		$backtrace  = wp_debug_backtrace_summary( 'WP_Hook', 0, false ); // phpcs:ignore
		$save_index = array_search( "do_action('woocommerce_after_calculate_totals')", $backtrace, true );

		/**
		 * Avalara calculates the cart totals again inside the callback bound to the hook `woocommerce_after_calculate_totals`.
		 * So, we need to discard the nested call and apply the changes only in the call made by {@see WC_Cart::calculate_totals()}.
		 */
		if ( 'WC_Cart->calculate_totals' !== $backtrace[ $save_index + 1 ] || 0 === strpos( $backtrace[ $save_index + 2 ], 'WC_AvaTax_Checkout_Handler' ) ) {
			return false;
		}

		return $calculate_discounts;
	}

	/**
	 * Gets the data for an Avalara rate.
	 *
	 * @since 5.0.0
	 *
	 * @param array<string, mixed>|mixed $tax_rate tax rate data
	 * @param mixed|string $rate_id tax rate ID
	 * @param mixed|WC_Order $order order object
	 */
	protected function get_tax_rate_for_store_credit_discount( $tax_rate, $rate_id, $order ) {

		if ( ! $order instanceof WC_Order ) {
			return $tax_rate;
		}

		if ( is_string( $rate_id ) && 0 === strpos( $rate_id, 'AVATAX-' ) ) {
			$tax_items = $order->get_taxes();

			foreach ( $tax_items as $tax_item ) {
				if ( $rate_id === $tax_item->get_rate_code() ) {
					return [
						'name'              => $rate_id,
						'tax_rate'          => $tax_item->get_rate_percent(),
						'tax_rate_shipping' => ( 0 < $tax_item->get_shipping_tax_total() ),
						'tax_rate_compound' => $tax_item->get_compound(),
					];
				}
			}
		}

		return $tax_rate;
	}

	/**
	 * Filters the AvaTax transaction request data to exclude store credit discount lines.
	 *
	 * @since 5.0.0
	 *
	 * @param array<string, mixed>|mixed $data
	 * @return array<string, mixed>|mixed
	 */
	protected function exclude_store_credit_discount_lines_from_request( $data ) {

		if ( ! is_array( $data ) || ! isset( $data['lines'] ) || ! is_array( $data['lines'] ) ) {
			return $data;
		}

		foreach ( $data['lines'] as $key => $line ) {
			// exclude the shipping discount lines
			if ( 'store_credit_discount' === $line['itemCode'] ) {
				unset( $data['lines'][ $key ] );
			}
		}

		return $data;
	}

}
