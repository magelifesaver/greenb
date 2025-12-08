<?php
/**
 * Plugin Name: Store Credit for WooCommerce
 * Plugin URI: https://woocommerce.com/products/store-credit/
 * Description: Create "store credit" coupons for customers which are redeemable at checkout.
 * Author: Kestrel
 * Author URI: https://kestrelwp.com/
 * Text Domain: woocommerce-store-credit
 * Domain Path: /i18n/languages/
 * Version: 5.1.2
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 10.0.4
 * Woo: 18609:c4bf3ecec4146cb69081e5b28b6cdac4
 * Copyright: (c) 2012-2025 Kestrel [hey@kestrelwp.com]
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2012-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace Kestrel;

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor-scoped/aviary-autoload.php';

use Kestrel\Store_Credit\Plugin;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Loader;

/**
 * Store Credit plugin loader.
 *
 * @since 5.0.0
 */
class Store_Credit extends Loader {

	/** @var string plugin name */
	const PLUGIN_NAME = 'Kestrel Store Credit for WooCommerce';

	/** @var class-string<Plugin> plugin main class */
	const PLUGIN_MAIN_CLASS = Plugin::class;

	/** @var string plugin file path */
	const PLUGIN_FILE_PATH = __FILE__;

	/** @var string required PHP version */
	const MINIMUM_PHP_VERSION = '7.4';

	/** @var string required WordPress version */
	const MINIMUM_WP_VERSION = '6.0';

	/** @var string required WooCommerce version */
	const MINIMUM_WC_VERSION = '8.0';

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param array<string, mixed> $args
	 */
	public function __construct( $args = [] ) {

		$current_version = get_option( 'kestrel_store_credit_version' );
		$legacy_version  = ! $current_version ? get_option( 'wc_store_credit_version' ) : false;

		if ( $legacy_version ) {
			update_option( 'kestrel_store_credit_version', $legacy_version );
		}

		parent::__construct( $args );
	}

}

Store_Credit::bootstrap();
