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
 * PDF Invoices & Packing Slips integration.
 *
 * @since 5.0.0
 */
final class PDF_Invoices_Packing_Slips implements Integration {
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

		self::add_filter( 'woocommerce_order_get_discount_total', [ $this, 'get_order_discount_total' ], 20, 2 );
	}

	/**
	 * Integration loads if the PDF Invoices & Packing Slips plugin is active.
	 *
	 * @since 5.0.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php' );
	}

	/**
	 * Filters the order 'discount_total' value.
	 *
	 * @since 5.0.0
	 *
	 * @param float|mixed $discount_total discounted amount
	 * @param mixed|WC_Order $order the order instance
	 * @return float|mixed
	 */
	protected function get_order_discount_total( $discount_total, $order ) {

		$credit = $order instanceof WC_Order ? wc_get_store_credit_for_order( $order, false, 'cart' ) : null; // @phpstan-ignore-line

		if ( is_numeric( $discount_total ) && is_numeric( $credit ) && 0 < $credit ) {
			$backtrace = wp_debug_backtrace_summary( 'WP_Hook', 0, false ); // phpcs:ignore

			// restore Store Credit discount when displaying a PDF Invoice
			if ( false !== array_search( 'WPO\WC\PDF_Invoices\Documents\Order_Document_Methods->get_order_discount', $backtrace, true ) ) {
				$discount_total += $credit;
			}
		}

		return $discount_total;
	}

}
