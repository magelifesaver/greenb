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

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;

defined( 'ABSPATH' ) or exit;

/**
 * Migration for version 3.2.0.
 *
 * @since 3.2.0
 */
final class Upgrade_To_Version_3_2_0 implements Migration {

	/**
	 * Upgrades the plugin to version 3.2.0.
	 *
	 * @since 3.2.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		update_option( 'account_funds_enable_funds_on_register', ( (float) get_option( 'account_funds_add_on_register' ) > 0 ? 'yes' : 'no' ) );
	}

}
