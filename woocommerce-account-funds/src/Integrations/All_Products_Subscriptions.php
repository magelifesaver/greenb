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

namespace Kestrel\Account_Funds\Integrations;

defined( 'ABSPATH' ) or exit;

use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Integrations\Contracts\Integration;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Traits\Is_Handler;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\WordPress\Plugins;

/**
 * WooCommerce All Products for Subscriptions integration class.
 *
 * @since 3.1.0
 */
final class All_Products_Subscriptions implements Integration {
	use Is_Handler;

	/**
	 * Constructor.
	 *
	 * @since 3.2.0
	 */
	protected function __construct() {

		self::add_filter( 'wcsatt_supported_product_types', [ $this, 'add_supported_product_types' ], 10, 3 );
	}

	/**
	 * Determines whether the integration should be initialized.
	 *
	 * @since 3.2.0
	 *
	 * @return bool
	 */
	public static function should_initialize() : bool {

		return Plugins::is_plugin_active( 'woocommerce-all-products-for-subscriptions/woocommerce-all-products-for-subscriptions.php' );
	}

	/**
	 * Filters the product types that support subscriptions.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed|string[] $types
	 * @return mixed|string[]
	 */
	protected function add_supported_product_types( $types ) {

		if ( ! is_array( $types ) ) {
			return $types;
		}

		$types[] = 'deposit';

		return $types;
	}

}
