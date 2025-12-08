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

/**
 * Enumerator for store credit status.
 *
 * @since 4.0.0
 *
 * @method static self active()
 * @method static self inactive()
 * @method static self depleted()
 */
final class Reward_Status {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string */
	public const ACTIVE = 'active';

	/** @var string */
	public const INACTIVE = 'inactive';

	/** @var string */
	public const DEPLETED = 'depleted';

	/** @var string default value */
	protected static string $default = self::INACTIVE;

	/**
	 * Returns the label for the store credit status.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label() : string {

		switch ( $this->value() ) {
			case self::ACTIVE:
				/* translators: Context: Store credit status */
				return __( 'Active', 'woocommerce-account-funds' );
			case self::DEPLETED:
				/* translators: Context: Store credit status */
				return __( 'Depleted', 'woocommerce-account-funds' );
			case self::INACTIVE:
			default:
				/* translators: Context: Store credit status */
				return __( 'Inactive', 'woocommerce-account-funds' );
		}
	}

	/**
	 * Returns a description of the store credit status.
	 *
	 * @since 4.0.0
	 *
	 * @param string|null $reward_type_label
	 * @return string
	 */
	public function description( ?string $reward_type_label = null ) : string {

		$reward_type_label = $reward_type_label ?: __( 'Store credit', 'woocommerce-account-funds' );

		switch ( $this->value() ) {
			case self::ACTIVE:
				/* translators: Placeholder: %s - Store credit type label */
				return sprintf( __( 'This %s configuration is active and will be awarded to customers when its conditions are met.', 'woocommerce-account-funds' ), strtolower( $reward_type_label ) );
			case self::DEPLETED:
				/* translators: Placeholder: %s - Store credit type label */
				return sprintf( __( 'This %s configuration has reached its award limit and will no longer be awarded to customers.', 'woocommerce-account-funds' ), strtolower( $reward_type_label ) );
			case self::INACTIVE:
			default:
				/* translators: Placeholder: %s - Store credit type label */
				return sprintf( __( 'This %s configuration is inactive and will not be awarded to customers.', 'woocommerce-account-funds' ), strtolower( $reward_type_label ) );
		}
	}

	/**
	 * Returns options for the store credit status.
	 *
	 * @since 4.0.0
	 *
	 * @return array<string, string>
	 */
	public static function options() : array {

		return [
			self::ACTIVE   => self::active()->label(),
			self::INACTIVE => self::inactive()->label(),
			self::DEPLETED => self::depleted()->label(),
		];
	}

}
