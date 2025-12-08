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

namespace Kestrel\Account_Funds\Store_Credit\Wallet;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Named_Constructors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
use WC_Order;
use WC_Order_Refund;
use WC_Product;
use WP_Comment;
use WP_User;

/**
 * Enumerator for events.
 *
 * These events may trigger credit being awarded to a customer, or may link a transaction that decreased store credit in their wallet.
 *
 * @since 4.0.0
 *
 * @method static self user_action()
 * @method static self account_signup()
 * @method static self product_purchase()
 * @method static self product_review()
 * @method static self order_cancelled()
 * @method static self order_paid()
 * @method static self order_refunded()
 * @method static self migration()
 * @method static self undefined()
 */
final class Transaction_Event {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string a user performed a manual action */
	public const USER_ACTION = 'user_action';

	/** @var string guest has signed up for a customer account */
	public const ACCOUNT_SIGNUP = 'account_signup';

	/** @var string customer purchased a product */
	public const PRODUCT_PURCHASE = 'product_purchase';

	/** @var string customer reviewed a product */
	public const PRODUCT_REVIEW = 'product_review';

	/** @var string customer paid for an order (e.g. order is processing) */
	public const ORDER_PAID = 'order_paid';

	/** @var string customer had order refunded */
	public const ORDER_REFUNDED = 'order_refunded';

	/** @var string customer had order cancelled */
	public const ORDER_CANCELLED = 'order_cancelled';

	/** @var string reserved for data migrations, imports, etc. */
	public const MIGRATION = 'migration';

	/** @var string internal type for special or one-off events */
	public const UNDEFINED = 'undefined';

	/** @var string default value */
	protected static string $default = self::UNDEFINED;

	/**
	 * Returns the label for the credit award event.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label() : string {

		switch ( $this->value() ) {
			case self::USER_ACTION:
				return __( 'User action', 'woocommerce-account-funds' );
			case self::ACCOUNT_SIGNUP:
				return __( 'Customer account registration', 'woocommerce-account-funds' );
			case self::PRODUCT_PURCHASE:
				return __( 'Product purchase', 'woocommerce-account-funds' );
			case self::PRODUCT_REVIEW:
				return __( 'Product review', 'woocommerce-account-funds' );
			case self::ORDER_PAID:
				return __( 'Order paid', 'woocommerce-account-funds' );
			case self::ORDER_REFUNDED:
				return __( 'Order refunded', 'woocommerce-account-funds' );
			case self::ORDER_CANCELLED:
				return __( 'Order cancelled', 'woocommerce-account-funds' );
			case self::MIGRATION:
				return __( 'Data migration', 'woocommerce-account-funds' );
			case self::UNDEFINED:
			default:
				return __( 'Undefined event', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Returns the object for the given ID, related to the event trigger, if applicable.
	 *
	 * @since 4.0.0
	 *
	 * @param int|numeric-string|null $id
	 * @return WC_Order|WC_Order_Refund|WC_Product|WP_Comment|WP_User|null
	 */
	public function object( $id ) : ?object {

		// @phpstan-ignore-next-line
		if ( empty( $id ) || ! is_numeric( $id ) ) {
			return null;
		}

		$id    = intval( $id );
		$event = $this->value();

		switch ( $event ) {
			case self::ORDER_PAID:
			case self::ORDER_REFUNDED:
			case self::ORDER_CANCELLED:
				$order = wc_get_order( $id );

				if ( $event === self::ORDER_REFUNDED ) {
					return $order instanceof WC_Order_Refund ? $order : null;
				} elseif ( $order instanceof WC_Order && ! is_a( $order, 'WC_Subscription' ) ) {
					return $order;
				}

				return null;
			case self::PRODUCT_PURCHASE:
				return wc_get_product( $id ) ?: null;
			case self::PRODUCT_REVIEW:
				return get_comment( $id ) ?: null;
			case self::USER_ACTION:
			case self::ACCOUNT_SIGNUP:
				return get_user_by( 'id', $id ) ?: null;
			case self::MIGRATION:
			case self::UNDEFINED:
			default:
				return null;
		}
	}

}
