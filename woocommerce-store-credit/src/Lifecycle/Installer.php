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

namespace Kestrel\Store_Credit\Lifecycle;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Lifecycle\Installer as Base_Installer;

/**
 * Store Credit plugin installer.
 *
 * @since 5.0.0
 */
final class Installer extends Base_Installer {

	/**
	 * Performs installation routines.
	 *
	 * @see Plugin::initialize_endpoints()
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function activate() : void {

		$mask = ( function_exists( 'WC' ) && ! is_null( WC()->query ) ? WC()->query->get_endpoints_mask() : EP_PAGES ); // @phpstan-ignore-line

		add_rewrite_endpoint( 'store-credit', $mask );

		flush_rewrite_rules();
	}

}
