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

namespace Kestrel\Account_Funds\Lifecycle;

defined ( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Installer as Base_Installer;

/**
 * Installer class.
 *
 * @since 3.2.0
 */
final class Installer extends Base_Installer {

	/**
	 * Routines to run on plugin activation.
	 *
	 * @see Plugin::initialize_endpoints()
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function activate() : void {

		$endpoint = get_option( 'woocommerce_myaccount_account_funds_endpoint', 'account-funds' );

		add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES ); // @phpstan-ignore-line WP constants

		flush_rewrite_rules();
	}

}
