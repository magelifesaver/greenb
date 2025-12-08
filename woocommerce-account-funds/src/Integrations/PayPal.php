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
use Kestrel\Account_Funds\Settings\Store_Credit_Label;
use WC_Order;

/**
 * PayPal integration.
 *
 * @since 3.1.0
 */
final class PayPal implements Integration {
	use Is_Handler;

	/**
	 * Initializes the integration.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	protected function __construct() {

		self::add_filter( 'woocommerce_paypal_args', [ $this, 'filter_paypal_line_item_names' ], 10, 2 );
	}

	/**
	 * Determines whether the integration should be initialized.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return true; // always load this integration
	}

	/**
	 * When store credit is applied, it causes an order totals mismatch with total from order items because we are filtering the order total based on store credit used.
	 *
	 * This filter adjust the line item name to indicate the amount is with tax and store credit applied already.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed>|mixed $paypal_args PayPal args
	 * @param mixed|WC_Order $order order object
	 * @return array<string, mixed>|mixed PayPal args
	 */
	protected function filter_paypal_line_item_names( $paypal_args, $order ) {

		if ( ! is_array( $paypal_args ) || ! $order instanceof WC_Order ) {
			return $paypal_args;
		}

		$funds_used = (float) $order->get_meta( '_funds_used' );

		if ( 0 >= $funds_used ) {
			return $paypal_args;
		}

		$item_indexes = $this->get_paypal_line_item_indexes( $paypal_args );

		foreach ( $item_indexes as $index ) {

			$key = 'item_name_' . $index;
			$val = $paypal_args[ $key ];

			/* translators: Placeholders: %1$s - Product name in cart (cart item), %2$s: Label for store credit (default: "Store credit") */
			$paypal_args[ $key ] = sprintf( __( '%1$s (with tax, discount, and %2$s applied)', 'woocommerce-account-funds' ), $val, Store_Credit_Label::plural()->lowercase()->to_string() );
		}

		return $paypal_args;
	}

	/**
	 * Get the item indexes from all PayPal items.
	 *
	 * Only indexes with existing name, amount and quantity are added.
	 *
	 * @since 3.1.0
	 *
	 * @param array<string, mixed>|mixed $paypal_args PayPal args
	 * @return array<string, mixed>|mixed item indexes
	 */
	private function get_paypal_line_item_indexes( $paypal_args ) {

		if ( ! is_array( $paypal_args ) ) {
			return $paypal_args;
		}

		$item_indexes = [];

		foreach ( $paypal_args as $key => $arg ) {

			if ( ! preg_match( '/item_name_/', $key ) ) {
				continue;
			}

			$index = str_replace( 'item_name_', '', $key );

			// make sure the item name, amount and quantity values exist
			if ( isset( $paypal_args[ 'amount_' . $index ], $paypal_args[ 'item_name_' . $index ], $paypal_args[ 'quantity_' . $index ] ) ) {
				$item_indexes[] = $index;
			}
		}

		return $item_indexes;
	}

}
