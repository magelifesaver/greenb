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

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Logger;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;

/**
 * Migration for version 3.2.0.
 *
 * This update fixes the non-stored '_funds_used' metadata on orders full-paid with funds and created with WooCommerce 4.7+ and Account Funds 2.3.x lower than 2.3.4.
 *
 * @since 3.2.0
 */
final class Upgrade_To_Version_2_3_7 implements Migration {

	/**
	 * Performs the upgrade.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		if ( 'yes' === get_option( 'wc_account_funds_skip_migration_237', 'no' ) ) {

			Logger::notice( 'Updating the plugin from an older version than 2.3.0. No need to execute the migration to 2.3.7.' );

			delete_option( 'wc_account_funds_skip_migration_237' );

			return;
		}

		// Identify the Orders that need to be fixed.
		$order_ids = wc_get_orders(
			[
				'type'           => 'shop_order',
				'limit'          => -1,
				'status'         => [ 'wc-processing', 'wc-completed' ],
				'return'         => 'ids',
				'payment_method' => 'accountfunds',
				'total'          => '0',
				'funds_query'    => [
					[
						'key'   => '_funds_removed',
						'value' => '1',
					],
					[
						'key'     => '_funds_used',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		if ( empty( $order_ids ) ) {
			return;
		}

		$header_text = <<<'EOT'
------------------------------------------------------------------------------
The funds used on these orders were not deducted from the customers' accounts:
------------------------------------------------------------------------------
EOT;
		Logger::notice( $header_text );

		$order_balances = [];

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			$order_total = round(
				$order->get_subtotal() +
				$order->get_cart_tax( 'edit' ) +
				$order->get_total_fees() +
				(float) $order->get_shipping_total( 'edit' ) +
				(float) $order->get_shipping_tax( 'edit' ) -
				(float) $order->get_discount_total(),
				wc_get_price_decimals()
			);

			$order_balances[ $order->get_id() ] = $order_total;

			// add the missing metadata
			$order->update_meta_data( '_funds_used', (string) $order_total );
			$order->save_meta_data();

			Logger::notice(
				sprintf(
					'Order ID: #%1$s, Funds Used: %2$s, Customer ID: #%3$s',
					$order_id,
					$order_total,
					$order->get_customer_id( 'edit' )
				)
			);
		}

		update_option( 'account_funds_update_2_3_7_fix_order_balances', $order_balances );
	}

}
