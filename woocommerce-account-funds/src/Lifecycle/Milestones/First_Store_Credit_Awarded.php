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

/**
 * Milestone triggered when a customer is awarded their first store credit.
 *
 * @since 4.0.0
 */
final class First_Store_Credit_Awarded extends Milestone {

	/** @var string */
	protected const ID = 'awarded_first_store_credit';

	/**
	 * Returns the milestone title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function title() : string {

		return __( 'Nice! A customer was awarded store credit!', 'woocommerce-account-funds' );
	}

	/**
	 * Returns the milestone notice message.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function message() : string {

		return sprintf(
			/* translators: Placeholders: %1$s - opening anchor tag, %2$s - closing anchor tag, %3$s - opening anchor tag, %4$s - closing anchor tag */
			__( 'They can use the store credit they earned to pay for an order. Excited? Having questions or feedback? %1$sLeave a review%2$s or %3$sget in touch with us%4$s!', 'woocommerce-account-funds' ),
			'<a href="' . self::plugin()->reviews_url() . '" target="_blank">',
			'</a>',
			'<a href="' . self::plugin()->support_url() . '" target="_blank">',
			'</a>'
		);
	}

}
