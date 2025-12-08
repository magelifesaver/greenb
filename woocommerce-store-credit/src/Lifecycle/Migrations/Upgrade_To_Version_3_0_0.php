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
 * Upgrade to version 3.0.0 migration class.
 *
 * @since 5.0.0
 */
final class Upgrade_To_Version_3_0_0 implements Migration {

	/**
	 * Updates the plugin to version 3.0.0.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		$this->migrate_settings();
		$this->update_orders_store_credit_version();
		$this->update_orders_credit_discounts();
		$this->update_coupons_metadata();
	}

	/**
	 * Migrates the plugin settings to the new version.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function migrate_settings() : void {

		$rename_settings = [
			'woocommerce_store_credit_show_my_account',
			'woocommerce_store_credit_individual_use',
		];

		foreach ( $rename_settings as $setting_name ) {
			$value = get_option( $setting_name );

			if ( false !== $value ) {
				add_option( str_replace( 'woocommerce_', 'wc_', $setting_name ), $value );
				delete_option( $setting_name );
			}
		}

		$before_tax = wc_string_to_bool( get_option( 'woocommerce_store_credit_apply_before_tax', 'no' ) );

		add_option( 'wc_store_credit_inc_tax', wc_bool_to_string( ! $before_tax ) );
		add_option( 'wc_store_credit_apply_to_shipping', wc_bool_to_string( ! $before_tax ) );

		delete_option( 'woocommerce_store_credit_apply_before_tax' );
		delete_option( 'woocommerce_delete_store_credit_after_usage' );
		delete_option( 'woocommerce_store_credit_coupons_retention' );
	}

	/**
	 * Updates the store credit version used to calculate the discounts for the specified order.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function update_orders_store_credit_version() : void {

		$order_ids = wc_get_orders(
			[
				'type'               => 'shop_order',
				'return'             => 'ids',
				'limit'              => -1,
				'store_credit_query' => [
					[
						'key'     => '_store_credit_used',
						'compare' => 'EXISTS',
					],
					[
						'key'     => '_store_credit_version',
						'compare' => 'NOT EXISTS',
					],
					[
						'key'     => '_store_credit_discounts',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		foreach ( $order_ids as $order_id ) {
			$order = wc_store_credit_get_order( $order_id ); // @phpstan-ignore-line

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$coupon_items = wc_get_store_credit_coupons_for_order( $order ); // @phpstan-ignore-line

			if ( ! empty( $coupon_items ) ) {
				$coupon_item = reset( $coupon_items );

				$order->update_meta_data( '_store_credit_before_tax', wc_bool_to_string( $coupon_item->get_discount_tax( 'edit' ) > 0 ) );
			}

			$order->update_meta_data( '_store_credit_version', '2.2' );
			$order->save();
		}
	}

	/**
	 * Updates the discounts applied by store credit coupons for the specified order.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function update_orders_credit_discounts() : void {

		$order_ids = wc_get_orders(
			[
				'limit'              => -1,
				'type'               => 'shop_order',
				'return'             => 'ids',
				'store_credit_query' => [
					[
						'key'     => '_store_credit_used',
						'compare' => 'EXISTS',
					],
					[
						'key'     => '_store_credit_discounts',
						'compare' => 'NOT EXISTS',
					],
					[
						'relation' => 'OR',
						[
							'key'     => '_store_credit_before_tax',
							'compare' => 'NOT EXISTS',
						],
						[
							'key'   => '_store_credit_before_tax',
							'value' => 'yes',
						],
					],
				],
			]
		);

		foreach ( $order_ids as $order_id ) {
			$order = wc_store_credit_get_order( $order_id ); // @phpstan-ignore-line

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$coupon_items = wc_get_store_credit_coupons_for_order( $order ); // @phpstan-ignore-line

			if ( empty( $coupon_items ) ) {
				return;
			}

			$discounts = [];

			foreach ( $coupon_items as $coupon_item ) {
				$discounts[ $coupon_item->get_code( 'edit' ) ] = [
					'cart'     => (string) $coupon_item->get_discount( 'edit' ),
					'cart_tax' => (string) $coupon_item->get_discount_tax( 'edit' ),
				];
			}

			$order->update_meta_data( '_store_credit_discounts', $discounts );
			$order->save();
		}
	}

	/**
	 * Updates the coupons metadata.
	 *
	 * Adds the global settings 'inc_tax' and 'apply_to_shipping' as metadata.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function update_coupons_metadata() : void {

		$coupon_ids = get_posts(
			[
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => [ 'publish', 'trash' ],
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'   => 'discount_type',
						'value' => 'store_credit',
					],
					[
						'key'     => 'store_credit_inc_tax',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		$metas = [
			'store_credit_inc_tax'           => wc_bool_to_string( get_option( 'wc_store_credit_inc_tax', 'no' ) ),
			'store_credit_apply_to_shipping' => wc_bool_to_string( get_option( 'wc_store_credit_apply_to_shipping', 'no' ) ),
		];

		foreach ( $coupon_ids as $coupon_id ) {
			// @phpstan-ignore-next-line
			if ( $coupon = wc_store_credit_get_coupon( $coupon_id ) ) {

				$order_ids = wc_store_credit_get_coupon_orders( $coupon ); // @phpstan-ignore-line

				if ( ! empty( $order_ids ) ) {
					$order_id   = reset( $order_ids );
					$before_tax = wc_store_credit_apply_before_tax( $order_id ); // @phpstan-ignore-line

					$metas['store_credit_inc_tax']           = wc_bool_to_string( ! $before_tax );
					$metas['store_credit_apply_to_shipping'] = wc_bool_to_string( ! $before_tax );
				}

				foreach ( $metas as $key => $value ) {
					if ( ! $coupon->meta_exists( $key ) ) {
						$coupon->add_meta_data( $key, $value, true );
					}
				}

				$coupon->save();
			}
		}
	}

}
