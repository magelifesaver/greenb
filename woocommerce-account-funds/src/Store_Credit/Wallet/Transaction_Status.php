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

/**
 * Enumerator for credit transaction statuses in the store credit ledger.
 *
 * @since 4.0.0
 *
 * @method static self committed()
 * @method static self voided()
 * @method static self expired()
 */
final class Transaction_Status {
	use Is_Enum;

	use Has_Named_Constructors;

	/** @var string */
	public const COMMITTED = 'committed'; // the transaction is commited and accounted in the customer balance

	/** @var string */
	public const VOIDED = 'voided'; // the transaction is voided and no longer part of the customer balance

	/** @var string */
	public const EXPIRED = 'expired'; // the transaction concerns store credit that has expired and is no longer part of the customer balance

	/** @var string default value */
	protected static string $default = self::COMMITTED;

	/**
	 * Returns the label for the current status.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	public function label() : string {

		switch ( $this->value() ) {
			case self::VOIDED:
				return __( 'Voided', 'woocommerce-account-funds' );
			case self::EXPIRED:
				return __( 'Expired', 'woocommerce-account-funds' );
			case self::COMMITTED:
			default:
				return __( 'Committed', 'woocommerce-account-funds' );
		}
	}

}
