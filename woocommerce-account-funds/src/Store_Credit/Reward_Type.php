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

namespace Kestrel\Account_Funds\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Has_Named_Constructors;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Traits\Is_Enum;
use Kestrel\Account_Funds\Store_Credit\Rewards\Cashback;
use Kestrel\Account_Funds\Store_Credit\Rewards\Milestone;
use Kestrel\Account_Funds\Store_Credit\Wallet\Transaction_Event;

/**
 * Enumerator for types of store credit.
 *
 * @since 4.0.0
 *
 * @method static self cashback()
 * @method static self milestone()
 * @method static self reward()
 */
final class Reward_Type {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string */
	public const CASHBACK = 'cashback';

	/** @var string */
	public const MILESTONE = 'milestone';

	/** @var string */
	public const REWARD = 'reward';

	/** @var string default value */
	protected static string $default = self::REWARD;

	/**
	 * Returns the label for the store credit type.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label_singular() : string {

		switch ( $this->value() ) {
			case self::CASHBACK:
				return __( 'Cashback', 'woocommerce-account-funds' );
			case self::MILESTONE:
				return __( 'Milestone', 'woocommerce-account-funds' );
			default:
				return __( 'Reward', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Returns the plural label for the store credit type.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label_plural() : string {

		switch ( $this->value() ) {
			case self::CASHBACK:
				return __( 'Cashback', 'woocommerce-account-funds' );
			case self::MILESTONE:
				return __( 'Milestones', 'woocommerce-account-funds' );
			default:
				return __( 'Rewards', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Determines if the store credit type supports percentage values.
	 *
	 * @since 4.0.0
	 *
	 * @return bool
	 */
	public function supports_percentage_amount() : bool {

		return $this->value() === self::CASHBACK;
	}

	/**
	 * Returns the events that can award the type of store credit.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, Transaction_Event>
	 */
	public function awarded_on() : array {

		switch ( $this->value() ) {
			case self::CASHBACK:
				return [
					Transaction_Event::ORDER_PAID       => Transaction_Event::order_paid(),
					Transaction_Event::PRODUCT_PURCHASE => Transaction_Event::product_purchase(),
				];
			case self::MILESTONE:
				return [
					Transaction_Event::ACCOUNT_SIGNUP => Transaction_Event::account_signup(),
					Transaction_Event::PRODUCT_REVIEW => Transaction_Event::product_review(),
				];
			default:
				return [];
		}
	}

	/**
	 * Seeds a store credit instance according to current type.
	 *
	 * @since 4.0.0
	 *
	 * @param array<string, mixed> $args
	 * @return Reward
	 */
	public function seed( array $args = [] ) : Reward {

		switch ( $this->value() ) {
			case self::CASHBACK:
				return Cashback::seed( $args );
			case self::MILESTONE:
				return Milestone::seed( $args );
			case self::REWARD:
			default:
				return Reward::seed( $args );
		}
	}

}
