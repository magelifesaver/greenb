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

namespace Kestrel\Account_Funds\Lifecycle\Milestones;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Milestone;
use WC_Order;

/**
 * Milestone achieved when a customer pays with store credit for the first time.
 *
 * @since 4.0.0
 */
final class Customer_Paid_With_Store_Credit extends Milestone {

	/** @var string */
	protected const ID = 'customer_paid_with_store_credit';

	/**
	 * Returns the milestone title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function title() : string {

		$order = $this->order();

		return sprintf(
			/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
			__( 'Success! %1$sA customer just paid for their order using store credit!%2$s', 'woocommerce-account-funds' ),
			$order ? '<a href="' . esc_url( $order->get_view_order_url() ) . '">' : '',
			$order ? '</a>' : ''
		);
	}

	/**
	 * Returns the milestone notice message.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function message() : string {

		$message  = __( 'Liking the experience so far? It helps us a bunch if you tell other WooCommerce users what made it work for you.', 'woocommerce-account-funds' );
		$message .= ' <a href="' . self::plugin()->reviews_url() . '" target="_blank">' . __( 'Leave a quick review', 'woocommerce-account-funds' ) . ' &rarr;</a>';

		return $message;
	}

	/**
	 * Returns the order the customer paid with store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return WC_Order|null
	 */
	private function order() : ?WC_Order {

		$data = $this->data();

		if ( ! isset( $data['order_id'] ) || ! is_numeric( $data['order_id'] ) ) {
			return null;
		}

		$order = wc_get_order( (int) $data['order_id'] );

		if ( ! $order instanceof WC_Order ) {
			return null;
		}

		return $order;
	}

	/**
	 * Determines if the milestone should be triggered.
	 *
	 * @since 4.0.0
	 *
	 * @param array $args
	 * @return bool
	 */
	protected static function should_trigger( array $args = [] ) : bool {

		return parent::should_trigger( $args ) && self::plugin()->is_new_installation();
	}

}
