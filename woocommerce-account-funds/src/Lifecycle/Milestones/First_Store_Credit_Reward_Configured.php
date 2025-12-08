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
 * Milestone triggered when the merchant configures their first credit source.
 *
 * @since 4.0.0
 */
final class First_Store_Credit_Reward_Configured extends Milestone {

	/** @var string */
	protected const ID = 'configured_first_store_credit_reward';

	/**
	 * Returns the milestone title.
	 *
	 * @since 4.0.0
	 *
	 * @return string
	 */
	protected function title() : string {

		return __( 'Awesome, your first store credit is live!', 'woocommerce-account-funds' );
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
			/* translators: Placeholders: %1$s - opening <a> HTML link tag, %2$s - closing </a> HTML link tag */
			__( 'Want a say in what we build? %1$sJoin our newsletter%2$s for roadmap updates, early access, and the occasional bird pun. Then reply to the welcome email and tell us what you’d love to see next.', 'woocommerce-account-funds' ),
			'<a href="https://www.kestrelwp.com/newsletter" target="_blank">',
			'</a>'
		);
	}

}
