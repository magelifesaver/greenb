<?php
/**
 * Kestrel Store Credit for WooCommerce
 *
 * This source file is subject to the GNU General Public License v3.0 that is bundled with this plugin in the file license.txt.
 *
 * Please do not modify this file if you want to upgrade this plugin to newer versions in the future.
 * If you want to customize this file for your needs, please review our developer documentation.
 * Join our developer program at https://kestrelwp.com/developers
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

declare( strict_types = 1 );

namespace Kestrel\Store_Credit\Lifecycle\Migrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Helpers\Strings;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Contracts\Migration;

/**
 * Upgrade to version 3.0.0 migration class.
 *
 * @since 5.0.0
 */
final class Upgrade_To_Version_5_0_0 implements Migration {

	/**
	 * Updates the plugin to version 3.0.0.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function upgrade() : void {

		$legacy_version = get_option( 'wc_store_credit_version' );
		$update_history = Lifecycle::get_update_history();

		// record update from the latest legacy version
		if ( empty( $update_history ) && Strings::is_semver( $legacy_version ) ) {
			Lifecycle::record_update( $legacy_version, '2.0.0' );
		}

		delete_option( 'wc_store_credit_version' );
		delete_option( 'wc_store_credit_db_version' );
	}

}
