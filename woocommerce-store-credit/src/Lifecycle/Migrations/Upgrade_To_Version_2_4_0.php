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

namespace Kestrel\Store_Credit\Lifecycle\Migrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;
use WC_Order;

/**
 * Upgrade to version 2.4.0 migration class.
 *
 * @since 5.0.0
 */
final class Upgrade_To_Version_2_4_0 implements Migration {

	/**
	 * Updates the plugin to version 2.4.0.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		$this->sync_credit_used_by_orders();
		$this->set_store_credit_payment_method_to_orders();
		$this->clear_exhausted_coupons();
	}

	/**
	 * Temporarily stores the orders that need to synchronize the credit used.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function sync_credit_used_by_orders() : void {
		global $wpdb;

		// fetch coupons
		$orders_ids_with_coupons = $wpdb->get_col(
			"SELECT DISTINCT order_id
			 FROM {$wpdb->prefix}woocommerce_order_items
			 WHERE order_item_type = 'coupon'"
		);

		// filter orders by type and status
		$order_ids = wc_get_orders(
			[
				'type'     => 'shop_order',
				'return'   => 'ids',
				'status'   => [ 'wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed' ],
				'limit'    => -1,
				'post__in' => $orders_ids_with_coupons,
			]
		);

		foreach ( $order_ids as $order_id ) {

			$order = wc_store_credit_get_order( $order_id ); // @phpstan-ignore-line

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$credit_used = wc_get_store_credit_used_for_order( $order, 'per_coupon' ); // @phpstan-ignore-line

			if ( ! empty( $credit_used ) ) {
				return;
			}

			// fetch the 'store_credit' coupons
			$coupons = wc_get_store_credit_coupons_for_order( $order ); // @phpstan-ignore-line

			if ( ! empty( $coupons ) ) {
				$credit = [];

				foreach ( $coupons as $coupon ) {
					$credit[ $coupon->get_code( 'edit' ) ] = $coupon->get_discount( 'edit' );
				}

				/**
				 * Store the version used to calculate the discounts.
				 * If the `_store_credit_used` meta doesn't exists, it was created before version 2.2.
				 */
				$order->update_meta_data( '_store_credit_version', '2.1' );

				wc_update_store_credit_used_for_order( $order, $credit ); // @phpstan-ignore-line
			}
		}
	}

	/**
	 * Sets the payment method to 'Store credit' to the orders paid with a store credit coupon.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function set_store_credit_payment_method_to_orders() : void {

		$orders = wc_get_orders(
			[
				'type'               => 'shop_order',
				'status'             => [ 'wc-processing', 'wc-completed' ],
				'limit'              => -1,
				'store_credit_query' => [
					[
						'key'   => '_payment_method',
						'value' => '',
					],
					[
						'key'     => '_order_total',
						'value'   => 0,
						'compare' => '<=',
						'type'    => 'NUMERIC',
					],
					[
						'key'     => '_store_credit_used',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		if ( ! empty( $orders ) ) {
			/* translators: Context: Payment method */
			$payment_method = __( 'Store Credit', 'woocommerce-store-credit' );

			foreach ( $orders as $order ) {
				$order->update_meta_data( '_payment_method', $payment_method );
				$order->save_meta_data();
			}
		}
	}

	/**
	 * Clears the remaining credit from trashed store credit coupons.
	 *
	 * The coupons were trashed without decreasing the credit.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function clear_exhausted_coupons() : void {
		global $wpdb;

		$coupon_ids = get_posts(
			[
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'trash',
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => 'discount_type',
						'value' => 'store_credit',
					],
					[
						'key'     => 'coupon_amount',
						'value'   => 0,
						'compare' => '>',
					],
				],
			]
		);

		if ( ! empty( $coupon_ids ) ) {
			$wpdb->query( $wpdb->prepare( "
				UPDATE {$wpdb->postmeta} as metas
				SET meta_value = 0
				WHERE meta_key = 'coupon_amount'
				  AND metas.post_id IN ('" . implode( "','", array_fill( 0, count( $coupon_ids ), '%d' ) ) . "')
			", array_map( 'absint', $coupon_ids ) ) );
		}
	}

}
