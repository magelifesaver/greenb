<?php
/**
 * Plugin Name: Account Funds for WooCommerce
 * Plugin URI: https://woocommerce.com/products/account-funds/
 * Description: Allow customers to deposit funds into their accounts and pay with account funds during checkout.
 * Author: Kestrel
 * Author URI: http://kestrelwp.com
 * Text Domain: woocommerce-account-funds
 * Domain Path: /i18n/languages/
 * Version: 4.0.8
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Tested up to: 6.8.3
 * Requires Plugins: woocommerce
 * WC requires at least: 8.2
 * WC tested up to: 10.3.5
 * Copyright: (c) 2024-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    Kestrel
 * @copyright Copyright (c) 2015-2025 Kestrel Commerce LLC [hey@kestrelwp.com]
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * Woo: 18728:a6fcf35d3297c328078dfe822e00bd06

 */

namespace Kestrel;

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/vendor-scoped/aviary-autoload.php';

use Kestrel\Account_Funds\Plugin;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Loader;

/**
 * Account funds for WooCommerce loader.
 *
 * @since 1.0.0
 */
class Account_Funds extends Loader {

	/** @var string plugin name */
	const PLUGIN_NAME = 'Account Funds for WooCommerce';

	/** @var class-string<Plugin> plugin main class */
	const PLUGIN_MAIN_CLASS = Plugin::class;

	/** @var string plugin file path */
	const PLUGIN_FILE_PATH = __FILE__;

	/** @var string required PHP version */
	const MINIMUM_PHP_VERSION = '7.4';

	/** @var string required WordPress version */
	const MINIMUM_WP_VERSION = '6.2';

	/** @var string required WooCommerce version */
	const MINIMUM_WC_VERSION = '8.2';

	/**
	 * Plugin loader constructor.
	 *
	 * @since 3.2.0
	 *
	 * @param array<string, mixed> $args
	 */
	protected function __construct( $args = [] ) {

		$old_version = get_option( 'account_funds_version' );

		if ( ! empty( $old_version ) && is_string( $old_version ) ) {
			update_option( 'kestrel_account_funds_version', $old_version );
			delete_option( 'account_funds_version' );
		}

		parent::__construct( $args );
	}

}

Account_Funds::bootstrap();
