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
 * Migration for version 2.0.9.
 *
 * @since 3.2.0
 */
final class Upgrade_To_Version_2_0_9 implements Migration {

	/**
	 * Runs the upgrade.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		$orders = $this->get_renewal_orders_paid_with_account_funds();

		foreach ( $orders as $order ) {
			$funds_used      = $order->get_meta( '_funds_used' );
			$recurring_total = $this->get_recurring_total( $order->get_parent_id() );
			$order_total     = $order->get_total( 'edit' );

			if ( $order_total > 0 ) {
				$order->set_total( 0 );
			}

			if ( $funds_used !== $recurring_total ) {
				$order->update_meta_data( '_funds_used', $recurring_total );
			}

			$order->save();
		}
	}

	/**
	 * Gets subscription renewal orders which original order paid with account funds.
	 *
	 * @since 3.2.0
	 *
	 * @return array<string, mixed> list of renewal orders
	 */
	private function get_renewal_orders_paid_with_account_funds() : array {

		return wc_get_orders(
			[
				'type'           => 'shop_subscription',
				'parent_exclude' => [ '0' ],
				'funds_query'    => [
					[
						'key'   => '_funds_removed',
						'value' => '1',
					],
					[
						'key'     => '_funds_used',
						'value'   => '0',
						'compare' => '>',
					],
				],
			]
		);
	}

	/**
	 * Get recurring total.
	 *
	 * @since 3.2.0
	 *
	 * @param int|numeric-string $parent_id Order parent ID
	 * @return string Recurring total
	 */
	private function get_recurring_total( $parent_id ) : string {

		$subscription    = wc_get_order( $parent_id );
		$recurring_total = $subscription->get_meta( '_wcs_migrated_order_recurring_total' );

		if ( ! $recurring_total ) {
			$recurring_total = $subscription->get_meta( '_order_recurring_total' );
		}

		return is_string( $recurring_total ) || is_numeric( $recurring_total ) ? (string) $recurring_total : '';
	}

}
