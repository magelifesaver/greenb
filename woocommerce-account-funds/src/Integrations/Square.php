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

namespace Kestrel\Account_Funds\Integrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Plugins;
use WC_Order;
use WC_Order_Item_Tax;
use WC_Tax;

/**
 * WooCommerce Square integration class.
 *
 * @since 3.1.0
 */
final class Square implements Integration {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	protected function __construct() {

		self::add_filter( 'woocommerce_order_get_discount_total', [ $this, 'adjust_order_discount_total' ], 10, 2 );
	}

	/**
	 * Determines whether the integration should be initialized.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-square/woocommerce-square.php' );
	}

	/**
	 * Filters the 'discount_total' property for the order when store credits have been applied to it.
	 *
	 * @since 3.1.0
	 *
	 * @param float|int|mixed $total_discount
	 * @param mixed|WC_Order $order
	 * @return float|int|mixed
	 */
	protected function adjust_order_discount_total( $total_discount, $order ) {

		if ( ! $order instanceof WC_Order || ! is_numeric( $total_discount ) || 'square_credit_card' !== $order->get_payment_method() ) {
			return $total_discount;
		}

		$funds_used_for_order = (float) $order->get_meta( '_funds_used' );

		if ( 0 >= $funds_used_for_order ) {
			return $total_discount;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
		$backtrace  = wp_debug_backtrace_summary( 'WP_Hook', 0, false );
		$save_index = array_search( 'WC_Abstract_Order->get_discount_total', $backtrace, true );
		$callback   = $backtrace[ $save_index + 1 ];

		if ( 'WooCommerce\Square\Gateway\API\Requests\Orders->set_create_order_data' === $callback ) {

			$rates = $this->get_order_tax_rates( $order );

			// When multiple tax rates are applied to the items, the store credit amount without taxes cannot be calculated.
			// So we leave the Square extension to adjust the order total.
			if ( 1 >= count( $rates ) ) {

				$funds_tax = wc_round_tax_total( array_sum( WC_Tax::calc_tax( $funds_used_for_order, $rates, true ) ) );

				$total_discount += ( $funds_used_for_order - $funds_tax );
			}
		}

		return $total_discount;
	}

	/**
	 * Gets the order tax rates.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed|WC_Order $order
	 * @return array<int, array<string, mixed>>
	 */
	private function get_order_tax_rates( $order ) : array {

		$taxes = $order instanceof WC_Order ? $order->get_taxes() : [];
		$rates = [];

		foreach ( $taxes as $tax ) {

			// @phpstan-ignore-next-line sanity check
			if ( ! $tax instanceof WC_Order_Item_Tax ) {
				continue;
			}

			$rate_id = $tax->get_rate_id();
			$rate    = WC_Tax::_get_tax_rate( $rate_id );

			$rates[ $rate_id ] = [
				'rate'     => $rate['tax_rate'],
				'name'     => $rate['tax_rate_name'],
				'priority' => (int) $rate['tax_rate_priority'],
				'compound' => (bool) $rate['tax_rate_compound'],
				'order'    => (int) $rate['tax_rate_order'],
				'class'    => $rate['tax_rate_class'] ?: 'standard',
			];
		}

		return $rates;
	}

}
