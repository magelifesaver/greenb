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

namespace Kestrel\Account_Funds\Lifecycle\Migrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;

/**
 * Migration for version 2.1.3.
 *
 * This updates order item meta, as WooCommerce v3.0 will invalidate order items with invalid product IDs.
 * Before 2.1.3, Account Funds stored `_product_id` with a value set to the ID of the my-account page instead.
 *
 * @since 3.1.0
 */
final class Upgrade_To_Version_2_1_3 implements Migration {

	/**
	 * Runs the upgrade.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() : void {
		global $wpdb;

		// if we don't have myaccount page, no need to proceed
		if ( wc_get_page_id( 'myaccount' ) <= 0 ) {
			return;
		}

		// add new AF top-up order item meta
		$res = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta " .
				'( order_item_id, meta_key, meta_value ) ' .
				'SELECT ids.order_item_id, "_top_up_amount", totals.meta_value ' .
				"FROM {$wpdb->prefix}woocommerce_order_itemmeta as ids " .
				"LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as totals " .
				'ON ids.order_item_id = totals.order_item_id ' .
				'WHERE ' .
				'ids.meta_key = "_product_id" AND ' .
				'ids.meta_value = %d AND ' .
				'totals.meta_key = "_line_subtotal"',
				wc_get_page_id( 'myaccount' )
			)
		);

		if ( ! $res ) {
			return;
		}

		// add item meta to indicate that this is top-up product
		$res = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta " .
				'( order_item_id, meta_key, meta_value ) ' .
				'SELECT order_item_id, "_top_up_product", "yes" ' .
				"FROM {$wpdb->prefix}woocommerce_order_itemmeta " .
				'WHERE ' .
				'meta_key = "_product_id" AND ' .
				'meta_value = %d',
				wc_get_page_id( 'myaccount' )
			)
		);

		if ( ! $res ) {
			return;
		}

		// update all product item ID to 0
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}woocommerce_order_itemmeta " .
				'SET meta_value = 0 ' .
				'WHERE meta_key = "_product_id" AND meta_value = %d',
				wc_get_page_id( 'myaccount' )
			)
		);
	}

}
