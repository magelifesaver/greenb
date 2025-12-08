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

namespace Kestrel\Store_Credit;

defined( 'ABSPATH' ) or exit;

use Kestrel\Store_Credit\Integrations\Avalara;
use Kestrel\Store_Credit\Integrations\PDF_Invoices_Packing_Slips;
use Kestrel\Store_Credit\Integrations\Shipping_Tax;
use Kestrel\Store_Credit\Lifecycle\Installer;
use Kestrel\Store_Credit\Lifecycle\Migrations\Upgrade_To_Version_2_4_0;
use Kestrel\Store_Credit\Lifecycle\Migrations\Upgrade_To_Version_3_0_0;
use Kestrel\Store_Credit\Lifecycle\Migrations\Upgrade_To_Version_5_0_0;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Extension;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\WooCommerce\Features\Feature;
use WC_Store_Credit;

/**
 * Store Credit plugin main class.
 *
 * @since 5.0.0
 */
final class Plugin extends Extension {

	/** @var string plugin ID */
	protected const ID = 'store_credit';

	/** @var string plugin version */
	protected const VERSION = '5.1.2';

	/** @var string plugin vendor */
	protected const VENDOR = 'Kestrel';

	/** @var string plugin text domain */
	protected const TEXT_DOMAIN = 'woocommerce-store-credit';

	/** @var string URL pointing to the plugin's documentation */
	protected const DOCUMENTATION_URL = 'https://woocommerce.com/document/woocommerce-store-credit/';

	/** @var string URL pointing to the plugin's support */
	protected const SUPPORT_URL = 'https://kestrelwp.com/contact/';

	/** @var string URL pointing to the plugin's sales page */
	protected const SALES_PAGE_URL = 'https://woocommerce.com/products/woocommerce-store-credit/';

	/** @var string URL pointing to the plugin's reviews */
	protected const REVIEWS_URL = 'https://woocommerce.com/products/woocommerce-store-credit/#reviews';

	/**
	 * Plugin constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $args = [] ) {

		parent::__construct( wp_parse_args( $args, [
			'blocks'       => [
				'handler' => Blocks::class,
			],
			'lifecycle'    => [
				'installer'  => Installer::class,
				'migrations' => [
					'2.4.0' => Upgrade_To_Version_2_4_0::class,
					'3.0.0' => Upgrade_To_Version_3_0_0::class,
					'5.0.0' => Upgrade_To_Version_5_0_0::class,
				],
			],
			'integrations' => [
				Avalara::class,
				PDF_Invoices_Packing_Slips::class,
				Shipping_Tax::class,
			],
			'woocommerce'  => [
				'supported_features' => [
					Feature::CART_CHECKOUT_BLOCKS,
					Feature::HPOS,
				],
			],
		] ) );
	}

	/**
	 * Returns the plugin name.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function name() : string {

		return __( 'Store Credit for WooCommerce', 'woocommerce-store-credit' );
	}

	/**
	 * Returns the plugin settings URL.
	 *
	 * @since 5.0.0
	 *
	 * @return string
	 */
	public function settings_url() : string {

		return admin_url( 'admin.php?page=wc-settings&tab=store_credit' );
	}

	/**
	 * Initializes the plugin.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	protected function initialize() : void {

		parent::initialize();

		$this->initialize_legacy();

		self::add_action( 'init', [ $this, 'initialize_endpoints' ] );
	}

	/**
	 * Initializes the plugin endpoints.
	 *
	 * @NOTE Consider moving this to a different handler in the future.
	 *
	 * @see Installer::activate() may to be enough to initialize the endpoints.
	 *
	 * @since 5.1.2
	 *
	 * @return void
	 */
	protected function initialize_endpoints() : void {

		$mask = ( function_exists( 'WC' ) && ! is_null( WC()->query ) ? WC()->query->get_endpoints_mask() : EP_PAGES ); // @phpstan-ignore-line

		add_rewrite_endpoint( 'store-credit', $mask );
	}

	/**
	 * Initializes the legacy plugin components.
	 *
	 * @NOTE The legacy constants below are deprecated and should not be relied upon.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	private function initialize_legacy() : void {

		if ( ! defined( 'WC_STORE_CREDIT_FILE' ) ) {
			define( 'WC_STORE_CREDIT_FILE', $this->absolute_file_path() );
		}

		require_once $this->absolute_dir_path() . '/legacy/functions.php';

		if ( ! class_exists( 'WC_Store_Credit' ) ) {
			require_once $this->absolute_dir_path() . '/legacy/includes/class-wc-store-credit.php';
		}

		if ( ! defined( 'WC_STORE_CREDIT_VERSION' ) ) {
			define( 'WC_STORE_CREDIT_VERSION', self::VERSION );
		}

		if ( ! defined( 'WC_STORE_CREDIT_PATH' ) ) {
			define( 'WC_STORE_CREDIT_PATH', plugin_dir_path( \WC_STORE_CREDIT_FILE ) );
		}

		if ( ! defined( 'WC_STORE_CREDIT_URL' ) ) {
			define( 'WC_STORE_CREDIT_URL', plugin_dir_url( \WC_STORE_CREDIT_FILE ) );
		}

		if ( ! defined( 'WC_STORE_CREDIT_BASENAME' ) ) {
			define( 'WC_STORE_CREDIT_BASENAME', plugin_basename( \WC_STORE_CREDIT_FILE ) );
		}

		WC_Store_Credit::instance(); // @phpstan-ignore-line
	}

}
